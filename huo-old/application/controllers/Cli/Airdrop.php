<?php
/**
 * 空投
 *
 */
class Cli_AirdropController extends Ctrl_Cli
{
	protected $logDir = 'Airdrop/';

	public function runAction($coin='lcc')
	{
		call_user_func(array(&$this, $coin));
	}


	public function lcc()
	{
		die('结束');
		$r = Exchange_LccModel::getInstance()->where('bak="空投"')->fRow();
		if($r)
		{
			die('已发放');
		}
		$autonymMo = AutonymModel::getInstance();
		$where = 'status=2';
		$total = $autonymMo->where($where)->count();
		$pageSize = 5000;
		$totalPage = ceil($total/$pageSize);

		$addTime = strtotime('2018-8-29 15:00');
		$autonymMo->begin();
		for($i=0; $i<$totalPage; $i++)
		{
			$realInfoUid = $autonymMo->field('uid')->where($where)->order('id asc')->page($i+1, $pageSize)->fList();

			if($realInfoUid)
			{
				//按空投数量随机分组
				$group = array();
				foreach ($realInfoUid as $v) 
				{
					$group[rand(1, 3)][] = $v['uid'];
				}

				foreach($group as $num=>$curGroup)
				{
					//更新用户资金
					$r = UserModel::getInstance()->exec(sprintf('UPDATE `user` SET `lcc_over`=lcc_over+%s WHERE uid in (%s)', $num, implode(',', $curGroup)));
					if(!$r)
					{
						$autonymMo->back();
						Tool_Fnc::warning(sprintf('空投更新user信息失败, data:%s', json_encode(UserModel::getInstance()->getError())));
						die('空投更新user信息失败');
					}

					$exData = array();
					$urData = array();
					foreach($curGroup as $uid)
					{
						$exData[] = array(
							'uid' => $uid,
							'admin' => 6,
							'email' => '',
							'wallet' => '',
							'opt_type' => 'in',
							'number' => $num,
							'created' => $addTime,
							'updated' => $addTime,
							'is_out' => 1,
							'createip' => '6.6.6.66',
							'bak' => '空投',
							'status' => '成功',
							'txid' => '',
						);

						$urData[] = array(
				            'uid'=>$uid,
				            'aid'=>13,
				            'coin'=>'lcc',
				            'created'=>$addTime,
				            'updated'=>$addTime,
				            'number'=>$num,
				        );
					}

					//插入转入记录
					$r = Exchange_LccModel::getInstance()->batchInsert($exData);
					if(!$r)
					{
						$autonymMo->back();
						Tool_Fnc::warning(sprintf('空投插入exchange信息失败, data:%s', json_encode(Exchange_LccModel::getInstance()->getError())));
						die('空投插入exchange信息失败');
					}

					//插入user_reward记录
			        $r = UserRewardModel::getInstance()->batchInsert($urData);
			        if(!$r)
			        {
			           	$autonymMo->back();
						Tool_Fnc::warning(sprintf('插入user_reward记录失败, data:%s', json_encode(UserRewardModel::getInstance()->getError())));
						die('插入user_reward记录失败');
			        }

				}
				
				
			}
		}

		$autonymMo->commit();
		exit('done');
	}



	public function ccl()
	{
		$exchangeCllMo = Exchange_CclModel::getInstance();
		$r = $exchangeCllMo->where('bak="空投"')->fRow();
		if($r)
		{
			die('已发放');
		}


		$addTime = strtotime('2018-09-01 15:00');
		

		$beginTime = strtotime('2018-08-07 15:00');
		$endTime = strtotime('2018-09-01 15:00');
		$exList = $exchangeCllMo->field('uid, sum(number) total')->where(sprintf('type=1 and status="成功" and opt_type="in" and bak="" and created>%d and created<%d', $beginTime, $endTime))->group('uid')->order('total desc')->limit(100)->fList();

		if($exList)
		{
			$exchangeCllMo->begin();
			
			foreach($exList as $k=>$v)
			{
				if($k==0)
				{
					$num = 150000;
				}
				elseif($k==1)
				{
					$num = 140000;
				}
				elseif($k==2)
				{
					$num = 130000;
				}
				elseif($k==3)
				{
					$num = 120000;
				}
				elseif($k==4)
				{
					$num = 110000;
				}
				else
				{
					$num = 10000;
				}
	
				//更新用户资金
				$r = UserModel::getInstance()->exec(sprintf('UPDATE `user` SET `ccl_over`=ccl_over+%s WHERE uid = %d', $num, $v['uid']));
				if(!$r)
				{
					$exchangeCllMo->back();
					Tool_Fnc::warning(sprintf('空投更新user信息失败, data:%s', json_encode(UserModel::getInstance()->getError())));
					die('ccl空投更新user信息失败');
				}


				$exData = array(
					'uid' => $v['uid'],
					'admin' => 6,
					'email' => '',
					'wallet' => '',
					'opt_type' => 'in',
					'number' => $num,
					'created' => $addTime,
					'updated' => $addTime,
					'is_out' => 1,
					'createip' => '6.6.6.66',
					'bak' => '空投',
					'status' => '成功',
					'txid' => '',
				);

				$urData = array(
		            'uid'=>$v['uid'],
		            'aid'=>42,
		            'coin'=>'ccl',
		            'created'=>$addTime,
		            'updated'=>$addTime,
		            'number'=>$num,
		        );
				

				//插入转入记录
				$r = $exchangeCllMo->insert($exData);
				if(!$r)
				{
					$exchangeCllMo->back();
					Tool_Fnc::warning(sprintf('ccl空投插入exchange信息失败, data:%s', json_encode(Exchange_LccModel::getInstance()->getError())));
					die('空投插入exchange信息失败');
				}

				//插入user_reward记录
		        $r = UserRewardModel::getInstance()->insert($urData);
		        if(!$r)
		        {
		           	$exchangeCllMo->back();
					Tool_Fnc::warning(sprintf('ccl插入user_reward记录失败, data:%s', json_encode(UserRewardModel::getInstance()->getError())));
					die('插入user_reward记录失败');
		        }		
			}

			
			
		}
		

		$exchangeCllMo->commit();
		exit('done');
	}


