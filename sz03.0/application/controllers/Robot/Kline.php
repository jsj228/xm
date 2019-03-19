<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/19
 * Time: 17:12
 */

class Robot_KlineController extends Ctrl_Base{

    //获取价格
    public function getKlineAction(){
        set_time_limit(0);
        $currency = $_REQUEST['currency']?strtolower($_REQUEST['currency']):'wcg_cnyx';
        $js_file = APPLICATION_PATH."/public/robot/js/$currency.js";
        exec('phantomjs --output-encoding=utf8 '.$js_file,$output_main);

        if(isset($output_main[0])){
            self::ax(['code'=>1,'data'=>$output_main[0]]);
        }else{
            self::ax(['code'=>0,'msg'=>'获取失败']);
        }

    }
}