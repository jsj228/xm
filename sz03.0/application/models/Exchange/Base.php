<?php
class Exchange_BaseModel extends ExchangeBaseModel{
	public $table = '';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'email' => array('type' => "char(60)", 'comment' => '用户名'),
		'admin' => array('type' => "int(11) unsigned", 'comment' => '管理员'),
		'wallet' => array('type' => "char(32)", 'comment' => '钱包地址'),
		'txid' => array('type' => "char", 'comment' => ''),
		'confirm' => array('type' => "char", 'comment' => ''),
		'number' => array('type' => "decimal(20,8) unsigned", 'comment' => '数量'),
		'opt_type' => array('type' => "enum('in','out')", 'comment' => '类型'),
		'status' => array('type' => "enum('等待','确认中','成功','已取消','冻结中')", 'comment' => '状态'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),
		'updateip' => array('type' => "char(15)", 'comment' => '修改ip'),
		'bak'   => array('type'=>"char", 'comment'=>''),
		'is_out'   => array('type'=>"tinyint(2)", 'comment'=>'是否是内转： 1是内转，0是外转'),
		'tid'   => array('type'=>"int", 'comment'=>'trans sys 交易序列号'),
		'type'   => array('type'=>"tinyint", 'comment'=>'1 普通转入 2 交易挖矿 3 持币分红'),
		'label'   => array('type'=>"varchar(100) unsigned", 'comment'=>'标签'),
	);
	public $pk = 'id';

	/**
     * 转出goc
	 * @param $pKey 币地址
	 * @param $coin 币种
	 * @param $pUser 用户
	 * @return array 富途币数据
	 */
//	public function post($pKey, $pNum, $coin, &$pUser,$label){
//
//		// 判断是否是内转is_out=1，还是外转is_out=0
//		$coinInfo = User_CoinModel::getInstance()->where(array('name' => $coin))->fRow();
//		$addressMo = new AddressModel();
//		//$toUid = $addressMo->where(array('address'=>"$pKey",'coin'=> $coin))->fOne('uid');
//		$toUid = $addressMo->field('address,label')->where("uid = {$pUser['uid']} and coin = '{$coin}' and status = 0")->fRow();
//	//	show($addressMo->getLastSql());
//		if($toUid)
//		{
//			// 内转
//			$is_out = 1;
//		}
//		else
//		{
//			// 外转
//			$is_out = 0;
//		}
//		#update user
//        $tMO = new UserModel();
//        $tMO->begin();
//        $coin_data  = array($coin.'_lock' => $pNum, $coin.'_over' => Tool_Math::mul(-1, $pNum));
//        if(!$tMO->safeUpdate($pUser, $coin_data)){
//            $tMO->back();
//            return $this->setError($tMO->error[2]);
//        }
//
//        //trans
//        if(Yaf_Registry::get("config")->trans['enabled'])
//        {
//        	$addr = AddressModel::getInstance()->getAddrMap($pUser['uid'], $coin);
//			$charge = Tool_Math::mul($coinInfo['rate_out'], $pNum);
//			$realNum = Tool_Math::sub($pNum, $charge);
//			$reqData = array(
//				"command"=> "trans_normal",
//				"coin"=> $coin,
//				"num"=> $realNum,
//				"checked"=> 1,
//				"charge"=> $charge,
//				"from"=> $addr[$pUser['uid']][$coin],
//				"to"=> $pKey,
//			);
//			$r = Api_Trans_Client::request($reqData);
//			$errlogdir = APPLICATION_PATH .  '/log/exchange/' . date('Ymd');//日志文件
//
//			//转出失败
//			if(!$r || $r['code']<0)
//			{
//				Tool_Log::wlog(sprintf('error:%s, data:%s', json_encode($r), json_encode($reqData)), $errlogdir, true);
//				$tMO->back();
//				//錢包地址錯誤
//				if($r['code']=='-3000')
//					return $this->setError($GLOBALS['MSG']['WALLET_ADDR_ERROR']);
//	            return $this->setError('trans error');
//			}
//			//平台内转出成功
//			elseif($r['code']==0)
//			{
//				//转入记录
//				if(!$tId = $this->insert($inData = array(
//					'uid'=>$toUid,
//					'admin' => 6,
//					'email'=>'',
//					'wallet'=>$pKey,
//					'opt_type'=>'in',
//					'number'=>$realNum,
//					'created'=>$_SERVER['REQUEST_TIME'],
//					'is_out'  => $is_out,
//		            'createip'=>Tool_Fnc::realip(),
//		            'tid'=>0,
//					'label'=>$label,
//		            'status'=>'成功',
//				))){
//					$tMO->back();
//					Tool_Log::wlog(sprintf('sql error:%s, sql:%s', $this->getError(2), $this->getLastSql()), $errlogdir, true);
//					return false;
//				}
//				//转入到用户余额
//				$userMo = UserModel::getInstance();
//				if(!$userMo->exec(sprintf('update user set %s_over=%s_over+%s where uid=%s', $coin, $coin, $realNum, $toUid)))
//				{
//					$tMO->back();
//					Tool_Log::wlog(sprintf('sql error:%s, sql:%s, data:', $userMo->getError(2), $userMo->getLastSql(), $inData), $errlogdir, true);
//					return false;
//				}
//				//转出用户扣除冻结额
//				if(!$userMo->exec(sprintf('update user set %s_lock=%s_lock-%s where uid=%s', $coin, $coin, $pNum, $pUser['uid'])))
//				{
//					$tMO->back();
//					Tool_Log::wlog(sprintf('sql error:%s, sql:%s, data:', $userMo->getError(2), $userMo->getLastSql(), $inData), $errlogdir, true);
//					return false;
//				}
//				//手续费用户
//				if(!$userMo->exec(sprintf('update user set %s_over=%s_over+%s where uid=2', $coin, $coin, $charge)))
//				{
//					$tMO->back();
//					Tool_Log::wlog(sprintf('sql error:%s, sql:%s, data:', $userMo->getError(2), $userMo->getLastSql(), $inData), $errlogdir, true);
//					return false;
//				}
//			}
//        }
//
//
//        $exOutData = array(
//			'uid'=>$pUser['uid'],
//			'admin' => 6,
//			'email'=>$pUser['email'],
//			'wallet'=>$pKey,
//			'opt_type'=>'out',
//			'number'=>$pNum,
//			'created'=>$_SERVER['REQUEST_TIME'],
//			'is_out'  => $is_out,
//			'label'=>$label,
//            'createip'=>Tool_Fnc::realip(),
//		);
//
//		if(isset($r))
//		{
//			$exOutData['tid'] = $r['result']['tid'];
//			$exOutData['status'] = $r['code']==0?'成功':'等待';
//			$exOutData['platform_fee'] = $charge;
//		}
//
//		if(!$tId = $this->insert($exOutData)){
//			Tool_Log::wlog(sprintf('sql error:%s, sql:%s', $this->getError(2), $this->getLastSql()), $errlogdir, true);
//			$tMO->back();
//			return false;
//		}
//		PhoneCodeModel::updateCode($pUser,2,$tId);
//		# 提交数据
//		$tMO->commit();
//		Tool_Session::mark($pUser['uid']);
//		# 转出GOC，操作用户表
//		$_SESSION['user'] = $pUser;
//
//		return array('id'=>$tId, 'created'=>date('Y年m月d日 H:i:s'), 'number'=>$pNum);
//	}
}
