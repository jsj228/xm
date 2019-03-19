<?php


class Ajax_UserController extends Ajax_BaseController
{
    //111
    # 启用 SESSION
    protected $_auth = 1;
    # 注册发送短信接口key
    protected $_sendMsgKey = 'regsendcode';

    /**
     * 注册
     */
    public function registerAction()
    {
        //邀请人
        $fromUid = isset($_COOKIE['regfrom'])?Tool_Code::idDecode($_COOKIE['regfrom']):0;
        is_numeric($fromUid) or $fromUid = 0;

        if ($_POST['regtype'] == 'email') //邮箱sendregmsg
        {
            $password1 = $_POST['pwd'];
            $password2 = $_POST['repwd'];
            $email     = trim($_POST['account']);

            //检验邮箱是否在激活时间内
            $tMO = new EmailactivateModel;

            $tData =$tMO ->where(array('email'=>$email))->fList();
           // $tData = $tMO->fRow('SELECT * FROM email_activate WHERE email = \'' . $email . '\' limit 1');
            $time = time();
            if (($time - $tData['senttime']) < 1800)
            {
                $this->ajax($GLOBALS['MSG']['EMAIL_YZC'],0, 'email');//发送邮箱时间没有超过30分钟
            }
            $validata = new ValidatelogicModel();
            $postdata = [
                'email' => $email,
                'password' => $password1,
                'repassword' => $password2,
            ];

            $result = $validata->scene('email_register')->check($postdata);
            if (!$result)
            {//验证
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM = $k;
                }
                $this->ajax($errordata, 0, $errorM);
            }
            # 保存到 MYSQL
            $prand = Tool_Md5::getUserRand();

            $pwd   = Tool_Md5::encodePwd($password1, $prand);
            $pTime = $_SERVER['REQUEST_TIME'];
            $tData = array(
                'email'        => $email,
                'pwd'          => $pwd,
                'created'      => $pTime,
                'createip'     => Tool_Fnc::realip(),
                'role'         => 'user',
                'registertype' => '1',
                'from_uid'     => $fromUid,  //是否被邀请
                'updated'      => $pTime,
            );

            #激活郵件
            $eMO = new EmailactivateModel;

            $pKey = Tool_Md5::emailActivateKey($email, $pTime);//key
            $eData = array(
                'email' => $email,
                'reg_time' => $pTime,
                'senttime' => $pTime,
                'activate_time' =>0,
                'key' => $pKey,
            );
            $oldactive = $eMO->where(array('email' => $email))->fList();
                  //lallal
            if (!empty($oldactive)) {//更新1
                if ($eMO->where(array('email' => $email))->update($eData)) {
                    $value = $tData['pwd'] . ',' . $tData['email'] . ',' . $tData['role'] . ','. $prand.','. $tData['created'] . ',' . $tData['registertype'].','. $fromUid;

                    $db2 = Yaf_Registry::get("config")->redis->default->db;
                    UserModel::addRedis($db2, 'emailactive', $email, $value); ////邮箱激活 存用户信息
                    UserModel::saveEmailRedis(array(
                        'key' => $pKey,
                        'email' => $email,
                    ));

                    $_SESSION['emailactive'] = $tData;
                    $this->ajax($GLOBALS['MSG']['REGISTER_SUCCESS'], 1);//注册成功

                }
            } else {//插入
                if ($eMO->insert($eData)) {
                    $value = $tData['pwd'] . ',' . $tData['email'] . ',' . $tData['role'] . ','.$prand.',' . $tData['created'] . ',' . $tData['registertype'] . ',' . $fromUid;
                    $db2 = Yaf_Registry::get("config")->redis->default->db;
                    UserModel::addRedis($db2, 'emailactive', $email, $value); ////邮箱激活 存用户信息
                    UserModel::saveEmailRedis(array(
                        'key' => $pKey,
                        'email' => $email,
                    ));

                    $_SESSION['emailactive']= $tData;
                    $this->ajax($GLOBALS['MSG']['REGISTER_SUCCESS'], 1);//注册成功
                }
            }

        }
        elseif ($_POST['regtype'] == 'phone') //手机注册
        {
            $password1        = trim($_POST['pwd']);
            $password2        = trim($_POST['repwd']);
            $phone            = trim($_POST['account']);
            $phoneCode        = trim($_POST['code']);
            $action           = trim($_POST['action']);
            $_POST['captcha'] = trim($_POST['captcha']);
            $area = $_POST['area']?trim($_POST['area']):'+86';//手机区号
            $validata         = new ValidatelogicModel();
            $postdata         = [
                'mo'         => $phone,
                'password'   => $password1,
                'repassword' => $password2,
                'smsCaptch'  => $phoneCode,
                'captcha'    => $_POST['captcha'],
                'area'       => $area
            ];

            $result = $validata->scene('register')->check($postdata);
            if (!$result && MOBILE_CODE)
            {
                foreach ($validata->getError() as $k => $v)
                {
                    $errordata = $v;
                    $errorM    = $k;
                }
                $this->ajax($errordata, 0, $errorM);
            }

            $phone = trim($phone);
            $tMO   = new UserModel;

            # 保存到 MYSQL
            $prand = Tool_Md5::getUserRand();
            $pwd   = Tool_Md5::encodePwd($password1, $prand);
            //注册模式
            $activity = new ActivityModel();
            $activeId = $activity->where(array('name'=>'注册有礼'))->fList();
            //$activeId = $tMO->query("select * from activity where name='注册有礼'");
            if($activeId[0]['status']==1&& $_SERVER['REQUEST_TIME']> $activeId[0]['start_time'] && $_SERVER['REQUEST_TIME']< $activeId[0]['end_time']){
                //注册有礼
                $tData = array(
                    'mcc_over'     => 5,
                    'mo'           => $phone,
                    'prand'        => $prand,
                    'pwd'          => $pwd,
                    'created'      => $_SERVER['REQUEST_TIME'],
                    'createip'     => Tool_Fnc::realip(),
                    'registertype' => '2',
                    'role'         => 'user',
                    'updated'      => $_SERVER['REQUEST_TIME'],
                    'from_uid'     => $fromUid,
                    'area' => $area,//手机区号
                );
                $actmo = new UserRewardModel;
                $tMO->begin();
                if (!$tData['uid'] = $tMO->insert($tData)) {
                    $tMO->back();
                    $this->ajax($GLOBALS['MSG']['REGISTER_FAIL'], 0, 'fail');//注册失败
                }

                $forwarddata = array(
                    'uid'        => $tData['uid'],
                    'number_reg' => 5,
                    'aid'        => $activeId[0]['id'],
                    'coin'       => 'mcc',
                    'type'       => 1,
                    'created'    => $_SERVER['REQUEST_TIME'],
                    'updated'    => $_SERVER['REQUEST_TIME']
                );
                if (!$actmo->insert($forwarddata))
                {
                    $tMO->back();
                    $this->ajax($GLOBALS['MSG']['REGISTER_FAIL'], 0, 'fail');//注册失败
                }
                $tMO->commit();
                $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
                $db2   = Yaf_Registry::get("config")->redis->default->db;
                $db3   = Yaf_Registry::get("config")->redis->user->db;
                if($area=='+86'){
                    $phone= $tData['mo'];
                }else{
                    $phone = $area.$tData['mo'];
                }
                UserModel::addRedis($db2, 'userphone', $phone, $tData['uid']); //库2
                UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
                Tool_Session::mark($tData['uid']);
                setcookie('regfrom', 'del', 1, '/');
                $this->ajax($GLOBALS['MSG']['REGISTER_SUCCESS'], 1, '');//注册成功
            }
            else{
                //普通注册
                $tData = array(
                    'mo'           => $phone,
                    'prand'        => $prand,
                    'pwd'          => $pwd,
                    'created'      => $_SERVER['REQUEST_TIME'],
                    'createip'     => Tool_Fnc::realip(),
                    'registertype' => '2',
                    'role'         => 'user',
                    'updated'      => $_SERVER['REQUEST_TIME'],
                    'from_uid'     => $fromUid,
                    'area' => $area,//手机区号
                );

                if ($tData['uid'] = $tMO->insert($tData))
                {
                    $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
                    $db2=Yaf_Registry::get("config")->redis->default->db;
                    $db3 = Yaf_Registry::get("config")->redis->user->db;
                    if ($area == '+86') {
                        $phone = $tData['mo'];
                    } else {
                        $phone = $area . $tData['mo'];
                    }
                    UserModel::addRedis($db2, 'userphone', $phone, $tData['uid']); //库2
                    UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
                    Tool_Session::mark($tData['uid']);
                    setcookie('regfrom', 'del', 1, '/');
                    //$this->delCaptcha();//清除验证码
                    //$_SESSION['user'] = $tData;
                    $this->ajax($GLOBALS['MSG']['REGISTER_SUCCESS'], 1, '');//注册成功
                }
                else
                {
                    $this->ajax($GLOBALS['MSG']['REGISTER_FAIL'], 0, 'fail');//注册失败
                }

            }

        }
        exit;
    }

    //注册发送短信或语音
    public function sendregmsgAction()
    {
        $captcha = $_POST['captcha'];
        $phone   = trim($_POST['phone']);
        $area = $_POST['area'] ? trim($_POST['area']) : '+86';

        $validata = new ValidatelogicModel();
        $postdata = [
            'mo'      => $phone,
            'captcha' => $captcha,
            'area'    => $area
        ];
        $result = $validata->scene('mo')->check($postdata);
        if (!$result)
        {

            foreach ($validata->getError() as $k => $v)
            {
                $errordata = $v;
                $errorM    = $k;
            }
            $this->ajax($errordata, 0, $errorM);
        }
        if ($_POST['action'] == 8 && $area != '+86') {
            $this->ajax($GLOBALS['MSG']['GUOJI_VOICE'],0,'vcode');//国际语音暂不支持
        }
        // action==11 是找回密码 action==8语音
        if ($_POST['action'] && $_POST['action'] == 11)
        {
            $type = '11';
        }
        else if ($_POST['action'] && $_POST['action'] == 8)
        {
            $type = '8'; //语音
        }
        else
        {
            $type = '1';
        }
        $num = 0; //$_POST['num'];

        if (!$type = abs($type))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 0, '');//参数错误
        }
        # 验证登录
        $time  = time();
        $start = $time - 3600;
       $count = PhoneCodeModel::getInstance()->where(array("mo"=>$phone, "area"=>$area, "ctime"=>['>=', $start], "action"=>$type))->count();

        if ($count >= 10)
        {
            //短信过于频繁，请使用语音验证码
            $this->ajax('短信過於頻繁，請稍後再試，1小時内最多發送10條短信', 0, 'vcode');//$GLOBALS['MSG']['SMS_TO_VOICE_WARN']
        }
        if (PhoneCodeModel::regverifiTime($phone, $type,$area))
        {

            if ($type == '8') {
                //语音
                $user['mo'] = $phone;
                $code       = PhoneCodeModel::sendCode($user, $type, $area, $num);
            } else {
                $sms = PhoneCodeModel::sendregCode($phone, $type, $area, $num); //短信
                $code = $sms['code'];
            }

            if ($code == '200')
            {
                if(MOBILE_CODE){
                    $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1, ''); //发送成功
                }
                $this->ajax($sms['msg'], 0, 'vcode'); //演示模式发送成功
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'vcode'); //发送失败
            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'], 0, 'vcode'); //请您过60秒再点击发送
        }
    }

    /*
     * 登入
     **/
    public function loginAction()
    {
        //接收数据
        $postdata = Tool_Request::post();

        $userMo = new UserModel();
        $email     = strtolower(trim($_POST['account']));
        if($postdata['regtype'] =='email')
        {
            $data=[
                'email_bb'     =>trim($postdata['account']),
                'password_bb'  =>$postdata['pwd'],
                'captcha'     =>$postdata['captcha']
            ];
            //检验邮箱是否在激活时间内
            $activity = new EmailactivateModel();
            $tData = $activity->where(array('email'=>$data['email_bb']))->fList();
           // $tData = $tMO->fRow('SELECT * FROM email_activate WHERE email = \'' . $email . '\' limit 1');
            $time = time();
            if (($time - $tData['senttime']) < 1800 &&  $tData['activate_time']=='')
            {
                $_SESSION['emailactive']['email']=$email;
                $this->ajax($GLOBALS['MSG']['EMAIL_WJH'],0,'emailfix');//发送邮箱时间没有超过30分钟
            }

            //实例化
            $validatelogicModel = new ValidatelogicModel();
            $result = $validatelogicModel->scene('loginemail')->check($data);
            //如果为空,则报错,并输出错误信息
            if(!$result)
            {
                foreach ($validatelogicModel->getError() as $k=>$v)
                {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                if($errorM=='password_bb')
                {
                    $errorM='Upassword';
                }
                $this->ajax($errorData, '0', $errorM);
            }
            $user = $userMo->where(['email' => $data['email_bb']])->fRow();

        }
        else   //手机
        {
            $area= $postdata['area']? trim($postdata['area']):'+86';
            $data     = [
                'phone'     => trim($postdata['account']),
                'captcha'   => $postdata['captcha'],
                //'code'      => $postdata['code'],
                'Upassword' => $postdata['pwd'],
                'area'      => $area
            ];

            $usermo=new UserModel();
            $userdd = $usermo->field('area')->where(array('mo' => $data['phone']))->fList();
            if(!empty($userdd)){
                if (!in_array($area, array_column($userdd, 'area'))) {
                    $this->ajax($GLOBALS['MSG']['YUN_SEND_FAIL'], '0', 'phone');//輸入號碼與歸屬地不匹配
                }
            }

            // 实例化
            $validatelogicModel = new ValidatelogicModel();
            // 调用login场景，check方法验证
            $result = $validatelogicModel->scene('userlogin')->check($data);


            //如果为空 ，则报错，并输出错误信息
            if (!$result)
            {
                foreach ($validatelogicModel->getError() as $k => $v)
                {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                $this->ajax($errorData, '0', $errorM);
            }


            $user = $userMo->where(['mo' => $data['phone'],'area'=>"{$area}"])->fRow();
            if(!$user)
            {
                $db2=Yaf_Registry::get("config")->redis->default->db;
                if($area=='+86'){
                    UserModel::delRedis($db2, 'userphone', $data['phone']);
                }else{
                    UserModel::delRedis($db2, 'userphone', $area.$data['phone']);
                }
                $this->ajax($GLOBALS['MSG']['USER_NOT_EXISIT'], 0, 'mo');
            }
        }


        unset($user['pwd']);
        if($user['google_key']=='')
        {
        $realInfo = AutonymModel::getInstance()->field('name,cardtype,idcard')->where(['status'=>2, 'uid'=>$user['uid']])->fRow();
        if($realInfo)
        {
            $user['realInfo'] = $realInfo;
        }

        // 登录之后存储登录信息
        $_SESSION['user'] = $user;
        // 添加客户端标志
        $now = time();
        $usersession = (array)json_decode(Cache_Redis::instance()->hGet('usersession', $user['uid']), true)?:[];
        foreach ($usersession as $k=>$v)
        {
            //回收无效sessionid
            if(!isset($v['time']) || $now-$v['time']>86400)
            {
                unset($usersession[$k]);
            }
        }
        $usersession[$_COOKIE[SESSION_NAME]] = ['time'=>time(), 'status'=>0];
        Cache_Redis::instance()->hSet('usersession', $user['uid'], json_encode($usersession));

        //销毁验证码
        $this->delCaptcha();
        //重置交易密码状态
        Tool_Md5::pwdTradeCheck($user['uid'], 'del');

        $coinToBtc = IS_MOBILE?$userMo->convertCoin($user, 'btc'):null;

        # TODO 登陆日志
        setcookie('reurl', 'del', 1);
        setcookie('reurl', 'del', 1, '/', Tool_Url::getDomain());
        setcookie('WSTK', Tool_Code::id32Encode($user['uid']), 0, '/');//加密uid存到cookie， 用于websocket身份绑定
        if($postdata['regtype'] =='phone')  //手机身份信息
        {
            $returnData = array(
                'reUrl'=>$_COOKIE['reurl']?str_replace('/?login', '', $_COOKIE['reurl']):'/',
                'user'=>array(
                    'phone'=>preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $user['mo']),
                    'total'=>$coinToBtc
                ),
            );
        }elseif($postdata['regtype'] =='email')  //邮箱登入成功信息
        {
            $email_array = explode("@", $postdata['email']);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($postdata['email'], 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $postdata['email'], -1, $count);
            $rs = $prevfix . $str;
            $returnData = array(
                'reUrl'=>$_COOKIE['reurl']?str_replace('/?login', '', $_COOKIE['reurl']):'/',
                'user'=>array(
                    'email'=>$rs,
                    'total'=>$coinToBtc
                ),
            );
        }

        //跳转URL安全检查
        $_COOKIE['reurl'] = !preg_match('#[\r\n]+|^http[s]?://(?![^?/]+?\.huocoin.com).*$#i', $_COOKIE['reurl'])?:'/';


        $this->recordIpData($user);//记录ip信息
        $userMo->where("uid=$user[uid]")->update(['updated'=> $now,'updateip'=> Tool_Fnc::realip()]);//更新登录时间
        $this->ajax($GLOBALS['MSG']['LOGIN_SUCCESS'], '1', $returnData);//登入成功
        }
        else    //有谷歌验证码进入第二步
        {
            $User = [
                'uid'        =>$user['uid'],
                'success'    =>'success',
                'account'    => $postdata['account'],
                'area'    => $postdata['area']
            ];
             $_SESSION['list'] = $User;
             $this->ajax('登入成功',1,'success');
        }
    }

    //有绑定谷歌验证码go第二步
    public function logintwoAction()
    {
        if(isset($_SESSION['list']['success']))
        {
            $userMo = new UserModel();
            $postdata = Tool_Request::post();
            $postdata = $this->rsaDecode()?:$postdata;
            $uid = $_SESSION['list']['uid'];
            $user = $userMo->where(['uid' => $uid])->fRow();
            //错误次数限制
            $redis = Cache_Redis::instance();
            if(!$_SESSION['list']['area'])
            {
                $errorKey = 'LoginPasswordError_'.$_SESSION['list']['account'];
            }
            else
            {
                $errorKey = 'LoginPasswordError_'.$_SESSION['list']['area']. $_SESSION['list']['account'];
            }

            $errorNum = $redis->get($errorKey);

            if($errorNum>=5)
            {
                $this->ajax($GLOBALS['MSG']['ERROR_NUM_LIMIT'],0,'code');
            }

            if (!Api_Google_Authenticator::verify_key($user['google_key'], $postdata['code']))
            {
                $redis->incr($errorKey);
                $redis->expire($errorKey,7200);
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0,'code');
            }
        }
        else
        {
           $this->ajax($GLOBALS['MSG']['ILLEGAL'],0,'error');
        }

        unset($user['pwd']);
        $realInfo = AutonymModel::getInstance()->field('name,cardtype,idcard')->where(['status'=>2, 'uid'=>$user['uid']])->fRow();
        if($realInfo)
        {
            $user['realInfo'] = $realInfo;
        }

        // 登录之后存储登录信息
        $_SESSION = ['user'=>$user];
        // 添加客户端标志
        $now = time();
        $usersession = (array)json_decode(Cache_Redis::instance()->hGet('usersession', $user['uid']), true)?:[];
        foreach ($usersession as $k=>$v)
        {
            //回收无效sessionid
            if(!isset($v['time']) || $now-$v['time']>86400)
            {
                unset($usersession[$k]);
            }
        }
        $usersession[$_COOKIE[SESSION_NAME]] = ['time'=>time(), 'status'=>0];
        Cache_Redis::instance()->hSet('usersession', $user['uid'], json_encode($usersession));

        //销毁验证码
        $this->delCaptcha();
        //重置交易密码状态
        Tool_Md5::pwdTradeCheck($user['uid'], 'del');

        $coinToBtc = IS_MOBILE?$userMo->convertCoin($user, 'btc'):null;

        # TODO 登陆日志
        setcookie('reurl', 'del', 1);
        setcookie('reurl', 'del', 1, '/', Tool_Url::getDomain());
        setcookie('WSTK', Tool_Code::id32Encode($user['uid']), 0, '/');//加密uid存到cookie， 用于websocket身份绑定
        if($postdata['regtype'] =='phone')  //手机身份信息
        {
            $returnData = array(
                'reUrl'=>$_COOKIE['reurl']?str_replace('/?login', '', $_COOKIE['reurl']):'/',
                'user'=>array(
                    'phone'=>preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $user['mo']),
                    'total'=>$coinToBtc
                ),
            );
        }elseif($postdata['regtype'] =='email')  //邮箱登入成功信息
        {
            $email_array = explode("@", $postdata['account']);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($postdata['account'], 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $postdata['account'], -1, $count);
            $rs = $prevfix . $str;
            $returnData = array(
                'reUrl'=>$_COOKIE['reurl']?str_replace('/?login', '', $_COOKIE['reurl']):'/',
                'user'=>array(
                    'email'=>$rs,
                    'total'=>$coinToBtc
                ),
            );
        }

        //跳转URL安全检查
        $_COOKIE['reurl'] = !preg_match('#[\r\n]+|^http[s]?://(?![^?/]+?\.huocoin.com).*$#i', $_COOKIE['reurl'])?:'/';


        $this->recordIpData($user);//记录ip信息
        $userMo->where("uid=$user[uid]")->update(['updated'=> $now,'updateip'=> Tool_Fnc::realip()]);//更新登录时间
        if($_COOKIE['LANG']=='')   //存用户当前语言到redis
        {
          $lang = 'cn';
        }
        else
        {
            $lang = $_COOKIE['LANG'];
        }
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        UserModel::addRedis($db3, 'setlang', $user[uid], $lang);
        $this->ajax($GLOBALS['MSG']['LOGIN_SUCCESS'], '1', $returnData);//登入成功


    }



    private function recordIpData($user)//登录记录ip信息
    {
        if (!$user)
        {
            return false;
        }
        else
        {
            $eData = array(

                'uid' => $user['uid'],
                'ip' => Tool_Fnc::realip(),
                'time'=>time()
            );

            $eRedis = Cache_Redis::instance('user');
            $dd = $eRedis->lpush('recordIP', json_encode($eData));
            $userloginMo = new UserLoginModel();
            $data        = array(
                'uid'       => $user['uid'],
                'created'   => time(),
                'createdip' =>Tool_Fnc::realip(),
            );
            $userloginMo->insert($data);
            return true;
            /* $ip     = Tool_Fnc::realip();
             $url    = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
             $result = file_get_contents($url);
             $result = json_decode($result, true);
             if ($result['code'] !== 0 || !is_array($result['data']))
             {
                 return false;
             }
             //return $result['data'];
             $ipdata      = $result['data']['ip'];
             if($result['data']['country']=='内网IP'){
                 $iparea = $result['data']['country'];
             }else{
                 $iparea = $result['data']['country'].$result['data']['region'] . $result['data']['city'];
             }

             $userloginMo = new UserLoginModel();
             $data        = array(
                 'uid'       => $user['uid'],
                 'created'   => time(),
                 'createdip' => $ipdata,
                 'area'      => $iparea
             );
           $userloginMo->insert($data);*/
        }

    }
    /**
     * 设置交易密码
     */
    public function setTradePwdAction()
    {
        $pwd1 = $_POST['p1'];
        $pwd2 = $_POST['p2'];
        

        if ($pwd2 != $pwd1)
        {
            $this->ajax($GLOBALS['MSG']['PWD_DIFF']);
        }

        if (strlen($pwd1) < 6 || strlen($pwd1) > 20)
        {
            $this->ajax($GLOBALS['MSG']['PWD_LEN']);
        }

        if (!Tool_Validate::pwd($pwd1))
        {
            $this->ajax($GLOBALS['MSG']['NO_SPC_CHR']);
        }

        $userMo   = UserModel::getInstance();
        $userInfo = $userMo->field('email,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);

        //有设置过交易密码
        if ($userInfo['pwdtrade'])
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        //效验设置交易是否跟登入一样
        $login = Tool_Md5::encodePwd($pwd1,$userInfo['prand']);
        //交易密码加密
        $pwdtrade = Tool_Md5::encodePwdTrade($pwd1,$userInfo['prand']);

        if ($userInfo['pwd'] == $login)
        {
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAME']);
        }

        $userMo->update(array('uid' => $this->mCurUser['uid'], 'pwdtrade' => $pwdtrade));
        $this->delCaptcha(); //销毁图形验证码
        Tool_Session::mark($this->mCurUser['uid']);
        $this->ajax($GLOBALS['MSG']['SET_SUCCESS'], 1);
    }

    /**
     * 重置交易密码
     */
    public function resetTradePwdAction()
    {
        if(!$_SESSION['RESET_TRADE_PWD'])
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        $pwd1 = trim($_POST['p1']);
        $pwd2 = trim($_POST['p2']);

        if ($pwd2 != $pwd1)
        {
            $this->ajax($GLOBALS['MSG']['PWD_DIFF'],0,'pw2');
        }

        if (strlen($pwd1) < 6 || strlen($pwd1) > 20)
        {
            $this->ajax($GLOBALS['MSG']['PWD_LEN'],0,'pw2');
        }

        if (!Tool_Validate::pwd($pwd1))
        {
            $this->ajax($GLOBALS['MSG']['NO_SPC_CHR'],0,'pw2');
        }

        $userMo   = UserModel::getInstance();
        $userInfo = $userMo->field('area,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);
        //效验设置交易是否跟登入一样
        $login = Tool_Md5::encodePwd($pwd1,$userInfo['prand']);
        //重置交易密码加密
        $pwdtrade = Tool_Md5::encodePwdTrade($pwd1, $userInfo['prand']);
        if ($userInfo['pwd'] == $login)
        {
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAME'],0,'pw1');
        }

        $userMo->update(array('uid' => $this->mCurUser['uid'], 'pwdtrade' => $pwdtrade, 'updated'=>time()));
        unset($_SESSION['RESET_TRADE_PWD']);
        Tool_Session::mark($this->mCurUser['uid']);

        //log
        PwdModLogModel::getInstance()->save(array(
                'uid'=>$this->mCurUser['uid'],
                'type'=>2,
                'createdip'=>Tool_Fnc::realip(),
            )
        );

        $this->ajax($GLOBALS['MSG']['SET_SUCCESS'], 1);
    }


    /**
     * 委托查询
     */
    public function getMyTrustAction()
    {
        $this->_ajax_islogin();

        $start    = $_POST['start'];
        $end      = $_POST['end'];
        $coin     = $_POST['coin'];
        $flag     = $_POST['flag'];
        $page     = max(intval($_POST['page']), 1);
        $pageSize = isset($_POST['size']) ? intval($_POST['size']) : 30;

        if (!$coin || !preg_match('/[a-z]/i', $coin))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $where = array('uid=' . $this->mCurUser['uid']);
        if (trim($start))
        {
            $where[] = 'created >= ' . strtotime($start);
        }

        if (trim($end))
        {
            $where[] = 'created <= ' . strtotime($end);
        }

        if ($flag)
        {
            switch ($flag)
            {
                case 1:$where[] = 'flag = "buy"';
                    break; //买
                case 2:$where[] = 'flag = "sale"';
                    break; //卖
                case 3:$where[] = 'status = ' . Trust_CoinModel::STATUS_ALL;
                    break; //全部成交
                case 4:$where[] = 'status = ' . Trust_CoinModel::STATUS_PART;
                    break; //部分成交
                case 5:$where[] = 'status = ' . Trust_CoinModel::STATUS_UNSOLD;
                    break; //未成交
                case 6:$where[] = 'status = ' . Trust_CoinModel::STATUS_CANCEL;
                    break; //已撤销
            }
        }

        $where = implode(' and ', $where);

        $trustCoinMo = Trust_CoinModel::getInstance();

        $pData = $trustCoinMo->getPrepareData(array('coin_from' => $coin));
        $where .= $pData['str'];

        $tSql = sprintf('SELECT * FROM `trust_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);

        $list = $trustCoinMo->query($tSql, $pData['values']);

        foreach ($list as &$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
        }

        $this->ajax('', 1, $list);

    }

    /**
     * 成交查询
     */
    public function getMyOrderAction()
    {
        $this->_ajax_islogin();

        $start    = $_POST['start'];
        $end      = $_POST['end'];
        $coin     = $_POST['coin'];
        $flag     = $_POST['flag'];
        $page     = max(intval($_POST['page']), 1);
        $pageSize = isset($_POST['size']) ? intval($_POST['size']) : 30;

        if (!$coin || !preg_match('/[a-z]/i', $coin))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $where = array();
        if (trim($start))
        {
            $where[] = 'created >= ' . strtotime($start);
        }

        if (trim($end))
        {
            $where[] = 'created <= ' . strtotime($end);
        }

        if ($flag)
        {
            switch ($flag)
            {
                case 1:$where[] = sprintf('(buy_uid=%d or sale_uid=%d)', $this->mCurUser['uid'], $this->mCurUser['uid']);
                    break; //全部
                case 2:$where[] = 'buy_uid=' . $this->mCurUser['uid'];
                    break; //买
                case 3:$where[] = 'sale_uid=' . $this->mCurUser['uid'];
                    break; //卖
            }
        }

        $where = implode(' and ', $where);

        $trustCoinMo = Order_CoinModel::getInstance();

        $pData = $trustCoinMo->getPrepareData(array('coin_from' => $coin));
        $where .= $pData['str'];

        $tSql = sprintf('SELECT * FROM `trust_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
        $list = $trustCoinMo->query($tSql, $pData['values']);

        foreach ($list as &$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
        }

        $this->ajax('', 1, $list);

    }

    /**
     * 提币记录
     */

    public function getCoinOutRecordAction()
    {
        $this->_ajax_islogin();

        $start    = $_POST['start'];
        $end      = $_POST['end'];
        $coin     = $_POST['coin'];
        $page     = max(intval($_POST['page']), 1);
        $pageSize = isset($_POST['size']) ? intval($_POST['size']) : 30;

        if (!$coin || !preg_match('/[a-z]/i', $coin))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $where = array('opt_type'=>'out');
        if (trim($start))
        {
            $where[] = 'created >= ' . strtotime($start);
        }

        if (trim($end))
        {
            $where[] = 'created <= ' . strtotime($end);
        }

        $where = implode(' and ', $where);

        $exchangeMo = Exchange_BaseModel::getInstance();

        $pData = $exchangeMo->getPrepareData(array('coin_from' => $coin));
        $where .= $pData['str'];

        $tSql = sprintf('SELECT `id`,`updated`,`wallet`,`number`,`status` FROM `exchange_%s` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
        $list = $exchangeMo->query($tSql, $pData['values']);

        foreach ($list as &$v)
        {
            $v['updated'] = date('Y-m-d H:i:s', $v['updated']);
        }

        $this->ajax('', 1, $list);

    }

    /**
     * 冲提币统计
     */
    public function getCoinStatisticAction()
    {
        $this->_ajax_islogin();

        $coin = $_POST['coin'];

        if (!$coin || !preg_match('/[a-z]/i', $coin))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $exchangeMo = Exchange_BaseModel::getInstance();

        $pSql      = 'SELECT MIN(`updated`) date, COUNT(*) times, SUM(`number`) number FROM ? where status=="成功" and uid =? and opt_type="out" ';
        $tVals     = array('exchange_' . $coin, $this->mCurUser['uid']);
        $statistic = $exchangeMo->query($pSql, $tVals);

        if ($statistic)
        {
            $data = sprintf($GLOBALS['MSG']['USER_COIN_STATISTIC_MSG'], $statistic['date'], $statistic['times'], $statistic['number']);
        }
        else
        {
            $data = '';
        }

        $this->ajax('', 1, $data);
    }

    /**
     * 提币
     */
    public function coinOutAction()
    {
        $this->_ajax_islogin();
        $userMo = new UserModel();
        $key =  'resetPwdsError' .$this->mCurUser['uid'];
        $user = UserModel::getInstance()->fRow($this->mCurUser['uid']);
        //没有交易密码
        if (!$user['pwdtrade']) $this->ajax($GLOBALS['MSG']['NEED_REAL_AUTH'], 0, 'pwdtrade');

        $realInfo = AutonymModel::getInstance()->where(array('uid' => $this->mCurUser['uid'], 'status' => 2))->fRow();
        //没有实名认证
        if(!$realInfo) $this->ajax($GLOBALS['MSG']['NEED_REAL_AUTH']);

        //图形验证码
        $this->validCaptcha();
        $coin = trim($_POST['coin']);
        $wallet   = trim(strip_tags($_POST['wallet']));
        $number   = trim($_POST['number']);
        $pwdtrade = trim($_POST['pwdtrade']);
        $code     = trim($_POST['code']);
        $coinName = trim($_POST['coin']);
        $action = trim($_POST['action']);
        $regtype = trim($_POST['regtype']);
        $google_code = trim($_POST['google_code']);
        $label = trim($_POST['label']);
        $txid = trim($_POST['txid']);
        $coinMo = new CoinModel();
        $cointype = $coinMo->where("name='{$coin}'")->fOne('type');

        if(($cointype == 'rgb' || $cointype== 'xrp' || $cointype=='eos') && !$label) $this->ajax('請輸入公鑰', 0, 'label');
       
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);
        if($count >= 5) $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'code');//輸入錯誤次數過多，請稍後再試

        //是否被冻结禁止数字货币提现
        $cancoinout = User_CoinModel::getCoinOutStatus($this->mCurUser['uid']);

        if ($cancoinout == 0) $this->ajax($GLOBALS['MSG']['COIN_OUT_FROZEN']);
       
        //钱包地址错误
        if (!$wallet) $this->ajax($GLOBALS['MSG']['WALLET_ADDR_ERROR'], 0, 'wallet');

         if(!$label  && $coinName=='eos') $this->ajax($GLOBALS['MSG']['BIAOQIAN_NO'], 0, 'label');

        //币信息
        $coinInfo = User_CoinModel::getInstance()->where(array('name' => $coinName))->fRow();

        if (!$coinName || !$coinInfo) $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);

        //提币暂停
        if($coinInfo['out_status']==1) $this->ajax($GLOBALS['MSG']['COIN_OUT_STOP']);

        //转出限额
        if (Tool_Math::comp($number, $coinInfo['minout'])==-1 || (Tool_Math::comp($number, $coinInfo['maxout'])==1  && $coinInfo['maxout']>0))
        {
            $this->ajax(sprintf($GLOBALS['MSG']['COIN_OUT_RANGE'], $coinInfo['minout'], $coinInfo['maxout']), 0, 'number');
        }

        $tNum = Tool_Math::format($number);

        //小数位限制
        list($int, $dec) = explode('.', $tNum);
        if ($coinInfo['number_float'] && strlen($dec)>$coinInfo['number_float']) $this->ajax($GLOBALS['MSG']['NUMBER_ERROR'], 0, 'number');

        //用户余额不足
        if (Tool_Math::comp($tNum, $this->mCurUser[$coinName . '_over'])==1) $this->ajax($GLOBALS['MSG']['COIN_NOT_ENOUGH'], 0, 'number');

        //  '100' 康康 测试账号   '101' 康康 测试账号
        $noPhoneCodeUser = ['13232544', '13282497', '100', '13231491', '101'];
        //验证交易密码
        
        if (!$pwdtrade || (Tool_Md5::encodePwdTrade($pwdtrade, $user['prand']) != $user['pwdtrade'])) $this->ajax($GLOBALS['MSG']['TRADE_PWD_ERROR'], 0, 'pwdtrade');

        if($this->mCurUser['google_key'])  //有绑定谷歌验证码
        {
            if (!Api_Google_Authenticator::verify_key($this->mCurUser['google_key'], $google_code))
            {
                UserModel::getInstance()->finderror($key);
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0,'google_code');
            }
        }elseif ($regtype=='email')  //邮箱验证码
        {
            //实例化 13232544
            $validatelogicModel = new ValidatelogicModel();
            // $this->mCurUser['uid']!='13232544'
            if(!in_array($this->mCurUser['uid'], $noPhoneCodeUser))
            {
                if (!$validatelogicModel->emailcodey($code))
                {
                    $userMo->finderror($key);
                    $this->ajax($GLOBALS['MSG']['EMAIL_NUMBER'], 0, 'code');
                }
            }
        }
        elseif($regtype=='phone')            // 手机验证码
        {
            // $this->mCurUser['uid']!='13232544' && $this->mCurUser['uid']!='13282497'
            if(!in_array($this->mCurUser['uid'], $noPhoneCodeUser))
            {
                if (!PhoneCodeModel::verifiCode($this->mCurUser, $action, $code, $user['area']))
                {
                     //存错误次数到redis
                    $userMo->finderror($key);
                    $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR'], 0, 'code');
                }

            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 0, 'error');
        }

        //入库
        $mo  = 'Exchange_' . ucfirst($coinName) . 'Model';
        $tMO = new $mo();

        if (!$a = $tMO->post($wallet, $tNum, $coinName, $this->mCurUser,$label))
        {
            $this->ajax($tMO->getError(2)?:$GLOBALS['MSG']['SYS_ERROR']);
        }
        //销毁验证码
        $this->delCaptcha();

        $this->ajax('', 1);
    }


    /**
     * 用户信息
     */
    public function getUserInfoAction()
    {
        if($this->mCurUser)
        {
            $user = $this->mCurUser;
            foreach ($this->mCurUser as $k => $v)
            {
                if(stripos($k, '_over') || stripos($k, '_lock'))
                {
                    $data[$k] = sprintf('%.8f', $v);
                }
            }
            $data['realInfo'] = intval($this->mCurUser['realInfo']);

            $data['total'] = UserModel::getInstance()->convertCoin($this->mCurUser, 'btc');
            if($this->mCurUser['area']=='+86'){
                $data['phone'] = substr_replace($this->mCurUser['mo'], '****', 3, 4);
            }else{
                $data['phone'] =substr_replace($_SESSION['user']['mo'], '**', -4, 2);
            }
            $data['area'] = $this->mCurUser['area'];
            //加个邮箱是否存在
            if($this->mCurUser['email'])
            {
                $email_array = explode("@", $this->mCurUser['email']);
                $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($this->mCurUser['email'], 0, 3); //邮箱前缀
                $count = 0;
                $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $this->mCurUser['email'], -1, $count);
                $data['email'] = $prevfix . $str;
            }
            else
            {
                $data['email'] = '';
            }

            //$data['phone'] = preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $this->mCurUser['mo']);
        }
        $this->ajax('', 1, $data);

    }



    /**
     * 提币 短信接口
     */
    public function smsAction()
    {
        $this->_ajax_islogin();
        $this->validCaptcha();
        $user   = $this->mCurUser;
        $action  = intval($_POST['action']);
        $key =  'resetPwdsError' .$user['uid'];
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'code');//輸入錯誤次數過多，5分钟再試
        }
        if ($action == 8 && $user['area'] != '+86') {
            $this->ajax($GLOBALS['MSG']['GUOJI_VOICE'], 0, 'code');//国际语音暂不支持
        }


        //限制发送频率
        $start = time() - 3600;
        $count = PhoneCodeModel::getInstance()->where("mo = {$user['mo']} and ctime >= {$start} and area='{$user['area']}' and action = {$action}")->count();

        if ($count >= 20) $this->ajax($GLOBALS['MSG']['SMS_TO_VOICE_LATER'], 0, 'code'); //短信过于频繁，请使用语音验证码

        if (PhoneCodeModel::regverifiTime($user['mo'], $action,$user['area']))
        {
            $sms = PhoneCodeModel::sendCode($user, $action, $user['area']);
            $code = $sms['code'];
            if ($code == '200')
            {
                if(MOBILE_CODE){
                    $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1); //发送成功
                }
                $this->ajax($sms['msg'], 0, 'code'); //演示模式发送成功
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SMS_FAILED'], 0, 'code'); //发送频率过快，请稍后发送
            }

