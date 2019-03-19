<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/19
 * Time: 17:12
 */

class Robot_CountController extends Robot_SafeController{
    public function init(){
        $this->CountLogic = new Robot_CountLogicController();
    }

    //统计总额
    public function incomeAction(){
        $input = $_REQUEST;

        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['startTime','endTime','type'];

        $valid_res = self::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']) self::ax($valid_res);

        $input['uid'] = $valid_res['uid'];
        $res = $this->CountLogic->income($input);

        self::ax($res);

    }

    //统计盘口数据
    public function tradesAction(){
        $input = $_REQUEST;
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['own'];

        $valid_res = self::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']) self::ax($valid_res);

        $res = $this->CountLogic->trades($input,$valid_res['uid']);
        self::ax($res);
    }

    //按档次统计盘口数据
    public function trades_levelAction(){
        $input = $_REQUEST;
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['own','size','limit','kline'];

        $valid_res = self::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']) self::ax($valid_res);

        $res = $this->CountLogic->trades_level($input,$valid_res['uid']);
        self::ax($res);
    }

    //统计盘口详情
    public function trades_detailAction(){
        $input = $_REQUEST;
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['type','limit'];

        $valid_res = self::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']) self::ax($valid_res);

        $res = $this->CountLogic->trades_detail($input,$valid_res['uid']);
        self::ax($res);
    }

    //统计交易记录
    public function trade_logAction(){
        $input = $_REQUEST;
        //待验证参数 $mandatory必选 $optional可选
        $mandatory = ['accesskey','currency','method','sign','reqTime'];
        $optional = ['limit','type'];

        $valid_res = self::valid_sign($input,$mandatory,$optional);
        if(!$valid_res['code']) self::ax($valid_res);

        $res = $this->CountLogic->trade_log($input,$valid_res['uid']);
        self::ax($res);

    }
}