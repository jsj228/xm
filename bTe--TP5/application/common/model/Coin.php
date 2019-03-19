<?php
namespace app\common\model;

use think\Model;
use think\Db;

class Coin extends Model
{

    public function getCoin($flush = false) {
        $coinList = (config('app_debug') || $flush) ? null : cache($this->key);
        if (empty($coinList)) {
            $coinList = array();
            $coin = Db::name('Coin')->where(array('status' => 1))->order('sort')->select();
            foreach ($coin as $k => $v) {
                $coinList['coin'][$v['name']] = $v;

                if ($v['name'] != 'cny') {
                    $coinList['coin_list'][$v['name']] = $v;
                }

                if ($v['type'] == 'rmb') {
                    $coinList['rmb_list'][$v['name']] = $v;
                } else {
                    $coinList['xnb_list'][$v['name']] = $v;
                }

                if ($v['type'] == 'rgb') {
                    $coinList['rgb_list'][$v['name']] = $v;
                }

                if ($v['type'] == 'qbb') {
                    $coinList['qbb_list'][$v['name']] = $v;
                }
            }
            cache($this->key, $coinList);
        }

        return $coinList;
    }

	public function get_all_name_list()
	{
		$list = Db::name('Coin')->order('sort')->select();

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
		$list = Db::name('Coin')->order('sort')->select();

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
		$list = Db::name('Coin')->where(['status' => 1])->order('sort')->select();

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

		$get_title = Db::name('Coin')->where(['name' => $name])->value('title');
		return $get_title;
	}

	public function get_img($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_img = Db::name('Coin')->where(['name' => $name])->value('img');
		return $get_img;
	}

	public function get_sum_coin($name = NULL, $userid = NULL)
	{
		if (empty($name)) {
			return null;
		}

		if ($userid) {
			$a = Db::name('UserCoin')->where(['userid' => $userid])->sum($name);
			$b = Db::name('UserCoin')->where(['userid' => $userid])->sum($name . 'd');
		} else {
			$a = Db::name('UserCoin')->sum($name);
			$b = Db::name('UserCoin')->sum($name . 'd');
		}

		$c = $a + $b;
		return $c;
	}
}

?>