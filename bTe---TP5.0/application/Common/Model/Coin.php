<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;

class Coin extends Model
{
	public function check_install()
	{
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
	}

	public function check_file()
	{
	}

	public function get_all_name_list()
	{
		$list = DB::name('Coin')->order('sort asc')->select();

		if (is_array($list)) {
			foreach ($list as $k => $v) {
				$get_all_name_list[$v['name']] = $v['title'];
			}
		} else {
			$get_all_name_list = null;
		}

		return $get_all_name_list;
	}

	public function get_all_xnb_list()
	{
		$list = DB::name('Coin')->order('sort asc')->select();

		if (is_array($list)) {
			foreach ($list as $k => $v) {
				if ($v['type'] != 'rmb') {
					$get_all_xnb_list[$v['name']] = $v['title'];
				}
			}
		} else {
			$get_all_xnb_list = null;
		}

		return $get_all_xnb_list;
	}
	
	
	public function get_all_xnb_list_allow()
	{
		$list = DB::name('Coin')->where(array("status" => 1))->order('sort asc')->select();

		if (is_array($list)) {
			foreach ($list as $k => $v) {
				if ($v['type'] != 'rmb') {
					$get_all_xnb_list_allow[$v['name']] = $v['title'];
				}
			}
		} else {
			$get_all_xnb_list_allow = null;
		}

		return $get_all_xnb_list_allow;
	}
	
	
	
	

	public function get_title($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_title = DB::name('Coin')->where(array('name' => $name))->value('title');
		return $get_title;
	}

	public function get_img($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_img = DB::name('Coin')->where(array('name' => $name))->value('img');
		return $get_img;
	}

	public function get_sum_coin($name = NULL, $userid = NULL)
	{
		if (empty($name)) {
			return null;
		}

		if ($userid) {
			$a = DB::name('UserCoin')->where(array('userid' => $userid))->sum($name);
			$b = DB::name('UserCoin')->where(array('userid' => $userid))->sum($name . 'd');
		} else {
			$a = DB::name('UserCoin')->sum($name);
			$b = DB::name('UserCoin')->sum($name . 'd');
		}

		$c = $a + $b;
		return $c;
	}
}

?>