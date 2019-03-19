<?php
/**
 * Created by PhpStorm.
 * User: longbijia
 * Date: 2017/9/13
 * Time: 17:31
 */
class ValidatelogicModel extends Tool_Validate{
     //检验规则
     protected $rule = [
         // 邮箱注册
         'email'      =>   'require|regexEmail|checkEmail|max:32',
         //邮箱登入
         'email_bb'      =>   'require|regexEmail|forEmail',
         // 邮箱密码登入
         'password_bb'   =>   'require|max:30|min:6|emailPwd|checkPwdError',
         // 登录手机
         'phone'      =>   'require|number|regexMo|checkPhone',
         // 注册手机
         'mo'         =>   'require|number|regexMo|checkMo',
         //区号
         'area'       => 'require|checkArea',
         //找回密码
         'getpwd_phone' => 'require|number|regexMo',
         // 密码
         'password'   =>   'require|max:30|min:6|regexPwd',
         // 确认密码
         'repassword' =>   'require|confirm:password',
         // 验证码
         'captcha'     =>   'require|max:4|min:4|checkCaptcha',//
 //        'number' => 'require|max:9|min:1',
         // 登录密码
         'Upassword'   =>   'require|max:30|min:6|checkPwd',//checkPwdError|
         // 短信验证码
         'code'  =>   'require|max:6|min:6|number|checkCodey',
         //邮箱验证码
         'email_code'=> 'require|max:6|min:6|number|emailcodey',
         'email_codemm'=> 'require|max:6|min:6|number|emailcode',
         'smsCaptch'  =>   'require|max:6|min:6|number|checkCode',


         // 前台
         // 用户名称
         'name'       =>   'require|min:2|max:100|chsDashName',
         // 证件
         'idcard'     =>   'require|max:20|min:1|alphaNum|regexIdcard',
         'cardtype'   =>   'require|in:1,2,3',

         'frontFace'  =>   'image:jpg,png,gif,jpeg',
         'backFace'  =>    'image:jpg,png,gif,jpeg',
         'handkeep'  =>    'image:jpg,png,gif,jpeg',
          //手机图片
         'baseyi'  =>     'require',
         'baseer'  =>     'require',
         'basesan'  =>    'require',

         //交易密码
         'email_mm'=> 'require',
         //绑定谷歌解绑谷歌
         'secret'         =>'alphaNum',
         'google_code'    =>'require|max:6|min:6|number',
         // 评论
         'nid'      =>  'require|number',
         'content'  =>  'require|maxstrlen|checkHtml',
         'themessage'  =>  'max:200|checkHtml|pushlen',
         //工单系统
         'id_txid' => 'require|alphaNum',
         'questioncontent' => 'require|max:3000|chsDashComment',
         //找回密码
         'email_app_find' => 'require|regexEmail|forEmail',
         'emailcode_app_find' => 'require|max:6|min:6|number|checkemailCode',

         'emailcode_register' => 'require|max:6|min:6|number|checkemailCode',
         // 登录手机
         'findpwd_mo' => 'require|number|regexMo|checkPhone',

         'findpwd_email' => 'require|regexEmail|forEmail',
         'binding_emailcode' => 'require|max:6|min:6|number|checkemailCode',
         // 昵称
         'nickname' => 'require|max:18|chsAlphaNum|byusing',
     ];




     // 检验提示信息
     protected	$message;

