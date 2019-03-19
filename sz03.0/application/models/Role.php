<?php
/**
* 角色基本信息表
*
*/
class RoleModel extends Orm_Base{
	public $table = 'role';
	public $field = array(
		'role_id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'role_name' => array('type' => "varchar", 'comment' => '角色名'),
		'status' => array('type' => "tinyint", 'comment' => '启用角色：0是，1否'),
		'explains' => array('type' => "varchar", 'comment' => '角色说明'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
	);
	public $pk = 'role_id';

	public static function getRightsByRoleId($role_id) {
		$sql = "select rights.rights_id as rights_id,rights.rights_name as rights_name from role_rights,rights where role_rights.role_id={$role_id} and role_rights.rights_id=rights.rights_id";
		$r_mo = new RoleModel;
		$data = $r_mo->query($sql);
		if(empty($data)) {
			$data = array();
		}

		return $data;
	}

}
