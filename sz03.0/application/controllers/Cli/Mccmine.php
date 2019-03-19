<?php
/**
 * MCC 挖矿、分红
 *
 */
class Cli_MccmineController extends Ctrl_Cli
{

	protected $logDir = 'mcc_mine/';

	public function runAction()
	{
		$this->tradeMining();
		$this->dividend();
		exit;
	}

	/*
	* 交易挖矿
	*/
	public function tradeMining()
	{
		$userFee = [];
		$openCoins = Coin_PairModel::getInstance()->field('coin_from')->where(['status'=>Coin_PairModel::STATUS_ON])->group('coin_from')->fList();
		$timeBorder = strtotime('6:00');
		$where = sprintf('created>%d and created<%d', $timeBorder-86400, $timeBorder);

		//避免重复操作
		if(Exchange_MccModel::getInstance()->where(sprintf('created>%d and type=2', strtotime('today')))->fRow())
		{
			die('重复操作');
		}

		//MCC 24小时均价
		$mccAvgPrice = Order_CoinModel::getInstance()->designTable('mcc')->field('avg(price) avg_price, coin_to')->where($where)->group('coin_to')->fList();
		$mccAvgPrice = array_column($mccAvgPrice, 'avg_price', 'coin_to');

		foreach($openCoins as $v)
		{
			$coin = $v['coin_from'];
			$orderCoinMo = Order_CoinModel::getInstance()->designTable($coin);
			$count = $orderCoinMo->where($where)->count();
			
			if($count>0)
			{
				//24小时均价
				$avg = $orderCoinMo->field('avg(price) avg_price, coin_to')->where($where)->group('coin_to')->fList();
				$avg = array_column($avg, 'avg_price', 'coin_to');

				$buyFee = $orderCoinMo->field('sum(buy_fee) totalNum, buy_uid uid, coin_to')->where($where . ' and buy_uid != sale_uid')->group('buy_uid,coin_to')->fList();
				$saleFee = $orderCoinMo->field('sum(sale_fee) totalNum, sale_uid uid, coin_to')->where($where . ' and buy_uid != sale_uid')->group('sale_uid,coin_to')->fList();
				
				//买入手续费
				foreach ($buyFee as $v)
				{
					if(isset($avg[$v['coin_to']], $mccAvgPrice[$v['coin_to']]) && $avg[$v['coin_to']]>0)
					{
						$exMcc = Tool_Math::mul($v['totalNum'], $avg[$v['coin_to']]);//to 交易区币
						$exMcc = Tool_Math::div($exMcc, $mccAvgPrice[$v['coin_to']]);//to mcc
						$userFee[$v['uid']] = isset($userFee[$v['uid']]) ? Tool_Math::add($userFee[$v['uid']], $exMcc) : $exMcc;
					}
				}

				//卖出手续费
				foreach($saleFee as $v)
				{
					if(isset($mccAvgPrice[$v['coin_to']]) && $mccAvgPrice[$v['coin_to']]>0)
					{
						$exMcc = Tool_Math::div($v['totalNum'], $mccAvgPrice[$v['coin_to']]);//to mcc
						$userFee[$v['uid']] = isset($userFee[$v['uid']]) ? Tool_Math::add($userFee[$v['uid']], $exMcc) : $exMcc;
					}
				}

			}
			
		}

		//入库
		$now = time();
		$ip = '1.2.3.1';
		$exchangeMo = Exchange_MccModel::getInstance();
		$userMo = UserModel::getInstance();
		
		$userMo->begin();
		$fromUser = UserModel::getInstance()->lock()->field('mcc_over')->where(['uid'=>7])->fRow();
		foreach ($userFee as $uid=>$num)
		{
			$num = Tool_Math::format($num, 8, 2);
			//小于0.00000001 不给
			if(Tool_Math::comp('0.00000001', $num)==1)
			{
				continue;
			}
			//超标
			$fromUser['mcc_over'] = Tool_Math::sub($fromUser['mcc_over'], $num);
			if(Tool_Math::comp($fromUser['mcc_over'], '-50000000')<=0)
			{
				$msg = 'MCC挖矿总额超过5千万';
				$this->log($msg);
				Tool_Fnc::warning($msg);
				die;
			}
			//转入记录
			$exData = array(
	            'uid' => $uid,
	            'admin' => 6,
	            'email' => '',
	            'wallet' => '',
	            'opt_type' => 'in',
	            'number' => $num,
	            'created' => $now,
	            'updated' => $now,
	            'is_out' => 1,
	            'createip' => $ip,
	            'bak' => '交易挖矿',
	            'status' => '成功',
	            'txid' => '',
	            'type'=>2
	        );
	        if (!$exchangeMo->save($exData)) {
	            $userMo->back();
	            $this->log('转入记录插入失败', $exchangeMo->getLastSql());
	            continue;
	        }

	        //更新用户余额
	        $r = $userMo->exec(sprintf('update user set mcc_over = mcc_over+%s, updated=%d, updateip="%s" where uid=%d', $num, $now, $ip, $uid));

	        if (!$r) {
	            $userMo->back();
	            $this->log('更新用户余额失败', $userMo->getLastSql());
	            continue;
	        }
		}

		//更新来源用户余额
        $r = $userMo->exec(sprintf('update user set mcc_over = %s, updated=%d, updateip="%s" where uid=7', $fromUser['mcc_over'], $now, $ip));
        if (!$r) {
            $userMo->back();
            $this->log('更新来源用户余额失败', $userMo->getLastSql());
            return false;
        }

		$userMo->commit();
		return true;

	}


