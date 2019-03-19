<?php
class C2ctradelogModel extends Orm_Base{
	public $table = 'c2c_trade_log';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'buyid' => array('type' => "int(11) unsigned", 'comment' => '买家ID'),
		'sellid' => array('type' => "int(11) unsigned", 'comment' => '卖家ID'),
		'price' => array('type' => "decimal(20,8)", 'comment' => '价格'),
		'num' => array('type' => "decimal(20,8)", 'comment' => '到账价格'),
		'coinname'=> array('type' => "varchar(20)", 'comment' => '币种名称'),
		'type' => array('type' => "tinyint(2)", 'comment' => '1，为买 2，为卖'),
		'buytruename' => array('type' => "varchar(20)", 'comment' => '买家姓名'),
		'buymoble' => array('type' => "varchar(15)", 'comment' => '买家手机号'),
		'buytradeno' => array('type' => "varchar(30)", 'comment' => '买家流水号'),
		'selltruename' => array('type' => "varchar(30)", 'comment' => '卖家真实姓名'),
		'sellmoble' => array('type' => "varchar(15)", 'comment' => '卖家手机号'),
		'selltradeno' => array('type' => "varchar(30)", 'comment' => '卖家流水号'),
		'addtime' => array('type' => "int(11)", 'comment' => '时间'),
		'bank'=>array('typepe' => "tinyint(2)", 'comment' => '网银'),
		'wechat'=>array('typepe' => "tinyint(2)", 'comment' => '微信支付'),
		'alipay'=>array('typepe' => "tinyint(2)", 'comment' => '支付宝'),
		'feesell'=>array('typepe' => "decimal(20,8)", 'comment' => '手续费'),
		'status'=>array('typepe' => "tinyint(2)", 'comment' => '0，交易中 1，完成'),
	);
	public $pk = 'uid';
}
