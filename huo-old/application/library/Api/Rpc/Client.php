<?php
class Api_Rpc_Client{
	private $debug;
	private $url;
	private $id;
	private $notification = false;

	public function __construct($url, $debug = false){
		$this->url = $url;
		empty($proxy)? $this->proxy = '': $this->proxy = $proxy;
		empty($debug)? $this->debug = false: $this->debug = true;
		$this->id = 1;
	}

	public function setRPCNotification($notification){
		$this->notification = !empty($notification);
	}

	public function __call($method, $params){

		if(!is_scalar($method)){
			throw new Exception('Method name has no scalar value');
		}
		if(is_array($params)){
			$params = array_values($params);
		}
		else{
			throw new Exception('Params must be given as array');
		}
		if($this->notification){
			$currentId = null;
		}
		else{
			$currentId = $this->id;
		}
		$request = array('method' => $method, 'params' => $params, 'id' => $currentId);
		$request = json_encode($request);
		$this->debug && $this->debug .= '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****' . "\n\n";
		$opts = array('http' => array('method' => 'POST', 'header' => 'Content-type: application/json', 'content' => $request));
		$context = stream_context_create($opts);
		if($fp = fopen($this->url, 'r', false, $context)){
			$response = '';
			while($row = fgets($fp)){
				$response .= trim($row) . "\n";
			}
			$this->debug && $this->debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
			$response = json_decode($response, true);
		}
		else{
			throw new Exception('钱包地址错误或官方钱包维护');
		}
		if($this->debug){
			echo nl2br($this->debug);
		}
		if(!$this->notification){
			if($response['id'] != $currentId){
				throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
			}
			if(!is_null($response['error'])){
				throw new Exception('Request error: ' . $response['error']);
			}
			return $response['result'];
		}
		else{
			return true;
		}
	}

	static function getAddrByCache($pKey, $pCache = 0, $coin){
        if(empty($coin)){
            return FALSE;
        }
		$tRedis = &Cache_Redis::instance();
		if($pCache && $tAddr = $tRedis->hget($coin.'addr', $pKey)){
			return $tAddr;
		}
		if($pCache && $tAddr = $tRedis->hget($coin.'addrnew', $pKey)){
			return $tAddr;
		}
		if(1 == $pCache){
			return false;
        }
		$tARC = new Api_Rpc_Client(Yaf_Application::app()->getConfig()->api->rpcurl->$coin);
		$tAddr = $tARC->getnewaddress($pKey);
		$tRedis->hset($coin.'addrnew', $pKey, $tAddr);
        #dumpprivkey
        #$key_priv   = $tARC->dumpprivkey($tAddr);
        #file_put_contents('/var/www/ybcoin_log/addrkeys.log', "{$pKey} {$tAddr} {$key_priv} ".date('YmdHi')." \n", FILE_APPEND);
		return $tAddr;
	}
    /**
     * @desc 获取游戏coin地址
     */
    static function getAddrForGame($key, $coin, $cache){
        if(empty($coin)){
            return FALSE;
        }
		$redis = &Cache_Redis::instance('gameaddr');
		if($cache && $addr = $redis->hget($coin.'gameaddr', $key)){
			return $addr;
		}
		if(1 == $pCache){
			return false;
        }
		$rpc = new Api_Rpc_Client(Yaf_Application::app()->getConfig()->api->rpcurl->$coin);
		$addr = $rpc->getnewaddress($key);
        $redis->hSet($coin.'gameaddr', $key, $addr);
        return $addr;
    }

    static function getBalance($coin){
        $allow_coin = array('goc'=>0, 'gocin'=>2, 'gocout'=>3);
        if(empty($coin) || !isset($allow_coin[$coin])){
            return FALSE;
        }
		$tARC = new Api_Rpc_Client(Yaf_Application::app()->getConfig()->api->rpcurl->$coin);
		$balance    = $tARC->getinfo();
        return (empty($balance) ? 0 : $balance['balance']);
    }

	static function sendToUserAddress($address, $amount, $coin){
		if(!$tConfig = Yaf_Application::app()->getConfig()->api->rpcurl->$coin){
			return false;
		}
		$tARC = new Api_Rpc_Client($tConfig);
        #$amount = bcsub($amount, 0.0001, 4);
        #$tARC->settxfee(0.0001);

		$tTxid = $tARC->sendtoaddress($address, $amount);
		if(strlen($tTxid) != 64 && FALSE !== strpos($tTxid, 'error')){
			return false;
		}
		return $tTxid;
	}
}
