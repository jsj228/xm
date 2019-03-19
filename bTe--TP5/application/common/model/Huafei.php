<?php
namespace app\common\model;

use think\Model;
use think\Db;
class Huafei extends Model
{
	protected $key = 'home_huafei';

	public function get_type($name = NULL, $status = 1)
	{
		$list = Db::name('HuafeiType')->where(['status' => $status])->select();
		if (!$list) {
			return null;
		}

		$data = array();
		foreach ($list as $k => $v) {
			$data[$v['name']] = $v['title'];
		}

		if ($name) {
			return $data[$name];
		} else {
			return $data;
		}
	}

	public function get_coin($name = NULL, $status = 1)
	{
		$list = cache($this->key . '_' . $name . '_' . $status);
		if (!$list) {
			$list = Db::name('HuafeiCoin')->where(['status' => $status])->select();
			cache($this->key . '_' . $name . '_' . $status, $list);
		}

		$data = array();
		
		$market_type = array();
		$market = Db::name('Market')->where(['status' => 1])->select();
		foreach ($market as $k => $v) {
			$keykey= explode('_', $v['name'])[0];
			$market_type[$keykey]=$v['name'];
		}

		$market = new Market();
		$coin = new Coin();
		foreach ($list as $k => $v) {
			$price = (empty($v['price']) ? $market->get_new_price($market_type[$v['coinname']]) : $v['price']);
			$data[$v['coinname']] = array($coin->get_title($v['coinname']), Num($price));
		}

		if ($name) {
			return $data[$name];
		} else {
			return $data;
		}
	}

	public function setStatus($id = NULL, $type = NULL, $moble = 'Huafei')
	{
		if (empty($id)) {
			return null;
		}

		if (empty($type)) {
			return null;
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($moble)->where($where)->delete()) {
				return true;
			}
			else {
				return null;
			}

			break;

		default:
			return null;
		}

		if (Db::name($moble)->where($where)->update($data)) {
			return true;
		}
		else {
			return null;
		}
	}
}

?>