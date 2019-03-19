<?php
/**
* 权限基本表
*
*/
class RoleRightsModel extends Orm_Base{
	public $table = 'role_rights';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'role_id' => array('type' => "int(11) unsigned", 'comment' => '角色id'),
		'content' => array('type' => "text()", 'comment' => '权限'),
		'is_delete' => array('type' => "tinyint", 'comment' => '是否删除：0否1是'),
		'admin_id' => array('type' => "int(11) unsigned", 'comment' => '管理员uid'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated' => array('type' => "int", 'comment' => '更新时间'),
	);
	public $pk = 'id';

}
