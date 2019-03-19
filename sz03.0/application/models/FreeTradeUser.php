<?php
class FreeTradeUserModel extends Orm_Base{
	public $table = 'free_trade_user';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'end_time' => array('type' => "int(11) unsigned", 'comment' => '過期時間'),
	);

	public $pk = 'id';
	
}