     // 检验场景
     protected	$scene	=	[
         // 登录
         'userlogin'   =>  ['phone','captcha',/*'code',*/'Upassword','area'],

         //邮箱登入
         'loginemail'  =>['email_bb','captcha','password_bb'],
         //邮箱找回登入密码
         'email'       =>['email_bb','captcha'],
         // 找回密码，发送短信时，检验手机号和图形验证码
         'phone' => ['getpwd_phone','captcha','area'],
         //找回密码验证邮箱验证码
         'emailcode'=>['email_code'],
         //找回交易密码
         'emailcodemm'=>['email_codemm','email_mm'],
         // 发送短信时，检验手机号
         'back' => ['phone','area'],
         //绑定邮箱第一步
         'verifyemail'=>['email_mm','email_codemm'],
         //绑定邮箱
         'emaildinbing'=>['email','email_code'],

         //绑定手机第一步
         'dinbingmo' =>['phone','area'],
         //绑定手机
         'modinbing' =>['mo'],
         // 发送短信时，检验手机号和短信
         'authenticate' => ['phone','code','area'],
         //重设密码
         'resetPassword' => ['password','repassword'],
         //邮箱注册
         'email_register' => ['email', 'password', 'repassword'],
         // 注册
         'register'   =>  ['mo','password', 'repassword','captcha','smsCaptch','area'],
         'mo' => ['mo', 'captcha','area'],

         // 实名
         'shiMing'          => ['name','idcard','cardtype','frontFace','handkeep','backFace'],
         'telphone'         => ['name','idcard','cardtype','baseyi','baseer','basesan'],
         'sphone'           => ['name','idcard','cardtype'],

         // 评论
         'newsComment'      => ['nid','content'],
         'pushComment'      => ['themessage'],

         //验证码绑定||解除绑定
         'google'      => ['secret','google_code'],

         //工单系统
         'question' => ['id_txid','questioncontent'],
         'question1' => [ 'questioncontent'],
         'question2' => ['questioncontent'],
         //互转图形验证码
         'userinturn_captcha'=>['captcha'],

         //app
         'app_register' => ['mo', 'smsCaptch', 'area'],
         'app_register_pwd' => ['password', 'repassword'],
         'app_sendregmsg_register' => ['mo', 'area'],
         'app_sendregmsg_findpwd' => ['findpwd_mo', 'area'],
         'app_email_register' => ['email'],
         'app_email_findpwd' => ['findpwd_email'],
         'app_registeremail' => ['email', 'emailcode_register'],
         //找回密码
         'app_findemail_pwd' => ['email_app_find', 'emailcode_app_find'],
         //app找回密码
         'app_findphone_pwd' => ['phone', 'code', 'area'],
         //app邮箱登入
         'loginemail_app' => ['email_bb', 'password_bb'],
         // app登录
         'userlogin_app' => ['phone', 'Upassword', 'area'],
         //绑定手机
         'binding_mo' => ['mo', 'area', 'code'],
         //绑定邮箱
         'binding_email' => ['email', 'binding_emailcode'],
         //实名第一步
         'app_autonym_one' => ['name', 'idcard', 'cardtype'],
         //实名第二步
         'app_autonym_two' => [ 'baseyi', 'baseer', 'basesan'],
         //用户反馈
         'feedback' => ['themessage'],
         // 昵称
         'nickname' => ['nickname'],
     ];

