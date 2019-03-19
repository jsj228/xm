<?php
/**
  * start page for mobile Response
  *
  * PHP version 5
  *
  * @category  PHP
  * @version   v1.0
  */


class Tool_Response {

	private static $_code = null;
	private static $_msg  = null;
	private static $_data = null;
	private static $_debug= false;

	private function __construct() {}
	public function __clone() { trigger_error('Clone is not allow!', E_USER_ERROR); }


	/**
	 * 数据返回：支持:xml，json，debug, array
	 *
	 * @param 	string 	$code 	状态码
	 * @param 	string 	$msg 	状态值
	 * @param 	array 	$data 	数据
	 * @param 	boolean $log 	true:写日志，false:不写日志
	 * @return 	mixed
	 */
	public static function show($code, $data=null, $msg=null, $log=false) {
		//返回数据类型
		$type = isset($_GET['dt']) ? trim($_GET['dt']) : 'json';
		self::$_code = $code;
		self::$_msg  = $msg;
		self::$_data = $data;

		if( 'xml' == $type ) {
			self::xml();
		} elseif( self::$_debug && 'debug' == $type ) {
			self::debug();
		} elseif( 'array'== $type ) {
			self::parray();
		} else {
			self::json();
		}
	}


	/**
	 * 返回json数据
	 *
	 */
	private static function json() {

		echo header('Content-Type:application/json;charset=utf-8');
		exit( json_encode( self::getArray() ) );
	}


	/**
	 * 返回xml数据
	 *
	 */
	private static function xml() {

		self::$_code = '10006';
		self::$_msg = null;
		self::json();
		exit;
	}


	/**
	 * 输出array数据
	 *
	 */
	private static function parray() {

		print_r( self::getArray() );
		exit;
	}


	/**
	 * 输出debug数据
	 *
	 */
	private static function debug() {

		echo '<pre>';
		print_r( self::getArray() );
		echo '</pre>';
		
		exit;
	}


	private static function getArray() {

		if( !self::$_msg ) {
			$res = Tool_Error::getErrMsg(self::$_code);
		} else {
			$res = array('code'=>self::$_code, 'msg'=>self::$_msg);
		}
		
		
		if( null !== self::$_data && '' != self::$_data ) {
			$res['data'] = self::$_data;
		}

		return $res;
	}
	
	/**
     *  输出接口返回信息
     */
    public static function reMsg($params)
    {
    	
    	echo json_encode($params);
    	exit;
    } 

}