//            $code = PhoneCodeModel::sendCode($user, $action, $user['area']);
//            if ($code == '200')
//            {
//                $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1); //发送成功
//            }
//            else
//            {
//                $this->ajax($GLOBALS['MSG']['SMS_FAILED'], 0, 'code'); //发送频率过快，请稍后发送
//            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'], 0, 'code'); //请您过60秒再点击发送
        }
    }

    public function msgAction()
    {
        if($_POST['regtype']=='phone')
        {
            $action  = intval($_POST['action']);
            $phone  = intval($_POST['phone']);
            $area  = $_POST['area'];
            $key =  'resetPwdsError' .$this->mCurUser['uid'];
            $redis = Cache_Redis::instance();
            $count = $redis->get($key);
            if($count >= 5)
            {
                $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'code');//輸入錯誤次數過多，5分钟再試
            }
            if ($action == 8 && $area != '+86') {
                $this->ajax($GLOBALS['MSG']['GUOJI_VOICE'], 0, 'code');//国际语音暂不支持
            }
            $start = time() - 3600;
            $count = PhoneCodeModel::getInstance()->where("mo = {$phone} and ctime >= {$start} and area='{$area}' and action = {$action}")->count();

            if ($count >= 20)
            {
                //短信过于频繁，请使用语音验证码
                $this->ajax($GLOBALS['MSG']['SMS_TO_VOICE_WARN'], 0, 'code');
            }

            if (PhoneCodeModel::regverifiTime($phone, $action,$area))
            {
                $user['mo']=$phone;
                $code = PhoneCodeModel::sendCode($user, $action, $area);

                if ($code == '200')
                {
                    $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1); //发送成功
                }
                else
                {
                    $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'code'); //發送失敗
                }
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'], 0, 'code'); //请您过60秒再点击发送
            }
        }
    }

    /**
     * 重置交易密码接口
     */
    public function smsnocapAction()
    {
        $this->_ajax_islogin();
        $user   = $this->mCurUser;
        $action  = intval($_POST['action']);
        $key = 'resetPwdsError' .$user['uid'];

//        if($action==8&& $user['area']!='+86'){
//            $this->ajax($GLOBALS['MSG']['GUOJI_VOICE']);//国际语音暂不支持
//        }
        if(!$user['mo']) $this->ajax($GLOBALS['MSG']['NEED_PHONE']);

        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'vcode');//輸入錯誤次數過多，請稍後再試
        }
        //限制发送频率
        $start = time() - 3600;
        $count = PhoneCodeModel::getInstance()->where("mo = {$user['mo']} and ctime >= {$start} and area='{$user['area']}' and action = {$action}")->count();
        if ($count >= 20) $this->ajax($GLOBALS['MSG']['SMS_TO_VOICE_LATER']); //短信过于频繁，請稍後再試


        if (PhoneCodeModel::regverifiTime($user['mo'], $action,$user['area']))
        {
            $sms = PhoneCodeModel::sendCode($user, $action, $user['area']);
            $code = $sms['code'];
            if ($code == '200')
            {
                if(MOBILE_CODE){
                    $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1); //发送成功
                }
                $this->ajax($sms['msg']); //发送成功
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SMS_FAIL']); //发送失败
            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC']); //请您过60秒再点击发送
        }
    }



    /**
     * 短信/邮箱验证交易密码
     */
    public function smsVerifyAction()
    {

        $this->_ajax_islogin();    //接收数据
        $postdata = Tool_Request::post();
        $user = new UserModel();
        $key = 'resetPwdsError' .$this->mCurUser['uid'];  //验证码错误key
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);
        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
        }
        $postdata['google_code'] =(int)$postdata['google_code'];

        if($this->mCurUser['google_key'])  //有绑定谷歌验证码
        {
            if (!Api_Google_Authenticator::verify_key($this->mCurUser['google_key'], $postdata['google_code']))
            {
                UserModel::getInstance()->finderror($key);
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0,'google_code');
            }
        }
        if($postdata['regtype'] =='email') //邮箱重置交易密码
        {
            $data     = [
                'email_mm'   =>$this->mCurUser['email'],
                'email_codemm'     => $postdata['code'],
            ];

            $validatelogicModel = new ValidatelogicModel();   //实例化

            $result = $validatelogicModel->scene('emailcodemm')->check($data);  // 调用场景，check方法验证

            if (!$result)      //如果为空 ，则报错，并输出错误信息
            {

                foreach ($validatelogicModel->getError() as $k => $v)
                {
                    $errorData = $v; // 错误信息
                    $errorM = $k;   // 错误字段
                }
                if($errorM=='email_codemm')
                {
                    $user->finderror($key);
                }
                $this->ajax($errorData, '0', $errorM);
            }

            $scene = $_POST['scene'];
            $_SESSION[$scene] = true;
        }else   //手机重置交易密码
        {
            if(!isset($_POST['scene'], $_POST['code'], $_POST['action']))
            {
                $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
            }
            $action = intval($_POST['action']);
            $code = intval($_POST['code']);
            $scene = $_POST['scene'];


            if (!$code || !PhoneCodeModel::verifiCode($this->mCurUser, $action, $code, $this->mCurUser['area']))
            {
                $user->finderror($key);
                $_SESSION['SMS_ERROR'.$_POST['action']] ++;
                $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR']);
            }
            $_SESSION[$scene] = true;
        }

        $this->ajax('', 1);
    }