    function __construct()
    {
        $this->message = [
            // 邮箱注册
            'email.require'      => $GLOBALS['MSG']['EMAIL_BI'],
            'email.regexEmail'   => $GLOBALS['MSG']['EMAIL_FORMAT'],
            'email.checkEmail'   => $GLOBALS['MSG']['EMAIL_TO_USE'],
            'email.max'          => $GLOBALS['MSG']['EMAIL_TO_USE'],

            'email_mm.require'   =>$GLOBALS['MSG']['EMAIL_BI'],
            //邮箱登入
            'email_bb.require'   => $GLOBALS['MSG']['EMAIL_BI'],
            'email_bb.regexEmail'     => $GLOBALS['MSG']['EMAIL_FORMAT'],
            'email_bb.forEmail'  => $GLOBALS['MSG']['EMAIL_FOREMAIL'],


            // 登录密码
            'password_bb.require'   => $GLOBALS['MSG']['TEL_PASSWORD'],
            'password_bb.max'       => $GLOBALS['MSG']['TEL_CHAOGUO'],
            'password_bb.min'       => $GLOBALS['MSG']['TEL_SHAOYU'],
            'password_bb.emailPwd'  => $GLOBALS['MSG']['TEL_CCW'],
            'password_bb.checkPwdError'  => $GLOBALS['MSG']['ERROR_NUM_LIMIT'],
            //手機驗證base
            'baseyi.require'  =>     $GLOBALS['MSG']['BASEYI'],
            'baseer.require'  =>     $GLOBALS['MSG']['BASEER'],
            'basesan.require'  =>    $GLOBALS['MSG']['BESESAN'],
            // 登录手机
          //  'phone.require'      => $GLOBAL['MSG']['TEL_DUO'],
            'phone.require'      => $GLOBALS['MSG']['YOUXIANG'],
            'phone.max'          =>$GLOBALS['MSG']['TEL_DUO'] ,
            'phone.min'          =>$GLOBALS['MSG']['TEL_SHAO'],
            'phone.regexMo'      => $GLOBALS['MSG']['TEL_GESHI'],
            'phone.number'       => $GLOBALS['MSG']['TEL_GESHI'],
            'phone.checkPhone'   =>$GLOBALS['MSG']['TEL_BUCUNZAI'],
            //找回密码
            'getpwd_phone.require' => $GLOBALS['MSG']['YOUXIANG'],
            'getpwd_phone.regexMo' => $GLOBALS['MSG']['TEL_GESHI'],
            'getpwd_phone.number' => $GLOBALS['MSG']['TEL_GESHI'],


            // 手机
            'mo.require' => $GLOBALS['MSG']['YOUXIANG'],
            'mo.max'     => $GLOBALS['MSG']['TEL_DUO'],
            'mo.min'     => $GLOBALS['MSG']['TEL_SHAO'],
            'mo.regexMo' => $GLOBALS['MSG']['TEL_GESHI'],
            'mo.number'  =>$GLOBALS['MSG']['TEL_GESHI'],
            'mo.checkMo' => $GLOBALS['MSG']['TEL_SHIYONG'],
            'area.require'=>'区号必须',
            'area.checkArea' => '区号错误',
            // 密码
            'password.require'   =>$GLOBALS['MSG']['TEL_PASSWORD'],
            'password.max'       => $GLOBALS['MSG']['TEL_CHAOGUO'],
            'password.min'       =>$GLOBALS['MSG']['TEL_SHAOYU'] ,
            'password.regexPwd'  =>$GLOBALS['MSG']['TEL_CUOWU'],

            // 登录密码
            'Upassword.require'   => $GLOBALS['MSG']['TEL_PASSWORD'],
            'Upassword.max'       => $GLOBALS['MSG']['TEL_CHAOGUO'],
            'Upassword.min'       => $GLOBALS['MSG']['TEL_SHAOYU'],
            'Upassword.checkPwdError'       => $GLOBALS['MSG']['ERROR_NUM_LIMIT'],
            'Upassword.checkPwd'  => $GLOBALS['MSG']['TEL_CCW'],

            //重复密码
            'repassword.require' => $GLOBALS['MSG']['TEL_CHONGFU'],
            'repassword.confirm' =>$GLOBALS['MSG']['TEL_BUYIZHI'] ,

            // 验证码
            'captcha.require'      => $GLOBALS['MSG']['TEL_YZM'],
            'captcha.max'          => $GLOBALS['MSG']['TEL_ZQ'],
            'captcha.min'          => $GLOBALS['MSG']['TEL_ZQ'],
            'captcha.checkCaptcha' =>$GLOBALS['MSG']['TEL_YZM_CW'],

            // 验证码
            'code.require'        => $GLOBALS['MSG']['TEL_YZM_DX'],
            'code.max'            => $GLOBALS['MSG']['TEL_YZM_DXL'],
            'code.min'            =>$GLOBALS['MSG']['TEL_YZM_DXL'],
            'code.number'         => $GLOBALS['MSG']['TEL_NUMBER'],
            'code.checkCodey'      => $GLOBALS['MSG']['TEL_NUMBER'],

            //邮箱验证码
            'email_code.require'        => $GLOBALS['MSG']['EMAIL_REQUIRE'],
            'email_code.max'            => $GLOBALS['MSG']['EMAIL_MAX'],
            'email_code.min'            => $GLOBALS['MSG']['EMAIL_MAX'],
            'email_code.number'         => $GLOBALS['MSG']['EMAIL_NUMBER'],
            'email_code.emailcodey'      => $GLOBALS['MSG']['EMAIL_NUMBER'],

            //邮箱验证码
            'email_codemm.require'        => $GLOBALS['MSG']['EMAIL_REQUIRE'],
            'email_codemm.max'            => $GLOBALS['MSG']['EMAIL_MAX'],
            'email_codemm.min'            =>$GLOBALS['MSG']['EMAIL_MAX'],
            'email_codemm.number'         => $GLOBALS['MSG']['EMAIL_NUMBER'],
            'email_codemm.emailcode'      => $GLOBALS['MSG']['EMAIL_NUMBER'],

            // 验证码
            'smsCaptch.require'        =>  $GLOBALS['MSG']['TEL_YZM_DX'],
            'smsCaptch.max'            => $GLOBALS['MSG']['TEL_YZM_DXL'],
            'smsCaptch.min'            =>$GLOBALS['MSG']['TEL_YZM_DXL'],
            'smsCaptch.number'         => $GLOBALS['MSG']['TEL_NUMBER'],
            'smsCaptch.checkCode'      => $GLOBALS['MSG']['TEL_NUMBER'],

            'name.require'                   => $GLOBALS['MSG']['TEL_NAME'],
            'name.max'                       => $GLOBALS['MSG']['TEL_NAME_YQ'],
            'name.min'                       => $GLOBALS['MSG']['TEL_NAME_YQ'],
            'name.chsDashName'               => $GLOBALS['MSG']['TEL_NAME_YQ'],

            'idcard.require'                 => $GLOBALS['MSG']['TEL_ZJ'],
            'idcard.max'                     => $GLOBALS['MSG']['TEL_ZJ_GESHI'],
            'idcard.min'                     => $GLOBALS['MSG']['TEL_ZJ_GESHI'],
            'idcard.alphaNum'                => $GLOBALS['MSG']['TEL_ZJ_GESHI'],
            'idcard.regexIdcard'             => $GLOBALS['MSG']['TEL_ZJ_GESHI'],

            'cardtype.require'               =>$GLOBALS['MSG']['TEL_ZJ_CS'],
            'cardtype.in'                    => $GLOBALS['MSG']['TEL_ZJ_CS'],

            'frontFace.image'                => $GLOBALS['MSG']['TEL_CS'],
            'backFace.image'                 => $GLOBALS['MSG']['TEL_CS'],
            'handkeep.image'                 => $GLOBALS['MSG']['TEL_CS'],

            // 评论
            'content.require'             => $GLOBALS['MSG']['TEL_CONTENT'],
            'content.maxstrlen'                 =>  $GLOBALS['MSG']['CONTENT_DUO'],
            'content.checkHtml'      =>   $GLOBALS['MSG']['CONTENT_DU'],

            'nid.require'                 => $GLOBALS['MSG']['TEL_ZJ_CS'],
            'nid.number'                  =>  $GLOBALS['MSG']['TEL_ZJ_CS'],


            'themessage.max'          =>   $GLOBALS['MSG']['CONTENT_DU'],
            'themessage.checkHtml'    =>   $GLOBALS['MSG']['CONTENT_DU'],
            'themessage.pushlen'    =>   $GLOBALS['MSG']['CONTENT_DU'],

            //绑定谷歌
            'secret.alphaNum'          =>'只能是数字和字母',
            'google_code.require'          =>'验证码必须',
            'google_code.max'          =>'验证码必须6位',
            'google_code.min'          =>'验证码必须6位',
            'google_code.number'          =>'验证码必须是数字',

            //工单系统
            'questioncontent.require' => '描述不能为空',
            'questioncontent.max' => '最多只能3000个字符',
            'questioncontent.chsDashComment' => '只允许汉字、字母、数字和下划线_及破折号',

            'id_txid.require' => 'id或txid不能为空',
            'id_txid.alphaNum' => 'id或txid只允许字母或数字',
            //app
            //邮箱
            'email_app_find.require' => $GLOBALS['MSG']['EMAIL_BI'],
            'email_app_find.regexEmail' => $GLOBALS['MSG']['EMAIL_FORMAT'],
            'email_app_find.forEmail' => $GLOBALS['MSG']['EMAIL_FOREMAIL'],
            // 验证码
            'emailcode_app_find.require' => $GLOBALS['MSG']['EMAIL_REQUIRE'],
            'emailcode_app_find.max' => $GLOBALS['MSG']['EMAIL_MAX'],
            'emailcode_app_find.min' => $GLOBALS['MSG']['EMAIL_MAX'],
            'emailcode_app_find.number' => $GLOBALS['MSG']['EMAIL_NUMBER'],
            'emailcode_app_find.checkemailCode' => $GLOBALS['MSG']['EMAIL_NUMBER'],
            // 验证码
            'emailcode_register.require' => $GLOBALS['MSG']['EMAIL_REQUIRE'],
            'emailcode_register.max' => $GLOBALS['MSG']['EMAIL_MAX'],
            'emailcode_register.min' => $GLOBALS['MSG']['EMAIL_MAX'],
            'emailcode_register.number' => $GLOBALS['MSG']['EMAIL_NUMBER'],
            'emailcode_register.checkemailCode' => $GLOBALS['MSG']['EMAIL_NUMBER'],
            //找回密码
            'findpwd_mo.require' => $GLOBALS['MSG']['YOUXIANG'],
            'findpwd_mo.max' => $GLOBALS['MSG']['TEL_DUO'],
            'findpwd_mo.min' => $GLOBALS['MSG']['TEL_SHAO'],
            'findpwd_mo.regexMo' => $GLOBALS['MSG']['TEL_GESHI'],
            'findpwd_mo.number' => $GLOBALS['MSG']['TEL_GESHI'],
            'findpwd_mo.checkPhone' => $GLOBALS['MSG']['TEL_BUCUNZAI'],
            //邮箱找回密码
            'findpwd_email.require' => $GLOBALS['MSG']['EMAIL_BI'],
            'findpwd_email.regexEmail' => $GLOBALS['MSG']['EMAIL_FORMAT'],
            'findpwd_email.forEmail' => $GLOBALS['MSG']['EMAIL_FOREMAIL'],

            // 邮箱验证码
            'binding_emailcode.require' => $GLOBALS['MSG']['EMAIL_REQUIRE'],
            'binding_emailcode.max' => $GLOBALS['MSG']['EMAIL_MAX'],
            'binding_emailcode.min' => $GLOBALS['MSG']['EMAIL_MAX'],
            'binding_emailcode.number' => $GLOBALS['MSG']['EMAIL_NUMBER'],
            'binding_emailcode.checkemailCode' => $GLOBALS['MSG']['EMAIL_NUMBER'],
            //昵称
            'nickname.require' => $GLOBALS['MSG']['NICKNAME_MUST'],
            'nickname.max' => $GLOBALS['MSG']['NICKNAME_MAX'],
            'nickname.chsAlphaNum' => $GLOBALS['MSG']['NICKNAME_CHS'],
            'nickname.byusing' => $GLOBALS['MSG']['NICKNAME_BYU'],

        ];
    }

