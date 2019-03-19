<?php

class UserForbiddenModel extends Orm_Base
{
	public $table = 'user_forbidden';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => '用户id'),
		'admin' => array('type' => "int", 'comment' => '操作管理员id'),
		'bak' => array('type' => "char(200)", 'comment' => '冻结原因'),
		'canbuy' => array('type' => "tinyint", 'comment' => '是否可以买'),
		'cansale' => array('type' => "tinyint", 'comment' => '是否可以卖'),
		'canrmbout' => array('type' => "tinyint", 'comment' => '是否可以人民币提现'),
		'cancoinout' => array('type' => "tinyint", 'comment' => '是否可以虚拟货币提现'),
		'status' => array('type' => "tinyint", 'comment' => '删除状态，0正常，1已删除'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated' => array('type' => "int", 'comment' => '更新时间')
	);
	public $pk = 'id';
}
