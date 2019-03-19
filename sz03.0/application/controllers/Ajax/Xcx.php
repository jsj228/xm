<?php
die('接口关闭');
class Ajax_XcxController extends Ajax_BaseController
{
    protected $key='qLgN89svNK8jZDYTiMmrPBO1NRhqiHU7';
    //小程序设置密码
    public function setPwdAction(){
        header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
		header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
		header("Content-Type: application/json; charset=utf-8");
        die;
        if (!isset($_POST['pwd'], $_POST['key'], $_POST['uid'])) {
            $this->ajax('参数错误');
        }
        if(trim($_POST['key'])!=$this->key){
            $this->ajax('key 错误');
        }
        $uid= trim($_POST['uid']);
        $usermo=new UserModel();
        $userlist=$usermo->where("uid=$uid")->fList();
        if(empty($userlist)){
            $this->ajax('用户不存在');
        }
        $prand= $userlist[0]['prand'];
        $pwd= trim($_POST['pwd']);
        $pwds= Tool_Md5::encodePwdMD5($pwd, $prand);
        $arr=array(
            'pwd'=> $pwds
        );
        $upda=$usermo->where("uid=$uid")->update($arr);
        if($upda){
            $value = $pwds . ',' . $userlist[0]['uid'] . ',' . $userlist[0]['role'] . ',' . $userlist[0]['prand'];
            $db2 = Yaf_Registry::get("config")->redis->default->db;
            $db3 = Yaf_Registry::get("config")->redis->user->db;
            if ($userlist[0]['area'] == '+86') {
                $phone = $userlist[0]['mo'];
            } else {
                $phone = $userlist[0]['area'] . $userlist[0]['mo'];
            }
            UserModel::addRedis($db2, 'userphone', $phone, $userlist[0]['uid']); //库2
            UserModel::addRedis($db3, 'uid', $userlist[0]['uid'], $value); ////库3
            $this->ajax('设置登录密码成功',1);
        }else{
            $this->ajax('设置登录密码失败',0);
        }
    }

    //小程序注册火网账号
    public function xcxregisterAction()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
        header("Content-Type: application/json; charset=utf-8");
        if (!isset($_POST['key'],$_POST['mo'], $_POST['role'], $_POST['area'], $_POST['prand'], $_POST['created'], $_POST['createip'], $_POST['registertype'], $_POST['from_uid'])) {
            $this->ajax('参数错误');
        }
        if (trim($_POST['key']) != $this->key) {
            $this->ajax('key 错误');
        }
        $data = array(
            'mo' => trim($_POST['mo']),
            'prand' => trim($_POST['prand']),
            'created' => trim($_POST['created']),
            'createip' => trim($_POST['createip']),
            'registertype' => trim($_POST['registertype']),
            'role' => trim($_POST['role']),
            'from_uid' => trim($_POST['from_uid']),
            'area' => trim($_POST['area']),
        );
        $usermo = new UserModel();
        $list=$usermo->where("mo=$data[mo] and area='$data[area]'")->fList();
        if(!empty($list)){
            $this->ajax('该用户已存在',1);
        }
        $userlist = $usermo->insert($data);
        if($userlist){
            $this->ajax('注册成功',1);
        }else{
            $this->ajax('注册失败');
        }
    }

    //小程序提币到火网
    public function xcxoutcoinAction()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
        header("Content-Type: application/json; charset=utf-8");
        if (!isset($_POST['key'], $_POST['uid'], $_POST['coin'], $_POST['number'], $_POST['ip'])) {
            $this->ajax('参数错误');
        }
        if (trim($_POST['key']) != $this->key) {
            $this->ajax('key 错误');
        }
        $data = array(
            'uid' => trim(addslashes($_POST['uid'])),
            'coin' => trim(addslashes($_POST['coin'])),
            'number' => trim(addslashes($_POST['number'])),
            'ip' => trim(addslashes($_POST['ip'])),
        );
        $db = new UserModel();
        $uidlist=$db->where("uid={$data['uid']}")->fList();
        if(empty($uidlist)){
            $this->ajax('该用户不存在');
        }
        $db->begin();
        $time = time();

        //更新火网的用户资产
        if (!$db->exec("update user set {$data['coin']}_over={$data['coin']}_over+{$data['number']},updated={$time},updateip='{$data['ip']}' where uid={$data['uid']}")) {
            $db->back();
            $this->ajax('更新失败');
        }
        //更新火网4用户资产
        if (!$db->exec("update user set {$data['coin']}_over={$data['coin']}_over-{$data['number']},updated={$time},updateip='{$data['ip']}' where uid=4")) {
            $db->back();
            $this->ajax('更新失败');
        }
        //插入火网转入转出表
        $sql = sprintf("insert into exchange_{$data['coin']} (uid,number,opt_type,status,created,createip,bak)values
          (%s,%s,'in','成功',%s,'%s','小程序提取到火网账户')", $data['uid'], $data['number'], $time, $data['ip']);
        if (!$db->exec($sql)) {
            $this->back();
            $this->ajax('更新失败');
        }

        if ($db->commit()) {
            $this->ajax('更新成功',1);
        } else {
            $this->ajax('更新失败');
        }
    }

}
