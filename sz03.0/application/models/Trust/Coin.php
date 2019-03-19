<?php
class Trust_CoinModel extends Orm_Base{
	public $table = 'trust_coin';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'price' => array('type' => "decimal(20,8) unsigned", 'comment' => '单价'),
		'number' => array('type' => "decimal(20,8) unsigned", 'comment' => '数量'),
		'numberover' => array('type' => "decimal(20,8) unsigned", 'comment' => '剩余数量'),
		'numberdeal' => array('type' => "decimal(20,8) unsigned", 'comment' => '成交数量'),
		'flag' => array('type' => "enum('buy','sale')", 'comment' => '买卖标志'),
		'isnew' => array('type' => "enum('Y','N')", 'comment' => '新委托'),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => '状态'),
		'coin_from' => array('type' => "varchar(10)", 'comment' => '要兑换的币'),
		'coin_to' => array('type' => "varchar(10)", 'comment' => '目标兑换'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
		'updateip' => array('type' => "char(15)", 'comment' => '更新ip'),
		'trust_type' => array('type' => "int(11) unsigned", 'comment' => 'trust_type'),
	);
	public $pk = 'id';

	const STATUS_UNSOLD = 0; # 未成交
	const STATUS_PART   = 1; # 部分成交
	const STATUS_ALL    = 2; # 全部成交
	const STATUS_CANCEL = 3; # 已经撤销

	static $status = array('未成交', '部分成交', '全部成交', '已经撤销');

	public function designTable($table){	//chen
       $this->table='trust_'.$table.'coin';
       return $this;
    }
    public function getList($sql){
        return $this->query($sql);
    }
    public function getOne($sql){
        return $this->fRow($sql);
    }

    public function pushInQueue($coinPairName, $data, $type='update')
    {
        $pushData = array(
			't'=>$type,
			'd'=>$data,
		);
		$r = Cache_redis::instance('quote')->lpush('trust_queue_'.$coinPairName, json_encode($pushData));
		return $r;
    }
	/**
	 * 撤销委托
	 * @param $pId
	 * @param $pUser
     * @param $ctype 1富途币，2融资
	 */
	public function cancel($pId, &$pUser, $ctype = 1, $api =false){
		# 开始事务
		$this->begin();
		# 查询委托
		if(!$tTrust = $this->field('uid,number,numberover,price,flag,isnew,status,coin_from,coin_to')->lock()->fRow($pId))
		{
			$this->back();
			return $this->setError($GLOBALS['MSG']['SYS_BUSY']);
		}

		# 用户验证
		if($tTrust['uid'] != $pUser['uid']) 
		{
			$this->back();
			return $this->setError($GLOBALS['MSG']['HAVE_NO_RIGHT']);
		}

		# 状态查询
		if($tTrust['status'] > 1)
		{
			$this->back();
			return $this->setError($GLOBALS['MSG']['CANCEL_FAILED']);
		}

        //买卖
        if($tTrust['flag']=='buy')
        {
            $tMoney = Tool_Math::mul($tTrust['numberover'], $tTrust['price']);
            $tUserData = array($tTrust['coin_to'].'_lock' => Tool_Math::mul('-1', $tMoney), $tTrust['coin_to'].'_over' => $tMoney);
        } 
        else 
        {
            $tUserData = array($tTrust['coin_from'].'_lock' => Tool_Math::mul('-1', $tTrust['numberover']), $tTrust['coin_from'].'_over' => $tTrust['numberover']);
        }
        # 更新用户
        $tMO = new UserModel();
        if(TRUE !== $tMO->safeUpdate($pUser, $tUserData, $api, $tTrust['coin_from'].'_'.$tTrust['coin_to']))
        {
            $this->back();
            return $this->setError($tMO->error[2]);
        }
		# 更新委托
		if(!$tid = $this->update(array('id'=>$pId, 'numberover'=>0, 'isnew'=>'N', 'status'=>self::STATUS_CANCEL, 'updated'=>time(), 'updateip'=>Tool_Fnc::realip()))){
			$this->back();
			return $this->setError($GLOBALS['MSG']['SYS_ERROR']);
		}

//		#写入资产明细
//        $assetDetailMo = AssetDetailModel::getInstance();
//        if(!$assetDetailMo->trustcancel($pUser, $tUserData,$tTrust,$pId,$this->table)){
//            $this->back();
//            return $this->setError($GLOBALS['MSG']['SYS_ERROR']);
//        }

		$this->commit();
		
		//刷新委托列表
		$this->pushInQueue($tTrust['coin_from'].'_'.$tTrust['coin_to'], array(
	            'n'=>Tool_Math::mul($tTrust['numberover'], -1),
	            'f'=>$tTrust['flag'],
	            'p'=>$tTrust['price'],
	            'uid'=>$tTrust['uid'],
	            's'=>self::STATUS_CANCEL,
	            'o'=>0,
	            'id'=>$pId,
	        ));

		Tool_Session::mark($pUser['uid']);

		return true;
	}

	/**
	 * 交易中心批量撤销委托
	 * @param $pId
	 * @param $pUser
	 * @param $ctype
	 */
	public function batchcancel($pId, &$pUser, $ctype = 1, $api = false)
	{
		$where="1=1";
		if($_POST['flag']=='all'){
			$where.='';
		}elseif($_POST['flag'] == 'buy'){
			$where .= " and flag='buy'";
		} elseif ($_POST['flag'] == 'sale') {
			$where .= " and flag='sale'";
		}
		if(!empty($_POST['minprice']) && !empty($_POST['maxprice'])){//price判断
			$where .= " and price BETWEEN $_POST[minprice] and $_POST[maxprice]";
		}elseif(!empty($_POST['minprice']) && empty($_POST['maxprice'])){
			$where .= " and price > $_POST[minprice]";
		} elseif (empty($_POST['minprice']) && !empty($_POST['maxprice'])) {
			$where .= " and price < $_POST[maxprice]";
		}else{
			$where .= "";
		}

		$where.=" and uid=$pUser[uid] and status<2";

		# 开始事务
		$this->begin();
		# 查询委托
		if (!$tTrust = $this->field('id,uid,number,numberover,price,flag,isnew,status,coin_from,coin_to')->where($where)->lock()->fList()) {
			$this->back();
			return $this->setError($GLOBALS['MSG']['NO_ABOUT_TRUST']);
		}
	/*	# 用户验证
		if ($tTrust['uid'] != $pUser['uid']) {
			$this->back();
			return $this->setError($GLOBALS['MSG']['HAVE_NO_RIGHT']);
		}

		# 状态查询
		if ($tTrust['status'] > 1) {
			$this->back();
			return $this->setError($GLOBALS['MSG']['CANCEL_FAILED']);
		}*/
		foreach($tTrust as $key=>$v){
			//买卖
			if ($v['flag'] == 'buy') {
				$tMoney = Tool_Math::mul($v['numberover'], $v['price']);
				$tUserData = array($v['coin_to'] . '_lock' => Tool_Math::mul('-1', $tMoney), $v['coin_to'] . '_over' => $tMoney);
			} else {
				$tUserData = array($v['coin_from'] . '_lock' => Tool_Math::mul('-1', $v['numberover']), $v['coin_from'] . '_over' => $v['numberover']);
			}
			# 更新用户
			$tMO = new UserModel();
			if (TRUE !== $tMO->safeUpdate($pUser, $tUserData, $api)) {
				$this->back();
				return $this->setError($tMO->error[2]);
			}
			# 更新委托
			if (!$this->update(array('id' => $v['id'], 'numberover' => 0, 'isnew' => 'N', 'status' => self::STATUS_CANCEL, 'updated' => time(), 'updateip' => Tool_Fnc::realip()))) {
				$this->back();
				return $this->setError($GLOBALS['MSG']['SYS_ERROR']);
			}

			$this->pushInQueue($v['coin_from'].'_'.$v['coin_to'], array(
	            'n'=>Tool_Math::mul($v['numberover'], -1),
	            'f'=>$v['flag'],
	            'p'=>$v['price'],
	            'uid'=>$v['uid'],
	            's'=>self::STATUS_CANCEL,
	            'o'=>0,
	            'id'=>$v['id'],
	        ));
		}
		$this->commit();
		Tool_Session::mark($pUser['uid']);
		
		return true;
	}
	/**
	 * 后台撤销委托
	 * @param $pId
	 */
	public function adminCancel($pId){
		# 开始事务
		$this->begin();
		# 查询委托
		if(!$tTrust = $this->lock()->fRow("SELECT uid,number,numberover,price,flag,isnew,status,coin_from,coin_to FROM {$this->table} WHERE id=$pId")){
			$this->back();
			Tool_Fnc::ajaxMsg('委托记录不存在');
		}
		# 状态验证
		if(!in_array($tTrust['status'], array(0, 1))) {
			$this->back();
			Tool_Fnc::ajaxMsg('您已不能进行此操作');
		}
		# 剩余查询
		if(($tTrust['numberover'] < 0.0001) || ($tTrust['status'] > 1)){
			$this->back();
			Tool_Fnc::ajaxMsg('撤消失败，委托已经被处理过');
		}

        # 撤销用户信息
        $pUser = array('uid'=>$tTrust['uid']);
        # 买卖
        if($tTrust['flag']=='buy'){
            $tMoney = bcmul(Tool_Str::format($tTrust['numberover'], 20), Tool_Str::format($tTrust['price'], 20), 20);
            $tUserData = array($tTrust['coin_to'].'_lock' => Tool_Math::mul('-1', $tMoney), $tTrust['coin_to'].'_over' => $tMoney);
        } else {
            $tUserData = array($tTrust['coin_from'].'_lock' => Tool_Math::mul('-1', $tTrust['numberover']), $tTrust['coin_from'].'_over' => $tTrust['numberover']);
        }
        # 更新用户
        $tMO = new UserModel();
        if(!$tMO->safeUpdate($pUser, $tUserData)){
            $this->back();
            Tool_Fnc::ajaxMsg($tMO->error[2]);
        }
		# 更新委托
		if(!$this->update(array('id'=>$pId, 'numberover'=>0, 'isnew'=>'N', 'status'=>self::STATUS_CANCEL, 'updated'=>time(), 'updateip'=>Tool_Fnc::realip()))){
			$this->back();
			Tool_Fnc::ajaxMsg('系统错误，请通知管理员 [错误编号:T_C_001]');
		}
		$this->commit();
		Tool_Session::mark($tTrust['uid']);
	}
	/**
	 * 交易
	 */
	public function btc($pData, &$pUser, $api = false){
		# 保存DB
		$this->begin();
		# 买入
		if($pData['type']=='in'){
			$totalPrice = Tool_Math::mul($pData['price'], $pData['number']);
			$coinData = array($pData['coin_to'].'_lock' => $totalPrice, $pData['coin_to'].'_over' => Tool_Math::mul('-1', $totalPrice));
			$pData['type'] = 'buy';
		}
		# 卖出
		else {
			$number = $pData['number'];
			$coinData = array($pData['coin_from'].'_lock' => $number, $pData['coin_from'].'_over' => Tool_Math::mul('-1', $number));
			$pData['type'] = 'sale';
		}
		# 写入资产明细
		$userMo = UserModel::getInstance();
		if(!$userMo->safeUpdate($pUser, $coinData, $api, $pData['coin_from'].'_'.$pData['coin_to'])){
			$this->back();
			return $this->setError($userMo->error[2]);
		}


		# 写入委托
		if(!$tId = $this->insert(array(
			'uid'=>$pUser['uid'],
			'price'=>$pData['price'],
			'number'=>$pData['number'],
			'numberover'=>$pData['number'],
			'flag'=>$pData['type'],
			'status'=>0,
			'coin_from'=>$pData['coin_from'],
			'coin_to'=>$pData['coin_to'],
			'created'=>time(),
			'createip'=>Tool_Fnc::realip()
		))){
			$this->back();
			return $this->setError($GLOBALS['MSG']['SYS_ERROR']);
		}

        #写入资产明细
//        $assetDetailMo = AssetDetailModel::getInstance();
//        if(!$assetDetailMo->setTrust($pUser, $pData,$coinData,$tId,$this->table)){
//            $this->back();
//            return $this->setError($GLOBALS['MSG']['SYS_ERROR']);
//        }

		//写入队列
		$r = $this->pushInQueue($pData['coin_from'].'_'.$pData['coin_to'], array(
            'id'=>$tId,
        ), 'new');

        if(!$r)
        {
        	$this->back();
        	return $this->setError($GLOBALS['MSG']['SYS_ERROR'].'[2]');
        }

        # 提交数据
		$this->commit();
		return $tId;
	}

	 /**
     * 获取符合对应价格的list
     * @param pair array(0=>coin_from,1=>coin_to,2=>pair_id)
     * @param flag buy获取买单list,sale获取卖单list
     *
     * return array()|bool
     */
    public function getListByPrice($price, $pair=array(), $flag='buy', $num=200) {
        if(!is_numeric($price) || !is_array($pair)){
            return false;
        }
        $where = array(
            'buy' => "coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and flag='{$flag}' and isnew='N' and price>={$price} and status<2",
            'sale' => "coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and flag='{$flag}' and isnew='N' and price<={$price} and status<2"
        );
        $order = array(
            'buy' => 'price desc,id asc',
            'sale' => 'price asc,id asc'
        );
        $list = $this->field('id,uid')->where($where[$flag])->order($order[$flag])->limit($num)->fList();
        if(empty($list)){
            return false;
        }
        return $list;
    }


    public function getDealList($price, $pair=array(), $flag='buy') {
        if(!is_numeric($price) || !is_array($pair)){
            return false;
        }
        $where = array(
            'buy' => "coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and flag='{$flag}' and price>={$price} and status<2",
            'sale' => "coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and flag='{$flag}' and price<={$price} and status<2"
        );
        $order = array(
            'buy' => 'price desc,id asc',
            'sale' => 'price asc,id asc'
        );
        $list = $this->field('id,uid,price')->where($where[$flag])->order($order[$flag])->limit(50)->fList();
        if(empty($list)){
            return false;
        }
        return $list;
    }

    /**
     * 成交更改numberover
     *
     */
    public function updateNumber($id, $number, $numberdeal){
        if($number < 1E-9){
            $status = self::STATUS_ALL;
        } else {
            $status = self::STATUS_PART;
        }
        
        $data = array('id'=>$id, 'numberover'=>$number, 'numberdeal'=>$numberdeal, 'isnew'=>'N', 'status'=>$status, 'updated'=>time());
        return $this->update($data);
    }

    /**
     * 根据币名撤销挂单
     * @param  [string]  $coin_from [原来]
     * @param  [string]  $coin_to   [目标]
     * @param  integer $uid       [uid]
     * @return [boolean]             [true/false]
     */
    public function cancleAllByCoin($coin_from, $coin_to, $uid=0) {
    	$where = '';
    	if($uid) {
    		$where .= 'uid='.$uid.' and ';
    	}

    	$where .= "coin_from='".$coin_from."' and coin_to='".$coin_to."' and status in(0, 1) and numberover>0.0001";
		# 查询委托
		// $sql = "SELECT uid,number,numberover,price,flag,isnew,status,coin_from,coin_to FROM {$this->table} WHERE ".$where;
		if(!$tTrustAll = $this->lock()->where($where)->fList()){
			return true;
		}

	    $tMO 	= new UserModel();
	    $ip 	= Tool_Fnc::realip();
	    $redis 	= Cache_Redis::instance();
	    $time 	= time();

	    # 开始事务
		$this->begin();
		foreach ($tTrustAll as $tTrust) {
			# 撤销用户信息
	        $pUser = array('uid'=>$tTrust['uid']);
	        # 买卖
	        if($tTrust['flag']=='buy'){
	            $tMoney 	= bcmul(Tool_Str::format($tTrust['numberover'], 20), Tool_Str::format($tTrust['price'], 20), 20);
	            $tUserData 	= array($tTrust['coin_to'].'_lock' => -$tMoney, $tTrust['coin_to'].'_over' => $tMoney);
	        } else {
	            $tUserData 	= array($tTrust['coin_from'].'_lock' => -$tTrust['numberover'], $tTrust['coin_from'].'_over' => $tTrust['numberover']);
	        }
	        # 更新用户
	        if(!$tMO->safeUpdate($pUser, $tUserData, true)){
	            $this->back();
	            return false;
	        }
			# 更新委托
			if(!$this->update(array('id'=>$tTrust['id'], 'numberover'=>0, 'isnew'=>'N', 'status'=>self::STATUS_CANCEL, 'updated'=>$time, 'updateip'=>$ip))){
				$this->back();
				return false;
			}
			Tool_Session::mark($tTrust['uid']);
		}

		$this->commit();

		return true;

    }


    /**
     * 查询用户是否冻结禁止交易
     */
    public static function getTradeStatus($uid){
    	$forMo = new UserForbiddenModel;
        $fdata = $forMo->lock()->where("uid = {$uid} and status = 0")->fRow();

        if( $fdata ){
        	return $fdata;
        }else{
        	return false;
        }
    }


    /*
     * 查询买一卖一价格
     */
    public function getTradeOnePrice($coin){

        $buy_where = "coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}' and flag='buy' and status=0 and numberover>0";
        $buy_price = $this->where($buy_where)->order("price desc")->fOne('price');

        $sale_where = "coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}' and flag='sale' and status=0 and numberover>0";
        $sale_price = $this->where($sale_where)->order("price asc")->fOne('price');

        $deci = mt_rand($coin['robot_min_deci'],$coin['robot_max_deci']);

//        $diff_price = ($sale_price-$buy_price)/10;

        $number = round(mt_rand($coin['robot_min_num'], $coin['robot_max_num'])-lcg_value(), mt_rand(0, 4));

        $price = round($buy_price+($sale_price-$buy_price)* mt_rand(1, 5) / 10,$deci);

        echo PHP_EOL;
        echo "price".$price.PHP_EOL;


        $trade = [
            'price'=>$price,
            'number'=>$number,
//            'deci'=>$deci
        ];

        return $trade;
    }
}
