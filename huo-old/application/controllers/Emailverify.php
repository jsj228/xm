<?php
/**
 * 邮件认证
 */

class EmailverifyController extends Ctrl_Base{

    #普通用户
    protected $_auth =1;

    public function indexAction()
    {
        $tMO = new EmailactivateModel;
        $email=$_SESSION['emailactive']['email'];
        if (!empty($email)) {
            $pData = $tMO->fRow("SELECT activate_time FROM email_activate WHERE email ='$email'");
            if (!isset($pData['activate_time']) || !empty($pData['activate_time'])) {
                Tool_Fnc::showMsg('', '/index');//
            }
        }
    }

    #重新发送邮件
    public function retrysentAction()
    {
        $eMO = new EmailactivateModel;
        $uMO = new UserModel;
        $email= $_SESSION['emailactive']['email'];
        if(empty($email)){
            $this->ajax($GLOBALS['MSG']['TEL_ZJ_CS'], 0);
        }
        $eData = $eMO->fRow("SELECT * FROM email_activate WHERE email = '$email'");
        if(!empty($eData['activate_time'])){
            $this->ajax($GLOBALS['MSG']['EMAIL_SUCCESS'],0);
        }#激活成功

        //发送邮件时间判断
        $pNowtime = time();
        if(isset($_SESSION['sentemail_time'])){
            $pTime = $pNowtime-$_SESSION['sentemail_time'];
            if($pTime <= 30*60){
                $this->ajax($GLOBALS['MSG']['EMAIL_ZIX'], 0);
            }
        }
        $_SESSION['sentemail_time'] = $eData['reg_time'];

        $pKey = Tool_Md5::emailActivateKey($email , $pTime);
        $pData = array(
            'email' => $email,
            'reg_time' => $_SERVER['REQUEST_TIME'],
            'senttime' => $_SERVER['REQUEST_TIME'],
            'activate_time' => 0,
            'key' => $pKey,
        );
        if(empty($eData)){
            if(!$eMO->insert($pData)){
                $this->ajax($GLOBALS['MSG']['EMAIL_SB'], 0);
            }
        }else{
            if(!$eMO->where(array('email'=>$email))->update($pData)){
                $this->ajax($GLOBALS['MSG']['EMAIL_SB'], 0);
            }
        }
        UserModel::saveEmailRedis(array(
            'key' => $pKey,
            'email' => $email,
        ));
            $this->ajax($GLOBALS['MSG']['EMAIL_SUCCESSSB'],1);

    }

    #邮箱激活验证
    public function emailactivateAction()
    {
        $pId = trim($_GET['id']) or Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
        $pUrl = unserialize(base64_decode($pId));

        is_array($pUrl) ? '' : Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
        if (empty($pUrl['email']) || empty($pUrl['key'])) {
            Tool_Fnc::showMsg($GLOBALS['MSG']['TEL_ZJ_CS']);
        }

        if (!Tool_Validate::email($pUrl['email'])) {
            Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
        }
        if (!Tool_Validate::safe($pUrl['key'])) {
            Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
        }

        $tMO = new EmailactivateModel;
        $tData = $tMO->fRow('SELECT * FROM email_activate WHERE email = \'' . $pUrl['email'] . '\' limit 1');
        if (!count($tData)) {
            Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
        }

        #激活时间限制
        $time = time();
        if (($time - $tData['senttime']) > 1800) {
            Tool_Fnc::showMsg('', '/index?emailactive=2');//激活时间不能超过30分钟,请您重新注册！
        }
        if ($tData['activate_time']) {
            $_SESSION['user'] = null;
            if ($this->mCurUser) unset($_SESSION['user']);
            Tool_Fnc::showMsg('', '/index?emailactive=1');//您的邮箱已经激活
        }
        if ($pUrl['key'] == $tData['key']) {
            $db2 = Yaf_Registry::get("config")->redis->default->db;
            $userdata=UserModel::lookRedis($db2, 'emailactive', $pUrl['email']); ////邮箱激活 存用户信息
            $arr=explode(',', $userdata);
            $tData = array(
                'email' => $arr[1],
                'prand' => $arr[3],
                'pwd' => $arr[0],
                'created' => $_SERVER['REQUEST_TIME'],
                'createip' => Tool_Fnc::realip(),
                'role' => $arr[2],
                'registertype' => '1',
                'updated' => $_SERVER['REQUEST_TIME'],
                'from_uid'=> $arr[6]
            );
            $tMO = new UserModel;
            $tMO->begin();
            if (!$tMO->exec("UPDATE email_activate set activate_time =$time WHERE email ='$pUrl[email]'")) {
                $tMO->back();
                Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
            }
            if (!$tData['uid']=$tMO->insert($tData)) {
                $tMO->back();
                Tool_Fnc::showMsg('', '/index?emailactive=0');//激活失败
            }else{
                $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
                $db2 = Yaf_Registry::get("config")->redis->default->db;
                $db3 = Yaf_Registry::get("config")->redis->user->db;

                UserModel::addRedis($db2, 'useremail', $tData['email'], $tData['uid']); //库2
                UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
                UserModel::delRedis($db2, 'emailactive', $tData['email']); ////删除暂存的 用户信息

                Cache_Redis::instance()->hSet('usersession', $tData['uid'], 1);
                $tMO->commit();
                $_SESSION['user']=null;
                Tool_Fnc::showMsg('', '/index?emailactive=1');//激活成功

            }
        } else {
            Tool_Fnc::showMsg('', '/index?emailactive=0');//激活连接已失效
        }
    }

