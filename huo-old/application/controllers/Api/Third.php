<?php
/**
 * 第三方對接接口
 */
class Api_ThirdController extends Api_BaseController
{
	/*
	* 第三方平台注册的用户
	*/
	public function ushare()
	{
		$coin = 'read';
		$num  = 10;
		$aid  = 20;
		$ustr = $_POST['ustr'];
		$errlogdir = APPLICATION_PATH .  '/log/ushare/' . date('Ymd');
		if(!preg_match('/^[\d\-\+\|\:]+$/', $ustr))
		{
			$this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
		}
		//判斷活動時間
		$activity = ActivityModel::getInstance()->where("id={$aid} and status=1")->fRow();
		if(!$activity || time()<$activity['start_time'] || time()>$activity['end_time'])
		{
			$this->ajax('活動不存在或已結束');
		}

		$ip = Tool_Fnc::realip();
		$userlist = explode('|', $ustr);
		$userMo = UserModel::getInstance();
		foreach($userlist as $v)
		{
			list($mo, $area) = explode(':', $v);
			$user = $userMo->field('uid,created')->where(['mo'=>$mo, 'area'=>$area])->fRow();
			if($user)
			{
				//註冊時間不在活動範圍
				// if($user['created']<$activity['start_time'] || $user['created']>$activity['end_time'])
				// {
				// 	continue;
				// }
				//檢查實名
				$auth = AutonymModel::getInstance()->where(['uid'=>$user['uid'], 'status'=>2])->fRow();
				if(!$auth)
				{
					continue;
				}
				//已經獎勵過
				if(UserRewardModel::getInstance()->where(['aid'=>$aid, 'uid'=>$user['uid']])->count()>1)
				{
					continue;
				}
				$userMo->begin();
				//更新用户余额
				$r = $userMo->safeUpdate($user, array($coin.'_over'=>10));
				if(!$r)
				{
					$userMo->back();
					Tool_Log::wlog(sprintf("error:%s, data: %s, sql: %s", $userMo->getError(2), json_encode($v), $userMo->getLastSql()), $errlogdir, true);
					continue;
				}

				$now = time();

				//更新来源用户余额
		        $r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', 10, $now, $ip));
		        if (!$r) {
		            $userMo->back();
		            Tool_Log::wlog(sprintf("error:%s, data: %s, sql: %s", $userMo->getError(2), json_encode($v), $userMo->getLastSql()), $errlogdir, true);
					continue;
		        }
				

				//添加转入记录
				$moName = 'Exchange_' . ucfirst($coin) . 'Model';
        		$exchangeMo = $moName::getInstance();
				$exData = array(
	                'uid' => $user['uid'],
	                'admin' => 6,
	                'email' => '',
	                'wallet' => '',
	                'opt_type' => 'in',
	                'number' => $num,
	                'created' => $now,
	                'updated' => $now,
	                'is_out' => 1,
	                'createip' => $ip,
	                'bak' => '注册赠送' . $coin,
	                'status' => '成功',
	                'txid' => '',
	            );
	            if (!$exchangeMo->save($exData)) 
	            {
	            	$userMo->back();
	                Tool_Log::wlog(sprintf("error:%s, data: %s, sql: %s", $exchangeMo->getError(2), json_encode($v), $exchangeMo->getLastSql()), $errlogdir, true);
					continue;
	            }

	            //添加奖励记录
	            $urData = array(
	                'uid' => $user['uid'],
	                'aid' => $aid,
	                'coin' => $coin,
	                'created' => $now,
	                'updated' => $now,
	                'number' => $num,
	                'type'=>2,
	            );
				$r = UserRewardModel::getInstance()->save($urData);
				if(!$r)
				{
					$userMo->back();
					Tool_Log::wlog(sprintf("error:%s, data: %s, sql: %s", UserRewardModel::getInstance()->getError(2), json_encode($v), UserRewardModel::getInstance()->getLastSql()), $errlogdir, true);
					continue;
				}
				$userMo->commit();
			}
			else
			{
				$redis = Cache_Redis::instance();
				$redis->hSet('USHARE', $v, 1);
			}
		}

		$this->ajax('success', 1);
	}


}