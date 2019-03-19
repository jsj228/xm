<?php
/**
 * 返佣统计
 *
 */
class Cli_RebateController extends Ctrl_Cli
{
	protected $logDir = 'rebate/';
	public function runAction()
	{
		$logDir = $this->logDir.date('Ymd');
		//注册90天内的用户才计算
		$start = time()-90*86400;
		$user = UserModel::getInstance()->field('uid, from_uid, created')->where('from_uid>0 and created>'.$start)->fList();

		if(!$user)
		{
			exit('no from_uid ');
		}

		//避免redis数据被清空而造成佣金结算异常
		if(!Cache_Redis::instance()->exists('rebate_statistic_last_orderid'))
		{
			exit('no rebate_statistic_last_orderid');
		}

		$fromUidMap = array_column($user, 'from_uid', 'uid');
		$fromUid = array_keys($fromUidMap);
		
		//默认20位小数
		bcscale(20);

		$coinList = Coin_PairModel::getInstance()->where(["status" =>Coin_PairModel::STATUS_ON])->field('coin_from,coin_to')->getList();

		//被邀请人交易贡献的手续费
		$userFee = array();
		foreach($coinList as $v)
		{
			
			$lastId = Cache_Redis::instance()->hGet('rebate_statistic_last_orderid', $v['coin_from'])?:0;
			$fromUidStr = implode(',', $fromUid);
			$where = sprintf('(buy_uid in (%s) or sale_uid in(%s)) and id>%s', $fromUidStr, $fromUidStr, $lastId);
			$orders = Order_CoinModel::getInstance()->designTable($v['coin_from'])->where($where)->order('id desc')->fList();
			if($orders)
			{
				//保存最后统计的id，下一次从这个id开始统计
				Cache_Redis::instance()->hSet('rebate_statistic_last_orderid', $v['coin_from'], intval($orders[0]['id']));
				foreach ($orders as $v) 
				{
					//不管买还是卖都收coin_to币
					if($buyIsset = isset($fromUidMap[$v['buy_uid']]))
					{
						$userFee[$v['coin_to']][$v['buy_uid']] = isset($userFee[$v['coin_to']][$v['buy_uid']])?bcadd($userFee[$v['coin_to']][$v['buy_uid']], $v['sale_fee']):$v['sale_fee'];
					}
					//同一笔交易的买卖方如果邀请人相同，只收取一份提成
					if(isset($fromUidMap[$v['sale_uid']]) && (!$buyIsset || $fromUidMap[$v['buy_uid']]!=$fromUidMap[$v['sale_uid']]))
					{
						$userFee[$v['coin_to']][$v['sale_uid']] = isset($userFee[$v['coin_to']][$v['sale_uid']])?bcadd($userFee[$v['coin_to']][$v['sale_uid']], $v['sale_fee']):$v['sale_fee'];
					}
				}
			}
		}

		//佣金
		$fromUidRebate = array();
		foreach($userFee as $area=>$uf)
		{
			foreach($fromUidMap as $uid=>$fromUid)
			{
				$fromUidRebate[$area][$fromUid] = isset($fromUidRebate[$area][$fromUid])?bcadd($uf[$uid], $fromUidRebate[$area][$fromUid]):$uf[$uid];
			}
		}

		foreach ($fromUidRebate as $area=>$list) 
		{
			foreach($list as $uid=>$num)
			{
				if($num==0)
				{
					continue;
				}
				
				$saveData = array(
					'coin'=>$area,
					'number'=>bcmul(0.2, $num),
					'uid'=>$uid,
					'type'=>1,
					'created'=>time(),
				);
				$userRebateMo = User_RebateLogModel::getInstance();
				$userRebateMo->begin();
				$result = User_RebateLogModel::getInstance()->save($saveData);
				if(!$result)
				{
					$userRebateMo->back();
					Tool_Log::wlog(sprintf("插入数据失败, data:%s ", json_encode($saveData)), $logDir, true);
				}

				$userMo = UserModel::getInstance();
				$userInfo = $userMo->where('uid='.$uid)->fRow();
				$rebate = json_decode($userInfo['rebate'], true);

				//为了区分注册邀请的mcc,这里改下名字
				if($area=='mcc')
				{
					$area .= '_rebate';
				}
				$rebate[$area.'_in'] = bcadd($rebate[$area.'_in'], $saveData['number']);
				$userData = array('rebate'=>json_encode($rebate), 'uid'=>$uid);
				if(!$userMo->save($userData))
				{
					$userRebateMo->back();
					Tool_Log::wlog(sprintf("插入数据失败, data:%s ", json_encode($userData)), $logDir, true);
				}
				$userRebateMo->commit();
			}
		}

		exit;
	}
}