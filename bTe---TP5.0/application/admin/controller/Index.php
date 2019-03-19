<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;
class Index extends AdminCommon{

	public function index()
	{
//        ini_set('max_execution_time','0');
//        set_time_limit(100);
		$arr = array();
		$arr['reg_sum'] = Db::name('User')->count();

        $cny_sum = Db::name('user_coin uc')
            ->join('user u','u.id = uc.userid')
            ->where(['u.usertype'=>0])
            ->sum('uc.cny');

        $cnyd_sum = Db::name('user_coin uc')
            ->join('user u','u.id = uc.userid')
            ->where(['u.usertype'=>0])
            ->sum('uc.cnyd');

        $arr['cny_num'] = $cny_sum + $cnyd_sum;

		$arr['trance_mum'] = Db::name('TradeLog')->sum('mum');

		if (10000 < $arr['trance_mum']) $arr['trance_mum'] = round($arr['trance_mum'] / 10000) . '万';
		if (100000000 < $arr['trance_mum']) $arr['trance_mum'] = round($arr['trance_mum'] / 100000000) . '亿';

		$arr['art_sum'] = Db::name('Article')->count();
		$data = array();

		$time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (40 * 24 * 60 * 60);
		$i = 0;

        $mycz_arr = Db::name('Mycz')->where(array('status'  => array('IN',[1,2,5])))->field('status,addtime,num')->select();


		for ($i=0; $i <= 40; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time - (60 * 60), 'Y-m-d');

//            echo $a;
//			echo "++".$time;
//            echo "++".$date;die;
            $mycz = 0;
            $mytx = 0;
            foreach ($mycz_arr as $k=>$v){
                if(($v['status']==2 || $v['status']==5) && $v['addtime']>$a && $v['addtime']<$time) $mycz += $v['num'];
                if($v['status']==1 && $v['addtime']>$a && $v['addtime']<$time) $mytx += $v['num'];
            }

//			$mycz = DB::name('Mycz')->where(array(
//				'status'  => array('IN',2,5),
//				'addtime' => array('between',"$a,$time"),
//				))->sum('num');
//			$mytx = DB::name('Mytx')->where(array(
//				'status'  => 1,
//				'addtime' => array('between',"$a,$time")
//				))->sum('num');

//			$mycz = $mycz?$mycz:0;
//			$mytx = $mytx?$mytx:0;
            $data['cztx'][] = array('date' => $date, 'charge' => $mycz, 'withdraw' => $mytx);
		}

		$time = time() - (30 * 24 * 60 * 60);
        $user_arr = DB::name('User')->field('addtime')->select();
		for ($i=0; $i < 60; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time, 'Y-m-d');

			$user_sum = 0;
			foreach ($user_arr as $v){
			    if($v['addtime']>$a && $v['addtime']<$time) $user_sum +=1;
            }
//			$user = DB::name('User')->where(array(
//				'addtime' => array('between',"$a,$time")
//				))->count();
//			$user = $user?$user:0;
			if ($user_sum) {
				$data['reg'][] = array('date' => $date, 'sum' => $user_sum);
			}
		}

//		echo 111;die;
        //月交易量
        $mum_arr = Db::name('trade_log tl')->join('user u','tl.userid=u.id','left')
            ->field('tl.addtime,tl.mum')->where(array('u.usertype'=>0))->select();


		$time = time() - (30 * 24 * 60 * 60);
		$i = 0;
		for (; $i < 60; $i++) {
			$b = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time, 'Y-m-d');
			$sum_mum = 0;
			foreach ($mum_arr as $v){
			    if($v['addtime']>$b && $v['addtime']<$time) $sum_mum += $v['mum'];
            }

