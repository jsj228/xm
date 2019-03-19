<?php
/**
 * 充值送币活动
 *
 */
class Cli_CoiningiftController extends Ctrl_Cli
{
	protected $logDir = 'CoinInGift/';

	protected $conf = array(
		'ptoc'=>array(
			'totalCoin'=>100000,
			'act'=>array(
				array(
					'type'=>1,
					'percent'=>'0.05',
					'baseCoin'=>'ptoc',
					'beginTime'=>1517554800,
					'endTime'=>1517796000,
					'aid'=>101,
					'txidPreg'=>'/^eth\-/i'
				),
				array(
					'type'=>2,
					'percent'=>'20',
					'baseCoin'=>'btc',
					'beginTime'=>1517328000,
					'endTime'=>1517796000,
					'aid'=>102,
					'txidPreg'=>'/^\d+$/'
				),
				array(
					'type'=>2,
					'percent'=>'2',
					'baseCoin'=>'eth',
					'beginTime'=>1517328000,
					'endTime'=>1517796000,
					'aid'=>103,
					'txidPreg'=>'/^eth\-/i'
				),

			),
		),
		'obc'=>array(
			'totalCoin'=>50000,
			'act'=>array(
				array(
					'type'=>1,
					'percent'=>'0.05',
					'baseCoin'=>'obc',
					'beginTime'=>1517727600,
					'endTime'=>1517796000,
					'aid'=>104,
					'txidPreg'=>'/^mac\-/i'
				),
				array(
					'type'=>2,
					'percent'=>'5',
					'baseCoin'=>'eth',
					'beginTime'=>1517500800,
					'endTime'=>1517796000,
					'aid'=>105,
					'txidPreg'=>'/^eth\-/i'
				),

			),
		),
		'dcon' => array(
			'totalCoin' => 100000,
			'act' => array(
				array(
					'type' => 1,
					'percent' => '0.03',
					'baseCoin' => 'dcon',
					'beginTime' => 1519347600,
					'endTime' => 1519437600,
					'aid' => 106,
					'txidPreg' => '/^\d+$/'
				),
				array(
					'type' => 1,
					'percent' => '10',
					'baseCoin' => 'eth',
					'beginTime' => 1519347600,
					'endTime' => 1519437600,
					'aid' => 107,
					'txidPreg' => '/^\d+$/'
				),

			),
		),

		'cash' => array(
			'totalCoin' => 30000,
			'act' => array(
				array(
					'type' => 1,
					'percent' => '0.03',
					'baseCoin' => 'cash',
					'beginTime' => 1522288800,
					'endTime' => 1522375200,
					'aid' => 108,
					'txidPreg' => '/^\d+$/'
				),
				array(
					'type' => 1,
					'percent' => '100',
					'baseCoin' => 'btc',
					'beginTime' => 1522288800,
					'endTime' => 1522375200,
					'aid' => 109,
					'txidPreg' => '/^\d+$/'
				),

			),
		),

		'ctz' => array(
			'totalCoin' => 20000,
			'act' => array(
				array(
					'type' => 1,
					'percent' => '0.02',
					'baseCoin' => 'ctz',
					'beginTime' => 1527213600,
					'endTime' => 1528905600,
					'aid' => 110,
					'txidPreg' => '/^\d+$/'
				),
				array(
					'type' => 1,
					'percent' => '0.01',
					'baseCoin' => 'dob',
					'beginTime' => 1527213600,
					'endTime' => 1528905600,
					'aid' => 111,
					'txidPreg' => '/^\d+$/',
					'otc' => true,
				),

			),
		),

	);

	protected $totalCoinKey;//总赠送redisKey

	protected $endTime;

	/*（1）2018年2月2日15:00至2018年2月5日10:00期間轉入PTOC的用護，均可獲得轉入PTOC數量5%的PTOC獎勵。
          （2）2018年1月31日00:00至2018年2月5日10:00期间新注册用户充值BTC，将获得1BTC=20PTOC比例的活动奖励。
          （3）2018年1月31日00:00至2018年2月5日10:00期间新注册用户充值ETH，将获得1ETH=2PTOC比例的活动奖励。
          （4）充值送PTOC活動數量有限，先到先得，10萬PTOC送完為止。*/

