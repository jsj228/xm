<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;


class Test extends HomeCommon
{

    public function index(){
        $y = date("Y");
        $m = date("m");
        $d = date("d");
        $morningTime= mktime(0,0,0,$m,$d,$y);
        //获取当日24点的时间戳
        $nightTime = $morningTime+86400;
    }

    public function add_table(){
    	
    }
}