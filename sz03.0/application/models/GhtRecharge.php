<?php

class GhtRechargeModel extends Orm_Base
{
	public $table = 'ght_recharge';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => '用户id'),
		'order_no' => array('type' => "varchar", 'comment' => '钱包地址'),
		'status' => array('type' => "tinyint", 'comment' => '0 等待 , 1 成功, 2失败'),
		'created' => array('type' => "datetime", 'comment' => '创建时间'),
		'updated'=>array('type'=>"datetime",'comment'=>'更新时间'),
		'price'=>array('type'=>"decimal",'comment'=>'价格'),
		'balance'=>array('type'=>"decimal",'comment'=>'平台余额'),
		'bank'=>array('type'=>"varchar",'comment'=>'充值途径'),
	);
	public $pk = 'id';

	public static $statusMap = array(
		0=>'待支付',
		1=>'成功',
		2=>'失败',
		3=>'结束',
	);

}