<?php
/**
 * 推送交易数据到mytoken
 *
 */
class Cli_SendtomytokenController extends Ctrl_Cli
{
	protected $url; // = 'http://matrix.api.mytoken.io';//正式
	protected $token; // = 'kRIGbG+P/UnAN3uwheJjxSL/doP5Sw4Y/aq68LBO0aU=';//正式
	//protected $url = 'http://matrix.beta.mytoken.io';//测试
	//protected $token = 'yN2UojiLmJiF7UG7+SFdB0zago7G375B/mkU+WBrwv8=';//测试

	public function init()
	{
		$conf = Yaf_Registry::get("config")->mytoken->toArray();
		$this->url = $conf['url'];
		$this->token = $conf['token'];
	}

	//获取token
	public function testAction()
	{die;
		$url = 'http://matrix.beta.mytoken.io/api/v1/markets';
		$json = '{
  "name": "火網1",
  "website": "https://www.huocoin.com",
  "contact": "service@huocoin.com"
}';
		$headers = $headers = array('accept: application/json', 'Content-Type: application/json');
		$strResult = $this->callInterfaceCommon($url, "POST", $json, $headers);
		show($strResult);
	}
	//2提交币信息  添加币种
	public function addcoinAction()
	{
		$redisjson = Cache_Redis::instance('common')->get('mytokencoinlist');//mytoken已添加的币列表记录
		if(empty($redisjson)){
			Cache_Redis::instance('common')->set('mytokencoinlist',json_encode(array()));//添加一个空数组
			$redisjson = Cache_Redis::instance('common')->get('mytokencoinlist');//mytoken已添加的币列表记录
		}
		$mytokencoin=json_decode($redisjson, true);

		$url = $this->url. '/api/v1/tokens';
		//$json = '{"symbol": "RSS","name": "红贝壳","unique_key": "RSS"}';
		$token = $this->token;//找官方要
		$headers = $headers = array('accept: application/json', 'Content-Type: application/json', "X-API-key:$token");
		$coinmo = new User_CoinModel();
		$data = $coinmo->field('name,display')->where('status=0')->fList();

		foreach ($data as $v) {
			$name = strtoupper($v['name']);
			if (in_array($name, $mytokencoin)) {
				continue;//已存在跳过
			}
			$arr = array(
				'symbol' => $name,
				'name' => $name,
				'unique_key' => $name
			);
			$json = json_encode($arr);
			$strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);

			$rarr = json_decode($strResult, true);
			if (isset($rarr['name'])&& $rarr['name']== $name) {//添加成功
				array_push($mytokencoin, $name);
				Cache_Redis::instance('common')->set('mytokencoinlist', json_encode($mytokencoin));
				continue;
			}
			if ($rarr['code'] == 400 && $rarr['message'] == 'Market has already been taken') {
				array_push($mytokencoin, $name);
				Cache_Redis::instance('common')->set('mytokencoinlist', json_encode($mytokencoin));
			}
		}
		//print_r('coin token 创建完成。。');
	}
	//ticker创建 添加交易币种 mcc_btc
	public function addtradeAction()
	{
		$redisjson = Cache_Redis::instance('common')->get('mytokentradelist');//mytoken已添加的交易区记录
		if (empty($redisjson)) {
			Cache_Redis::instance('common')->set('mytokentradelist', json_encode(array()));//添加一个空数组
			$redisjson = Cache_Redis::instance('common')->get('mytokentradelist');//mytoken已添加的交易区记录
		}
		$mytokentrade = json_decode($redisjson, true);

		$url = $this->url .'/api/v1/tickers';
		$token = $this->token;//找官方要
		$headers = $headers = array('accept: application/json', 'Content-Type: application/json', "X-API-key:$token");
		$coinmo = new Coin_PairModel();
		$data = $coinmo->field('coin_from,coin_to')->where('status=1')->fList();
		foreach ($data as $v) {
			$coin_from= strtoupper($v['coin_from']);
			$coin_to = strtoupper($v['coin_to']);
			$trade = $coin_from.'_'. $coin_to;
			if (in_array($trade, $mytokentrade)) {
				continue;//已存在跳过
			}
			$j = $v['coin_from'] . '_' . $v['coin_to'] . '_quote';
			$redisjson = Cache_Redis::instance('quote')->get($j);//获取最小成交价，交易量
			$redisarr = json_decode($redisjson, true);

			$arr = array('ticker' => array(
				'symbol_key' => $coin_from,//交易币种
				'symbol_name' => $coin_from,
				'anchor_key' => $coin_to,//交易区
				'anchor_name' => $coin_to,
				'price' => $redisarr['price'],//成交价
				'price_updated_at' => date('Y-m-d H:i:s', time()),
				'volume_24h' => $redisarr['amount'],//成交量
				'volume_anchor_24h' => $redisarr['money'],//成交额
			));
			$json = json_encode($arr);

			$strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);
			$rarr = json_decode($strResult, true);
			if (isset($rarr['symbol_key'], $rarr['anchor_key'])&& $rarr['symbol_key']== $coin_from&& $rarr['anchor_key'] == $coin_to) {//添加成功
				array_push($mytokentrade, $trade);
				Cache_Redis::instance('common')->set('mytokentradelist', json_encode($mytokentrade));
			}
		}
		//print_r('coin ticker 创建完成。。');
		//$json = '{"ticker": {"symbol_key": "ltc","symbol_name": "莱特币","anchor_key": "btc","anchor_name": "比特币","price": 220,"price_updated_at": "1520235288","volume_24h": 1110,"volume_anchor_24h": 4440}}';

	}

	//ticker更新 更新交易数据 mcc_btc
	public function updatetradeAction()
	{
		$url = $this->url .'/api/v1/tickers/batch_create';
		$token = $this->token;//找官方要
		$headers = $headers = array('accept: application/json', 'Content-Type: application/json', "X-API-key:$token");
		$coinmo = new Coin_PairModel();
		$data = $coinmo->field('coin_from,coin_to')->where('status=1')->fList();
		foreach ($data as $v) {
			$j = $v['coin_from'] . '_' . $v['coin_to'] . '_quote';
			$redisjson = Cache_Redis::instance('quote')->get($j);//获取最小成交价，交易量
			$redisarr = json_decode($redisjson, true);
			$arr['tickers'][] = array(
				'symbol_key' => strtoupper($v['coin_from']),//交易币种
				'symbol_name' => strtoupper($v['coin_from']),
				'anchor_key' => strtoupper($v['coin_to']),//交易区
				'anchor_name' => strtoupper($v['coin_to']),
				'price' => $redisarr['price'],//成交价
				'price_updated_at' => date('Y-m-d H:i:s', time()),
				'volume_24h' => $redisarr['amount'],//成交量
				'volume_anchor_24h' => $redisarr['money'],//成交额
			);


		}
		$json = json_encode($arr);
		//show($json);
		$strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);
		$rarr = json_decode($strResult, true);
		if($rarr['code']==201){
			//print_r('coin ticker 批量创建成功。。');
		}else{
			echo sprintf('[%s] %s', date('Y-m-d H:i:s'), $strResult).PHP_EOL;
		}

	}

	public function runAction()
	{
		while(1){
			$this->addcoinAction();
			$this->addtradeAction();
			$this->updatetradeAction();
			//print_r('。。等待。。');
			sleep(10);
		}
	}

}