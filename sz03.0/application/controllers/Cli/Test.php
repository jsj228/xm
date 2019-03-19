<?php

/*
* 用户资产校验
*/
class Cli_TestController extends Ctrl_Cli
{
	public function runAction()
	{
		$coin = 'ccl';
		$userMo = UserModel::getInstance();
		$userCount = $userMo->count();
		$pageSize = 1000;

		$userCoin = [];
		for($i=1; $i<=ceil($userCount/$pageSize); $i++)
		{
			//用户余额
			$userList = $userMo->field("{$coin}_over as 'over', {$coin}_lock as 'lock', uid")->page($i, $pageSize)->order('uid asc')->fList();
			foreach ($userList as $v) 
			{
				$userCoin[$v['uid']]['_over'] = $v['over'];
				$userCoin[$v['uid']]['_lock'] = $v['lock'];
				$userCoin[$v['uid']]['total'] = Tool_Math::add($v['over'], $v['lock']);
			}


			$uidList = implode(',', array_column($userList, 'uid'));
			$exchangeIn = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'in' and STATUS = '成功' and uid in ({$uidList}) GROUP BY uid ");

			$exchangeIn = array_column($exchangeIn, 'totalnum', 'uid');
			$exchangeOut = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'out' and STATUS = '成功' and uid in ({$uidList}) GROUP BY uid ");
			$exchangeOut = array_column($exchangeOut, 'totalnum', 'uid');
			
			$coinList = Coin_PairModel::getInstance()->field('coin_from')->where("coin_to='{$coin}'")->fList();
			$coinList[] = array('coin_from'=>$coin);
			foreach($coinList as $v)
			{
				if($v['coin_from']!=$coin)
				{
					$innums = Order_CoinModel::getInstance()->field('sum(price*number-sale_fee) innum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid in ({$uidList}) and coin_to='{$coin}'")->group("sale_uid")->fList();

					$outnums = Order_CoinModel::getInstance()->field('sum(price*number) outnum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid in ({$uidList}) and coin_to='{$coin}'")->group("buy_uid")->fList();
				}
				else
				{
					$innums = Order_CoinModel::getInstance()->field('sum(number-buy_fee) innum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid in ({$uidList})")->group("buy_uid")->fList();

					$outnums = Order_CoinModel::getInstance()->field('sum(number) outnum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid in ({$uidList})")->group("sale_uid")->fList();
				}
				
				foreach($innums as $vv)
				{
					$userCoin[$vv['uid']]['innum'] = Tool_Math::add($userCoin[$vv['uid']]['innum']?:0, $vv['innum']);
					$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_innum'] = $vv['innum'];
				}

				foreach($outnums as $vv)
				{
					$userCoin[$vv['uid']]['outnum'] = Tool_Math::add($userCoin[$vv['uid']]['outnum']?:0, $vv['outnum']);
					$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_outnum'] = $vv['outnum'];
				}
			}

			foreach($userList as $v)
			{
				$userCoin[$v['uid']]['exIn']  = $exchangeIn[$v['uid']]?:0;
				$userCoin[$v['uid']]['exOut']  = $exchangeOut[$v['uid']]?:0;

				$userCoin[$v['uid']]['total'] = Tool_Math::sub($userCoin[$v['uid']]['total'], $exchangeIn[$v['uid']]?:0);
				$userCoin[$v['uid']]['total'] = Tool_Math::add($userCoin[$v['uid']]['total'], $exchangeOut[$v['uid']]?:0);
				$userCoin[$v['uid']]['total'] = Tool_Math::sub($userCoin[$v['uid']]['total'], $userCoin[$v['uid']]['innum']?:0);
				$userCoin[$v['uid']]['total'] = Tool_Math::add($userCoin[$v['uid']]['total'], $userCoin[$v['uid']]['outnum']?:0);

				if(Tool_Math::comp(abs($userCoin[$v['uid']]['total']), '0.000001')==1)
				{
					echo $v['uid'].PHP_EOL;
					print_r($userCoin[$v['uid']]);
				}
				//show($userCoin);
			}
		}
		
		show('done');

	}



	public function mccrunAction()
	{
		$coin = 'mcc';
		$userMo = UserModel::getInstance();
		$userCount = $userMo->count();
		$pageSize = 1000;

		$userCoin = [];
		for($i=1; $i<=ceil($userCount/$pageSize); $i++)
		{
			//用户余额
			$userList = $userMo->field("{$coin}_over as 'over', {$coin}_lock as 'lock', uid")->page($i, $pageSize)->order('uid asc')->fList();
			foreach ($userList as $v) 
			{
				$userCoin[$v['uid']]['_over'] = $v['over'];
				$userCoin[$v['uid']]['_lock'] = $v['lock'];
				$userCoin[$v['uid']]['total'] = Tool_Math::add($v['over'], $v['lock']);
			}


			$uidList = implode(',', array_column($userList, 'uid'));
			$exchangeIn = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'in' and (STATUS = '成功' or STATUS = '冻结中') and uid in ({$uidList}) GROUP BY uid ");

			$exchangeIn = array_column($exchangeIn, 'totalnum', 'uid');
			$exchangeOut = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'out' and (STATUS = '成功' or STATUS = '冻结中') and uid in ({$uidList}) GROUP BY uid ");
			$exchangeOut = array_column($exchangeOut, 'totalnum', 'uid');
			
			$coinList = Coin_PairModel::getInstance()->field('coin_from')->where("coin_to='{$coin}'")->fList();
			$coinList[] = array('coin_from'=>$coin);
			foreach($coinList as $v)
			{
				if($v['coin_from']!=$coin)
				{
					$innums = Order_CoinModel::getInstance()->field('sum(price*number-sale_fee) innum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid in ({$uidList}) and coin_to='{$coin}'")->group("sale_uid")->fList();

					$outnums = Order_CoinModel::getInstance()->field('sum(price*number) outnum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid in ({$uidList}) and coin_to='{$coin}'")->group("buy_uid")->fList();
				}
				else
				{
					$innums = Order_CoinModel::getInstance()->field('sum(number-buy_fee) innum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid in ({$uidList})")->group("buy_uid")->fList();

					$outnums = Order_CoinModel::getInstance()->field('sum(number) outnum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid in ({$uidList})")->group("sale_uid")->fList();
				}
				
				foreach($innums as $vv)
				{
					$userCoin[$vv['uid']]['innum'] = Tool_Math::add($userCoin[$vv['uid']]['innum']?:0, $vv['innum']);
					$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_innum'] = $vv['innum'];
				}

				foreach($outnums as $vv)
				{
					$userCoin[$vv['uid']]['outnum'] = Tool_Math::add($userCoin[$vv['uid']]['outnum']?:0, $vv['outnum']);
					$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_outnum'] = $vv['outnum'];
				}
			}

			foreach($userList as $v)
			{
				$userCoin[$v['uid']]['exIn']  = $exchangeIn[$v['uid']]?:0;
				$userCoin[$v['uid']]['exOut']  = $exchangeOut[$v['uid']]?:0;

				$userCoin[$v['uid']]['total'] = Tool_Math::sub($userCoin[$v['uid']]['total'], $exchangeIn[$v['uid']]?:0);
				$userCoin[$v['uid']]['total'] = Tool_Math::add($userCoin[$v['uid']]['total'], $exchangeOut[$v['uid']]?:0);
				$userCoin[$v['uid']]['total'] = Tool_Math::sub($userCoin[$v['uid']]['total'], $userCoin[$v['uid']]['innum']?:0);
				$userCoin[$v['uid']]['total'] = Tool_Math::add($userCoin[$v['uid']]['total'], $userCoin[$v['uid']]['outnum']?:0);

				if(Tool_Math::comp(abs($userCoin[$v['uid']]['total']), '5000')==1)
				{
					echo $v['uid'].PHP_EOL;
					print_r($userCoin[$v['uid']]);
				}
				//show($userCoin);
			}
		}
		
		show('done');

	}


	/*
	*  單個用戶mcc資產
	*/
	public function onemccAction()
	{
		$uid = 13250830;
$uid = 13231173;;
		$coin = 'mcc';
		$userMo = UserModel::getInstance();
		$userCount = $userMo->count();


		$userCoin = [];

		//用户余额
		$user = $userMo->field("{$coin}_over as 'over', {$coin}_lock as 'lock', uid")->where(['uid'=>$uid])->fRow();

		if(!$user)
		{
			die('user dose not exisit');
		}

		$userCoin[$user['uid']]['_over'] = $user['over'];
		$userCoin[$user['uid']]['_lock'] = $user['lock'];
		$userCoin[$user['uid']]['total'] = Tool_Math::add($user['over'], $user['lock']);
		



		$exchangeIn = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'in' and (STATUS = '成功' or STATUS = '冻结中') and uid = {$uid}");

		$exchangeIn = array_column($exchangeIn, 'totalnum', 'uid');
		$exchangeOut = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'out' and (STATUS = '成功' or STATUS = '冻结中') and uid = {$uid}");
		$exchangeOut = array_column($exchangeOut, 'totalnum', 'uid');
		
		$coinList = Coin_PairModel::getInstance()->field('coin_from')->where("coin_to='{$coin}'")->fList();
		$coinList[] = array('coin_from'=>$coin);
		foreach($coinList as $v)
		{
			if($v['coin_from']!=$coin)
			{
				$innums = Order_CoinModel::getInstance()->field('sum(price*number-sale_fee) innum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid ={$uid} and coin_to='{$coin}'")->group("sale_uid")->fList();

				$outnums = Order_CoinModel::getInstance()->field('sum(price*number) outnum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid = {$uid} and coin_to='{$coin}'")->group("buy_uid")->fList();
			}
			else
			{
				$innums = Order_CoinModel::getInstance()->field('sum(number-buy_fee) innum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid ={$uid}")->group("buy_uid")->fList();

				$outnums = Order_CoinModel::getInstance()->field('sum(number) outnum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid ={$uid}")->group("sale_uid")->fList();
			}
			

			foreach($innums as $vv)
			{
				$userCoin[$vv['uid']]['innum'] = Tool_Math::add($userCoin[$vv['uid']]['innum']?:0, $vv['innum']);
				$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_innum'] = $vv['innum'];
			}

			foreach($outnums as $vv)
			{
				$userCoin[$vv['uid']]['outnum'] = Tool_Math::add($userCoin[$vv['uid']]['outnum']?:0, $vv['outnum']);
				$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_outnum'] = $vv['outnum'];
			}


		}


		$userCoin[$user['uid']]['exIn']  = $exchangeIn[$user['uid']]?:0;
		$userCoin[$user['uid']]['exOut']  = $exchangeOut[$user['uid']]?:0;

		$userCoin[$user['uid']]['total'] = Tool_Math::sub($userCoin[$user['uid']]['total'], $exchangeIn[$user['uid']]?:0);
		$userCoin[$user['uid']]['total'] = Tool_Math::add($userCoin[$user['uid']]['total'], $exchangeOut[$user['uid']]?:0);
		$userCoin[$user['uid']]['total'] = Tool_Math::sub($userCoin[$user['uid']]['total'], $userCoin[$user['uid']]['innum']?:0);
		$userCoin[$user['uid']]['total'] = Tool_Math::add($userCoin[$user['uid']]['total'], $userCoin[$user['uid']]['outnum']?:0);

		// if(Tool_Math::comp(abs($userCoin[$user['uid']]['total']), '0.1')==1)
		// {
		// 	echo $user['uid'].PHP_EOL;
		// 	print_r($userCoin[$user['uid']]);
		// }
		
		show($userCoin);
		$old = '6.2528803619168';
		if($old!==$userCoin[$user['uid']]['total'])
		{
			Tool_Fnc::warning(sprintf('机器人eth资产差值变动, old:%s, new:%s, time:%s, date:%s', $old, $userCoin[$user['uid']]['total'], date('Y-m-d H:i:s'), json_encode($userCoin)));
			return;
		}
		
		
		//show('done');

	}



	/*
	*  监控單個用戶資產
	*/
	public function checkOneAction()
	{
		while(true)
		{
			$this->oneAction();
			sleep(30);
		}
	}


	/*
	*  單個用戶資產
	*/
	public function oneAction()
	{
		$uid = 13232743;

		$coin = 'eth';
		$userMo = UserModel::getInstance();
		$userCount = $userMo->count();
		$pageSize = 1000;

		$userCoin = [];

		//用户余额
		$user = $userMo->field("{$coin}_over as 'over', {$coin}_lock as 'lock', uid")->where(['uid'=>$uid])->fRow();

		if(!$user)
		{
			die('user dose not exisit');
		}

		$userCoin[$user['uid']]['_over'] = $user['over'];
		$userCoin[$user['uid']]['_lock'] = $user['lock'];
		$userCoin[$user['uid']]['total'] = Tool_Math::add($user['over'], $user['lock']);
		



		$exchangeIn = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'in' and STATUS = '成功' and uid = {$uid}");

		$exchangeIn = array_column($exchangeIn, 'totalnum', 'uid');
		$exchangeOut = $userMo->query("SELECT sum(number) totalnum, uid from exchange_{$coin} where opt_type = 'out' and STATUS = '成功' and uid = {$uid}");
		$exchangeOut = array_column($exchangeOut, 'totalnum', 'uid');
		
		$coinList = Coin_PairModel::getInstance()->field('coin_from')->where("coin_to='{$coin}'")->fList();
		$coinList[] = array('coin_from'=>$coin);
		foreach($coinList as $v)
		{
			if($v['coin_from']!=$coin)
			{
				$innums = Order_CoinModel::getInstance()->field('sum(price*number-sale_fee) innum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid ={$uid} and coin_to='{$coin}'")->group("sale_uid")->fList();

				$outnums = Order_CoinModel::getInstance()->field('sum(price*number) outnum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid = {$uid} and coin_to='{$coin}'")->group("buy_uid")->fList();
			}
			else
			{
				$innums = Order_CoinModel::getInstance()->field('sum(number-buy_fee) innum, buy_uid uid')->designTable($v['coin_from'])->where("buy_uid ={$uid}")->group("buy_uid")->fList();

				$outnums = Order_CoinModel::getInstance()->field('sum(number) outnum, sale_uid uid')->designTable($v['coin_from'])->where("sale_uid ={$uid}")->group("sale_uid")->fList();
			}
			
			foreach($innums as $vv)
			{
				$userCoin[$vv['uid']]['innum'] = Tool_Math::add($userCoin[$vv['uid']]['innum']?:0, $vv['innum']);
				$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_innum'] = $vv['innum'];
			}

			foreach($outnums as $vv)
			{
				$userCoin[$vv['uid']]['outnum'] = Tool_Math::add($userCoin[$vv['uid']]['outnum']?:0, $vv['outnum']);
				$userCoin[$vv['uid']]['orders'][$v['coin_from'].'_outnum'] = $vv['outnum'];
			}
		}


		$userCoin[$user['uid']]['exIn']  = $exchangeIn[$user['uid']]?:0;
		$userCoin[$user['uid']]['exOut']  = $exchangeOut[$user['uid']]?:0;

		$userCoin[$user['uid']]['total'] = Tool_Math::sub($userCoin[$user['uid']]['total'], $exchangeIn[$user['uid']]?:0);
		$userCoin[$user['uid']]['total'] = Tool_Math::add($userCoin[$user['uid']]['total'], $exchangeOut[$user['uid']]?:0);
		$userCoin[$user['uid']]['total'] = Tool_Math::sub($userCoin[$user['uid']]['total'], $userCoin[$user['uid']]['innum']?:0);
		$userCoin[$user['uid']]['total'] = Tool_Math::add($userCoin[$user['uid']]['total'], $userCoin[$user['uid']]['outnum']?:0);

		// if(Tool_Math::comp(abs($userCoin[$user['uid']]['total']), '0.1')==1)
		// {
		// 	echo $user['uid'].PHP_EOL;
		// 	print_r($userCoin[$user['uid']]);
		// }
		show($userCoin);
		$old = '6.2528803619168';
		if($old!==$userCoin[$user['uid']]['total'])
		{
			Tool_Fnc::warning(sprintf('机器人eth资产差值变动, old:%s, new:%s, time:%s, date:%s', $old, $userCoin[$user['uid']]['total'], date('Y-m-d H:i:s'), json_encode($userCoin)));
			return;
		}
		
		
		//show('done');

	}



	public function wsAction()
	{
		for($i=0; $i<1000000; $i++)
		{
			Tool_Push::send('test', md5(uniqid()));
		}

		show('done'.PHP_EOL);
		
	}


	
}