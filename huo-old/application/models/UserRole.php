<?php
/**
* 权限基本表
*
*/
class UserRoleModel extends Orm_Base{
	public $table = 'user_role';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'uid'),
		'role_id' => array('type' => "int(11) unsigned", 'comment' => '角色id'),
		'admin_id' => array('type' => "int(11) unsigned", 'comment' => '管理员uid'),
		'is_bind' => array('type' => "tinyint", 'comment' => '是否绑定'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated' => array('type' => "int", 'comment' => '更新时间'),
	);
	public $pk = 'id';

}
