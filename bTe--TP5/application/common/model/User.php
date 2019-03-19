<?php

namespace app\common\model;

use think\Model;
use think\Db;
class User extends Model
{
    protected $key = 'home_user';

	public function check_update($flush = false)
	{
        $check_update_user = (config('app_debug') || $flush) ? null : cache($this->key);
		if (!$check_update_user) {
			$User_DbFields = Db::name()->getTableFields();

			if (!in_array('alipay', $User_DbFields)) {
				Db::name()->execute('ALTER TABLE `weike_user` ADD COLUMN `alipay` VARCHAR(200) NULL  COMMENT \'支付宝\' AFTER `status`;');
			}

			if (!in_array('email', $User_DbFields)) {
				Db::name()->execute('ALTER TABLE `weike_user` ADD COLUMN `email` VARCHAR(200) NULL  COMMENT \'邮箱\' AFTER `status`;');
			}

            cache($this->key, 1);
		}
	}

	public function get_userid($username = NULL)
	{
		if (empty($username)) {
			return null;
		}

		$get_userid_user = cache('get_userid_' . $username);
		if (!$get_userid_user) {
			$get_userid_user = Db::name('User')->where(array('username' => $username))->value('id');
            cache('get_userid_' . $username, $get_userid_user);
		}

		return $get_userid_user;
	}

	public function get_username($id = NULL)
	{
		if (empty($id)) {
			return null;
		}

		$user = cache('get_username_' . $id);
		if (!$user) {
			$user = Db::name('User')->where(array('id' => $id))->value('username');
            cache('get_username_' . $id, $user);
		}

		return $user;
	}
}

?>