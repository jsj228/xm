<?php
/**
 * dst  活动
 *
 */
class Cli_DstController extends Ctrl_Cli
{
	public function runAction()
	{
		$user = new UserModel();
		$coin = 'dst';
		$userinfo = $user->query("select {$coin}_over,uid from user where {$coin}_over >0");   //查出现有dst余额的用户

		foreach ($userinfo as $k=>$v)
		{
			$num =  Cache_Redis::instance()->hget('dsthd',$v['uid']);//防止意外情况，检查是否有过赠送记录

			if($v['uid']==1 ||$v['uid'] ==2 || $v['uid'] ==3 || $v['uid'] ==4 || $v['uid'] ==6 ||$num )  //手续费机器人用户不参与活动
			{
				unset($userinfo[$k]);
				continue;
			}

			$redis  = Cache_Redis::instance()->hSet('dsthd', $v['uid'],$v['dst_over']/100);  //有余额用户存入redis
		}

		exit;
	}

	public function listAction()
	{
		$user = new UserModel();
		$coin = 'dst';
		$userinfo = $user->query("select {$coin}_over,uid from user where {$coin}_over >0");   //查出现有dst余额的用户

		foreach ($userinfo as $k=>$v)
		{
			$num =  Cache_Redis::instance()->hget('dsthd',$v['uid']);

			if($num)
			{
				$now = time();
				$ip = Tool_Fnc::realip();
				$userInfo = $user->where(['uid'=>$v['uid']])->fRow();

				$rewardData = array(
					'uid' => $v['uid'],
					'aid' => 32,
					'coin' => $coin,
					'type'=>2,
					'created' => $now,
					'updated' => $now,
					'number' => $num,
				);
				if(!UserRewardModel::getInstance()->save($rewardData))
				{
					$user->back();
					Tool_Log::wlog(sprintf("返傭記錄插入失敗, uid:%s", $v['uid']), APPLICATION_PATH . '/log/dst', true);

				}
				//添加轉幣記錄
				$moName = 'Exchange_' . ucfirst($coin) . 'Model';
				$exchangeMo = $moName::getInstance();
				$exData = array(
					'uid' => $v['uid'],
					'admin' => 6,
					'email' => '',
					'wallet' => '',
					'opt_type' => 'in',
					'number' =>$num,
					'created' => $now,
					'updated' => $now,
					'is_out' => 1,
					'createip' => $ip,
					'bak' => '空投赠送' . $coin,
					'status' => '成功',
					'txid' => '',
				);
				if (!$exchangeMo->save($exData))
				{
					$user->back();
					Tool_Log::wlog(sprintf("轉幣記錄插入失敗, uid:%s", $v['uid']), APPLICATION_PATH . '/log/dst', true);

				}
				//更新用戶餘額

				$userData  = array($coin.'_over'=>$num);

				if(!UserModel::getInstance()->safeUpdate($userInfo, $userData))
				{
					$user->back();
					Tool_Log::wlog(sprintf("更新用戶余额失败, uid:%s", $v['uid']), APPLICATION_PATH . '/log/dst', true);

				}

				//更新来源用户余额
				$r = $user->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $num, $now, $ip,$v['uid']));

				if (!$r) {
					$user->back();
					Tool_Log::wlog(sprintf("更新来源用戶余额失败, uid:%s", $v['uid']), APPLICATION_PATH . '/log/dst', true);
				}
			}
		}

		exit;

	}

}