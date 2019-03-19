<?php
class ExchangeBaseModel extends Orm_Base{
	public static $calledClass;
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
	);
	public $pk = 'id';


	public function __construct($pPK = 0, $pConfig = 'default', $connect='default')
	{
		$this->setDefaultValues(self::$calledClass);
		parent::__construct($pPK, $pConfig, $connect);
	}
	/**
     * 转出goc
	 * @param $pKey 币地址
	 * @param $coin 币种
	 * @param $pUser 用户
	 * @return array 富途币数据
	 */
	public function post($pKey, $pNum, $coin, &$pUser,$label=''){

		// 判断是否是内转is_out=1，还是外转is_out=0
		$coinInfo = User_CoinModel::getInstance()->where(array('name' => $coin))->fRow();
		$addressMo = new AddressModel();
		$toUid = $addressMo->where(array('address'=>"$pKey",'coin'=> $coin,'status'=>0))->fOne('uid');

		if($toUid) {
			// 内转
			$is_out = 1;
		} else {
			// 外转
            $toUid = 0;
			$is_out = 0;
		}
		#update user
        $tMO = new UserModel();
        $tMO->begin();
        $coin_data  = array($coin.'_lock' => $pNum, $coin.'_over' => Tool_Math::mul(-1, $pNum));
        if(!$tMO->safeUpdate($pUser, $coin_data)){
            $tMO->back();
            return $this->setError($tMO->error[2]);
        }

        //trans
        if($str_len = strpos($coinInfo['rate_out'],'%')){
            $num = substr($coinInfo['rate_out'],0,$str_len);
            $charge = Tool_Math::mul(($num/100), $pNum);
        }else{
            $charge = (float)$coinInfo['rate_out'];
        }

        $exOutData = array(
			'uid'=>$pUser['uid'],
			'admin' => 6,
			'email'=>$pUser['email'],
			'wallet'=>$pKey,
			'opt_type'=>'out',
			'number'=>$pNum,
			'number_real'=>$pNum-$charge,
			'created'=>$_SERVER['REQUEST_TIME'],
			'is_out'  => $is_out,
            'to_uid' => $toUid,
            'platform_fee'=>$charge,
            'createip'=>Tool_Fnc::realip(),
            'label' => $label
		);

		if(isset($r))
		{
			$exOutData['tid'] = $r['result']['tid'];
			$exOutData['status'] = $r['code']==0?'成功':'等待';
			$exOutData['platform_fee'] = $charge;
		}

		if(!$tId = $this->insert($exOutData)){
			Tool_Log::wlog(sprintf('sql error:%s, sql:%s', $this->getError(2), $this->getLastSql()), $errlogdir, true);
			$tMO->back();
			return false;
		}
		PhoneCodeModel::updateCode($pUser,2,$tId);
		# 提交数据
		$tMO->commit();
		Tool_Session::mark($pUser['uid']);
		# 转出GOC，操作用户表
		$_SESSION['user'] = $pUser;

		return array('id'=>$tId, 'created'=>date('Y年m月d日 H:i:s'), 'number'=>$pNum);
	}

	/*
	* 重写该方法，支持属性赋默认值
	*/
	public static function getInstance()
    {
        $class_now = self::$calledClass?:get_called_class();
        if(empty(self::$instance_model[$class_now])){
            self::$instance_model[$class_now] = new $class_now;
        }
        //重置
        self::$calledClass = null;
        self::$instance_model[$class_now]->setDefaultValues($class_now);
        return self::$instance_model[$class_now];
    }


    /*
	* 属性赋默认值
	*/
    protected function setDefaultValues($calledClass)
    {
    	if(!isset($calledClass))
    	{
    		return false;
    	}
    	preg_match('/Exchange_([\d\a-z]+?)Model/i', $calledClass, $match);
        $this->table = $match?('exchange_' . strtolower($match[1])): '';
        return true;
    }
}
