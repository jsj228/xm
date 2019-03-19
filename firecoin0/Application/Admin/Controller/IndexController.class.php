<?php
namespace Admin\Controller;

class IndexController extends AdminController
{
	public function index()
	{
        set_time_limit(0);
		$arr = array();
		$arr['reg_sum'] = M('User')->count();
		$arr['cny_num'] = $arr['cny_num'] = M('UserCoin')->join('weike_user ON weike_user.id = weike_user_coin.userid')->where(['usertype'=>0])->sum('cny') + M('UserCoin')->join('weike_user ON weike_user.id = weike_user_coin.userid')->where(['usertype'=>0])->sum('cnyd');
		$arr['trance_mum'] = M('TradeLog')->sum('mum');

		if (10000 < $arr['trance_mum']) {
			$arr['trance_mum'] = round($arr['trance_mum'] / 10000) . '万';
		}

		if (100000000 < $arr['trance_mum']) {
			$arr['trance_mum'] = round($arr['trance_mum'] / 100000000) . '亿';
		}

		$arr['art_sum'] = M('Article')->count();
		$data = array();
		//系统 充值/提现 统计图(40天)
		$time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (40 * 24 * 60 * 60);
		$i = 0;

		for (; $i < 41; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time - (60 * 60), 'Y-m-d');
			$mycz = M('Mycz')->where(array(
				'status'  => array('exp', ' IN (2,5) '),
				'addtime' => array(
					array('gt', $a),
					array('lt', $time)
				)
			))->sum('num');

			$mytx = M('Mytx')->where(array(
				'status'  => 1,
				'addtime' => array(
					array('gt', $a),
					array('lt', $time)
				)
			))->sum('num');

			$mycz = $mycz?$mycz:0;
			$mytx = $mytx?$mytx:0;
			$data['cztx'][] = array('date' => $date, 'charge' => $mycz, 'withdraw' => $mytx);
		}
		//C2C商家充值提现
//		$time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (40 * 24 * 60 * 60);
//        $time_end = $time + (60 * 60 * 24*41);
//
//        $buysjs = M('user_c2c_log')->where(array(
//            'buyid'=>array('in','28830,28831,5392,28974'),
//            'status'=>1,
//            'addtime' => array(
//                array('gt', $time),
//                array('lt', $time_end)
//            )
//        ))->field('addtime,num')->select();
//
//        $sellsjs = M('user_c2c_log')->where(array(
//            'sellid'  =>array('in','28830,28831,5392,28974'),
//            'status'  =>1,
//            'addtime' => array(
//                array('gt', $time),
//                array('lt', $time_end)
//            )
//        ))->field('addtime,price')->select();
//
//        for ($i = 0; $i < 41; $i++) {
//            $a = $time;
//            $time = $time+86400;
//            $date = addtime($time - 3600, 'Y-m-d');
//            $buysj = 0;
//            $sellsj = 0;
//            foreach ($buysjs as $k=>$v){
//                if($v['addtime']>$a && $v['addtime']<$time) $buysj += $v['num'];
//            }
//            foreach ($sellsjs as $k=>$v){
//                if($v['addtime']>$a && $v['addtime']<$time) $sellsj += $v['price'];
//            }
//
//            $data['sj'][] = array('date' => $date, 'charge' => $buysj,'withdraw' => $sellsj);
//        }


		$time = time() - (30 * 24 * 60 * 60);
        $time_end = $time + (60 * 60 * 24*61);

        $users = M('User')->where(array(
            'addtime' => array(
                array('gt', $time),
                array('lt', $time_end)
            )
        ))->field('addtime')->select();

        for ($i = 0; $i < 60; $i++) {
            $a = $time;
            $time = $time + 86400;//(60 * 60 * 24)
            $date = addtime($time, 'Y-m-d');
            $user = 0;
            foreach ($users as $k=>$v){
                if($v['addtime']>$a && $v['addtime']<$time) $user++;
            }
            if ($user) $data['reg'][] = array('date' => $date, 'sum' => $user);
        }

        //月交易量
		$time = time() - (30 * 24 * 60 * 60);
        $time_end = $time+(60 * 60 * 24*60);
        $users = M('TradeLog tl')
            ->join('weike_user as u on tl.userid=u.id','left')
            ->where(array('u.usertype'=>0,'tl.addtime' => array(array('gt',$time), array('lt', $time_end))))
            ->field('tl.addtime,tl.mum')->select();

		for ($i = 0; $i < 60; $i++) {
			$b = $time;
			$time = $time + 86400;
			$date = addtime($time, 'Y-m-d');

			$user = 0;
			foreach ($users as $v){
			    if($v['addtime']>$b && $v['addtime']<$time) $user += $v['mum'];
            }
			if ($user) $data['jiaoyi'][] = array('date' => $date, 'sum' => round($user,2));
	   }

        //C2C商户充值提现
        $time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (40 * 24 * 60 * 60);
        $time_end = $time + (60 * 60 * 24 * 41);
        $c2cbuys = M('user_c2c_log')->where(array(
            'type'=>1,
            'status'=>1,
            'addtime' => array(array('gt', $time), array('lt', $time_end))
        ))->field('addtime,num')->select();

