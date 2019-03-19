<?php
namespace Home\Controller;

class WeikeController extends HomeController
{

	public function index(){
        $market = I('market/s', NULL);

		if(!$market){
			$weike = C('market_mr');
		}else{
			$weike = $market;
		}
		
		$weike_getCoreConfig = weike_getCoreConfig();
		if(!$weike_getCoreConfig){
			$this->error('');
		}
		
		$jiaoyiqu = S('jiaoyiqu');

		if(!$jiaoyiqu){
			foreach(C('market') as $k => $v){
				$jiaoyiqu[$v['jiaoyiqu']][] = $k;
			}
			S('jiaoyiqu',$jiaoyiqu);
		}
		
		$this->assign('weike', $weike);
		$this->assign('weike_jiaoyiqu', $weike_getCoreConfig['weike_indexcat']);
		$this->assign('weike_marketjiaoyiqu', $jiaoyiqu);
		$this->assign('weike_xnb', explode('_', $weike)[0]);
		$this->assign('weike_rmb', explode('_', $weike)[1]);
		$this->display();
		
	}
	
	public function weike_chart_json(){
        $market = I('market/s', NULL);

		if($market==null){
			$market = C('market_mr');
		}

		$timeaa = S('getChartJsontime' . $market);

		if (($timeaa + 60) < time()) {
			S('getChartJson' . $market, null);
			S('getChartJsontime' . $market, time());
		}
		
		$weike_showdata =  S('getChartJson'.$market);

        if(!$weike_showdata)
        {
//            $weike_showdata = array();
//            $weike_showdata['menu'] = array();
//
//            foreach(C('market') as $k => $v){
//                $weike_showdata['menu'][$v['name']]['price'] = strval($v['new_price']);
//            }
//
//            $weike_showdata['top'] = array(
//                strval(C('market')[$market]['new_price']),
//                strval(C('market')[$market]['max_price']),
//                strval(C('market')[$market]['min_price']),
//                strval(C('market')[$market]['buy_price']),
//                strval(C('market')[$market]['sell_price']),
//                strval(C('market')[$market]['volume']),
//                strval(C('market')[$market]['change'])
//            );
//
//            $buy = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit(100)->select();
//
//            $buydata = array();
//            $weike_showdata['buy'] = array();
//            foreach($buy as $k => $v){
//                $buydata[]= array(strval($v['price']),strval($v['nums']));
//            }
//            $weike_showdata['buy'] = $buydata;
//
//            $sell = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit(100)->select();
//
//            $selldata = array();
//            $weike_showdata['sell'] = array();
//            foreach($sell as $k => $v){
//                $selldata[]= array(strval($v['price']),strval($v['nums']));
//            }
//            $weike_showdata['sell'] = $selldata;
//
//            $log_data = M('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(50)->select();
//            $logarr = array();
//
//            if($log_data){
//                foreach($log_data as $k => $v){
//                    $logarr[]=array(strval(date('H:i:s', $v['addtime'])),strval($v['type']),strval(floatval($v['price'])),strval(floatval($v['num'])),strval(floatval($v['mum'])));
//                }
//            }
//            $weike_showdata['log'] = $logarr;
//            S('getChartJson'.$market,$weike_showdata);
//            unset($log_data);
            //自动刷单代码
            $weike_showdata = array();
            $weike_showdata['menu'] = array();

            foreach(C('market') as $k => $v){
                $trade_log = M('TradeLog')->where(['market' => $v['name']])->order('id desc')->find();
                $weike_showdata['menu'][$v['name']]['price'] = strval(round($trade_log['price'],4));
            }
            $anto_market = M('AutoTrade')->where(['market' => $market])->find();
            $trade_price = M('TradeLog')->where(['market' => $market])->order('id desc')->find();
            $buy_price = M('TradeLog')->where(['market' =>$market , 'type' => 1 , 'status' => 1])->order('id desc')->getField('price');
            $sell_price = M('TradeLog')->where(['market' =>$market , 'type' => 2 , 'status' => 1])->order('id desc')->getField('price');
            $weike_showdata['top'] = array(
                strval(round($trade_price['price'] ,5)),
                strval(round($buy_price ,4)),
                strval(round($sell_price,4)),
                strval(C('market')[$market]['min_price']),
                strval(C('market')[$market]['max_price']),
                strval(round($anto_market['volume'],2)),
                strval(round($anto_market['change'], 2))
            );
            $buy = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit(100)->select();
            $buydata = array();
            $weike_showdata['buy'] = array();
            foreach($buy as $k => $v){
                $buydata[]= array(strval($v['price']),strval($v['nums']));
            }
            $weike_showdata['buy'] = $buydata;

            $sell = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit(100)->select();
            $selldata = array();
            $weike_showdata['sell'] = array();
            foreach($sell as $k => $v){
                $selldata[]= array(strval($v['price']),strval($v['nums']));
            }
            $weike_showdata['sell'] = $selldata;
            $log_data = M('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(50)->select();
            $logarr = array();

            if($log_data){
                foreach($log_data as $k => $v){
                    $logarr[]=array(strval(date('H:i:s', $v['addtime'])),strval($v['type']),strval(floatval($v['price'])),strval(floatval($v['num'])),strval(floatval($v['mum'])));
                }
            }
            $weike_showdata['log'] = $logarr;
            S('getChartJson'.$market,$weike_showdata);
            unset($log_data);
        }

		echo json_encode($weike_showdata);
	}
	
	public function weike_pro(){
        $market = I('market/s', NULL);
		
		if(!$market){
			$weike = C('market_mr');
		}else{
			$weike = $market;
		}
		$this->assign('weike', $weike);
		$this->display();
	}
	
	
	public function weike_kline_h_kline(){
        $symbol = I('symbol/s', NULL);
        $type = I('type/s', NULL);
//        $size = I('size/d', 1000);

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
		$timeaa = S('ChartgetMarketSpecialtyJsontime' . $market . $time);
		if (($timeaa + 60) < time()) {
			S('ChartgetMarketSpecialtyJson' . $market . $time, null);
			S('ChartgetMarketSpecialtyJsontime' . $market . $time, time());
		}

		$tradeJson = S('ChartgetMarketSpecialtyJson' . $market . $time);
		if (!$tradeJson) {
            $tradeJson = M('TradeJson')->where(array('market' => $market, 'type' => $time, 'data' => array('neq', '')))->order('id desc')->limit(2000)->select();
//            $tradeJson = M('TradeJson')->where(array('market' => $market, 'type' => $time, 'data' => array('neq', ''), 'addtime' => array('gt', $start)))->order('id desc')->limit(1000)->select();

            array_multisort($tradeJson);
			S('ChartgetMarketSpecialtyJson' . $market . $time, $tradeJson);
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
		$marketchar_pro['moneyType'] = "cny";
		$marketchar_pro['symbol'] = $symbol;
		$marketchar_pro['url'] = "";

		echo json_encode($marketchar_pro);
	}
	
	
	public function weike_kline_h_depths(){
        $depth = I('depth/s', NULL);
		if(!$depth){
			$market = C('market_mr');
		}else{
			$market = $depth;
		}
		
		$timeaa = S('ChartgetMarketDepthJsonBidstime' . $market);
		if(($timeaa + 60) < time()) {
			S('ChartgetMarketDepthJsonBids' . $market, null);
			S('ChartgetMarketDepthJsonBidstime' . $market, time());
		}

		$tradeDepth_bids = S('ChartgetMarketDepthJsonBids' . $market);
		$kline_depths = array();
		
		if(!$tradeDepth_bids){
			//echo "bids未读取缓存";
            $buy = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit(100)->select();

			$buydata = array();
			foreach($buy as $k => $v){
				$buydata[]= array(floatval($v['price']),floatval($v['nums']));
			}
			$tradeDepth_bids = $buydata;
			S('ChartgetMarketDepthJsonBids' . $market , $tradeDepth_bids);
			unset($buy);
			unset($buydata);
		}
		
		$kline_depths['bids'] = $tradeDepth_bids;
		
		//$kline_depths['asks'] = $tradeDepth_bids;
		
		$timeaa = S('ChartgetMarketDepthJsonAskstime' . $market);
		
		if(($timeaa + 60) < time()) {
			S('ChartgetMarketDepthJsonAsks' . $market, null);
			S('ChartgetMarketDepthJsonAskstime' . $market, time());
		}

		$tradeDepth_asks = S('ChartgetMarketDepthJsonAsks' . $market);	

		if(!$tradeDepth_asks){
			//echo "ask未读取缓存";
            $sell = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit(100)->select();
			$selldata = array();
			foreach($sell as $k => $v){
				$selldata[]= array(floatval($v['price']),floatval($v['nums']));
			}
			
			$selldata = array_reverse($selldata);
			
			$tradeDepth_asks = $selldata;
			S('ChartgetMarketDepthJsonAsks' . $market , $tradeDepth_asks);
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