    //檢查昵稱是否存在
    public function byusing($values)
    {
        $Otcusermo = new Otcorder_UserModel();
        if ($Otcusermo->field("nickname")->where("nickname='" . $values . "'")->fRow()) {
            return false;
        }
        return true;
    }
    //校验区号
    protected function checkArea($values)
    {
       $mo=new PhoneAreaCodeModel();
        $va=$mo->where("area_code='$values'")->fList();
        if(empty($va)){
            return false;
        }
            return true;
    }
// 检验评论内容字符长度140
    protected function maxstrlen($values)
    {
        $ccontent = html_entity_decode($values);

        $cc = str_replace("&#39;", "'", $ccontent);
        $length=mb_strlen($cc, 'utf-8');
        if ($length>140) {
            return false;
        } else {
            return true;
        }
    }
    //发送消息
    protected function pushlen($values)
    {
        $ccontent = html_entity_decode($values);

        $cc = str_replace("&#39;", "'", $ccontent);
        $length=mb_strlen($cc, 'utf-8');
        if ($length>200) {
            return false;
        } else {
            return true;
        }
    }
    // 检验是否有html标签
    protected function checkHtml($values)
    {
        if ($values != strip_tags($values)) {
            return false;
        } else {
            return true;
        }
    }

    // 验证邮箱格式
    public function regexEmail($values)
    {

        //+允许中文
        $dd=preg_match('/^[A-Za-z0-9\x{4e00}-\x{9fa5}-_\.]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/u',$values);
       // $dd=preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/',$values);

        if($dd==1)
        {
            return true;
        }
        return false;
    }
    //邮箱登入验证是否注册
    public function forEmail($values)
    {
        $userMo = new UserModel();
        $db2 = Yaf_Registry::get("config")->redis->default->db;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        $data = UserModel::lookRedis($db2, 'useremail', $values);

        if(!$data)
        {
            $userphone = $userMo->where(['email' => $values])->fRow();
            if(!empty($userphone)){//如果数据库有，redis没有，加缓存

                $value = $userphone['pwd'] . ',' . $userphone['uid'] . ',' . $userphone['role'] . ',' . $userphone['prand'];
                UserModel::addRedis($db2, 'useremail', $userphone['email'], $userphone['uid']); //库2
                UserModel::addRedis($db3, 'uid', $userphone['uid'], $value); ////库3

                Tool_Session::mark($userphone['uid']);
                return true;
            }
            return false;
        }

        if (!$data) {
            $checkData = $userMo->field('uid')->where(array('email' => $values))->fRow();
            if ($checkData) {
                return false;
            }
            return false;
        }
        return true;
    }
    // 邮箱回调方法
     protected function checkEmail($values)
     {
         $userMo =  new UserModel();
         $data = $userMo->where("email = '{$values}'")->fRow();
         if($data)
         {
             return false;
         }
         return true;
     }