//充币
    private function callInterfaceCommon($URL, $type, $params, $headers)
    {
        $ch      = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $URL); //发贴地址
        if ($headers != "")
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        else
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/json'));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        switch ($type)
        {
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "PUT" :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }
        $file_contents = curl_exec($ch);//获得返回值
        curl_close($ch);

        return $file_contents;
    }

    //获取钱包地址wallet=false表示获取失败 c++
    public function getAddressbycAction()
    {
        if (empty($this->mCurUser)) {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        $name = $_POST['coin'] ? trim(addslashes($_POST['coin'])) : 'etw';
        $addressMo = new AddressModel;
        $addr = $addressMo->where("uid = {$this->mCurUser['uid']} and coin = '{$name}' and status = 0")->fOne('address');
        if(empty($addr)){//没有钱包地址，生成钱包地址
            $c_data = array(
                'command' =>'apply_addr',
                'coin' => $name,
            );
            $response = Api_Trans_Client::request($c_data);//调c++
            if($response=='response empty'||empty($response)){//请求无响应
                $data['wallet'] = false;
                $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
            }else if ($response['code'] == 0) {//成功
                $addressda= array(
                    'uid' =>$this->mCurUser['uid'],
                    'coin' => $name,
                    'address' => $response['result']['addr'],
                    'created' => time()
                );
                if ($addressMo->insert($addressda)) {
                    $data['wallet'] = $response['result']['addr'];
                    $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_SUCCESS'], 1, $data);//获取钱包地址成功
                } else {
                    $data['wallet'] = false;
                    $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
                }
            }else{//失败
                $data['wallet'] = false;
                $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
            }
        }else{//有钱包地址
            $data['wallet'] = $addr;
            $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_SUCCESS'], 1, $data);//获取钱包地址成功
        }

    }
    //获取钱包地址wallet=false表示获取失败
    public function getCoinAddressAction()
    {
        if (empty($this->mCurUser))
        {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        $name = $_POST['coin'] ? trim(addslashes($_POST['coin'])) : $_GET['coin'];

        $cointype = CoinModel::getInstance()->where(['name'=>$name])->fOne('type');

        $addressMo = new AddressModel;

        //bit系列 # 一代钱包地址
        if($cointype == 'bit') $wallet = $addressMo->getAddr1($this->mCurUser['uid'], $name);

        //eth系列
        if($cointype == 'eth' || $cointype == 'token') $wallet = $addressMo->getAddr($this->mCurUser['uid'],$name);

        //EOS系列
        if($cointype == 'eos') $wallet = $addressMo->getAddrEos($this->mCurUser['uid'], $name);

        //xrp系列
        if($cointype == 'xrp') $wallet = $addressMo->getAddrXrp($this->mCurUser['uid'], $name);

        //BTM系列
        if($cointype == 'btm') $wallet = $addressMo->getAddrBtm($this->mCurUser['uid'], $name);

        //RGB系列
        if($cointype == 'rgb') $wallet = $addressMo->getAddrRgb($name);


        if (!$wallet) {
            $data['wallet'] = false;
            $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
        }
//
//        //EOS系列
//        if($cointype == 'eos'){
////            $mome = '';
////            for (; true;) {
////                $mome = substr(md5(userid().time()),0,16);
////                if (!M('UserCoin')->where(array($coin . 'p' => $mome))->find()) {
////                    break;
////                }
////            }
////            $rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao, $coin . 'p' => $mome));
////            $this->assign('mome', $mome);
////            if (!$rs) {
////                $this->error('钱包地址添加出错！');
////            }
//        }




//        if ($name == 'rss' || $name == 'npc' || $name == 'mcc')
//        {
//
//            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$name;
//            if (!$rpcurl)
//            {
//                $data['wallet'] = false;
//                $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
//            }
//            //$cointype 用的的js库
//            //$addrType 地址的首位字符
//            if ($name == 'rss')
//            {
//                $cointype = 'redss-js';
//                $addrType = 'A';
//            }
//            else if($name == 'npc')
//            {
//                $cointype = 'asch-js';
//                $addrType = 'A';
//            }
//            else
//            {
//                $cointype = 'mcc-js';
//                $addrType = 'M';
//            }
//            # 二代钱包地址
//            $twoaddress = new AddressModel;
//
//            $wallet = $twoaddress->getAddr($this->mCurUser['uid'], $name);
//            if (!$wallet)
//            {
//                $params     = array('coinType' => $cointype,'addrType'=>$addrType);
//                $params     = json_encode($params);
//                $headers    = array('Content-type: application/json');
//                $url        = Yaf_Registry::get("config")->api->rpcurl->node;//node.js地址
//                $strResult  = $this->callInterfaceCommon($url, "POST", $params, $headers);
//                $strResult  = json_decode($strResult, true);
//                $strResult  = $strResult['data'];
//                $time       = time();
//                if($name == 'mcc')
//                {
//                    $address = $strResult['address']."M";
//                }
//                else
//                {
//                    $address = $strResult['address'];
//                }
//                $insertData = array(
//                    'uid'       => $this->mCurUser['uid'],
//                    'address'   => $address,
//                    'coin'      => $name,
//                    'secret'    => $strResult['newsecret'],
//                    'publicKey' => $strResult['publicKey'],
//                    'status'    => 0,
//                    'created'   => $time
//                );
//
//                //web端钱包登陆接口
//                $params    = array("publicKey" => "{$strResult['publicKey']}");
//                $params    = json_encode($params);
//                $headers   = array('Content-type: application/json');
//                $url       = $rpcurl . "api/accounts/open2/";
//                $strResult = $this->callInterfaceCommon($url, "POST", $params, $headers);//获取全部交易
//                $strResult = json_decode($strResult, true);
//                if ($strResult['success'] == true)
//                {
//                    $twoaddress->insert($insertData);
//                    $wallet = $twoaddress->getAddr($this->mCurUser['uid'], $name);
//                }
//                else
//                {
//                    $data['wallet'] = false;
//                    $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
//                }
//            }
//        }
//        // else if ($name == 'etw' || $name == 'eth' || $name == 'eos' || $name=='etc'|| $name=='htc'|| $name=='mac'|| $name == 'bvt'|| $name == 'ptoc'|| $name == 'obc'|| $name == 'afc'|| $name == 'kkc')
//        else if(in_array($name,array('etw','eth', 'eos','etc','htc','mac', 'bvt','ptoc','obc','afc','kkc','lcc','xtc','ethms','sw','qaq','en','read','ait','bqt','sec','dst','pal', 'jc','ccl','bocc','dco','ctm','uenc','bta','pax','tatatu','mtc')))
//        {
//            # etc地址
//            $addressMo = new AddressModel;
//            $wallet    = $addressMo->getAddr($this->mCurUser['uid'], $name);
//            if (!$wallet)
//            {
//                $etwurl = Yaf_Registry::get("config")->api->rpcurl->$name;
//                if (!$etwurl)
//                {
//                    $data['wallet'] = false;
//                    $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
//                }
//                if($name=='lcc'){//lcc参数不一样
//                    $params = '{"password":"dob88888","save":true}';
//                }else{
//                    $params = '{"jsonrpc":"2.0","method":"personal_newAccount", "params":["bjs88888"],"id":1}';
//                }
//                $headers   = array('Content-type: application/json');
//                $strResult = $this->callInterfaceCommon($etwurl, "POST", $params, $headers);
//                $strResult = json_decode($strResult, true);
////                $this->ajax('1221',0,$strResult);
//                if (isset($strResult['error']))
//                {
//                    $data['wallet'] = false;
//                    $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
//                    //echo json_encode($data);
//                    //exit;
//                }
//                $time       = time();
//                if ($name == 'lcc') {//lcc参数不一样
//                    $addrs = $strResult['address'];
//                } else {
//                    $addrs = $strResult['result'];
//                }
//                if($name == 'eos') {
//                    $tool = new Tool_Validate();
//                    $label = $tool->eos();
//                }
//                $insertData = array(
//                    'address' => $addrs,
//                    'coin'    => $name,
//                    'created' => $time,
//                    'label'   => $label
//                );
////                $this->ajax('scs',0,$insertData);
//                //$sql  = "insert into address(address,coin,created) values ('{$strResult['result']}', '{$name}', {$time})";
//                if (!$addressMo->insert($insertData))
//                {
//                    $data['wallet'] = false;
//                    $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
//                    //echo json_encode($data);
//                    //exit;
//                }
//                $wallet = $addressMo->getAddr($this->mCurUser['uid'], $name);
//            }
//            else if($name=='eos')  //针对已有eos地址的用户增加标签
//            {
//                $actdata=$addressMo->where(array('coin'=>$name,'uid'=>$this->mCurUser['uid']))->fList();
//                if(!$actdata[0]['label'])
//               {
//                   $tool = new Tool_Validate();
//                   $label = $tool->eos();   //随机数
//
//                   $insertData = array(
//                       'id'      => $actdata[0]['id'],
//                       'coin'    => $name,
//                       'created' => time(),
//                       'label'   => $label
//                   );
//                   if (!$addressMo->update($insertData))
//                   {
//                       $data['wallet'] = false;
//                       $this->ajax($GLOBALS['MSG']['FAILURE_BQ'], 0, $data);//获取标签失败
//                   }
//                   $wallet = $addressMo->getAddr($this->mCurUser['uid'], $name);
//               }
//
//            }
//        }
//        else
//        {
//            # 一代钱包地址
//            $addressMo = new AddressModel;
//
//            $wallet = $addressMo->getAddr1($this->mCurUser['uid'], $name);
//
//            if (!$wallet)
//            {
//                $data['wallet'] = false;
//                $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
//                //echo json_encode($data);
//                //exit;
//            }
//        }

        $data['wallet'] = $wallet;
        $this->ajax($GLOBALS['MSG']['GET_WALLETADDRESS_SUCCESS'], 1, $data);//获取钱包地址成功
    }

    function qrimagesAction()
    {
        $text   = isset($_GET['text']) ? $_GET['text'] : 'null';
        $size   = isset($_GET['size']) ? $_GET['size'] : 6;
        $margin = isset($_GET['margin']) ? $_GET['margin'] : 4;
        ob_clean();
        Tool_Qrcode::png($text, false, QR_ECLEVEL_L, $size, $margin, false);
        exit(0);
    }
    public function coinRecordAction($csv = 0)//转币记录
    {
        if (empty($this->mCurUser))
        {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        //$uid = 13231356;
        $uid = $this->mCurUser['uid'];
        if ($csv == 0)
        {
            $coin      = $_POST['coin'] ? trim(addslashes($_POST['coin'])) : 'btc';
            $coinType  = $_POST['coinType'] ? trim(addslashes($_POST['coinType'])) : 'in';
            $type      = $_POST['type'] ? trim(addslashes($_POST['type'])) : 'all';
            $in_type = $_POST['in_type'] ? trim(addslashes($_POST['in_type'])) : 'all';
            $startTime = $_POST['startTime'] ? strtotime(trim(addslashes($_POST['startTime']))) : strtotime(date('Y-m-d', time()) . '00:00:00');
            $endTime   = $_POST['endTime'] ? strtotime(trim(addslashes($_POST['endTime']))) : strtotime(date('Y-m-d', time()) . '23:59:59');
        }
        else
        {//导出excel
            $coin      = $_GET['coin'] ? trim(addslashes($_GET['coin'])) : 'btc';
            $coinType  = $_GET['coinType'] ? trim(addslashes($_GET['coinType'])) : 'in';
            $type      = $_GET['type'] ? trim(addslashes($_GET['type'])) : 'all';
            $in_type = $_POST['in_type'] ? trim(addslashes($_POST['in_type'])) : 'all';
            $startTime = $_GET['startTime'] ? strtotime(trim(addslashes($_GET['startTime']))) : strtotime(date('Y-m-d', time()) . '00:00:00');
            $endTime   = $_GET['endTime'] ? strtotime(trim(addslashes($_GET['endTime']))) : strtotime(date('Y-m-d', time()) . '23:59:59');
        }
        $mo = 'Exchange_' . ucfirst($coin) . 'Model';
        //$datas['list'] = $mo->where($where)->limit($tPage->limit())->order('uid DESC')->fList();
        $table = new $mo();
        if ($type == 'all')
        {
            $where = "uid=$uid and opt_type='$coinType'";
        }
        else if ($type == 1)//当天
        {
            $startTime = strtotime(date('Y-m-d', time()));
            $endTime   = time();
            $where     = "uid=$uid and opt_type='$coinType' and created between $startTime and $endTime";
        }
        else if ($type == 2)//30天
        {
            $startTime = strtotime("-30 day");
            $endTime   = time();
            $where     = "uid=$uid and opt_type='$coinType' and created between $startTime and $endTime";
        }
        else    //筛选
        {

            $where = "uid=$uid and opt_type='$coinType' and created between $startTime and $endTime";
        }
        if($in_type=='all'){//转入类型
            $where .='';
        }elseif($in_type == 1){//普通转入
            $where .= ' and type=1';
        } elseif ($in_type == 2) {//交易挖矿
            $where .= ' and type=2';
        } elseif ($in_type == 3) {//持币分红
            $where .= ' and type=3';
        }
        $data=null;
        $exchangetable='exchange_'.$coin;

        $total = $table->query("select count(a.id) total from (select id,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%s') time,txid,number,confirm,status,type from $exchangetable where $where order by created desc) as a LEFT JOIN (select id,txid from $exchangetable where opt_type='out' and status='成功' and txid!='') as b on a.txid=b.txid order by a.id desc");

        if ($total[0]['total'] == 0) {
            $data['list'] = '';
            $data['pagetotal'] = 0;
            $data['prev'] = '';
            $data['next'] = '';
            $data['currentpage'] = '';
        } else {
            if ($csv == 0) {//分页
                $page = $_POST['page'] ? (int)addslashes($_POST['page']) : 1;//页码
                $pagenumber = $_POST['size'] ? (int)addslashes($_POST['size']) :7;//每页多少条
                $data['pagetotal'] = ceil($total[0]['total'] / $pagenumber);//总页数
                if ($page > $data['pagetotal']) {
                    $page = $data['pagetotal'];
                }
                if ($page < 1) {
                    $page = 1;
                }
                $p = ($page - 1) * $pagenumber;

                $data['list'] = $table->query("select a.id,a.time,a.txid,a.number,a.confirm,a.status,a.type,b.id bid,a.wallet,a.bak from (select id,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%s') time,txid,number,confirm,status,type,wallet,bak from $exchangetable where $where order by created desc) as a LEFT JOIN (select id,txid from $exchangetable where opt_type='out' and status='成功' and txid!='') as b on a.txid=b.txid order by a.id desc limit $p,$pagenumber");

                foreach ($data['list'] as &$v) {//去0
                    $v['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v['number']), '.');
                }
                $data['prev'] = $page - 1;//上一页
                $data['next'] = $page + 1;//下一页
                if ($data['prev'] < 1) {
                    $data['prev'] = 1;
                }
                if ($data['next'] > $data['pagetotal']) {
                    $data['next'] = $data['pagetotal'];
                }
                $data['currentpage'] = $page;//当前页
            }else{//excel
                $data['list'] = $table->query("select a.id,a.time,a.txid,a.number,a.confirm,a.status,a.type,b.id bid,a.wallet,a.bak from (select id,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%s') time,txid,number,confirm,status,type,wallet,bak from $exchangetable where $where order by created desc) as a LEFT JOIN (select id,txid from $exchangetable where opt_type='out' and status='成功' and txid!='') as b on a.txid=b.txid order by a.id desc");

            }

        }
        $statusMap = array(
            '待审核' =>$GLOBALS['MSG']['PENDING_AUDIT'],
            '等待' => $GLOBALS['MSG']['WAIT_FOR_COINOUT'],
            '成功' => $GLOBALS['MSG']['SUCCEED'],
            '已取消' => $GLOBALS['MSG']['CANCELED'],
            '确认中' =>  $GLOBALS['MSG']['CONFORM'],
            '冻结中' => $GLOBALS['MSG']['FREEZING'],
        );
        if($data['list'])
        {
            foreach ($data['list'] as &$v)
            {
                $v['colour'] = $v['status']=='成功'?1:($v['status'] == '冻结中'?3:0);//成功 1 冻结中3 其他0
                $v['type'] = $v['type'] == 1 ? $GLOBALS['MSG']['IN_PUTONG'] : ($v['type'] == 2 ? $GLOBALS['MSG']['IN_MINE'] : $GLOBALS['MSG']['IN_SHARE']);
                $v['thaw_time'] = $v['status']=='冻结中'? date('Y-m-d H:i:s',strtotime("$v[time] +30 day")):'';//解冻时间 30天
                $v['status']= $statusMap[$v['status']];
                if($v['txid']=='' || $v['bak']!=''||!empty($v['bid']))
                {
                    $v['bid'] = $GLOBALS['MSG']['IN_PLATFORM'];//平台内
                }else{
                    $v['bid'] = $GLOBALS['MSG']['OUT_PLATFORM'];//平台外
                }
            }
        }
    
        if ($csv == 1)
        {
            return $data['list'];
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'], 1, $data);//获取数据成功
            //echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function coinRecordMessageAction()//一句话
    {
        if (empty($this->mCurUser))
        {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        //$uid = 132727;
        $uid       = $this->mCurUser['uid'];
        $coin      = $_POST['coin'] ? trim(addslashes($_POST['coin'])) : ($_GET['coin'] ? trim(addslashes($_GET['coin'])) : 'btc');
        $mo        = 'Exchange_' . ucfirst($coin) . 'Model';
        $table     = new $mo();
        $data      = $table->where(array(
            'opt_type' => 'in',
            'uid'      => $uid
        ))->order('created asc')->fList();
        $cointable = new CoinModel();
        //$coinname  = $cointable->field('display')->where(array('name' => "$coin"))->fList();
        if (!empty($data))
        {
            $total     = $table->field('count(id) count,sum(number) number')->where(array(
                'opt_type' => 'in',
                'uid'      => $uid
            ))->order('created asc')->fList();
            $total[0]['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $total[0]['number']), '.');//去0
            $firstTime = date('Y-m-d H:i:s', $data[0]['created']);
            $str = sprintf($GLOBALS['MSG']['USER_COIN_IN_MSG'], $_SESSION['user']['realInfo']['name'], $firstTime, $coin, $total[0]['count'], $total[0]['number']);
            //$str       = '親愛的用戶：您在' . $firstTime . '第一次轉入' . $coinname[0]['display'] . '，至今已累計轉入' . $total[0]['count'] . '筆，共' . $total[0]['number'] . '個幣！';
        }
        else
        {
            $str = '';
        }
        $da['message'] = $str;
        $this->ajax($GLOBALS['MSG']['GET_A_WORD_SUCCESS'], 1, $da);//获取一句话成功
        //echo json_encode($str, JSON_UNESCAPED_UNICODE);
        //exit;
    }

    /**
     * 找回密码  验证手机或邮箱
     */
    public function erifypPhoneAction()
    {
        if($this->mCurUser && (($this->mCurUser['mo']!=$_POST['account']&&$_POST['regtype']=='phone') || ($_POST['regtype']=='email' && $this->mCurUser['email']!=$_POST['account'])))
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        //接收数据
        $postdata = Tool_Request::post();

        $usermo = new UserModel();

        // 实例化
        $validatelogicModel = new ValidatelogicModel();

        if($postdata['regtype']=='email')
        {

            $data = [
                'email_bb'   =>trim($postdata['account']),
                'captcha'    =>$postdata['captcha']
            ];

            // 调用login场景，check方法验证
            $result = $validatelogicModel->scene('email')->check($data);
            //如果为空 ，则报错，并输出错误信息
            if (!$result)
            {
                foreach ($validatelogicModel->getError() as $k => $v)
                {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                $this->ajax($errorData, '0', $errorM);
            }
            $this->delCaptcha();
            $_SESSION['email']= $data['email_bb'];
            $_SESSION['STEP'] = 1;
            // 关联第二步做效验
            $_SESSION['confirm']= 'confirm';
            $users=$usermo->field('uid,email')->where(array('email' => $data['email_bb']))->fList();
            $_SESSION['uid'] = $users[0]['uid'];
            $coinList = UserModel::getInstance()->field('mo,email,google_key')->where(['email'=>$data['email_bb']])->fList();
            if($coinList[0]['google_key'])
            {
                $_SESSION['google_key']= $coinList[0]['google_key'];
                $this->ajax($GLOBALS['MSG']['SUCCESS'],1,array('google_key'=>1));
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SUCCESS'],1,array('google_key'=>0));

            }


        }
        elseif ($postdata['regtype']=='phone')
        {

            $area= $postdata['area']? trim($postdata['area']):'+86';
            $data     = [
                'getpwd_phone'     => trim($postdata['account']),
                'captcha'   => $postdata['captcha'],
                'area'      => $area
            ];

            $users=$usermo->where(array('mo' => $data['getpwd_phone'],'area' => $data['area']))->fList();

            if(empty($users)) {
                $this->ajax($GLOBALS['MSG']['TEL_BUCUNZAI'], '0', 'phone');//手机号不存在
            }else{
                if($users[0]['area']!= $area){
                    $this->ajax($GLOBALS['MSG']['YUN_SEND_FAIL'], '0', 'phone');//輸入號碼與歸屬地不匹配
                }
            }
            $_SESSION['phone']= $data['getpwd_phone'];
            $_SESSION['area'] = $area;
            $_SESSION['uid'] = $users[0]['uid'];
            $_SESSION['STEP'] = 1;

            // 调用login场景，check方法验证
            $result = $validatelogicModel->scene('phone')->check($data);
            //如果为空 ，则报错，并输出错误信息
            if (!$result)
            {
                foreach ($validatelogicModel->getError() as $k => $v)
                {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                $this->ajax($errorData, '0', $errorM);
            }
            $this->delCaptcha();

            // 关联第二步做效验
            $_SESSION['confirm']= 'confirm';
            $user = UserModel::getInstance()->field('mo,email,google_key')->where("mo={$data['getpwd_phone']} and area={$data['area']}")->fList();
            if($user[0]['google_key'])
            {
                $_SESSION['google_key']= $user[0]['google_key'];
                $this->ajax($GLOBALS['MSG']['SUCCESS'],1,array('google_key'=>1));
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SUCCESS'],1,array('google_key'=>0));

            }

        }else
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'],0);
        }

    }
    /**
     * 找回密码  验证身份
     */
    public function authenticateAction()
    {
        //接收数据
        $postdata = Tool_Request::post();
        $user = new UserModel();
        $uid = $this->mCurUser['uid']?:$_SESSION['uid'];
        $key = 'resetPwdsError'.$uid;
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($postdata['regtype']=='email')//邮箱
        {
            $data     = [
                'email_code'     => $postdata['code'],
            ];

            $check = 'emailcode';

        }
        elseif ($postdata['regtype']=='phone')  //手机
        {
            $area  = $this->mCurUser['area']?:$_SESSION['area'];
            $phone = $this->mCurUser['mo']?:$_SESSION['phone'];
            $data  = [
                'phone' => $phone,
                'code'  => $postdata['code'],
                'area'  => $area
            ];
            // show($data);
            $check = 'authenticate';

        }
        else
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);//参数错误
        }

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN']);//輸入錯誤次數過多，5分钟再試
        }
        //公共部分
        $validatelogicModel = new ValidatelogicModel();   //实例化

        // 调用场景，check方法验证
        $result = $validatelogicModel->scene($check)->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result)
        {
            foreach ($validatelogicModel->getError() as $k => $v)
            {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            if($errorM=='code'||$errorM=='email_code')  //错误次数
            {
                $user->finderror($key);

            }
            $this->ajax($errorData, '0', $errorM);
        }

        //设置第二部成功标识
        $_SESSION['STEP'] .= 2;
        $google_key = $this->mCurUser['google_key'] ?:$_SESSION['google_key'];
        if($google_key)  //有绑定谷歌验证码
        {
            if (!Api_Google_Authenticator::verify_key($google_key, trim($postdata['google_code'])))
            {
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0,'google_code');
            }
        }
        $this->ajax($GLOBALS['MSG']['SUCCESS'],1);
    }


    /**
     * 找回密码  重设登录密码
     */
    public function resetPasswordAction()
    {

        //接收数据
        $postdata = Tool_Request::post();

        $phone = $_SESSION['phone']?:$this->mCurUser['mo'];
        $email = $_SESSION['email']?:$this->mCurUser['email'];
        $area=$_SESSION['area']?:$this->mCurUser['area'];

        if($_SESSION['STEP']!='2'&& !$this->mCurUser)
        {
            if((!$phone && !$email) || $_SESSION['STEP'] != '12')  //没有执行前面2步直接执行这个接口  非法操作
             {
                $this->ajax($GLOBALS['MSG']['ILLEGAL'],0);
             }
        }
        $data     = [
            'password'     => $postdata['pwd'],
            'repassword'     => $postdata['repwd'],
        ];

        // 实例化
        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('resetPassword')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result)
        {
            foreach ($validatelogicModel->getError() as $k => $v)
            {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            $this->ajax($errorData, '0', $errorM);
        }

        $tMO   = new UserModel;

        if($postdata['regtype']=='email')//邮箱
        {

            $userInfo = UserModel::getInstance()->field('area,uid,role,pwd,mo,pwdtrade,prand,email')->where(array('email'=>$email))->fRow();
            $pwd   = Tool_Md5::encodePwd($data['password'], $userInfo['prand']);
            $tData = array(
                'uid'          => $userInfo['uid'],
                'email'           => $email,
                'prand'        => $userInfo['prand'],
                'pwd'          => $pwd,
                'role'         => 'user',
                'updated'      => $_SERVER['REQUEST_TIME']
            );
        }
        elseif ($postdata['regtype']=='phone')  //手机
        {
            //重置登入不能密码不能跟交易密码一致

            $userInfo = UserModel::getInstance()->field('area,uid,role,pwd,mo,pwdtrade,prand,email')->where(array('mo'=>$phone,'area'=>"$area"))->fRow();
            if(!$phone)
            {
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
            }
            //   $res=$tMO->field('area,uid,mo,role,prand')->where(array('mo'=>$phone,'area'=>"$area"))->fRow();

            if(!$userInfo)
            {
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
            }
            $prand = $userInfo['prand'];
            $pwd   = Tool_Md5::encodePwd($data['password'], $prand);
            $tData = array(
                'uid'          => $userInfo['uid'],
                'mo'           => $phone,
                'prand'        => $prand,
                'pwd'          => $pwd,
                'role'         => 'user',
                'updated'      => $_SERVER['REQUEST_TIME']
            );
        }

        //效验设置交易是否跟登入一样
        $login = Tool_Md5::encodePwdTrade($data['repassword'],$userInfo['prand']);

        if ($userInfo['pwdtrade'] == $login)
        {
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAMEY'],0,'pw1');
        }


        $db2 = Yaf_Registry::get("config")->redis->default->db;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        # 更新 MYSQL
        if (!$tMO->update($tData))
        {
            $this->ajax($GLOBALS['MSG']['SMS_SHIBAN'],0);
        }

        unset($_SESSION['STEP_2']);
        if($phone && $userInfo['mo']&& $userInfo['email']=='')  //手机找回密码没有绑定邮箱
        {
            if($userInfo['area']!='+86')
            {
                UserModel::addRedis($db2, 'userphone', $area.$tData['mo'], $userInfo['uid']); //库2
            }else
            {
                UserModel::addRedis($db2, 'userphone', $tData['mo'], $userInfo['uid']); //库2
            }

            $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
            UserModel::addRedis($db3, 'uid', $userInfo['uid'], $value); ////库3
            Cache_Redis::instance()->hSet('usersession', $userInfo['uid'], 1);

            //log
            PwdModLogModel::getInstance()->save(array(
                        'uid'=>$userInfo['uid'],
                        'type'=>1,
                        'createdip'=>Tool_Fnc::realip(),
                    )
            );
            session_destroy();
            $this->ajax($GLOBALS['MSG']['RETRIEVE_PASSWORD_SUCCESS'],1,$GLOBALS['MSG']['MODIFY_LOGIN_PASSWORD']);
        }
        elseif ($email &&$userInfo['email']&&$userInfo['phone']=='')   //邮箱找回密码没有绑定手机
        {

            $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
            UserModel::addRedis($db2, 'useremail', $tData['email'], $tData['uid']); //库2
            UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
            Cache_Redis::instance()->hSet('usersession', $tData['uid'], 1);
            //log
            PwdModLogModel::getInstance()->save(array(
                        'uid'=>$userInfo['uid'],
                        'type'=>1,
                        'createdip'=>Tool_Fnc::realip(),
                    )
            );
            session_destroy();
            $this->ajax($GLOBALS['MSG']['RETRIEVE_PASSWORD_SUCCESS'],1,$GLOBALS['MSG']['MODIFY_LOGIN_PASSWORD']);
        }
        elseif ($userInfo['email']&&$userInfo['mo'])  //邮箱或手机都有绑定
        {
            if($userInfo['area']!='+86')
            {
                UserModel::addRedis($db2, 'userphone', $area.$userInfo['mo'], $userInfo['uid']); //库2
            }else
            {
                UserModel::addRedis($db2, 'userphone', $userInfo['mo'], $userInfo['uid']); //库2
            }
            $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
            UserModel::addRedis($db2, 'useremail', $userInfo['email'], $tData['uid']); //库2
            UserModel::addRedis($db3, 'uid', $userInfo['uid'], $value); ////库3
            //log
            PwdModLogModel::getInstance()->save(array(
                    'uid'=>$userInfo['uid'],
                    'type'=>1,
                    'createdip'=>Tool_Fnc::realip(),
                )
            );
            session_destroy();
            $this->ajax($GLOBALS['MSG']['RETRIEVE_PASSWORD_SUCCESS'],1,$GLOBALS['MSG']['MODIFY_LOGIN_PASSWORD']);

        }

        else
        {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0, 'fail');
        }


    }


    /**
     * 邀请有礼查询
     */
    public function inviteAction()
    {
        $this->_ajax_islogin();

        $size = 10;
        $page = coll('post','page');
        $page = (($page?$page:1)-1)*$size;


        $sql = "select a.status,u.mo,u.email,u.created from user u left join autonym a on a.uid=u.uid where u.from_uid={$this->mCurUser['uid']} order by created desc limit {$page},{$size}";
        $data['list'] = UserModel::getInstance()->query($sql);

        $sql = "select count(u.uid) count from user u left join autonym a on a.uid=u.uid where u.from_uid={$this->mCurUser['uid']}";
        $count = UserModel::getInstance()->query($sql);
        $data['total'] = $count[0]['count'];
        $data['pages'] = ceil($data['total']/$size);

        foreach ($data['list'] as &$v){
            $v['created'] = date('Y-m-d',$v['created']);
        }

        $this->ajax('',1,$data);
//
//        switch ($type)
//        {
//            case 1:
//                $this->getInviteRecord();//邀请记录
//                break;
//            case 2:
//                $this->getInviteRankingList();//排行榜
//                break;
//        }
//        die;
    }


    /**
     * 提取返佣奖励
     */
    public function rebateInAction()
    {
        $this->_ajax_islogin();

        $number = $_POST['number'];
        $coin   = $_POST['coin'];
        $type   = $_POST['type']?:'trade';
        if(0>=$number || !$coin)
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        //提币限额
        $pair = array(
            'mcc'=>['min'=>50, 'numLimit'=>0],
            'btc'=>['min'=>0.01, 'numLimit'=>8],
            'eth'=>['min'=>0.01, 'numLimit'=>8],
        );
        //MCC最低提取额度：50MCC，且只能提取整數。
        //BTC最低提取額度：0.01BTC，且最多不超過8位小數。
        //ETH最低提取額度：0.01BTC，且最多不超過8位小數。
        if($coin=='mcc' && (Tool_Math::comp($number, intval($number))!=0 || $number<$pair[$coin]['min']))
        {
            $this->ajax($GLOBALS['MSG']['NUMBER_ERROR']);
        }
        elseif(Tool_Math::comp($number, Tool_Math::format($number, $pair[$coin]['numLimit']))!=0 || Tool_Math::comp($number, $pair[$coin]['min'])==-1)
        {
            $this->ajax($GLOBALS['MSG']['NUMBER_ERROR']);
        }


        $mo = 'Exchange_'.ucfirst($coin).'Model';
        $exchangeMo = $mo::getInstance();
        $exchangeMo->begin();

        //检查用户奖励余额， 然后加进该币正常余额
        $userInfo = UserModel::getInstance()->lock()->fRow($this->mCurUser['uid']);
        $rebate = $userInfo['rebate']?json_decode($userInfo['rebate'], true):'';

        $coinInKey = $coin;
        if($type=='trade'&&$coin=='mcc')
        {
            $coinInKey = 'mcc_rebate';
        }
        //奖励余额不足
        if(!$rebate || Tool_Math::comp($rebate[$coinInKey.'_in'], $number)==-1)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['BALANCE_NOT_ENOUGH']);
        }

        //可用金额
        $rebate[$coinInKey.'_in'] = Tool_Math::sub($rebate[$coinInKey.'_in'], $number);
        //已提取金额
        $rebate[$coinInKey.'_out'] = Tool_Math::add($rebate[$coinInKey.'_out'], $number);

        $InUserData  = array('uid'=>$this->mCurUser['uid'], $coin.'_over' =>Tool_Math::add($userInfo[$coin.'_over'], $number), 'rebate'=>json_encode($rebate));
        if(!UserModel::getInstance()->save($InUserData))
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'].' [1]');
        }

        if($coin!='mcc')
        {
            //扣除COIN_FEE用户余额
            $OutUserData  = array($coin.'_over' => -$number);
            $outUser = array('uid'=>User_AdminModel::COIN_FEE);
            if(!UserModel::getInstance()->safeUpdate($outUser, $OutUserData))
            {
                $exchangeMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'].' [2]');
            }


            //存记录到exchange表
            $txid = $this->mCurUser['uid'].(microtime(true)*10000);

            $now = time();
            //转入记录
            $exchangeInData = array(
                'uid'=>$this->mCurUser['uid'],
                'admin' => 6,
                'email'=>'',
                'wallet'=>'',
                'opt_type'=>'in',
                'number'=>$number,
                'created'=>$now,
                'updated'=>$now,
                'is_out'  => 1,
                'createip'=>Tool_Fnc::realip(),
                'bak'=>'奖励提取',
                'status'=>'成功',
                'txid'=>$txid,
            );

            if(!$exchangeMo->save($exchangeInData))
            {
                $exchangeMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'].' [3]');
            }

            //转出记录
            $exchangeOutData = array(
                'uid'=>User_AdminModel::COIN_FEE,
                'admin' => 6,
                'email'=>'',
                'wallet'=>'',
                'opt_type'=>'out',
                'number'=>$number,
                'created'=>$now,
                'updated'=>$now,
                'is_out'  => 1,
                'createip'=>Tool_Fnc::realip(),
                'bak'=>'奖励提取',
                'status'=>'成功',
                'txid'=>$txid,
            );

            if(!$exchangeInid = $exchangeMo->save($exchangeOutData))
            {
                $exchangeMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'].' [4]');
            }

        }


        $rebateData = array(
            'coin'=>$coin,
            'number'=>$number,
            'uid'=>$this->mCurUser['uid'],
            'type'=>0,
            'created'=>time(),
            'exchange_id'=>$exchangeInid,
        );

        if(!User_RebateLogModel::getInstance()->save($rebateData))
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'].' [5]');
        }

        $exchangeMo->commit();

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);

    }

    /**
     * 获取邀请人信息
     */
    public function getRfAction()
    {
        $fromUid = isset($_COOKIE['regfrom'])?Tool_Code::idDecode($_COOKIE['regfrom']):0;
        is_numeric($fromUid) or $fromUid = 0;
        $data = '';

        if($fromUid)
        {
            $userInfo = UserModel::getInstance()->field('mo,email')->fRow($fromUid);

            if($userInfo)
            {
                if($userInfo['mo'])
                {
                    $moLen = strlen($userInfo['mo']);
                    $mo = substr_replace($userInfo['mo'], str_pad('',$moLen>7?4:$moLen-4,"*"), max(-8, -$moLen), -4);
                    $data = $GLOBALS['MSG']['INVITER'] .' : '. $mo;
                }
                else
                {

                    $email_array = explode("@", $userInfo['email']);
                    $not= substr_replace($email_array[0], '**', -4, 2);
                    $email= $not.'@'.$email_array[1];
                    $data = $GLOBALS['MSG']['INVITER'] .' : '. $email;
                }

            }
        }

        $this->ajax('', 1, $data);
    }


    public function logoutAction()
    {
        if(isset($this->mCurUser['uid']))
        {
            Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'del');
            $redis = Cache_Redis::instance();
            setcookie('reurl', 'del', 1);
            setcookie('WSTK', 'del', 1);
            $redis->del('admin_google_auth_'.$this->mCurUser['uid']);
            session_destroy();
        }

        $this->ajax('', 1);
    }


    /**
     * 撤销转出
     */
    public function cancelOutAction()
    {
        $this->_ajax_islogin();

        if(!isset($_POST['id'], $_POST['coin']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $uid = $this->mCurUser['uid'];

        $coin = $_POST['coin'];
        $id       = intval($_POST['id']);
        $time     = time();

        $moName = 'Exchange_'.ucfirst($coin).'Model';
        $exchangeMo = new $moName;
        $exchangeMo->begin();
        $data = $exchangeMo->lock()->fRow($id);

        //記錄不存在或者正在轉出中
        if(!$data || $data['status'] != '等待' || $data['confirm']>0)
        {
            $this->ajax($GLOBALS['MSG']['RECORD_NOT_EXISIT']);
        }
        //用戶id不一致，非法操作
        if($data['uid'] != $uid)
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        $userMo = new UserModel();
        $userInfo = $userMo->lock()->field("{$coin}_lock,{$coin}_over")->fRow($uid);

        //判断用户冻结餘額
        if(Tool_Math::comp($userInfo[$coin.'_lock'], $data['number']) == -1)
        {
            $this->ajax($GLOBALS['MSG']['BALANCE_NOT_ENOUGH']);
        }

        //修改轉出單狀態
        $result = $exchangeMo->update(array('status'=>'已取消', 'updated'=>$time, 'id'=>$id));

        if(!$result)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY']);
        }

        //修改用餘額
        $userSaveData = array(
            "{$coin}_lock"=>Tool_Math::sub($userInfo[$coin.'_lock'], $data['number']),
            "{$coin}_over"=>Tool_Math::add($userInfo[$coin.'_over'], $data['number']),
            'updated'=>$time,
            'updateip'=>Tool_Fnc::realip(),
            'uid'=>$uid,
        );
        $result = $userMo->save($userSaveData);
        if (!$result)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'].'[2]');
        }
        $exchangeMo->commit();

        //刷新用戶信息緩存
        Tool_Session::mark($uid);

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);

    }


    /**
     * 送币通用接口
     */
    public function coinGiftAction()
    { die;
        $this->_ajax_islogin();

        //实名认证
        if (!$this->mCurUser['realInfo']) {
            $this->ajax($GLOBALS['MSG']['NEED_REAL_AUTH'], 0, array('need_real_auth' => 1));
        }

        if (!isset($_POST['coin']) || !preg_match('/[a-z]/i', $_POST['coin'])) {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }
        $fiveflag=false;//判断是否五次标志
        $coin = strtolower($_POST['coin']);

        //赠送配置
        $activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1])->fRow();


        if (!$activity || !$conf = json_decode($activity['conf'], true)) {
            $this->ajax($GLOBALS['MSG']['HUODONG_BUCUN'], 2);//该活动不存在
        }
        if(time()<$activity['start_time'])
        {
            $this->ajax($GLOBALS['MSG']['ACTIVITY_ABOUT_TO_START'], 2);//活動即將開始
        }

        if (time() > $activity['end_time']) {
            $this->ajax($GLOBALS['MSG']['HUODONG_NO']);//活动已结束!
        }

        $moName = 'Exchange_' . ucfirst($coin) . 'Model';

        $exchangeMo = $moName::getInstance();
        if (in_array($coin, array('kkc', 'afc','ctz'))) {//每天只能領取一次哦
            $todaytime = strtotime(date('Ymd', time()));//今天0点
            $bak = '领取' . $coin;
            if ($exchangeMo->where("uid={$this->mCurUser['uid']} and bak='$bak' and created>=$todaytime")->count()) {
                $this->ajax($GLOBALS['MSG']['HUODONG_YI']);//每人每天只能領取一次哦
            }
        } else {
            if ($exchangeMo->where(['uid' => $this->mCurUser['uid'], 'bak' => '领取' . $coin])->count()) {
                $this->ajax($GLOBALS['MSG']['HUODONG_YICI']);//每人只能領取一次哦
            }
        }

        $pool = array();
        $numLen = 100;

        //注册时间
        $time = $_SESSION['user']['created'];

        //活动开始注册的用户领币规则
        if ($time > $activity['start_time'] && $_POST['coin'] == 'nbtc') {
            //活动开始的新用户
            $conf['percent'] = $conf['percent1'];
        } else {
            //老用户
            $conf['percent'] = $conf['percent'];
        }
        $redis = Cache_Redis::instance();

        $today = strtotime(date('Y-m-d 23:59:59', time()));

        if(!$redis->hsetnx('mylist',$this->mCurUser['uid'], 1))
        {
            $this->ajax($GLOBALS['MSG']['HUODONG_YI']);//每人每天只能领取一次
        }
        //以毫秒为单位设置指定key的值和过期时间。成功返回true。
        $ret = $redis->expireat('mylist',$today);
        foreach ($conf['percent'] as $range => $percent) {
            list($min, $max) = explode('~', $range);
            $bnum = 100;//放大倍数
            $min *= $bnum;
            $max *= $bnum;
            //  整数领取规则
            /* if($min<1)
             {
                 list($intNum, $floatNum) = explode('.', Tool_Math::eftnum($min));

                 $bnum = strlen($floatNum)*10;
                 $min *= $bnum;
                 $max *= $bnum;

             }*/
            for ($i = 0; $i < $numLen * $percent; $i++) {
                $pool[] = rand($min, $max) / $bnum;

            }
        }
        $getCoin = $pool[rand(0, $numLen - 1)];
        if (!is_numeric($getCoin)) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[0]');
        }
        $totalRecordKey = 'coinGift' . ucfirst($coin) . 'Total';

        $getTotal = Cache_Redis::instance()->get($totalRecordKey);

        if ($getTotal === '' || $getTotal === false) {
            $totalarr = $exchangeMo->field('sum(number) total')->where(['opt_type' => 'in', 'status' => '成功', 'bak' => '领取' . $coin])->fList();
            $getTotal = $totalarr[0]['total'];
        }

        if ($getTotal >= $conf['total']) {
            $this->ajax($GLOBALS['MSG']['HUODONG_LO']);//晚來了一步,已經送完咯!
        }

        $userMo = UserModel::getInstance();
        $now = time();
        $ip = Tool_Fnc::realip();

        $userMo->begin();
        //活动签到
        if (in_array($coin, array('kkc', 'afc'))) {//参与签到的币种
            $todaytime = strtotime(date('Ymd', time()));//今天0点
            $yesterdaytime = strtotime(date("Y-m-d", strtotime("-1 day")));//昨天0点

            $bak = '领取' . $coin;
            if ($exchangeMo->where("uid={$this->mCurUser['uid']} and bak='$bak' and created BETWEEN $yesterdaytime and $todaytime")->count()) {//昨天有签到

                $sign = '+1';
            } else {
                $sign = '1';
            }
            $signmo = new ActivitySignModel();
            $signdata = $signmo->where("uid={$this->mCurUser['uid']} and coin='$coin'")->fList();

            if (empty($signdata)) {//空 则插入
                $sign_insert_data = array(
                    'uid' => $this->mCurUser['uid'],
                    'coin' => $coin,
                    'created' => time(),
                    'sign' => 1
                );
                if (!$signmo->insert($sign_insert_data)) {
                    $userMo->back();
                    $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[0]');
                }
            } else {//更新
                $time = time();
                if ($sign === '+1') {//连续签到加1
                    if ($signdata[0]['sign'] >= 5) { //大于等于5
                        $udtedsign = 1;
                    } else {
                        $udtedsign = $signdata[0]['sign'] + 1;
                    }
                    $sign_updated_data = array(
                        'sign' => $udtedsign,
                        'updated' => time(),
                    );
                } else {
                    $sign_updated_data = array(
                        'sign' => 1,
                        'updated' => time(),
                    );
                }
                //$updatesql="UPDATE `activity_sign` SET sign=$sign ,updated=$time  WHERE uid={$this->mCurUser['uid']} and coin='$coin'";
                if (!$signmo->where("uid={$this->mCurUser['uid']} and coin='$coin'")->update($sign_updated_data)) {
                    $userMo->back();
                    $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[01]');
                }
            }
            $Sdata = $signmo->where("uid={$this->mCurUser['uid']} and coin='$coin'")->fList();
            if ($Sdata[0]['sign'] == 5) {//连续签到5天额外送币
                $getCoin = $getCoin + $conf['fivedays'];
                $fiveflag=true;
            }
        }
        //活动签到
        $exData = array(
            'uid' => $this->mCurUser['uid'],
            'admin' => 6,
            'email' => '',
            'wallet' => '',
            'opt_type' => 'in',
            'number' => $getCoin,
            'created' => $now,
            'updated' => $now,
            'is_out' => 1,
            'createip' => $ip,
            'bak' => '领取' . $coin,
            'status' => '成功',
            'txid' => '',
        );
        if (!$exchangeMo->save($exData)) {
            $userMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[1]');
        }

        //更新用户余额
        $r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $this->mCurUser['uid']));

        if (!$r) {
            $userMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[2]');
        }
        //更新来源用户余额
        $r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $this->mCurUser['uid']));
        if (!$r) {
            $userMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[3]');
        }

        //领取记录
        $urData = array(
            'uid' => $this->mCurUser['uid'],
            'aid' => $activity['id'],
            'coin' => $coin,
            'created' => $now,
            'updated' => $now,
            'number' => $getCoin,
        );

        $r = UserRewardModel::getInstance()->save($urData);
        if (!$r) {
            $userMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[4]');
        }

        $userMo->commit();

        Cache_Redis::instance()->set($totalRecordKey, $getTotal + $getCoin);
        Tool_Session::mark($this->mCurUser['uid']);
        if($fiveflag){
            $this->ajax($GLOBALS['MSG']['HUODONG_SIGN'], 1, $getCoin);//恭喜您連續簽到5次，獲得
        }else{
            $this->ajax($GLOBALS['MSG']['HUODONG_CG'], 1, $getCoin);//領取成功
        }
    }



    /**
     *  抢红包
     */

    public function coindobAction()
    {   die;
        $this->_ajax_islogin();

        //实名认证
        if (!$this->mCurUser['realInfo']) {
            $this->ajax($GLOBALS['MSG']['NEED_REAL_AUTH'], 0, array('need_real_auth' => 1));
        }

        if (!isset($_POST['coin']) || !preg_match('/[a-z]/i', $_POST['coin'])) {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $coin = $_POST['coin'];
        //赠送配置
        $activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1])->fRow();

        if (!$activity) {
            $this->ajax($GLOBALS['MSG']['HUODONG_BUCUN'], 2);//该活动不存在
        }


        if (time() > $activity['end_time']) {
            $this->ajax($GLOBALS['MSG']['HUODONG_NO']);//活动已结束!
        }

        //活動刷新時間
        $startTime = array('round1'=>'10:00', 'round2'=>'15:00');

        $now = time();
        if($activity['start_time']<time())
        {
            if($now>=$currentRoundTime = strtotime(date('Y-m-d ').$startTime['round2']))
            {
                $nextRoundTime = strtotime(date('Y-m-d ').$startTime['round1'])+86400;
            }
            elseif($now>=$currentRoundTime = strtotime(date('Y-m-d ').$startTime['round1']))
            {
                $nextRoundTime = strtotime(date('Y-m-d ').$startTime['round2']);
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['ACTIVITY_ABOUT_TO_START']);
            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['ACTIVITY_ABOUT_TO_START']);
        }

        $moName = 'Exchange_' . ucfirst($coin) . 'Model';
        $exchangeMo = $moName::getInstance();

        //查詢是否領取過
        $bak = '领取' . $coin;
        if ($exchangeMo->where("uid={$this->mCurUser['uid']} and bak='$bak' and created>={$currentRoundTime}")->count() || TicketlogModel::getInstance()->where("uid={$this->mCurUser['uid']} and created>={$currentRoundTime}")->count())
        {
            $this->ajax('您已領取過該紅包。<br>下輪活動開始時間:<br>'.date('Y年m月d日 H:i:s', $nextRoundTime));
        }

        //名額隊列
        $qKey = 'qhbQuotalist';
        $redis = Cache_Redis::instance();
        if(!$redis->rpop($qKey))
        {
            $this->ajax('很遺憾，本輪紅包已被領完。<br>下輪活動開始時間：<br>'.date('Y年m月d日 H:i:s', $nextRoundTime));//晚來了一步,已經送完咯!
        }

        //獎品概率
        $numLen = 10000;
        $randNum = rand(0, $numLen - 1);

        if($randNum<0.6*$numLen)
        {
            $getGift = ['dob', 3];
        }
        elseif($randNum<0.8*$numLen)
        {
            $getGift = ['ticket', 3];
        }
        elseif($randNum<0.9*$numLen)
        {
            $getGift = ['dob', 5];
        }
        elseif($randNum<0.998*$numLen)
        {
            $getGift = ['ticket', 7];
        }
        elseif($randNum<0.999*$numLen)
        {
            $getGift = ['dob', 30];
        }
        else
        {
            $getGift = ['ticket', 30];
        }

        //得到dob
        if($getGift[0]=='dob')
        {
            $getCoin = $getGift[1];
            $userMo = UserModel::getInstance();
            $ip = Tool_Fnc::realip();
            $now = time();
            $userMo->begin();
            $exData = array(
                'uid' => $this->mCurUser['uid'],
                'admin' => 6,
                'email' => '',
                'wallet' => '',
                'opt_type' => 'in',
                'number' => $getCoin,
                'created' => $now,
                'updated' => $now,
                'is_out' => 1,
                'createip' => $ip,
                'bak' => '领取' . $coin,
                'status' => '成功',
                'txid' => '',
            );
            if (!$exchangeMo->save($exData)) {
                $userMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[1]');
            }

            //更新用户余额
            $r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $this->mCurUser['uid']));

            if (!$r) {
                $userMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[2]');
            }

            //更新来源用户余额
            $r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $this->mCurUser['uid']));
            if (!$r) {
                $userMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[3]');
            }

            //领取记录
            $urData = array(
                'uid' => $this->mCurUser['uid'],
                'aid' => $activity['id'],
                'coin' => $coin,
                'created' => $now,
                'updated' => $now,
                'number' => $getCoin,
            );

            $r = UserRewardModel::getInstance()->save($urData);
            if (!$r) {
                $userMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[4]');
            }

            $userMo->commit();

            Tool_Session::mark($this->mCurUser['uid']);

        }
        //領取到免交易費卷
        elseif($getGift[0]=='ticket')
        {
            $ticketMo = TicketlogModel::getInstance();
            $ticketMo->begin();
            $r = $ticketMo->save(array('uid'=>$this->mCurUser['uid'], 'expire'=>$getGift[1], 'created'=>$now));
            if (!$r) {
                $ticketMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[5]');
            }
            $freeTradeUserMo = FreeTradeUserModel::getInstance();
            $ftu = $freeTradeUserMo->where(array('uid'=>$this->mCurUser['uid']))->fRow();
            if(!$ftu)
            {   //第一次
                $r = $freeTradeUserMo->insert(array('uid'=>$this->mCurUser['uid'], 'end_time'=>$now+86400*$getGift[1]));
            }
            elseif($ftu['end_time']<time() && $ftu['end_time']>0)
            {   //已過期
                $r = $freeTradeUserMo->where(array('uid'=>$this->mCurUser['uid'],))->update(array('end_time'=>$now+86400*$getGift[1]));
            }
            elseif($ftu['end_time']>0)
            {   //還沒過期
                $r = $freeTradeUserMo->where(array('uid'=>$this->mCurUser['uid'],))->update(array('end_time'=>$ftu['end_time']+86400*$getGift[1]));
            }

            if (!$r) {
                $ticketMo->back();
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'] . '[6]');
            }

            $ticketMo->commit();
        }

        $msg = $getGift[0]=='dob'?sprintf($GLOBALS['MSG']['RECEIPT_COIN_NOTICE'], $getGift[1].$getGift[0]):sprintf($GLOBALS['MSG']['RECEIPT_VOLUME_NOTICE'], $getGift[1]);
        $this->ajax($msg, 1, array('type'=>$getGift[0]));//領取成功

    }



    /*
    * 分叉幣配置
    */
    private function getBranchConf($coin='all', $branch='all')
    {
        $coinList = array(
            'BTC'=>array(
                'SBTC'=>array(
                    'height'=>500944,
                    'percent'=>1,
                    'expire'=>'2018-01-01 18:00'
                ),
                'LBTC'=>array(
                    'height'=>500000,
                    'percent'=>1,
                    'expire'=>'2018-01-01 18:00'
                ),
            ),
            'ETH'=>array(),
        );
        return $coin=='all'?$coinList:$coinList[strtoupper($coin)][strtoupper($branch)];
    }


    /*
    * 分叉幣列表
    */
    public function branchlistAction()
    {
        $coinList = $this->getBranchConf();

        $cKey = 'dessertlist';
        foreach($coinList as $parent=>&$blocks)
        {
            foreach($blocks as $branch=>&$oneblock)
            {
                $oneblock['received'] = 0;//已经领取
                $oneblock['unreceived'] = 0;//可领取
                $branchCoin = strtolower($branch);
                $open = Cache_Redis::instance()->hGet('coin_branch', 'coin_branch_'.$branchCoin);
                if($open)
                {
                    $open = json_decode($open, true);
                    //已领取数量
                    $mo = 'Exchange_'.ucfirst($branchCoin).'Model';
                    $exchange = $mo::getInstance()->where(['uid'=>$this->mCurUser['uid'], 'bak'=>'领取分叉币'])->fRow();
                    if($exchange)
                    {
                        $oneblock['received'] = $exchange['number'];
                    }
                    else
                    {
                        $oneblock['unreceived'] = UserModel::getInstance()->getCoinSnapshot($this->mCurUser['uid'], strtolower($parent), $open['date']);
                    }
                }
                $oneblock['open'] = boolval($open);
                $oneblock['branch'] = $branch;
                $oneblock['parent'] = $parent;
                $oneblock['percent'] = sprintf('1%s:%s%s', $parent, $oneblock['percent'], $branch);
            }
            $blocks = array_values($blocks);
        }
        $this->ajax('', 1, $coinList);
    }

    /*
    * 領取分叉幣
    */
    public function getBranchAction()
    {
        $this->_ajax_islogin();

        if(!isset($_POST['branch'], $_POST['parent']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $branch = strtolower($_POST['branch']);
        $parent = strtolower($_POST['parent']);

        //是否截止
        $conf = $this->getBranchConf($parent, $branch);
        if(!$conf || time()>strtotime($conf['expire']))
        {
            $this->ajax($GLOBALS['MSG']['ACTIVITY_EXPIRED']);
        }

        //是否可以领取

        $open = Cache_Redis::instance()->hGet('coin_branch', 'coin_branch_'.$branch);
        if(!$open)
        {
            $this->ajax($GLOBALS['MSG']['BLOCK_NOT_EXISTS']);
        }

        $open = json_decode($open, true);

        //是否已经领取
        $mo = 'Exchange_'.ucfirst($branch).'Model';
        $exchangeMo = $mo::getInstance();
        $exists = $exchangeMo->where(['uid'=>$this->mCurUser['uid'], 'bak'=>'领取分叉币'])->fRow();
        if($exists)
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        //领币
        $now = time();
        $ip = Tool_Fnc::realip();
        $parentNum = UserModel::getInstance()->getCoinSnapshot($this->mCurUser['uid'], $parent, $open['date']);
        $number = Tool_Math::mul($parentNum, $conf['percent']);
        if(Tool_Math::comp($number, 0)==0)
        {
            $this->ajax($GLOBALS['MSG']['NO_BRANCH_COIN']);
        }
        $exchangeMo->begin();
        $saveData = array(
            'uid'=>$this->mCurUser['uid'],
            'admin' => 6,
            'email'=>'',
            'wallet'=>'',
            'opt_type'=>'in',
            'number'=>$number,
            'created'=>$now,
            'updated'=>$now,
            'is_out'  => 1,
            'createip'=>$ip,
            'bak'=>'领取分叉币',
            'status'=>'成功',
            'txid'=>'',
        );
        $r = $exchangeMo->save($saveData);
        if(!$r)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'].'[1]');
        }

        $r = UserModel::getInstance()->exec(sprintf('update user set '.$branch.'_over = '.$branch.'_over+%s, updated=%d, updateip="%s" where uid=%d', $number, $now, $ip, $this->mCurUser['uid']));
        if(!$r)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'].'[2]');
        }
        $exchangeMo->commit();
        Tool_Session::mark($this->mCurUser['uid']);
        $this->ajax($GLOBALS['MSG']['SUCCEED'], 1);
    }


    /*
    * 活动赠送币
    */
    public function giftListAction()
    {
        $this->_ajax_islogin();

        $activity = ActivityModel::getInstance()->where(['bak'=>'zs666', 'status'=>1])->fList();
        if(!$activity || !$activityId = array_column($activity, 'id'))
        {
            $this->ajax('', 1, []);
        }

        $where = sprintf('aid in (%s) and uid=%d', implode(',', $activityId), $this->mCurUser['uid']);
        $UserRewardM=new UserRewardModel();
        $receives = $UserRewardM->field('aid,sum(number) number')->where($where)->group('aid')->fList();
        if($receives)
        {
            $receives = array_column($receives, 'number', 'aid');
        }

        $data = array();
        foreach ($activity as $v)
        {
            $bnumber= isset($receives[$v['id']]) ? Tool_Math::eftnum($receives[$v['id']]) : 0;
            if($v['conf'])
            {
                $v['conf'] = json_decode($v['conf'], true);
                if(isset($v['conf']['user_hide']))
                {
                    continue;
                }
            }
            //控制按钮
            $coin= str_replace('赠送', '', $v['name']);
            if (in_array($coin, array('kkc', 'afc','lcc','cash','mbt','ctz','nrc'))) {//参与签到的币种 控制领取按钮
                $mo = 'Exchange_' . ucfirst($coin) . 'Model';
                $exchangeMo = new $mo();
                $todaytime = strtotime(date('Ymd', time()));//今天0点
                $bak = '领取' . $coin;
                if ($exchangeMo->where("uid={$this->mCurUser['uid']} and bak='$bak' and created>$todaytime")->count()) {//今天有签到
                    $button = 0;//0 不可领取
                } else {
                    $button = 1;//1 领取
                }
            }
            elseif (!$v['conf'] || isset($v['conf']['no_btn'])) {
                $button = 0;//0 不可领取
            } else {
                if($bnumber==0){
                    $button=1;//1 领取
                }else{
                    $button=0;//0 不可领取
                }

            }

            if($v['end_time']<time())
                $button = 0;

            //控制按钮
            $data[] = array(
                'number'=> $bnumber,
                'begin'=>date('Y-m-d H:i:s', $v['start_time']),
                'end'=>date('Y-m-d H:i:s', $v['end_time']),
                'coin'=>strtoupper(str_replace('赠送', '', $v['name'])),
                'button'=> $button,
                'type'=> $v['type'],
            );

        }

        $this->ajax('', 1, $data);
    }


    /*
    * 获取公共rsa秘钥
    */
    public function getCommonRsaKeyAction()
    {
        $path = CONF_PATH.'commonRsaPublic.key';
        is_file($path) and $data = file_get_contents($path);
        if(!$data)
        {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY']);
        }
        $this->ajax('', 1, $data);
    }

    /*
   * 存一个语言类型标志
   */
    public function setlangAction()
    {
        $uid=$this->mCurUser['uid'];
        //$uid=1253;
        $lang=trim($_POST['lang']);

        if(isset($uid)){
            $db3 = Yaf_Registry::get("config")->redis->user->db;
            UserModel::addRedis($db3, 'setlang', $uid, $lang);
        }
        $this->ajax('成功', 1);
    }

    /*
     *   账户信息
     *
     * */
    public function accountuserAction(){

        $this->_ajax_islogin();

        //用户信息
        $user = UserModel::getInstance()->fRow($this->mCurUser['uid']);
        $coin = CoinModel::getInstance()->where(['status'=>0])->field('name,display,out_status,in_status')->order('order_by asc')->fList();
        $mo = new Orm_Base();

        foreach ($coin as &$v){
            $v['over'] = sprintf("%.8f",$user[$v['name'].'_over']);
            $v['lock'] = sprintf("%.8f",$user[$v['name'].'_lock']);
            $v['sum'] = sprintf("%.8f",$user[$v['name'].'_over']+$user[$v['name'].'_lock']);

            if($v['name']=='cnyx'){
                $v['sum_cnyx'] = sprintf("%.8f",$v['sum']);
            }else{
                if($v['sum']){
                    $unit_price = $mo->table("order_{$v['name']}coin")->order('id desc')->fOne('price');
                    $v['sum_cnyx'] = sprintf("%.8f",$v['sum']*$unit_price);
                }else{
                    $v['sum_cnyx'] = sprintf("%.8f",0);
                }
            }
            $v['name'] = strtoupper($v['name']);
        }
        $data['total'] = round(array_sum(array_map(function($val){return $val['sum_cnyx'];}, $coin)));

        $data['list'] = $coin;

        $this->ajax('',1,$data);
    }



