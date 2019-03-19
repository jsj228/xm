<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/19
 * Time: 17:12
 */
namespace Api\Controller;

use function PHPSTORM_META\elementType;
use Home\Controller\HomeController;

class CountController extends HomeController{

    //统计总额
    public function income(){

        $input = I('');
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['startTime','endTime','type'];

        $valid_res = SafeController::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']){
            echo json($valid_res);die;
        }else {
            $res = CountLogicController::income($input);
            echo json($res);
        }
    }

    //统计盘口数据
    public function trades(){
        $input = I('');
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['own'];

        $valid_res = SafeController::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']){
            echo json($valid_res);die;
        }else {
            $res = CountLogicController::trades($input);
            echo json($res);
        }
    }

    //按档次统计盘口数据
    public function trades_level(){
        $input = I('');
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['own','size','limit','kline'];

        $valid_res = SafeController::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']){
            echo json($valid_res);die;
        }else {
            $res = CountLogicController::trades_level($input);
            echo json($res);
        }
    }

    //统计盘口详情
    public function trades_detail(){
        $input = I('');
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['type','limit'];

        $valid_res = SafeController::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']){
            echo json($valid_res);die;
        }else {
            $res = CountLogicController::trades_detail($input);
            echo json($res);
        }
    }

    //统计交易记录
    public function trade_log(){
        $input = I('');
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['limit','type'];

        $valid_res = SafeController::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']){
            echo json($valid_res);die;
        }else {
            $res = CountLogicController::trade_log($input);
            echo json($res);
        }
    }

    public function test(){
        $input = I('');
        $res = CountLogicController::trades_detail($input);
        p($res);
    }
}