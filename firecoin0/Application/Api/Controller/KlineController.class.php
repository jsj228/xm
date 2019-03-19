<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/19
 * Time: 17:12
 */
namespace Api\Controller;

use Think\Controller;

class KlineController extends Controller{

    //获取价格
    public function getKline(){
        set_time_limit(0);
        $currency = strtolower(I('currency','wcg_cny'));
        $js_file = "/Public/Api/Js/getKline/$currency.js";
        exec('phantomjs --output-encoding=utf8 '.$js_file,$output_main);



        if(isset($output_main[0])){
            echo json(['code'=>1,'data'=>$output_main[0]]);
        }else{
            echo json(['code'=>0,'msg'=>'获取失败']);
        }

    }
}