//    public function accountuserAction()
//    {
//        $this->_ajax_islogin();
//        $coinGroup = array();
//        $yibicoin = array();
//        //用户信息
//        $bibi = UserModel::getInstance()->fRow($this->mCurUser['uid']);
//        //易币数据
//        $user['fabi'] = $this->getyibiuser($this->mCurUser['email'],$this->mCurUser['area'], $this->mCurUser['mo'], '');//获取易币账户余额
//
//        $fabi =  $user['fabi']['data'];
//
//        if($user['fabi']['status']==0)
//        {
//            $coinGroup['fabi']['if_data']=0;
//        }
//        else
//        {
//            $coinGroup['fabi']['if_data']=1;
//        }
//        if ($user['fabi']['status'] == 1)
//        {
//            unset($fabi['mo']);
//            unset($fabi['ext_over']);
//            unset($fabi['ext_lock']);
//        }
//
//        if($_POST['coin'])
//        {
//            $bbover =  sprintf('%.8f',$bibi[$_POST['coin'].'_over'],'8');//币币余额
//
//            //幣交易规则
//            $coin= CoinModel::getInstance()->where(array( 'name' =>$_POST['coin']))->fRow();
//
//            $faover = sprintf('%.8f', $fabi[$_POST['coin'].'_over'],'8');  //法币余额
//
//            $user = array(
//                'uid'=>$this->mCurUser['uid'],
//                'coin'=>$coin['name'],
//                'priceoverbi'=> sprintf('%.8f', $bbover),
//                'priceoverfa'=>sprintf('%.8f', $faover),
//                'maxrange'=>$coin['otc_max'],
//                'max'=>$coin['otc_transfer_nfloat'],
//            );
//
//            $this->ajax('',1,$user);
//        }else
//        {
//            //币列表
//            $coinList = User_CoinModel::getInstance()->getList();
//            //幣交易规则
//            $coinPair = Coin_PairModel::getInstance()->field('coin_from')->where('status=1')->fList();
//            $coinStatus = array_column($coinPair, 'coin_from');
//            $yi_coinList = User_CoinModel::getInstance()->where('status=0 and otc=0')->fList();
//
//            foreach ($yi_coinList as $key => &$v)
//            {
//                if ($user['fabi']['status'] == 0) //没数据
//                {
//                    break;
//                }
//                $v['sum']= sprintf('%.8f', bcadd($fabi[$v['name'].'_over'],$fabi[$v['name'].'_lock'],'8'));
//                //上线的币
//                if (in_array($v['name'], $coinStatus) || in_array($v['name'], array('btc','dob')))
//                {
//                    //易币数据
//                    $yibicoin[] = $v['name'];
//
//                    if ($fabi[$v['name'] . '_over'] > 0 || $fabi[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc')
//                    {
//
//                         $v['sum']= sprintf('%.8f', bcadd($fabi[$v['name'].'_over'],$fabi[$v['name'].'_lock'],'8'));
//                         $coinGroup['fabi']['on']['owned'][] = $v;
//
//                    }
//                    else
//                    {
//                        $v['sum']= sprintf('%.8f',bcadd($fabi[$v['name'].'_over'],$fabi[$v['name'].'_lock'],'8'));
//                        $coinGroup['fabi']['on']['others'][] = $v;
//                    }
//                        $max = min($v['otc_max'], $fabi[$v['name'] . '_over']);
//                        $fabi[$v['name'] . '_max'] = sprintf('%.8f', $max);//最大可互转
//
//                }
//            }
//
//            array_push($coinStatus,'cnyx');
//            //币币数据
//            foreach ($coinList as $key => &$v)
//            {
//                //上线的币
//                if (in_array($v['name'], $coinStatus) || in_array($v['name'], array('btc', 'dob')))
//                {
//                    if ($bibi[$v['name'] . '_over'] > 0 || $bibi[$v['name'] . '_lock'] > 0 )
//                    {
//
//                        $v['sum']= sprintf('%.8f',bcadd($bibi[$v['name'].'_over'],$bibi[$v['name'].'_lock'],'8'));
//
//                        $coinGroup['bibi']['on']['owned'][] = $v;
//
//                    }
//
//                else
//                {
//                        $v['sum']=sprintf('%.8f', bcadd($bibi[$v['name'].'_over'],$bibi[$v['name'].'_lock'],'8'));
//                        $coinGroup['bibi']['on']['others'][] = $v;
//                }
//                        $max = min($v['otc_max'], $bibi[$v['name'] . '_over']);
//                        $bibi[$v['name'] . '_max'] = sprintf('%.8f', $max);//最大可互转
//
//                } //下架的币
//                else
//                {
//                       $v['sum']=sprintf('%.8f', bcadd($bibi[$v['name'].'_over'],$bibi[$v['name'].'_lock'],'8'));
//                       $coinGroup['bibi']['off'][] = $v;
//                }
//            }
//
//            $bibiReturn = [];
//            $fabiReturn = [];
//            foreach($bibi as $k=>$v)
//            {
//                if(strpos($k, '_lock')!==false||strpos($k, '_over')!==false)
//                {
//                    $bibiReturn[$k] = sprintf('%.8f',$v);
//                }
//            }
//
//            if($fabi)
//            {
//                foreach($fabi as $k1=>$v1)
//                {
//                    if(strpos($k1, '_lock')!==false||strpos($k1, '_over')!==false)
//                    {
//                        $fabiReturn[$k1] = sprintf('%.8f',$v1);
//                    }
//                }
//            }
//
//
//            //全部币都换算成btc
//            $newPrice = Coin_PairModel::getInstance()->getCoinPrice();
//
//            $tradearea = Coin_PairModel::getInstance()->field('DISTINCT coin_to')->fList();
//            foreach ($tradearea as &$v1)
//            {
//                $v1['coin_to'] = '_' . $v1['coin_to'];
//            }
//
//            $coinPrice = [];
//            //全部币都换算成btc
//            foreach ($newPrice as $coin => $area)
//            {
//                if ($coin == 'btc')
//                {
//                    foreach ($area as $k => $v)
//                    {
//                        $coinPrice[str_replace(array_column($tradearea, 'coin_to'), '', $k)] = array(
//                            preg_replace('/.+?_/', '', $k), Tool_Math::format($v['price']),
//                        );
//                    }
//                }
//                else
//                {
//                    if($newPrice['btc'][$coin . '_btc']['price'])
//                    {
//                        $method = 'mul';
//                        $transPirce = $newPrice['btc'][$coin . '_btc']['price'];
//                    }
//                    else
//                    {
//                        $method = 'div';
//                        $transPirce = $newPrice[$coin]['btc_'.$coin]['price'];
//                    }
//
//                    foreach ($area as $k => $v)
//                    {
//                        $cc = str_replace(array_column($tradearea, 'coin_to'), '', $k);
//                        $btcarr = array_keys($coinPrice);
//                        if (in_array($cc, $btcarr))//如果已经有跳过
//                        {
//                            continue;
//                        }
//                        $coinPrice[$cc] = array(
//                            'btc', Tool_Math::$method($v['price'], $transPirce),
//                        );
//                    }
//                }
//            }
//
//
//            //全部币都换算成btc
//            $coinGroup['user']  = $bibiReturn;
//            $coinGroup['userfabi'] = $fabiReturn;
//            $coinGroup['coinPrice'] = $coinPrice;
//            $this->ajax('',1,$coinGroup);
//        }
//
//    }



    //获取易币用户余额
    public function getyibiuser($email,$area, $mo, $coin)
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
        $dobi_used_key = "EIML%CcrtqVwXzrT4s8%F5YaDdZ1F^6A";//火網传给法币
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

    //实名状态接口
    public function userstatusAction()
    {
        $this->_ajax_islogin();
        $realInfo = AutonymModel::getInstance()->where(array('uid' => $this->mCurUser['uid']))->fRow();
        if($realInfo){
            $realInfo['idcardyi'] = substr($realInfo['idcard'], 0, 2) . '************' . substr($realInfo['idcard'], -3);
        }
        $this->ajax('获取成功',1,$realInfo);
    }

    /**
     *  绑定手机或邮箱第一步
     */
    public function verifyUserAction()
    {
        $this->_ajax_islogin();
        $postdata = Tool_Request::post();
        $user = $this->mCurUser;
        $userMo = new UserModel();
        $key = 'resetPwdsError' .$user['uid'];
        $code = trim($postdata['code']);
        if($user['email'] && $postdata['regtype']=='email')  //手机用户绑定邮箱
        {
            $data = [
                'email_mm' => $user['email'],
                'email_codemm' => $postdata['code'],

            ];
            $cene = 'verifyemail';
        }
        else if($user['mo'] && $postdata['regtype']=='phone')   // 邮箱用户绑定手机
        {
            $data = [
                'phone' => $user['mo'],
                'area' => $user['area'],

            ];
            $cene = 'dinbingmo';
        }

        $redis = Cache_Redis::instance();
        $count = $redis->get($key);
        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'vcode');//輸入錯誤次數過多，請稍後再試
        }

        $validatelogicModel = new ValidatelogicModel();   //实例化

        $result = $validatelogicModel->scene($cene)->check($data);  // 调用场景，check方法验证

        if (!$result)      //如果为空 ，则报错，并输出错误信息
        {

            foreach ($validatelogicModel->getError() as $k => $v)
            {
                $errorData = $v; // 错误信息
                $errorM = $k;   // 错误字段
            }
            if($errorM=='email_codemm')
            {
                $userMo->finderror($key);
            }
            $this->ajax($errorData, '0', $errorM);
        }



        if($postdata['regtype']=='phone')
        {

            if (!PhoneCodeModel::verifiCode($this->mCurUser, 7, $code, $user['area']))
            {
                //存错误次数到redis
                $userMo->finderror($key);
                $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR'], 0, 'code');
            }

        }
        $_SESSION['succeed'] ='step_one';
        $this->ajax('',1);
    }


    //绑定手机或邮箱
    public function moemailAction()
    {
        $this->_ajax_islogin();
        if(!isset($_SESSION['succeed'] ) || $_SESSION['succeed']!='step_one') //没有验证第一步
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'],0);
        }
        //接收数据
        $postdata = Tool_Request::post();
        $key = 'resetPwdsError' .$this->mCurUser['uid'];
        $user = new UserModel();
        //用户信息
        $tData = UserModel::getInstance()->fRow($this->mCurUser['uid']);

        if($postdata['regtype']=='email')  //绑定邮箱
        {
            $data = [
                'email'        => $postdata['email'],
                'email_code'   => $postdata['code']
            ];
            $check = 'emaildinbing';
        }
        else      //绑定手机
        {
            $area = $_POST['area'] ? trim($_POST['area']) : '+86';
            $data = [
                'mo'           =>$postdata['phone'],
                'area'         =>$area
            ];
            $check = 'modinbing';
        }
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);

        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'vcode');//輸入錯誤次數過多，請稍後再試
        }

        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene($check)->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result)
        {
            foreach ($validatelogicModel->getError() as $k => $v)
            {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            if($errorM=='email_code')
            {
                $user->finderror($key);
            }
            $this->ajax($errorData, '0', $errorM);
        }
        if($tData['google_key'])  //有绑定谷歌验证码
        {
            if (!Api_Google_Authenticator::verify_key($tData['google_key'], trim($postdata['googlecode'])))
            {
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0,'googlecode');
            }
        }
        $code = trim($postdata['code']);


        if($postdata['regtype']=='phone')
        {
            if (!PhoneCodeModel::verifiCode($data, 7, $code, $data['area']))
            {
                $user->finderror($key);
                $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR'], 0, 'code');
            }
        }

        $db2 = Yaf_Registry::get("config")->redis->default->db;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        if($postdata['regtype']=='phone'&&$tData['phone']=='')
        {
            if($tData['area']!='+86')
            {
                UserModel::addRedis($db2, 'userphone', $area.$data['mo'], $tData['uid']); //库2
            }
            else
            {
                UserModel::addRedis($db2, 'userphone', $data['mo'], $tData['uid']); //库2
            }
            $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
            UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
            Cache_Redis::instance()->hSet('usersession', $tData['uid'], 1);
            $list = [
                'uid'=>$this->mCurUser['uid'],
                'mo'=>$data['mo'],
                'area'=>$area
            ];
        }
        elseif($postdata['regtype']=='email'&&$tData['email']=='')
        {
            $value = $tData['pwd'] . ',' . $tData['uid'] . ',' . $tData['role'] . ',' . $tData['prand'];
            UserModel::addRedis($db2, 'useremail', $data['email'], $tData['uid']); //库2
            UserModel::addRedis($db3, 'uid', $tData['uid'], $value); ////库3
            Cache_Redis::instance()->hSet('usersession', $tData['uid'], 1);
            $list = [
                'uid'=>$this->mCurUser['uid'],
                'email'=>$data['email']
            ];
        }

        if(!$user->update($list))   //保存数据
        {
            $this->ajax($GLOBALS['MSG']['SMS_SHIBAN'],0);
        }
        unset($_SESSION['succeed']);
        $this->ajax($GLOBALS['MSG']['EMAIL_BANGDING'],1);
    }

    //邀请有礼  提币奖励限制
    public function limitAction()
    {

        $userInfo = UserModel::getInstance()->field('rebate')->where('uid='.$this->mCurUser['uid'])->fRow();
        $rebate = json_decode($userInfo['rebate'], true);
        //历史遗留，导致mcc差异化处理
        $rebateGroup = ['mcc_reg'=>['mcc_reg_in'=>$rebate['mcc_in'], 'mcc_reg_out'=>$rebate['mcc_out']]];

        $area = Coin_PairModel::getInstance()->field('coin_to')->group('coin_to')->fList();
        foreach ($area as $k=>$v)
        {
            $coinKey = $v['coin_to']=='mcc'?'mcc_rebate':$v['coin_to'];
            $rebateGroup[$v['coin_to']][$v['coin_to'].'_in'] = $rebate[$coinKey.'_in'];
            $rebateGroup[$v['coin_to']][$v['coin_to'].'_out'] = $rebate[$coinKey.'_out'];
        }

        //提币限额
        $rebateGroup['pair'] = array(
            'mcc'=>['min'=>50, 'numLimit'=>0],
            'btc'=>['min'=>0.01, 'numLimit'=>8],
            'eth'=>['min'=>0.01, 'numLimit'=>8],
        );

        $this->ajax('',1,$rebateGroup);
    }
    /*
    * 添加自选
    */
    public function setSelectedAction()
    {
        $this->_ajax_islogin();
        $selected = $_POST['coins'];
        $type = $_POST['type'];

        if(!$type || !in_array($type, ['add', 'del']) || !preg_match('/^[a-z_]+$/', $selected))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $userConfigMo =  UserConfigModel::getInstance();
        $oldData = $userConfigMo->where(['uid'=>$this->mCurUser['uid']])->fRow();
        if($oldData)
        {
            $oldConf = json_decode($oldData['config'], true);
            $oldSelected = $oldConf['selected'];
            if($type=='add')
            {
                $oldSelected[] = $selected;
            }
            elseif($type=='del')
            {
                if(($idx = array_search($selected, $oldSelected)) !== false)
                {
                    array_splice($oldSelected, $idx, 1);
                }
            }

            $oldConf['selected'] = $oldSelected?array_unique($oldSelected):[];

            if($oldConf)
            {
                $oldData['config'] = json_encode($oldConf);
            }

            $saveData = $oldData;
        }
        elseif($type=='add')
        {
            $saveData = array('uid'=>$this->mCurUser['uid'],'config'=>json_encode(array('selected'=>[$selected])));
        }

        if($saveData)
        {
            $r = $userConfigMo->save($saveData);
        }

        if(!$r)
        {
            $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);
        }

        $this->ajax('',1);

    }


    /*
    * 我的自选
    */
    public function selectedAction()
    {
        $this->_ajax_islogin();

        $userConfigMo =  UserConfigModel::getInstance();
        $data = $userConfigMo->where(['uid'=>$this->mCurUser['uid']])->fRow();
        $data and $data = json_decode($data['config'], true);
        $this->ajax('', 1, $data?implode(',', $data['selected']):'');
    }


    /*
    * 我的自选（分组）
    */
    public function selectedGroupAction()
    {
        $this->_ajax_islogin();

        $userConfigMo =  UserConfigModel::getInstance();
        $data = $userConfigMo->where(['uid'=>$this->mCurUser['uid']])->fRow();
        $data and $data = json_decode($data['config'], true);
        if($data)
        {
            $returnData = array();
            foreach($data['selected'] as $v)
            {
                list($coinFrom, $coinTo) = explode('_', $v);
                $returnData[$coinTo][] = $v;
            }
        }
        $this->ajax('', 1, $returnData);
    }


    /*
     *  重置登录密码、交易秘密
     */
    public function resetPwdsAction()
    {
        $this->_ajax_islogin();

        $tradePwd1 = $_POST['tradePwd1'];
        $tradePwd2 = $_POST['tradePwd2'];
        $loginPwd1 = $_POST['loginPwd1'];
        $loginPwd2 = $_POST['loginPwd2'];
        $smsCode   = $_POST['code'];

        $this->validCaptcha();

        if ($tradePwd1 != $tradePwd2 || $loginPwd1 != $loginPwd2)
        {
            $this->ajax($GLOBALS['MSG']['PWD_DIFF']);
        }

        if (strlen($tradePwd1) < 6 || strlen($tradePwd1) > 20 ||strlen($loginPwd1) < 6|| strlen($loginPwd2) > 20 )
        {
            $this->ajax($GLOBALS['MSG']['PWD_LEN']);
        }

        if (!Tool_Validate::pwd($tradePwd1)||!Tool_Validate::pwd($loginPwd1))
        {
            $this->ajax($GLOBALS['MSG']['NO_SPC_CHR']);
        }

        //这个接口每个用户只能用一次
        $redis = Cache_Redis::instance();
        $doneKey = 'RESET_PWDS_DONE';
        if($redis->hget($doneKey, $this->mCurUser['uid']))
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        //短信验证码校验
        $errorKey =  'resetPwdsError' .$this->mCurUser['uid'];
        if($redis->get($errorKey)>=5)
        {
            $this->ajax($GLOBALS['MSG']['ERROR_NUM_LIMIT']);
        }
        if($_POST['regtype']=='phone')
        {
            if (!PhoneCodeModel::verifiCode($this->mCurUser, 7, $smsCode, $this->mCurUser['area']))
            {
                UserModel::getInstance()->finderror($errorKey);
                $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR'], 0, 'code');
            }
        }
        elseif($_POST['regtype']=='email')
        {
            $validatelogicModel = new ValidatelogicModel();

            if (!$validatelogicModel->emailcodey($smsCode))
            {
                $this->ajax($GLOBALS['MSG']['EMAIL_NUMBER'], 0, 'code');
            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 0); //参数错误
        }


        $userMo   = UserModel::getInstance();
        $userInfo = $userMo->field('email,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);

        //效验设置交易是否跟登入一样
        $loginPwd = Tool_Md5::encodePwd($loginPwd1, $userInfo['prand']);
        //交易密码加密
        $tradePwd = Tool_Md5::encodePwdTrade($tradePwd1, $userInfo['prand']);
        //交易密码不能更登录密码一致
        if ($tradePwd1 == $loginPwd1)
        {
            $this->ajax($GLOBALS['MSG']['PWD_NOTBESAME']);
        }

        $r = $userMo->where(['uid'=>$this->mCurUser['uid']])->update(array(
            'pwd'=>$loginPwd,
            'pwdtrade'=>$tradePwd,
            'updated'=>time()
        ));

        if(!$r)
        {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY']);
        }

        //redis
        //完成标识
        $redis->hSet($doneKey, $this->mCurUser['uid'], time());
        //更新登录密码
        $redis->select(Yaf_Registry::get("config")->redis->user->db);
        $redis->hSet('uid', $this->mCurUser['uid'], implode(',', [$loginPwd, $this->mCurUser['uid'], 'user', $userInfo['prand']]));
        $redis->select(0);

        //log
        PwdModLogModel::getInstance()->batchInsert(array(
                array(
                    'uid'=>$this->mCurUser['uid'],
                    'type'=>1,
                    'createdip'=>Tool_Fnc::realip(),
                ),
                array(
                    'uid'=>$this->mCurUser['uid'],
                    'type'=>2,
                    'createdip'=>Tool_Fnc::realip(),
                ),
            )
        );
        //销毁验证码
        $this->delCaptcha();

        $this->ajax('', 1);

    }
    //mcc活动
    public function mccactivityAction()
    {
        $this->_ajax_islogin();
        $uid = $this->mCurUser['uid'];
        $automynmo = new AutonymModel();
        $mccmo = new Exchange_MccModel();
        $Ticketlogmo = new TicketlogModel();
        $actmo = new ActivityModel();
        $activity = $actmo->where("name='赠送mcc' and status=1 and bak='zs666' and admin=110")->fRow();
        $time = time();
        if (empty($activity)) {
            $this->ajax($GLOBALS['MSG']['HUODONG_BUCUN'], 0);//该活动不存在
        }
        if ($time < $activity['start_time']) {
            $this->ajax($GLOBALS['MSG']['HUODONG_BUCUN_NO'], 0);//活动未开始
        }
        if ($time > $activity['end_time']) {
            $this->ajax($GLOBALS['MSG']['HUODONG_NO'], 0);//活动已结束
        }
        $aulist = $automynmo->where(array('uid' => $uid))->fList();
        if (empty($aulist) || $aulist[0]['status'] != 2) {
            $this->ajax($GLOBALS['MSG']['PLEASE_AUTOMYN'], 0,['need_realinfo'=>1]);//請先完成實名認證
        }
        if (!($this->mCurUser['created'] > $activity['start_time'] && $this->mCurUser['created'] < $activity['end_time'] && $aulist[0]['updated'] > $activity['start_time'] && $aulist[0]['updated'] < $activity['end_time'])) {
            $this->ajax($GLOBALS['MSG']['ONLY_NEW_USER'], 0);//活動期間內的新用戶才可以參與
        }
        $ex_list = $mccmo->where(array('uid' => $uid, 'bak' => '领取mcc', 'opt_type' => 'in', 'status' => '成功'))->fList();
        $tict_list = $Ticketlogmo->where(array('uid' => $uid, 'type' => 1, 'gift' => 'Plus2'))->fList();
        if ($ex_list || $tict_list) {
            $this->ajax($GLOBALS['MSG']['GET_ONE'], 0);//已領取過
        }
        $plustotal=$Ticketlogmo->field("count(uid) total")->where("type=1")->fList();
        $data = array();
        for ($i = 1; $i < 10001; $i++) {
            if ($i <= 6000) {
                $gift = 3;
            } elseif ($i > 6000 && $i <= 8000) {
                $gift = 5;
            } elseif ($i > 8000 && $i <= 9000) {
                $gift = 20;
            } elseif ($i > 9000 && $i <= 9990) {
                $gift = 30;
            } elseif ($i > 9990 && $i <= 9995) {
                $gift = 50;
            } elseif ($i > 9995 && $i <= 10000) {
                if($plustotal[0]['total']>3){
                    $gift = 50;
                }else{
                    $gift = 'Plus2';
                }
            }
            $data[$i] = $gift;
        }
        shuffle($data);
        $key = array_rand($data);
        $get_gift = $data[$key];
        if (!is_numeric($get_gift) && $get_gift == 'Plus2') {//送Plus2
            $insert_data = array(
                'uid' => $uid,
                'created' => time(),
                'type' => 1,
                'gift' => 'Plus2'
            );
            if (!$Ticketlogmo->insert($insert_data)) {
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0);//系统繁忙
            } else {
                $this->ajax($GLOBALS['MSG']['GET_PLUS2'], 1,'plus2');//恭喜您領取到了Plus2手环，請到公告查看如何聯繫我們領取獎品。
            }
        } else {//送币
            if ($this->mccadd($uid, $get_gift)) {
                $this->ajax(sprintf($GLOBALS['MSG']['RECEIPT_COIN_NOTICE'], ' '.$get_gift . ' mcc'), 1,'mcc');
            } else {
                $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0);//系统繁忙
            }
        }

    }

