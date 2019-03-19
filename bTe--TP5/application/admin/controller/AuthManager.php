<?php
namespace app\admin\controller;

use think\Db;

class AuthManager extends Admin
{
	public function index()
	{
		$list = $this->lists('AuthGroup', array('module' => 'admin'), 'id asc');
		$list = int_to_string($list);
		$this->assign('_list', $list);
		$this->assign('_use_tip', true);
		$this->meta_title = '权限管理';
		$this->display('index');
	}

	protected function updateRules()
	{
		$nodes = $this->returnNodes(false);
		$AuthRule = Db::name('AuthRule');
		$map = array(
			'module' => 'admin',
			'type'   => array('in', '1,2')
			);
		$rules = $AuthRule->where($map)->order('name')->select();
		$data = array();

		foreach ($nodes as $value) {
			$temp['name'] = $value['url'];
			$temp['title'] = $value['title'];
			$temp['module'] = 'admin';

			if (0 < $value['pid']) {
				$temp['type'] = \app\common\model\AuthRuleModel::RULE_URL;
			}
			else {
				$temp['type'] = \app\common\model\AuthRuleModel::RULE_MAIN;
			}

			$temp['status'] = 1;
			$data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp;
		}

		$update = array();
		$ids = array();

		foreach ($rules as $index => $rule) {
			$key = strtolower($rule['name'] . $rule['module'] . $rule['type']);

			if (isset($data[$key])) {
				$data[$key]['id'] = $rule['id'];
				$update[] = $data[$key];
				unset($data[$key]);
				unset($rules[$index]);
				unset($rule['condition']);
				$diff[$rule['id']] = $rule;
			}
			else if ($rule['status'] == 1) {
				$ids[] = $rule['id'];
			}
		}

		if (count($update)) {
			foreach ($update as $k => $row) {
				if ($row != $diff[$row['id']]) {
					$AuthRule->where(array('id' => $row['id']))->update($row);
				}
			}
		}

		if (count($ids)) {
			$AuthRule->where(array(
				'id' => array('IN', implode(',', $ids))
				))->update(array('status' => -1));
		}

		if (count($data)) {
			$AuthRule->addAll(array_values($data));
		}

		if ($AuthRule->getDbError()) {
			trace('[' . 'Admin\\Controller\\AuthManagerController::updateRules' . ']:' . $AuthRule->getDbError());
			return false;
		}
		else {
			return true;
		}
	}

	public function createGroup()
	{
		if (empty($this->auth_group)) {
			$this->assign('auth_group', array('title' => null, 'id' => null, 'description' => null, 'rules' => null));
		}

		$this->meta_title = '新增用户组';
		$this->display('editgroup');
	}

	public function editGroup()
	{
		$auth_group = Db::name('AuthGroup')->where(array('module' => 'admin', 'type' => \app\common\model\AuthGroup::TYPE_ADMIN))->find((int) $_GET['id']);
		$this->assign('auth_group', $auth_group);
		$this->meta_title = '编辑用户组';
		return $this->fetch();
	}

