<?php
/**
* 权限基本表
*
*/
class RightsModel extends Orm_Base{
	public $table = 'rights';
	public $field = array(
		'rights_id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'rights_name' => array('type' => "varchar", 'comment' => '权限名'),
		'code' => array('type' => "varchar", 'comment' => '权限码'),
		'status' => array('type' => "tinyint", 'comment' => '是否开启：0开启，1关闭'),
		'readme' => array('type' => "varchar", 'comment' => '说明信息'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated' => array('type' => "int", 'comment' => '更新时间'),
	);
	public $pk = 'rights_id';

	/**
	 *  用户权限
	 */
	public function rightsByUid($uid) {
		$role_arr = UserRoleModel::getInstance()->field('DISTINCT role_id')->where('uid='.$uid)->fList();
		$in   	  = '';
		foreach ($role_arr as $v) {
			$in  .= $v['role_id'].',';
		}

		$in = rtrim($in, ',');
		if(!$in) {
			# 没有任何权限
			return false;
		}

		$rights_arr = RoleRightsModel::getInstance()->field('DISTINCT code')->where('role_id in ( ' . $in .' ) and is_delete=0')->fList();
		if(empty($rights_arr)) {
			return false;
		}

		return $rights_arr;
	}
}
