<?php

class LogFinanceModel extends Orm_Base
{
	public $table = 'log_finance';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'from_uid' => array('type' => "int", 'comment' => '内部流出uid'),
		'to_uid' => array('type' => "int", 'comment' => '内部流入uid'),
		'coin_from' => array('type' => "varchar(6)", 'comment' => ''),
		'coin_to' => array('type' => "varchar(6)", 'comment' => ''),
		'coin' => array('type' => "varchar(6)", 'comment' => '币类型,默认rmb'),
		'number' => array('type' => "decimal(20,8)", 'comment' => '币数量'),
		'type' => array('type' => "tinyint(4)", 'comment' => '0现金卡,1.校园送币,99用户平账,3还款还息'),
		'flag' => array('type' => "tinyint(2)", 'comment' => '0. 卖 1. 买'),
		'bak_id' => array('type' => "int(11)", 'comment' => '如投标id,卡片id'),
		'reason' => array('type' => "varchar(200)", 'comment' => ''),
		'created' => array('type' => "int(11)", 'comment' => ''),
		'update_time' => array('type' => "timestamp", 'comment' => ''),
		'outid' => array('type' => "int", 'comment' => '出借id'),
		'fee_status' => array('type' => "int", 'comment' => '交易手续费奖励，0未奖励，1已奖励'),
		'fee_time' => array('type' => "int", 'comment' => '奖励时间'),
	);
	public $pk = 'id';

}
