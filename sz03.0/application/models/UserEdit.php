<?php
class UserEditModel extends Orm_Base{
	public $table = 'user_edit';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'aid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => 'session_id'),
		'con' => array('type' => "int(11) unsigned", 'comment' => ''),
		'newcon' => array('type' => "int(11) unsigned", 'comment' => ''),
		'message' => array('type' => "int(11) unsigned", 'comment' => ''),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => ''),
	);
	public $pk = 'id';
}
