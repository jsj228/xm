<?php
class FeeBonusModel extends Orm_Base{
	public $table = 'fee_bonus';
	public $field = array(
		'id' => array('type' => "int(20) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(20) unsigned", 'comment' => 'UID'),
		'price' => array('type' => "decimal(20,8)", 'comment' => '价格'),
        'number' => array('type' => "decimal(20,8)", 'comment' => '数量'),
        'coin_from' => array('type' => "varchar(20)", 'comment' => '要兑换的币'),
        'coin_to' => array('type' => "varchar(20)", 'comment' => '目标兑换'),
        'oid' => array('type' => "int(20)", 'comment' => '订单ID'),
        'fee' => array('type' => "decimal(40,8)", 'comment' => '分红手续费'),
        'created' => array('type' => "int(11)", 'comment' => '添加时间'),
	);

	public $pk = 'id';
	
}
