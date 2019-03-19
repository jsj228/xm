<?php
class MessageUidModel extends Orm_Base{
	public $table = 'message_uid';
	public $field = array(
		'id'  => array('type' => "int(11)"),
		'mid' => array('type' => "int(11) unsigned", 'comment' => ''),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '使用者id'),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => '状态'),
		'utime' => array('type' => "int(10) unsigned", 'comment' => '更新时间'),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => '创建时间')
	);
	
}
