<?php
namespace app\admin\controller;

use think\Db;

class Invit extends Admin
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = Db::name('Invit');
		$this->Title = '推广记录';
	}

	public function index()
	{
        $name = input('name/s');
        $where = [];
		if ($name) {
			$where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
		}

        $summum = $this->Model->where($where)->sum('num');
        $sumfee = $this->Model->where($where)->sum('fee');

		$list = $this->Model->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['invit'] = Db::name('User')->where(array('id' => $item['invit']))->value('username');
            return $item;
        });
		$show = $list->render();
		$count = $list->total();

		$this->assign('list', $list);
		$this->assign('page', $show);
        $this->assign('count', $count);
        $this->assign('summum', $summum);
        $this->assign('sumfee', $sumfee);
		return $this->fetch();
	}
}

?>