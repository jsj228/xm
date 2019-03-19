<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;

class User extends Admin
{
    public function index()
    {
        $name = input('name/s', NULL);
        $field = input('field/s', NULL);
        $status = input('status/d', NULL);
        $address = input('address/s', NULL);

        $where=" 1 ";
        if ($field && $name) {
            $where = "`".$field."`='".$name."'";
        }
        if ($status) {
            if($status>2){
                switch($status){
                    case "3":
                        $where = $where." and `idcardauth`=1 ";
                        break;
                    case "4":
                        $where = $where."and idcardimg1 != '' and idcardauth=0";
                        break;
                }

            } else {
                $where = $where." and `status`=".($status-1);
            }
        }
        if($address){
            $userid = Db::name('UserCoin')->where(['vbcb' => $address])->value('userid');
            if($userid) {
                $where = $where . " and `id` = " . $userid;
            }
        }

        $list = Db::name('User')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['invit1'] = Db::name('User')->where(array('id' => $item['invit_1']))->value('username');
            $item['invit2'] = Db::name('User')->where(array('id' => $item['invit_2']))->value('username');
            $item['invit3'] = Db::name('User')->where(array('id' => $item['invit_3']))->value('username');
            $item['vbcb'] = Db::name('UserCoin')->where(array('userid' => $item['id']))->value('vbcb');
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function edit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $user = Db::name('User')->where(array('id' => trim($id)))->find();
                $vbcb = Db::name('UserCoin')->where(array('userid' => trim($id)))->value('vbcb');
                $user['vbcb'] = $vbcb;
                if (!empty($user['statusinfo'])){
                    $user['statusinfo'] = explode(',',$user['statusinfo']);
                }
                $this->data = $user;
            }

            $imgstr = "";
            if(!empty($user['idcardimg1'])){
                $img_arr = explode("_",$user['idcardimg1']);

                foreach($img_arr as $k=>$v){
                    $imgstr = $imgstr.'<img src="'.config('view_replace_str.__DOMAIN__').'/Upload/newcard/'.$v.'"  style="width:200px;height:100px;" />';
                }

                unset($img_arr);
            }

            $this->assign('data',$this->data);
            $this->assign('userimg', $imgstr);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            if ($_POST['password']) {
                $_POST['password'] = md5($_POST['password']);
            } else {
                unset($_POST['password']);
            }

            if ($_POST['paypassword']) {
                $_POST['paypassword'] = md5($_POST['paypassword']);
            } else {
                unset($_POST['paypassword']);
            }

            $_POST['mobletime'] = strtotime($_POST['mobletime']);

            //增加 和 编辑 限制唯一用户名和手机号码
            $userbyname = Db::name('User')->where(array('username' => $_POST['username']))->find();
            $userbymoble = Db::name('User')->where(array('moble' => $_POST['moble']))->find();

            Db::startTrans();
            $rs = [];
            if (isset($_POST['id'])) {
                if($userbyname && $userbyname['id'] != $_POST['id']){
                    Db::rollback();
                    $this->error('用户名已存在！');
                }
                if($userbymoble && $userbymoble['id'] != $_POST['id']){
                    Db::rollback();
                    $this->error('手机号码已存在！');
                }


                $statusinfo = Db::name('User')->lock(true)->where(['id' => $_POST['id']])->value('statusinfo');

                $rs[] = Db::table('weike_user_coin')->lock(true)->where(['userid' => $_POST['id']])->update(['vbcb' => $_POST['vbcb']]);
                unset($_POST['vbcb']);
                unset($_POST['mobletime']);
                if (empty($statusinfo)) {
                    $rs[] = Db::table('weike_user')->where(['id' => $_POST['id']])->update($_POST);
                } else {
                    $statusinfo = explode(',', $statusinfo);
                    $post_status = trim($_POST['statusinfo']);
                    if ($post_status) {
                        array_unshift($statusinfo, $_POST['statusinfo']);
                    }
                    $_POST['statusinfo'] = implode(',', $statusinfo);
                    $rs[] = Db::table('weike_user')->where(['id' => $_POST['id']])->update($_POST);
                }

//                $rs[] = Db::table('weike_user')->where(['id' => $_POST['id']])->update($_POST);
                $log = '管理员 ' . session('admin_username') . ' 修改了用户 ID '. $_POST['id'] .' 的信息 SQL 语句为： ' . db()->getLastSql();
                mlog($log);
            } else {
                if ($userbyname) {
                    Db::rollback();
                    $this->error('用户名已存在！');
                }
                if ($userbymoble) {
                    Db::rollback();
                    $this->error('手机号码已存在！');
                }

                //过滤数据表中不存在的字段
                $tb_fields=Db::table('weike_user')->getTableFields();
                foreach ($_POST as $key=>$val){
                    if(!in_array($key,$tb_fields)){
                        unset($_POST[$key]);
                    }
                }
                $rs[] = Db::table('weike_user')->insertGetId($_POST);
                $log = '管理员 ' . session('admin_username') . ' 增加了用户 ID ' . $rs[0] . ' 的信息 SQL 语句为： ' . db()->getLastSql();
                mlog($log);
                $rs[] = Db::table('weike_user_coin')->insert(array('userid' => $rs[0]));
            }

