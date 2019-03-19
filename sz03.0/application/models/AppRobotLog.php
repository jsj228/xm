<?php

class AppRobotLogModel extends Orm_Base
{
	public $table = 'app_robot_log';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => ''),
		'type' => array('type' => "enum('撤销委托','委托','登陆')", 'comment' => '类型'),
		'data' => array('type' => "varchar", 'comment' => ''),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'createip'=>array('type'=>"int",'comment'=>'创建ip'),
		'field1' => array('type' => "varchar", 'comment' => '自定义字段'),
	);
	public $pk = 'id';
}
