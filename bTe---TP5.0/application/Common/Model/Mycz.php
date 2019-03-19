<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;

class Mycz extends Model
{
	protected $keyS = 'Mycz';

	public function check_intact()
	{
		$list = Db::name('Menu')->where(array('url' => 'Mycz/index'))->select();

		if ($list[1]) {
			Db::name('Menu')->where(array('id' => $list[1]['id']))->delete();
		}
		else if (!$list) {
			Db::name('Menu')->insert(array('url' => 'Mycz/index', 'title' => '充值记录', 'pid' => 4, 'sort' => 1, 'hide' => 0, 'group' => '充值管理', 'ico_name' => 'th-list'));
		}
		else {
			Db::name('Menu')->where(array('url' => 'Mycz/index'))->update(array('title' => '充值记录', 'pid' => 4, 'sort' => 1, 'hide' => 0, 'group' => '充值管理', 'ico_name' => 'th-list'));
		}

		$list = Db::name('Menu')->where(array('url' => 'Mycz/type'))->select();

		if ($list[1]) {
			Db::name('Menu')->where(array('id' => $list[1]['id']))->delete();
		}
		else if (!$list) {
			Db::name('Menu')->insert(array('url' => 'Mycz/type', 'title' => '充值方式', 'pid' => 4, 'sort' => 2, 'hide' => 0, 'group' => '充值管理', 'ico_name' => 'th-list'));
		}
		else {
			Db::name('Menu')->where(array('url' => 'Mycz/type'))->update(array('title' => '充值方式', 'pid' => 4, 'sort' => 2, 'hide' => 0, 'group' => '充值管理', 'ico_name' => 'th-list'));
		}

		$list = Db::name('Menu')->where(array('url' => 'Mycz/invit'))->select();

		if ($list[1]) {
			Db::name('Menu')->where(array('id' => $list[1]['id']))->delete();
		}
		else if (!$list) {
			Db::name('Menu')->insert(array('url' => 'Mycz/invit', 'title' => '充值推荐', 'pid' => 4, 'sort' => 3, 'hide' => 0, 'group' => '充值管理', 'ico_name' => 'th-list'));
		}
		else {
			Db::name('Menu')->where(array('url' => 'Mycz/invit'))->update(array('title' => '充值推荐', 'pid' => 4, 'sort' => 3, 'hide' => 0, 'group' => '充值管理', 'ico_name' => 'th-list'));
		}
	}

	public function check_type($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		if (Db::name('MyczType')->where(array('name' => $name))->find()) {
			return true;
		}
		else {
			return null;
		}
	}

	public function get_type_list()
	{
		$get_type_list = $indexAdver = Cache::store('redis')->get('get_type_list' . $this->keyS);

		if (!$get_type_list) {
			$list = Db::name('MyczType')->select();

			foreach ($list as $k => $v) {
				$get_type_list[$v['name']] = $v['title'];
			}

			 Cache::store('redis')->set('get_type_list' . $this->keyS, $get_type_list);
		}

		return $get_type_list;
	}
}

?>