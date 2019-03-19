<?php
class Ajax_BaseController extends Ctrl_Base
{

	public function init()
	{
		// show($_POST);
		parent::init();
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
		header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
		header("Content-Type: application/json,application/x-www-form-urlencoded; charset=utf-8");
		if(in_array($this->_request->action,array("issettradepwd","checktradepwd",'xcxoutcoin','mobilephone','phone','rgbCancel','otcuserwallet')))  //实名接口不加密
		{

		}
		else
		{
			$_POST =  $this->rsaDecode();
		}

	}

	/*
	 *	安全检查
	 */
	private function safetyCheck()
	{
		if(!$_SERVER['HTTP_REFERER'] || !$_SERVER['HTTP_USER_AGENT'] || stripos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false)
		{
			header("status: 403 Forbidden");
			die;
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

	public function setPassSign($expire=1800)
	{
		$sign = md5(uniqid( microtime(true), true ));
		$redis = Cache_Redis::instance();
		$redis->select(0);
		$redis->set('pass_sign'.$sign, 1, $expire);
		return $sign;
	}

	public function getPassSign($sign)
	{
		$redis = Cache_Redis::instance();
		$redis->select(0);
		return $redis->get('pass_sign'.$sign);
	}


	public function delPassSign($sign)
	{
		Cache_Redis::instance()->select(0)->del('pass_sign'.$sign);
	}


	/**
	 * AJAX返回
	 */
	protected function ajax($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		//不成功结果记录到log
		if($pStatus!=1)
		{
			$data = array(
				'uid' =>isset($this->mCurUser['uid'])?$this->mCurUser['uid']:0,
		        'req_url' =>substr(REDIRECT_URL, 0, 255),
		        'param' =>(string)isset($_POST)?json_encode($_POST):$_GET,
		        'response' =>sprintf('status:%d, msg:%s, data:%s', $pStatus, $pMsg, json_encode($pData)),
		        'sql' => Orm_Base::$lastSql,
		        'session' =>isset($_SESSION)?json_encode($_SESSION):'',
		        'req_time' =>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
		        'req_ip' =>Tool_Fnc::realip(),
		        'created' =>date('Y-m-d H:i:s'),
			);
			Cache_Redis::instance()->lpush('sqlQueue', json_encode(array('model'=>'ReqFailedLogModel', 'data'=>$data)));
		}

		$ip = Tool_Fnc::realip();
		if((in_array($ip, ['114.236.94.9', '36.149.41.165', '112.21.203.55', '36.149.169.165', '112.3.133.145', '183.206.72.51', '36.149.40.241','183.206.85.158'])
			|| in_array($this->mCurUser['uid'], ['13232584', '13231258','13281609'])) && stripos(REDIRECT_URL, 'ajax_market')===false)
		{
			$data = array(
				'uid' =>666,
		        'req_url' =>REDIRECT_URL,
		        'param' =>(string)isset($_POST)?json_encode($_POST):$_GET,
		        'response' =>sprintf('status:%d, msg:%s, data:%s', $pStatus, $pMsg, json_encode($pData)),
		        'sql' => Orm_Base::$lastSql,
		        'session' =>isset($_SESSION)?substr(json_encode($_SESSION), 0, 900):'',
		        'req_time' =>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
		        'req_ip' =>Tool_Fnc::realip(),
		        'created' =>date('Y-m-d H:i:s'),
			);
			Cache_Redis::instance()->lpush('sqlQueue', json_encode(array('model'=>'ReqFailedLogModel', 'data'=>$data)));
		}

		Tool_Fnc::ajaxMsg($pMsg, $pStatus, $pData, $pType);
	}

	// 谷歌验证码二维码
	function qrimagesAction()
	{
		$text = isset($_GET['text'])?$_GET['text']:'null';
		$size = isset($_GET['size'])?$_GET['size']:6;
		$margin= isset($_GET['margin'])?$_GET['margin']:4;
		ob_clean();
		Tool_Qrcode::png($text,false, QR_ECLEVEL_L, $size, $margin, false);
		exit(0);
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
