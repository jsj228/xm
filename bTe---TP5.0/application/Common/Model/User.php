<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;

class User extends Model
{
	public function check_install()
	{
		$this->check_authorization();
		$this->check_database();
		$this->check_update();
	}

	public function check_uninstall()
	{
	}

	public function check_server()
	{
	}

	public function check_authorization()
	{
	}

	public function check_database()
	{
	}

	public function check_update()
	{
		$check_update_user =  Cache::store('redis')->get('check_update_user');

		if (!$check_update_user) {
			$User_DbFields = Db::name('User')->getTableFields();

			if (!in_array('alipay', $User_DbFields)) {
				Db::name()->execute('ALTER TABLE `weike_user` ADD COLUMN `alipay` VARCHAR(200) NULL  COMMENT \'支付宝\' AFTER `status`;');
			}

			if (!in_array('email', $User_DbFields)) {
				Db::name()->execute('ALTER TABLE `weike_user` ADD COLUMN `email` VARCHAR(200) NULL  COMMENT \'邮箱\' AFTER `status`;');
			}
 			Cache::store('redis')->set('check_update_user', 1);
		}
	}

	public function get_userid($username = NULL)
	{
		if (empty($username)) {
			return null;
		}

		$get_userid_user = Cache::store('redis')->get('get_userid_user' . $username);

		if (!$get_userid_user) {
			$get_userid_user = Db::name('User')->where(array('username' => $username))->value('id');
			 Cache::store('redis')->set('get_userid_user' . $username, $get_userid_user);
		}

		return $get_userid_user;
	}

	public function get_username($id = NULL)
	{
		if (empty($id)) {
			return null;
		}

		$user = Cache::store('redis')->get('get_username' . $id);

		if (!$user) {
			$user = Db::name('User')->where(array('id' => $id))->value('username');
			 Cache::store('redis')->set('get_username' . $id, $user);
		}

		return $user;
	}
}

?>