	public function access()
	{
		$this->updateRules();
		$auth_group = Db::name('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => \app\common\model\AuthGroup::TYPE_ADMIN
			))->column('id,id,title,rules');
		$node_list = $this->returnNodes();
		$map = array('module' => 'admin', 'type' => \app\common\model\AuthRule::RULE_MAIN, 'status' => 1);
		$main_rules = Db::name('AuthRule')->where($map)->value('name,id');
		$map = array('module' => 'admin', 'type' => \app\common\model\AuthRule::RULE_URL, 'status' => 1);
		$child_rules = Db::name('AuthRule')->where($map)->value('name,id');
		$this->assign('main_rules', $main_rules);
		$this->assign('auth_rules', $child_rules);
		$this->assign('node_list', $node_list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) input('param.group_id')]);
		$this->meta_title = '访问授权';
		$this->display('managergroup');
	}

	public function writeGroup()
	{
        $_POST = input('post.');
		if (isset($_POST['rules'])) {
			sort($_POST['rules']);
			$_POST['rules'] = implode(',', array_unique($_POST['rules']));
		}

		$_POST['module'] = 'admin';
		$_POST['type'] = \app\common\model\AuthGroup::TYPE_ADMIN;
		$AuthGroup = model('AuthGroup');
		$data = $AuthGroup->create();

		if ($data) {
			if (empty($data['id'])) {
				$r = $AuthGroup->insert();
			}
			else {
				$r = $AuthGroup->update();
			}

			if ($r === false) {
				$this->error('操作失败' . $AuthGroup->getError());
			}
			else {
				$this->success('操作成功!', url('index'));
			}
		}
		else {
			$this->error('操作失败' . $AuthGroup->getError());
		}
	}

	public function changeStatus($method = NULL)
	{
		if (empty($_REQUEST['id'])) {
			$this->error('请选择要操作的数据!');
		}

		switch (strtolower($method)) {
		case 'forbidgroup':
			$this->forbid('AuthGroup');
			$this->success('操作成功', '/');
			break;

		case 'resumegroup':
			$this->resume('AuthGroup');
			$this->success('操作成功', '/');
			break;

		case 'deletegroup':
			$this->delete('AuthGroup');
			$this->success('操作成功', '/');
			break;

		default:
			$this->error($method . '参数非法');
		}
	}

	public function user($group_id)
	{
        $group_id =input('group_id');
		if (empty($group_id)) {
			$this->error('参数错误');
		}

		$auth_group = Db::name('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => \app\common\model\AuthGroup::TYPE_ADMIN
			))->column('id,id,title,rules');
		$prefix = config('database.prefix');
		$l_table = $prefix . \app\common\model\AuthGroup::MEMBER;
		$r_table = $prefix . \app\common\model\AuthGroup::AUTH_GROUP_ACCESS;
		$model = Db::table($l_table . ' m')->join($r_table . ' a',' m.id=a.uid');
		$_REQUEST = array();
		$list = $this->lists($model, array(
			'a.group_id' => $group_id,
			'm.status'   => array('egt', 0)
			), 'm.id asc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status');
		int_to_string($list);
		$this->assign('_list', $list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) input('param.group_id')]);
		$this->meta_title = '成员授权';
		return $this->fetch();
	}

	public function category()
	{
		$auth_group = Db::name('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => \app\common\model\AuthGroupl::TYPE_ADMIN
			))->column('id,id,title,rules');
		$group_list = model('Category')->getTree();
		$authed_group = \app\common\model\AuthGroup::getCategoryOfGroup(input('group_id'));
		$this->assign('authed_group', implode(',', (array) $authed_group));
		$this->assign('group_list', $group_list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) input('param.group_id')]);
		$this->meta_title = '分类授权';
		return $this->fetch();
	}

	public function tree($tree = NULL)
	{
		$this->assign('tree', $tree);
		$this->display('tree');
	}

	public function group()
	{
		$uid = input('uid');
		$auth_groups = model('AuthGroup')->getGroups();
		$user_groups = \app\common\model\AuthGroup::getUserGroup($uid);
		$ids = array();

		foreach ($user_groups as $value) {
			$ids[] = $value['group_id'];
		}

		$nickname = model('Member')->getNickName($uid);
		$this->assign('nickname', $nickname);
		$this->assign('auth_groups', $auth_groups);
		$this->assign('user_groups', implode(',', $ids));
		return $this->fetch();
	}

	public function addToGroup()
	{
		$uid = input('uid');

		if (empty($uid)) {
			$this->error('请输入后台成员信息');
		}

		if (!check($uid, 'd')) {
			$user = Db::name('Admin')->where(array('username' => $uid))->find();

			if (!$user) {
				$user = Db::name('Admin')->where(array('nickname' => $uid))->find();
			}

			if (!$user) {
				$user = Db::name('Admin')->where(array('moble' => $uid))->find();
			}

			if (!$user) {
				$this->error('用户不存在(id 用户名 昵称 手机号均可)');
			}

			$uid = $user['id'];
		}

		$gid = input('group_id');

		if ($res = Db::name('AuthGroupAccess')->where(array('uid' => $uid))->find()) {
			if ($res['group_id'] == $gid) {
				$this->error('已经存在,请勿重复添加');
			}
			else {
				$res = Db::name('AuthGroup')->where(array('id' => $gid))->find();

				if (!$res) {
					$this->error('当前组不存在');
				}

				$this->error('已经存在[' . $res['title'] . ']组,不可重复添加');
			}
		}

		$AuthGroup = model('AuthGroup');

		if (is_numeric($uid)) {
			if (is_administrator($uid)) {
				$this->error('该用户为超级管理员');
			}

			if (!Db::name('Admin')->where(array('id' => $uid))->find()) {
				$this->error('管理员用户不存在');
			}
		}

		if ($gid && !$AuthGroup->checkGroupId($gid)) {
			$this->error($AuthGroup->error);
		}

		if ($AuthGroup->addToGroup($uid, $gid)) {
			$this->success('操作成功');
		}
		else {
			$this->error($AuthGroup->getError());
		}
	}

	public function removeFromGroup()
	{
		$uid = input('uid');
		$gid = input('group_id');

		if ($uid == UID) {
			$this->error('不允许解除自身授权');
		}

		if (empty($uid) || empty($gid)) {
			$this->error('参数有误');
		}

		$AuthGroup = model('AuthGroup');

		if (!$AuthGroup->find($gid)) {
			$this->error('用户组不存在');
		}

		if ($AuthGroup->removeFromGroup($uid, $gid)) {
			$this->success('操作成功');
		}
		else {
			$this->error('操作失败');
		}
	}

	public function addToCategory()
	{
		$cid = input('cid');
		$gid = input('group_id');

		if (empty($gid)) {
			$this->error('参数有误');
		}

		$AuthGroup = model('AuthGroup');

		if (!$AuthGroup->find($gid)) {
			$this->error('用户组不存在');
		}

		if ($cid && !$AuthGroup->checkCategoryId($cid)) {
			$this->error($AuthGroup->error);
		}

		if ($AuthGroup->addToCategory($gid, $cid)) {
			$this->success('操作成功');
		}
		else {
			$this->error('操作失败');
		}
	}

	public function addToModel()
	{
		$mid = input('id');
		$gid = input('param.group_id');

		if (empty($gid)) {
			$this->error('参数有误');
		}

		$AuthGroup = model('AuthGroup');

		if (!$AuthGroup->find($gid)) {
			$this->error('用户组不存在');
		}

		if ($mid && !$AuthGroup->checkModelId($mid)) {
			$this->error($AuthGroup->error);
		}

		if ($AuthGroup->addToModel($gid, $mid)) {
			$this->success('操作成功');
		}
		else {
			$this->error('操作失败');
		}
	}
}

?>