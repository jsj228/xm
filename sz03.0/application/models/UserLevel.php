<?php
class UserLevelModel extends Orm_Base{
	public $table = 'user_level';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'credit' => array('type' => "int(11) unsigned", 'comment' => '积分'),
		'level' => array('type' => "varchar(50) unsigned", 'comment' => '级别'),
		'fee_rmbout' => array('type' => "decimal(6,5) unsigned", 'comment' => ''),
		'fee_ybcout' => array('type' => "decimal(6,5) unsigned", 'comment' => ''),
		'loan_limit' => array('type' => "init(11) unsigned", 'comment' => '融资上限'),
		'loan_profit' => array('type' => "decimal(7,5) unsigned", 'comment' => '融资利息')
	);
	public $pk = 'id';
}