            if (check_arr($rs)) {
                Db::commit();
                $this->success('编辑成功！');
            } else {
                Db::rollback();
                $this->error('编辑失败！');
            }
        }
    }

    public function status()
    {
        $id = input('id/a', NULL);
        $type = input('param.type/s', NULL);
        $moble = input('param.moble/s','User');

        if (empty($id)) {
            $this->error('请选择会员！');
        }

        if (empty($type)) {
            $this->error('参数错误！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $_where = array(
                        'userid' => $where['id'],
                    );
                    $log = '管理员 ' . session('admin_username') . ' 删除了用户 ID ' . $id[0] . ' 的信息 SQL 语句为： ' . db()->getLastSql();
                    mlog($log);
                    Db::name('UserCoin')->where($_where)->delete();
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            case 'idauth':
                $data = array('idcardauth' => 1, 'endtime' => time(),'czr' => session('admin_username'));
                break;

            case 'notidauth':
                $data = array('idcardauth' => 0, 'idcardimg1' => '');
                break;

            default:
                $this->error('操作失败！');
        }


        if (false !== Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function admin( )
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        $DbFields = Db::name('Admin')->getTableFields();
        if (!in_array('email', $DbFields)) {
            Db::execute('ALTER TABLE `weike_admin` ADD COLUMN `email` VARCHAR(200)  NOT NULL   COMMENT \'\' AFTER `id`;');
        }
        $where = array();
        if ($field && $name) {
            $where[$field] = $name;
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $list = Db::name('Admin')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function adminEdit()
    {
        $_GET = input('param.');
        $_POST = input('post.');
        if (empty($_POST)) {
            if (empty($_GET['id'])) {
                $this->data = null;
            } else {
                $this->data = Db::name('Admin')->where(array('id' => trim($_GET['id'])))->find();
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $input = $_POST;
            if (!check($input['username'], 'username')) {
                $this->error('用户名格式错误！');
            }

            if ($input['nickname'] && !check($input['nickname'], 'A')) {
                $this->error('昵称格式错误！');
            }

            if ($input['password'] && !check($input['password'], 'password')) {
                $this->error('登录密码格式错误！');
            }

            if ($input['moble'] && !check($input['moble'], 'moble')) {
                $this->error('手机号码格式错误！');
            }

            if ($input['email'] && !check($input['email'], 'email')) {
                $this->error('邮箱格式错误！');
            }

            if ($input['password']) {
                $input['password'] = md5($input['password']);
            } else {
                unset($input['password']);
            }

            if (!empty($_POST['id'])) {
                $rs = Db::name('Admin')->update($input);
            } else {
                $_POST['addtime'] = time();
                $rs = Db::name('Admin')->insert($input);
            }

            if (false !== $rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }

    public function adminStatus()
    {
        $id = input('id/a', NULL);
        $type = input('param.type/s', NULL);
        $moble = input('param.moble/s','Admin');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (false !== Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (false !== Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function auth()
    {
        $authGroup = Db::name('AuthGroup');
        $condition['module'] = 'admin';
        $map = array(
            'status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')
        );
        $list = $authGroup->order('id asc')->where($condition)->paginate(15,false,['query'=>request()->param()])->each(function($item, $key) use ($map){
                foreach ($map as $col => $pair) {
                    if (isset($item[$col]) && isset($pair[$item[$col]])) {
                        $item[$col . '_text'] = $pair[$item[$col]];
                    }
                }

            return $item;
        });
        $show = $list->render();
        $this->assign('_list', $list);
        $this->assign('_page',$show);
        $this->assign('_use_tip', true);
        $this->meta_title = '权限管理';
        return $this->fetch();
    }

    public function authEdit()
    {
        $_GET = input('param.');
        if (empty($_POST)) {
            if (empty($_GET['id'])) {
                $this->data = null;
            } else {
                $this->data = Db::name('AuthGroup')->where(array(
                    'module' => 'admin',
                    'type' => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
                ))->find((int) $_GET['id']);
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            if (isset($_POST['rules'])) {
                sort($_POST['rules']);
                $_POST['rules'] = implode(',', array_unique($_POST['rules']));
            }

            $_POST['module'] = 'admin';
            $_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;

            $AuthGroup = model('AuthGroup');
            if(empty($_POST['id'])){
                $r=$AuthGroup->create($_POST,true);
             }else{
                $r=$AuthGroup->update($_POST,['id'=>$_POST['id']],true);
            }
            if (false !== $r) {
                $this->success('操作成功!');

            } else {
                $this->error('操作失败' . $AuthGroup->getError());
            }

        }
    }

    public function authStatus()
    {
        $moble = input('moble/s','AuthGroup');
        $id = input('id/a', NULL);
        $type = input('param.type/s', NULL);
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (false !== Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (false !== Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function authStart()
    {
        if (false !== Db::name('AuthRule')->where(array('status' => 1))->delete()) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function authAccess()
    {
        $this->updateRules();
        $auth_group = Db::name('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type'   => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->column('id,id,title,rules');
        $node_list = $this->returnNodes();
        $map = array(
            'module' => 'admin',
            'type' => 2,//Common\Model\AuthRuleModel::RULE_MAIN,
            'status' => 1
        );
        $main_rules = Db::name('AuthRule')->where($map)->column('name,id');
        $map = array(
            'module' => 'admin',
            'type' => 1,//Common\Model\AuthRuleModel::RULE_URL,
            'status' => 1
        );
        $child_rules = Db::name('AuthRule')->where($map)->column('name,id');
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $node_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int) input('param.group_id')]);
        $this->meta_title = '访问授权';
        return $this->fetch();
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
                $temp['type'] = 1;//Common\Model\AuthRuleModel::RULE_URL;
            } else {
                $temp['type'] = 2;//Common\Model\AuthRuleModel::RULE_MAIN;
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
            } else if ($rule['status'] == 1) {
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
            $AuthRule->insertAll(array_values($data));
        }

        return true;

//        if ($AuthRule->getDbError()) {
//            trace('[' . 'Admin\\Controller\\UserController::updateRules' . ']:' . $AuthRule->getDbError());
//            return false;
//        } else {
//            return true;
//        }
    }

    public function authAccessUp()
    {
        $_POST = input('post.');
        if (isset($_POST['rules'])) {
            sort($_POST['rules']);
            $_POST['rules'] = implode(',', array_unique($_POST['rules']));
        }

        $_POST['module'] = 'admin';
        $_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;
        $AuthGroup = model('AuthGroup');
        if(empty($_POST['id'])){
            $r=$AuthGroup->create($_POST,true);
        }else{
            $r=$AuthGroup->update($_POST,['id'=>$_POST['id']],true);
        }

        if ($r === false) {
            $this->error('操作失败' . $AuthGroup->getError());
        } else {
            $this->success('操作成功!');
        }

    }

    public function authUser()
    {
        $group_id = input('group_id/d');
        if (empty($group_id)) {
            $this->error('参数错误');
        }

        $auth_group = Db::name('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type'   => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->column('id,id,title,rules');
        $prefix = config('database.prefix');
        $l_table = $prefix . 'auth_group_access';//Common\Model\AuthGroupModel::MEMBER;
        $r_table = $prefix . 'admin';//Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;

        $map = array(
            'status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')
        );
        $list = Db::table($l_table . ' a')->field('m.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status')
            ->join($r_table . ' m', 'm.id=a.uid')->where(array('a.group_id' => $group_id))->order('a.uid desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key) use ($map){
                foreach ($map as $col => $pair) {
                    if (isset($item[$col]) && isset($pair[$item[$col]])) {
                        $item[$col . '_text'] = $pair[$item[$col]];
                    }
                }

                return $item;
            });


        $show = $list->render();

        $this->assign('_list', $list);
        $this->assign('page',$show);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int) input('param.group_id')]);
        $this->meta_title = '成员授权';
        return $this->fetch();
    }

    public function authUserAdd()
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
            } else {
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
            $rs =Db::name('Admin')->where(array('id' => $uid))->find();
            if (!$rs) {
                $this->error('管理员用户不存在');
            }
        }

        if ($gid && !$AuthGroup->checkGroupId($gid)) {
            $this->error($AuthGroup->error);
        }

        if ($AuthGroup->addToGroup($uid, $gid)) {
            $this->success('操作成功');
        } else {
            $this->error($AuthGroup->getError());
        }
    }

    public function authUserRemove()
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

        if (false !== $AuthGroup->removeFromGroup($uid, $gid)) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function log()
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $list = Db::name('UserLog')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function logEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = Db::name('UserLog')->where(array('id' => trim($id)))->find();
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (false !== Db::table('weike_user_log')->where(array('id'=>$id))->update($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (false !== Db::table('weike_user_log')->insert($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }

        }
    }

    public function logStatus()
    {
        $id = input('id/a', NULL);
        $type = input('param.type/s', NULL);
        $moble = input('moble/s','UserLog');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (false !== Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (false !== Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function qianbao()
    {
        $where = array();
        $name = input('name/s', NULL);
        $field = input('field/s', NULL);
        $status = input('status/d', NULL);
        $coinname = input('coinname/s', NULL);

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        if ($coinname) {
            $where['coinname'] = trim($coinname);
        }

        $list = Db::name('UserQianbao')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function qianbaoEdit()
    {
        $id = input('id/d');

        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = Db::name('UserQianbao')->where(array('id' => trim($id)))->find();
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (false !== Db::name()->table('weike_user_qianbao')->where(array('id' => $id))->update($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (false !== Db::name()->table('weike_user_qianbao')->insert($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
        }
    }

    public function qianbaoStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s','UserQianbao');

        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (false !== Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (false !== Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function bank()
    {
        $name = input('param.name/s',NULL);
        $field = input('param.field/s',NULL);
        $status = input('param.status/d',NULL);
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        if ($status) {
            $where['status'] = $status - 1;
        }

        $list = Db::name('UserBank')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function bankEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = Db::name('UserBank')->where(array('id' => trim($id)))->find();
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                if (false !== Db::name()->table('weike_user_bank')->where(array('id' => $id))->update($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (false !== Db::name()->table('weike_user_bank')->insert($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
        }
    }

    public function bankStatus()
    {
        $id =input('id/a', null);
        $moble = input('moble','UserBank');
        $type =input('param.type/s',NULL);
        if (empty($id)) {
            $this->error('参数错误！');
        }
        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (false !== Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (false !== Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coin()
    {
        $name = input('name/s',NULL);
        $field = input('field/s',NULL);
        $coins = input('coins',NULL);
        $number = input('number/f',0);
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if($coins){
            if ($coins != '') {
                $where[$coins] = array('gt', $number);
            }
        }

        $count = Db::name('UserCoin')->where($where)->count();
        //$Page = new \Think\Page($count, 15);
        //$show = $Page->show();
        if (!empty($coins)) {
            $char = substr($coins, strlen($coins) - 1, strlen($coins));
            if ($char === 'd'){
                $coins = substr($coins,0,strlen($coins)-1); ;
            }
            $order_field = $coins.'d';
            $list = Db::name('UserCoin')->where($where)->order("$order_field"." desc , id desc")->paginate(15);
        }else{
            $list = Db::name('UserCoin')->where($where)->order('id desc')->paginate(15);
        }
        $show = $list->render();
        $list = $list->all();
        $ids = '';
        foreach ($list as $k => $v) {
            if(isset($v['userid'])){
                $ids = $ids . $v['userid'] . ',';
            }
        }

        $ids = rtrim($ids, ',');
        $new_c2c_tx1 = Db::name('UserC2cLog')
            ->field('sellid, sum(price * num) * 1.005 as total')->where(['sellid' => ['IN', $ids], 'status' => 1,'order' => 0])
            ->group('sellid')->order('id desc')->select();
        $new_c2c_tx2 = Db::name('UserC2cLog')
            ->field('sellid, sum(price * num) * 1.01 as total')->where(['sellid' => ['IN', $ids], 'status' => 1,'order' => 1])
            ->group('sellid')->order('id desc')->select();
        $new_c2c_tx0 = Db::name('UserC2cTrade')
            ->field('userid, sum(num) as total')->where(['userid' => ['IN', $ids], 'type' => 2, 'status' => 1,'matchtime' =>0])
            ->group('userid')->order('id desc')->select();
        //get user data
        $user = Db::name('User')->field('id,username')->where(['id' => ['IN', $ids]])->order('id desc')->select();
        foreach($user as $key=>$val){
            $user[$val['id']] = $val['username'];
            unset($user[$key]);
        }

        $mycz1 = Db::name('Mycz')->field('userid, sum(num) as num')->where(['userid' => ['IN', $ids], 'status' => 1])->group('userid')->order('id desc')->select();
        $mycz2 = Db::name('Mycz')->field('userid, sum(num) as num')->where(['userid' => ['IN', $ids], 'status' => 2])->group('userid')->order('id desc')->select();
        $mycz3 = Db::name('Mycz')->field('userid, sum(mum) as mum')->where(['userid' => ['IN', $ids], 'status' => 5])->group('userid')->order('id desc')->select();
        $mycz4 = Db::name('Mytx')->field('userid, sum(num) as num')->where(['userid' => ['IN', $ids], 'status' => 1])->group('userid')->order('id desc')->select();
        $myepay = Db::name('Epaycz')->field('userid,sum(mum) as mum')->where(['userid' => ['IN', $ids], 'status' => 2])->group('userid')->order('id desc')->select();
        $user_c2c_trade1 = Db::name('UserC2cTrade')
            ->field('userid, sum(price * num) as total')->where(['userid' => ['IN', $ids], 'type' => 1, 'status' => 1])
            ->group('userid')->order('id desc')->select();
        $invit = Db::name('Invit')->field('userid, sum(fee) as fee')->where(['userid' => ['IN', $ids]])->group('userid')->order('id desc')->select();
        $trade_log1 = Db::name('TradeLog')->field('userid, sum(mum) as mum')->where(['userid' => ['IN', $ids]])->group('userid')->order('id desc')->select();
        $trade_log2 = Db::name('TradeLog')->field('peerid, sum(mum) as mum')->where(['peerid' => ['IN', $ids]])->group('peerid')->order('id desc')->select();
        $trade_log3 = Db::name('TradeLog')->field('userid, sum(fee_buy) as fee_buy')->where(['userid' => ['IN', $ids]])->group('userid')->order('id desc')->select();
        $trade_log4 = Db::name('TradeLog')->field('peerid, sum(fee_sell) as fee_sell')->where(['peerid' => ['IN', $ids]])->group('peerid')->order('id desc')->select();
//        $chage_coin = Db::name('Coinlog')->field('sum(hkd) as chage_hkd')->where(['user_id' => ['IN', $ids]])->group('user_id')->select();

        //get all coin data
        $coin = '';
        foreach (config('coin') as $k => $v) {
            $coin = $coin . $v['name'] . ',';
        }
        $coin = rtrim($coin, ',');
        //get user data
        $myzr = Db::name('Myzr')->field('userid, sum(mum) as mum, coinname')->where(['coinname' => ['IN', $coin], 'userid' => ['IN', $ids], 'status' => 1])->group('userid, coinname')->order('id desc')->select();
        $myzc = Db::name('Myzc')->field('userid, sum(num) as num, coinname')->where(['coinname' =>  ['IN', $coin], 'userid' => ['IN', $ids], 'status' => 1])->group('userid, coinname')->order('id desc')->select();
        $issue_coin = Db::name('IssueCoin')->field('userid, sum(interest) as interest, coinname')->where(['coinname' => ['IN', $coin], 'userid' => ['IN', $ids]])->group('userid, coinname')->order('id desc')->select();

        //get all coin data
        $coin = '';
        foreach (config('coin') as $k => $v) {
            $coin = $coin . $v['name'] . '_hkd,';
        }
        $coin = rtrim($coin, ',');
        //get user data
        $trade_log5 = Db::name('TradeLog')->field('userid, sum(num) as num, market')->where(['userid' => ['IN', $ids], 'market' => ['IN', $coin]])->group('userid, market')->select();
        $trade_log6 = Db::name('TradeLog')->field('peerid, sum(num) as num, market')->where(['peerid' => ['IN', $ids], 'market' => ['IN', $coin]])->group('peerid, market')->select();
        //get user ejf unfreeze
        $myejf = Db::name('IssueEjf')->field('userid, coinname, num * `unlock` / 100 as mum')->where(['userid' => ['IN', $ids]])->order('id desc')->select();

        //Add for query list
        foreach ($list as $k => $v) {
            //init variable
            $list[$k]['recharge_cash']=0;
            $list[$k]['recharge_person']=0;
            $list[$k]['recharge_ant']=0;
            $list[$k]['num_sell']=0;
            $list[$k]['trade_award'] = 0;
            $list[$k]['recharge_c2c'] = 0;
            $list[$k]['epaycz']=0;

            $list[$k]['withdraw_cash'] = 0;
            $list[$k]['fee_buy']=0;
            $list[$k]['fee_sell'] = 0;
            $list[$k]['num_buy'] = 0;
            $list[$k]['withdraw_c2c'] =0;


            //recharge withdraw
            $list[$k]['username'] = isset($user[(int)$v['userid']])? $user[(int)$v['userid']]:'' ;

            foreach ($mycz1 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['recharge_cash'] = $val['num'];
                }
            }
            foreach ($mycz2 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['recharge_person'] = $val['num'];
                }
            }
            foreach ($mycz3 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['recharge_ant'] = $val['mum'];
                }
            }
            foreach ($mycz4 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['withdraw_cash'] = $val['num'];
                }
            }

            foreach ($myepay as $key => $val){
                if ($v['userid'] === $val['userid']){
                    $list[$k]['epaycz'] = $val['mum'];
                }
            }
            //c2c recharge withdraw
            foreach ($user_c2c_trade1 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['recharge_c2c'] = $val['total'];
                }
            }
            if (!$new_c2c_tx1 && !$new_c2c_tx2){
                foreach ($new_c2c_tx0 as $key => $val) {
                    if ($v['userid'] === $val['userid']) {
                        $list[$k]['withdraw_c2c'] = $val['total']; //+ $new_c2c_tx1[$key]['total'] + $new_c2c_tx2[$key]['total'];
                    }
                }
            }elseif(!$new_c2c_tx2 && !$new_c2c_tx0){
                foreach ($new_c2c_tx1 as $key => $val) {
                    if ($v['userid'] === $val['sellid']) {
                        $list[$k]['withdraw_c2c'] = $val['total']; //+ $new_c2c_tx0[$key]['total'] + $new_c2c_tx2[$key]['total'];
                    }
                }
            }elseif(!$new_c2c_tx1 && !$new_c2c_tx0){
                foreach ($new_c2c_tx2 as $key => $val) {
                    if ($v['userid'] === $val['sellid']) {
                        $list[$k]['withdraw_c2c'] = $val['total']; //+ $new_c2c_tx0[$key]['total'] + $new_c2c_tx1[$key]['total'];
                    }
                }
            }else{
                foreach ($new_c2c_tx1 as $key => $val) {
                    if ($v['userid'] === $val['sellid']) {
                        $new_c2c_tx0[$key]['total'] = isset($new_c2c_tx0[$key]['total'])?$new_c2c_tx0[$key]['total']:0;
                        $new_c2c_tx2[$key]['total'] = isset($new_c2c_tx2[$key]['total'])?$new_c2c_tx2[$key]['total']:0;
                        $list[$k]['withdraw_c2c'] = $val['total'] + $new_c2c_tx0[$key]['total'] + $new_c2c_tx2[$key]['total'];
                    }
                }
            }


            //trade award
            foreach ($invit as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['trade_award'] = $val['fee'];
                }
            }

            //trade num buy or sell
            foreach ($trade_log1 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['num_buy'] = $val['mum'];
                }
            }
            foreach ($trade_log2 as $key => $val) {
                if ($v['userid'] === $val['peerid']) {
                    $list[$k]['num_sell'] = $val['mum'];
                }
            }

            //trade fee buy or sell
            foreach ($trade_log3 as $key => $val) {
                if ($v['userid'] === $val['userid']) {
                    $list[$k]['fee_buy'] = $val['fee_buy'];
                }
            }
            foreach ($trade_log4 as $key => $val) {
                if ($v['userid'] === $val['peerid']) {
                    $list[$k]['fee_sell'] = $val['fee_sell'];
                }
            }
            //trade pay in or out
            $list[$k]['pay_in'] = $list[$k]['recharge_cash'] + $list[$k]['recharge_person'] + $list[$k]['recharge_ant']
                + $list[$k]['num_sell'] + $list[$k]['trade_award'] + $list[$k]['recharge_c2c'] + $list[$k]['epaycz'];
            $list[$k]['pay_out'] = $list[$k]['withdraw_cash'] + $list[$k]['fee_buy'] + $list[$k]['fee_sell'] + $list[$k]['num_buy']
                + $list[$k]['withdraw_c2c'];

            //statistics every coin
            foreach (config('coin') as $key => $val) {
                //turn_info turn_out
                foreach ($myzr as $kkk => $vvv) {
                    if ($v['userid'] === $vvv['userid'] && $val['name'] === $vvv['coinname']) {
                        $list[$k]['turn_into'][$val['name']] = $vvv['mum'];
                    }
                }
                foreach ($myzc as $kkk => $vvv) {
                    if ($v['userid'] === $vvv['userid'] && $val['name'] === $vvv['coinname']) {
                        $list[$k]['turn_out'][$val['name']] = $vvv['num'];
                    }
                }

                //currency join interest
                $coins_interest = ['wcg','erc','ejf','drt','mat'];
                if (in_array($val['name'],$coins_interest)) {
                    foreach ($issue_coin as $kkk => $vvv) {
                        if ($v['userid'] === $vvv['userid'] && $val['name'] === $vvv['coinname']) {
                            $list[$k]['interest'][$val['name']] = $vvv['interest'];
                        }
                    }
                }

                //trade coin buy or sell
                foreach ($trade_log5 as $kkk => $vvv) {
                    if ($v['userid'] === $vvv['userid'] && $val['name'] . '_hkd' === $vvv['market']) {
                        $list[$k]['coin_buy'][$val['name']] = isset($list[$k]['coin_buy'][$val['name']])?$list[$k]['coin_buy'][$val['name']] : 0  + $vvv['num'];
                    }
                }
                foreach ($trade_log6 as $kkk => $vvv) {
                    if ($v['userid'] === $vvv['peerid'] && $val['name'] . '_hkd' === $vvv['market']) {
                        $list[$k]['coin_sell'][$val['name']] = isset($list[$k]['coin_sell'][$val['name']])? $list[$k]['coin_sell'][$val['name']] : 0  + $vvv['num'];
                    }
                }
                //ejf unfreeze
                foreach ($myejf as $kkk => $vvv) {
                    if ($v['userid'] === $vvv['userid'] && $val['name'] === 'ejf') {
                        $list[$k]['myejf'][$val['name']] = isset($list[$k]['myejf'][$val['name']])? $list[$k]['myejf'][$val['name']] : 0  + $vvv['mum'];
                    }
                }
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('statistics', $coins != null ? '用户 ' . $coins . ' 大于 ' . $number . ' 的数量：' . $count . ' 个！': '');
        return $this->fetch();
    }

    public function coinEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $usercoin = Db::name('UserCoin')->where(array('id' => trim($id)))->find();
                $remark = Db::name('Coinlog')->where(['user_id' => $usercoin['userid']])->value('remark');
                $usercoin['remark'] = $remark;
                $this->data = $usercoin;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $remark = $_POST['remark'];
            unset($_POST['remark']);
            if ($id) {
                Db::startTrans();
                $old_coinlog = Db::name('UserCoin')->lock(true)->where(['userid' => $_POST['userid']])->find();
                try {
                    $rs = [];
                    $rs[] = Db::table('weike_user_coin')->where(['id' => $id])->update($_POST);
                    $rs[] = $new_usercoin = Db::table('weike_user_coin')->where(['id' => $id])->find();
                    if ($new_usercoin) {
                        $new_coinlog = Db::name('UserCoin')->where(['userid' => $_POST['userid']])->find();
                        $data = [
                            'admin_id' => session('admin_id'),
                            'user_id' => $_POST['userid'],
                            'add_time' => time(),
                            'remark' => $remark
                        ];
                        $coin = Db::name('Coin')->where(['status' => 1])->select();
                        foreach ($coin as $k => $v) {
                            $data[$v['name']] = $new_coinlog[$v['name']] - $old_coinlog[$v['name']];
                        }
                        $rs[] = Db::table('weike_coinlog')->insert($data);
                    }
                    if (check_arr($rs)) {
                        Db::commit();
                        $log = '管理员 ' . session('admin_username') . ' 修改了用户 ID ' . $_POST['userid'] . ' 的虚拟币 SQL 语句为： ' . db()->getLastSql();
                        mlog($log);
                        $this->success('编辑成功！');
                    } else {
                        Db::rollback();
                        $this->error('编辑失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('编辑失败！');
                }
            } else {
                if ($id = Db::table('weike_user_coin')->insert($_POST)) {
                    $log = '管理员 ' . session('admin_username') . ' 增加了用户 ID ' . $id . ' 的虚拟币 SQL 语句为： ' . db()->getLastSql();
                    mlog($log);
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
        }
    }

    public function coinLog()
    {
        $userid =intval(input('userid'));
        $coinname =strval(input('coinname'));
        $data['userid'] = $userid;
        $data['username'] = Db::name('User')->where(['id' => $userid])->value('username');
        $data['coinname'] = $coinname;

        //用户当前账户余额
        $data['balance'] = Db::name('UserCoin')->where(['userid' => $userid])->value($coinname);
        $data['freeze'] = Db::name('UserCoin')->where(['userid' => $userid])->value($coinname . 'd');
        $data['total'] = $data['balance'] + $data['freeze'];

        //用户充值成功金额，提现成功金额
        $data['recharge_cash'] = Db::name('Mycz')->where(['userid' => $userid, 'status' => 1])->sum('num');
        $data['recharge_person'] = Db::name('Mycz')->where(['userid' => $userid, 'status' => 2])->sum('num');
        $data['recharge_ant'] = Db::name('Mycz')->where(['userid' => $userid, 'status' => 5])->sum('num');
        $data['recharge_process'] = Db::name('Mycz')->where(['userid' => $userid, 'status' => 3])->sum('num');
        $data['withdraw_cash'] = Db::name('Mytx')->where(['userid' => $userid, 'status' => 1])->sum('num');
        $data['withdraw_process'] = Db::name('Mytx')->where(['userid' => $userid, 'status' => 3])->sum('num');

        //variable init
        $data['turn_into_process']=0;
        $data['turn_into_success']=0;
        $data['turn_out_process'] =0;
        $data['turn_out_success'] =0;

        //用户转入数量，转出数量
        if ($coinname != 'cny' && $coinname != 'hkd') {
            $data['turn_into_process'] = Db::name('Myzr')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => ['neq', '0']])->sum('num');
            $data['turn_into_success'] = Db::name('Myzr')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => 1])->sum('num');
            $data['turn_out_process'] = Db::name('Myzc')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => ['neq', '0']])->sum('num');
            $data['turn_out_success'] = Db::name('Myzc')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => 1])->sum('num');
        }

        $this->assign('data', $data);
        return $this->fetch();
    }

    public function setpwd()
    {
        $_POST = input('post.');
        if (IS_POST) {
            $oldpassword = $_POST['oldpassword'];
            $newpassword = $_POST['newpassword'];
            $repassword = $_POST['repassword'];

            if (!check($oldpassword, 'password')) {
                $this->error('旧密码格式错误！');
            }

            if (md5($oldpassword) != session('admin_password')) {
                $this->error('旧密码错误！');
            }

            if (!check($newpassword, 'password')) {
                $this->error('新密码格式错误！');
            }

            if ($newpassword != $repassword) {
                $this->error('确认密码错误！');
            }

            if (false !== Db::name('Admin')->where(array('id' => session('admin_id')))->update(array('password' => md5($newpassword)))) {
                $this->success('登陆密码修改成功！', url('Login/loginout'));
            } else {
                $this->error('登陆密码修改失败！');
            }
        }

        return $this->fetch();
    }

    //管理员变动用户财产记录
    public function changelog()
    {
        $name =input('name/s',NULL);
        $field =input('field/s',NULL);
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['user_id'] = Db::name('User')->where(array('username' => $name))->value('id');
            } elseif ($field == 'adminname'){
                $where['admin_id'] = Db::name('admin')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $list = Db::name('Coinlog')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['user_id']))->value('username');
            $item['adminname'] = Db::name('Admin')->where(array('id' => $item['admin_id']))->value('username');
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
}

?>