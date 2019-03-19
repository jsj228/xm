<?php
/**
 * 接收客户端数据类
 *
 */
class Tool_Request {
	private static $_token;
	//3分钟
	private static $_timeStep = 180;
	
	private function __construct() {  }
	function __destruct() {}
	function __clone() { trigger_error('Clone is not allow!', E_USER_ERROR); }

	public static function get($key='') {
		if($key) {
			return isset($_GET[$key]) ? $_GET[$key] : '';
		}

		return $_GET;
	}

	public static function post($key='') {
		if($key) {
			return isset($_POST[$key]) ? $_POST[$key] : '';
		}

		return $_POST;
	}

	public static function request($key='') {
		if($key) {
			return isset($_REQUEST[$key]) ? $_REQUEST[$key] : '';
		}

		return $_REQUEST;
	}


	/**
	 * 检验签名
	 *
	 */
	public static function checkSignature() {

		$signature 	= self::get("signature");
		$timestamp 	= self::get("timestamp");
		$nonce		= self::get("nonce");		
		$token		= self::get("access_token");
		$pass = ((time() - $timestamp) > self::$_timeStep) || !$signature || !$nonce || !$token;
		if($pass) Tool_Response::show('10006');

		$tmpArr		= array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr 	= implode($tmpArr);
		$tmpStr 	= sha1($tmpStr);

		return ($tmpStr == $signature ? true : false);
	}

	/**
	 * 请求方式
	 * @return string
	 */
	public static function method() {
		return strtoupper(addslashes($_SERVER['REQUEST_METHOD']));
	}

	public static function valiRepeatData($repeat){
		$repeat_del = $_SESSION['repeat_del'];
		if($repeat_del != $repeat){
			return false;
		}else{
			return  $_SESSION['repeat_del'] = md5(time().rand(10000,9999));
		}
	}
}
