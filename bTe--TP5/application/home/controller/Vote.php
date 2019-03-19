<?php
namespace app\home\controller;

use think\Db;

class Vote extends Home
{
	public function index()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}
		
		$coin_list = cache('VoteType');
		if(!$coin_list){
			$coin_list = Db::name('VoteType')->select();
			cache('VoteType',$coin_list);
		}

		if (is_array($coin_list)) {
			foreach ($coin_list as $k => $v) {
				$vv = $v;
			
				$list[$vv['coinname']]['name'] = $vv['coinname'];
				$list[$vv['coinname']]['title'] = $vv['title'];
				
				$zhichi = cache('Votezhichi'.$vv['coinname']);
				if($zhichi===false){
					$zhichi = Db::name('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['zhichi'];
					cache('Votezhichi'.$vv['coinname'],$zhichi);
				}
				
				$fandui = cache('Votefandui'.$vv['coinname']);
				if($fandui===false){
					$fandui = Db::name('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['fandui'];
					cache('Votefandui'.$vv['coinname'],$fandui);
				}

				$list[$vv['coinname']]['zhichi'] = $zhichi;
				$list[$vv['coinname']]['fandui'] = $fandui;
				$list[$vv['coinname']]['zongji'] = $list[$vv['coinname']]['zhichi'] - $list[$vv['coinname']]['fandui'];
				$list[$vv['coinname']]['bili'] = round(($list[$vv['coinname']]['zhichi'] / $list[$vv['coinname']]['zongji']) * 100, 2);
				$list[$vv['coinname']]['votecoin'] =  config('coin')[$vv['votecoin']]['title'];
				$list[$vv['coinname']]['assumnum'] = $vv['assumnum'];
				$list[$vv['coinname']]['id'] = $vv['id'];
			}

 			$sort = array(  
				'direction' => 'SORT_DESC',
				'field'     => 'zongji',
			);  
			$arrSort = array();  
			foreach($list AS $uniqid => $row){  
				foreach($row AS $key=>$value){  
					$arrSort[$key][$uniqid] = $value;  
				}  
			}
			
			if($sort['direction']){  
				array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);  
			}   		
			
			$this->assign('list', $list);
		}

		$showText = model('Text')->get_content('game_vote');
		$this->assign('prompt_text', $showText);
		return $this->fetch();
	}

	public function up()
	{
        $type = input('type/d', NULL);
        $coinname = input('coinname/s', NULL);
        $votecoin = input('votecoin/s', NULL);
        $id = input('id/d', 0);

		if (!userid()) {
			$this->error('请先登录！');
		}

		if (($type != 1) && ($type != 2)) {
			$this->error('参数错误！');
		}

		if (!is_array(model('Coin')->get_all_name_list())) {
			$this->error('参数错误2！');
		}

		$curVote = Db::name('VoteType')->where(array('coinname'=>$coinname,'id'=>$id))->find();
		if($curVote){
			$curUserB = Db::name('UserCoin')->where(array('userid' =>userid()))->value($curVote['votecoin']);
			if(floatval($curUserB)<floatval($curVote['assumnum'])){
				$this->error('投票所需要的'.$votecoin.'数量不足');
			}
		} else {
			$this->error('不存在的投票类型');
		}
		//$this->error('测试中');
		//if (Db::name('Vote')->where(array('userid' => userid(), 'coinname' => $coinname))->find()) {
			//$this->error('您已经投票过，不能再次操作！');
		//}


		if (1>3) {
			//$this->error('您已经投票过，不能再次操作！');
		} else if(Db::name('Vote')->insert(array('userid' => userid(), 'coinname' => $coinname,'title' => $curVote['title'], 'type' => $type, 'addtime' => time(), 'status' => 1))) {
//            $zhichi = Db::name('Vote')->where(array('coinname' => $coinname, 'type' => 1))->count();
//            $fandui = Db::name('Vote')->where(array('coinname' => $coinname, 'type' => 2))->count();
//            $meta = array(
//                'zhichi' => $zhichi,
//                'fandui' => $fandui,
//                'zongji' => $zhichi + $fandui,
//                'bili' => round(($zhichi / $fandui) * 100, 2),
//            );
//            Db::name('VoteType')->where(array('coinname' => $coinname))->update($meta);

			Db::name('UserCoin')->where(array('userid' =>userid()))->setDec($curVote['votecoin'],$curVote['assumnum']);
			cache('Votezhichi'.$coinname,null);
			cache('Votefandui'.$coinname,null);
			
			$this->success('投票成功！');
		} else {
			$this->error('投票失败！');
		}
	}
}

?>
