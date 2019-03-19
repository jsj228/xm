<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;
class Vote extends HomeCommon
{
	public function index()
	{
		if (!userid()) {
			redirect('/#login');
		}
		
		$coin_list = Cache::store('redis')->get('VoteType');
		if(!$coin_list){
			$coin_list = Db::name('VoteType')->select();
			Cache::store('redis')->set('VoteType',$coin_list);

		}

		if (is_array($coin_list)) {
			foreach ($coin_list as $k => $v) {
				$vv = $v;
			
				$list[$vv['coinname']]['name'] = $vv['coinname'];
				$list[$vv['coinname']]['title'] = $vv['title'];
				
				$zhichi = Cache::store('redis')->get('Votezhichi'.$vv['coinname']);
				if($zhichi===false){
					$zhichi = Db::name('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['zhichi'];
					Cache::store('redis')->set('Votezhichi'.$vv['coinname'],$zhichi);

				}
				
				$fandui = Cache::store('redis')->get('Votefandui'.$vv['coinname']);
				if($fandui===false){
					$fandui = Db::name('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['fandui'];
					Cache::store('redis')->set('Votefandui'.$vv['coinname'],$fandui);

				}

				$list[$vv['coinname']]['zhichi'] = $zhichi;
				$list[$vv['coinname']]['fandui'] = $fandui;
				$list[$vv['coinname']]['zongji'] = $list[$vv['coinname']]['zhichi'] - $list[$vv['coinname']]['fandui'];
				$list[$vv['coinname']]['bili'] = round(($list[$vv['coinname']]['zhichi'] / $list[$vv['coinname']]['zongji']) * 100, 2);
				$list[$vv['coinname']]['votecoin'] =  C('coin')[$vv['votecoin']]['title'];
				$list[$vv['coinname']]['assumnum'] = $vv['assumnum'];
				$list[$vv['coinname']]['id'] = $vv['id'];
			}

 			$sort = [
				'direction' => 'SORT_DESC',
				'field'     => 'zongji',
			];
			$arrSort = [];
			if ($arrSort){
                foreach($list as $uniqid => $row){
                    foreach($row as $key=>$value){
                        $arrSort[$key][$uniqid] = $value;
                    }
                }

                if($sort['direction']){
                    array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
                }
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

		if (!is_array(D('Coin')->get_all_name_list())) {
			$this->error('参数错误2！');
		}

		$curVote = Db::name('VoteType')->where(['coinname'=>$coinname,'id'=>$id])->find();
		if($curVote){
			$curUserB = Db::name('UserCoin')->where(['userid' =>userid()])->value($curVote['votecoin']);
			if(floatval($curUserB)<floatval($curVote['assumnum'])){
				$this->error('投票所需要的'.$votecoin.'数量不足');
			}
		} else {
			$this->error('不存在的投票类型');
		}


		if (1>3) {
			//$this->error('您已经投票过，不能再次操作！');
		} else if(Db::name('Vote')->insert([
			'userid' => userid(),
			'coinname' => $coinname,
			'title' => $curVote['title'],
			'type' => $type,
			'addtime' => time(),
			'status' => 1
		])) {

			Db::name('UserCoin')->where(['userid' =>userid()])->setDec($curVote['votecoin'],$curVote['assumnum']);
			Cache::rm('Votezhichi'.$coinname);
			Cache::rm('Votefandui'.$coinname);
			$this->success('投票成功！');
		} else {
			$this->error('投票失败！');
		}
	}
}

?>
