<?php
namespace app\admin\controller;

use think\Db;

class Menu extends Admin
{
	public function index()
	{
		$pid = input('param.pid', 0);

		if ($pid) {
			$data = Db::name('Menu')->where(['id'=> $pid])->field(true)->find();
			$this->assign('data', $data);
		}

		$title = trim(input('param.title'));
		$type = config('CONFIG_GROUP_LIST');
		$all_menu = Db::name('Menu')->column('id,title');
		$map['pid'] = $pid;

		if ($title) {
			$map['title'] = array('like', '%' . $title . '%');
		}

		$list = Db::name('Menu')->where($map)->field(true)->order('sort asc,id asc')->select();
		int_to_string($list, array(
			'hide'   => array(1 => '是', 0 => '否'),
			'is_dev' => array(1 => '是', 0 => '否')
			));

		if ($list) {
			foreach ($list as &$key) {
				if ($key['pid']) {
					$key['up_title'] = $all_menu[$key['pid']];
				}
			}

			$this->assign('list', $list);
		}

		Cookie('__forward__', $_SERVER['REQUEST_URI']);
		$this->meta_title = '菜单列表';
		return $this->fetch();
	}

	public function add()
	{
		if (IS_POST) {
			$Menu = model('Menu');
			$data = $Menu->create();

			if ($data) {
				$id = $Menu->insert();

				if ($id) {
					action_log('update_menu', 'Menu', $id, UID);
					$this->success('新增成功', Cookie('__forward__'));
				} else {
					$this->error('新增失败');
				}
			} else {
				$this->error($Menu->getError());
			}
		} else {
			$this->assign('info', array('pid' => input('pid')));
			$menus = Db::name('Menu')->field(true)->select();
			$menus = model('Tree')->toFormatTree($menus);
			$menus = array_merge(array(
				array('id' => 0, 'title_show' => '顶级菜单')
				), $menus);
			$this->assign('Menus', $menus);
			$this->meta_title = '新增菜单';
			$this->display('edit');
		}
	}

	public function edit()
	{
        $id = input('id/d');
		if (IS_POST) {
			$Menu = model('Menu');
			$data = $Menu->create();

			if ($data) {
				if ($Menu->update() !== false) {
					action_log('update_menu', 'Menu', $data['id'], UID);
					$this->success('更新成功', Cookie('__forward__'));
				} else {
					$this->error('更新失败');
				}
			} else {
				$this->error($Menu->getError());
			}
		} else {
			$info = array();
			$info = Db::name('Menu')->field(true)->find($id);
			$menus = Db::name('Menu')->field(true)->select();
			$menus = model('Tree')->toFormatTree($menus);
			$menus = array_merge(array(
				array('id' => 0, 'title_show' => '顶级菜单')
				), $menus);
			$this->assign('Menus', $menus);

			if (false === $info) {
				$this->error('获取后台菜单信息错误');
			}

			$this->assign('info', $info);
			$this->meta_title = '编辑后台菜单';
			return $this->fetch();
		}
	}

	public function del()
	{
		$id = array_unique((array) input('id', 0));
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$map = ['id' => ['in', $id]];

		if (Db::name('Menu')->where($map)->delete()) {
			action_log('update_menu', 'Menu', $id, UID);
			$this->success('删除成功');
		} else {
			$this->error('删除失败！');
		}
	}

	public function toogleHide()
	{
        $id = input('id/d');
        $value = input('value','1');
		$this->editRow('Menu', array('hide' => $value), array('id' => $id));
	}

	public function toogleDev($id, $value = 1)
	{
        $id = input('id/d');
        $value = input('value','1');
		$this->editRow('Menu', array('is_dev' => $value), array('id' => $id));
	}

	public function importFile()
	{
        $tree = input('tree');
        $pid = input('pid','0');
		if ($tree == null) {
			$file = APP_PATH . 'Admin/Conf/Menu.php';
			$tree = require_once $file;
		}

		$menuModel = model('Menu');

		foreach ($tree as $value) {
			$add_pid = $menuModel->insert(array('title' => $value['title'], 'url' => $value['url'], 'pid' => $pid, 'hide' => isset($value['hide']) ? (int) $value['hide'] : 0, 'tip' => isset($value['tip']) ? $value['tip'] : '', 'group' => $value['group']),false,true);

			if ($value['operator']) {
				$this->import($value['operator'], $add_pid);
			}
		}
	}

	public function import()
	{
		if (IS_POST) {
			$tree = input('post.tree');
			$lists = explode(PHP_EOL, $tree);
			$menuModel = Db::name('Menu');

			if ($lists == array()) {
				$this->error('请按格式填写批量导入的菜单，至少一个菜单');
			} else {
				$pid = input('post.pid');

				foreach ($lists as $key => $value) {
					$record = explode('|', $value);

					if (count($record) == 2) {
						$menuModel->insert(array('title' => $record[0], 'url' => $record[1], 'pid' => $pid, 'sort' => 0, 'hide' => 0, 'tip' => '', 'is_dev' => 0, 'group' => ''));
					}
				}

				$this->success('导入成功', url('index?pid=' . $pid));
			}
		} else {
			$this->meta_title = '批量导入后台菜单';
			$pid = (int) input('param.pid');
			$this->assign('pid', $pid);
			$data = Db::name('Menu')->where(['id'=> $pid])->field(true)->find();
			$this->assign('data', $data);
			return $this->fetch();
		}
	}

	public function sort()
	{
		if (IS_GET) {
			$ids = input('param.ids');
			$pid = input('param.pid');
			$map = array(
				'status' => array('gt', -1)
				);

			if (!empty($ids)) {
				$map['id'] = array('in', $ids);
			} else if ($pid !== '') {
				$map['pid'] = $pid;
			}

			$list = Db::name('Menu')->where($map)->field('id,title')->order('sort asc,id asc')->select();
			$this->assign('list', $list);
			$this->meta_title = '菜单排序';
			return $this->fetch();
		} else if (IS_POST) {
			$ids = input('post.ids');
			$ids = explode(',', $ids);

			foreach ($ids as $key => $value) {
				$res = Db::name('Menu')->where(array('id' => $value))->setField('sort', $key + 1);
			}

			if ($res !== false) {
				$this->success('排序成功！');
			} else {
				$this->eorror('排序失败！');
			}
		} else {
			$this->error('非法请求！');
		}
	}
}

?>