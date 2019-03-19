<?php
namespace app\home\controller;

use think\Db;

class Weike extends Home
{

	public function index(){
        $market = input('market/s', NULL);

		if(!$market){
			$weike = config('market_mr');
		}else{
			$weike = $market;
		}
		
		$weike_getCoreConfig = weike_getCoreConfig();
		if(!$weike_getCoreConfig){
			$this->error('');
		}
		
		$jiaoyiqu = cache('jiaoyiqu');

		if(!$jiaoyiqu){
			foreach(config('market') as $k => $v){
				$jiaoyiqu[$v['jiaoyiqu']][] = $k;
			}
			cache('jiaoyiqu',$jiaoyiqu);
		}
		
		$this->assign('weike', $weike);
		$this->assign('weike_jiaoyiqu', $weike_getCoreConfig['weike_indexcat']);
		$this->assign('weike_marketjiaoyiqu', $jiaoyiqu);
		$this->assign('weike_xnb', explode('_', $weike)[0]);
		$this->assign('weike_rmb', explode('_', $weike)[1]);
		return $this->fetch();
		
	}
	
	public function weike_chart_json(){
        $market = input('market/s', NULL);

		if($market==null){
			$market = config('market_mr');
		}

		$timeaa = cache('getChartJsontime' . $market);

		if (($timeaa + 60) < time()) {
			cache('getChartJson' . $market, null);
			cache('getChartJsontime' . $market, time());
		}
		
		$weike_showdata =  cache('getChartJson'.$market);
		
		if(!$weike_showdata)
		{	
			$weike_showdata = array();
			$weike_showdata['menu'] = array();

			foreach(config('market') as $k => $v){
                $trade_log = Db::name('TradeLog')->where(['market' => $v['name']])->order('id desc')->find();
				$weike_showdata['menu'][$v['name']]['price'] = strval(round($trade_log['price'],4));
			}
            $anto_market = Db::name('AutoTrade')->where(['market' => $market])->find();
            $trade_price = Db::name('TradeLog')->where(['market' => $market])->order('id desc')->find();
            $buy_price = Db::name('TradeLog')->where(['market' =>$market , 'type' => 1 , 'status' => 1])->order('id desc')->value('price');
            $sell_price = Db::name('TradeLog')->where(['market' =>$market , 'type' => 2 , 'status' => 1])->order('id desc')->value('price');
			$weike_showdata['top'] = array(
                strval(round($trade_price['price'] ,4)),
                strval(round($buy_price ,4)),
                strval(round($sell_price,4)),
                strval(config('market')[$market]['min_price']),
                strval(config('market')[$market]['max_price']),
                strval(round($anto_market['volume'],2)),
                strval(round($anto_market['change'], 2))
			);
            $buy = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit(100)->select();
			$buydata = array();
			$weike_showdata['buy'] = array();
			foreach($buy as $k => $v){
				$buydata[]= array(strval($v['price']),strval($v['nums']));
			}
			$weike_showdata['buy'] = $buydata;

            $sell = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit(100)->select();
			$selldata = array();
			$weike_showdata['sell'] = array();
			foreach($sell as $k => $v){
				$selldata[]= array(strval($v['price']),strval($v['nums']));
			}
			$weike_showdata['sell'] = $selldata;
			$log_data = Db::name('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(50)->select();
			$logarr = array();
			
			if($log_data){
				foreach($log_data as $k => $v){
					$logarr[]=array(strval(date('H:i:s', $v['addtime'])),strval($v['type']),strval(floatval($v['price'])),strval(floatval($v['num'])),strval(floatval($v['mum'])));
				}
			}
			$weike_showdata['log'] = $logarr;
			cache('getChartJson'.$market,$weike_showdata);
			unset($log_data);
		}

		echo json_encode($weike_showdata);
	}
	
	public function weike_pro(){
        $market = input('market/s', NULL);
		
		if(!$market){
			$weike = config('market_mr');
		}else{
			$weike = $market;
		}
		$this->assign('weike', $weike);
		return $this->fetch();
	}
	
	
	public function weike_kline_h_kline(){
        $symbol = input('symbol/s', NULL);
        $type = input('type/s', NULL);
        $size = input('size/d', 1000);
		
		switch($type){
			case "1day":
				$time = 1440;
				break;
			case "12hour":
				$time = 720;
				break;
			case "6hour":
				$time = 360;
				break;
			case "4hour":
				$time = 240;
				break;	
			case "2hour":
				$time = 120;
				break;
			case "1hour":
				$time = 60;
				break;	
			case "30min":
				$time = 30;
				break;	
			case "15min":
				$time = 15;
				break;	
			case "5min":
				$time = 5;
				break;	
			case "3min":
				$time = 3;
				break;	
			case "1min":
				$time = 1;
				break;
			default:
				$time = 15;
				break;
		}
		
		$marketchar_pro = array();
		$tempdata = array();
		$tempdata['DSCCNY'] 	  = 6.5746;
		$tempdata['contractUnit'] = "DSC";
		
		$market = $symbol;
		$timeaa = cache('ChartgetMarketSpecialtyJsontime' . $market . $time);
		if (($timeaa + 60) < time()) {
			cache('ChartgetMarketSpecialtyJson' . $market . $time, null);
			cache('ChartgetMarketSpecialtyJsontime' . $market . $time, time());
		}

		$tradeJson = cache('ChartgetMarketSpecialtyJson' . $market . $time);
		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(array('market' => $market, 'type' => $time, 'data' => array('neq', '')))->order('id desc')->limit(1000)->select();
            array_multisort($tradeJson);
			cache('ChartgetMarketSpecialtyJson' . $market . $time, $tradeJson);
		}
		$json_data = $data = array();
		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v['data'], true);
		}

		foreach ($json_data as $k => $v) {
			$data[] = array($v[0]*1000,floatval($v[2]),floatval($v[3]),floatval($v[4]),floatval($v[5]),floatval($v[1]));
		}
		$tempdata['data'] = $data;
		$marketchar_pro['datas'] = $tempdata;

		$marketchar_pro['des'] = "";
		$marketchar_pro['isSuc'] = true;
		$marketchar_pro['marketName'] = "weike";
		$marketchar_pro['moneyType'] = "hkd";
		$marketchar_pro['symbol'] = $symbol;
		$marketchar_pro['url'] = "";

		echo json_encode($marketchar_pro);
	}
	
	
	public function weike_kline_h_depths(){
        $depth = input('depth/s', NULL);
		if(!$depth){
			$market = config('market_mr');
		}else{
			$market = $depth;
		}
		
		$timeaa = cache('ChartgetMarketDepthJsonBidstime' . $market);
		if(($timeaa + 60) < time()) {
			cache('ChartgetMarketDepthJsonBids' . $market, null);
			cache('ChartgetMarketDepthJsonBidstime' . $market, time());
		}

		$tradeDepth_bids = cache('ChartgetMarketDepthJsonBids' . $market);
		$kline_depths = array();
		
		if(!$tradeDepth_bids){
			//echo "bids未读取缓存";
            $buy = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit(100)->select();

			$buydata = array();
			foreach($buy as $k => $v){
				$buydata[]= array(floatval($v['price']),floatval($v['nums']));
			}
			$tradeDepth_bids = $buydata;
			cache('ChartgetMarketDepthJsonBids' . $market , $tradeDepth_bids);
			unset($buy);
			unset($buydata);
		}
		
		$kline_depths['bids'] = $tradeDepth_bids;
		
		//$kline_depths['asks'] = $tradeDepth_bids;
		
		$timeaa = cache('ChartgetMarketDepthJsonAskstime' . $market);
		
		if(($timeaa + 60) < time()) {
			cache('ChartgetMarketDepthJsonAsks' . $market, null);
			cache('ChartgetMarketDepthJsonAskstime' . $market, time());
		}

		$tradeDepth_asks = cache('ChartgetMarketDepthJsonAsks' . $market);

		if(!$tradeDepth_asks){
			//echo "ask未读取缓存";
            $sell = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit(100)->select();
			$selldata = array();
			foreach($sell as $k => $v){
				$selldata[]= array(floatval($v['price']),floatval($v['nums']));
			}
			
			$selldata = array_reverse($selldata);
			
			$tradeDepth_asks = $selldata;
			cache('ChartgetMarketDepthJsonAsks' . $market , $tradeDepth_asks);
			unset($sell);
			unset($selldata);
		}
		
		$kline_depths['asks'] = $tradeDepth_asks;
		//$kline_depths['bids'] = $tradeDepth_asks;

		$kline_depths['date'] = time();
		
		echo json_encode($kline_depths);
	}
}

?>