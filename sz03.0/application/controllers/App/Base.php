<?php
/**
* app base
*/

class App_BaseController extends Ctrl_Base
{
	//后端存储的token前缀
	const TOKEN_PREFIX = 'APP_';
	//当前请求的token
	protected $token;
	//设备
	protected $device;

	public function init()
	{
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, token");
		header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
		header("Content-Type: application/json,application/x-www-form-urlencoded; charset=utf-8");
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
		{
			die;
		}

		set_exception_handler(array(&$this, 'exceptionHandler'));
		//获取|生成token
		if(isset($_SERVER['HTTP_TOKEN']))
		{
			$this->checkToken();
		}
		else
		{
			$this->createToken();
		}

		if(LANG=='cn'){
			$langue='zh-'. LANG;
		}else{
			$langue = LANG;
		}
		header('language:' . $langue);
		//session
		session_id(self::TOKEN_PREFIX . $this->token);

		//强制下线标识检查
		$logout = Cache_Redis::instance()->get('LOGOUT_' . session_id());
		if($logout)
		{
			Cache_Redis::instance()->del('LOGOUT_' . session_id());
			$this->response($GLOBALS['MSG']['TOKEN_REPEATED_WARNING'], 461);
		}

		$this->paramsDecode();

		parent::init();
	}


	/**
	* 参数解密
	*/
	protected function paramsDecode()
	{
		if(!in_array($this->_request->action, array("sendautonymimg","sendautonymimgtwo",'otcuserdata')))  //接口不加密
		{
			$data = $this->rsaDecode(CONF_PATH . 'AppRsaPrivate.key');
			if($data === false)
			{
				$this->response('encode key expire', 462);
			}
			$_POST = $data;
		}
	}

	/**
	* 创建token
	*/
	protected function createToken()
	{
		$this->token = md5(microtime(true).uniqid());
		$sIdx = hexdec($this->token{16});
		$this->token{$sIdx} = base_convert(hexdec($this->token{$sIdx}) + 16, 10, 32);
		header('token:' . $this->token);
	}


	/**
	* 校验token
	*/
	protected function checkToken()
	{
		//校验token合法性
		if(!preg_match('/^[\da-z]{32}$/i', $_SERVER['HTTP_TOKEN']) || base_convert($_SERVER['HTTP_TOKEN']{hexdec($_SERVER['HTTP_TOKEN']{16})}, 32, 10)<16)
		{
			$this->response('Illegal token', 2);
		}
		$this->token = $_SERVER['HTTP_TOKEN'];
	}


	/**
	* 是否登录
	*/
	protected function _islogin()
	{
		$this->_session();
		empty($this->mCurUser) && $this->response($GLOBALS['MSG']['NEED_LOGIN'], 403);
	}


	/*
	* 会话认证
	*/
	protected function auth()
	{
		$this->_session();
		if($this->mCurUser)
		{
			$this->verifySign();
			return $this->mCurUser;
		}
		$this->response($GLOBALS['MSG']['NEED_LOGIN'], 403);
	}


	/*
	* 校验签名
	*/
	protected function verifySign()
	{
		$secretKey = $_SESSION['userStatic']['secretKey'];
		if(!$secretKey)
		{
			$this->response($GLOBALS['MSG']['NEED_LOGIN'], 403);
		}

		if(isset($this->token, $_POST['sign'], $_POST['timestamp'], $_POST['version']))
		{
			if($_POST['version'] != '1.0')
			{
				$this->response('Illegal version', 2);
			}

			if(!is_numeric($_POST['timestamp']))
			{
				$this->response('Illegal timestamp', 2);
			}

			//允许客户端时间与服务器时间差小于一分钟
			if(abs(time()-$_POST['timestamp'])>60)
			{
				$this->response($GLOBALS['MSG']['ILLEGAL_TIMESTAMP'], 460);
			}


			//为了防止secretKey被爆破，签名错误一次，拦截ip
			$IpStr = Tool_Fnc::xRealIp();
			$signErrorIpKey = 'app_sign_error';
			if(isset($_SESSION[$signErrorIpKey][$IpStr]))
			{
				$this->response($GLOBALS['MSG']['ILLEGAL'], 2);
			}

			$postSign = $_POST['sign'];
			$params = $_POST;

			//常规参数，剔除sign
			unset($params['sign']);

			//按key升序排序
			ksort($params);

			$queryStr = http_build_query($params, '', '&');
			$sign=hash_hmac('sha1', $this->token . $queryStr , $secretKey);

			if($sign == $postSign)
			{
				$redis = Cache_Redis::instance('user');
				if(!$redis->set('app_sign_'.$sign, 1, array('nx', 'ex' => 120)))
				{
					$this->response($GLOBALS['MSG']['SUBMIT_DUPLICATE']);
				}
				return true;
			}
			else
			{
				//记录签名错误token的ip
				$_SESSION[$signErrorIpKey][$IpStr] = 1;
			}
		}

		$this->response($GLOBALS['MSG']['INVALID_SIGN'], 2);
	}


	/**
	 * response返回
	 */
	protected function response($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		//不成功结果记录到log
		if($pStatus!=1)
		{
			$data = array(
				'uid' =>isset($this->mCurUser['uid'])?$this->mCurUser['uid']:0,
		        'req_url' =>REDIRECT_URL,
		        'param' =>(string)isset($_POST)?json_encode($_POST):$_GET,
		        'response' =>(string)sprintf('status:%d, msg:%s, data:%s', $pStatus, $pMsg, json_encode($pData)),
		        'sql' => (string)Orm_Base::getInstance()->getLastSql(),
		        'session' =>(string)isset($_SESSION)?json_encode($_SESSION):'',
		        'req_time' =>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
		        'req_ip' =>Tool_Fnc::realip(),
		        'created' =>date('Y-m-d H:i:s'),
			);
			Cache_Redis::instance()->lpush('sqlQueue', json_encode(array('model'=>'ReqFailedLogModel', 'data'=>$data)));
		}
		$tResult = array('status' => $pStatus, 'msg' => $pMsg, 'data' => $pData);

		//便于前端解析，去掉data这个key
		if($pStatus>3)
		{
			unset($tResult['data']);
		}

		# 格式
		if(!DEBUG) ob_clean();
		switch ($pType) {
			case 'json':
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode($tResult));
			case 'xml':
				exit(xml_encode($tResult));
			case 'eval':
				exit($pData);
		}
	}


	//图形验证码校验
	public function validCaptcha($captcha='')
	{
		$captcha or $captcha = $_POST['captcha']?:$_GET['captcha'];
		$r = false;
		if($captcha)
		{
			$tCaptcha = Tool_Captcha::getInstance();
			$r = $tCaptcha->check($captcha);
		}

		if(!$r)
			$this->ajax($GLOBALS['MSG']['CODE_ERROR'], 0, 'captcha');

	}


	public function delCaptcha()
	{
		Tool_Captcha::getInstance()->delCaptcha();
	}


	/*
	* 异常输出
	*/
	function exceptionHandler($e)
	{
		$this->response($e->getMessage(), $e->getCode(), $e->getData());
	}
}
