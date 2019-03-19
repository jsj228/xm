<?php

class AddressModel extends Orm_Base
{
	public $table = 'address';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => '用户id'),
		'address' => array('type' => "char", 'comment' => '钱包地址'),
		'coin' => array('type' => "char", 'comment' => '币种'),
		'secret' => array('type' => "char", 'comment' => '用户密码'),
		'publicKey' => array('type' => "char", 'comment' => '用户公钥'),
		'status' => array('type' => "int", 'comment' => '0 显示 , 1 删除'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated'=>array('type'=>"int",'comment'=>''),
		'label'=>array('type'=>"varchar(100) unsigned",'comment'=>'标签')
	);
	public $pk = 'id';

	public function getAddr($uid, $coin)
	{
		if (!$addr = $this->field('address,label')->where("uid = {$uid} and coin = '{$coin}' and status = 0")->fRow()) {

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            if (empty($rpcurl)) return false;

            $params = '{"jsonrpc":"2.0","method":"personal_newAccount", "params":["bjs88888"],"id":1}';
            $headers   = array('Content-type: application/json');

            $strResult = Tool_Fnc::callInterfaceCommon($rpcurl, "POST", $params, $headers);
            $strResult = json_decode($strResult, true);

            if (isset($strResult['error'])) return false;

            if ($coin == 'lcc') {//lcc参数不一样
                $addrs = $strResult['address'];
            } else {
                $addrs = $strResult['result'];
            }
            $insertData = array(
                'uid' => $uid,
                'address' => $addrs,
                'coin'    => $coin,
                'created' => time(),
                'secret' =>'bjs88888'
            );

            if (!$this->insert($insertData)) return false;
            return $addrs;
		}

        return $addr;
	}

	public function getAddr1($uid, $coin){

		if (!$addr = $this->where("uid = {$uid} and coin = '{$coin}' and status = 0")->fOne('address'))
		{
			$rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;

			if (empty($rpcurl)) return false;
			$rpcMo = new Api_Rpc_Client($rpcurl);

            //生成地址
            try
            {
                $addr = $rpcMo->getnewaddress();
            }
            catch (Exception $e)
            {
                return false;
            }

            if (empty($addr) || strlen($addr) < 31) return false;

            if ($this->insert(array(
                'uid'     => $uid,
                'coin'    => $coin,
                'address' => $addr,
                'created' => time()
            ))
            )
            {
                return $addr;
            }
            else
            {
                return false;
            }
		}
		else
		{
			return $addr;
		}

		return false;
	}

	//XRP
	public function getAddrXrp($uid, $coin){
        $addr = CoinModel::getInstance()->where(['name'=>$coin,'status'=>0])->fOne('account');
        $user_address = $this->where("uid = '{$uid}' and coin = '{$coin}' and status = 0")->field('id,address,label')->fRow();
        if(isset($user_address) && $user_address && $user_address['label']){
            $wallet = ['address'=>$addr,'label'=>$user_address['label']];
        }

        if(!$user_address){
            $xrp_len = 9-strlen($uid);
            $min = pow(10 , ($xrp_len - 1));
            $max = pow(10, $xrp_len) - 1;
            $xrp_str =  mt_rand($min, $max);
            $label = $uid.$xrp_str;
            if (!$this->insert(['uid' => $uid, 'coin' => $coin, 'created' => time(), 'label' => $label])) return false;
            $wallet = ['address'=>$addr,'label'=>$label];
        }

        if(isset($user_address) && $user_address && !$user_address['label']){
            $xrp_len = 9-strlen($uid);
            $min = pow(10 , ($xrp_len - 1));
            $max = pow(10, $xrp_len) - 1;
            $xrp_str =  mt_rand($min, $max);
            $label = $uid.$xrp_str;
            if(!$this->update(["id"=>$user_address['id'],'label'=>$label])) return false;
            $wallet = ['address'=>$addr,'label'=>$label];
        }

        return $wallet;

    }

    //EOS
    public function getAddrEos($uid,$coin){

        $addr = CoinModel::getInstance()->where(['name'=>$coin,'status'=>0])->fOne('account');
        $user_address = $this->where("uid = '{$uid}' and coin = '{$coin}' and status = 0")->field('id,address,label')->fRow();
        if(isset($user_address) && $user_address && $user_address['label']){
            $wallet = ['address'=>$addr,'label'=>$user_address['label']];
        }

        if(!$user_address){
            $tool = new Tool_Validate();
            $label = $tool->eos();
            if (!$this->insert(['uid' => $uid, 'coin' => $coin, 'created' => time(), 'label' => $label])) return false;
            $wallet = ['address'=>$addr,'label'=>$label];
        }

        if(isset($user_address) && $user_address && !$user_address['label']){
            $tool = new Tool_Validate();
            $label = $tool->eos();
            if(!$this->update(["id"=>$user_address['id'],'label'=>$label])) return false;
            $wallet = ['address'=>$addr,'label'=>$label];
        }

        return $wallet;
    }

    //Btm系列
    public function getAddrBtm($uid,$coin){
        if(!$address = $this->where(['uid'=>$uid,'coin'=>$coin,'status'=>0])->fOne('address')){
            if ($coinConf = CoinModel::getInstance()->where(['type'=>'btm'])->fRow()){
                $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
                $str = mb_substr($rpcurl, mb_strlen('http://'));
                $address = mb_substr($str, 0, mb_strpos($str, ':'));
                $port = mb_substr($str, mb_strpos($str, ':') + 1);
                $btmClient = new Api_Rpc_BtmClient($address,$port, reset(explode('-',$coinConf['account'])),$coinConf['password'], end(explode('-', $coinConf['account'])),$coinConf['token_address']);
                if(!$address = $btmClient->createAcountAddress()) return false;
                if(!$this->insert(['uid'=>$uid,'address'=>$address,'coin'=>$coin,'created'=>time()])) return false;
            }
        }
        return $address;
    }

    //Rgb系列
    public function getAddrRgb($coin){
        $addrs = CoinModel::getInstance()->where(['name'=>$coin,'status'=>0])->fOne('account');
        $addrs = explode(',',$addrs);
        if(count($addrs)==2){
            $wallet = ['address'=>$addrs[0],'label'=>$addrs[1]];
        }else{
            $wallet = $addrs[0];
        }
        return $wallet;
    }

	/*
	 * 获取uid,钱包地址映射
	*/
	public function getAddrMap($uid, $coin)
	{
		//查询缓存
		static $cache;
		$cKey = md5(json_encode($uid).json_encode($coin));
		if(isset($cache[$cKey]))
		{
			return $cache[$cKey];
		}

		$where = '';
		if(is_array($uid))
			$where .= sprintf('uid in (%s) ', implode(',', $uid));
		else
			$where .= sprintf('uid = %s ', $uid);

		if(is_array($coin))
			$where .= sprintf(' and coin in ("%s") ', implode('","', $coin));
		
		else
			$where .= sprintf(' and coin = "%s" ', $coin);


		$address = $this->field('address, uid, coin')->where($where)->fList();
		$addrMap  = array();
		$uid = (array)$uid;
		foreach($uid as $uidOne)
		{	
			$addrMap[$uidOne] = array();
		}

		foreach($address as $v)
		{
			$addrMap[$v['uid']][$v['coin']] = $v['address'];
		}

		//没有地址的新生成地址
		$coin = (array)$coin;
		foreach ($addrMap as $uid=>$v) 
		{
			foreach($coin as $coinOne)
			{
				if(!isset($v[$coinOne]))
				{
					$newAddrOne = $this->createAddr($uid, $coinOne);
					if(!$newAddrOne)
						return false;
					
					$addrMap[$uid][$coinOne] = $newAddrOne;
				}
			}
		}
		//缓存
		$cache[$cKey] = $addrMap;
		return $addrMap;
		
	}

	/*
	* 创建新地址
	*/
	public function createAddr($uid, $coin)
	{
		$reqData = array(
            'command'=>'apply_addr',
            "coin"=> $coin,
        );

        $result = Api_Trans_Client::request($reqData);
        if($result && $result['code']==0)
        {
        	$r = $this->insert(array(
				'uid'     => $uid,
				'coin'    => $coin,
				'address' => $result['result']['addr'],
				'created' => time()
			));
			if($r)
				return $result['result']['addr'];
        }
        else
        {
        	$this->setError(sprintf('coin:%s, errorCode:%s, msg:%s', $coin, $result['code'], $result['msg']));
        }
        return false;
	}

}
