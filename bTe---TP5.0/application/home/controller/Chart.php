<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Debug;

class Chart extends HomeCommon
{
	public function getJsonData($market = NULL, $ajax = 'json')
	{
		if ($market) {
			$data = [];
			if (!$data) {
				$data[2] = $this->getTradeLog($market);
				Cache::store('redis')->set('ChartgetJsonData' . $market, $data);
			}
			exit(json_encode($data));
		}
	}

	protected function getTradeBuy($market)
	{
		$mo = Db::name('chart');

		$buy = $mo->query('select id,price,sum(num-deal)as nums from hs_trade  where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit 100;');
		$data = '';

		if ($buy) {
			$maxNums = maxArrayKey($buy, 'nums') / 2;

			foreach ($buy as $k => $v) {
				$data .= '<tr><td class=\'buy\'  width=\'50\'>买' . ($k + 1) . '</td><td class=\'buy\'  width=\'80\'>' . floatval($v['price']) . '</td><td class=\'buy\'  width=\'120\'>' . floatval($v['nums']) . '</td><td  width=\'100\'><span class=\'buySpan\' style=\'width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;\' ></span></td></tr>';
			}
		}

		return $data;
	}

	protected function getTradeSell($market)
	{
		$mo = Db::name('chart');
		$sell = $mo->query('select id,price,sum(num-deal)as nums from hs_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit 100;');
		$data = '';

		if ($sell) {
			$maxNums = maxArrayKey($sell, 'nums') / 2;

			foreach ($sell as $k => $v) {
				$data .= '<tr><td class=\'sell\'  width=\'50\'>卖' . ($k + 1) . '</td><td class=\'sell\'  width=\'80\'>' . floatval($v['price']) . '</td><td class=\'sell\'  width=\'120\'>' . floatval($v['nums']) . '</td><td style=\'width: 100px;\'><span class=\'sellSpan\' style=\'width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;\' ></span></td></tr>';
			}
		}

		return $data;
	}

	protected function getTradeLog($market)
	{
		$log = Db::name('TradeLog')->where(['status' => 1, 'market' => $market])->order('id desc')->limit(100)->select();
		$data = '';

		if ($log) {
			foreach ($log as $k => $v) {
				if ($v['type'] == 1) {
					$type = 'buy';
				}
				else {
					$type = 'sell';
				}

				$data .= '<tr><td class=\'' . $type . '\'  width=\'70\'>' . date('H:i:s', $v['addtime']) . '</td><td class=\'' . $type . '\'  width=\'70\'>' . floatval($v['price']) . '</td><td class=\'' . $type . '\'  width=\'100\'>' . floatval($v['num']) . '</td><td class=\'' . $type . '\'>' . floatval($v['mum']) . '</td></tr>';
			}
		}
		return $data;
	}

	public function trend()
	{
		$input = input('get.');
		$market = (is_array(config('market')[$input['market']]) ? trim($input['market']) : config::get('market_mr'));
		$this->assign('market', $market);
		 return $this->fetch();
	}