        $c2csells = M('user_c2c_log')->where(array(
			'type'=>2,
            'status'  =>1,
            'addtime' => array(array('gt', $time), array('lt', $time_end))
        ))->field('addtime,price')->select();

        for ($i = 0; $i < 41; $i++) {
            $a = $time;
            $time = $time + (86400);
            $date = addtime($time - (3600), 'Y-m-d');
            $c2cbuy = 0;
            $c2csell=0;
            foreach ($c2cbuys as $v){
                if($v['addtime']>$a && $v['addtime']<$time) $c2cbuy +=$v['num'];
            }
            foreach ($c2csells as $v){
                if($v['addtime']>$a && $v['addtime']<$time) $c2csell +=$v['price'];
            }
            $data['c2c'][] = array('date' => $date, 'charge' => $c2cbuy, 'withdraw' => $c2csell);
        }

		$this->assign('cztx', json_encode($data['cztx']));
		$this->assign('jiaoyi', json_encode($data['jiaoyi']));
		$this->assign('reg', json_encode($data['reg']));
		$this->assign('c2c', json_encode($data['c2c']));
		$this->assign('arr', $arr);

		$this->display();
	}

	public function coin()
	{
        $coinname = strval(I('coinname'));
		if (!$coinname) {
			$coinname = C('xnb_mr');
		}

		if (empty($coinname)) {
			echo '请去设置--其他设置里面设置默认币种';
			exit();
		}

		if (!M('Coin')->where(['name' => $coinname])->find()) {
			echo '币种不存在,请去设置里面添加币种，并清理缓存';
			exit();
		}

		$this->assign('coinname', $coinname);
		$data = [];
        $data['trance_b'] = M('UserCoin')->join('weike_user ON weike_user.id = weike_user_coin.userid')->where(['usertype'=>0])->sum($coinname);
        $data['trance_s'] = M('UserCoin')->join('weike_user ON weike_user.id = weike_user_coin.userid')->where(['usertype'=>0])->sum($coinname . 'd');
		$data['trance_num'] = $data['trance_b'] + $data['trance_s'];
		$data['trance_song'] = M('Myzr')->where(['coinname' => $coinname,'status'=>1])->sum('fee');
		$data['trance_fee'] = M('Myzc')->where(['coinname' => $coinname,'status'=>1])->sum('fee');

//        筛选出特殊用户
//        $special_id = M('User')->where(['usertype' => array('gt',0)])->field('id')->select();
//        $xnb_sum_b = '';
//        $xnb_sum_s = '';
//        foreach ($special_id as $k => $v){
//            $xnb_sum_b += M('UserCoin')->where(['userid' => $v['id']])->getField($coinname);
//            $xnb_sum_s += M('UserCoin')->where(['userid' => $v['id']])->getField($coinname . 'd');
//        }
//        $data['trance_b'] = $data['trance_b'] - $xnb_sum_b;
//        $data['trance_s'] = $data['trance_s'] - $xnb_sum_s;
//        $data['trance_num'] = $data['trance_b'] + $data['trance_s'];

        $dj_username = C('coin')[$coinname]['dj_yh'];
        $dj_password = C('coin')[$coinname]['dj_mm'];
        $dj_address = C('coin')[$coinname]['dj_zj'];
        $dj_port = C('coin')[$coinname]['dj_dk'];
		if (C('coin')[$coinname]['type'] == 'bit') {
			$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
			$json = $CoinClient->getinfo();

			if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包对接失败！');
			}

			$data['trance_mum'] = $json['balance'];
		} elseif(C('coin')[$coinname]['type'] == 'eth' || C('coin')[$coinname]['type'] == 'token'){
            $CoinClient = EthClient($dj_address,$dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包对接失败！');
            }

            $accounts = $CoinClient->eth_accounts();
            $sum = 0;
            foreach ($accounts as $key => $value) {
                if(C('coin')[$coinname]['type'] == 'eth'){
                    $sum += $CoinClient->eth_getBalance($value);
                }elseif(C('coin')[$coinname]['type'] == 'token'){
                    $call = [
                        'to' => C('coin')[$coinname]['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($value)
                    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , C('coin')[$coinname]['decimals']);
                }

            }
            $data['trance_mum'] = $sum;
        } elseif (C('coin')[$coinname]['type'] == 'eos') {
            $EosClient = EosClient($dj_address, $dj_port);
            $json = $EosClient->get_info();
            if (empty($json)) {
                $this->error('钱包对接失败!!');
            }
            $tradeInfo = [
                "account" => C('coin')[$coinname]['dj_yh'],
                "code" => C('coin')[$coinname]['token_address'],
                "symbol" => $coinname,
            ];
            $account_info = $EosClient->get_currency_balance($tradeInfo);
            $data['trance_mum'] = $account_info[0];
        } else {
			$data['trance_mum'] = 0;
		}

		$this->assign('data', $data);
		$market_json = M('CoinJson')->where(['name' => I('get.coinname')])->order('id desc')->find();

		if ($market_json) {
			//$addtime = $market_json['addtime'] + 60;
			$addtime = $market_json['addtime'];
            if (time() > $addtime) {
                $addtime = $market_json['addtime'] + 60;
            }
		} else {
			$addtime = M('Myzr')->where(['coinname' => I('get.coinname')])->order('id asc')->find()['addtime'];
		}

		if (!$addtime) {
			$addtime = time();
		}
        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
        if ($addtime) {
			$trade_num = M('UserCoin')->sum($coinname);
			$trade_mum = M('UserCoin')->sum($coinname . 'd');
			$aa = $trade_num + $trade_mum;
            $bb = $data['trance_mum'];

			$trade_fee_buy = M('Myzr')->where(['coinname' => $coinname, 'addtime' => [['egt', $start], ['elt', $end]]])->sum('fee');
			$trade_fee_sell = M('Myzc')->where(['coinname'    => $coinname,	'addtime' => [['egt', $start], ['elt', $end]]])->sum('fee');
			$d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            // 如果找到添加时间等于end的时间
			if (M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
				M('CoinJson')->where(['name' => I('get.coinname'), 'addtime' => $end])->save(['data' => json_encode($d)]);
			} else {
				M('CoinJson')->add(['name' => $coinname, 'data' => json_encode($d), 'addtime' => $end]);
			}
		}

		$tradeJson = M('CoinJson')->where(array('name' => I('get.coinname')))->order('id asc')->limit(100)->select();
		foreach ($tradeJson as $k => $v) {
			if ((addtime($v['addtime']) != '---') && (14634049 < $v['addtime'])) {
				$date = addtime($v['addtime'], 'Y-m-d H:i:s');
				$json_data = json_decode($v['data'], true);
				$cztx[] = ['date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]];
			}
		}

		$this->assign('cztx', json_encode($cztx));
		$this->display();
	}

	public function coinSet()
	{
        $coinname = strval(I('coinname'));
		if (!$coinname) {
			$this->error('参数错误！');
		}

		if (M('CoinJson')->where(array('name' => I('get.coinname')))->delete()) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function market()
	{
        $market = strval(I('market'));
		if (!$market) {
			$market = C('market_mr');
		}

		if (!$market) {
			echo '请去设置--其他设置里面设置默认市场';
			exit();
		}

		$market = trim(I('get.market'));
		$xnb = explode('_', $market)[0];
		$rmb = explode('_', $market)[1];
		$this->assign('xnb', $xnb);
		$this->assign('rmb', $rmb);
		$this->assign('market', $market);
		$data = array();
		$data['trance_num'] = M('TradeLog')->where(array('market' => $market))->sum('num');
		$data['trance_buyfee'] = M('TradeLog')->where(array('market' => $market))->sum('fee_buy');
		$data['trance_sellfee'] = M('TradeLog')->where(array('market' => $market))->sum('fee_sell');
		$data['trance_fee'] = $data['trance_buyfee'] + $data['trance_sellfee'];
		$data['trance_mum'] = M('TradeLog')->where(array('market' => $market))->sum('mum');
		$data['trance_ci'] = M('TradeLog')->where(array('market' => $market))->count();
		$market_json = M('MarketJson')->where(array('name' => $market))->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		} else {
			$addtime = M('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
		}

		if (!$addtime) {
			$addtime = time();
		}

		if ($addtime) {
			if ($addtime < (time() + (60 * 60 * 24))) {
				$t = $addtime;
				$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
				$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
				$trade_num = M('TradeLog')->where(array(
					'market'  => $market,
					'addtime' => array(
						array('egt', $start),
						array('elt', $end)
						)
					))->sum('num');

				if ($trade_num) {
					$trade_mum = M('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('mum');
					$trade_fee_buy = M('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('fee_buy');
					$trade_fee_sell = M('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('fee_sell');
					$d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

					if (M('MarketJson')->where(array('name' => $market, 'addtime' => $end))->find()) {
						M('MarketJson')->where(array('name' => $market, 'addtime' => $end))->save(array('data' => json_encode($d)));
					}
					else {
						M('MarketJson')->add(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
					}
				}
				else {
					$d = null;

					if (M('MarketJson')->where(array('name' => $market, 'data' => ''))->find()) {
						M('MarketJson')->where(array('name' => $market, 'data' => ''))->save(array('addtime' => $end));
					}
					else {
						M('MarketJson')->add(array('name' => $market, 'data' => '', 'addtime' => $end));
					}
				}
			}
		}

		$tradeJson = M('MarketJson')->where(array('name' => $market))->order('id asc')->limit(100)->select();

		foreach ($tradeJson as $k => $v) {
			if ((addtime($v['addtime']) != '---') && (14634049 < $v['addtime'])) {
				$date = addtime($v['addtime'] - (60 * 60 * 24), 'Y-m-d H:i:s');
				$json_data = json_decode($v['data'], true);
				$cztx[] = array('date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]);
			}
		}

		$this->assign('cztx', json_encode($cztx));
		$this->assign('data', $data);
		$this->display();
	}

	public function marketSet()
	{
        $market = strval(I('market'));
		if (!$market) {
			$this->error('参数错误！');
		}

		if (M('MarketJson')->where(array('name' => $market))->delete()) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
}

?>