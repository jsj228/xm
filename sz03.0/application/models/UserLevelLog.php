<?php
class UserLevelLogModel extends Orm_Base{
	public $table = 'user_level_log';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'credit' => array('type' => "int(11) unsigned", 'comment' => ''),
		'type' => array('type' => "tinyint(1) unsigned", 'comment' => '1登录,2ybc tx,3btc tx,4rmbin,5invite'),
		'created' => array('type' => "int(11) unsigned", 'comment' => ''),
		'description' => array('type' => "varchar(80) unsigned", 'comment' => '')
	);
	public $pk = 'id';
}
