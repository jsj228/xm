<?php
abstract class CommonController{
	static $param;
	static $is_open;
	static $conf;

	public function __construct($param,$is_open,$conf){
		self::$param = $param;
		self::$is_open = $is_open;
		self::$conf = $conf;
	}

	//权限验证
	private static function author(){
		return true;
	}
}