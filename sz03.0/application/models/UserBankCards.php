<?php
class UserBankCardsModel extends Orm_Base{
	public $table = 'user_bank_cards';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'email' => array('type' => "char(60)", 'comment' => '用户名'),
		'account' => array('type' => "char(120)", 'comment' => '汇款账号'),
		'bank' => array('type' => "char(255)", 'comment' => '开户行'),
		'name' => array('type' => "char(12)", 'comment' => '姓名'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'province' => array('type' => "char", 'comment' => ''),
		'city' => array('type' => "char", 'comment' => ''),
		'district' => array('type' => "char", 'comment' => ''),
		'subbranch' => array('type' => "char", 'comment' => ''),
		'status' => array('type' => "int", 'comment' => '是否删除'),
	);
	public $pk = 'id';
}