	/*活動二：充值就送OBC
			活動規則：（1）2018年2月4日15:00至2018年2月5日10:00期間轉入OBC的用護，均可獲得轉入OBC數量5%的OBC獎勵。
          （3）2018年2月2日00:00至2018年2月5日10:00期间新注册用户充值ETH，将获得1ETH=5OBC比例的活动奖励。
           （4）充值送OBC活動數量有限，先到先得，10萬OBC送完為止。*/

	public function runAction($act='ptoc')
	{
		if(!isset($this->conf[$act]))
		{
			throw new Exception("act not exists");
		}

		$conf = $this->conf[$act];

		$this->totalCoinKey = strtoupper($act).'_TOTAL';
		$this->totalCoin = $conf['totalCoin'];

		while (true) 
		{
			$logDir = $this->logDir.date('Ymd');
			foreach($conf['act'] as $k=>$v)
			{
				$v['inCoin'] = $act;
				$this->{'act'.$v['type']}($logDir, $v);
			}
			sleep(10);
		}

	}

	//不限注册时间，充值送币
	private function act1($logDir, $conf)
	{
		$cKey = 'CoinInGiftLastId'.$conf['aid'];
		if(!$lastId = Cache_Redis::instance()->get($cKey))
		{
			throw new Exception('act1 lost redis record');
		}

		if($conf['baseCoin']=='dob')
		{
			$newEx = $newEx =Orm_Base::instance('otc')->table('order_'.$conf['baseCoin'])->field('id, uid, order_price number')->where(sprintf('id>%d and status=2 and updated>%s and updated<%s and type=0', $lastId, $conf["beginTime"], $conf["endTime"]))->order('id desc')->fList();
			if($newEx)
			{
				$userIdList = array_column($newEx, 'uid');
				$otcUidMap = array();
				$moList = Orm_Base::instance('otc')->table('user')->field('uid, mo, area')->where(sprintf('uid in (%s)', implode(',', $userIdList)))->fList();
				foreach($moList as $v)
				{
					$dobiUser = UserModel::getInstance()->field('uid')->where(['mo'=>$v['mo'], 'area'=>$v['area']])->fRow();
					$otcUidMap[$v['uid']] = $dobiUser['uid'];
				}
				foreach($newEx as &$v)
				{
					$v['uid'] = $otcUidMap[$v['uid']];
				}	
				unset($v);
			}
		}
		else
		{
			$mo = 'Exchange_'.ucfirst($conf['baseCoin']).'Model';
			$newEx = $mo::getInstance()->field('id, uid, number, txid, bak')
			->where(sprintf('opt_type="in" and status="成功" and created>%s and created<%s and id>%s', $conf['beginTime'], $conf['endTime'], intval($lastId)))
			->order('id desc')
			->fList();
		}
		

		if($newEx)
		{
			
			$lastId = $newEx[0]['id'];
			Cache_Redis::instance()->set($cKey, $lastId);

			$exGroup = array();
			foreach($newEx as $ex)
			{
				if(!$conf['otc'])
				{
					if(!$ex['txid'] || $ex['bak']!='' || preg_match($conf['txidPreg'], $ex['txid']))
					{
						continue;//平台内不送
					}
				}
				
				if(isset($exGroup[$ex['uid']]))
				{
					$exGroup[$ex['uid']] = Tool_Math::add($ex['number'], $exGroup[$ex['uid']]);
				}
				else
				{
					$exGroup[$ex['uid']] = $ex['number'];
				}	
			}

			if($exGroup)
			{
				foreach($exGroup as $uid=>$number)
				{
					$r = $this->indb($uid, Tool_Math::mul($conf['percent'], $number), $conf['aid'], $conf['inCoin']);
					if(!$r)
					{
						Tool_Log::wlog(sprintf("act%s插入数据失败, uid:%s, num:%s", $conf['type'].$conf['baseCoin'], $uid, $number), $logDir, true);
					}					
				}		
			}
			
			
		}

	}


