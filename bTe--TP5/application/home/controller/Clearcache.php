<?php
namespace app\home\controller;

class Clearcache extends \Think\Controller
{
	public function index($cachename=null){
		if(!empty($cachename)){
			cache($cachename,null);
		}
	}
}

?>