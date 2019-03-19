<?php
namespace Home\Controller;

class ClearcacheController extends \Think\Controller
{
	public function index($cachename=null){
		if(!empty($cachename)){
			S($cachename,null);
		}
	}
}

?>