     // 正则检验手机号
     protected  function regexMo($values)
     {
         $quhao=$this->data['area']? $this->data['area']:'+86';
         if($quhao=='+86') {
             // if (!preg_match("/13[0-9]{9}|15[0-9]{9}|19[0-9]{9}|17[0-9]{9}|145[0-9]{8}|18[0-9]{9}|147[0-9]{8}/", $values)) {
               if (!preg_match("/^\d{11}$/", $values)) {
                 return false;
             }
             return true;
         }else{
             if(strlen($values)>11){
                 return false;
             }
             return true;
         }
     }

     // 检验手机号 是否已经注册
     protected  function checkMo($values)
     {
         $userMo =  new UserModel();
         $db2=Yaf_Registry::get("config")->redis->default->db;
         if($this->data['area']!='+86'){//如果不是国内手机
             $values= $this->data['area']. $values;
         }
         $data = UserModel::lookRedis($db2,'userphone', $values);
         if(!$data)
         {
             $checkData = UserModel::getInstance()->field('uid')->where(array('mo' => $values,'area'=>"{$this->data['area']}"))->fRow();
             if ($checkData)
             {
                 return false;
             }
             else
             {
                 return true;
             }
         }
         return false;


     }


     /*
     * 错误次数限制
     */
     protected function checkPwdError($values)
     {
        $redis = Cache_Redis::instance();
         if($this->data['email_bb'])
         {
             $errorKey = 'LoginPasswordError_'.$this->data['email_bb'];
         }
         else
         {
             $errorKey = 'LoginPasswordError_'.$this->data['area']. $this->data['phone'];
         }
        $errorNum = $redis->get($errorKey);
        if($errorNum>=5)
        {
            return false;
        }
        return true;
     }


