<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;

class Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439 extends \think\Controller
{
    //实例化缓存数据
    protected function _initialize()
    {
       //建立计划锁目录
        if(!is_dir(CRONLOCK_PATH)){
            @(mkdir(CRONLOCK_PATH));
        }

        $config = cache('home_config');
        if (!$config) {
            $config = Db::name('Config')->where(array('id' => 1))->find();
            cache('home_config', $config);
        }

        config($config);

        $coin = cache('home_coin');
        if (!$coin) {
            $coin = Db::name('Coin')->where(array('status' => 1))->select();
            cache('home_coin', $coin);
        }

        $coinList = [];
        foreach ($coin as $k => $v) {
            $coinList['coin'][$v['name']] = $v;

            if ($v['type'] != 'rmb') {
                $coinList['coin_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rmb') {
                $coinList['rmb_list'][$v['name']] = $v;
            } else {
                $coinList['xnb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rgb') {
                $coinList['rgb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'bit') {
                $coinList['bit_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'eth') {
                $coinList['eth_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'token') {
                $coinList['token_list'][$v['name']] = $v;
            }
        }


        config($coinList);
        $market = cache('home_market');
        $market_type = [];
        $coin_on = [];
        if (!$market) {
            $market = Db::name('Market')->where(array('status' => 1))->select();
            cache('home_market', $market);
        }

        foreach ($market as $k => $v) {
            if(!$v['round']){
                $v['round'] = 4;
            }

            $v['new_price'] = round($v['new_price'], $v['round']);
            $v['buy_price'] = round($v['buy_price'], $v['round']);
            $v['sell_price'] = round($v['sell_price'], $v['round']);
            $v['min_price'] = round($v['min_price'], $v['round']);
            $v['max_price'] = round($v['max_price'], $v['round']);
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $v['xnbimg'] = config('coin')[$v['xnb']]['img'];
            $v['rmbimg'] = config('coin')[$v['rmb']]['img'];
            $v['volume'] = $v['volume'] * 1;
            $v['change'] = $v['change'] * 1;
            $v['title'] = config('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
            $v['navtitle'] = config('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']). ')';
            if($v['begintrade']){
                $v['begintrade'] = $v['begintrade'];
            }else{
                $v['begintrade'] = "00:00:00";
            }
            if($v['endtrade']){
                $v['endtrade']    = $v['endtrade'];
            }else{
                $v['endtrade']    = "23:59:59";
            }

            $market_type[$v['xnb']]=$v['name'];
            $coin_on[]= $v['xnb'];
            $marketList['market'][$v['name']] = $v;
        }

        config('market_type',$market_type);
        config('coin_on',$coin_on);
        config($marketList);
    }

    //index OK
	public function index()
	{
		foreach (config('market') as $k => $v) {
			
		}

		foreach (config('coin_list') as $k => $v) {
			
		}
		echo "ok";
	}

	//检测异常，调整不正常的委单
	public function checkYichang()
	{

	    Db::startTrans();
        try{
            $Trade = Db::name('Trade')->lock(true)->where('deal > num')->order('id desc')->find();

            if ($Trade) {
                if ($Trade['status'] == 0) {
                    Db::table('weike_trade')->where(array('id' => $Trade['id']))->update(array('deal' => Num($Trade['num']), 'status' => 1));
                } else {
                    Db::table('weike_trade')->where(array('id' => $Trade['id']))->update(array('deal' => Num($Trade['num'])));
                }
                Db::commit();
            } else {
                Db::rollback();
            }

        }catch (Exception $e){
            Db::rollback();
        }

	}

	//检查大盘，调整不成交委单
	public function checkDapan()
	{
        $market = input('market/s', 'doge_hkd');

        $url = 'http://www.btchkgj.com/Trade/matchingTrade/market/'.$market;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_exec($ch);
        $this->success('检测成功！');
	}

    //检查币种
	public function checkUsercoin()
	{
		foreach (config('coin') as $k => $v) {
			
		}
	}

	//设置市场和币种
	public function marketandcoinb8c3b3d94512472db8()
	{
        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//
                $error_info = '';
                foreach (config('market') as $k => $v) {
                    try{
                        $this->setMarket($v['name']);

                    }catch (Exception $e){
                        $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";

                    }


                }

                foreach (config('coin_list') as $k => $v) {
                    try{
                        $this->setcoin($v['name']);

                    }catch (Exception $e){
                        $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";

                    }

                }
                //--业务结束----//
                if($error_info){
                    throw new Exception($error_info);
                }
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .= $e->getMessage()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


	}

	//机器刷单
    public function marketandcoinb8c3b3d94512472db()
    {
        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                $error_info='';
                foreach (config('market') as $k => $v) {
                    try{
                        $this->autoTrade($v['name']);
                    }catch (Exception $e){
                        $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";

                    }

                }
                if($error_info){
                    throw new Exception($error_info);
                }
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .=$e->getMessage()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


    }

    //自动刷单功能
    private function autoTrade($market){
        if(date('i') % 5 !== 0) {
            return ;
        }
        $data = cache('autoData'.$market);
        $market_config = config('market');
        $buy = Db::name('Trade')->where(array('market' => $market, 'type' => 1,'userid' => array('gt',0), 'status' => 0))->order('price desc,id asc')->find();
        $sell = Db::name('Trade')->where(array('market' => $market, 'type' => 2,'userid' => array('gt',0),'status' => 0))->order('price asc,id asc')->find();
        $auto = Db::name('AutoTrade')->where(['market' => $market , 'status' => 1])->find();
        $num = round(randomFloat(0, 5),2);
        $sell_num = round(randomFloat(0, 5),3);
        $buy_num = round(randomFloat(0, 5),3);
        $type = rand(1,2);
        if (!$data) {
            if($market){
                $xnb = explode('_', $market)[0];
                $rmb = explode('_', $market)[1];
            }

            //每个是市场买卖数量设置
            if ($auto['market'] &&  $auto['market'] == $market){
                $num = round(randomFloat($auto['min'], $auto['max']), 2);
            }

            //判断用户买一价
            $plus = $buy['price'];
            $buy_price = round($plus, 4);
            //判断用户卖一价
            $plus = $sell['price'];
            $sell_price = round($plus, 4);

            //如果 买一价比卖一价大  调换位置
            if($buy_price > $sell_price){
                $swith = $sell_price;
                $sell_price = $buy_price;
                $buy_price = $swith;
            }

            Db::name('AutoTrade')->where(array('market' => $market ,'status' => 1))->update(['buy_price' => $buy_price, 'buy_num' => $buy_num ,'sell_price' => $sell_price, 'sell_num' => $sell_num , 'time' => time()]);

            $tradeLog = Db::name('TradeLog')->where(['status' => 1, 'market' => $market])->order('id desc')->find();
            //成交最大价
            $max_price = $market_config[$market]['max_price'];
            //成交最小价
            $min_price = $market_config[$market]['min_price'];

            $new_plus = randomFloat($buy['price'], $sell['price']);
            $new_plus= round($new_plus,3);

            //手续费
            $m_fee_buy = $market_config[$market]['fee_buy'] ? $market_config[$market]['fee_buy'] : 0 ;
            $m_fee_sell = $market_config[$market]['fee_sell'] ? $market_config[$market]['fee_sell'] : 0;
            $fee1 = $m_fee_buy * $num * $new_plus ;//买入手续费
            $fee2 = $m_fee_sell * $num * $new_plus ;//卖出手续费

            //交易金额
            $total = $new_plus * $num ;
            $mum = $auto['price'] * $num ;
            $all_market = $data = Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->select();
            $suc_trade = Db::name('AutoTrade')->where(array('market' => $market , 'status' => 1))->update(['price' => $new_plus , 'max_price' =>$max_price , 'num' => $num ,'type' =>$type , 'min_price' =>$min_price]);
            Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->setInc('volume',$num);
            Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->setInc('deal_toble',$mum);

            if ($suc_trade) {
                $old_time = Db::name('TradeLog')->where(['market' => $market])->order('id desc')->value('addtime');
                $new_time = rand($old_time, time());
                Db::name('TradeLog')->insert(['userid' => 35, 'peerid' => 33, 'market' => $market , 'price' => $new_plus , 'num' => $num , 'mum' => $total , 'fee_buy' => $fee1, 'fee_sell' => $fee2 , 'type' => $type , 'addtime' => $new_time , 'status' => 1]);

            }
            $mum = $auto['price'] * $num ;

            //24成交量  和  成交额  归零处理
            if (date('H') == 0 && date('i') == 0){
                $volume = Db::name('AutoTrade')->where(['market' => $market , 'status' => 1])->update(['volume' => 0]);
                $deal_toble = Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->update(['deal_toble' => 0]);
            }

            //涨跌幅
            $hou_price = $market_config[$market]['hou_price'];
            if($hou_price && intval($hou_price)!=0){
                $a_price = Db::name('TradeLog')->where(['market' => $market])->order('id desc')->value('price');
                $change = round(( ($a_price - $hou_price)/$hou_price ) *100,2 );
                Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->update(['change' => $change]);
            }

            foreach ($all_market as $k =>$v){
                $data['list'][$k]['market'] = $v['market'];
                $data['list'][$k]['img'] = $v['img'];
                $data['list'][$k]['title'] = $v['title'];
                $data['list'][$k]['price'] = round( $v['price'],4);
            }
            $info = Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->find();
            $data['info']['rmb'] = $rmb ;
            $data['info']['buy_price'] = round( $info['buy_price'],4);
            $data['info']['sell_price'] = round( $info['sell_price'],4);
            $data['info']['volume'] = round( $info['volume'],4);
            $data['info']['change'] = round( $info['change'],2);
            $data['info']['min_price'] = round( $info['min_price'], 4);
            $data['info']['max_price'] = round( $info['max_price'], 4);
            $data['info']['price'] = round( $info['price'], 3);
            $data['info']['num'] = round( $info['num'], 4);
            $data['info']['buy_num'] = round( $info['buy_num'], 2);
            $data['info']['sell_num'] = round( $info['sell_num'], 3);
            $data['info']['type'] = round( $info['type'], 4);
            $data['info']['mum'] = round( $info['mum'], 3);
            $data['info']['buy_mum'] = round( $info['buy_num'] * $info['buy_price'], 3);
            $data['info']['sell_mum'] = round( $info['sell_num'] * $info['sell_price'], 3);
            $data['info']['time'] = addtime($info['time'],'m-d H:i:s');
            cache('autoTrade' . $market, $data);

            //清理首页缓存
            $jiaoyiqu = config('market')[$market]['jiaoyiqu'];
            cache('weike_allcoin'.$jiaoyiqu,null);
            cache('getChartJson'.$market , null);
            cache('getTradelog' . $market, null);
            cache('getJsonTop' . $market, null);
        }
    }

	//设置市场
	private function setMarket($market = NULL)
	{
		if (!$market) {
			return null;
		}
        $trade = Db::name('Market')->where(['name' => $market])->value('trade');
		if ($trade == 0){
            return false;
        }
		$market_json = Db::name('Market_json')->where(array('name' => $market))->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		} else {
			$addtime = Db::name('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
		}

		$t = $addtime;
		$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
		$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
		$trade_num = Db::name('TradeLog')->where(array(
			'market'  => $market,
			'addtime' => array(
				array('egt', $start),
				array('elt', $end)
				)
			))->sum('num');

		if ($trade_num) {
			$trade_mum = Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('mum');
			$trade_fee_buy = Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('fee_buy');
			$trade_fee_sell = Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('fee_sell');
			$d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

			if (Db::name('Market_json')->where(array('name' => $market, 'addtime' => $end))->find()) {
				Db::name('Market_json')->where(array('name' => $market, 'addtime' => $end))->update(array('data' => json_encode($d)));
			} else {
				Db::name('Market_json')->insert(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
			}
		} else {
			$d = null;

			if (Db::name('Market_json')->where(array('name' => $market, 'data' => ''))->find()) {
				Db::name('Market_json')->where(array('name' => $market, 'data' => ''))->update(array('addtime' => $end));
			} else {
				Db::name('Market_json')->insert(array('name' => $market, 'data' => '', 'addtime' => $end));
			}
		}
	}

	//设置市场
	private function setcoin($coinname = NULL)
	{
		if (!$coinname) {
			return null;
		}

        $dj_username = config('coin')[$coinname]['dj_yh'];
        $dj_password = config('coin')[$coinname]['dj_mm'];
        $dj_address = config('coin')[$coinname]['dj_zj'];
        $dj_port = config('coin')[$coinname]['dj_dk'];
		if (config('coin')[$coinname]['type'] == 'bit') {
			$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                throw new Exception($coinname."钱包连接失败!");
                $this->error('钱包连接失败！');
            }

			$data['trance_mum'] = $json['balance'];
        } elseif(config('coin')[$coinname]['type'] == 'eth' || config('coin')[$coinname]['type'] == 'token'){
            $CoinClient = EthClient($dj_address,$dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                throw new Exception($coinname."钱包连接失败!");
                $this->error('钱包连接失败');
            }

            $accounts = $CoinClient->eth_accounts();
            $sum = 0;
            foreach ($accounts as $key => $value) {
                if (config('coin')[$coinname]['type'] == 'eth') {
                    $sum += $CoinClient->eth_getBalance($value);
                } elseif ( config('coin')[$coinname]['type'] == 'token' ){
                    $call = [
                        'to' => config('coin')[$coinname]['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($value)
                    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , config('coin')['$coinname']['decimals']);
                }

            }
            $data['trance_mum'] = $sum;

        } else {
			$data['trance_mum'] = 0;
		}

		$market_json = Db::name('CoinJson')->where(array('name' => $coinname))->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		} else {
			$addtime = Db::name('Myzr')->where(array('coinname' => $coinname))->order('id asc')->find()['addtime'];
		}

		$t = $addtime;
		$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
		$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

		if ($addtime) {
			if ((time() + (60 * 60 * 24)) < $addtime) {
				return null;
			}

//			$trade_num = Db::name('UserCoin')->where(array(
//				'addtime' => array(
//					array('egt', $start),
//					array('elt', $end)
//					)
//				))->sum($coinname);
//			$trade_mum = Db::name('UserCoin')->where(array(
//				'addtime' => array(
//					array('egt', $start),
//					array('elt', $end)
//					)
//				))->sum($coinname . 'd');
			$aa = 0;//$trade_num + $trade_mum;
            $bb = $data['trance_mum'];

			$trade_fee_buy = Db::name('Myzr')->where(array(
				'coinname'    => $coinname,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('fee');
			$trade_fee_sell = Db::name('Myzc')->where(array(
				'coinname'    => $coinname,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('fee');
			$d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

			if (Db::name('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
				Db::name('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->update(array('data' => json_encode($d)));
			} else {
				Db::name('CoinJson')->insert(array('name' => $coinname, 'data' => json_encode($d), 'addtime' => $end));
			}
		}
	}

	//排错
	public function paicuo()
	{

	}

	//设置最后的价格
	public function houpriceb8c3b3d94512472db8()
	{
        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//
                foreach (config('market') as $k => $v) {
                    if (!$v['hou_price'] || (date('H', time()) == '00')) {
                        $t = time();
                        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
                        $hou_price = Db::name('TradeLog')->where(array(
                            'market'  => $v['name'],
                            'addtime' => array('lt', $start)
                        ))->order('id desc')->limit(1)->value('price');

                        if (!$hou_price) {
                            $hou_price = $v['weike_faxingjia'];
                            Db::name('Market')->where(array('name' => $v['name']))->setField('hou_price', $hou_price);
                            cache('home_market', null);
                        }elseif($hou_price != $v['hou_price']){
                            Db::name('Market')->where(array('name' => $v['name']))->setField('hou_price', $hou_price);
                            cache('home_market', null);
                        }
                    }
                }
                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


	}

	//比特币系列的轮询
	public function qianbaob8c3b3d94512472db7()
	{
        $coin = input('param.coin', 'btc', 'string');
        if (!$coin) {
            exit('no coin name');
        }

        $fileName = CRONLOCK_PATH.__FUNCTION__."_".$coin.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//

                $coinconf = Db::name('Coin')->where(['status' => 1,'zr_jz' => 1, 'name' => $coin])->find();

                $coin = $coinconf['name'];
                if (!$coin){
                    throw new Exception($coin."币的状态不可用!");
                    $this->error($coin."币的状态不可用!");
                }

                if ($coinconf['type'] == 'bit') {
                    $dj_username = $coinconf['dj_yh'];
                    $dj_password = $coinconf['dj_mm'];
                    $dj_address = $coinconf['dj_zj'];
                    $dj_port = $coinconf['dj_dk'];
                    echo 'start ' . $coin . "\n";
                    $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                    if ($coinconf['name'] == 'ltc' || $coinconf['name'] == 'btc') {
                        $json = $CoinClient->getnetworkinfo();
                    } else {
                        $json = $CoinClient->getinfo();
                    }

                    if (!isset($json['version']) || !$json['version']) {
                        throw new Exception($coin." 连接失败!");

                    }

                    echo 'Cmplx ' . $coin . ' start,connect ' . (empty($CoinClient) ? 'fail' : 'ok') . ' :' . "\n";
                    $listtransactions = $CoinClient->listtransactions('*', 100, 0);
                    echo 'listtransactions:' . count($listtransactions) . "\n";
                    krsort($listtransactions);

                    foreach ($listtransactions as $trans) {
                        if (!$trans['account']) {
                            echo 'empty account continue' . "\n";
                            continue;
                        }

                        $user = Db::name('User')->where(array('username' => $trans['account']))->find();
                        if (!$user) {
                            echo 'no account find continue' . "\n";
                            continue;
                        }


                        if (Db::name('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1'))->find()) {
                            echo 'txid had found continue' . "\n";
                            continue;
                        }
                        //无blockhash  不处理
                        if ($trans['blockhash']) {
                            $is_block = $CoinClient->getblock($trans['blockhash']);
                            $get_blockinfo = $is_block['tx'];
                            foreach ($get_blockinfo as $tx) {
                                if ($tx != $trans['txid']) {
                                    continue;
                                }
                            }
                        } else {
                            continue;
                        }
                        echo 'all check ok ' . "\n";
                        if ($trans['category'] == 'receive') {
                            print_r($trans);
                            echo 'start receive do:' . "\n";
                            $sfee = 0;
                            $true_amount = $trans['amount'];

                            if (config('coin')[$coin]['zr_zs']) {
                                $song = round(($trans['amount'] / 100) * config('coin')[$coin]['zr_zs'], 8);

                                if ($song) {
                                    $sfee = $song;
                                    $trans['amount'] = $trans['amount'] + $song;
                                }
                            }

                            if ($trans['confirmations'] < config('coin')[$coin]['zr_dz']) {
                                echo $trans['account'] . ' confirmations ' . $trans['confirmations'] . ' not elengh ' . config('coin')[$coin]['zr_dz'] . ' continue ' . "\n";
                                echo 'confirmations <  c_zr_dz continue' . "\n";

                                $res = Db::name('myzr')->where(array('txid' => $trans['txid']))->find();
                                if ($res) {
                                    Db::name('myzr')->update(array('id' => $res['id'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - config('coin')[$coin]['zr_dz'])));
                                } else {
                                    Db::name('myzr')->insert(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - config('coin')[$coin]['zr_dz'])));
                                }

                                continue;
                            } else {
                                echo 'confirmations full' . "\n";
                            }

                            Db::startTrans();
                            try{

                                $rs = [];
                                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $user['id']))->setInc($coin, $trans['amount']);

                                $res = Db::table('weike_myzr')->where(array('txid' => $trans['txid']))->find();
                                if ($res) {
                                    echo 'weike_myzr find and set status 1';
                                    $rs[] = Db::table('weike_myzr')->update(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                                } else {
                                    echo 'weike_myzr not find and add a new weike_myzr' . "\n";
                                    $rs[] = Db::table('weike_myzr')->insert(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => 1));
                                }

                                if (check_arr($rs)) {
                                    Db::commit();
                                    echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                                    echo 'commit ok' . "\n";
                                } else {
                                    Db::rollback();
                                    echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                                    echo var_export($rs, true);
                                    print_r($rs);
                                    echo 'rollback ok' . "\n";
                                }

                            }catch (Exception $e){
                                Db::rollback();
                                echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                                echo var_export($rs, true);
                                print_r($rs);
                                echo 'rollback ok' . "\n";
                            }

                        }

                        if ($trans['category'] == 'send') {
                            echo 'start send do:' . "\n";

                            if (3 <= $trans['confirmations']) {
                                $myzc = Db::name('Myzc')->where(array('txid' => $trans['txid']))->find();

                                if ($myzc) {
                                    if ($myzc['status'] == 0) {
                                        Db::name('Myzc')->where(array('txid' => $trans['txid']))->update(array('status' => 1));
                                        echo $trans['amount'] . '成功转出' . $coin . ' 币确定';
                                    }
                                }
                            }
                        }
                    }
                }


                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }



	}

    //ETH 系列的轮询
    public function qianbaob8c3b3d94512472db8()
    {
        $coin = input('param.coin', 'eth', 'string');
        if (!$coin) {
            exit('no coin name');
        }

        $fileName = CRONLOCK_PATH.__FUNCTION__."_".$coin.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//

                $coinconf = Db::name('Coin')->where(['status' => 1, 'name' => $coin])->find();
                $coin = $coinconf['name'];
                $coinAddress = $coin.'b';
                if (!$coin) {
                    throw new Exception($coin."币的状态不可用!");
                    $this->error($coin."币的状态不可用!");
                }

                if ($coinconf['type'] == 'eth' || $coinconf['name'] == 'uicc') {
                    $dj_address = $coinconf['dj_zj'];
                    $dj_port = $coinconf['dj_dk'];
                    $CoinClient = EthClient($dj_address, $dj_port);
                    $json = $CoinClient->eth_blockNumber(true);
                    if (empty($json) || $json <= 0) {
                        throw new Exception($coin."钱包连接失败!");
                        $this->error('钱包连接失败');
                    }
                    //开始轮询
                    $listtransactions = $CoinClient->listLocal($coin, $json);
                    echo 'listtransactions:' . count($listtransactions) . "\n";
                    if (empty($listtransactions)) {
                        throw new Exception($coin."高度太高，无法轮询!");
                        $this->error('高度太高，无法轮询');
                    }
                    foreach ($listtransactions as $trans) {
                        if (!$trans->to) {
                            echo 'empty to continue' . "<br>";
                            continue;
                        }
                        //判断判断
                        if (strlen($trans->input) == 138) {
                            $sts = $CoinClient->eth_getTransactionReceipt($trans->hash);
                            $sts_s = object_array($sts);
                            $sts = substr($sts_s['status'], 2, 1);
                            //判断区块上面的转入状态,status = 0 失败,  logs为空 失败
                            if ($sts != 1 || !$sts_s['logs']) {
                                continue;
                            }
                            $coinconf_token = Db::name('Coin')->where(['status' => 1, 'token_address' => $trans->to])->find();
                            //数据库里面是否有token币种
                            if($coinconf_token) {

                                $token_value = substr($trans->input, 74, 64);
                                $to = "0x" . substr($trans->input, 34, 40);
                                Db::startTrans();
                                //判断该交易是否轮询
                                if (Db::table('weike_myzr')->lock(true)->where(['txid' => $trans->hash])->value('id')) {
                                    Db::rollback();
                                    continue;
                                }
                                //拿token 名称
                                $coinAddress_token = $coinconf_token['name'] . 'b';
                                $user_token = Db::name('UserCoin')->where([$coinAddress_token => $to])->find();
                                if ($user_token) {
                                    $sfee = 0;
                                    $true_amount_token = $CoinClient->real_banlance_token($CoinClient->decode_hex($token_value), $coinconf_token['decimals']);

                                    $yue_token = $CoinClient->eth_getBalance($to);
                                    if ($yue_token < floatval('0.002')) {
                                        //eth转账到token的eth手续费
                                        $tradeInfo_token = [[
                                            'from' => $coinconf['dj_yh'],
                                            'to' => $to,
                                            'gas' => '0x1046a',
                                            'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval(0.005 - $yue_token))),
                                            'gasPrice' => '0x189640200', //$CoinClient->eth_gasPrice()
                                        ]];
                                        $sendrs = $CoinClient->eth_sendTransaction($coinconf['dj_yh'], $coinconf['dj_mm'], $tradeInfo_token);
                                    }

                                    $rs = [];
                                    $rs[] = Db::table('weike_user_coin')->where(['userid' => $user_token['userid']])->setInc($coinconf_token['name'], $true_amount_token);
                                    $txid = Db::table('weike_myzr')->order('id desc')->limit(1)->value('txid');
                                    if ($txid == $trans->hash){
                                        Db::rollback();
                                        continue;
                                    }
                                    $rs[] = Db::table('weike_myzr')->insert([
                                        'userid' => $user_token['userid'],
                                        'username' => $to,
                                        'coinname' => $coinconf_token['name'],
                                        'fee' => $sfee,
                                        'txid' => $trans->hash,
                                        'num' => $true_amount_token,
                                        'mum' => $true_amount_token,
                                        'addtime' => time(),
                                        'status' => 1
                                    ]);
                                    if (check_arr($rs)) {
                                        Db::commit();
                                    } else {
                                        Db::rollback();
                                    }
                                }

                            }else{
                                continue;
                            }
                            //转出
                            if (!$trans->from) {
                                echo 'empty to continue' . "<br>";
                                continue;
                            }
                            if ($user_token = Db::name('UserCoin')->where([$coinAddress_token => $trans->from])->find()) {
                                echo 'start send do:' . "\n";
                                $myzc_token = Db::name('Myzc')->where(['txid' => $trans->hash])->find();
                                if ($myzc_token) {
                                    if ($myzc_token['status'] == 0) {
                                        Db::name('Myzc')->where(['txid' => $trans->hash])->update(['status' => 1]);
                                        echo $true_amount_token . '成功转出' . $coin . ' 币确定';
                                    }
                                }
                            } else {
                                continue;
                            }

                        } else {
                            //eth,etc轮询
                            if ($coinconf['type'] == 'token') {
                                $coinAddress = 'ethb';
                                $coin = 'eth';
                            }
                            $user = Db::name('UserCoin')->where([$coinAddress => $trans->to])->find();
                            if ($user) {
                                echo 1;
                                if (Db::name('Myzr')->where(['txid' => $trans->hash, 'status' => '1'])->value('id')) {
                                    continue;
                                }
                                $sfee = 0;
                                $true_amount = $CoinClient->real_banlance($CoinClient->decode_hex($trans->value));
                                $final_amount = $true_amount - 0.001;
                                if ($final_amount > 0.002) {
                                    Db::startTrans();

                                    //事务中锁表，避免写入
                                    if (Db::table('weike_myzr')->lock(true)->where(['txid' => $trans->hash])->value('id')) {
                                        Db::rollback();
                                        continue;
                                    }

                                    $rs = [];
                                    $rs[] = Db::table('weike_user_coin')->where(['userid' => $user['userid']])->setInc($coin, $final_amount);
                                    $rs[] = Db::table('weike_myzr')->insert([
                                        'userid' => $user['userid'],
                                        'username' => $trans->to,
                                        'coinname' => $coin,
                                        'fee' => $sfee,
                                        'txid' => $trans->hash,
                                        'num' => $true_amount,
                                        'mum' => $final_amount,
                                        'addtime' => time(),
                                        'status' => 1
                                    ]);

                                    if (check_arr($rs)) {
                                        Db::commit();
                                    } else {
                                        Db::rollback();
                                    }
                                }
                            }
                            //转出
                            if (!$trans->from) {
                                continue;
                            }
                            if ($user = Db::name('UserCoin')->where([$coinAddress => $trans->from])->find()) {
                                $myzc = Db::name('Myzc')->where(['txid' => $trans->hash])->find();
                                if ($myzc) {
                                    if ($myzc['status'] == 0) {
                                        Db::name('Myzc')->where(['txid' => $trans->hash])->update(['status' => 1]);
                                    }
                                }
                            }
                        }
                    }
                }

                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


    }

    //EOS系列的轮询
    public function qianbaob8c3b3d94512472db9()
    {
        $coin = input('param.coin', 'eos', 'string');
        if (!$coin) {
            exit('no coin name');
        }

        $fileName = CRONLOCK_PATH.__FUNCTION__."_".$coin.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//

                $coinconf = Db::name('Coin')->where(['status' => 1, 'name' => $coin])->find();
                $coin = $coinconf['name'];
                if (!$coin) {
                    throw new Exception($coin." 币状态不可用!");
                }

                if ($coinconf['type'] == 'eos') {
                    $dj_address = $coinconf['dj_zj'];
                    $dj_port = $coinconf['dj_dk'];
                    $CoinClient = EosClient($dj_address, $dj_port);
                    $get_info = $CoinClient->get_info();
                    if (!$get_info) {
                        throw new Exception($coin."钱包连接失败!");
                    }
                    //获取信息
                    $offset = 10;
                    $transfer = [
                        'account_name' => $coinconf['dj_yh'],
                        'pos' => $coinconf['block_num'],
                        'offset' => $offset,
                    ];
                    $block_info = $CoinClient->get_actions($transfer);
                    if (!$block_info) {
                        throw new Exception($coin."轮询出错!");
                    }

                    $error_info='';
                    foreach ($block_info as $k => $v) {
                        //判断状态释放
                        $token_action_trace = $v['action_trace'];
                        if (Db::name('Myzr')->where(['txid' => $token_action_trace['trx_id'], 'status' => '1'])->value('id')) {
                            continue;
                        }

                        //判断该交易是否轮询
                        Db::startTrans();
                        try {
                            if (Db::table('weike_myzr')->lock(true)->where(['txid' => $token_action_trace['trx_id']])->value('id')) {
                                Db::rollback();
                                continue;
                            }
                            $token_receipt = $token_action_trace['receipt'];
                            //判断接受地址
                            if ($token_receipt['receiver'] == $coinconf['dj_yh']) {
                                $token_act = $token_action_trace['act'];
                                $coinAddress = Db::name('Coin')->where(['token_address' => $token_act['account']])->value('name');
                                $coinAddressb = $coinAddress . 'b';
                                $coinAddressp = $coinAddress . 'p';
                                $sfee = 0;
                                //判断操作类型
                                if ($coinAddress && $token_act['name'] == 'transfer') {
                                    $token_data = $token_act['data'];
                                    $user = Db::name('UserCoin')->where([$coinAddressp => $token_data['memo']])->find();
                                    //判断地址和memo是否存在
                                    $quantity = $token_data['quantity'];
                                    if ($user && $user[$coinAddressb] == $token_data['to']) {
                                        $final_amount = trim(substr($quantity, 0, strlen($quantity) - 3));
                                        $rs = [];
                                        $rs[] = Db::table('weike_user_coin')->where(['userid' => $user['userid']])->setInc($coinAddress, $final_amount);
                                        $username = $token_data['to'] . ' ' . $token_data['memo'];
                                        $rs[] = Db::table('weike_myzr')->insert([
                                            'userid' => $user['userid'],
                                            'username' => $username,
                                            'coinname' => $coinAddress,
                                            'fee' => $sfee,
                                            'txid' => $token_action_trace['trx_id'],
                                            'num' => $final_amount,
                                            'mum' => $final_amount,
                                            'addtime' => time(),
                                            'status' => 1
                                        ]);

                                        if (check_arr($rs)) {
                                            Db::commit();
                                            echo '轮询成功';
                                        } else {
                                            Db::rollback();
                                        }
                                    }
                                }
                            }
                        }catch (Exception $e){
                            Db::rollback();
                            $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";
                        }
                    }

                }


                if($error_info){
                    throw new Exception($error_info);
                }

                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


    }

    //ETH 子主地址同步
	public function qianbaosync()
	{
        $coin = input('param.coin', 'eth', 'string');

        $fileName = CRONLOCK_PATH.__FUNCTION__."_".$coin.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//

                $coinconf = Db::name('coin')->where(['name' => $coin, 'status' => 1])->find();
                if ($coinconf['type'] != 'eth' && $coinconf['type'] != 'token') {
                    throw new Exception($coin."不存在的币种类型!");
                }
                $dj_username = $coinconf['dj_yh'];
                $dj_address = $coinconf['dj_zj'];
                $dj_port = $coinconf['dj_dk'];
                $CoinClient = EthClient($dj_address,$dj_port);
                $json = $CoinClient->eth_blockNumber(true);

                if (empty($json) || $json <= 0) {
                    throw new Exception($coin."钱包连接失败!");
                }

                //筛选数据库中钱包中大于 0.01 的用户 分组
                $offset = 100;
                $coinb = $coin.'b';
                $coinp = $coin.'p';
                for ($i = 0; $i < 24; $i++) {
                    if (date('H', time()) == $i && $coinconf['type'] == 'eth') {
                        $accounts = Db::name('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->limit($i * $offset, 100)->select();
                    }elseif(date('H', time()) == 1 && $coinconf['type'] == 'token'){
                        $accounts = Db::name('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->limit($i * $offset, 100)->select();
                    }
                }

                //筛选钱包中钱包中大于 0.01 的用户
                $fee = 0.005;
                if(count($accounts) > 0) {
                    foreach ($accounts as $k => $v) {
                        if ($coinconf['type'] == 'eth') {
                            $num = $CoinClient->eth_getBalance($v[$coinb]);
                        } elseif ($coinconf['type'] == 'token') {
                            $call = [
                                'to' => $coinconf['token_address'],
                                'data' => '0x70a08231'.$CoinClient->data_pj($v[$coinb])
                            ];
                            $num = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , $coinconf['decimals']);
                        }

                        if ($num > 0.01) {
                            //转币小脚本 进行同步
                            do {
                                if ($coinconf['type'] == 'eth') {
                                    $num = $num - $fee;
                                    $tradeInfo = [[
                                        'from' => $v[$coin . 'b'],
                                        'to' => $dj_username,
                                        'gas' => '0x1046a',
                                        'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($num))),
                                        'gasPrice' => '0x189640200', //$CoinClient->eth_gasPrice()
                                    ]];
                                    $sendrs = $CoinClient->eth_sendTransaction($v[$coinb], $v[$coinp], $tradeInfo);
                                } elseif($coinconf['type'] == 'token') {
                                    $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($num) , $coinconf['decimals']));
                                    $tradeInfo = [[
                                        'from' => $v[$coin . 'b'],
                                        'to' => $coinconf['token_address'],
                                        'gas' => '0x1046a',
                                        'data' => '0xa9059cbb' . $CoinClient->data_pj($dj_username, $value),
                                        'gasPrice' => '0x189640200', //$CoinClient->eth_gasPrice()
                                    ]];
                                    $sendrs = $CoinClient->eth_sendTransaction($v[$coinb], $v[$coinp], $tradeInfo);
                                }

                            } while ($sendrs->error != '');
                        }
                    }
                }

                $info['name'] = $coin;
                $info['version'] = hexdec($CoinClient->eth_protocolVersion());
                $info['headers'] = hexdec($CoinClient->eth_blockNumber());
                $info['accounts'] = $CoinClient->eth_accounts();

                $sum = 0;
                foreach ($info['accounts'] as $key => $value) {
                    $sum += $CoinClient->eth_getBalance($value);
                }
                $coinbase = $CoinClient->eth_getBalance($dj_username);
                echo $coin . ' 账户总数量：' . $sum . "<br>";
                echo $dj_username . ' 主地址总数量：' . $coinbase;

                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }



	}

	//三日趋势图
	public function tendencyb8c3b3d94512472db8()
	{
        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//
                $error_info ='';
                foreach (config('market') as $k => $v) {
                    try {
                        echo '----计算趋势----' . $v['name'] . '------------';
                        $tendency_time = 4;
                        $t = time();
                        $tendency_str = $t - (24 * 60 * 60 * 3);
                        $x = 0;

                        for (; $x <= 18; $x++) {
                            $na = $tendency_str + (60 * 60 * $tendency_time * $x);
                            $nb = $tendency_str + (60 * 60 * $tendency_time * ($x + 1));
                            $b = Db::name('TradeLog')->where(['addtime' => [['egt', $na], ['lt', $nb]], 'market' => $v['name']])->max('price');
                            if (!$b) {
                                $houprice = Db::name('market')->field('hou_price')->where(['name' => $v['name']])->value('hou_price');
                                $b = $houprice;
                            }

                            $rs[] = array($na, $b);
                        }

                        Db::name('Market')->where(['name' => $v['name']])->setField('tendency', json_encode($rs));
                        unset($rs);
                        echo '计算成功!';
                        echo "\n";
                    }catch (Exception $e){
                        $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";
                    }
                }

                echo '趋势计算0k ' . "\n";
                if($error_info){
                    throw new Exception($error_info);
                }
                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


	}

	//计算行情
	public function chartb8c3b3d94512472db8()
	{
        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//
                $error_info ='';
                foreach (config('market') as $k => $v) {
                    try{
                        $this->setTradeJson($v['name']);
                    }catch (Exception $e){
                        $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";
                    }
                }

                echo '计算行情0k ' . "\n";
                if($error_info){
                    throw new Exception($error_info);
                }
                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


	}

    //计算行情
	private function setTradeJson($market)
	{
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

        $trade = Db::name('Market')->where(['name' => $market])->value('trade');
//        if ($trade == 0) {
//            return false;
//        }

		foreach ($timearr as $k => $v) {
			$tradeJson = Db::name('TradeJson')->where(array('market' => $market, 'type' => $v))->order('id desc')->find();

			//判断是全量获取数据还是增量
			if ($tradeJson) {
				$addtime = $tradeJson['addtime'];
			} else {
				$addtime = Db::name('TradeLog')->where(array('market' => $market))->order('id asc')->value('addtime');
			}

			//得到要生成的成交总量

			if ($addtime) {
                $youtradelog = Db::name('TradeLog')->where(['addtime'=>['egt',$addtime],'market'=>$market])->sum('num');
			}else{
                $youtradelog = 0;
            }

			if ($youtradelog) {
			    //以1分钟做为单位
				if ($v == 1) {
					$start_time = $addtime;
				} else {
					$start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $v) * $v, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
				}

				$x = 0;

				for (; $x <= 20; $x++) {
					$na = $start_time + (60 * $v * $x);
					$nb = $start_time + (60 * $v * ($x + 1));

					if (time() < $na) {
						break;
					}

					//该段成交量
                    $sum = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->sum('num');

					//得到该段的开盘价，收盘价，最高价，最低价
					if ($sum) {

                        $sta = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->order('id asc')->value('price');
                        $max = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->max('price');
                        $min = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->min('price');
                        $end = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->order('id desc')->value('price');
						$d = array($na, $sum, $sta, $max, $min, $end);
                        //写入或更新到k图的表
						if (Db::name('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->find()) {
							Db::name('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->update(array('data' => json_encode($d)));
						} else {

							Db::name('TradeJson')->insert(array('market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v));
							Db::name('TradeJson')->execute('commit');
//							Db::name('TradeJson')->where(array('market' => $market, 'data' => '', 'type' => $v))->delete();
//							Db::name('TradeJson')->execute('commit');
						}
					} //else {
//						Db::name('TradeJson')->insert(array('market' => $market, 'data' => '', 'addtime' => $na, 'type' => $v));
//						Db::name('TradeJson')->execute('commit');
//					}
				}
			}
		}

		return '计算成功!';
	}

	//设置队列
    public function queue_3a32849e0c77173c325c72a3c2d7aa49()
    {

        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//

                $time = time();
                if (cache('queue_chk_'.CONTROLLER_NAME.'_'.ACTION_NAME)){
                    throw new Exception("超时!");
                }else{
                    cache('queue_chk_'.CONTROLLER_NAME.'_'.ACTION_NAME,$time,60);
                }
                $file_path = DATABASE_PATH . '/check_queue.json';
                $timeArr = array();

                if (file_exists($file_path)) {
                    $timeArr = file_get_contents($file_path);
                    $timeArr = json_decode($timeArr, true);
                }

                array_unshift($timeArr, $time);
                $timeArr = array_slice($timeArr, 0, 3);

                file_put_contents($file_path, json_encode($timeArr));

                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


    }

    //设置自动交易的参考最低价和最高价
    public function set_api_hign_or_low_price()
    {
        $fileName = CRONLOCK_PATH.__FUNCTION__.".txt";
        $fp = fopen($fileName, 'a+b');

        if(flock($fp,LOCK_EX | LOCK_NB))
        {
            $f_size = filesize($fileName);
            //超过2MB大小，就会把文件的内容清空
            if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2 )){
                ftruncate($fp,0);
            }
            $period_time = '开始时间：'.date("Y-m-d H:i:s",time());
            try {
                //--业务开始----//
                $market_data = cache('set_api_hign_or_low_price');
                if (!$market_data) {
                    $market_data = Db::table('weike_market_control')->select();
                    cache('set_api_hign_or_low_price', $market_data);
                }

                $data = mCurl('http://data.gate.io/api2/1/tickers');
                $error_info = '';
                foreach ($market_data as $k => $v){
                    try {
                        $xnb = explode('_', $v['name'])[0];
                        $rmb = explode('_', $v['name'])[1];
                        if ($rmb === 'hkd') {
                            if ($data[$xnb . '_usdt']['result'] !== 'true') {
                                continue;
                            }
                            Db::table('weike_market_control')->where(['id' => $v['id']])->setField(['api_min_price' => $data[$xnb . '_usdt']['low24hr']]);
                            Db::table('weike_market_control')->where(['id' => $v['id']])->setField(['api_max_price' => $data[$xnb . '_usdt']['high24hr']]);
                        } elseif ($rmb === 'btc') {
                            if ($data[$xnb . '_btc']['result'] !== 'true') {
                                continue;
                            }
                            Db::table('weike_market_control')->where(['id' => $v['id']])->setField(['api_min_price' => $data[$xnb . '_btc']['low24hr']]);
                            Db::table('weike_market_control')->where(['id' => $v['id']])->setField(['api_max_price' => $data[$xnb . '_btc']['high24hr']]);
                        }
                    }catch (Exception $e){
                        $error_info .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n";
                    }
                }

                if($error_info){
                    throw new Exception($error_info);
                }
                //--业务结束----//
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }catch (Exception $e){
                $period_time .=' --- 结束时间：'.date("Y-m-d H:i:s",time())."\r\n";
                $period_time .='错误信息： '.$e->getMessage()."\r\n".$e->getFile()." --所在行： ".$e->getLine()."\r\n".$e->getTraceAsString()."\r\n\r\n";
                fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
                fwrite($fp,$period_time);
                flock($fp,LOCK_UN);
                fclose($fp);
            }
        }
        else
        {
            return;
        }


    }
    //华克金行情
    public function wcg_hq($market,$timearr)
    {


        if (!$market){
            exit('请选择市场');
        }

        if (!$timearr){
            exit('请选择k线时间1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080');
        }
        $tradeJson = Db::name('TradeJson')->where(array('market' => $market, 'type' => $timearr))->order('id desc')->find();
        if ($tradeJson) {
            $addtime = $tradeJson['addtime'];
        } else {
            $addtime = Db::name('TradeLog')->where(array('market' => $market))->order('id asc')->value('addtime');
        }

        if ($addtime) {
            $youtradelog = Db::name('TradeLog')->where(['addtime'=>['egt',$addtime],'market'=>$market])->sum('num');
        }

        if ($youtradelog) {
            if ($timearr == 1) {
                $start_time = $addtime;
            } else {
                $start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $timearr) * $timearr, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
            }


            $x = 0;


            for (; $x <= 20; $x++) {
                $na = $start_time + (60 * $timearr * $x);
                $nb = $start_time + (60 * $timearr * ($x + 1));

                if (time() < $na) {
                    break;
                }

                $sum = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->sum('num');

                if ($sum) {
                    $sta = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->order('id asc')->value('price');
                    $max = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->max('price');
                    $min = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->min('price');
                    $end = Db::name('TradeLog')->where(['addtime'=>[['egt',$na],['lt',$nb]],'market'=>$market])->order('id desc')->value('price');
                    $d = array($na, $sum, $sta, $max, $min, $end);

                    if (Db::name('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $timearr))->find()) {
                        Db::name('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $timearr))->update(array('data' => json_encode($d)));
                    } else {
                        Db::name('TradeJson')->insert(array('market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $timearr));
                        Db::name('TradeJson')->execute('commit');
                        Db::name('TradeJson')->where(array('market' => $market, 'data' => '', 'type' => $timearr))->delete();
                        Db::name('TradeJson')->execute('commit');
                    }
                } else {
                    Db::name('TradeJson')->insert(array('market' => $market, 'data' => '', 'addtime' => $na, 'type' => $timearr));
                    Db::name('TradeJson')->execute('commit');
                }
            }
        }

        return '成功!';

    }
}

?>