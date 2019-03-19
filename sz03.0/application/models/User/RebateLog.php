<?php
class User_RebateLogModel extends Orm_Base{
	public $table = 'user_rebate_log';
	public $field = array(
		'id' => array('type' => "int(3) unsigned", 'comment' => 'id'),
		'coin' => array('type' => "varchar(20)", 'comment' => '币种'),
		'number' => array('type' => "decimal(40,20)", 'comment' => '数量'),
	    'uid' => array('type' => "int(11)", 'comment' =>''),
		'type' => array('type' => "tinyint(1)", 'comment' => '0 减少 1 增加'),
	    'created' => array('type' => "int(10)", 'comment'=> '创建时间'),
	    'exchange_id' => array('type' => "int(11)", 'comment'=> 'exchange表的id'),
	    'be_invited' => array('type' => "int(11)", 'comment'=> '被邀请人uid'),
	);
	public $pk = 'id';

}