	public function getMarketTrendJson()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(config('market')[$input['market']]) ? trim($input['market']) : config::get('market_mr'));
		$data = (APP_DEBUG ? null : Cache::store('redis')->get('ChartgetMarketTrendJson' . $market));

		if (!$data) {
			$data = Db::name('TradeLog')->where([
				'market'  => $market,
				'addtime' => ['gt', time() - (60 * 60 * 24 * 30 * 2)]
			])->select();
			Cache::store('redis')->set('ChartgetMarketTrendJson' . $market, $data);
		}

		$json_data = [];
		foreach ($data as $k => $v) {
			$json_data[$k][0] = $v['addtime'];
			$json_data[$k][1] = $v['price'];
		}

		exit(json_encode($json_data));
	}

	public function ordinary()
	{
		$input = input('get.');
		$market = (is_array(config('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$this->assign('market', $market);
		 return $this->fetch();
	}

	public function getMarketOrdinaryJson()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(config('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$timearr = array(1, 5, 15, 30, 60, 360, 720, 1440, 10080);

		if (in_array($input['time'], $timearr)) {
			$time = $input['time'];
		}
		else {
			$time = 5;
		}

		$timeaa = (APP_DEBUG ? null : Cache::store('redis')->get('ChartgetMarketOrdinaryJsontime' . $market . $time));

		if (($timeaa + 60) < time()) {
			Cache::rm('ChartgetMarketOrdinaryJson' . $market . $time); 
			Cache::store('redis')->set('ChartgetMarketOrdinaryJsontime' . $market . $time, time());
		}

		$tradeJson = (APP_DEBUG ? null : Cache::store('redis')->get('ChartgetMarketOrdinaryJson' . $market . $time));

		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(array(
				'market' => $market,
				'type'   => $time,
				'data'   => ['neq', '']
			))->order('id desc')->limit(100)->select();
			Cache::store('redis')->set('ChartgetMarketOrdinaryJson' . $market . $time, $tradeJson);
		}

		krsort($tradeJson);

		$json_data = array();
		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v['data'], true);
		}

		exit(json_encode($json_data));
	}

	public function specialty()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(config('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$this->assign('market', $market);
		 return $this->fetch();
	}

	public function getMarketSpecialtyJson()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(config::get('market')[$input['market']]) ? trim($input['market']) : config::get('market_mr'));
		$timearr = array(1, 5, 15, 30, 60, 360, 720, 1440, 10080);

		if (in_array($input['step'] / 60, $timearr)) {
			$time = $input['step'] / 60;
		}
		else {
			$time = 5;
		}

		$timeaa = (APP_DEBUG ? null : Cache::store('redis')->get('ChartgetMarketSpecialtyJsontime' . $market . $time));
		if (($timeaa + 60) < time()) {
			Cache::rm('ChartgetMarketSpecialtyJson' . $market . $time);
			Cache::store('redis')->set('ChartgetMarketSpecialtyJsontime' . $market . $time, time());
		}

		$tradeJson = (APP_DEBUG ? null : Cache::store('redis')->get('ChartgetMarketSpecialtyJson' . $market . $time));

		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(['market' => $market, 'type'   => $time, 'data'   => array('neq', '')])
				->order('id asc')->limit(100)->select();
				Cache::store('redis')->set('ChartgetMarketSpecialtyJson' . $market . $time, $tradeJson);

		}

		$json_data = $data = array();
		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v['data'], true);
		}
		foreach ($json_data as $k => $v) {
			$data[$k][0] = $v[0];
			$data[$k][1] = 0;
			$data[$k][2] = 0;
			$data[$k][3] = $v[2];
			$data[$k][4] = $v[5];
			$data[$k][5] = $v[3];
			$data[$k][6] = $v[4];
			$data[$k][7] = $v[1];
		}
		exit(json_encode($data));
	}

	public function getSpecialtyTrades()
	{
		$input = input('get.');

		if (!$input['since']) {
			$tradeLog = Db::name('TradeLog')->where(['market' => $input['market']])->order('id desc')->find();
			$json_data[] = [
				'tid' => $tradeLog['id'],
				'date' => $tradeLog['addtime'],
				'price' => $tradeLog['price'],
				'amount' => $tradeLog['num'],
				'trade_type' => $tradeLog['type'] == 1 ? 'bid' : 'ask'
			];
			exit(json_encode($json_data));
		} else {
			$tradeLog = Db::name('TradeLog')->where(['market' => $input['market'], 'id' => ['gt', $input['since']]])->order('id desc')->select();
			$json_data = array();
			foreach ($tradeLog as $k => $v) {
				$json_data[] = [
					'tid' => $v['id'],
					'date' => $v['addtime'],
					'price' => $v['price'],
					'amount' => $v['num'],
					'trade_type' => $v['type'] == 1 ? 'bid' : 'ask'
				];
			}

			exit(json_encode($json_data));
		}
	}

	public function test()
	{

		$input = input('get.');
		$timearr = array(1, 5, 15, 30, 60, 360, 720, 1440, 10080);

		if (in_array($input['step'] / 60, $timearr)) {
			$time = $input['step'] / 60;
			$tradeJsonTime=Db::name('TradeJson')->where(['market' => $input['market'],'type'=>$time])->order('id desc')->value('addtime');
			$btime=$tradeJsonTime;
			$etime=$tradeJsonTime+$input['step'];
			if(!$input['since']){
				$tradeLog = Db::name('TradeJsonLog')->where(['market' => $input['market']])->order('id desc')->select();
				foreach($tradeLog as $key =>$value){
					if($value['addtime']>$etime || $value['addtime']==$etime){
						$addJson[]=$value;
					}elseif($value['addtime']>$btime || $value['addtime']=$btime && $value['addtime']<$etime){
						$upJson[]=$value;
					}
				}
			}
		}

		if (!$input['since']) {
			$tradeLog = Db::name('TradeJsonLog')->where(['market' => $input['market']])->order('id desc')->select();
			foreach($tradeLog as $key =>$value){
				$json_data[$key][]=$value;
			}
		}
	}

}
