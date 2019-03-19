<?php
class MessageModel extends Orm_Base{
	public $table = 'message';
	public $field = array(
		'id'  => array('type' => "int(11)"),
		'title' => array('type' => "varchar unsigned", 'comment' => ''),
		'message' => array('type' => "text", 'comment' => '使用者id'),
		'where' => array('type' => "text ", 'comment' => '状态'),
		'num' => array('type' => "int(10) unsigned", 'comment' => ''),
		'utime' => array('type' => "int(10) unsigned", 'comment' => '更新时间'),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => '创建时间')
	);
	
}