//			$user = round(DB::table('weike_trade_log')
//				->alias('tl')
//				->join('weike_user u','tl.userid=u.id','left')
//				->where(array('u.usertype'=>0,'tl.addtime' => array('between',"$b,$time")))
//				->sum('tl.mum'),2);
//			$user = $user?$user:0;

			$data['jiaoyi'][] = array('date' => $date, 'sum' => $sum_mum);
			
			
	   }
		//年交易量
		// $time=mktime(0, 0, 0, date('m'), date('d'), date('Y')-1);//获取时间戳
		// $i = 0;
		// for (; $i < 60; $i++) {
		// 	$b = $time;
		// 	$time = $time + (60 * 60 * 24 * 30);
		// 	$date = addtime($time, 'Y-m-d');
		// 	$user = round(DB::name('TradeLog')
		// 		->alias('tl')
		// 		->join('weike_user as u on tl.userid=u.id','left')
		// 		->where(array('u.usertype'=>0,'tl.addtime' => array(array('gt', $b), array('lt', $time))))
		// 		->SUM('tl.mum'),2);
		// 	S('atralog',$user);
		// 	if ($user) {
		// 		$data['niao'][] = array('date' => $date, 'sum' => $user);
		// 	}
		// }
	   //C2C充值提现
		$time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (40 * 24 * 60 * 60);


        $c2cbuy = Db::name('user_c2c_log')->where(array('buyid'=>array('neq','')))->field('addtime,num')->select();
        $c2csell = DB::name('user_c2c_log')->where(array('sellid'  =>array('neq','')))->field('addtime,price')->select();

		for ($i = 0; $i < 41; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time - (60 * 60), 'Y-m-d');
            $charge = 0;$withdraw = 0;
            foreach ($c2cbuy as $v){
                if($v['addtime']>$a && $v['addtime']<$time) $charge+=$v['num'];
            }
            foreach ($c2csell as $v){
                if($v['addtime']>$a && $v['addtime']<$time) $withdraw+=$v['price'];
            }


//			$c2cbuy = DB::name('user_c2c_log')->where(array(
//				'buyid'=>array('neq',''),
//				'addtime' => array('between',"$a,$time")
//                ))->sum('num');
//
//			$c2csell = DB::name('user_c2c_log')->where(array(
//				'sellid'  =>array('neq',''),
//				'addtime' => array('between',"$a,$time")
//			))->sum('price');