     // 检验密码
     protected  function checkPwd($values)
     {
//         print_r(json_encode($this->data['area']));die;
         //验证密码
        // print_r(UserModel::lookRedis(2,'userphone', $this->data['phone']));die;

         $db2=Yaf_Registry::get("config")->redis->default->db;
         $db3 = Yaf_Registry::get("config")->redis->user->db;
         if($this->data['area']=='+86'){
             $tUserMo = UserModel::lookRedis($db2, 'userphone', $this->data['phone']);
         }else{
             $pp= $this->data['area']. $this->data['phone'];
             $tUserMo = UserModel::lookRedis($db2, 'userphone', $pp);
         }

         if(!$tUserMo)
         {
             return false;
         }
         $phoneMo = UserModel::lookRedis($db3,'uid', $tUserMo);
         $array=explode(',',$phoneMo);

         $pwd=$array[0];

//         $user = UserModel::getInstance()->field('pwd,prand')->where(['mo'=>$this->data['phone']])->fRow();
//         if(Tool_Md5::encodePwd($values, $user['prand'])!=$user['pwd'])

         if(Tool_Md5::encodePwd($values, $array[3])!=$pwd)
         {
            $redis = Cache_Redis::instance();
            $errorKey = 'LoginPasswordError_'.$this->data['area']. $this->data['phone'];
            $redis->incr($errorKey);
            $redis->expire($errorKey,7200);
            return false;
         }
         return true;
     }

