<?php
/**
 * 定时任务
 *
 */
class Cli_TimerController extends Ctrl_Cli
{
	protected $logDir = 'Timer/';


	/*
	* 开放充提币
	*/
	public function withdrawOpenAction()
	{
		$r = User_CoinModel::getInstance()->where(sprintf('created=%d', strtotime(date('Y-m-d 10:00'))))->update(array('status'=>User_CoinModel::STATUS_ON));
		exit('done');
	}


	/*
	*开放交易
	*/
	public function tradeOpenAction()
	{
		$r = Coin_PairModel::getInstance()->where(sprintf('start=%d', strtotime(date('Y-m-d 15:00'))))->update(array('status'=>Coin_PairModel::STATUS_ON));
		Cache_Redis::instance()->del('ALL_COINS_INFO');
		Cache_Redis::instance()->del('ACTIVE_COIN_PAIR');
		exit('done');
	}



	/*
	* 用户资金快照
	*/
	public function assetssnapAction($coin='btc', $name='')
	{
		$fields = sprintf('%s_over+%s_lock total, uid', $coin, $coin);
		$where = sprintf('(%s_over+%s_lock>0.5) and uid>100', $coin, $coin);
		$r = UserModel::getInstance()->field($fields)->where($where)->fList();

		$cKey = $name?:$coin.'_snapshot_'.date('YmdHi');
		Cache_Redis::instance()->hmset($cKey, array_column($r, 'total', 'uid'));
		exit('done');
	}


	/*
	* 财务统计(每天一次)
	*/
	public function financialStatAction()
	{
		$cKey = 'DOBI_FINANCIAL_STAT';
		$redis = Cache_Redis::instance();

		//上一个统计日
		$prevDate = $redis->zrange($cKey, -1, -1);

		//没有上一个统计日，从今年年初开始统计
		if(!$prevDate)
		{
			$startTime = strtotime(date('Y').'-01-01');
		}
		else
		{
			$prevDate = json_decode($prevDate[0], true);
			$startTime = $prevDate['date']+86400;
		}

		$today = strtotime('today');
		if($today<=$startTime)
		{
			die('昨天已统计完成');
		}


		//所有交易对
		$coinPair = Coin_PairModel::getInstance()->field('coin_from, coin_to,name')->fList();

		
		$days = ceil(($today-$startTime)/86400);
		for($i=0; $i<$days; $i++)
		{
			$dateGroup = array();
			foreach($coinPair as $pair)
			{
				$coinFrom     = $pair['coin_from'];
				$coinTo       = $pair['coin_to'];
				$thisDayStart = $startTime + $i * 86400;
				$thisDayEnd   = $thisDayStart + 86400;

				$sum = Orm_Base::getInstance()->query(
					"SELECT
						a.date,
						a.count,
						a.number,
						a.money,
						a.salefee,
						a.buyfee,
						c.membernumber
					FROM
						( 
						SELECT
							FROM_UNIXTIME(created, '%Y-%m-%d') date,
							count(id) count,
							sum(number) number,
							sum(number * price) money,
							sum(buy_fee) buyfee,
							sum(sale_fee) salefee
						FROM
							order_{$coinFrom}coin
						WHERE
							coin_to = '{$coinTo}' and coin_from='{$coinFrom}'
						AND created BETWEEN {$thisDayStart}
						AND {$thisDayEnd}
						GROUP BY
							date ) AS a
					LEFT JOIN (
						SELECT
							count(DISTINCT(buy_uid)) membernumber,
							FROM_UNIXTIME(created, '%Y-%m-%d') date
						FROM
							(
								SELECT
									buy_uid,
									created
								FROM
									order_{$coinFrom}coin
								WHERE
										coin_to = '{$coinTo}' and coin_from='{$coinFrom}'
									AND created BETWEEN {$thisDayStart}
									AND {$thisDayEnd}
								UNION
									SELECT
										sale_uid,
										created
									FROM
										order_{$coinFrom}coin
									WHERE
										coin_to = '{$coinTo}' and coin_from='{$coinFrom}'
									AND created BETWEEN {$thisDayStart}
									AND {$thisDayEnd}
							) AS b
						GROUP BY
							date
					) AS c ON a.date = c.date"
					);

				$data = $sum?$sum[0]:[];

				$dateGroup[$coinTo][$coinFrom] = $data;
				$dateGroup[$coinTo]['all']['order_num'] += $data['count'];
				$dateGroup[$coinTo]['all']['money'] = Tool_Math::add($data['money'], $dateGroup[$coinTo]['all']['money']);
				$dateGroup[$coinTo]['all']['salefee'] = Tool_Math::add($data['salefee'], $dateGroup[$coinTo]['all']['salefee']);

				//统计交易总人数
				$where = "coin_to = '{$coinTo}' and coin_from='{$coinFrom}'
										AND created BETWEEN {$thisDayStart}
										AND {$thisDayEnd}";
				$sql = "select count(DISTINCT(buy_uid)) membernumber,FROM_UNIXTIME(created, '%Y-%m-%d') time from(";
	            $str = '';
	            foreach ($coinPair as $v) 
	            {
	            	$name = $v['coin_from'];
	                $table = 'order_' . $name . 'coin';
	                $str .= "(select buy_uid,created from $table where $where) union (select sale_uid,created from $table where $where) union ";
	            }

	            $str = substr($str, 0, -7);
	            $totalsql = $sql . $str . ') as b group by time';
	            $userNum = Orm_Base::getInstance()->query($totalsql);
	            foreach($userNum as $v)
	            {
	                $dateGroup[$coinTo]['all']['user_num'] = Tool_Math::add($v['membernumber'], $dateGroup[$coinTo]['all']['user_num']);
	            }

			}

			$dateGroup['date'] = $thisDayStart;
			Cache_Redis::instance()->zadd($cKey, $thisDayStart, json_encode($dateGroup)); echo $i.PHP_EOL;
		}


		exit('done');
	}

}