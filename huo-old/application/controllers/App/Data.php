<?php

class App_DataController extends App_BaseController
{
    protected $_auth = 1;

    public function init()
    {
        parent::init();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
        header("Content-Type: application/x-www-form-urlencoded; charset=utf-8");
    }
    //获取区号
    public function getPhoneareaAction()
    {
        $phone_area = new PhoneAreaCodeModel();
        $langue=LANG;
        $serach_input=addslashes(trim($_POST['serach_input']));
        if (!empty($serach_input)) {
            $list = $phone_area->where("langue='$langue' and area_code like '%$serach_input%' or langue='$langue' and country like '%$serach_input%'")->order("`character` asc")->fList();
            $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'], 1, $list);//成功
        }
        $hot=$phone_area->where("langue='$langue' and id<=20")->fList();
        $data = array();
        foreach($hot as &$vv){
            $vv['character']='hot';
            unset($vv['id']);
            unset($vv['langue']);
            $data[]= $vv;
        }
        $list = $phone_area->where("langue='$langue'")->order("`character` asc")->fList();

        foreach($list as $v){
            if($v['id']<=20){
               continue;
            }
            unset($v['id']);
            unset($v['langue']);
            $data[]= $v;
        }
        $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'], 1, $data);//成功

    }
    //发送邮件
    public function sendemailAction()
    {
        if ($this->mCurUser && $this->mCurUser['email']) {
            $email = $this->mCurUser['email'];
        } else {
            $email = addslashes(trim($_POST['email']));
        }
        $action = addslashes(trim($_POST['action']));

        if($action!='1')
        {
            $key = 'resetPwdsError' .$this->mCurUser['uid'];

        }else   //注册
        {
            $key = 'resetPwdsError' .$email;
        }
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
        }




        $validata = new ValidatelogicModel();
        if (!in_array($action, array(1, 11, 6, 7))) { //1 注册 11密码 6绑定 7通用
            $this->ajax('action 错误', 0);
        }
        if ($action == 1 || $action == 6) {//注册
            $checkdata = [
                'email' => $email
            ];
            $result = $validata->scene('app_email_register')->check($checkdata);
        }elseif ($action == 11|| $action ==7){
            $checkdata = [
                'findpwd_email' => $email
            ];
            $result = $validata->scene('app_email_findpwd')->check($checkdata);
        }

