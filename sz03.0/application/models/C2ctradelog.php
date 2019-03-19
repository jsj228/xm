<?php
class C2ctradelogModel extends Orm_Base{
	public $table = 'c2c_trade_log';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'userid' => array('type' => "int(11) unsigned", 'comment' => '用户ID'),
		'price' => array('type' => "decimal(10)", 'comment' => '价格'),
		'type' => array('type' => "tinyint(2)", 'comment' => '1，为买 2，为卖'),
		'moble' => array('type' => "int(11)", 'comment' => '手机号'),
		'bankaddr' => array('type' => "varchar(30)", 'comment' => '银行名称'),
		'addtime' => array('type' => "int(11)", 'comment' => '时间'),
		'bankcard' => array('type' => "varchar(25)", 'comment' => '银行卡号'),
		'paytype'=>array('typepe' => "tinyint", 'comment' => '商家支付类型，0网银，1支付宝 2微信支付'),
		'status'=>array('typepe' => "tinyint", 'comment' => '0，交易中 1，完成'),
	);
	
	public $pk = 'uid';
}
