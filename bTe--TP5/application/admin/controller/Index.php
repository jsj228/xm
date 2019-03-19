<?php

namespace app\admin\controller;

use think\Db;

class Index extends Admin
{
    public function index()
    {
        $arr = array();
        $arr['reg_sum'] = Db::name('User')->count();
        $arr['cny_num'] = Db::name('UserCoin')->sum('hkd') + Db::name('UserCoin')->sum('hkdd');
        $arr['trance_mum'] = Db::name('TradeLog')->sum('mum');
//		筛选出特殊用户
        $special_id = Db::name('User')->where(['usertype' => array('gt', 0)])->field('id')->select();
        $hkd_sum = 0;
        foreach ($special_id as $k => $v) {
            $hkd_sum += Db::name('UserCoin')->where(['userid' => $v['id']])->value('hkd') + Db::name('UserCoin')->where(['userid' => $v['id']])->value('hkdd');
        }
        $arr['cny_num'] = $arr['cny_num'] - $hkd_sum;
        if (10000 < $arr['trance_mum']) {
            $arr['trance_mum'] = round($arr['trance_mum'] / 10000) . '万';
        }

        if (100000000 < $arr['trance_mum']) {
            $arr['trance_mum'] = round($arr['trance_mum'] / 100000000) . '亿';
        }

        $arr['art_sum'] = Db::name('Article')->count();
        $data = array();
        $time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (29 * 24 * 60 * 60);
        $i = 0;

        $data['cztx'] =[];
        for (; $i < 30; $i++) {
            $a = $time;
            $time = $time + (60 * 60 * 24);
            $date = addtime($time - (60 * 60), 'Y-m-d');
            $mycz = Db::name('Mycz')->where(array(
                'status' => array('in', ' 1,2,5'),
                'addtime' => array(
                    array('gt', $a),
                    array('lt', $time)
                )
            ))->sum('num');
            $mytx = Db::name('Mytx')->where(array(
                'status' => 1,
                'addtime' => array(
                    array('gt', $a),
                    array('lt', $time)
                )
            ))->sum('num');

            if ($mycz || $mytx) {
                $data['cztx'][] = array('date' => $date, 'charge' => $mycz, 'withdraw' => $mytx);
            }
        }

        $time = time() - (30 * 24 * 60 * 60);
        $i = 0;

        $data['reg']=[];
        for (; $i < 31; $i++) {
            $a = $time;
            $time = $time + (60 * 60 * 24);
            $date = addtime($time, 'Y-m-d');
            $user = Db::name('User')->where(array(
                'addtime' => array(
                    array('gt', $a),
                    array('lt', $time)
                )
            ))->count();

            if ($user) {
                $data['reg'][] = array('date' => $date, 'sum' => $user);
            }
        }

        $this->assign('cztx', json_encode($data['cztx']));
        $this->assign('reg', json_encode($data['reg']));
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

        if (!Db::name('Coin')->where(['name' => $coinname])->find()) {
            echo '币种不存在,请去设置里面添加币种，并清理缓存';
            exit();
        }

        $this->assign('coinname', $coinname);
        $data = [];
        $data['trance_b'] = Db::name('UserCoin')->sum($coinname);
        $data['trance_s'] = Db::name('UserCoin')->sum($coinname . 'd');
        $data['trance_num'] = $data['trance_b'] + $data['trance_s'];
        $data['trance_song'] = Db::name('Myzr')->where(['coinname' => $coinname])->sum('fee');
        $data['trance_fee'] = Db::name('Myzc')->where(['coinname' => $coinname])->sum('fee');

//      筛选出特殊用户优化三次
        $special_id = Db::name('User')->where(['usertype' => array('gt', 0)])->field('id')->select();
        $xnb_sum_b = 0;
        $xnb_sum_s = 0;
        foreach ($special_id as $k => $v) {
            $xnb_sum_b += Db::name('UserCoin')->where(['userid' => $v['id']])->value($coinname);
            $xnb_sum_s += Db::name('UserCoin')->where(['userid' => $v['id']])->value($coinname . 'd');
        }
        $data['trance_b'] = $data['trance_b'] - $xnb_sum_b;
        $data['trance_s'] = $data['trance_s'] - $xnb_sum_s;
        $data['trance_num'] = $data['trance_b'] + $data['trance_s'];

        $dj_username = config('coin')[$coinname]['dj_yh'];
        $dj_password = config('coin')[$coinname]['dj_mm'];
        $dj_address = config('coin')[$coinname]['dj_zj'];
        $dj_port = config('coin')[$coinname]['dj_dk'];
        if (config('coin')[$coinname]['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            if ($coinname == 'btc' || $coinname == 'ltc') {
                $json = $CoinClient->getnetworkinfo();
                $block_balance = $CoinClient->getwalletinfo();

                $data['trance_mum'] = isset($block_balance['balance']) ? $block_balance['balance'] : 0;
            } else {
                $json = $CoinClient->getinfo();

                $data['trance_mum'] = isset($json['balance']) ? $json['balance'] : 0;
            }

            if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包对接失败！');
            }

        } elseif (config('coin')[$coinname]['type'] == 'eth' || config('coin')[$coinname]['type'] == 'token') {
            $CoinClient = EthClient($dj_address, $dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包对接失败！');
            }

            $accounts = $CoinClient->eth_accounts();
            $sum = 0;
            foreach ($accounts as $key => $value) {
                if (config('coin')[$coinname]['type'] == 'eth') {
                    $sum += $CoinClient->eth_getBalance($value);
                } elseif (config('coin')[$coinname]['type'] == 'token') {
                    $call = [
                        'to' => config('coin')[$coinname]['token_address'],
                        'data' => '0x70a08231' . $CoinClient->data_pj($value)
                    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), config('coin')[$coinname]['decimals']);
                }

            }
            $data['trance_mum'] = $sum;
        } elseif (config('coin')[$coinname]['type'] == 'eos') {
            $EosClient = EosClient($dj_address, $dj_port);
            $json = $EosClient->get_info();
            if (empty($json)) {
                $this->error('钱包对接失败!!');
            }
            $tradeInfo = [
                "account" => config('coin')[$coinname]['dj_yh'],
                "code" => config('coin')[$coinname]['token_address'],
                "symbol" => $coinname,
            ];
            $account_info = $EosClient->get_currency_balance($tradeInfo);
            $data['trance_mum'] = $account_info[0];
        } else {
            $data['trance_mum'] = 0;
        }

        $this->assign('data', $data);
        $market_json = Db::name('CoinJson')->where(['name' => input('param.coinname')])->order('id desc')->find();

        if ($market_json) {
            //$addtime = $market_json['addtime'] + 60;
            $addtime = $market_json['addtime'];
            if (time() > $addtime) {
                $addtime = $market_json['addtime'] + 60;
            }
        } else {
            $addtime = Db::name('Myzr')->where(['coinname' => input('param.coinname')])->order('id asc')->find()['addtime'];
        }

        if (!$addtime) {
            $addtime = time();
        }
        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
        if ($addtime) {
            $trade_num = Db::name('UserCoin')->sum($coinname);
            $trade_mum = Db::name('UserCoin')->sum($coinname . 'd');
            $aa = $trade_num + $trade_mum;
            $bb = $data['trance_mum'];

            $trade_fee_buy = Db::name('Myzr')->where(['coinname' => $coinname, 'addtime' => [['egt', $start], ['elt', $end]]])->sum('fee');
            $trade_fee_sell = Db::name('Myzc')->where(['coinname' => $coinname, 'addtime' => [['egt', $start], ['elt', $end]]])->sum('fee');
            $d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            // 如果找到添加时间等于end的时间
            if (Db::name('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
                Db::name('CoinJson')->where(['name' => input('param.coinname'), 'addtime' => $end])->update(['data' => json_encode($d)]);
            } else {
                Db::name('CoinJson')->insert(['name' => $coinname, 'data' => json_encode($d), 'addtime' => $end]);
            }
        }

        $tradeJson = Db::name('CoinJson')->where(array('name' => input('param.coinname')))->order('id asc')->limit(100)->select();
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

        if (Db::name('CoinJson')->where(array('name' => input('param.coinname')))->delete()) {
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

        $market = trim(input('param.market'));
        $xnb = explode('_', $market)[0];
        $rmb = isset(explode('_', $market)[1]) ? explode('_', $market)[1] : [];
        $this->assign('xnb', $xnb);
        $this->assign('rmb', $rmb);
        $this->assign('market', $market);
        $data = array();
        $data['trance_num'] = Db::name('TradeLog')->where(array('market' => $market))->sum('num');
        $data['trance_buyfee'] = Db::name('TradeLog')->where(array('market' => $market))->sum('fee_buy');
        $data['trance_sellfee'] = Db::name('TradeLog')->where(array('market' => $market))->sum('fee_sell');
        $data['trance_fee'] = $data['trance_buyfee'] + $data['trance_sellfee'];
        $data['trance_mum'] = Db::name('TradeLog')->where(array('market' => $market))->sum('mum');
        $data['trance_ci'] = Db::name('TradeLog')->where(array('market' => $market))->count();
        $market_json = Db::name('MarketJson')->where(array('name' => $market))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = Db::name('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
        }

        if (!$addtime) {
            $addtime = time();
        }

        if ($addtime) {
            if ($addtime < (time() + (60 * 60 * 24))) {
                $t = $addtime;
                $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
                $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
                $trade_num = Db::name('TradeLog')->where(array(
                    'market' => $market,
                    'addtime' => array(
                        array('egt', $start),
                        array('elt', $end)
                    )
                ))->sum('num');

                if ($trade_num) {
                    $trade_mum = Db::name('TradeLog')->where(array(
                        'market' => $market,
                        'addtime' => array(
                            array('egt', $start),
                            array('elt', $end)
                        )
                    ))->sum('mum');
                    $trade_fee_buy = Db::name('TradeLog')->where(array(
                        'market' => $market,
                        'addtime' => array(
                            array('egt', $start),
                            array('elt', $end)
                        )
                    ))->sum('fee_buy');
                    $trade_fee_sell = Db::name('TradeLog')->where(array(
                        'market' => $market,
                        'addtime' => array(
                            array('egt', $start),
                            array('elt', $end)
                        )
                    ))->sum('fee_sell');
                    $d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

                    if (Db::name('MarketJson')->where(array('name' => $market, 'addtime' => $end))->find()) {
                        Db::name('MarketJson')->where(array('name' => $market, 'addtime' => $end))->update(array('data' => json_encode($d)));
                    } else {
                        Db::name('MarketJson')->insert(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
                    }
                } else {
                    $d = null;

                    if (Db::name('MarketJson')->where(array('name' => $market, 'data' => ''))->find()) {
                        Db::name('MarketJson')->where(array('name' => $market, 'data' => ''))->update(array('addtime' => $end));
                    } else {
                        Db::name('MarketJson')->insert(array('name' => $market, 'data' => '', 'addtime' => $end));
                    }
                }
            }
        }

        $tradeJson = Db::name('MarketJson')->where(array('name' => $market))->order('id asc')->limit(100)->select();

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

        if (false !== Db::name('MarketJson')->where(array('name' => $market))->delete()) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }
}

?>