	/*
	* 交易送币
	*/
	public function tradeGiftAction()
	{
		$coin = 'ccl';

		$mo = 'Exchange_'.ucfirst($coin).'Model';
		$exchangeMo = $mo::getInstance();
		$r = $exchangeMo->where('bak="活动赠送"')->fRow();
		if($r)
		{
			die('已发放');
		}


		$addTime = time();//strtotime('2018-08-31 15:00');
		
		$beginTime = strtotime('2018-08-07 15:00');
		$endTime = strtotime('2018-09-07 15:00');
		$orderData = Trust_CoinModel::getInstance($coin)->query("select b.email,b.uid,sum(a.number) buy from order_cclcoin a LEFT JOIN user b on a.buy_uid=b.uid  where a.created>1534489200 and a.created<1536303600 GROUP BY a.buy_uid order by buy desc  LIMIT 100");

		$total = 0;
		if($orderData)
		{
			$exchangeMo->begin();
			
			foreach($orderData as $k=>$v)
			{
				if($v['email']=='smartsw@hanmail.net' || $v['email']=='jonead1212@gmail.com')
				{
					continue;
				}

				if($k==0)
				{
					$num = 150000;
				}
				elseif($k==1)
				{
					$num = 140000;
				}
				elseif($k==2)
				{
					$num = 130000;
				}
				elseif($k==3)
				{
					$num = 120000;
				}
				elseif($k==5)
				{
					$num = 110000;
				}
				else{
					$num = 10000;
				}
			
	
				//更新用户资金
				$r = UserModel::getInstance()->exec(sprintf('UPDATE `user` SET `%s_over`=%s_over+%s WHERE uid = %d', $coin, $coin, $num, $v['uid']));
				if(!$r)
				{
					$exchangeMo->back();
					Tool_Fnc::warning(sprintf('活动赠送更新user信息失败, data:%s', json_encode(UserModel::getInstance()->getError())));
					die('活动赠送更新user信息失败');
				}


				$exData = array(
					'uid' => $v['uid'],
					'admin' => 6,
					'email' => '',
					'wallet' => '',
					'opt_type' => 'in',
					'number' => $num,
					'created' => $addTime,
					'updated' => $addTime,
					'is_out' => 1,
					'createip' => '6.6.6.66',
					'bak' => '活动赠送',
					'status' => '成功',
					'txid' => '',
				);

				$urData = array(
		            'uid'=>$v['uid'],
		            'aid'=>42,
		            'coin'=>$coin,
		            'created'=>$addTime,
		            'updated'=>$addTime,
		            'number'=>$num,
		        );
				

				//插入转入记录
				$r = $exchangeMo->insert($exData);
				if(!$r)
				{
					$exchangeMo->back();
					Tool_Fnc::warning(sprintf('活动赠送插入exchange信息失败, data:%s', json_encode(Exchange_LccModel::getInstance()->getError())));
					die('活动赠送插入exchange信息失败');
				}

				//插入user_reward记录
		        $r = UserRewardModel::getInstance()->insert($urData);
		        if(!$r)
		        {
		           	$exchangeMo->back();
					Tool_Fnc::warning(sprintf('活动赠送插入user_reward记录失败, data:%s', json_encode(UserRewardModel::getInstance()->getError())));
					die('活动赠送插入user_reward记录失败');
		        }	

		        $total += $num;	
			}

			
			
		}
		

		$exchangeMo->commit();
		show($total);
		exit('done');
	}
	
}