//			$c2cbuy = $c2cbuy?$c2cbuy:0;
//			$c2csell = $c2csell?$c2csell:0;
			$data['c2c'][] = array('date' => $date, 'charge' => $charge, 'withdraw' => $withdraw);
		}
	 
		$this->assign('cztx', json_encode($data['cztx']));
		$this->assign('jiaoyi', json_encode($data['jiaoyi']));
		$this->assign('niao', json_encode($data['niao']));
		$this->assign('reg', json_encode($data['reg']));
		$this->assign('c2c', json_encode($data['c2c']));
		$this->assign('arr', $arr);

		return $this->fetch();
	}

	public function coin()
	{
        $coinname = strval(input('coinname'));
		if (!$coinname) {
			$coinname = config('xnb_mr');
		}

		if (empty($coinname)) {
			echo '请去设置--其他设置里面设置默认币种';
			exit();
		}

		if (!DB::name('Coin')->where(['name' => $coinname])->find()) {
			echo '币种不存在,请去设置里面添加币种，并清理缓存';
			exit();
		}

		$this->assign('coinname', $coinname);
		$data = [];
        $data['trance_b'] = DB::table('weike_user_coin')->join('weike_user','weike_user.id = weike_user_coin.userid')->where(['usertype'=>0])->sum($coinname);
        $data['trance_s'] = DB::table('weike_user_coin')->join('weike_user','weike_user.id = weike_user_coin.userid')->where(['usertype'=>0])->sum($coinname . 'd');
		$data['trance_num'] = $data['trance_b'] + $data['trance_s'];
		$data['trance_song'] = DB::name('Myzr')->where(['coinname' => $coinname,'status'=>1])->sum('fee');
		$data['trance_fee'] = DB::name('Myzc')->where(['coinname' => $coinname,'status'=>1])->sum('fee');

//        筛选出特殊用户
//        $special_id = DB::name('User')->where(['usertype' => array('gt',0)])->field('id')->select();
//        $xnb_sum_b = '';
//        $xnb_sum_s = '';
//        foreach ($special_id as $k => $v){
//            $xnb_sum_b += DB::name('UserCoin')->where(['userid' => $v['id']])->value($coinname);
//            $xnb_sum_s += DB::name('UserCoin')->where(['userid' => $v['id']])->value($coinname . 'd');
//        }
//        $data['trance_b'] = $data['trance_b'] - $xnb_sum_b;
//        $data['trance_s'] = $data['trance_s'] - $xnb_sum_s;
//        $data['trance_num'] = $data['trance_b'] + $data['trance_s'];

        $dj_username = config('coin')[$coinname]['dj_yh'];
        $dj_password = config('coin')[$coinname]['dj_mm'];
        $dj_address = config('coin')[$coinname]['dj_zj'];
        $dj_port = config('coin')[$coinname]['dj_dk'];
		if (config('coin')[$coinname]['type'] == 'bit') {
			$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
      
			$json = $CoinClient->getinfo();

			if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包对接失败！');
			}

			$data['trance_mum'] = $json['balance'];
		} elseif(config('coin')[$coinname]['type'] == 'eth' || config('coin')[$coinname]['type'] == 'token'){
            $CoinClient = EthClient($dj_address,$dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包对接失败！');
            }

            $accounts = $CoinClient->eth_accounts();
            $sum = 0;
            foreach ($accounts as $key => $value) {
                if(config('coin')[$coinname]['type'] == 'eth'){
                    $sum += $CoinClient->eth_getBalance($value);
                }elseif(config('coin')[$coinname]['type'] == 'token'){
                    $call = [
                        'to' => config('coin')[$coinname]['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($value)
                    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , config('coin')[$coinname]['decimals']);
                }

            }
            $data['trance_mum'] = $sum;
        } else {
			$data['trance_mum'] = 0;
		}

		$this->assign('data', $data);
		$market_json = DB::name('CoinJson')->where(['name' => input('get.coinname')])->order('id desc')->find();

		if ($market_json) {
			//$addtime = $market_json['addtime'] + 60;
			$addtime = $market_json['addtime'];
            if (time() > $addtime) {
                $addtime = $market_json['addtime'] + 60;
            }
		} else {
			$addtime = DB::name('Myzr')->where(['coinname' => input('get.coinname')])->order('id asc')->find()['addtime'];
		}

		if (!$addtime) {
			$addtime = time();
		}
        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
        if ($addtime) {
			$trade_num = DB::name('UserCoin')->sum($coinname);
			$trade_mum = DB::name('UserCoin')->sum($coinname . 'd');
			$aa = $trade_num + $trade_mum;
            $bb = $data['trance_mum'];

			$trade_fee_buy = DB::name('Myzr')->where(['coinname' => $coinname, 'addtime' => [['egt', $start], ['elt', $end]]])->sum('fee');
			$trade_fee_sell = DB::name('Myzc')->where(['coinname'    => $coinname,	'addtime' => [['egt', $start], ['elt', $end]]])->sum('fee');
			$d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            // 如果找到添加时间等于end的时间
			if (DB::name('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
				DB::name('CoinJson')->where(['name' => I('get.coinname'), 'addtime' => $end])->update(['data' => json_encode($d)]);
			} else {
				DB::name('CoinJson')->insert(['name' => $coinname, 'data' => json_encode($d), 'addtime' => $end]);
			}
		}

		$tradeJson = DB::name('CoinJson')->where(array('name' => input('get.coinname')))->order('id asc')->limit(100)->select();
		foreach ($tradeJson as $k => $v) {
			if ((addtime($v['addtime']) != '---') && (14634049 < $v['addtime'])) {
				$date = addtime($v['addtime'], 'Y-m-d H:i:s');
				$json_data = json_decode($v['data'], true);
				$cztx[] = ['date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]];
			}
		}

		$this->assign('cztx', json_encode($cztx));
		return $this->fetch();
	}

	public function coinSet()
	{
        $coinname = strval(input('coinname'));
		if (!$coinname) {
			$this->error('参数错误！');
		}

		if (DB::name('CoinJson')->where(array('name' => input('get.coinname')))->delete()) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function market()
	{
        $market = strval(input('market'));
		if (!$market) {
			$market = config('market_mr');
		}

		if (!$market) {
			echo '请去设置--其他设置里面设置默认市场';
			exit();
		}

		$market = trim(input('market'));
		$xnb = explode('_', $market)[0];
		$rmb = explode('_', $market)[1];
		$this->assign('xnb', $xnb);
		$this->assign('rmb', $rmb);
		$this->assign('market', $market);
		$data = array();
		$data['trance_num'] = DB::name('TradeLog')->where(array('market' => $market))->sum('num');
		$data['trance_buyfee'] = DB::name('TradeLog')->where(array('market' => $market))->sum('fee_buy');
		$data['trance_sellfee'] = DB::name('TradeLog')->where(array('market' => $market))->sum('fee_sell');
		$data['trance_fee'] = $data['trance_buyfee'] + $data['trance_sellfee'];
		$data['trance_mum'] = DB::name('TradeLog')->where(array('market' => $market))->sum('mum');
		$data['trance_ci'] = DB::name('TradeLog')->where(array('market' => $market))->count();
		$market_json = DB::name('MarketJson')->where(array('name' => $market))->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		} else {
			$addtime = DB::name('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
		}

		if (!$addtime) {
			$addtime = time();
		}

		if ($addtime) {
			if ($addtime < (time() + (60 * 60 * 24))) {
				$t = $addtime;
				$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
				$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
				$trade_num = DB::name('TradeLog')->where(array(
					'market'  => $market,
					'addtime' => array(
						array('egt', $start),
						array('elt', $end)
						)
					))->sum('num');

				if ($trade_num) {
					$trade_mum = DB::name('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('mum');
					$trade_fee_buy = DB::name('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('fee_buy');
					$trade_fee_sell = DB::name('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('fee_sell');
					$d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

					if (DB::name('MarketJson')->where(array('name' => $market, 'addtime' => $end))->find()) {
						DB::name('MarketJson')->where(array('name' => $market, 'addtime' => $end))->update(array('data' => json_encode($d)));
					}
					else {
						DB::name('MarketJson')->insert(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
					}
				}
				else {
					$d = null;

					if (DB::name('MarketJson')->where(array('name' => $market, 'data' => ''))->find()) {
						DB::name('MarketJson')->where(array('name' => $market, 'data' => ''))->update(array('addtime' => $end));
					}
					else {
						DB::name('MarketJson')->insert(array('name' => $market, 'data' => '', 'addtime' => $end));
					}
				}
			}
		}

		$tradeJson = DB::name('MarketJson')->where(array('name' => $market))->order('id asc')->limit(100)->select();

		foreach ($tradeJson as $k => $v) {
			if ((addtime($v['addtime']) != '---') && (14634049 < $v['addtime'])) {
				$date = addtime($v['addtime'] - (60 * 60 * 24), 'Y-m-d H:i:s');
				$json_data = json_decode($v['data'], true);
				$cztx[] = array('date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]);
			}
		}
		$this->assign('cztx', json_encode($cztx));
		$this->assign('data', $data);

		return $this->fetch();
	}

	public function marketSet()
	{
        $market = strval(input('market'));
     
		if (!$market) {
			$this->error('参数错误！');
		}

		if (DB::name('MarketJson')->where(array('name' => $market))->delete()) {
			return array('status'=>1,'msg'=>'操作成功！');

		} else {
			$this->error('操作失败！');
		}
	}
}

?>