    //邮箱检验密码
    protected  function emailPwd($values)
    {
        $db2=Yaf_Registry::get("config")->redis->default->db;
        $db3 = Yaf_Registry::get("config")->redis->user->db;
        $tUserMo = UserModel::lookRedis($db2,'useremail', $this->data['email_bb']);
        $phoneMo = UserModel::lookRedis($db3,'uid', $tUserMo);
        if(!$tUserMo)
        {
            return false;
        }
        $array=explode(',',$phoneMo);

        $pwd=$array[0];
        if(Tool_Md5::encodePwd($values, $array[3])!=$pwd)
        {
            $redis = Cache_Redis::instance();
            $errorKey = 'LoginPasswordError_'.$this->data['email_bb'];
            $redis->incr($errorKey);
            $redis->expire($errorKey,7200);
            return false;
        }
        return true;
    }
     // 检验密码格式
     protected function regexPwd($values)
     {
        // $regex = '/(?!^[0-9]+$)(?!^[A-z]+$)(?!^[^A-z0-9]+$)^.{6,25}$/';
         $regex = '/^[a-z\d~!@#$^&*()%_+-=|:;,.<>\/?]+$/i';
         if(!preg_match($regex, $values))
         {
             return false;
         }
         return true;
     }

     // 检验登录手机号是否存在
     protected  function checkPhone($values)
     {
         $db2=Yaf_Registry::get("config")->redis->default->db;
         //查询库2账号是否存在
         if($this->data['area']!='+86'){
             $mo= $this->data['area']. $values;
         }else{
             $mo= $values;
         }

         if(!(UserModel::lookRedis($db2,'userphone', $mo)))
         {
             $userMo = new UserModel();
             $userphone = $userMo->where(['mo' => $values,'area'=>"{$this->data['area']}"])->fRow();
             if(!empty($userphone)){//如果数据库有，redis没有，加缓存
                 $value = $userphone['pwd'] . ',' . $userphone['uid'] . ',' . $userphone['role'] . ',' . $userphone['prand'];
                 $db3 = Yaf_Registry::get("config")->redis->user->db;
                 if($userphone['area']!='+86'){
                     UserModel::addRedis($db2, 'userphone', $userphone['area'].$userphone['mo'], $userphone['uid']); //库2
                 }else{
                     UserModel::addRedis($db2, 'userphone', $userphone['mo'], $userphone['uid']); //库2
                 }
                 UserModel::addRedis($db3, 'uid', $userphone['uid'], $value); ////库3
                 Tool_Session::mark($userphone['uid']);
                 return true;
             }
             return false;
         }
         return true;
     }

