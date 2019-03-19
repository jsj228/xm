<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;

class Operate extends AdminCommon
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = DB::name('Invit');
		$this->Title = '推广记录';
	}

	public function index()
	{
        $name = strval(input('name'));
		if ($name) {
			$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
		}

		// $count = $this->Model->where($where)->count();
		// $Page = new \Think\Page($count, 15);
		// $show = $Page->show();
		$list = $this->Model->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
			$list[$k]['invit'] = DB::name('User')->where(array('id' => $v['invit']))->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}
}

?>