<?php
class C2ctradeModel extends Orm_Base{
	public $table = 'c2c_trade';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'userid' => array('type' => "int(11) unsigned", 'comment' => '挂单者ID'),
		'price' => array('type' => "decimal(20,8)", 'comment' => '价格'),
		'num' => array('type' => "decimal(20,8)", 'comment' => '总数量'),
		'deal' => array('type' => "decimal(20,8)", 'comment' => '剩余数量'),
		'type' => array('type' => "tinyint(2)", 'comment' => '1，为买 2，为卖'),
		'coin'=> array('type' => "varchar(15)", 'comment' => '币种'),
		'fee'=>array('type' => "decimal(20,8)", 'comment' => '手续费'),
		'moble' => array('type' => "varchar(11)", 'comment' => '手机号'),
		'tradeno'=> array('type' => "varchar(15)", 'comment' => '流水号'),
		'name' => array('type' => "varchar(20)", 'comment' => '姓名认证姓名'),
		'addtime' => array('type' => "int(10)", 'comment' => '时间'),
		'deal_time' => array('typepe' => "int(11)", 'comment' => '匹配时间'),
		'deal_id'=>array('typepe' => "int(11)", 'comment' => '匹配者ID'),
        'bank'=>array('typepe' => "tinyint(2)", 'comment' => '1网银'),
		'wechat'=>array('typepe' => "tinyint(2)", 'comment' => '2微信'),
		'alipay'=>array('typepe' => "tinyint(2)", 'comment' => '3支付宝'),
		'status'=>array('typepe' => "tinyint(2)", 'comment' => '0，交易中 1，完成'),
		'useradmin'=>array('typepe' => "varchar(20)", 'comment' => '管理员'),
	);
	
	public $pk = 'uid';
}