	//新注册用户充值送币
	private function act2($logDir, $conf)
	{
		$cKey = 'CoinInGiftLastId'.$conf['aid'];
		if(!$lastId = Cache_Redis::instance()->get($cKey))
		{
			throw new Exception('act2 lost redis recode');
		}

		$mo = 'Exchange_'.ucfirst($conf['baseCoin']).'Model';
		$newEx = $mo::getInstance()->field('id, uid, number, txid, bak')
		->where(sprintf('opt_type="in" and status="成功" and created>%s and created<%s and id>%s', $conf['beginTime'], $conf['endTime'], intval($lastId)))
		->order('id desc')
		->fList();

		if($newEx)
		{
			
			$lastId = $newEx[0]['id'];
			Cache_Redis::instance()->set($cKey, $lastId);

			$exGroup = array();
			foreach($newEx as $ex)
			{
				if(!$ex['txid'] || $ex['bak']!='' || preg_match($conf['txidPreg'], $ex['txid']))
				{
					continue;//平台内不送
				}
				if(isset($exGroup[$ex['uid']]))
				{
					$exGroup[$ex['uid']] = Tool_Math::add($ex['number'], $exGroup[$ex['uid']]);
				}
				else
				{
					$exGroup[$ex['uid']] = $ex['number'];
				}	
			}

			if($exGroup)
			{
				$uidList = array_keys($exGroup);
				$userList = UserModel::getInstance()->field('uid, created')->where(sprintf('uid in (%s)', implode(',', $uidList)))->fList();

				if($userList)
				{
					foreach($userList as $user)
					{
						if($user['created']>$conf['beginTime'] && $user['created']<$conf['endTime'])
						{
							$r = $this->indb($user['uid'], Tool_Math::mul($conf['percent'], $exGroup[$user['uid']]), $conf['aid'], $conf['inCoin']);
							if(!$r)
							{
								Tool_Log::wlog(sprintf("act%s插入数据失败, uid:%s, num:%s", $conf['type'].$conf['baseCoin'], $user['uid'], $exGroup[$user['uid']]), $logDir, true);
							}
						}
					}
				}
			}
			
			
		}


	}



	private function indb($uid, $getCoin, $aid, $coin='ptoc')
	{
		$this->totalRecode($getCoin);
		$userMo = UserModel::getInstance();
        $now = time();
        $ip = '0.0.0.0';

        $mo = 'Exchange_'.ucfirst($coin).'Model';
        $exchangeMo = $mo::getInstance();

        $userMo->begin();
        $exData = array(
            'uid'=>$uid,
            'admin' => 6,
            'email'=>'',
            'wallet'=>'',
            'opt_type'=>'in',
            'number'=>$getCoin,
            'created'=>$now,
            'updated'=>$now,
            'is_out'  => 1,
            'createip'=>$ip,
            'bak'=>'活动赠送'.$coin,
            'status'=>'成功',
            'txid'=>'',
        );
        if(!$exchangeMo->save($exData))
        {
            $userMo->back();
            return false;
        }

        //更新用户余额
        $r = $userMo->exec(sprintf('update user set '.$coin.'_over = '.$coin.'_over+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $uid));
        if(!$r)
        {
            $userMo->back();
            return false;
        }
        //更新来源用户余额
        $r = $userMo->exec(sprintf('update user set '.$coin.'_over = '.$coin.'_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $uid));
        if(!$r)
        {
            $userMo->back();
            return false;
        }

        //领取记录
        $urData = array(
            'uid'=>$uid,
            'aid'=>$aid,
            'coin'=>$coin,
            'created'=>$now,
            'updated'=>$now,
            'number'=>$getCoin,
        );
        $r = UserRewardModel::getInstance()->save($urData);
        if(!$r)
        {
            $userMo->back();
            return false;
        }

        $userMo->commit();
        return  true;
	}


	private function totalRecode($num)
	{
		$total = Cache_Redis::instance()->get($this->totalCoinKey);
		$total = Tool_Math::add($total, $num);
		if($total>$this->totalCoin)
		{
			die('limit');
		}
		Cache_Redis::instance()->set($this->totalCoinKey, $total);
	}

}