	/*
	* 持仓分红
	*/
	public function dividend()
	{
		//避免重复操作
		if(Exchange_MccModel::getInstance()->where(sprintf('created>%d and type=3', strtotime('today')))->fRow())
		{
			die('重复操作');
		}

		$rate = '0.0005';
		$pageSize = 500;
		//可用MCC余额大于500的用户
		$userMo = UserModel::getInstance();
		$where = 'mcc_over>=500 and uid>100';
		$count = $userMo->where($where)->count();
		
		$exData = [];
		$updateUid = [];
		$total = 0;
		$now = time();
		$ip  = '1.2.3.2';

		$userMo->begin();
		//来源用户
		$fromUser = UserModel::getInstance()->lock()->field('mcc_lock')->where(['uid'=>7])->fRow();
		for($curPage = 1; $curPage<=ceil($count/$pageSize); $curPage++)
		{
			$userData = $userMo->field('uid,mcc_over')->where($where)->page($curPage, $pageSize)->fList();
			foreach($userData as $v)
			{
				$num = Tool_Math::mul($v['mcc_over'], $rate);
				$num = Tool_Math::format($num, 8, 2);
				//小于0.00000001 不给
				if(Tool_Math::comp('0.00000001', $num)==1)
				{
					continue;
				}

				//超标
				$fromUser['mcc_lock'] = Tool_Math::sub($fromUser['mcc_lock'], $num);
				if(Tool_Math::comp($fromUser['mcc_lock'], '-40000000')<=0)
				{
					$msg = '分红总额超过4千万';
					$this->log($msg);
					Tool_Fnc::warning($msg);
					die;
				}


				//转入记录
				$exData[] = array(
		            'uid' => $v['uid'],
		            'admin' => 6,
		            'email' => '',
		            'wallet' => '',
		            'opt_type' => 'in',
		            'number' => $num,
		            'created' => $now,
		            'updated' => $now,
		            'is_out' => 1,
		            'createip' => $ip,
		            'bak' => '持仓分红',
		            'status' => '冻结中',
		            'txid' => '',
		            'type'=>3
		        );


		        $r = $userMo->exec(sprintf('update user set mcc_lock = `mcc_lock`+%s where uid = %d', $num, $v['uid']));
		    	if (!$r) {
		            $userMo->back();
		            $this->log('dividend -更新用户余额失败', $userMo->getLastSql());
		            return false;
		        }
			}
			
		}

		if($exData)
		{
			$exchangeMo = Exchange_MccModel::getInstance();
			if (!$exchangeMo->batchInsert($exData)) 
			{
	            $userMo->back();
	            $this->log('dividend -转入记录插入失败', $exchangeMo->getLastSql());
	            return false;
	        }

	        

	        //更新来源用户余额
	        $r = $userMo->exec(sprintf('update user set mcc_lock = %s, updated=%d, updateip="%s" where uid=7', $fromUser['mcc_lock'], $now, $ip));
	        if (!$r) {
	            $userMo->back();
	            $this->log('更新来源用户余额失败', $userMo->getLastSql());
	            return false;
	        }
	        $userMo->commit();
		}
		
		return true;
	}

	/*
	* 用户资金解冻
	*/
	public function unlockAction()
	{
		$exMccSqlWhere = sprintf(' created>=%d and created<%d and type=3 and status="冻结中" ', strtotime(date('Y-m-d 6:00', strtotime('-30 days'))), strtotime(date('Y-m-d 6:00', strtotime('-29 days'))));
		if(Exchange_MccModel::getInstance()->where($exMccSqlWhere)->count()==0)
		{
			exit(date('Y-m-d', strtotime('-30 days')). date('Y-m-d', strtotime('-29 days')) . '已解冻或当天没有分红');
		}

		$userMo = UserModel::getInstance();
		$userMo->begin();
		//用户余额
		$r = $userMo->exec('update `user` set mcc_over=mcc_over+(select number from exchange_mcc where'.$exMccSqlWhere.' and uid=user.uid), mcc_lock=mcc_lock-(select number from exchange_mcc where '.$exMccSqlWhere.' and uid=user.uid) where uid in (select uid from exchange_mcc where '.$exMccSqlWhere.')');

		if(!$r)
		{
			$userMo->back();
			$msg = 'mcc持仓分红解冻失败, 用户余额更新失败，'.$userMo->getError(2);
			Tool_Fnc::warning($msg);
			exit($msg);
		}

		//exchange 记录状态
		$r = Exchange_MccModel::getInstance()->exec(sprintf('update exchange_mcc set status="成功", updated=%d where %s' , time(), $exMccSqlWhere));
		if(!$r)
		{
			$userMo->back();
			$msg = 'mcc持仓分红解冻失败, exhange状态更新失败, '.Exchange_MccModel::getInstance()->getError(2);
			Tool_Fnc::warning($msg);
			exit($msg);
		}

		$userMo->commit();
		exit('done');
	}

	private function log($msg, $sql='')
	{
		$logDir = $this->logDir . date('Ymd');
		Tool_Log::wlog(sprintf("error : %s, sql : %s", $msg, $sql), $logDir, true);
	}


}