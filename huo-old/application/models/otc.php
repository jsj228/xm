<?php
class OtcModel extends Orm_Base{
	public $table = 'user_otc';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '挂单者ID'),
		'price' => array('type' => "decimal(20,8)", 'comment' => '价格'),
		'deal' => array('type' => "decimal(20,8)", 'comment' => '剩余数量'),
		'type' => array('type' => "tinyint(2)", 'comment' => '1，为买 2，为卖'),
		'coin'=> array('type' => "varchar(15)", 'comment' => '币种'),
		'fee'=>array('type' => "decimal(20,8)", 'comment' => '手续费'),
		'moble' => array('type' => "varchar(11)", 'comment' => '手机号'),
		'tradeno'=> array('type' => "varchar(15)", 'comment' => '流水号'),
		'addtime' => array('type' => "int(10)", 'comment' => '时间'),
		'appeal' => array('typepe' => "tinyint(2)", 'comment' => '0取消 1申诉'),
		'matching'=>array('typepe' => "int(11)", 'comment' => '匹配者ID'),
		'matchtime'=>array('typepe' => "int(10)", 'comment' => '匹配时间'),
		'bank'=>array('typepe' => "tinyint(2)", 'comment' => '1网银'),
		'bank'=>array('typepe' => "tinyint(2)", 'comment' => '2微信'),
		'bank'=>array('typepe' => "tinyint(2)", 'comment' => '3支付宝'),
		'status'=>array('typepe' => "tinyint(2)", 'comment' => '0，交易中 1，完成'),
	);
	public $pk = 'uid';
}
