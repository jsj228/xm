<?php
namespace app\home\controller;

use think\Db;

class Chart extends Home
{
	public function getJsonData()
	{
        $market = input('market/s', NULL);
		if ($market) {
			$data = cache('ChartgetJsonData' . $market);

			$weike_getCoreConfig = weike_getCoreConfig();
			if(!$weike_getCoreConfig){
				$this->error('核心配置有误');
			}else{
				$weike_putong = $weike_getCoreConfig['weike_userTradeDetailNum'];
				$weike_teshu = $weike_getCoreConfig['weike_specialUserTradeDetailNum'];
			}
			$limit = $weike_putong;
			if($userid = userid()){
				$usertype = Db::name('User')->where(array('id' => $userid))->value('usertype');
				if($usertype ==1){
					$limit = $weike_teshu;
				}else{
					$limit = $weike_putong;
				}
			}

			if (!$data) {
				$data[0] = $this->getTradeBuy($market,$limit);
				$data[1] = $this->getTradeSell($market,$limit);
				$data[2] = $this->getTradeLog($market,$limit);
				cache('ChartgetJsonData' . $market, $data);
			}

			exit(json_encode($data));
		}
	}

	private function getTradeBuy($market,$limit)
	{
        $buy = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit($limit)->select();
		$data = '';

		if ($buy) {
			$maxNums = maxArrayKey($buy, 'nums') / 2;

			foreach ($buy as $k => $v) {
				$data .= '<tr><td class=\'buy\'  width=\'50\'>买' . ($k + 1) . '</td><td class=\'buy\'  width=\'80\'>' . floatval($v['price']) . '</td><td class=\'buy\'  width=\'120\'>' . floatval($v['nums']) . '</td><td  width=\'100\'><span class=\'buySpan\' style=\'width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;\' ></span></td></tr>';
			}
		}

		return $data;
	}

    private function getTradeSell($market,$limit)
	{
		$limit = intval($limit);
        $sell = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit($limit)->select();
		$data = '';

		if ($sell) {
			$maxNums = maxArrayKey($sell, 'nums') / 2;

			foreach ($sell as $k => $v) {
				$data .= '<tr><td class=\'sell\'  width=\'50\'>卖' . ($k + 1) . '</td><td class=\'sell\'  width=\'80\'>' . floatval($v['price']) . '</td><td class=\'sell\'  width=\'120\'>' . floatval($v['nums']) . '</td><td style=\'width: 100px;\'><span class=\'sellSpan\' style=\'width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;\' ></span></td></tr>';
			}
		}

		return $data;
	}

