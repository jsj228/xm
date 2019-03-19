<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;
use think\Loader;
use app\common\model;


class User extends AdminCommon
{

    public function index()
    {
        $name = input('name');
        $field = input('field');
        $status = input('status');

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

        $list = DB::name('User')->where($where)->order('id desc')->paginate(15);
        $page =$list->render();
        // $list = $list->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['invit_1'] = DB::name('User')->where(array('id' => $v['invit_1']))->value('username');
            $list[$k]['invit_2'] = DB::name('User')->where(array('id' => $v['invit_2']))->value('username');
            $list[$k]['invit_3'] = DB::name('User')->where(array('id' => $v['invit_3']))->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function edit()
    {
        $id = input('id');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $user = DB::name('User')->where(array('id' => trim($id)))->find();
                $this->assign('data', $user);
            }

            $imgstr = "";
            if($user['idcardimg1']){
                $img_arr = array();
                $img_arr = explode("_",$user['idcardimg1']);

                foreach($img_arr as $k=>$v){
                    $imgstr = $imgstr.'<img src="'.config('TMPL_PARSE_STRING.__DOMAIN__').'/Upload/idcard/'.$v.'"  style="width:200px;height:100px;" />';
                   // $imgstr = $imgstr.'<img src="'.C('TMPL_PARSE_STRING.__DOMAIN__').'/Upload/idcard/'.$v.'"  style="width:200px;height:100px;" />';
                }

                unset($img_arr);
            }
           
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

            $flag = false;
            //增加 和 编辑 限制唯一用户名和手机号码
            $userbyname = DB::name('User')->where(array('username' => $_POST['username']))->find();
            $userbymoble = DB::name('User')->where(['moble' => $_POST['moble']])->find();

            if (isset($_POST['id'])) {
                if($userbyname && $userbyname['id'] != $_POST['id']){
                    $this->error('用户名已存在！');
                }
               
                if($userbymoble && $userbymoble['id'] != $_POST['id']){
                    $this->error('手机号码已存在！');
                }

                $rs = DB::name('User')->update($_POST);
                $log = '管理员 ' . session('admin_username') . ' 修改了用户 ID '. $_POST['id'] .' 的信息 SQL 语句为： ' . DB::name('admin_log')->getLastSql();
                mlog($log);
            } else {
                if ($userbyname) {
                    $this->error('用户名已存在！');
                }

                if ($userbymoble) {
                    $this->error('手机号码已存在！');
                }

                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user write , weike_user_coin write ');
                $rs[] = DB::table('weike_user')->insert($_POST);
                
                $log = '管理员 ' . session('admin_username') . ' 增加了用户 ID ' . $rs[0] . ' 的信息 SQL 语句为： ' . $mo->getLastSql();
                mlog($log);
                $rs[] = DB::table('weike_user_coin')->insert(array('userid' => $rs[0]));
                $flag = true;
            }

            if ($rs) {
                if ($flag) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                }
                session('reguserId', $rs);
                $this->success('编辑成功！');
            } else {
                if ($flag) {
                    $mo->execute('rollback');
                }
                $this->error('编辑失败！');
            }
        }
    }

    public function status()
    {
   
        $id = input('post.');
        foreach ($id as $key => $value) {
            $ids=implode(',', $value);
        }

        $type = input('type');
        // $moble = input('get.moble');

        session('uid',$id);
        if (empty($id)) {
            $this->error('请选择会员！');
        }

        if (empty($type)) {
            $this->error('参数错误！');
        }


        $where['id'] = array('in', $ids);
          
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
                if (DB::name('user')->where($where)->delete()) {
                    $_where = array(
                        'userid' => $where['id'],
                    );
                    $log = '管理员 ' . session('admin_username') . ' 删除了用户 ID ' . $id[0] . ' 的信息 SQL 语句为： ' . DB::name('user_log')->getLastSql();
                    mlog($log);
                    DB::name('UserCoin')->where($_where)->delete();
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            case 'idauth':
                $data = array('idcardauth' => 1, 'addtime' => time(), 'czr' => session('admin_username'));

                /*//注册赠送  判断总量
                $total = M('Coin')->where(['name' => 'ifc'])->getField('cs_cl');
                $sum = DB::name('UserAwardIfc')->sum('award_num');
                if ($total > $sum) {
                    //判断是否已经赠送
                    if (!DB::name('UserAwardIfc')->where(['userid' => $id[0], 'type' => 1])->find()) {
                        $rand_num = rand(1, 100);
                        if ($rand_num === 2) {
                            $num = rand(10000, 100000);
                        } else {
                            $num = rand(10000, 20000);
                        }

                        $arr = [
                            'userid' => $id[0],
                            'award_currency' => 'ifc',
                            'award_num' => $num,
                            'addtime' => time(),
                            'type' => 1,
                            'status' => 0,
                            'czr' => session('admin_username'),
                        ];
                        DB::name('UserAwardIfc')->add($arr);
                    }
                }*/
                break;

            case 'notidauth':
                $data = array('idcardauth' => 0, 'idcardimg1' => '' );
                break;

            default:
                $this->error('操作失败！');
        }

      
        if (DB::name('user')->where($where)->update($data)) {
            // foreach($where['id'][1] as $k => $v){
            //     CampaignController::add_reward_log($v);
            // }
            // $this->myhuodong();
            $this->success('操作成功！');//json_encode($where['id'][1])
        } else {
            $this->error('操作失败！');
        }
    }
   //用户邀请实名后奖励
    public function myhuodong()
    {
        //用户id
        $invit=implode(',',session('uid'));
        $reuser = DB::name('User')->where(array('id'=>$invit))->find();
       if($reuser['idcardauth']==0){
           return;
       }

        $vit = DB::name('RegisterAward')->where(array('one' =>DB::name('user')->where(array('id'=>$invit))->value('username'),'type'=>3))->find();
       if($vit){
           return;
       }
        $usname[]=DB::name('user')->where(array('invit_1'=>$invit))->value('username');
        $Campaign = DB::name('Campaign')->where(array('status' => 0))->find();
        if (time() >= $Campaign['start_time'] && time() <= $Campaign['end_time']) {
            if($str = implode(',',$usname)){
                DB::name('user_coin')->where(array('userid' => userid()))->setInc($Campaign['coin'], $Campaign['num']);
                $data = array(
                    'users' => implode(',', $usname),
                    'one' =>DB::name('user')->where(array('id'=>$invit))->value('username'),
                    'two'=> implode(',', $usname),
                    'n' =>1,
                    'coin' =>$Campaign['coin'],
                    'nums' => $Campaign['num'],
                    'active_time' => $Campaign['end_time'],
                    'add_time' => time(),
                    'type' => 3,
                    'status' => 1,
                );
                DB::name('RegisterAward')->insert($data);
            }

        }else{
            return;
        }

    }
    public function admin( )
    {
        $name = input('name');
        $field = input('field');
        $status = input('status');
        $DbFields = DB::name('Admin')->getTableFields();
        if (!in_array('email', $DbFields)) {
            DB::name()->execute('ALTER TABLE `weike_admin` ADD COLUMN `email` VARCHAR(200)  NOT NULL   COMMENT \'\' AFTER `id`;');
        }
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = DB::name('Admin')->where($where)->count();

        $list = DB::name('Admin')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function adminEdit()
    {
        $id = input('id');
        $_POST = input('post.');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->assign('data',DB::name('Admin')->where(array('id' =>$id))->find());
            }
            return $this->fetch('adminEdit');
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

            if ($input['moble'] && !check($input['moble'], 'moble2')) {
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

            if ($id) {
                $rs = DB::name('Admin')->where(['id'=>$id])->update($input);
            } else {
                $_POST['addtime'] = time();
                $rs = DB::name('Admin')->insert($input);
            }

            if ($rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }

    public function adminStatus()
    {
        $id = input('post.');
        foreach ($id as $key => $value) {
           $ids=implode(',', $value);
        }
        
        $type = input('type');
        
        $moble = input('moble');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }


        $where['id'] = array('in', $ids);

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
                if (DB::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function auth()
    {
        $authGroup = DB::name('AuthGroup');
        $condition['module'] = 'admin';
        $list = $authGroup->order('id asc')->where($condition)->select();
        $list = int_to_string($list);
        $this->assign('_list', $list);
        $this->assign('_use_tip', true);
        $this->meta_title = '权限管理';
        return $this->fetch();
    }

    public function authEdit()
    {
        $_GET = input('get.');
        if (empty($_POST)) {
            if (empty($_GET['id'])) {
                $this->data = null;
            } else {
                $this->data = DB::name('AuthGroup')->where(array(
                    'module' => 'admin',
                    'type' => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
                ))->find((int) $_GET['id']);
            }

           return $this->fetch('authedit');
        } else {
            $_POST = input('post.');
            if (isset($_POST['rules'])) {
                sort($_POST['rules']);
                $_POST['rules'] = implode(',', array_unique($_POST['rules']));
            }

            $_POST['module'] = 'admin';
            $_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;
            $AuthGroup = DB::name('AuthGroup');
            $data = $AuthGroup->create();

            if ($data) {
                if (empty($data['id'])) {
                    $r = $AuthGroup->insert();
                } else {
                    $r = $AuthGroup->update();
                }

                if ($r === false) {
                    $this->error('操作失败' . $AuthGroup->getError());
                } else {
                    $this->success('操作成功!');
                }
            } else {
                $this->error('操作失败' . $AuthGroup->getError());
            }
        }
    }

    public function authStatus()
    {
        $moble = input('moble','AuthGroup');
        $id = input('post.');
        foreach ($id as $key => $value) {
            $ids=implode(',', $value);
        }
        
        $type = input('type');
        if (empty($ids)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        $where['id'] = array('in', $ids);

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
                if (DB::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function authStart()
    {
        if (DB::name('AuthRule')->where(array('status' => 1))->delete()) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function authAccess()
    {
        $this->updateRules();
        $auth_group = DB::name('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type'   => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->field('id,id,title,rules')->select();


        $node_list = $this->returnNodes();

        $map = array(
            'module' => 'admin',
            'type' => 2,//Common\Model\AuthRuleModel::RULE_MAIN,
            'status' => 1
        );
        $main_rules = DB::name('AuthRule')->where($map)->field('name,id')->find();
        $map = array(
            'module' => 'admin',
            'type' => 1,//Common\Model\AuthRuleModel::RULE_URL,
            'status' => 1
        );
        $child_rules = DB::name('AuthRule')->where($map)->field('name,id')->find();
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $node_list);
//        p($auth_group);die;
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int) $_GET['group_id']]);
        $this->meta_title = '访问授权';
        return $this->fetch('authAccess');
    }

    protected function updateRules()
    {
        $nodes = $this->returnNodes(false);
        $AuthRule = DB::name('AuthRule');
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

        try{
            return true;
        }catch (\Exception $e){
            return false;
        }

//        if ($AuthRule->getDbError()) {
//            echo 111;die;
//            trace('[' . 'Admin\\Controller\\UserController::updateRules' . ']:' . $AuthRule->getDbError());
//
//            return false;
//        } else {
//            echo 222;die;
//            return true;
//        }
    }

    public function authAccessUp()
    {
        $_POST = input('post./a');
        if (isset($_POST['rules'])) {
            sort($_POST['rules']);
            $_POST['rules'] = implode(',', array_unique($_POST['rules']));
        }

        $_POST['module'] = 'admin';
        $_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;

        $AuthGroup = model('AuthGroup');

        $data = $AuthGroup->create();

        if ($data) {
            if (empty($data['id'])) {
                $r = $AuthGroup->insert();
            } else {
                $r = $AuthGroup->update();
            }

            if ($r === false) {
                $this->error('操作失败' . $AuthGroup->getError());
            } else {
                $this->success('操作成功!');
            }
        } else {
            $this->error('操作失败' . $AuthGroup->getError());
        }
    }

    public function authUser()
    {
        $group_id = input('group_id/d');
        if (empty($group_id)) {
            $this->error('参数错误');
        }

        $auth_group = DB::name('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type'   => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->column('id,id,title,rules');
        $prefix = config('DB_PREFIX');
        $l_table = $prefix . 'auth_group_access';//Common\Model\AuthGroupModel::MEMBER;
        $r_table = $prefix . 'admin';//Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;
        $model = DB::name()->table($l_table . ' a')->join($r_table . ' m ON m.id=a.uid');
        $_REQUEST = array();
        $list = $this->lists($model, array(
            'a.group_id' => $group_id,
        ), 'a.uid desc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status');


        int_to_string($list);

        $this->assign('_list', $list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int) $_GET['group_id']]);
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
            $user = DB::name('Admin')->where(array('username' => $uid))->find();

            if (!$user) {
                $user = DB::name('Admin')->where(array('nickname' => $uid))->find();
            }

            if (!$user) {
                $user = DB::name('Admin')->where(array('moble' => $uid))->find();
            }

            if (!$user) {
                $this->error('用户不存在(id 用户名 昵称 手机号均可)');
            }

            $uid = $user['id'];
        }

        $gid = input('group_id');
        if ($res = DB::name('AuthGroupAccess')->where(array('uid' => $uid))->find()) {
            if ($res['group_id'] == $gid) {
                $this->error('已经存在,请勿重复添加');
            } else {
                $res = DB::name('AuthGroup')->where(array('id' => $gid))->find();

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

            if (!DB::name('Admin')->where(array('id' => $uid))->find()) {
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

        if ($AuthGroup->removeFromGroup($uid, $gid)) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function log()
    {
        $name = input('name');
        $field = input('field');
        $status = input('status');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = DB::name('UserLog')->where($where)->count();
        $list = DB::name('UserLog')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        // $list  = $list ->all();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function logEdit()
    {
        $id = input('id');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->assign('data',DB::name('UserLog')->where(array('id' => trim($id)))->find());
            }

            return $this->fetch('logEdit');
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (DB::table('weike_user_log')->where(array('id'=>$id))->update($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (DB::table('weike_user_log')->insert($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }

        }
    }

    public function logStatus()
    {
        $id = input('post.');
        foreach ($id as $key => $value) {
            $ids=implode(',', $value);
        }
        $type = input('type');
        $moble = input('moble','UserLog');
        if (empty($ids)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        $where['id'] = array('in', $ids);

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
                if (DB::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function qianbao()
    {
        $where = array();
        $name = input('name');
        $field = input('field');
        $status = input('status');
        $coinname = input('coinname');

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
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
        $list = DB::name('UserQianbao')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function qianbaoEdit()
    {
        $id = input('id');

        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->assign('data',DB::name('UserQianbao')->where(array('id' => trim($id)))->find());
            }

           return $this->fetch('qianbaoedit');
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (DB::table('weike_user_qianbao')->where(array('id' => $id))->update($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (DB::table('weike_user_qianbao')->insert($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
        }
    }

    public function qianbaoStatus()
    {
        $id = input('post.');
        foreach ($id as $key => $value) {
           $ids=implode(',',$value);
        }
        
        $type = input('type');
        $moble = input('moble','user_qianbao');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }


        $where['id'] = array('in', $ids);

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
                if (DB::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function bank()
    {
        $name = input('get.name');
        $field = input('get.field');
        $status = input('get.status');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        if ($status) {
            $where['status'] = $status - 1;
        }
        $list = DB::name('UserBank')->where($where)->order('id desc')->paginate(15);

        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k => $v) {
            $list[$k]['username'] =  DB::name('User')->where(array('id' => $v['userid']))->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function bankEdit()
    {
        $id = input('id');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->assign('data',DB::name('UserBank')->where(array('id' => trim($id)))->find());
            }

            return $this->fetch('bankEdit');
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                if (DB::table('weike_user_bank')->where(array('id' => $id))->update($_POST)) {
                    // return array('status'=>1,'msg'=>'编辑成功！');
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (DB::table('weike_user_bank')->insert($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
        }
    }

    public function bankStatus()
    {
        $id =input('post.');
        foreach ($id as $key => $value) {
            $ids=implode(',', $value);
        }

        $moble = input('moble','UserBank');
        $type =input('type');

        if (empty($ids)) {
            $this->error('参数错误！');
        }
        if (empty($type)) {
            $this->error('参数错误1！');
        }

        $where['id'] = array('in', $ids);

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
                if (DB::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coin()
    {
        $name = input('name',NULL);
        $field = input('field',NULL);
        $coins = input('coins',NULL);
        $number = input('number',0);
        // $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if($coins){
            if ($coins != '') {
                $where[$coins] = array('gt', $number);
            }
        }
       

        //通过虚拟币来查看用户
  
        if (!empty($coins)) {
 
            $list = DB::name('user_coin')->where($where)->order("`{$coins}`+`{$coins}d`"."desc,id desc")->paginate(15);
            $page = $list->render();
            $list = $list->all();

        }else{

            $list = DB::name('user_coin')->where($where)->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
            $page = $list->render();
            $list = $list->all();

        }
        
        foreach ($list as $k => $v) {
            $uid = $v['userid'];
            $list[$k]['username'] = DB::name('User')->where(array('id' => $uid))->value('username');
            $list[$k]['fenhong'] = DB::name('fenhong_log')->where(array('userid' => $uid))->sum('mum');
            //分红
            $list[$k]['fenhong'] = $list[$k]['fenhong'] ? $list[$k]['fenhong'] : 0;
            //recharge withdraw
            //充值
            $list[$k]['recharge_cash'] = DB::name('Mycz')->where(['userid' => $uid, 'status' => 1])->sum('num');
            $list[$k]['recharge_person'] = DB::name('Mycz')->where(['userid' => $uid, 'status' => 2])->sum('num');
            $list[$k]['recharge_ant'] = DB::name('Mycz')->where(['userid' => $uid, 'status' => 5])->sum('mum');
            //提现
            $list[$k]['withdraw_cash'] = DB::name('Mytx')->where(['userid' => $uid, 'status' => 1])->sum('num');

            //statistics every coin
            foreach (config('coin') as $key => $val) {
                if ($val['name'] == 'btmz'){
                    continue;
                }
                //转入金额
                $list[$k]['turn_into'][$val['name']] = DB::name('Myzr')->where(['coinname' => $val['name'], 'userid' => $uid, 'status' => 1])->sum('mum');
                //转出金额
                if ($val['name'] == 'btm'){
                    $list[$k]['turn_out'][$val['name']] = DB::name('Myzc')->where(['coinname' => $val['name'], 'userid' => $uid, 'status' => 1])->sum('num');
                    $list[$k]['turn_out'][$val['name']] += DB::name('Myzc')->where(['coinname' => 'btmz', 'userid' => $uid, 'status' => 1])->sum('num');
                }else{
                    $list[$k]['turn_out'][$val['name']] = DB::name('Myzc')->where(['coinname' => $val['name'], 'userid' => $uid, 'status' => 1])->sum('num');
                }


               //买数量
                $list[$k]['coin_buy'][$val['name']] = DB::name('TradeLog')->where(['userid' => $uid, 'market' => $val['name'] . '_cny'])->sum('num');
                //卖数量
                $list[$k]['coin_sell'][$val['name']] = DB::name('TradeLog')->where(['peerid' => $uid, 'market' => $val['name'] . '_cny'])->sum('num');
            }


            $list[$k]['trade_award'] = DB::name('Invit')->where(['userid' => $uid])->sum('fee');
            //买入总额
            $list[$k]['num_buy'] = DB::name('TradeLog')->where(['userid' => $uid])->sum('mum');
            //卖入总额
            $list[$k]['num_sell'] = DB::name('TradeLog')->where(['peerid' => $uid])->sum('mum');
            //trade fee buy or sell
            //买入
            $list[$k]['fee_buy'] = DB::name('TradeLog')->where(['userid' => $uid])->sum('fee_buy');
            //卖出
            $list[$k]['fee_sell'] = DB::name('TradeLog')->where(['peerid' => $uid])->sum('fee_sell');
            //trade pay in or out
            $list[$k]['pay_in'] = $list[$k]['recharge_cash'] + $list[$k]['recharge_person'] + $list[$k]['recharge_ant']
                + $list[$k]['num_sell'] + $list[$k]['trade_award'];
            $list[$k]['pay_out'] = $list[$k]['withdraw_cash'] + $list[$k]['fee_buy'] + $list[$k]['fee_sell'] + $list[$k]['num_buy'];
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('statistics', $coins != null ? '用户 ' . $coins . ' 大于 ' . $number . ' 的数量：' . isset($count)?$count:0 . ' 个！': '');
        return $this->fetch();
    }

    public function coinEdit()
    {
        $id = input('id');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = DB::name('UserCoin')->where(array('id' => trim($id)))->find();
            }

           return $this->fetch('coinEdit');
        } else {
            $_POST = input('post.');
            $remark = $_POST['remark'];
            $data = array(
                'admin_id' => session('admin_id'),
                'user_id' => $_POST['userid'],
                'add_time' => time(),
                'remark' => $remark,
            );

            unset($_POST['remark']);
            if ($id) {
                $old_coinlog = DB::name('UserCoin')->where(['userid' => $_POST['userid']])->find();
                if (DB::name('UserCoin')->update($_POST)) {
                    $new_coinlog = DB::name('UserCoin')->where(['userid' => $_POST['userid']])->find();
                    $coin = DB::name('Coin')->where(['status' => 1])->select();
                    foreach ($coin as $k => $v){
                        $data[$v['name']] = $new_coinlog[$v['name']] - $old_coinlog[$v['name']];
                    }
                  DB::name('coinlog')->insert($data);
                    $log = '管理员 ' . session('admin_username') . ' 修改了用户 ID ' . $_POST['userid'] . ' 的虚拟币 SQL 语句为： ' . DB::getLastSql();
                    mlog($log);
                    return $data;
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
    
            } else {
                if ($id = DB::table('weike_user_coin')->insert($_POST)) {
                    $log = '管理员 ' . session('admin_username') . ' 增加了用户 ID ' . $id . ' 的虚拟币 SQL 语句为： ' . DB::name()->getLastSql();
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
        $data['username'] = DB::name('User')->where(['id' => $userid])->value('username');
        $data['coinname'] = $coinname;

        //用户当前账户余额
        $data['balance'] = DB::name('UserCoin')->where(['userid' => $userid])->value($coinname);
        $data['freeze'] = DB::name('UserCoin')->where(['userid' => $userid])->value($coinname . 'd');
        $data['total'] = $data['balance'] + $data['freeze'];

        //用户充值成功金额，提现成功金额
        $data['recharge_cash'] = DB::name('Mycz')->where(['userid' => $userid, 'status' => 1])->sum('num');
        $data['recharge_person'] = DB::name('Mycz')->where(['userid' => $userid, 'status' => 2])->sum('num');
        $data['recharge_ant'] = DB::name('Mycz')->where(['userid' => $userid, 'status' => 5])->sum('num');
        $data['recharge_process'] = DB::name('Mycz')->where(['userid' => $userid, 'status' => 3])->sum('num');
        $data['withdraw_cash'] = DB::name('Mytx')->where(['userid' => $userid, 'status' => 1])->sum('num');
        $data['withdraw_process'] = DB::name('Mytx')->where(['userid' => $userid, 'status' => 3])->sum('num');

        //用户转入数量，转出数量
        if ($coinname != 'cny' && $coinname != 'hkd') {
            $data['turn_into_process'] = DB::name('Myzr')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => ['neq', '0']])->sum('num');
            $data['turn_into_success'] = DB::name('Myzr')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => 1])->sum('num');
            $data['turn_out_process'] = DB::name('Myzc')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => ['neq', '0']])->sum('num');
            $data['turn_out_success'] = DB::name('Myzc')->where(['coinname' => $coinname, 'userid' => $userid, 'status' => 1])->sum('num');
        }
        $this->assign('data', $data);
       return $this->fetch();
    }

    public function setpwd()
    {
        $_POST = input('post.');
        if ($this->request->isPost()) {
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

            if (model('Admin')->where(array('id' => session('admin_id')))->update(array('password' => md5($newpassword)))) {
                $this->success('登陆密码修改成功！', url('/admin/Login/loginout'));
            } else {
                $this->error('登陆密码修改失败！');
            }
        }

        return $this->fetch();
    }

    //管理员变动用户财产记录
    public function changelog()
    {
        $name =input('name');
        $field =input('field');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['user_id'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        $list = DB::name('Coinlog')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();  
        $list = $list->all();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = DB::name('User')->where(array('id' => $v['user_id']))->value('username');
            $list[$k]['adminname'] = DB::name('Admin')->where(array('id' => $v['admin_id']))->value('username');
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    public function weike_Excel()
    {

        $_POST = input('post.');

        $where = [];
        if ($_POST['username']) {
           $where['userid'] = DB::name('User')->where(array('username' => $_POST['username']))->value('id');

        }
        if ($_POST['id']) {
            $where['id'] = array('in',implode(',',$_POST['id']));
        }

        if(!$_POST['id']){
            $this->error('请选择要操作的数据!');
        }
        //用户名查看用户

        $list = DB::name('UserCoin')->where($where)->order('id desc')->select();

        if(!$list){
            $this->error('导出记录为空!');
        }

        foreach ($list as $k=>$v) {
            foreach ($v as $kk=>$vv){
                if (preg_match('/E/',$vv)){
                    $a = explode("e",strtolower($vv));
                    $list[$k][$kk] = bcmul($a[0], bcpow(10, $a[1], 9), 9);
                }
            }
        }

        foreach ($list as $k => $v) {
            $uid = $v['userid'];
            $list[$k]['username'] = DB::name('User')->where(array('id' => $uid))->value('username');
            $list[$k]['fenhong'] = DB::name('fenhong_log')->where(array('userid' => $uid))->sum('mum');
            //分红
            $list[$k]['fenhong'] = $list[$k]['fenhong'] ? $list[$k]['fenhong'] : 0;
            //recharge withdraw
            //充值
            $list[$k]['recharge_cash'] = DB::name('Mycz')->where(['userid' => $uid, 'status' => 1])->sum('num');
            $list[$k]['recharge_person'] = DB::name('Mycz')->where(['userid' => $uid, 'status' => 2])->sum('num');
            $list[$k]['recharge_ant'] = DB::name('Mycz')->where(['userid' => $uid, 'status' => 5])->sum('mum');
            //提现
            $list[$k]['withdraw_cash'] = DB::name('Mytx')->where(['userid' => $uid, 'status' => 1])->sum('num');

            //statistics every coin
            foreach (config('coin') as $key => $val) {
                //转入金额
                $list[$k]['turn_into'][$val['name']] = DB::name('Myzr')->where(['coinname' => $val['name'], 'userid' => $uid, 'status' => 1])->sum('mum');
                //转出金额
                $list[$k]['turn_out'][$val['name']] = DB::name('Myzc')->where(['coinname' => $val['name'], 'userid' => $uid, 'status' => 1])->sum('num');
                //买数量
                $list[$k]['coin_buy'][$val['name']] = DB::name('TradeLog')->where(['userid' => $uid, 'market' => $val['name'] . '_cny'])->sum('num');
                //卖数量
                $list[$k]['coin_sell'][$val['name']] = DB::name('TradeLog')->where(['peerid' => $uid, 'market' => $val['name'] . '_cny'])->sum('num');
                $list[$k]['shengyu']= round($list[$k]['turn_into'][$val['name']]+ $list[$k]['coin_buy'][$val['name']]- $list[$k]['turn_out'][$val['name']]-$list[$k]['coin_sell'][$val['name']],8);
            }


            $list[$k]['trade_award'] = DB::name('Invit')->where(['userid' => $uid])->sum('fee');
            //买入总额
            $list[$k]['num_buy'] = DB::name('TradeLog')->where(['userid' => $uid])->sum('mum');
            //卖入总额
            $list[$k]['num_sell'] = DB::name('TradeLog')->where(['peerid' => $uid])->sum('mum');
            //trade fee buy or sell
            //买入
            $list[$k]['fee_buy'] = DB::name('TradeLog')->where(['userid' => $uid])->sum('fee_buy');
            //卖出
            $list[$k]['fee_sell'] = DB::name('TradeLog')->where(['peerid' => $uid])->sum('fee_sell');
            //trade pay in or out
            $list[$k]['pay_in'] = $list[$k]['recharge_cash'] + $list[$k]['recharge_person'] + $list[$k]['recharge_ant']
                + $list[$k]['num_sell'] + $list[$k]['trade_award'];
            $list[$k]['pay_out'] = $list[$k]['withdraw_cash'] + $list[$k]['fee_buy'] + $list[$k]['fee_sell'] + $list[$k]['num_buy'];
            $list[$k]['pay_zong']= number_format($list[$k]['recharge_ant']+$list[$k]['recharge_person']+$list[$k]['recharge_cash'],8,".", "");
            //
            $list[$k]['cny']=  $v['cny'];
            $list[$k]['cnyd']=  $v['cnyd'];
            $list[$k]['cnyzong']= $list[$k]['cny']+$list[$k]['cnyd'];
            $list[$k]['btc']=  $v['btc'];
            $list[$k]['btcd']=  $v['btcd'];
            $list[$k]['btczong']= $list[$k]['btc']+$list[$k]['btcd'];
            $list[$k]['eth']=  $v['eth'];
            $list[$k]['ethd']=  $v['ethd'];
            $list[$k]['ethzong']= $list[$k]['eth']+$list[$k]['ethd'];
            $list[$k]['etc']=  $v['etc'];
            $list[$k]['etcd']=  $v['etcd'];
            $list[$k]['etczong']= $list[$k]['etc']+$list[$k]['etcd'];
            $list[$k]['doge']=  $v['doge'];
            $list[$k]['doged']=  $v['doged'];
            $list[$k]['dogezong']= $list[$k]['doge']+$list[$k]['doged'];
            $list[$k]['wc']=  $v['wc'];
            $list[$k]['wcd']=  $v['wcd'];
            $list[$k]['wczong']= $list[$k]['wc']+$list[$k]['wcd'];
            $list[$k]['ifc']=  $v['ifc'];
            $list[$k]['ifcd']=  $v['ifcd'];
            $list[$k]['ifczong']= $list[$k]['ifc']+$list[$k]['ifcd'];
            $list[$k]['qtum']=  $v['qtum'];
            $list[$k]['qtumd']=  $v['qtumd'];
            $list[$k]['qtumzong']= $list[$k]['qtum']+$list[$k]['qtumd'];
            $list[$k]['bcd']=  $v['bcd'];
            $list[$k]['bcdd']=  $v['bcdd'];
            $list[$k]['bcdzong']= $list[$k]['bcd']+$list[$k]['bcdd'];
            $list[$k]['bcx']=  $v['bcx'];
            $list[$k]['bcxd']=  $v['bcxd'];
            $list[$k]['bcxzong']= $list[$k]['bcx']+$list[$k]['bcxd'];
            $list[$k]['eac']=  $v['eac'];
            $list[$k]['eacd']=  $v['eacd'];
            $list[$k]['eaczong']= $list[$k]['eac']+$list[$k]['eacd'];
            $list[$k]['ejf']=  $v['ejf'];
            $list[$k]['ejfd']=  $v['ejfd'];
            $list[$k]['ejfzong']= $list[$k]['ejf']+$list[$k]['ejfd'];
            $list[$k]['oioc']=  $v['oioc'];
            $list[$k]['oiocd']=  $v['oiocd'];
            $list[$k]['oioczong']= $list[$k]['oioc']+$list[$k]['oiocd'];
            $list[$k]['wcg']=  $v['wcg'];
            $list[$k]['wcgd']=  $v['wcgd'];
            $list[$k]['wcgzong']= $list[$k]['wcg']+$list[$k]['wcgd'];
            $list[$k]['btm']=  $v['btm'];
            $list[$k]['btmd']=  $v['btmd'];
            $list[$k]['btmzong']= $list[$k]['btm']+$list[$k]['btmd'];
            $list[$k]['eos']=  $v['eos'];
            $list[$k]['eosd']=  $v['eosd'];
            $list[$k]['eoszong']= $list[$k]['eos']+$list[$k]['eosd'];
        }
        $xlsName = 'usercoin_log';
        $xls = array(
            array('username','用户名'),
//            array('coin_buy','买入'),
//            array('coin_sell','卖出'),
//            array('turn_into','转入'),
//            array('turn_out','转出'),
//            array('shengyu','剩余'),
            array('fee_buy','买入手续费'),
            array('fee_sell','卖出手续费'),
            array('fenhong','分红金额'),
            array('trade_award','市场交易赠送'),
            array('num_buy','买入总金额'),
            array('num_sell','卖出总金额'),
            array('pay_in','总收入'),
            array('pay_out','总支出'),
            array('recharge_cash','充值成功'),
            array('recharge_person','人工充值'),
            array('recharge_ant','花呗充值'),
            array('pay_zong','总计充值'),
            array('withdraw_cash','总计提现'),
            array('cny','可用人民币'),
            array('cnyd','冻结人民币'),
            array('cnyzong','总计人民币'),
            array('btc','比特币'),
            array('btcd','冻结比特币'),
            array('btczong','总计比特币'),
            array('eth','以太坊'),
            array('ethd','冻结以太坊'),
            array('ethzong','总计以太坊'),
            array('etc','以太经典'),
            array('etcd','冻结以太经典'),
            array('etczong','总计以太经典'),
            array('doge','狗狗币'),
            array('doged','冻结狗狗币'),
            array('dogezong','总计狗狗币'),
            array('wc','云尊币'),
            array('wcd','冻结云尊币'),
            array('wczong','总计云尊币'),
            array('ifc','无限币'),
            array('ifcd','冻结无限币'),
            array('ifczong','总计无限币'),
            array('qtum','量子链'),
            array('qtumd','冻结量子链'),
            array('qtumzong','总计量子链'),
            array('bcd','比特钻石'),
            array('bcdd','冻结比特钻石'),
            array('bcdzong','总计比特钻石'),
            array('bcx','比特无限'),
            array('bcxd','冻结比特无限'),
            array('bcxzong','总计比特无限'),
            array('eac','地球币'),
            array('eacd','冻结地球币'),
            array('eaczong','总计地球币'),
            array('ejf','胶积分'),
            array('ejfd','冻结胶积分'),
            array('ejfzong','总计胶积分'),
            array('oioc','交子币'),
            array('oiocd','冻结交子币'),
            array('oioczong','总计交子币'),
            array('wcg','华克金'),
            array('wcgd','冻结华克金'),
            array('wcgzong','总计华克金'),
            array('btm','比原链'),
            array('btmd','冻结比原链'),
            array('btmzong','总计比原链'),
            array('eos','柚子'),
            array('eosd','冻结柚子'),
            array('eoszong','总计柚子'),
        );

//       dump($list);die;
        $this->exportExcel($xlsName, $xls, $list);
    }

    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $_SESSION['account'].date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        vendor("PHPExcel.PHPExcel");

        include ROOT_PATH."thinkphp/library/vendor/PHPExcel/PHPExcel.php";

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
