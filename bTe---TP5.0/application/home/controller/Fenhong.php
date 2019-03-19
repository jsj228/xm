<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Fenhong extends HomeCommon
{
	public function index()
	{
	    $uid = userid();
		if (!$uid) {
			redirect('/#login');
		}

		$this->assign('prompt_text', model('Text')->get_content('game_fenhong'));
		$coin_list = model('Coin')->get_all_xnb_list();
		foreach ($coin_list as $k => $v) {
		    if ($k == 'btmz'){
		        continue;
            }
			$list[$k]['img'] = model('Coin')->get_img($k);
			$list[$k]['title'] = $v;
			$list[$k]['quanbu'] = model('Coin')->get_sum_coin($k);
			$list[$k]['wodi'] = model('Coin')->get_sum_coin($k, $uid);
			$list[$k]['bili'] = $list[$k]['quanbu'] ? round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%' : '0%';
		}

		$this->assign('list', $list);
		return $this->fetch();
	}

	public function log()
	{
	    $uid = userid();
		if (!$uid) {
			redirect('/#login');
		}

		$this->assign('prompt_text', model('Text')->get_content('game_fenhong_log'));
		$where['userid'] = $uid;
        
		$list = Db::name('FenhongLog')->where($where)->order('id desc')->paginate(10,false,[]);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}
}

?>