     // 检验验证码
     protected function checkCaptcha($values)
     {
         $tCaptcha = Tool_Captcha::getInstance();
         $r = $tCaptcha->check($values);
         return $r;
     }
    //邮箱验证
    protected function checkemailCode($values)
    {
        $email = $this->data['email'] ? $this->data['email'] : $this->data['email_app_find'];
        if (!(PhoneCodeModel::checkemailCode($email, 1, $values)) && !(PhoneCodeModel::checkemailCode($email, 7, $values)) && !(PhoneCodeModel::checkemailCode($email, 8, $values)) && !(PhoneCodeModel::checkemailCode($email, 11, $values))) {
            return false;
        }
        return true;
    }
     protected function checkCode($values){

         if (!(PhoneCodeModel::verifiregCode($this->data['mo'], 1, $values, $this->data['area'])) && !(PhoneCodeModel::verifiregCode($this->data['mo'], 8, $values, $this->data['area'])))
         {
             return false;
         }
         return true;
     }
    //验证邮箱验证码
    public  function emailcodey($values)
    {
        $pc = new PhoneCodeModel();
        if(!$this->data['email'])  //绑定邮箱的
        {
            $email=$_SESSION['email'];
        }else{    //找回交易密码或找回登入密码的
            $email = $this->data['email'];
        }
        if($code = $pc->fRow("select * from {$pc->table} where email='{$email}' and code={$values} and action=2 and status=0 order by id desc")){
            if($code['ctime'] + 300 < time()){
                return false;
            }
            if($pc->exec("update {$pc->table} set status=1,utime={$_SERVER['REQUEST_TIME']} where id={$code['id']}")){
                return true;
            }
        }

        return false;
    }
    public function emailcode($values)  //找回交易密码
    {
        $pc = new PhoneCodeModel();

        $email = $this->data['email_mm'];
    //   show($this->mCurUser['email']);
        if($code = $pc->fRow("select * from {$pc->table} where email='{$email}' and code={$values} and action=2 and status=0 order by id desc")){
            if($code['ctime'] + 300 < time()){
                return false;
            }
            if($pc->exec("update {$pc->table} set status=1,utime={$_SERVER['REQUEST_TIME']} where id={$code['id']}")){
                return true;
            }
        }

        return false;

    }

     protected function checkCodey($values){
         $phone= $this->data['phone']? $this->data['phone']: $this->data['mo'];
         if (!(PhoneCodeModel::verifiregCode($phone, 1, $values,$this->data['area'])) && !(PhoneCodeModel::verifiregCode($phone, 8, $values, $this->data['area']))&& !(PhoneCodeModel::verifiregCode($phone, 7, $values, $this->data['area'])) && !(PhoneCodeModel::verifiregCode($phone, 11, $values, $this->data['area'])))
         {
             return false;
         }
         return true;
     }


    // 护照检验
    protected function regexIdcard($values)
    {
        // 护照
        $id15="/^[a-z\d\-\.]{1,30}$/i";
        if(preg_match($id15, $values))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }

    }

    // 身份证(只验证18位)
    public function Idcard($values)
    {
        $id18='/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/';
        if(preg_match($id18, $values))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }

    }


    // 检验idcard是否存在
    public function checkCard($values,$uid){
        $auMo = new AutonymModel();
        // 查询这个身份证号，如果不存在，则true;如果存在，要判断是否是自己的，是的话，true,不是false
        $data = $auMo->where(array('idcard'=>$values))->fOne('id');
        if(!$data)
        {
            return true;
        }
        else
        {
            $data1 = $auMo->where("uid = ".intval($uid))->fOne('id');
            if($data1==$data)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
}
