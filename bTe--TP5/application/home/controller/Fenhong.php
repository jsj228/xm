<?php
namespace app\home\controller;

class Fenhong extends Home
{
	public function index()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$this->assign('prompt_text', model('Text')->get_content('game_fenhong'));
		$coin_list = model('Coin')->get_all_xnb_list();

		foreach ($coin_list as $k => $v) {
			$list[$k]['img'] = model('Coin')->get_img($k);
			$list[$k]['title'] = $v;
			$list[$k]['quanbu'] = model('Coin')->get_sum_coin($k);
			$list[$k]['wodi'] = model('Coin')->get_sum_coin($k, userid());
			$list[$k]['bili'] = round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%';
		}

		$this->assign('list', $list);
		return $this->fetch();
	}

	public function log()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$this->assign('prompt_text', model('Text')->get_content('game_fenhong_log'));
		$where['userid'] = userid();
		$Model = Db::name('FenhongLog');
		$list = $Model->where($where)->order('id desc')->paginate(15);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
}

?>