//mcc 活动 加余额逻辑
    public function mccadd($uid, $number)
    {
        $coin = 'mcc';
        $ip = Tool_Fnc::realip();
        $getCoin = $number;
        $now = time();
        $moName = 'Exchange_' . ucfirst($coin) . 'Model';
        $exchangeMo = $moName::getInstance();
        $userMo = UserModel::getInstance();
        //赠送配置
        $activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1,'admin'=>110])->fRow();

        $exData = array(
            'uid' => $uid,
            'admin' => 6,
            'email' => '',
            'wallet' => '',
            'opt_type' => 'in',
            'number' => $getCoin,
            'created' => $now,
            'updated' => $now,
            'is_out' => 1,
            'createip' => $ip,
            'bak' => '领取' . $coin,
            'status' => '成功',
            'txid' => '',
        );
        $userMo->begin();
        $data = $userMo->where(array('uid' => $uid))->lock()->fList();
        if (empty($data)) {//用戶不存在
            $userMo->back();
            return false;
        }
        if (!$exchangeMo->save($exData)) {
            $userMo->back();
            Tool_Log::wlog(sprintf("mcc活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/mcc', true);
            return false;
        }

        //更新用户余额
        $r = $userMo->exec(sprintf('update user set ' . $coin . '_lock = ' . $coin . '_lock+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $uid));

        if (!$r) {
            $userMo->back();
            Tool_Log::wlog(sprintf("mcc活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/mcc', true);
            return false;
        }
        //更新来源用户余额
        $r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $uid));
        if (!$r) {
            $userMo->back();
            Tool_Log::wlog(sprintf("mcc活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/mcc', true);
            return false;
        }

        //领取记录
        $urData = array(
            'uid' => $uid,
            'aid' => $activity['id'],
            'coin' => $coin,
            'created' => $now,
            'updated' => $now,
            'number' => $getCoin,
            'type' => 0
        );

        $r = UserRewardModel::getInstance()->save($urData);
        if (!$r) {
            $userMo->back();
            Tool_Log::wlog(sprintf("mcc活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/mcc', true);
            return false;

        }
        if (!$userMo->commit()) {
            $userMo->back();
            Tool_Log::wlog(sprintf("mcc活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/mcc', true);
            return false;
        }
        Tool_Session::mark($uid);
        return true;
    }



    /*
     *  谷歌绑定解除
     */
    public function  googleaddAction()
    {
        $this->_ajax_islogin();

        $pdata = Tool_Request::post();
        $user = new UserModel();
        $tUserMo = $this->mCurUser['uid'];
        $redis = Cache_Redis::instance();
        $errorKey =  'resetPwdsError' .$this->mCurUser['uid'];
        if($redis->get($errorKey)>=5)
        {
            $this->ajax($GLOBALS['MSG']['ERROR_NUM_LIMIT']);
        }
        if($pdata['regtype']=='pinless') //绑定
        {
            $data =
            [
                'secret'          =>   $pdata['secret'],
                'google_code'     =>   $pdata['code']
            ];

        $validatelogicModel = new ValidatelogicModel();
        // 调用login场景，check方法验证
        $result = $validatelogicModel->scene('google')->check($data);

        //如果不为空 ，则报错，并输出错误信息
        if (!$result)
        {
            foreach ($validatelogicModel->getError() as $k => $v)
            {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            $this->ajax($errorData, '0', $errorM);
        }
         if(trim($pdata['pwd']))
         {
             $db3 = Yaf_Registry::get("config")->redis->user->db;
             $phoneMo = UserModel::lookRedis($db3,'uid', $tUserMo);
             $array=explode(',',$phoneMo);

             $pwd=$array[0];
             if(Tool_Md5::encodePwd($pdata['pwd'], $array[3])!=$pwd)
             {
                 UserModel::getInstance()->finderror($errorKey);
                 $this->ajax($GLOBALS['MSG']['TEL_CCW'],0);
             }
         }
            if (!Api_Google_Authenticator::verify_key($pdata['secret'], $pdata['code']))
            {
                UserModel::getInstance()->finderror($errorKey);
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0);
            }
            else
            {

                $list = [
                    'uid'           =>     $tUserMo,
                    'google_key'    =>     $data['secret']
                ];
                if(!$user->update($list))
                {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE_BD'],0);
                }
                Tool_Session::mark($tUserMo);
                $this->ajax($GLOBALS['MSG']['EMAIL_BANGDING'],1);
            }
        }
        elseif ($pdata['regtype']=='remove')  //解除
        {
            $key = UserModel::getInstance()->field('google_key')->fRow($this->mCurUser['uid']);
            $data =
                [
                    'secret'          =>  $key['google_key'],
                    'google_code'     =>   $pdata['code']
                ];

            $validatelogicModel = new ValidatelogicModel();
            // 调用login场景，check方法验证
            $result = $validatelogicModel->scene('google')->check($data);

            //如果不为空 ，则报错，并输出错误信息
            if (!$result)
            {
                foreach ($validatelogicModel->getError() as $k => $v)
                {
                    // 错误信息
                    $errorData = $v;
                    // 错误字段
                    $errorM = $k;
                }
                $this->ajax($errorData, '0', $errorM);
            }

            if(trim($pdata['pwd']))
            {
                $db3 = Yaf_Registry::get("config")->redis->user->db;
                $phoneMo = UserModel::lookRedis($db3,'uid', $tUserMo);
                $array=explode(',',$phoneMo);

                $pwd=$array[0];
                if(Tool_Md5::encodePwd($pdata['pwd'], $array[3])!=$pwd)
                {
                    UserModel::getInstance()->finderror($errorKey);
                    $this->ajax($GLOBALS['MSG']['TEL_CCW'],0);
                }
            }

            if (!Api_Google_Authenticator::verify_key($data['secret'], $data['google_code']))
            {
                UserModel::getInstance()->finderror($errorKey);
                $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE'],0,'code');
            }
            else
            {
                $key = "";   //解除绑定删除key
                $list = [
                    'uid'           =>   $tUserMo,
                    'google_key'    =>    $key
                ];
                if(!$user->update($list))
                {
                    $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE_JC'],0);
                }
            }
            Tool_Session::mark($tUserMo);
            $this->ajax($GLOBALS['MSG']['GOOGLE_ERROE_CG'],1);

        }

    }

    //RGB系列手动提交转入
    public function rgbCoinInAction(){
        $this->_ajax_islogin();
        $wallet = coll('post','wallet');
        $number = coll('post','number');
        $coin = coll('post','coin');
        $pwdtrade = coll('post','tradePwd');


        if(!$wallet || !$number || !$coin || !$pwdtrade) $this->ajax('参数错误');
        $exchange = "Exchange_".ucfirst($coin)."Model";
        $exchangeModle = new $exchange;

        $cointType = CoinModel::getInstance()->where(['name'=>$coin])->fOne('type');
        if($cointType=='rgb'){
            if($in_log = $exchangeModle->where(['uid'=>$this->mCurUser['uid'],'status'=>'待审核'])->fRow()) $this->ajax($GLOBALS['MSG']['UNFULFILLED_ORDER']);
        }


        //错误次数限制
        $ekey = 'TRADE_PWD_ERROR'.$this->mCurUser['uid'];
        $errorNum = $this->checkErrorNum($ekey);

        if (empty($pwdtrade) || Tool_Md5::encodePwdTrade($pwdtrade, $this->mCurUser['prand']) != $this->mCurUser['pwdtrade'])
        {
            $this->setErrorNum($ekey, $errorNum);
            $this->ajax($GLOBALS['MSG']['TRADE_PWD_ERROR']);
        }
        Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'add');

        if(!$wallet || !$number || !$coin) return false;

        // 判断是否是内转is_out=1，还是外转is_out=0
//        $coinInfo = User_CoinModel::getInstance()->where(array('name' => $coin))->fRow();
        $addressMo = new AddressModel();
        $toUid = $addressMo->where(array('address'=>"$wallet",'coin'=> $coin,'status'=>0))->fOne('uid');

        if($toUid) {
            // 内转
            $is_out = 1;
        } else {
            // 外转
            $toUid = 0;
            $is_out = 0;
        }

        $tradeno = self::get_tradeno($coin,10);

        $exOutData = array(
            'uid'=>$this->mCurUser['uid'],
            'wallet'=>$wallet,
            'opt_type'=>'in',
            'number'=>$number,
            'number_real'=>$number,
            'created'=>$_SERVER['REQUEST_TIME'],
            'status'=>'待审核',
            'is_out'  => $is_out,
            'to_uid' => $toUid,
            'platform_fee'=>0,
            'createip'=>Tool_Fnc::realip(),
            'bak'=>$tradeno
        );

        if(!$tId = $exchangeModle->insert($exOutData)){
            Tool_Log::wlog(sprintf('sql error:%s, sql:%s', $exchangeModle->getError(2), $exchangeModle->getLastSql()), '写入数据失败', true);
            $this->ajax($GLOBALS['MSG']['D_FAIL']);
        }
        $exOutData['time']=date("Y-m-d H:i:s",$exOutData['created']);
        $exOutData['id'] = $tId;
        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1,$exOutData);
    }


    //RGB系列撤销订单
    public function rgbCancelAction()
    {
        $this->_ajax_islogin();

        $coin = trim(coll('post','coin'));
        $id = trim(coll('post','id'));

        if(!$coin || !$id) $this->ajax("参数错误");

        $coinType = CoinModel::getInstance()->where(['name'=>$coin])->fOne('type');

        if($coinType!='rgb') $this->ajax("该币种转入不能撤销");
        $exchange = 'Exchange_'.ucfirst($coin).'Model';
        $exMo = $exchange::getInstance();

        if(!$ex = $exMo->where(['id'=>$id,'uid'=>$this->mCurUser['uid'],'opt_type'=>'in'])->fRow()){
            $this->ajax("未查询到该笔订单");
        }
        if($ex['status']!='待审核') $this->ajax("该订单不可撤销");

        if($up_id = $exMo->update(['id'=>$id,'status'=>'已取消'])){
            $this->ajax("撤销成功",1);
        }else{
            $this->ajax("撤销失败",0);
        }
    }

    //生成订单编号
    static function get_tradeno($coin,$leng){
        $tradeno='';
        $coin = strtolower($coin);
        for ($a = 0; $a<$leng; $a++) {
            $tradeno .= chr(mt_rand(65,90));    //生成php随机数(rand(65, 90))
        }
        $mo = Orm_Base::getInstance();
        if($no = $mo->table("exchange_{$coin}")->where("bak='{$tradeno}'")->fOne('id')) $tradeno = self::get_tradeno($coin,$leng);
        return $tradeno;
    }

    //持币分红
    public function bonusAction(){
        $this->_ajax_islogin();
        $user = $this->mCurUser;
        $mo = Orm_Base::getInstance();
        $bonus = $mo->table("coin_bonus")->where("status=0")->fList();

        $user_bonus = $mo->query("select * from user_bonus where id in(select max(id) from user_bonus where uid={$this->mCurUser['uid']} group by coin)");
        $numbers = array_column($user_bonus,'number','coin');
        $on_numbers = array_column($user_bonus,'on_number','coin');
        $createds = array_column($user_bonus,'created','coin');

        $day_time = strtotime(date('Y-m-d'));
        foreach ($bonus as &$v){
            $v['coin_over'] = $user[$v['name'].'_over'];
            $v['number'] = $createds[$v['name']]>=$day_time?$numbers[$v['name']]:0;
            $v['on_number'] = isset($on_numbers[$v['name']])?$on_numbers[$v['name']]:0;

        }
        if($bonus){
            $this->ajax('1',1,$bonus);
        }else{ $this->ajax('1',0,$bonus);}
    }
    //领取持币分红
    public function getBonusAction(){
//        file_put_contents(APPLICATION_PATH.'/shell/202.log',json_encode($_REQUEST['coin']));
        $this->_ajax_islogin();
        $coin = trim($_POST['coin']);

        // $uid = $this->mCurUser['uid'];
        // $type =$_POST['type'];
        // $coin = trim($_POST['coin']);
        // $number =$_POST['number'];
        // $price =$_POST['price'];
        // $pwdTrade =$_POST['pwdTrade'];

        // var_dump('1dd',$uid,$coin,$number,$price,$pwdTrade);die;



        
        $mo = Orm_Base::getInstance();
        if(!$coinBonus = $mo->table('coin_bonus')->where("name='{$coin}'")->fRow()) $this->ajax('該幣種暫時沒有分紅');
        if($coinBonus['start_date']>date('Y-m-d')) $this->ajax('該幣種分紅還未開始');
        if($coinBonus['end_date']<date('Y-m-d')) $this->ajax('該幣種分紅已結束');
        if($this->mCurUser[$coin.'_over']<$coinBonus['min_nuumber']) $this->ajax('您的可用額度不足，無法領取');
        if(floatval($this->mCurUser[$coin.'_over'])<=0) $this->ajax('您的可用額度不足，無法領取');

//        $day_time = strtotime(date('Y-m-d'));
        //查看最近一次領取
        $lastbonus = $mo->table('user_bonus')->where("uid={$this->mCurUser['uid']} and coin='{$coin}'")->order('created desc,id desc')->fRow();
//        if(isset($lastbonus) && $lastbonus['created']>=$day_time) $this->ajax("您已經領取過了");
        $date_day = date('H點i分',$lastbonus['created']);
        if($lastbonus && time()-$lastbonus['created']<60*60*24) $this->ajax("領取間隔時間必須大於24小時,您可以明天{$date_day}后再來領取");

        $lastNumber = isset($lastbonus['on_number'])?$lastbonus['on_number']:0;
        $number = Tool_Math::percent($this->mCurUser[$coin.'_over'],$coinBonus['rate']);

        $mo->begin();
        $in_data = [
            'uid'=>$this->mCurUser['uid'],
            'coin'=>$coin,
            'to_coinover'=>floatval($this->mCurUser[$coin.'_over']),
            'to_rate'=>$coinBonus['rate'],
            'number'=>$number,
            'on_number'=>floatval(Tool_Math::add($lastNumber,$number)),
            'created'=>time()
        ];

        try{
            $mo->table = 'user_bonus';
            $in_id = $mo->insert($in_data);
            $up_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$number} where uid={$this->mCurUser['uid']}");
            $pUser = UserModel::getInstance()->lock()->fRow($this->mCurUser['uid']);
            if($in_id && $up_id && $pUser){
                $mo->commit();
                $pushData = ["{$coin}_lock"=>(string)floatval($pUser["{$coin}_lock"]),"{$coin}_over"=>(string)floatval($pUser["{$coin}_over"])];
                //PUSH
                $_SESSION['user'] = $pUser;
                Tool_Push::one2nSend("{$coin}_cnyx", array('t'=>'balance', 'c'=>$pushData),[$this->mCurUser['uid']]);
                $this->ajax('領取成功',1,$number);
            }else{
                $mo->back();
                $this->ajax('領取失败');
            }
        }catch (Exception $e){
            $mo->back();
            $this->ajax('領取失败');
        }
    }


    public function validateTradepwdAction(){
        $tradepwd = coll("post","tradepwd");
        if(empty($tradepwd)){
            $this->ajax("交易密码不能为空",0);
        }
        $mo = Orm_Base::getInstance();
        $userInfo = $mo->table("user")->where("uid = {$this->mCurUser['uid']}")->fRow();
        if ($userInfo['pwdtrade'] != $pwdTrade = Tool_Md5::encodePwdTrade($tradepwd,$userInfo['prand'])) $this->ajax('交易密码错误');
        $this->ajax("验证交易密码成功",1);
    }

    //otc添加钱包
    public function otcUserWalletAction(){
        $this->_ajax_islogin();
        $type = coll('post','type');
        if(empty($type)){
            return $this->ajax("参数错误");
        }
        $postData = coll('post');
        $data = $this->Valiaddress($type,$postData);
        $mo = Orm_Base::getInstance();
        $id = coll("post","id");
        if(!empty($id)){
            $walletInfo = $mo->table("otc_address")->where(" id={$id} ")->fRow();
            if(empty($walletInfo)){
                $this->ajax("数据不存在");
            }
            $data['updated'] = time();
            $data['update_time'] = date("Y-m-d H:i:s", time());
            $mo->table = "otc_address";
            $result = $mo->where(['id'=>$id])->update($data);
            if($result){
                $this->ajax('編輯成功!',1);
            }else{
                $this->ajax('編輯失敗!',0);
            }
        }else{
            $data['status'] = 1;
            $data['created'] = time();
            $data['create_time'] = date("Y-m-d H:i:s", time());
            $data['updated'] = 0;
            $data['update_time'] = date("Y-m-d H:i:s", time());
            $mo->table = "otc_address";
            $result = $mo->insert($data);

            if($result){
                $this->ajax('添加成功!',1,["id"=>$result]);
            }else{
                $this->ajax('添加失敗!',0);
            }
        }
    }

    /**
     * 根据类型拼装添加数据
     * @param $type
     * @param $postData
     * @return array|void
     */
    public function Valiaddress($type,$postData){
        $data = array(
            'uid' => $this->mCurUser['uid'],
            'type' => $type,
            'coin' => strtolower($postData['coin']),
        );
        switch($type){
            case 1:
                if(empty($postData['coin']) || empty($postData['address'])){
                    return $this->ajax("参数错误");
                }
                $frontFace = "";
                if(!empty($postData['img'])) {
                    $path = "upload/Otc/";
                    $tMO = new Tool_Oss();
                    $frontFace = $tMO->uploadOne($path . date('Ymd') . '/' . uniqid() . md5($this->mCurUser['uid']) . '.' . 'jpg', $postData['img']);
                }
                $data['address'] = $postData['address'];
                $data['img'] = $frontFace;
                break;
            case 2:
            case 3:
            case 4:
                if(empty($postData['numbers']) || empty($postData['address']) || empty($postData['coin'])){
                    return $this->ajax("参数错误");
                }
                $data['numbers'] = $postData['numbers'];
                $data['address'] = $postData['address'];
                break;
            default:
                return $this->ajax("参数错误");
                break;
        }
        return $data;
    }

    //更新钱包
    public function modifyWalletAction(){
        $this->_ajax_islogin();
        $wallet_id = coll("post","wallet_id");
        $coin = coll("post","coin");
        $status = coll("post",'status');
        $mo = Orm_Base::getInstance();
        $walletInfo = $mo->table("otc_address")->where(['coin'=>$coin,'id'=>$wallet_id])->fRow();
        if(empty($walletInfo)){
            $this->ajax("钱包不存在");
        }
        $result = $mo->table("otc_address")->where(['id'=>$wallet_id,"coin"=>$coin])->update(['status'=>$status]);
        if($result){
            $this->ajax("成功",1);
        }
    }

    //OTC获取用户钱包信息
    public function getOtcUserWalletListAction(){
        $this->_ajax_islogin();
        $coin = coll("post","coin");
        $type = coll("post","type");

        $result = [];
        $mo = Orm_Base::getInstance();
        $result['walletList'] = $mo->table("otc_address")->where("coin='{$coin}' and uid={$this->mCurUser['uid']} ")->fList();
        $result['coinList']= CoinModel::getInstance()->where(['otc_open'=>1])->field('name,type,display')->fList();
        if($result['walletList']){$this->ajax('',1,$result);}
        else{$this->ajax('',0,$result);}
    }

    /*
     * 邀请分红
     */
    public function inviteBonusAction(){

        $this->_ajax_islogin();

        $size = 10;
        $page = coll('post','page');
        $page = (($page?$page:1)-1)*$size;
        $uid = $this->mCurUser['uid'];

        $sql = "select count(u.uid) count from user u left join autonym a on a.uid=u.uid where u.from_uid={$uid}";
        $count = UserModel::getInstance()->query($sql);
        $data['total'] = $count[0]['count'];

        $sql = "select a.status,u.uid,u.mo,u.email,u.created from user u left join autonym a on a.uid=u.uid where u.from_uid={$uid} order by created desc limit {$page},{$size}";
        $data['list'] = UserModel::getInstance()->query($sql);

        $mo = Orm_Base::getInstance();

//        $this->ajax(1,0,$data);

        //交易手续费
        $tradeFees = $mo->table("fee_bonus")->field("sum(fee) total_fee,origin_uid")->where("uid=$uid")->group("origin_uid")->fList();
        $tradeFees = array_column($tradeFees,'total_fee','origin_uid');

        //平台充值手续费
        $platformFees = $mo->table("fee_bonus_recharge")->field("sum(fee) total_fee,origin_uid")->where("uid=$uid and type='platform'")->group("origin_uid")->fList();
        $platformFees = array_column($platformFees,'total_fee','origin_uid');

        //C2C充值手续费
        $c2cFees = $mo->table("fee_bonus_recharge")->field("sum(fee) total_fee,origin_uid")->where("uid=$uid and type='c2c'")->group("origin_uid")->fList();
        $c2cFees = array_column($c2cFees,'total_fee','origin_uid');

        foreach ($data['list'] as &$v){
            $v['tradeFee'] = Tool_Math::format($tradeFees[$v['uid']],8,1,true);
            $v['platfromFee'] = Tool_Math::format($platformFees[$v['uid']],8,1,true);
            $v['c2cFee'] = Tool_Math::format($c2cFees[$v['uid']],8,1,true);
            $v['totalFee'] = $tradeFees[$v['uid']]+$platformFees[$v['uid']]+$c2cFees[$v['uid']];
            $v['created'] = date('Y-m-d',$v['created']);
        }
        $data['pages'] = ceil($data['total']/$size);

        $this->ajax('',1,$data);
    }


    /*
    * 邀请分红详情
    */
    public function inviteBonusDetailAction(){
        $this->_ajax_islogin();

        $size = 10;
        $page = coll('post','page');
        $page = $page?$page:1;

        $uid = $this->mCurUser['uid'];
        $origin_uid = coll('post','origin_uid');
        $type = coll('post','type');

        if(!$origin_uid || !$type) $this->ajax("参数错误");

        //交易手续费分红
        $mo = Orm_Base::getInstance();

        if($type==1){
            $total = $mo->table("fee_bonus")->where("uid=$uid and origin_uid={$origin_uid}")->order("created desc")->fOne("count(id)");
            $data['list'] = $mo->table("fee_bonus")->field("FROM_UNIXTIME(created) created,price,number,opt,coin_from,oid,fee")->where("uid=$uid and origin_uid={$origin_uid}")->page($page,$size)->order("created desc")->fList();
        }elseif($type==2){
            $total = $mo->table("fee_bonus_recharge")->where("uid=$uid and origin_uid={$origin_uid} and type='platform'")->order("created desc")->fOne("count(id)");
            $data['list'] = $mo->table("fee_bonus_recharge")->field("FROM_UNIXTIME(created) created,number,fee")->where("uid=$uid and origin_uid={$origin_uid} and type='platform'")->page($page,$size)->order("created desc")->fList();
        }elseif($type==3){
            $total = $mo->table("fee_bonus_recharge")->where("uid=$uid and origin_uid={$origin_uid} and type='c2c'")->order("created desc")->fOne("count(id)");
            $data['list'] = $mo->table("fee_bonus_recharge")->field("FROM_UNIXTIME(created) created,number,fee")->where("uid=$uid and origin_uid={$origin_uid} and type='c2c'")->page($page,$size)->order("created desc")->fList();
        }else{
            $this->ajax("参数错误");
        }

        $data['total'] = $total;
        $data['pages'] = ceil($data['total']/$size);

        $this->ajax('',1,$data);
    }
}
