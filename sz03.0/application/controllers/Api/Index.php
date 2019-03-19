<?php
/**
 * 首页
 */
class Api_IndexController extends Api_BaseController
{
	function init()
	{
        $this->selectLang();
		$this->setViewPath(PATH_TPL);
	}

    public function indexAction()
    {
    	
    }


    public function userAction()
    {
    	$this->_session();
    	if(!$this->mCurUser)
    	{
    		header('location:/');
    		die;
    	}

    	$openapi = OpenapiModel::getInstance()->where(['uid'=>$this->mCurUser['uid']])->fRow();
    	//没有就生成
    	if(!$openapi)
    	{
    		$openapi = array(
    				'uid'=>$this->mCurUser['uid'], 
    				'updated'=>time(), 
    				'access_key'=>md5($this->mCurUser['uid'] . uniqid(microtime(), true)),
    				'secret_key'=>md5(uniqid(microtime(), true).rand(1,100000)),
    		);
    		$r = OpenapiModel::getInstance()->insert($openapi);
    		if($r)
    		{
    			$cache = array('uid'=>$this->mCurUser['uid'], 'secretKey'=>$openapi['secret_key']);
    			Cache_Redis::instance('user')->hSet('OPENAPI_ACCESSKEY', $openapi['access_key'], json_encode($cache));
    		}
    	}
    	$userInfo = $this->mCurUser?:'';
    	if ($userInfo['mo']) {
            if ($userInfo['area'] == '+86') {
                $userInfo['mo'] = preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $userInfo['mo']);
            } else {
                $userInfo['mo'] = substr_replace($_SESSION['user']['mo'], '**', -4, 2);
            }
        }
    	$this->assign('userInfo', $userInfo);
    	$this->assign('openapi', $openapi);
    }


    /**
     * 短信接口
     */
    public function smsAction()
    {
        $this->_ajax_islogin();

        $user   = $this->mCurUser;

        //优先发送手机验证码
        if($user['mo'] && $user['registertype']==2)
        {
            $action = 7;
            //限制发送频率
            $start = time() - 3600;
            $count = PhoneCodeModel::getInstance()->where("mo = {$user['mo']} and ctime >= {$start} and area='{$user['area']}' and action = {$action}")->count();
            if ($count >= 5)
            {
                //短信过于频繁
                $this->ajax($GLOBALS['MSG']['SMS_FAILED'], 0, 'code'); 
            }

            if (PhoneCodeModel::regverifiTime($user['mo'], $action,$user['area']))
            {
                $code = PhoneCodeModel::sendCode($user, $action, $user['area']);
                if ($code == '200')
                {
                    $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1); //发送成功
                }
                else
                {
                    $this->ajax($GLOBALS['MSG']['SMS_FAILED'], 0, 'code'); //发送频率过快，请稍后发送
                }
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'], 0, 'code'); //请您过60秒再点击发送
            }
        }
        elseif($user['email'])
        {

            $rnd      = rand(1000,time());          //随机验证码
            $rand     = substr($rnd, 0, 6);
            $userMo =  new UserModel();


            $msg = "{$GLOBALS['MSG']['EMAIL_DB_YZM']}{$rand} (https://api.huocoin.com)";//申请找回登录密码
            $email = $user['email'];
            $tltle =$GLOBALS['MSG']['EMAIL_YZM'];

            $PhoneCodeMo = new PhoneCodeModel();
            if($code = $PhoneCodeMo->fRow("select * from {$PhoneCodeMo->table} where email='{$email}' and action=2  order by id desc"))
            {
                if($code['ctime']+60 > time())
                {
                    $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'],0,'email');// 請您過60秒再點擊發送
                }

            }

            $result =  UserModel::saveEmailRedis(array(
                'find' => 1,
                'tltle'=>$tltle,
                'msg'  =>$msg,
                'email' =>$email,
            ));

            if($result!=1)  //邮件发送失败
            {
              $this->ajax($GLOBALS['MSG']['EMAIL_SB'], 0);
            }

            $tData = array(
                'email' => $email,
                'code' => $rand,
                'message' => $msg,
                'ctime' => time(),
                'action' => 2,   //2是邮箱
                'status' => 0,

            );

            if(!$PhoneCodeMo->insert($tData))
            {
                $this->ajax($GLOBALS['MSG']['EMAIL_SB'].'[2]', 0);
            }

            $this->ajax($GLOBALS['MSG']['EMAIL_SUCCESSSB'], 1);
        
        }

        $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);
    }




    /**
     * secretkey
     */
    public function secretkeyAction()
    {
        $this->_ajax_islogin();
        $user   = $this->mCurUser;
        $code   = intval($_POST['code']);
        $action = 7;
     	

        //错误次数
        $ekey = 'GET_SECRETKEY_ERROR'.$this->mCurUser['uid'];
        $errorNum = $this->checkErrorNum($ekey);

        //优先验证短信验证吗
        if($user['mo'] && $user['registertype']==2)
        {
            if (!PhoneCodeModel::verifiCode($user, $action, $code, $user['area']))
            {   
                $this->setErrorNum($ekey, $errorNum);
                $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR'], 0, 'code');
            }
        }
        else//邮箱
        {
            $validatelogicModel = new ValidatelogicModel();
            if (!PhoneCodeModel::checkemailCode($user['email'], 2, $code))
            {
                $this->setErrorNum($ekey, $errorNum);
                $this->ajax($GLOBALS['MSG']['EMAIL_NUMBER'], 0, 'code');
            }
        }


        $secretkey = md5(uniqid(microtime(), true));
        $openapi = OpenapiModel::getInstance()->where(['uid'=>$this->mCurUser['uid'], 'status'=>1])->fRow();
        if($openapi)
        {
        	//重置secretkey
        	$result = OpenapiModel::getInstance()->where(['uid'=>$this->mCurUser['uid'], 'status'=>1])->update(array(
            	'secret_key'=>$secretkey,
                'updated'=>time(), 
	        ));
	        if(!$result)
	        {
	        	$this->ajax($GLOBALS['MSG']['SYS_BUSY']);
	        }
	        $cache = Cache_Redis::instance('user')->hGet('OPENAPI_ACCESSKEY', $openapi['access_key']);
	        $cache = json_decode($cache, true);
	        $cache['secretKey'] = $secretkey;
	        Cache_Redis::instance('user')->hSet('OPENAPI_ACCESSKEY', $openapi['access_key'], json_encode($cache)); 
	        $this->mCurUser['codeError'] = 0;
	        $this->ajax('', 1, $secretkey);
        }
        
        $this->ajax($GLOBALS['MSG']['REQUEST_ERROR']);

    }


    /*
    * 错误次数限制
    */
    protected function checkErrorNum($key, $limit=5)
    {
        $errorNum = (int)Cache_Redis::instance()->get($key);
        if($errorNum>=$limit)
        {
            $this->ajax($GLOBALS['MSG']['ERROR_NUM_LIMIT']);
        }
        return $errorNum;
    }


    /**
     * ip白名單修改
     */
    public function ipModAction()
    {
        $this->_ajax_islogin();
        $user   = $this->mCurUser;
        $code   = intval($_POST['code']);
        $iplist = $_POST['iplist'];
        $action = 7;
     	
     	if(!$iplist || !preg_match('/^[\d\.\,]+$/', $iplist))
     	{
     		$this->ajax('ip '.$GLOBALS['MSG']['ERROR_FORMAT']);
     	}

        //错误次数
        $ekey = 'IP_MOD_ERROR'.$this->mCurUser['uid'];
        $errorNum = $this->checkErrorNum($ekey);

        //优先验证短信验证吗
        if($user['mo'] && $user['registertype']==2)
        {
            if (!PhoneCodeModel::verifiCode($user, $action, $code, $user['area']))
            {   
                $this->setErrorNum($ekey, $errorNum);
                $this->ajax($GLOBALS['MSG']['PHONE_CODE_ERROR'], 0, 'code');
            }
        }
        else//邮箱
        {
            if (!PhoneCodeModel::checkemailCode($user['email'], 2, $code))
            {
                $this->setErrorNum($ekey, $errorNum);
                $this->ajax($GLOBALS['MSG']['EMAIL_NUMBER'], 0, 'code');
            }
        }

        $openapi = OpenapiModel::getInstance()->where(['uid'=>$this->mCurUser['uid'], 'status'=>1])->fRow();
        if($openapi)
        {
        	//過濾不合格的ip
        	$iplist = explode(',', $iplist);
        	foreach ($iplist as $k=>&$v) 
        	{
        		$v = trim($v);
        		if(!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $v))
        		{
        			unset($iplist[$k]);
        		}
        	}
        	unset($v);

        	if($iplist)
        	{
        		//刪除了一些ip
        		$oldValue = OpenapiModel::getInstance()->where(['uid'=>$this->mCurUser['uid'], 'status'=>1])->fRow();
        		if($oldValue && $oldValue['ip'])
        		{
        			$oldValue = explode(',', $oldValue['ip']);
        			foreach($oldValue as $v)
        			{
        				if(!in_array($v, $iplist))
        				{
        					//如果有多個用戶共用此ip，刪掉當前用戶標誌
        					$ipUser = Cache_Redis::instance()->hGet('API_PERMIT_IP', $v);
        					if($ipUser && strpos($ipUser, ','))
        					{
        						$ipUser = explode(',', $ipUser);
        						foreach ($ipUser as $ik=>$ipUserOne) 
        						{
        							if(strpos($ipUserOne, $this->mCurUser['uid']) !== false)
        							{
        								unset($ipUser[$ik]);
        								break;
        							}
        						}
        						Cache_Redis::instance()->hSet('API_PERMIT_IP', $v, implode(',', $ipUser));
        					}
        					else//如果只有一個用戶使用此ip，刪掉緩存
        					{
        						$cache = Cache_Redis::instance()->hDel('API_PERMIT_IP', $v);
        					}
        				}
        			}
        		}

        		//更新數據庫
        		$result = OpenapiModel::getInstance()->where(['uid'=>$this->mCurUser['uid'], 'status'=>1])->update(array(
        			'ip'=>implode(',', $iplist),
                    'updated'=>time()
		        ));

		        if(!$result)
		        {
		        	$this->ajax($GLOBALS['MSG']['SYS_BUSY']);
		        }

		        //緩存到redis
		        foreach($iplist as $v)
		        {
		        	$r = Cache_Redis::instance()->hsetnx('API_PERMIT_IP', $v, 'uid:'.$this->mCurUser['uid']);
		        	if(!$r)
		        	{	//有其它用戶使用此ip，續加用戶標誌
		        		$otherUser = Cache_Redis::instance()->hGet('API_PERMIT_IP', $v);
		        		$otherUser = explode(',', $otherUser);
		        		$otherUser[] = 'uid:' . $this->mCurUser['uid'];
		        		$otherUser = array_unique($otherUser);
		        		Cache_Redis::instance()->hset('API_PERMIT_IP', $v, implode(',', $otherUser));
		        	}
		        }
        	}
        	
	        $this->mCurUser['codeError'] = 0;
	        $this->ajax('', 1);
        }
        
        $this->ajax($GLOBALS['MSG']['REQUEST_ERROR']);

    }

    /**
     * ip白名單修改
     */
    public function userinfoAction()
    {
        $this->_ajax_islogin();
        $userInfo = $this->mCurUser?:'';
        $return = array();
        if ($userInfo && $userInfo['mo']) {
            if ($userInfo['area'] == '+86') {
                $return['account'] = preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $userInfo['mo']);
            } else {
                $return['account'] = substr_replace($_SESSION['user']['mo'], '**', -4, 2);
            }
        }
        elseif($userInfo['email'])
        {
            $email_array = explode("@", $userInfo['email']);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($userInfo['email'], 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $userInfo['email'], -1, $count);
            $return['account'] = $prevfix . $str;
        }
        
        $this->ajax('', 1, $return);
    }


    /*
    * 初始化API redis數據
    */
    public function initApiRedisDataAction()
    {
        $openapiData = OpenapiModel::getInstance()->fList();
        
        if($openapiData)
        {
            foreach ($openapiData as $v) 
            {
                $openAccesskey[$v['access_key']] = json_encode(array('uid'=>$v['uid'], 'secretKey'=>$v['secret_key']));
            }
            //OPENAPI_ACCESSKEY
            Cache_Redis::instance('user')->hmSet('OPENAPI_ACCESSKEY', $openAccesskey); 
        }
        show('done');
    }
}