    private function getTradeLog($market,$limit)
	{
		$log = Db::name('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit($limit)->select();
		$data = '';

		if ($log) {
			foreach ($log as $k => $v) {
				if ($v['type'] == 1) {
					$type = 'buy';
				} else {
					$type = 'sell';
				}

				$data .= '<tr><td class=\'' . $type . '\'  width=\'70\'>' . date('H:i:s', $v['addtime']) . '</td><td class=\'' . $type . '\'  width=\'70\'>' . floatval($v['price']) . '</td><td class=\'' . $type . '\'  width=\'100\'>' . floatval($v['num']) . '</td><td class=\'' . $type . '\'>' . floatval($v['mum']) . '</td></tr>';
			}
		}

		return $data;
	}

	public function trend()
	{
		// TODO: SEPARATE
		$input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$this->assign('market', $market);
		return $this->fetch();
	}

	public function getMarketTrendJson()
	{
		// TODO: SEPARATE
		$input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$data = cache('ChartgetMarketTrendJson' . $market);

		if (!$data) {
			$data = Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24 * 30 * 2))
				))->select();
			cache('ChartgetMarketTrendJson' . $market, $data);
		}

		$json_data = array();
		foreach ($data as $k => $v) {
			$json_data[$k][0] = $v['addtime'];
			$json_data[$k][1] = $v['price'];
		}

		exit(json_encode($json_data));
	}

	public function ordinary()
	{
		// TODO: SEPARATE
		$input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$this->assign('market', $market);
		return $this->fetch();
	}

	public function getMarketOrdinaryJson()
	{
		// TODO: SEPARATE
        $input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

		if (in_array($input_val['time'], $timearr)) {
			$time = $input_val['time'];
		} else {
			$time = 5;
		}

		//缓存只保留60秒
		$timeaa = cache('ChartgetMarketOrdinaryJsontime' . $market . $time);
		if (($timeaa + 60) < time()) {
			cache('ChartgetMarketOrdinaryJson' . $market . $time, null);
			cache('ChartgetMarketOrdinaryJsontime' . $market . $time, time());
		}

		$tradeJson = cache('ChartgetMarketOrdinaryJson' . $market . $time);
		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(array(
				'market' => $market,
				'type'   => $time,
				'data'   => array('neq', '')
				))->order('id desc')->limit(100)->select();
			cache('ChartgetMarketOrdinaryJson' . $market . $time, $tradeJson);
		}

		krsort($tradeJson);
		$json_data = array();
		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v['data'], true);
		}

		exit(json_encode($json_data));
	}
	

	public function getMarketNewJson()
	{
		// TODO: SEPARATE
		$input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);
		
		if (in_array($input_val['time'], $timearr)) {
			$time = $input_val['time'];
		} else {
			$time = 5;
		}

		$timeaa = cache('ChartgetMarketOrdinaryJsontime' . $market . $time);
		if (($timeaa + 60) < time()) {
			cache('ChartgetMarketOrdinaryJson' . $market . $time, null);
			cache('ChartgetMarketOrdinaryJsontime' . $market . $time, time());
		}

		$tradeJson = cache('ChartgetMarketOrdinaryJson' . $market . $time);
		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(array(
				'market' => $market,
				'type'   => $time,
				'data'   => array('neq', '')
				))->order('id desc')->limit(100)->select();
			cache('ChartgetMarketOrdinaryJson' . $market . $time, $tradeJson);
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
		$input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$this->assign('market', $market);
		return $this->fetch();
	}

	public function getMarketSpecialtyJson()
	{
		// TODO: SEPARATE
		$input_val = input('param.');
		$market = (is_array(config('market')[$input_val['market']]) ? trim($input_val['market']) : config('market_mr'));
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

		if (in_array($input_val['step'] / 60, $timearr)) {
			$time = $input_val['step'] / 60;
		} else {
			$time = 5;
		}

		$timeaa = cache('ChartgetMarketSpecialtyJsontime' . $market . $time);
		if (($timeaa + 60) < time()) {
			cache('ChartgetMarketSpecialtyJson' . $market . $time, null);
			cache('ChartgetMarketSpecialtyJsontime' . $market . $time, time());
		}

		$tradeJson = cache('ChartgetMarketSpecialtyJson' . $market . $time);
		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(array(
                'market' => $market,
                'type'   => $time,
                'data'   => array('neq', '')
            ))->order('id asc')->limit(500)->select();
			cache('ChartgetMarketSpecialtyJson' . $market . $time, $tradeJson);
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
		$input_val = input('param.');

		if (!$input_val['since']) {
			$tradeLog = Db::name('TradeLog')->where(array('market' => $input_val['market']))->order('id desc')->find();
			$json_data[] = array('tid' => $tradeLog['id'], 'date' => $tradeLog['addtime'], 'price' => $tradeLog['price'], 'amount' => $tradeLog['num'], 'trade_type' => $tradeLog['type'] == 1 ? 'bid' : 'ask');
			exit(json_encode($json_data));
		} else {
			$tradeLog = Db::name('TradeLog')->where(array(
                'market' => $input_val['market'],
                'id'     => array('gt', $input_val['since'])
            ))->order('id desc')->limit(500)->select();
			$json_data = array();
			foreach ($tradeLog as $k => $v) {
				$json_data[] = array('tid' => $v['id'], 'date' => $v['addtime'], 'price' => $v['price'], 'amount' => $v['num'], 'trade_type' => $v['type'] == 1 ? 'bid' : 'ask');
			}

			exit(json_encode($json_data));
		}
	}
}

?>