        if (!$result) {
            foreach ($validata->getError() as $k => $v) {
                $errordata = $v;
                $errorM = $k;
            }
            $this->ajax($errordata, 0, $errorM);
        }
        if ($action == 6) {
            $action = 7;
        }
        $time = time();
        $start = $time - 3600;
        $count = PhoneCodeModel::getInstance()->where("email = '$email' and ctime >= {$start} and ctime <= {$time} and action = {$action}")->count();
        if ($count >= 10) {
            //郵件發送過於頻繁，一小時後再操作
            $this->ajax($GLOBALS['MSG']['SENT_EMAIL_FAST'], 0, 'vcode');
        }
        $pc = new PhoneCodeModel();
        if($action==1){//注册
            $result = $pc->sendemail($email, 1);
        }elseif($action == 11){//找回密码
            $result = $pc->sendemail($email, 11);
        }elseif($action == 7){
            $result = $pc->sendemail($email, 7);//通用
        }
        if($result=='200'){//成功
            if($action==11) {
                $userMo = new UserModel();
                $list = $userMo->field("google_key")->where("email='$email'")->fList();
                if (empty($list[0]['google_key'])) {//没有绑定谷歌
                    $da['isset_google'] = 0;
                } else {
                    $da['isset_google'] = 1;
                }
                $this->ajax($GLOBALS['MSG']['EMAIL_SUCCESSSB'], 1, $da);//发送成功
            }
            $this->ajax($GLOBALS['MSG']['EMAIL_SUCCESSSB'], 1);//发送成功
        }else{
            $this->ajax($GLOBALS['MSG']['EMAIL_SB'], 0);//发送失败
        }

    }

    //app送短信
    public function sendregmsgAction()
    {
        if($this->mCurUser&& $this->mCurUser['mo']){
            $phone = $this->mCurUser['mo'];
            $area = $this->mCurUser['area'];
        }else{
            $phone = addslashes(trim($_POST['phone']));
            $area = $_POST['area'] ? addslashes(trim($_POST['area'])) : '+86';
        }
        $action= addslashes(trim($_POST['action']));
        if($action!='1')
        {
            $key = 'resetPwdsError' .$this->mCurUser['uid'];

        }else   //注册
        {
            $key = 'resetPwdsError' .$phone;
        }
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
        }

        if (!in_array($action, array(1, 11, 6, 7, 8))) {//1 注册 11密码 6绑定 7通用 8语音
            $this->ajax('action 错误', 0);
        }
        $validata = new ValidatelogicModel();

        if($action==1 || $action == 6){//注册
            $postdata = [
                'mo' => $phone,
                'area' => $area
            ];
            $result = $validata->scene('app_sendregmsg_register')->check($postdata);
        }elseif($action == 11|| $action == 7){//11找回密码 7通用
            $postdata = [
                'findpwd_mo' => $phone,
                'area' => $area
            ];
            $result = $validata->scene('app_sendregmsg_findpwd')->check($postdata);
        }

        if (!$result) {
            foreach ($validata->getError() as $k => $v) {
                $errordata = $v;
                $errorM = $k;
            }
            $this->ajax($errordata, 0, $errorM);
        }
        if ($_POST['action'] == 8 && $area != '+86') {
            $this->ajax($GLOBALS['MSG']['GUOJI_VOICE'], 0, 'vcode');//国际语音暂不支持
        }
        // action==11 是找回密码 action==8语音 1 注册 7通用
        if ($_POST['action'] && $_POST['action'] == 11) {
            $type = '11';
        } else if ($_POST['action'] && $_POST['action'] == 8) {
            $type = '8'; //语音
        } else if ($_POST['action'] && ($_POST['action'] == 7 || $_POST['action'] == 6)) {
            $type = '7'; //通用
        } else {
            $type = '1';
        }
        $num = 0; //$_POST['num'];
        if (!$type = abs($type)) {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 0, '');//参数错误
        }
        # 验证登录
        $time = time();
        $start = $time - 3600;
        $count = PhoneCodeModel::getInstance()->where("mo = {$phone} and area='$area' and ctime >= {$start} and ctime <= {$time} and action = {$type}")->count();
        if ($count >= 10) {
            //短信过于频繁，请使用语音验证码
            $this->ajax($GLOBALS['MSG']['SMS_TO_VOICE_WARN'], 0, 'vcode');
        }
        if (PhoneCodeModel::regverifiTime($phone, $type, $area)) {////判断60秒是否重复送
            if ($type == '8') {
                $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'vcode'); //禁止语音
                //语音
                $user['mo'] = $phone;
                $code = PhoneCodeModel::sendCode($user, $type, $area, $num);
            } else {
                $code = PhoneCodeModel::sendregCode($phone, $type, $area, $num); //短信
            }
            if ($code == '200') {
                if ($action == 11) {
                    $userMo = new UserModel();
                    $list = $userMo->field("google_key")->where("mo=$phone and area='$area'")->fList();
                    if (empty($list[0]['google_key'])) {//没有绑定谷歌
                        $da['isset_google'] = 0;
                    } else {
                        $da['isset_google'] = 1;
                    }
                }

                //一分钟只能发一条
                if($this->mCurUser)
                {
                    $cKey = 'sms_' . $this->mCurUser['uid'] . '_A' . $action;
                    $cache = Cache_Redis::instance()->get($cKey);
                    if($cache)
                    {
                        $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'], 0, 'vcode');
                    }
                    Cache_Redis::instance()->set($cKey, '1', 60);
                }
                $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1, isset($da)?$da:''); //发送成功
            } else {
                $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'vcode'); //发送失败
            }
        } else {
            $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'], 0, 'vcode'); //请您过60秒再点击发送
        }
    }

    //app注册第一步
    public function registeroneAction()
    {
        $_POST = $this->rsaDecode() ?: $_POST;
        $_POST['regtype'] = trim($_POST['regtype']);
        if ($_POST['regtype'] == 'email') //邮箱
        {
            $email = addslashes(trim($_POST['account']));
            $code = addslashes(trim($_POST['code']));
            $key = 'resetPwdsError' .$email;
            $redis = Cache_Redis::instance();
            $count = $redis->get($key);

            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }
            $validata = new ValidatelogicModel();
            $postdata = [
                'email' => $email,
                'emailcode_register' => $code,
            ];
            $result = $validata->scene('app_registeremail')->check($postdata);

            if (!$result)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='emailcode_register')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $tData = array(
                'regtype' => 'email',
                'email' => $email,
            );
            $_SESSION['registerone'] = $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        } elseif ($_POST['regtype'] == 'phone') //手机注册
        {
            $phone = addslashes(trim($_POST['account']));
            $code = addslashes(trim($_POST['code']));
            $area = $_POST['area'] ? addslashes(trim($_POST['area'])) : '+86';//手机区号
            $key = 'resetPwdsError' .$phone;
            $redis = Cache_Redis::instance();
            $count = $redis->get($key);

            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }
            $validata = new ValidatelogicModel();
            $postdata = [
                'mo' => $phone,
                'smsCaptch' => $code,
                'area' => $area
            ];

            $result = $validata->scene('app_register')->check($postdata);

            if (!$result)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='smsCaptch')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $tData = array(
                'regtype'=>'phone',
                'mo' => $phone,
                'area' => $area,//手机区号
            );
            $_SESSION['registerone']= $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        }

    }

    //app注册第二步
    public function registertwoAction()
    {
        $password1 = addslashes(trim($_POST['pwd']));
        $password2 = addslashes(trim($_POST['repwd']));
        $tMO = new UserModel;
        if(!$_SESSION['registerone']){
            $this->ajax($GLOBALS['MSG']['OPERATION_TIMEOUT']);//操作超时
        }else{
            $regtype = $_SESSION['registerone']['regtype'];

            $prand = Tool_Md5::getUserRand();
            $pwd = Tool_Md5::encodePwd($password1, $prand);
            $validata = new ValidatelogicModel();
            $checkdata = [
                'password' => $password1,
                'repassword' => $password2,
            ];
            $result = $validata->scene('app_register_pwd')->check($checkdata);
            if (!$result) {
                foreach ($validata->getError() as $k => $v) {
                    $errordata = $v;
                    $errorM = $k;
                }
                $this->ajax($errordata, 0, $errorM);
            }

            if($regtype=='email'){
                $tData = array(
                    'email' => $_SESSION['registerone']['email'],
                    'prand' => $prand,
                    'pwd' => $pwd,
                    'created' => $_SERVER['REQUEST_TIME'],
                    'createip' => Tool_Fnc::realip(),
                    'registertype' => '1',
                    'role' => 'user',
                    'updated' => $_SERVER['REQUEST_TIME'],
                    'updateip' => Tool_Fnc::realip(),
                );
                if ($tData['uid'] = $tMO->insert($tData)) {
                    $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
                    $db2 = Yaf_Registry::get("config")->redis->default->db;
                    $db3 = Yaf_Registry::get("config")->redis->user->db;
                    UserModel::addRedis($db2, 'useremail', $tData['email'], $tData['uid']); //库2
                    UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
                    $_SESSION['registerone'] = null;
                    Tool_Session::mark($tData['uid']);
                    $this->ajax($GLOBALS['MSG']['REGISTER_SUCCESS'], 1, '');//注册成功
                } else {
                    $this->ajax($GLOBALS['MSG']['REGISTER_FAIL'], 0, 'fail');//注册失败
                }
            }elseif($regtype == 'phone'){
                $tData = array(
                    'mo' => $_SESSION['registerone']['mo'],
                    'prand' => $prand,
                    'pwd' => $pwd,
                    'created' => $_SERVER['REQUEST_TIME'],
                    'createip' => Tool_Fnc::realip(),
                    'registertype' => '2',
                    'role' => 'user',
                    'updated' => $_SERVER['REQUEST_TIME'],
                    'updateip' => Tool_Fnc::realip(),
                    'area' => $_SESSION['registerone']['area'],//手机区号
                );
                if ($tData['uid']=$tMO->insert($tData)) {
                    $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
                    $db2 = Yaf_Registry::get("config")->redis->default->db;
                    $db3 = Yaf_Registry::get("config")->redis->user->db;
                    if ($tData['area'] == '+86') {
                        $phone = $tData['mo'];
                    } else {
                        $phone = $tData['area'] . $tData['mo'];
                    }
                    UserModel::addRedis($db2, 'userphone', $phone, $tData['uid']); //库2
                    UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
                    $_SESSION['registerone']=null;
                    Tool_Session::mark($tData['uid']);
                    $this->ajax($GLOBALS['MSG']['REGISTER_SUCCESS'], 1, '');//注册成功
            }else{
                    $this->ajax($GLOBALS['MSG']['REGISTER_FAIL'], 0, 'fail');//注册失败
                }
            }
        }
    }

    /*
     * 登入
     **/
    public function loginAction()
    {
        //相同token登錄
        if($this->mCurUser)
        {
            $this->response($GLOBALS['MSG']['ILLEGAL'], 2);
        }

        //接收数据
        $postdata = Tool_Request::post();
        $userMo = new UserModel();
        $email = strtolower(trim($_POST['account']));
        if ($postdata['regtype'] == 'email') {
            $data = [
                'email_bb' => addslashes(trim($postdata['account'])),
                'password_bb' => addslashes(trim($postdata['pwd'])),
            ];

            //实例化
            $validatelogicModel = new ValidatelogicModel();
            $result = $validatelogicModel->scene('loginemail_app')->check($data);
            //如果为空,则报错,并输出错误信息
            if (!$result) {
                foreach ($validatelogicModel->getError() as $k => $v) {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                if ($errorM == 'password_bb') {
                    $errorM = 'Upassword';
                }
                $this->ajax($errorData, 0, $errorM);
            }
            $user = $userMo->where(['email' => $data['email_bb']])->fRow();

        } else   //手机
        {
            $area = $postdata['area'] ? addslashes(trim($postdata['area'])) : '+86';
            $data = [
                'phone' => addslashes(trim($postdata['account'])),
                'Upassword' => addslashes(trim($postdata['pwd'])),
                'area' => $area
            ];

            $usermo = new UserModel();
            $userdd = $usermo->field('area')->where(array('mo' => $data['phone']))->fList();
            if (!empty($userdd)) {
                if (!in_array($area, array_column($userdd, 'area'))) {
                    $this->ajax($GLOBALS['MSG']['YUN_SEND_FAIL'], 0, 'phone');//輸入號碼與歸屬地不匹配
                }
            }

            // 实例化
            $validatelogicModel = new ValidatelogicModel();
            // 调用login场景，check方法验证
            $result = $validatelogicModel->scene('userlogin_app')->check($data);

            //如果为空 ，则报错，并输出错误信息
            if (!$result) {
                foreach ($validatelogicModel->getError() as $k => $v) {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                $this->ajax($errorData, 0, $errorM);
            }


            $user = $userMo->where(['mo' => $data['phone'], 'area' => "{$area}"])->fRow();
            if (!$user) {
                $db2 = Yaf_Registry::get("config")->redis->default->db;
                if ($area == '+86') {
                    UserModel::delRedis($db2, 'userphone', $data['phone']);
                } else {
                    UserModel::delRedis($db2, 'userphone', $area . $data['phone']);
                }
                $this->ajax($GLOBALS['MSG']['USER_NOT_EXISIT'], 0, 'mo');
            }
        }


        unset($user['pwd']);

        $realInfo = AutonymModel::getInstance()->field('name,cardtype,idcard')->where(['status' => 2, 'uid' => $user['uid']])->fRow();
        if ($realInfo) {
            $user['realInfo'] = $realInfo;
        }

        // 添加客户端标志
        $now = time();
        $usersession = (array)json_decode(Cache_Redis::instance()->hGet('usersession', $user['uid']), true) ?: [];
        foreach ($usersession as $k => $v) {
            //回收无效sessionid
            if (!isset($v['time']) || $now - $v['time'] > 86400) {
                unset($usersession[$k]);
            }
            //APP端只允許一個終端在線
            if(strpos($k, 'APP_')!==false)
            {
                unset($usersession[$k]);
                Cache_Redis::instance('user')->del($k);
                Cache_Redis::instance()->set('LOGOUT_' . $k, '1', (int)(Yaf_Registry::get("config")->session['timeout']?:3600));
            }
        }
        $usersession[session_id()] = ['time' => time(), 'status' => 0];
        Cache_Redis::instance()->hSet('usersession', $user['uid'], json_encode($usersession));


        //重置交易密码状态
        Tool_Md5::pwdTradeCheck($user['uid'], 'del');

        if ($postdata['regtype'] == 'phone')  //手机身份信息
        {
            //手机号打码
            $moLen = strlen($user['mo']);
            $mo = substr_replace($user['mo'], str_pad('',$moLen>7?4:$moLen-4,"*"), max(-8, -$moLen), -4);
            $returnData = array(
                'user' => array(
                    'phone' => $mo,
                ),
            );
        } elseif ($postdata['regtype'] == 'email')  //邮箱登入成功信息
        {
            $email_array = explode("@", $postdata['email']);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($postdata['account'], 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $postdata['account'], -1, $count);
            $rs = $prevfix . $str;
            $returnData = array(
                'user' => array(
                    'email' => $rs,
                ),
            );
        }


        if(!empty($user['google_key'])){//如果有谷歌验证器
            // 登录之后存储登录信息
            $_SESSION['aesKey'] = $_POST['aesKey'];
            $_SESSION['user_temporary'] = $user;//临时存储
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, ['need_google'=>1]);//成功 需要验证谷歌
        }else{
            // 登录之后存储登录信息
            $_SESSION['user'] = $user;
            $this->recordIpData($user);//记录ip信息
            $userMo->where("uid=$user[uid]")->update(['updated' => $now, 'updateip' => Tool_Fnc::realip()]);//更新登录时间
            $lang= LANG;
            $db3 = Yaf_Registry::get("config")->redis->user->db;
            UserModel::addRedis($db3, 'setlang', $user[uid], $lang);
            $returnData['tokens'] = $this->getTokens();
            $this->ajax($GLOBALS['MSG']['LOGIN_SUCCESS'], 1, $returnData);//登入成功
        }

    }

    private function recordIpData($user)//登录记录ip信息
    {
        if (!$user) {
            return false;
        } else {
            $eData = array(
                'uid' => $user['uid'],
                'ip' => Tool_Fnc::realip(),
                'time' => time()
            );

            $eRedis = Cache_Redis::instance('user');
            $dd = $eRedis->lpush('recordIP', json_encode($eData));
            return true;
        }

    }
    //验证谷歌
    public function checkgoogleAction(){
        $userMo = new UserModel();
        $user=$_SESSION['user_temporary'];
        if(!$user){
            $this->ajax('登录失败，请返回上一步', 0);//登录失败，请返回上一步
        }
        $google=trim($_POST['google']);
        if(!$google){
            $this->ajax('请输入谷歌验证码', 0);//请输入谷歌验证码
        }
        if (!Api_Google_Authenticator::verify_key($user['google_key'], $google)) {
            $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
        }

        if ($user['registertype'] ==2)  //手机身份信息
        {
            $returnData = array(
                'reUrl' => $_COOKIE['reurl'] ? str_replace('/?login', '', $_COOKIE['reurl']) : '/',
                'user' => array(
                    'phone' => preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $user['mo']),
                ),
            );
        } elseif ($user['registertype'] == 1)  //邮箱登入成功信息
        {
            $email_array = explode("@", $user['email']);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($user['email'], 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $user['email'], -1, $count);
            $rs = $prevfix . $str;
            $returnData = array(
                'reUrl' => $_COOKIE['reurl'] ? str_replace('/?login', '', $_COOKIE['reurl']) : '/',
                'user' => array(
                    'email' => $rs,
                ),
            );
        }
        $this->recordIpData($user);//记录ip信息
        $userMo->where("uid=$user[uid]")->update(['updated' => time(), 'updateip' => Tool_Fnc::realip()]);//更新登录时间
        $lang = LANG;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        UserModel::addRedis($db3, 'setlang', $user[uid], $lang);
        $_SESSION['user']= $_SESSION['user_temporary'];
        $_SESSION['user_temporary']=null;
        $returnData['tokens'] = $this->getTokens();
        $this->ajax($GLOBALS['MSG']['LOGIN_SUCCESS'], '1', $returnData);//登入成功
    }

    //是否绑定谷歌验证码
    public function issetgoogleAction()
    {
        $userMo = new UserModel();
        $postdata = Tool_Request::post();
        $type=trim($postdata['regtype']);
        $account= addslashes(trim($postdata['account']));
        $area = addslashes(trim($postdata['area']));
        if($type=='email'){
            $list=$userMo->field("google_key")->where("email='$account'")->fList();
            if(empty($list[0]['google_key'])){//没有绑定谷歌
                $this->ajax($GLOBALS['MSG']['MEI_BANG_GOOGLE'], 0);
            }else{
                $this->ajax($GLOBALS['MSG']['YI_BANG_GOOGLE'], 1);
            }
        }elseif($type == 'phone'){
            $list = $userMo->field("google_key")->where("mo=$account and area='$area'")->fList();
            if (empty($list[0]['google_key'])) {//没有绑定谷歌
                $this->ajax($GLOBALS['MSG']['MEI_BANG_GOOGLE'], 0);
            } else {
                $this->ajax($GLOBALS['MSG']['YI_BANG_GOOGLE'], 1);
            }
        }


    }


    /*
    * 获取各种token
    */
    private function getTokens()
    {
        if(isset($_SESSION['user']))
        {
            //签名密钥
            $_SESSION['userStatic']['secretKey'] = substr(md5(microtime(true)), rand(1, 6));
            $data = array(
                'wsToken'=>Tool_Code::id32Encode($_SESSION['user']['uid']), //加密uid， 用于websocket身份绑定
                'secretKey'=> $_SESSION['userStatic']['secretKey'],
            );
            $_POST['aesKey'] = $_POST['aesKey']?:$_SESSION['aesKey'];
            if(!isset($_POST['aesKey']) || strlen($_POST['aesKey'])<16)
            {
                $this->response($GLOBALS['MSG']['PARAM_ERROR'], 2);
            }

            //ASE 秘钥
            $aesKey = $_POST['aesKey'];
            //ASE 向量
            $iv = substr($aesKey, 0, 16);
            //ASE 加密
            $data = Tool_Aes::encrypt(json_encode($data), $aesKey, $iv);
        }
        
        return $data;
    }

    //app找回密码
    public function findpwdAction()
    {

        $_POST = $this->rsaDecode() ?: $_POST;
        $_POST['regtype'] = trim($_POST['regtype']);

        if ($_POST['regtype'] == 'email') //邮箱
        {
            $email = addslashes(trim($_POST['account']));
            $code = addslashes(trim($_POST['code']));
            $userMo = new UserModel();
            $googledata=$userMo->field("google_key,uid")->where("email='$email'")->fList();
            $key = 'resetPwdsError' .$googledata[0]['uid'];
            $redis = Cache_Redis::instance();
            $count = $redis->get($key);

            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }

            $validata = new ValidatelogicModel();
            $postdata = [
                'email_app_find' => $email,
                'emailcode_app_find' => $code,
            ];
            $result = $validata->scene('app_findemail_pwd')->check($postdata);

            if (!$result) {
                foreach ($validata->getError() as $k => $v) {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='emailcode_app_find')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $google= trim($_POST['google']);


            if (!empty($googledata[0]['google_key'])) {
                if (empty($google)) {
                    $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
                }
                if (!Api_Google_Authenticator::verify_key($googledata[0]['google_key'], $google)) {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
                }
            }

            $tData = array(
                'regtype' => 'email',
                'email' => $email,
            );
            $_SESSION['findpwd'] = $tData;
            $this->ajax($GLOBALS['MSG']['T_BODY_SUCCESS'], 1, '');//成功

        } elseif ($_POST['regtype'] == 'phone') //手机注册
        {
            $phone = addslashes(trim($_POST['account']));
            $code = addslashes(trim($_POST['code']));
            $area = $_POST['area'] ? addslashes(trim($_POST['area'])) : '+86';//手机区号
            $users=UserModel::getInstance()->where(array('mo' => $phone,'area' => $area))->fList();
            $key = 'resetPwdsError' .$users[0]['uid'];
            $redis = Cache_Redis::instance();
            $count = $redis->get($key);

            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }
            $validata = new ValidatelogicModel();
            $postdata = [
                'phone' => $phone,
                'code' => $code,
                'area' => $area
            ];

            $result = $validata->scene('app_findphone_pwd')->check($postdata);

            if (!$result) {
                foreach ($validata->getError() as $k => $v) {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='code')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $google = trim($_POST['google']);
            $userMo = new UserModel();
            $googledata = $userMo->field("google_key")->where("mo=$phone and area='$area'")->fList();
            if (!empty($googledata[0]['google_key'])) {
                if (!Api_Google_Authenticator::verify_key($googledata[0]['google_key'], $google)) {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
                }
            }
            $tData = array(
                'regtype' => 'phone',
                'mo' => $phone,
                'area' => $area,//手机区号
            );
            $_SESSION['findpwd'] = $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        }

    }

    /**
     *   app找回密码 重设登录密码
     */
    public function resetPasswordAction()
    {
        //接收数据
        $postdata = Tool_Request::post();
        $postdata = $this->rsaDecode() ?: $postdata;
        $sess=$_SESSION['findpwd'];
        if (!$sess) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }
        $data = [
            'password' => $postdata['password'],
            'repassword' => $postdata['repassword'],
        ];

        // 实例化
        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('resetPassword')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result) {
            foreach ($validatelogicModel->getError() as $k => $v) {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            $this->ajax($errorData, '0', $errorM);
        }
        $tMO = new UserModel;
        if ($sess['regtype'] == 'email') {
            $res = $tMO->field('email,pwd,pwdtrade,area,uid,mo,role,prand')->where(array('email' => $sess['email']))->fRow();
        } elseif ($sess['regtype'] == 'phone') {
            $res = $tMO->field('email,pwd,pwdtrade,area,uid,mo,role,prand')->where(array('mo' => $sess['mo'], 'area' => "$sess[area]"))->fRow();
        }
        if (!$res) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }
        $login = Tool_Md5::encodePwdTrade($data['repassword'], $res['prand']);
        if ($res['pwdtrade'] == $login) {//重置登入不能密码不能跟交易密码一致
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAMEY'], 0, 'pw1');
        }
        $prand = $res['prand'];
        $pwd = Tool_Md5::encodePwd($postdata['password'], $prand);
        $tData = array(
            'uid' => $res['uid'],
            'pwd' => $pwd,
            'updated' => $_SERVER['REQUEST_TIME']
        );
        if ($tMO->update($tData)) {
            $value = $pwd . ',' . $res['uid'] . ',' . $res['role'] . ',' . $prand;
            $db2 = Yaf_Registry::get("config")->redis->default->db;
            $db3 = Yaf_Registry::get("config")->redis->user->db;
            if ($sess['regtype'] == 'email') {
                UserModel::addRedis($db2, 'useremail', $res['email'], $res['uid']); //库2
            } elseif ($sess['regtype'] == 'phone') {
                if ($res['area'] != '+86') {
                    UserModel::addRedis($db2, 'userphone', $res['area'] . $res['mo'], $res['uid']); //库2
                } else {
                    UserModel::addRedis($db2, 'userphone', $res['mo'], $res['uid']); //库2
                }
            }
            UserModel::addRedis($db3, 'uid', $res['uid'], $value); ////库3
            Cache_Redis::instance()->hSet('usersession', $res['uid'], 1);
            session_destroy();
            $this->ajax($GLOBALS['MSG']['RETRIEVE_PASSWORD_SUCCESS'], 1, $GLOBALS['MSG']['MODIFY_LOGIN_PASSWORD']);
        } else {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }

    }

    /**
     *   语言列表
     */
    public function langueAction()
    {
        $languecodemo=new LanguageCodeModel();
        $languedata=$languecodemo->fList();
        foreach($languedata as &$v){
            if($v['code']==LANG){
                $v['selected']=1;
            }else{
                $v['selected'] = 0;
            }
            unset($v['id']);
            unset($v['created']);
        }
        $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'], 1, $languedata);//成功
    }

    /**
     *   账户安全
     */
    public function usersafeAction()
    {
        //$_SESSION['user']['uid']= 13231356;
        $this->_islogin();
        $uid = $_SESSION['user']['uid'];
        $Usermo = new UserModel();
        $userdata = $Usermo->field("area,mo,email,registertype,pwd,pwdtrade,google_key")->where("uid=$uid")->fList();
        $data=array();
        foreach($userdata as &$v){
            if($v['mo']){//手机
                if($v['area']=='+86'){
                    $data['mo']=substr($v['mo'],0,3).'****'. substr($v['mo'], -3);
                }else{
                    $data['mo'] = substr($v['mo'], 0, 3) . '****' . substr($v['mo'], -2);
                }
            }else{
                $data['mo'] = '';
            }
            if ($v['email']) {
                $email_array = explode("@", $v['email']);
                $not = substr_replace($email_array[0], '**', -4, 2);
                $data['email'] = $not . '@' . $email_array[1];
            }else{
                $data['email']='';
            }
            if($v['google_key']){
                $data['isset_google']=1;
            }else{
                $data['isset_google'] =0;
            }
            if ($v['pwdtrade']) {
                $data['isset_pwdtrade'] = 1;
            } else {
                $data['isset_pwdtrade'] = 0;
            }
        }
        $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'], 1, $data);//成功
    }

    //绑定手机
    public function bindingphoneAction()
    {
        //$_SESSION['user']['uid'] = 13231362;
        $this->_islogin();

        $tData = UserModel::getInstance()->fRow($this->mCurUser['uid']);
        $key = 'resetPwdsError' .$this->mCurUser['uid'];
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);
        if($tData['mo']){
            $this->ajax($GLOBALS['MSG']['ISSET_PHONE'], '0');//已绑定过手机
        }

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
        }

        $data = [
            'mo' => addslashes(trim($_POST['phone'])),
            'area' => addslashes(trim($_POST['area'])),
            'code'=> addslashes(trim($_POST['code'])),
        ];
        $google=addslashes(trim($_POST['google']));
        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('binding_mo')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result) {
            foreach ($validatelogicModel->getError() as $k => $v) {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            if($k=='code')
            {
                UserModel::getInstance()->finderror($key);
            }
            $this->ajax($errorData, '0', $errorM);
        }
        if ($tData['google_key'])  //有绑定谷歌验证码
        {
            if (empty($google)) {
                $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
            }
            if (!Api_Google_Authenticator::verify_key($tData['google_key'], $google)) {
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
            }
        }
        $list = [
            'uid' => $tData['uid'],
            'mo' => $data['mo']
        ];
        $usermo=new UserModel();

        if (!$usermo->update($list))   //保存数据
        {
            $this->ajax($GLOBALS['MSG']['SMS_SHIBAN'], 0);
        }
        $db2 = Yaf_Registry::get("config")->redis->default->db;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        if ($tData['area'] != '+86') {
            UserModel::addRedis($db2, 'userphone', $data['area'] . $data['mo'], $tData['uid']); //库2
        } else {
            UserModel::addRedis($db2, 'userphone', $data['mo'], $tData['uid']); //库2
        }
        $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
        UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
        Cache_Redis::instance()->hSet('usersession', $tData['uid'], 1);
        $this->ajax($GLOBALS['MSG']['EMAIL_BANGDING'],1);//绑定成功

    }

    //绑定邮箱
    public function bindingemailAction()
    {
        $_POST = $this->rsaDecode() ?: $_POST;
        //$_SESSION['user']['uid'] = 13231362;
        $uid = $_SESSION['user']['uid'];
        $this->_islogin();
        $tData = UserModel::getInstance()->fRow($uid);
        $key = 'resetPwdsError' .$uid;
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);
        if ($tData['email']) {
            $this->ajax($GLOBALS['MSG']['ISSET_EMAIL'], '0');//已绑定过邮箱
        }

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
        }

        $data = [
            'email' => addslashes(trim($_POST['email'])),
            'binding_emailcode' => addslashes(trim($_POST['code'])),
        ];
        $google = addslashes(trim($_POST['google']));
        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('binding_email')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result) {
            foreach ($validatelogicModel->getError() as $k => $v) {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            if($k=='binding_emailcode')
            {
                UserModel::getInstance()->finderror($key);
            }
            $this->ajax($errorData, '0', $errorM);
        }
        if ($tData['google_key'])  //有绑定谷歌验证码
        {
            if (empty($google)) {
                $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
            }
            if (!Api_Google_Authenticator::verify_key($tData['google_key'], $google)) {
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
            }
        }
        $list = [
            'uid' => $tData['uid'],
            'email' => $data['email']
        ];
        $usermo = new UserModel();

        if (!$usermo->update($list))   //保存数据
        {
            $this->ajax($GLOBALS['MSG']['SMS_SHIBAN'], 0);
        }
        $db2 = Yaf_Registry::get("config")->redis->default->db;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        UserModel::addRedis($db2, 'useremail', $data['email'], $tData['uid']); //库2
        $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
        UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
        Cache_Redis::instance()->hSet('usersession', $tData['uid'], 1);
        $this->ajax($GLOBALS['MSG']['EMAIL_BANGDING'], 1);//绑定成功

    }
    //生成谷歌key
  public function getgooglekeyAction(){
      $this->_islogin();
      $key=Api_Google_Authenticator::generate_secret_key();
      $regtype=$_POST['regtype']? $_POST['regtype']:'phone';
      if($regtype=='phone'){
          $account = $_SESSION['user']['mo'];
      }else{
          $account = $_SESSION['user']['email'];
      }
      $qrimages = urlencode('otpauth://totp/' . $account . '%20-%20dobitrade.com?secret=' . $key . '&issuer=' . $account);
      if($key){
          $data['qrimages']= $qrimages;
          $data['key'] = $key;
          $_SESSION['user_google_key']= $key;
          $this->ajax($GLOBALS['MSG']['D_SUCCESS'], 1, $data);//成功
      }else{
          $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY'], 0);//失败
      }

  }

    // 谷歌验证码二维码
    public function qrimagesAction()
    {
        $text = isset($_GET['text']) ? $_GET['text'] : 'null';
        $size = isset($_GET['size']) ? $_GET['size'] : 6;
        $margin = isset($_GET['margin']) ? $_GET['margin'] : 4;
        ob_clean();
        Tool_Qrcode::png($text, false, QR_ECLEVEL_L, $size, $margin, false);
        exit(0);
    }
    //绑定谷歌
    public function bindinggoogleAction()
    {
        $_POST = $this->rsaDecode() ?: $_POST;
        //$_SESSION['user']['uid'] = 13231362;
        $uid = $_SESSION['user']['uid'];
        $this->_islogin();
        $tData = UserModel::getInstance()->fRow($uid);

        if ($tData['google_key']) {
            $this->ajax($GLOBALS['MSG']['ISSET_GOOGLE'], 0);//已绑定过谷歌
        }
        $pwd=trim($_POST['pwd']);
        $google = trim($_POST['google']);
        $google_key = $_SESSION['user_google_key'];
        if(!$google_key){
            $this->ajax($GLOBALS['MSG']['GOOGLE_KEY_ERROR'], 0);//谷歌key错误
        }
        $newpwd = Tool_Md5::encodePwd($pwd, $tData['prand']);
        if($newpwd!= $tData['pwd']){//判断密码
            $this->ajax($GLOBALS['MSG']['TEL_CCW'], 0);//密码错误
        }
        if (!Api_Google_Authenticator::verify_key($google_key, $google)) {
            $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
        }
        $data=array(
            'uid'=> $uid,
            'google_key'=> $google_key
        );
        $usermo = new UserModel();
        if (!$usermo->update($data))   //保存数据
        {
            $this->ajax($GLOBALS['MSG']['SMS_SHIBAN'], 0);
        }else{
            $this->ajax($GLOBALS['MSG']['EMAIL_BANGDING'], 1);//绑定成功
        }
    }

    //解绑谷歌
    public function delgoogleAction()
    {
        $_POST = $this->rsaDecode() ?: $_POST;
        //$_SESSION['user']['uid'] = 13231362;
        $uid = $_SESSION['user']['uid'];
        $this->_islogin();
        $tData = UserModel::getInstance()->fRow($uid);
        if (!$tData['google_key']) {
            $this->ajax($GLOBALS['MSG']['DELETED_GOOGLE'], '0');//已解绑谷歌
        }
        $pwd = trim($_POST['pwd']);
        $google = trim($_POST['google']);
        $newpwd = Tool_Md5::encodePwd($pwd, $tData['prand']);
        if ($newpwd != $tData['pwd']) {//判断密码
            $this->ajax($GLOBALS['MSG']['TEL_CCW'], 0);//密码错误
        }
        if (!Api_Google_Authenticator::verify_key($tData['google_key'], $google)) {
            $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
        }
        $data = array(
            'uid' => $uid,
            'google_key' => ''
        );
        $usermo = new UserModel();
        if (!$usermo->update($data))   //保存数据
        {
            $this->ajax($GLOBALS['MSG']['SMS_SHIBAN'], 0);
        } else {
            $this->ajax($GLOBALS['MSG']['DEL_GOOGLE_SUSESS'], 1);//解绑成功
        }
    }

    //app 重置登录密码
    public function resetpwdoneAction()
    {
        $this->_islogin();
        $_POST = $this->rsaDecode() ?: $_POST;
        $_POST['regtype'] = trim($_POST['regtype']);
        $key = 'resetPwdsError' .$_SESSION['user']['uid'];

        if ($_POST['regtype'] == 'email') //邮箱
        {
            $email = addslashes(trim($_SESSION['user']['email']));
            $code = addslashes(trim($_POST['code']));
            $redis = Cache_Redis::instance();
            $count = $redis->get($key);
            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }

            $validata = new ValidatelogicModel();
            $postdata = [
                'email_app_find' => $email,
                'emailcode_app_find' => $code,
            ];
            $result = $validata->scene('app_findemail_pwd')->check($postdata);

            if (!$result)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='emailcode_app_find')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }

            $google = trim($_POST['google']);
            $userMo = new UserModel();
            $googledata = $userMo->field("google_key")->where("email='$email'")->fList();
            if (!empty($googledata[0]['google_key'])) {
                if (empty($google)) {
                    $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
                }
                if (!Api_Google_Authenticator::verify_key($googledata[0]['google_key'], $google)) {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
                }
            }

            $tData = array(
                'regtype' => 'email',
                'email' => $email,
            );
            $_SESSION['resetpwd'] = $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        } elseif ($_POST['regtype'] == 'phone') //手机注册
        {
            $phone = addslashes(trim($_SESSION['user']['mo']));
            $code = addslashes(trim($_POST['code']));
            $area = $_SESSION['user']['area'];//手机区号

            $redis = Cache_Redis::instance();
            $count = $redis->get($key);
            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }

            $validata = new ValidatelogicModel();
            $postdata = [
                'phone' => $phone,
                'code' => $code,
                'area' => $area
            ];

            $result = $validata->scene('app_findphone_pwd')->check($postdata);

            if (!$result)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='code')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $google = trim($_POST['google']);
            $userMo = new UserModel();
            $googledata = $userMo->field("google_key")->where("mo=$phone")->fList();
            if (!empty($googledata[0]['google_key'])) {
                if (empty($google)) {
                    $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
                }
                if (!Api_Google_Authenticator::verify_key($googledata[0]['google_key'], $google)) {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
                }
            }
            $tData = array(
                'regtype' => 'phone',
                'mo' => $phone,
                'area' => $area,//手机区号
            );
            $_SESSION['resetpwd'] = $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        }

    }

    /**
     *   app重置登录密码 重设登录密码
     */
    public function resetpwdtwoAction()
    {
        //接收数据
        $this->_islogin();
        $postdata = Tool_Request::post();
        $postdata = $this->rsaDecode() ?: $postdata;
        $sess = $_SESSION['resetpwd'];
        if (!$sess) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }
        $data = [
            'password' => $postdata['password'],
            'repassword' => $postdata['repassword'],
        ];

        // 实例化
        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('resetPassword')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result) {
            foreach ($validatelogicModel->getError() as $k => $v) {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            $this->ajax($errorData, '0', $errorM);
        }
        $tMO = new UserModel;
        if ($sess['regtype'] == 'email') {
            $res = $tMO->field('email,pwd,pwdtrade,area,uid,mo,role,prand')->where(array('email' => $sess['email']))->fRow();
        } elseif ($sess['regtype'] == 'phone') {
            $res = $tMO->field('email,pwd,pwdtrade,area,uid,mo,role,prand')->where(array('mo' => $sess['mo'], 'area' => "$sess[area]"))->fRow();
        }
        if (!$res) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }
        $login = Tool_Md5::encodePwdTrade($data['repassword'], $res['prand']);
        if ($res['pwdtrade'] == $login) {//重置登入不能密码不能跟交易密码一致
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAMEY'], 0, 'pw1');
        }
        $prand = $res['prand'];
        $pwd = Tool_Md5::encodePwd($postdata['password'], $prand);
        $tData = array(
            'uid' => $res['uid'],
            'pwd' => $pwd,
            'updated' => $_SERVER['REQUEST_TIME']
        );
        if ($tMO->update($tData)) {
            $value = $pwd . ',' . $res['uid'] . ',' . $res['role'] . ',' . $prand;
            $db2 = Yaf_Registry::get("config")->redis->default->db;
            $db3 = Yaf_Registry::get("config")->redis->user->db;
            if ($sess['regtype'] == 'email') {
                UserModel::addRedis($db2, 'useremail', $res['email'], $res['uid']); //库2
            } elseif ($sess['regtype'] == 'phone') {
                if ($res['area'] != '+86') {
                    UserModel::addRedis($db2, 'userphone', $res['area'] . $res['mo'], $res['uid']); //库2
                } else {
                    UserModel::addRedis($db2, 'userphone', $res['mo'], $res['uid']); //库2
                }
            }
            UserModel::addRedis($db3, 'uid', $res['uid'], $value); ////库3
            Cache_Redis::instance()->hSet('usersession', $res['uid'], 1);
            $_SESSION['resetpwd']=null;
            session_destroy();
            $this->ajax($GLOBALS['MSG']['RETRIEVE_PASSWORD_SUCCESS'], 1, $GLOBALS['MSG']['MODIFY_LOGIN_PASSWORD']);
        } else {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }

    }

    //app 设置交易密码 第一步
    public function settradepwdoneAction()
    {
        $this->_islogin();
        $_POST = $this->rsaDecode() ?: $_POST;
        $_POST['regtype'] = trim($_POST['regtype']);

        $key = 'resetPwdsError' .$_SESSION['user']['uid'];
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if ($_POST['regtype'] == 'email') //邮箱
        {

            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }
            $email = addslashes(trim($_SESSION['user']['email']));
            $code = addslashes(trim($_POST['code']));

            $validata = new ValidatelogicModel();
            $postdata = [
                'email_app_find' => $email,
                'emailcode_app_find' => $code,
            ];
            $result = $validata->scene('app_findemail_pwd')->check($postdata);

            if (!$result)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='emailcode_app_find')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $google = trim($_POST['google']);
            $userMo = new UserModel();
            $googledata = $userMo->field("google_key")->where("email='$email'")->fList();
            if (!empty($googledata[0]['google_key'])) {
                if (empty($google)) {
                    $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
                }
                if (!Api_Google_Authenticator::verify_key($googledata[0]['google_key'], $google)) {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
                }
            }

            $tData = array(
                'regtype' => 'email',
                'email' => $email,
            );
            $_SESSION['settradepwd'] = $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        } elseif ($_POST['regtype'] == 'phone') //手机注册
        {
            $phone = addslashes(trim($_SESSION['user']['mo']));
            $code = addslashes(trim($_POST['code']));
            $area = $_SESSION['user']['area'];//手机区号

            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
            }

            $validata = new ValidatelogicModel();
            $postdata = [
                'phone' => $phone,
                'code' => $code,
                'area' => $area
            ];

            $result = $validata->scene('app_findphone_pwd')->check($postdata);

            if (!$result)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                if($k=='code')
                {
                    UserModel::getInstance()->finderror($key);
                }
                $this->ajax($errordata, 0, $errorM);
            }
            $google = trim($_POST['google']);
            $userMo = new UserModel();
            $googledata = $userMo->field("google_key")->where("mo=$phone")->fList();
            if (!empty($googledata[0]['google_key'])) {
                if (empty($google)) {
                    $this->ajax($GLOBALS['MSG']['INSERT_GOOGLE'], 0);//请输入谷歌验证码
                }
                if (!Api_Google_Authenticator::verify_key($googledata[0]['google_key'], $google)) {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR'], 0);//谷歌验证码错误
                }
            }
            $tData = array(
                'regtype' => 'phone',
                'mo' => $phone,
                'area' => $area,//手机区号
            );
            $_SESSION['settradepwd'] = $tData;
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, '');//成功

        }

    }

    /**
     *  app设置交易密码 第二步
     */
    public function settradepwdtwoAction()
    {
        //接收数据
        $this->_islogin();
        $postdata = Tool_Request::post();
        $postdata = $this->rsaDecode() ?: $postdata;
        $sess = $_SESSION['settradepwd'];
        if (!$sess) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }
        $data = [
            'password' => addslashes(trim($postdata['password'])),
            'repassword' => addslashes(trim($postdata['repassword'])),
        ];

        // 实例化
        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('resetPassword')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result) {
            foreach ($validatelogicModel->getError() as $k => $v) {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            $this->ajax($errorData, '0', $errorM);
        }
        $userMo = UserModel::getInstance();
        $userInfo = $userMo->field('area,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);
        //效验设置交易是否跟登入一样
        $login = Tool_Md5::encodePwd($data['password'], $userInfo['prand']);
        //重置交易密码加密
        $pwdtrade = Tool_Md5::encodePwdTrade($data['password'], $userInfo['prand']);
        if ($userInfo['pwd'] == $login) {
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAME'], 0, 'pw1');
        }

        $update=$userMo->update(array('uid' => $this->mCurUser['uid'], 'pwdtrade' => $pwdtrade));
        if($update){
            unset($_SESSION['settradepwd']);
            Tool_Session::mark($this->mCurUser['uid']);
            $this->ajax($GLOBALS['MSG']['SET_SUCCESS'], 1);
        }else{
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }


    }

    /**
     *  app 是否设置交易密码
     */
    public function issettradepwdAction()
    {
        //接收数据
        $this->_islogin();
        $usermo=new UserModel();
        $data=$usermo->field("pwdtrade")->fRow($this->mCurUser['uid']);
        if($data['pwdtrade']){
            $this->ajax($GLOBALS['MSG']['ISSET_TRADEPWD'], 1);
        }else{
            $this->ajax($GLOBALS['MSG']['NO_TRADEPWD'], 0);
        }


    }

    /**
     *  app 查看实名状态 审核中 审核通过 审核失败
     */
    public function getautonymAction()
    {
        $this->_islogin();

        $AutonymMO = new AutonymModel;
        $user = $AutonymMO->where("uid = '{$this->mCurUser['uid']}'")->fRow();
        if(empty($user)){//没有
            $data = array(
                'status' => 4,
                'msg'=> $GLOBALS['MSG']['NO_REALNAME']);
            $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $data);//未實名
        }
        if($user['status']==2){//成功
            $data=array(
                'status'=>2,
                'name'=> $user['name'],
                'cardtype'=> $user['cardtype'],
                'idcard'=> substr($user['idcard'],0,2).'************'.substr($user['idcard'], -2),
                'time'=>date('Y-m-d H:i:s', $user['updated'])
            );
        }elseif($user['status'] == 1){//审核中
            $domain = Yaf_Registry::get("config")->domain;
            $data = array(
                'status' => 1,
                'msg'=> $GLOBALS['MSG']['SMS_SHENHE'],
                'name'=> $user['name'],
                'cardtype' => $user['cardtype'],
                'idcard' => $user['idcard'],
                'frontFace'=> $domain . substr($user['frontFace'], 2),
                'backFace' => $domain . substr($user['backFace'], 2),
                'handkeep' => $domain . substr($user['handkeep'], 2),
            );
        } elseif ($user['status'] == 3) {//拒绝
            $domain = Yaf_Registry::get("config")->domain;
            $data = array(
                'status' => 3,
                'msg' => $user['content'],
                'name' => $user['name'],
                'cardtype' => $user['cardtype'],
                'idcard' => $user['idcard'],
                'frontFace' => $domain . substr($user['frontFace'], 2),
                'backFace' => $domain . substr($user['backFace'], 2),
                'handkeep' => $domain . substr($user['handkeep'], 2),
            );
        }
        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $data);//成功

    }
    /**
     *  app 提交实名姓名和idcard
     */
    public function sendautonymdataAction()
    {
        $this->_islogin();

        $postData = Tool_Request::post();
        $postData = $this->rsaDecode() ?: $postData;
        $resend= $postData['resend']?addslashes(trim($postData['resend'])):0;//resend=1 重新提交 0 第一次提交
        $data=array(
            'name'=> addslashes(trim($postData['name'])),
            'idcard'=> addslashes(trim($postData['idcard'])),
            'cardtype'=> (int)addslashes(trim($postData['cardtype'])),
            'resend'=> $resend
        );
        if (!in_array($resend,array(1,0))) {
            $this->ajax('resend 错误', 0);//resend 错误
        }
            $AutonymMO = new AutonymModel;
            $user = $AutonymMO->where("uid = '{$this->mCurUser['uid']}'")->fRow();
            if($resend == 1&&empty($user)){//重新实名判断
                $this->ajax('你还未提交过实名', 0);//你还未提交过实名
            }
            if ($user && $resend == 0 && ($user['status'] == 1 || $user['status'] == 2 || $user['status'] == 3)) {
                $this->ajax($GLOBALS['MSG']['ALREADY_FINISH_MESSAGE'], 0);//你已完善过资料
            }

            $validateMo = new ValidatelogicModel();
            $result = $validateMo->scene('app_autonym_one')->check($data);
            if (!$result || $validateMo->getError()) {
                foreach ($validateMo->getError() as $v) {
                    $this->ajax($v, 0);
                }
            }
            $result1 = $validateMo->checkCard($postData['idcard'], $this->mCurUser['uid']);
            if (!$result1) {
                $this->ajax($GLOBALS['MSG']['CARD_ALREADY_IS_USED'], 0);//證件號已被使用
            }
            //验证身份证
            if (empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard']))) {
                $this->ajax($GLOBALS['MSG']['CARD_TYPE_ERROR'], 0);//證件格式不正確
            }

            $_SESSION['autonym_data']= $data;
            $this->ajax($GLOBALS['MSG']['SET_SUCCESS'], 1);

    }

    /**
     *  app 提交实名图片
     */
    public function sendautonymimgAction()
    {
        $this->_islogin();
        if (!$_SESSION['autonym_data']) {//名字信息
            $this->ajax('session 不存在，请返回上一步', 0);
        }
        if ($_SESSION['autonym_data']['resend'] != 0) {//名字信息
            $this->ajax($GLOBALS['MSG']['CONTROLLER_ERROR'], 0);
        }
        $postData = Tool_Request::post();
        $postData = $this->rsaDecode() ?: $postData;
        $data = array(
            'baseyi' => addslashes(trim($postData['baseyi'])),
            'baseer' => addslashes(trim($postData['baseer'])),
            'basesan' => addslashes(trim($postData['basesan'])),
        );

            $validateMo = new ValidatelogicModel();
            $result = $validateMo->scene('app_autonym_two')->check($data);
            if (!$result || $validateMo->getError()) {
                foreach ($validateMo->getError() as $v) {
                    $this->ajax($v, 0);
                }
            }

        if (!empty($postData['baseyi']) && !empty($postData['baseer']) && !empty($postData['basesan'])) {
            $tMO = new Tool_UploadOne();
            $url = '';
            $frontFace = $tMO->uploadOne($postData['baseyi'], $url);
            $backFace = $tMO->uploadOne($postData['baseer'], $url);
            $handkeep = $tMO->uploadOne($postData['basesan'], $url);
            if (!$frontFace || !$backFace || !$handkeep) {
                $this->ajax($tMO->getError(), 0);
            }
            $AutonymMO = new AutonymModel;
            $time = time();
            $tData = array(
                'uid' => $this->mCurUser['uid'],
                'name' => $_SESSION['autonym_data']['name'],
                'cardtype' => $_SESSION['autonym_data']['cardtype'],
                'idcard' => $_SESSION['autonym_data']['idcard'],
                'frontFace' => $frontFace,
                'backFace' => $backFace,
                'handkeep' => $handkeep,
                'created' => $time,
                'status' => 1
            );
            if (!$AutonymMO->insert($tData)) {
                //show($AutonymMO->getLastSql());
                $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'], 0);//资料提交失败,请重試
            } else {

                $this->ajax($GLOBALS['MSG']['SMS_SHENHE'], 1);
            }
        }

    }

    /**
     *   重新提交实名图片
     */
    public function sendautonymimgtwoAction()
    {
        $this->_islogin();
        $postData = Tool_Request::post();
        $postData = $this->rsaDecode() ?: $postData;
        if (!$_SESSION['autonym_data']) {//名字信息
            $this->ajax('session 不存在，请返回上一步', 0);
        }
        if ($_SESSION['autonym_data']['resend'] != 1) {//
            $this->ajax($GLOBALS['MSG']['CONTROLLER_ERROR'], 0);
        }
        //权限||上一次提交的数据
        $AutonymMO = new AutonymModel;
        $time = time();
        $user = $AutonymMO->where("uid = {$this->mCurUser['uid']}")->fRow();
        if (!$user) {//如果没有，请求接口错误
            $this->ajax($GLOBALS['MSG']['CONTROLLER_ERROR'],0);//請求接口錯誤
        }
        if ($user && ($user['status'] == 2)) {
            $this->ajax($GLOBALS['MSG']['ALREADY_CERTIFIED_SUCCESSFUL'], 1);//你已认证成功
        } else {
            //审核中和认证失败进来

            $tMO = new Tool_UploadOne();
            $frontFace = $tMO->uploadOne($postData['baseyi'], $url = '');
            $backFace = $tMO->uploadOne($postData['baseer'], $url = '');
            $handkeep = $tMO->uploadOne($postData['basesan'], $url = '');

            //重新认证没有选择图片进来
            if ($postData['baseyi'] == '' && $postData['baseer'] == '' && $postData['basesan'] == '') {
                $tData = array(
                    'uid' => $this->mCurUser['uid'],
                    'name' => $_SESSION['autonym_data']['name'],
                    'cardtype' => $_SESSION['autonym_data']['cardtype'],
                    'idcard' => $_SESSION['autonym_data']['idcard'],
                    'created' => $time,
                    'status' => 1
                );
            } else {
                //只要上传一张就进来

                $time = time();
                if ($user && ($user['status'] == 1 || $user['status'] == 3)) {
                    //如果只更改一张或两张 没有跟改的就把路径重新update
                    //正面

                    if (empty($frontFace) && empty($postData['baseyi'])) {
                        $frontFace = $user['frontFace'];
                    }
                    //背面
                    if (empty($backFace) && empty($postData['baseer'])) {
                        $backFace = $user['backFace'];

                    }
                    //手持
                    if (empty($handkeep) && empty($postData['basesan'])) {
                        $handkeep = $user['handkeep'];
                    }
                    //组装数据
                    $tData = array(
                        'uid' => $this->mCurUser['uid'],
                        'name' => $_SESSION['autonym_data']['name'],
                        'cardtype' => $_SESSION['autonym_data']['cardtype'],
                        'idcard' => $_SESSION['autonym_data']['idcard'],
                        'frontFace' => $frontFace,
                        'backFace' => $backFace,
                        'handkeep' => $handkeep,
                        'created' => $time,
                        'status' => 1
                    );

                }

            }
            if (!$AutonymMO->where("id={$user['id']}")->update($tData)) {
                $this->ajax($GLOBALS['MSG']['CARD_PHOTO_NEED_ALL'], 0);//證件照片需上传完整
            }
            $_SESSION['autonym_data']=null;
            $this->ajax($GLOBALS['MSG']['SMS_SHENHE'], 1);
        }

    }
    /**
     *  app 重新认证时，获取实名名字信息
     */
    public function getautonymdataAction()
    {
        //接收数据
        $this->_islogin();
        $automo=new AutonymModel();
        $uid=$_SESSION['user']['uid'];
       // $uid= 13231356;
        $data=$automo->field("name,cardtype,idcard,frontFace,backFace,handkeep")->where("uid=$uid")->fList();
        $domain = Yaf_Registry::get("config")->domain;
        foreach($data as &$v){
            $v['frontFace']= $domain. $v['frontFace'];
            $v['backFace'] = $domain . $v['backFace'];
            $v['handkeep'] = $domain . $v['handkeep'];
        }
        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $data[0]);
    }

    //用户反馈
    public function feedbackAction()
    {
        $this->_islogin();

        if (Tool_Request::method() == "POST") {
            $new = new UserCommentModel();
            $postData = Tool_Request::post();
            $data = [
                'themessage' => $postData['content'],
            ];
            // 实例化
            $validatelogicModel = new ValidatelogicModel();

            // 调用login场景，check方法验证
            $result = $validatelogicModel->scene('feedback')->check($data);

            //如果为空 ，则报错，并输出错误信息
            if (!$result) {
                foreach ($validatelogicModel->getError() as $k => $v) {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                $this->ajax($errorData, '0', $errorM);
            }

            $tData = array(
                'uid' => $this->mCurUser['uid'],
                'content' => $postData['content'],
                'created' => time()
            );
            if (!$new->insert($tData)) {
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0);
            } else {
                //show($webMo->getLastSql());
                $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);
            }

        }
    }

    //总资产
    public function getusermoneytotalAction()
    {
       /* $this->mCurUser=array(
            'uid'=> 13231448,
            'area'=>'+86',
            'mo'=>'15113816345',
            'email' =>''
        );*/
        $this->_islogin();
        $userInfo = UserModel::getInstance()->fRow($this->mCurUser['uid']);

        $yibidata = $this->getyibiuser($this->mCurUser['area'], $this->mCurUser['mo'], $this->mCurUser['email'], '');//获取易币账户余额
        if(empty($yibidata)|| $yibidata['status']==0){
            $this->ajax('otc busy', 0);
        }
        if($yibidata['status']==1){
            $yibiuserInfo= $yibidata['data'];
        }

        $newPrice = Coin_PairModel::getInstance()->getCoinPrice();
        $tradearea = Coin_PairModel::getInstance()->field('DISTINCT coin_to')->fList();
        foreach ($tradearea as &$v1) {
            $v1['coin_to'] = '_' . $v1['coin_to'];
        }

        //全部币都换算成btc
        foreach ($newPrice as $coin => $area) {
            if ($coin == 'btc') {
                foreach ($area as $k => $v) {
                    $coinPrice[str_replace(array_column($tradearea, 'coin_to'), '', $k)] = array(
                        preg_replace('/.+?_/', '', $k), Tool_Math::format($v['price']),
                    );
                }
            } else {
                $transPirce = $newPrice['btc'][$coin . '_btc']['price'];
                foreach ($area as $k => $v) {
                    $cc = str_replace(array_column($tradearea, 'coin_to'), '', $k);
                    $btcarr = array_keys($coinPrice);
                    if (in_array($cc, $btcarr)) {//如果已经有跳过
                        continue;
                    }
                    $coinPrice[$cc] = array(
                        'btc', Tool_Math::mul($v['price'], $transPirce),
                    );
                }
            }
        }
        $eRedis = Cache_Redis::instance('default');
        $btcprice = $eRedis->get('btc_rmb_price');
        $coinPrice['dob'] = array(
            'btc', Tool_Math::div(1, $btcprice),
        );
        //折算总资产
        $allToBtc = Tool_Math::add($userInfo['btc_over'], $userInfo['btc_lock']);//全部折算btc 多比
        if ($yibidata['status'] == 1) {//有数据
            $yibiallToBtc = Tool_Math::add($yibiuserInfo['btc_over'], $yibiuserInfo['btc_lock']);//全部折算btc 易币
            foreach ($coinPrice as $k => $v) {
                $over_add_lock = Tool_Math::add($userInfo[$k . '_over'], $userInfo[$k . '_lock']);
                $coin_to_btc = Tool_Math::mul($over_add_lock, $v[1]);
                $allToBtc = Tool_Math::add($allToBtc, $coin_to_btc);
                if (isset($yibiuserInfo[$k . '_over'])) {
                    $yibi_over_add_lock = Tool_Math::add($yibiuserInfo[$k . '_over'], $yibiuserInfo[$k . '_lock']);
                    $yibi_coin_to_btc = Tool_Math::mul($yibi_over_add_lock, $v[1]);
                    $yibiallToBtc = Tool_Math::add($yibiallToBtc, $yibi_coin_to_btc);
                }
            }
        }
        $data=array(
            'btctotal' => Tool_Math::add($allToBtc, $yibiallToBtc,8),
            'dobibtc' => Tool_Math::add($allToBtc, 0, 8),
            'yibibtc' => Tool_Math::add($yibiallToBtc, 0, 8),
            'moneytotal'=> Tool_Math::mul(Tool_Math::add($allToBtc, $yibiallToBtc), $btcprice,2),
            'dobimoney'=> Tool_Math::mul($allToBtc, $btcprice,2),
            'yibimoney' => Tool_Math::mul($yibiallToBtc, $btcprice,2),
        );
        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $data);
    }

    //法币头像和昵称 修改
    public function otcuserdataAction()
    {
        $this->_islogin();

        $area= $this->mCurUser['area'];
        $mo = $this->mCurUser['mo'];
        $email = $this->mCurUser['email'];
        $Otcusermo = new Otcorder_UserModel();
        if($email&& $mo){
            $where= "(area='$area' and mo=$mo) or email='$email' ";
        }elseif($email){
            $where = "email='$email' ";
        } elseif ($mo) {
            $where = "area='$area' and mo=$mo";
        }
        $list= $Otcusermo->field("uid,nickname,logo")->where($where)->fList();
        $yibiurl = Yaf_Registry::get("config")->domain;
        $data=array(
            'url'=> $yibiurl. substr($list[0]['logo'], 2),
            'nickname'=> $list[0]['nickname'],
        );

        if (Tool_Request::method() == 'POST') {
            $postData = Tool_Request::post();
            $logo = trim($postData['logo']);
            $nickname = trim($postData['nickname']);
            if (!empty($logo)) {
               /* $tMO = new Tool_Upload();
                $frontFace = $tMO->baseAutony($logo);*/
                $tMO = new Tool_UploadOne();
                $frontFace = $tMO->uploadOne($logo, $url = 'upload/otclogo/');
                if (!$frontFace) {
                    $this->ajax($tMO->getError(), 0);
                }
                $time = time();
                $upData = array(
                    'uid' => $list[0]['uid'],
                    'logo' => $frontFace,
                    'updated' => $time,
                    'updateip' => Tool_Fnc::realip(),
                );

                if ($Otcusermo->update($upData)) {
                    Tool_Session::mark($list[0]['uid']);
                    $mkMo = new Market_BtcModel();
                    // 刷新该用户的信息缓存
                    $mkMo->flushUserMarket($list[0]['uid']);
                    $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);
                } else {
                    $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0);
                }

            }
            if (!empty($nickname)) {
                $checkdata['nickname']= $nickname;
                $validateMo = new ValidatelogicModel();
                $result = $validateMo->scene('nickname')->check($checkdata);
                if (!$result || $validateMo->getError()) {
                    foreach ($validateMo->getError() as $v) {
                        $this->ajax($v, 0);
                    }
                }
                //$list = SensitiveModel::getInstance()->field("content")->fList();
                $dd = $Otcusermo->query("select content from `sensitive`");
                foreach ($dd as $k => $v) {
                    if (strstr($nickname, $v['content'])) {
                        $nickname = str_replace($v['content'], "**", $nickname);
                    }
                }

                $time = time();
                $upData = array(
                    'uid' => $list[0]['uid'],
                    'nickname' => $nickname,
                    'updated' => $time,
                    'updateip' => Tool_Fnc::realip(),
                );

                // 更新数据
                if ($Otcusermo->update($upData)) {
                    //成功，刷新user数据
                    Tool_Session::mark($list[0]['uid']);
                    $mkMo = new Market_BtcModel();
                    // 刷新该用户的信息缓存
                    $mkMo->flushUserMarket($list[0]['uid']);
                    $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);
                } else {
                    $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0);
                }
            }
        }

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $data);

    }

    public function logoutAction()
    {
        if (isset($this->mCurUser['uid'])) {
            Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'del');
            $redis = Cache_Redis::instance();
            setcookie('reurl', 'del', 1);
            setcookie('WSTK', 'del', 1);
            $redis->del('admin_google_auth_' . $this->mCurUser['uid']);
            session_destroy();
        }
        $this->ajax('', 1);
    }
    //获取易币用户余额
    public function getyibiuser($area, $mo, $email, $coin)
    {
        $data = array(
            'area' => $area,
            'mo' => $mo,
            'email' => $email,
            'coin' => $coin,
            'timestamp' => time()
        );
        ksort($data);
        $str = '';
        foreach ($data as $key => $v) {
            $str .= $key . $v;
        }
        //$token = md5($str . 'asdcsd');
        $dobi_used_key = "EIML%CcrtqVwXzrT4s8%F5YaDdZ1F^6A";//多比传给法币
        $token = md5($str . $dobi_used_key);
        $data['token'] = $token;
        $headers = array('Content-Type:application/x-www-form-urlencoded', 'charset=utf-8');
        $yibiurl = Yaf_Registry::get("config")->yibi->ip;
        $url = $yibiurl . 'api_user/userover';
        $json = http_build_query($data);
        //show($json);
        //$strResult = $this->callInterfaceCommon($url, "POST", $json, $headers);
        $strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);

        $ddd = substr($strResult, strpos($strResult, "{"));//去掉json前面的东东
        $ars = json_decode($ddd, true);
        return $ars;
    }

    /**
     * 所有币的最新行情排序
     */
    public function getAllQuoteV2Action($name = "mcc_dob")
    {
        $data = Coin_PairModel::getInstance()->getCoinPrice();
        $coinmo = new CoinModel();
        $coinurllist = $coinmo->field('name,logo,type')->where('status=0')->fList();
        $coinurlarr = array_column($coinurllist, 'logo', 'name');
        $return = array();
        $host = Yaf_Registry::get("config")->domain;
        $host=substr($host,0,-1);
        foreach ($data as $key => &$v) {
            $currency = Cache_Redis::instance()->get(strtolower($key) . '_rmb_price');
            foreach ($v as $kk => &$vv) {
                $vv['ratio'] = sprintf('%.2f', $vv['ratio']);
                $vv['amount'] = sprintf('%.2f', $vv['amount']);
                $vv['price'] = sprintf('%.8f', $vv['price']);
                $vv['money'] = Tool_Math::format($vv['money']);
                $vv['currency'] = $currency ? sprintf('%.2f', $currency * $vv['price'], 2) : '';
                $vv['coin'] = $kk;
                $vv['max'] = strval($vv['max']);
                $vv['min'] = strval($vv['min']);
                $as = explode('_', $kk);
                $vv['coinurl'] = $host.$coinurlarr[$as[0]];
                if ($vv['type'] == 1) {
                    $return[$key][] = $vv;
                } elseif ($vv['type'] == 2) {
                    $return['New'][] = $vv;
                }

            }
        }

        //交易区排序
        $areaSort = array('dob', 'btc', 'eth', 'New');
        $temp = array();
        foreach ($areaSort as $v) {
            if (!$return[$v]) {
                continue;
            }
            $temp[$v] = $return[$v];
        }
        $return = $temp;
        $return['default_trade']= $name;
        $this->ajax('', 1, $return);
    }
    //交易对
    public function getTradePairAction($name = "mcc_dob")
    {
        $data = Coin_PairModel::getInstance()->getCoinPrice();
        $coinmo = new CoinModel();
        $coinurllist = $coinmo->field('name,logo')->where('status=0')->fList();
        $coinurlarr = array_column($coinurllist, 'logo', 'name');
        $return = array();
        $host = Yaf_Registry::get("config")->domain;
        $host = substr($host, 0, -1);
        foreach ($data as $key => &$v) {

            foreach ($v as $kk => &$vv) {
                $vv['coin'] = $kk;
                $as = explode('_', $kk);
                $vv['coinurl'] = $host . $coinurlarr[$as[0]];
                if ($vv['type'] == 1) {
                    $return[$key][] = array('coin'=> $vv['coin'],'coinurl'=> $vv['coinurl']);
                } elseif ($vv['type'] == 2) {
                    $return['news'][] = array('coin' => $vv['coin'], 'coinurl' => $vv['coinurl']);
                }

            }
        }

        //交易区排序
        $areaSort = array('dob', 'btc', 'eth', 'new');
        $temp = array();
        foreach ($areaSort as $v) {
            if (!$return[$v]) {
                continue;
            }
            $temp[$v] = $return[$v];
        }
        $return = $temp;
        $return['default_trade'] = $name;
        $this->ajax('', 1, $return);
    }

    /*
       * 添加自选
       */
    public function setSelectedAction()
    {
        //$this->mCurUser['uid']= 13231356;
        $this->_islogin();
        $_POST = $this->rsaDecode() ?: $_POST;
        $selected = $_POST['coins'];//mcc_dob
        $type = $_POST['type'];// add  del

        if (!$type || !in_array($type, ['add', 'del']) || !preg_match('/^[a-z_]+$/', $selected)) {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $userConfigMo = UserConfigModel::getInstance();
        $oldData = $userConfigMo->where(['uid' => $this->mCurUser['uid']])->fRow();
        if ($oldData) {
            $oldConf = json_decode($oldData['config'], true);
            $oldSelected = $oldConf['selected'];
            if(in_array($selected, $oldSelected)&& $type == 'add'){
                $this->ajax($GLOBALS['MSG']['ALREADY_SELECTED']);//已添加过自选
            }
            if ($type == 'add') {
                $oldSelected[] = $selected;
            } elseif ($type == 'del') {
                if (($idx = array_search($selected, $oldSelected)) !== false) {
                    array_splice($oldSelected, $idx, 1);
                }
            }

            $oldConf['selected'] = $oldSelected ? array_unique($oldSelected) : [];

            if ($oldConf) {
                $oldData['config'] = json_encode($oldConf);
            }

            $saveData = $oldData;
        } elseif ($type == 'add') {
            $saveData = array('uid' => $this->mCurUser['uid'], 'config' => json_encode(array('selected' => [$selected])));
        }

        if ($saveData) {
            $r = $userConfigMo->save($saveData);
        }

        if (!$r) {
            $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);
        }

        $this->ajax('', 1);

    }


    /*
    * 我的自选
    */
    public function selectedAction()
    {
        //$this->mCurUser['uid'] = 13231356;
        $this->_islogin();

        $userConfigMo = UserConfigModel::getInstance();
        $data = $userConfigMo->where(['uid' => $this->mCurUser['uid']])->fRow();
        $data and $data = json_decode($data['config'], true);
        $this->ajax('', 1, $data ? implode(',', $data['selected']) : '');
    }

    /*
       * 获取可用余额
       */
    public function getUserBalanceAction()
    {
        //$this->mCurUser['uid']= 13231356;
        $this->_islogin();
        $_POST = $this->rsaDecode() ?: $_POST;
        $coins = $_POST['coins'];//mcc_dob
        $arr=explode('_', $coins);
        $str='';
        foreach($arr as $v){
        $str.=$v.'_over,'. $v . '_lock,';
        }
        $str =substr($str,0,-1);
        $usermo=new UserModel();
        $list=$usermo->field($str)->where(array('uid'=> $this->mCurUser['uid']))->fList();
        foreach($list[0] as &$v){
            $v=trim(preg_replace('/(\.\d*?)0+$/', '$1', $v), '.');
        }

        $this->ajax('', 1, $list[0]);
    }

}