<?php
class UserLoginModel extends Orm_Base{
	public $table = 'user_login';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'created' => array('type' => "int(11) unsigned", 'comment' => ''),
		'createdip' => array('type' => "char(15) unsigned", 'comment' => ''),
		'area' => array('type' => "varchar(25) unsigned", 'comment' => ''
		)
	);
	public $pk = 'id';
}
