<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 1/26/2018
 * Time: 10:56 AM
 */

namespace Home\Controller;

class TestController extends HomeController{
    public function index(){

        $url = "https://www.mbaex.net/";
        $html = file_get_contents($url);
        //如果出现中文乱码使用下面代码
        $getcontent = iconv("gb2312", "utf-8",$html);
        echo "<textarea style='width:800px;height:600px;'>".$html."</textarea>";

    }

}