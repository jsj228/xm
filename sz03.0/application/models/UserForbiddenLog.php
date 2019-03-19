<?php

class UserForbiddenLogModel extends Orm_Base
{
	public $table = 'user_forbidden_log';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => '用户id'),
		'admin' => array('type' => "int", 'comment' => '操作管理员id'),
		'content' => array('type' => "varchar(200)", 'comment' => '操作内容'),
		'created' => array('type' => "int", 'comment' => '创建时间')
	);
	public $pk = 'id';
}