    #找回密码或交易密码发送邮件
    public function findpwdAction()
    {
        $postdata =$this->rsaDecode()?:$_POST;
        $rnd      = rand(1000,time());          //随机验证码
        $rand     = substr($rnd, 0, 6);
        $userMo =  new UserModel();
        if($postdata['regtype']=='forget')   //登入密码找回
        {
           if(!$_SESSION['confirm'] && !$this->mCurUser)
            {
                $this->ajax($GLOBALS['MSG']['ILLEGAL'],0,'error');
            }
            $msg = "{$GLOBALS['MSG']['EMAIL_DB_YZM']}{$rand}{$GLOBALS['MSG']['EMAIL_NH']}{$_SESSION['email']}{$GLOBALS['MSG']['EMAIL_SQZH']}";//申请找回登录密码
            $email = $this->mCurUser['email']?:$_SESSION['email'];
            $tltle =$GLOBALS['MSG']['EMAIL_YZM'];
            if($this->mCurUser)
            {
                $_SESSION['email']=$email;
            }
        }
        elseif ($postdata['regtype']=='trust')//找回交易密码或提币验证码
        {
            $msg = "{$GLOBALS['MSG']['EMAIL_DB_YZM']} {$rand}{$GLOBALS['MSG']['EMAIL_DR']}";
            $email = $this->mCurUser['email'];
            $_SESSION['email']=$this->mCurUser['email'];
            $tltle = $GLOBALS['MSG']['EMAIL_YZM'];
        }
        elseif($postdata['regtype']=='pinless')//绑定邮箱
        {
            $msg = "{$GLOBALS['MSG']['EMAIL_DB_YZM']} {$rand}{$GLOBALS['MSG']['EMAIL_DR']}";
            $emailY = new ValidatelogicModel();
            $email = trim($postdata['email']);
            if(!$emailY->regexEmail($email))
            {
                 $this->ajax($GLOBALS['MSG']['EMAIL_FORMAT']);
            }
            $data = $userMo->where("email = '{$email}'")->fRow();
            $tltle ="{$GLOBALS['MSG']['EMAIL_YZM']}"; //多比邮箱验证码
            if($data['email'])
            {
                $this->ajax($GLOBALS['MSG']['EMAIL_TO_USE'],0,'email');//邮箱已被使用
            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'],0,'error');
        }
        if($postdata['regtype']=='pinless')
        {
            $erroeuser['uid']=$this->mCurUser['uid'];
        }
        else
        {
            $erroeuser = $userMo->field('uid,email,mo')->where("email = '{$email}'")->fRow();

        }
       
        $key =  $key =  'resetPwdsError' .$erroeuser['uid'];
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'code');//輸入錯誤次數過多，請稍後再試
        }

        $pc = new PhoneCodeModel();
        if($code = $pc->fRow("select * from {$pc->table} where email='{$email}' and action=2  order by id desc"))
        {
            if($code['ctime']+60 > time())
            {
                $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'],0,'email');// 請您過60秒再點擊發送
            }

        }

       $list =  UserModel::saveEmailRedis(array(
            'find' => 1,
            'tltle'=>$tltle,
            'msg'  =>$msg,
            'email' =>$email,
        ));
          if($list!=1)  //邮件发送失败
          {
              $this->ajax($GLOBALS['MSG']['EMAIL_SB'],0,'code');
          }
        $tData = array(
            'email' => $email,
            'code' => $rand,
            'message' => $msg,
            'ctime' => time(),
            'action' => 2,   //2是邮箱
            'status' => 0,

        );
        $pc = new PhoneCodeModel();
        if(!$pc->insert($tData))
        {
            $this->ajax($GLOBALS['MSG']['EMAIL_SB'],0,'error');
        }
        $this->ajax($GLOBALS['MSG']['EMAIL_SUCCESSSB'],1);
    }

}
