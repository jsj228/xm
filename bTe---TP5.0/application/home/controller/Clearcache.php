<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Clearcache extends HomeCommon
{
	public function index($cachename=null){
		if(!empty($cachename)){
			Cache::rm($cachename);
		}
	}
}

?>