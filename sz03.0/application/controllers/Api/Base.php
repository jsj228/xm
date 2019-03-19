<?php
class Api_BaseController extends Ctrl_Base
{
	protected $ipOwner;

	function init()
	{
		if(!isset($_GET['callback'])|| $isOpenApi=preg_match('#/(trade|market)#i', REDIRECT_URL))
		{
			//白名单
			$cKey = 'API_PERMIT_IP';
			$ip = Tool_Fnc::xRealIp();
			if(isset($_GET['showip']))
			{
				show($ip);
			}

			$permit = Cache_Redis::instance()->hGet($cKey, $ip);
			
			if(!$permit)
			{
				$this->ajax('Illegal IP', 2);
			}

			$this->ipOwner = $permit;
		}
	}

	protected function ajax($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json')
	{
		//不成功结果记录到log
		if($pStatus!=1)
		{
			$data = array(
				'uid' =>isset($this->mCurUser['uid'])?$this->mCurUser['uid']:0,
		        'req_url' =>REDIRECT_URL,
		        'param' =>isset($_POST)?json_encode($_POST):$_GET,
		        'response' =>sprintf('status:%d, msg:%s, data:%s', $pStatus, $pMsg, json_encode($pData)),
		        'sql' => Orm_Base::getInstance()->getLastSql(),
		        'session' =>isset($_SESSION)?json_encode($_SESSION):'',
		        'req_time' =>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
		        'req_ip' =>Tool_Fnc::realip(),
		        'created' =>date('Y-m-d H:i:s'),
			);
			Cache_Redis::instance()->lpush('sqlQueue', json_encode(array('model'=>'ReqFailedLogModel', 'data'=>$data)));
		}
		
		if(isset($_GET['callback']) && trim($_GET['callback'])) 
		{
			header('Content-Type:application/javascript; charset=utf-8');
			$jsonValue = json_encode(array('status' => $pStatus, 'msg' => $pMsg, 'data' => $pData));
			exit(sprintf('%s(%s)', $_GET['callback'], $jsonValue));
		}
		parent::ajax($pMsg, $pStatus, $pData, $pType);
	}

	protected function auth()
	{ 
		$accessKey = $_POST['accessKey'];
		if(!$accessKey || strlen($accessKey)!=32)
		{
			$this->ajax('accessKey error', 2);
		}
		$access = Cache_Redis::instance('user')->hGet('OPENAPI_ACCESSKEY', $accessKey);
		if($access && $access = json_decode($access, true))
		{
			if(strpos($this->ipOwner, 'uid:' . $access['uid']) === false)
			{
				$this->ajax('Forbidden', 2);
			}
			$this->verifySign($access['secretKey']);
			$user = UserModel::getInstance()->fRow($access['uid']);
			$this->mCurUser = $user;
			return $user;
		}
		$this->ajax($GLOBALS['MSG']['UNAUTH'], 2);
		
	}


	/* 
	* 校验签名
	*/
	protected function verifySign($secretKey)
	{
		if(isset($_POST['sign'], $_POST['accessKey'], $_POST['timestamp'], $_POST['version']))
		{
			if($_POST['version'] != '1.0' || !is_numeric($_POST['timestamp']))
			{
				$this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
			}
			
			$postSign = $_POST['sign'];
			$params = $_POST;

			//常规参数，剔除sign
			unset($params['sign']);

			//按key升序排序
			ksort($params);

			$queryStr = http_build_query($params, '', '&');
			$sign=hash_hmac('sha1', $queryStr, $secretKey);

			if($sign == $postSign)
			{
				$redis = Cache_Redis::instance('user');
				if(!$redis->set('old_'.$sign, 1, array('nx', 'ex' => 60)))
				{
					$this->ajax($GLOBALS['MSG']['SUBMIT_DUPLICATE']);
				}
				return true;
			}
		}

		$this->ajax($GLOBALS['MSG']['INVALID_SIGN'], 2);
	}

	public function callAction($action, $param1='')
	{
		$param = array($param1);
		return call_user_func_array(array($this, $action), $param);
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

    protected function setErrorNum($key, $errorNum, $expire=7200)
    {
        Cache_Redis::instance()->set($key, $errorNum+1, $expire);//两小时
    }
}
 