<?php
namespace app\admin\controller;
use app\common\ext\Eth;
use  app\common\ext\BtmClient;
use think\Controller;
use think\Cache;
use think\Db;
use think\Request;
use think\Exception;

class Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439 extends Controller
{
    //实例化缓存数据
    protected function _initialize()
    {
        $config = Cache::store('redis')->get('home_config');
        if (!$config) {
            $config = Db::name('Config')->where(['id' => 1])->find();
            Cache::store('redis')->set('home_config', $config);
        }

        config($config);

        $coin = Cache::store('redis')->get('home_coin');
        if (!$coin) {
            $coin = Db::name('Coin')->where(['status' => 1])->select();
            Cache::store('redis')->set('home_coin', $coin);
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
        $market = Cache::store('redis')->get('home_market');
        $market_type = [];
        $coin_on = [];
        if (!$market) {
            $market = Db::name('Market')->where(array('status' => 1))->select();
            Cache::store('redis')->set('home_market', $market);
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

    //检测异常，调整不正常的委单
    public function checkYichang()
    {

        $mo = Db::name('');
        $mo->startTrans();

        $Trade = Db::name('Trade')->where('deal > num')->lock(true)->order('id desc')->find();

        if (!$Trade){
            $mo->rollback();
        }

        try{
            if ($Trade['status'] == 0) {
                $mo->table('weike_trade')->where(['id' => $Trade['id']])->update(['deal' => Num($Trade['num']), 'status' => 1]);
            } else {
                $mo->table('weike_trade')->where(['id' => $Trade['id']])->update(['deal' => Num($Trade['num'])]);
            }
            $mo->commit();
        }catch (Exception $e){
            $mo->rollback();
        }
    }

    //检查大盘，调整不成交委单
    public function checkDapan()
    {
        $market = input('market/s', 'doge_hkd');

        $url = 'https://www.huocoin.vip/Trade/matchingTrade/market/'.$market;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_exec($ch);
        $this->success('检测成功！');
    }

    //设置市场和币种
    public function marketandcoinb8c3b3d94512472db8()
    {

        foreach (config('market') as $k => $v) {
            $this->autoMatchingTrade($v['name']);
            $this->autoTrade($v['name']);
            $this->setMarket($v['name']);
        }

        foreach (config('coin_list') as $k => $v) {
            $this->setcoin($v['name']);
        }
    }

    public function autoMatchingTrade($market){

        if (!$market){
            return false;
        }

        $url = 'https://'.$_SERVER['SERVER_NAME'].'/Trade/matchingTrade/market/'.$market;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_exec($ch);
    }

    //自动刷单功能
    public function autoTrade($market)
    {

        $time_i = time();
        if(date('i',$time_i) % 2 !== 0) {
            return ;
        }else{
            $a_time = Db::name('trade_log')->where(['userid' => 5308, 'market'=> $market])->order('id desc')->limit(1)->find();
            if (date('i',$a_time['addtime']) == date('i',$time_i)){
                return ;
            }
        }

        $data = Cache::store('redis')->get('autoData'.$market);
        $market_config = config('market');
        $buy = Db::name('Trade')->where(['market' => $market, 'type' => 1,'userid' => ['gt',0], 'status' => 0])->order('price desc,id asc')->find();
        $sell = Db::name('Trade')->where(['market' => $market, 'type' => 2,'userid' => ['gt',0],'status' => 0])->order('price asc,id asc')->find();
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
                $num = round(randomFloat($auto['min'], $auto['max']), 6);
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

            Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->update(['buy_price' => $buy_price, 'buy_num' => $buy_num ,'sell_price' => $sell_price, 'sell_num' => $sell_num , 'time' => time()]);

            $tradeLog = Db::name('TradeLog')->where(['status' => 1, 'market' => $market])->order('id desc')->find();
            //成交最大价
            $max_price = $market_config[$market]['max_price'];
            //成交最小价
            $min_price = $market_config[$market]['min_price'];

            $new_plus = randomFloat($buy['price'], $sell['price']);
            //修改无限币价格小数点

            if ($xnb == 'ifc' || $xnb == 'eac' || $xnb == 'oioc' || $xnb == 'bcx'){
                $new_plus= round($new_plus,5);
            }else if($xnb == 'doge'){
                $new_plus= round($new_plus,4);
            }else if($xnb == 'btc'){
                $new_plus= round($new_plus,2);
            }else{
                $new_plus= round($new_plus,3);
            }
            //手续费
            $fee1 = $market_config[$market]['fee_buy'] * $num * $new_plus ;//买入手续费
            $fee2 = $market_config[$market]['fee_sell'] * $num * $new_plus ;//卖出手续费

            //交易金额
            $totle = $new_plus * $num ;
            $mum = $auto['price'] * $num ;
            $all_market = $data = Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->select();
            $suc_trade = Db::name('AutoTrade')->where(['market' => $market , 'status' => 1])->update(['price' => $new_plus , 'max_price' =>$max_price , 'num' => $num ,'type' =>$type , 'min_price' =>$min_price]);
            Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->setInc('volume',$num);
            Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->setInc('deal_toble',$mum);

            if ($suc_trade) {
                $new_time = time();
                Db::name('TradeLog')->insert(['userid' => 5308, 'peerid' =>5309, 'market' => $market , 'price' => $new_plus , 'num' => $num , 'mum' => $totle , 'fee_buy' => $fee1 , 'fee_sell' => $fee2 , 'type' => $type , 'addtime' => $new_time , 'status' => 1]);

            }
            $mum = $auto['price'] * $num ;

            //24成交量  和  成交额  归零处理
            if (date('H') == 0 && date('i') == 0){
                $volume = Db::name('AutoTrade')->where(['market' => $market , 'status' => 1])->update(['volume' => 0]);
                $deal_toble = Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->update(['deal_toble' => 0]);
            }

            //涨跌幅
            $hou_price = $market_config[$market]['hou_price'];
            $a_price = Db::name('TradeLog')->where(['market' => $market])->order('id desc')->value('price');
            $change = round(( ($a_price - $hou_price)/$hou_price ) *100,2 );
            $new_change = Db::name('AutoTrade')->where(['market' => $market ,'status' => 1])->update(['change' => $change]);

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
            Cache::store('redis')->set('autoTrade' . $market, $data);

            //清理首页缓存
            $jiaoyiqu = config('market')[$market]['jiaoyiqu'];
            Cache::store('redis')->set('weike_allcoin'.$jiaoyiqu,null);
            Cache::store('redis')->set('getChartJson'.$market , null);
            Cache::store('redis')->set('getTradelog' . $market, null);
            Cache::store('redis')->set('getJsonTop' . $market, null);
        }
    }

    //设置市场
    public function setMarket($market = NULL)
    {
        if (!$market) {
            return null;
        }

        $market_json = Db::name('Market_json')->where(['name' => $market])->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = Db::name('TradeLog')->where(['market' => $market])->order('addtime asc')->find()['addtime'];
        }



        $t = $addtime;
        $start = date('Y-m-d',$t).' 00:00:00';
        $start = strtotime($start);
        $end = date('Y-m-d',$t).' 23:59:59';
        $end = strtotime($end);
        $trade_num = Db::name('TradeLog')->where([
            'market'  => $market,
            'addtime' => [
                ['egt', $start],
                ['elt', $end]
            ]
        ])->sum('num');

        if ($trade_num) {
            $trade_mum = Db::name('TradeLog')->where([
                'market'  => $market,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('mum');
            $trade_fee_buy = Db::name('TradeLog')->where([
                'market'  => $market,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee_buy');
            $trade_fee_sell = Db::name('TradeLog')->where([
                'market'  => $market,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee_sell');
            $d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);
            if (Db::name('Market_json')->where(['name' => $market, 'addtime' => $end])->find()) {
                Db::name('Market_json')->where(['name' => $market, 'addtime' => $end])->update(['data' => json_encode($d)]);
            } else {
                Db::name('Market_json')->insert(['name' => $market, 'data' => json_encode($d), 'addtime' => $end]);
            }
        } else {
            $d = null;

            if (Db::name('Market_json')->where(['name' => $market, 'data' => ''])->find()) {
                Db::name('Market_json')->where(['name' => $market, 'data' => ''])->update(['addtime' => $end]);
            } else {
                Db::name('Market_json')->insert(['name' => $market, 'data' => '', 'addtime' => $end]);
            }
        }
    }

    //设置市场
    public function setcoin($coinname = NULL)
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
                $this->error('钱包连接失败！');
            }

            $data['trance_mum'] = $json['balance'];
        } elseif(config('coin')[$coinname]['type'] == 'eth' || config('coin')[$coinname]['type'] == 'token'){
            $CoinClient = EthClient($dj_address,$dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包连接失败');
            }

            $accounts = $CoinClient->eth_accounts();
            $sum = 0;
            foreach ($accounts as $key => $value) {
                if(config('coin')[$coinname]['type'] == 'eth'){
                    $sum += $CoinClient->eth_getBalance($value);
                } elseif ( config('coin')[$coinname]['type'] == 'token' ){
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

        $market_json = Db::name('CoinJson')->where(array('name' => $coinname))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = Db::name('Myzr')->where(array('name' => $coinname))->order('id asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = date('Y-m-d',$t).' 00:00:00';
        $start = strtotime($start);
        $end = date('Y-m-d',$t).' 23:59:59';
        $end = strtotime($end);

        if ($addtime) {
            if ((time() + (60 * 60 * 24)) < $addtime) {
                return null;
            }

            $aa = 0;
            $bb = $data['trance_mum'];

            $trade_fee_buy = Db::name('Myzr')->where([
                'coinname'    => $coinname,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee');
            $trade_fee_sell = Db::name('Myzc')->where([
                'coinname'    => $coinname,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee');
            $d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            if (Db::name('CoinJson')->where(['name' => $coinname, 'addtime' => $end])->find()) {
                Db::name('CoinJson')->where(['name' => $coinname, 'addtime' => $end])->update(['data' => json_encode($d)]);
            } else {
                Db::name('CoinJson')->insert(['name' => $coinname, 'data' => json_encode($d), 'addtime' => $end]);
            }
        }
    }

    //设置最后的价格
    public function houpriceb8c3b3d94512472db8()
    {
        foreach (config('market') as $k => $v) {

            if (!$v['hou_price'] || (date('H', time()) == '00')) {
                $t = time();
                $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
                if ($v['name'] == 'drt_cny'){
                    $twoStart = mktime(14, 0, 0, date('m', $t), date('d', $t)-1, date('Y', $t));
                    $hou_price = Db::name('TradeLog')->where([
                        'market'  => $v['name'],
                        'addtime' => ['lt', $twoStart]
                    ])->order('id desc')->limit(1)->value('price');
                }else{
                    $hou_price = Db::name('TradeLog')->where([
                        'market'  => $v['name'],
                        'addtime' => ['lt', $start]
                    ])->order('id desc')->limit(1)->value('price');
                }

                if (!$hou_price) {
                    $hou_price = $v['weike_faxingjia'];
                    Db::name('Market')->where(['name' => $v['name']])->setField('hou_price', $hou_price);
                    Cache::store('redis')->set('home_market', null);
                }elseif($hou_price != $v['hou_price']){
                    Db::name('Market')->where(['name' => $v['name']])->setField('hou_price', $hou_price);
                    Cache::store('redis')->set('home_market', null);
                }
            }
        }
    }

    //比特币系列的轮询
    public function qianbaob8c3b3d94512472db7()
    {
        $coin = input('get.coin', 'btc', 'string');
        if (!$coin) {
            exit('no coin name');
        }

        $coinconf = Db::name('Coin')->where(['status' => 1, 'name' => $coin])->find();

        $coin = $coinconf['name'];
        if (!$coin) {
            exit('MM');
        }

        if ($coinconf['type'] == 'bit') {
            $dj_username = $coinconf['dj_yh'];
            $dj_password = $coinconf['dj_mm'];
            $dj_address = $coinconf['dj_zj'];
            $dj_port = $coinconf['dj_dk'];
            echo 'start ' . $coin . "\n";
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                exit('###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . "\n");
            }

            echo 'Cmplx ' . $coin . ' start,connect ' . (empty($CoinClient) ? 'fail' : 'ok') . ' :' . "\n";
            $listtransactions = $CoinClient->listtransactions('*', 100, 0);
            echo 'listtransactions:' . count($listtransactions) . "\n";
            krsort($listtransactions);
            foreach ($listtransactions as $trans) {

                //账号不存在不处理
                if (!$trans['account']) {
                    echo 'empty account continue' . "\n";
                    continue;
                }

                //账号不在数据库中不处理
                if (!($user = Db::name('User')->where(['username' => $trans['account']])->find())) {
                    echo 'no account find continue' . "\n";
                    continue;
                }

                //如果订单写入了数据库不处理
                if (Db::name('Myzr')->where(['txid' => $trans['txid'], 'status' => '1'])->find()) {
                    echo 'txid had found continue' . "\n";
                    continue;
                }

                echo 'all check ok ' . "\n";
                if ($trans['category'] == 'receive') {
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

                        $res = Db::name('myzr')->where(['txid' => $trans['txid']])->find();
                        if (!$res) {
                            Db::name('myzr')->insert(['userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => -1]);
                        }

                        continue;
                    } else {
                        echo 'confirmations full' . "\n";
                    }

                    $mo = Db::name('');
                    $mo->startTrans();
                    try{
                        $mo->table('weike_user_coin')->where(array('userid' => $user['id']))->setInc($coin, $trans['amount']);
                        $res = $mo->table('weike_myzr')->lock(true)->where(['txid' => $trans['txid']])->find();
                        if ($res && $trans['blockhash']) {
                            echo 'weike_myzr find and set status 1';
                            $mo->table('weike_myzr')->update(['id' => $res['id'], 'addtime' => time(), 'status' => 1]);
                        } else {
                            echo 'weike_myzr not find and add a new weike_myzr' . "\n";
                            $mo->table('weike_myzr')->insert(['userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => 1]);
                        }

                        $flag = true;
                        $mo->commit();
                    }catch (Exception $e){
                        $flag = false;
                        $mo->rollback();
                    }


                    if ($flag) {
                        echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                        echo 'commit ok' . "\n";
                    } else {
                        echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                        echo 'rollback ok' . "\n";
                    }
                }

                if ($trans['category'] == 'send') {
                    echo 'start send do:' . "\n";

                    if (3 <= $trans['confirmations']) {
                        $myzc = Db::name('Myzc')->where(['txid' => $trans['txid']])->find();

                        if ($myzc) {
                            if ($myzc['status'] == 0) {
                                Db::name('Myzc')->where(['txid' => $trans['txid']])->update(['status' => 1]);
                                echo $trans['amount'] . '成功转出' . $coin . ' 币确定';
                            }
                        }
                    }
                }
            }
        }
    }

    //ETH 系列的轮询
    public function qianbaob8c3b3d94512472db8()
    {
        $coin = I('get.coin', 'eth', 'string');
        if (!$coin) {
            exit('no coin name');
        }
        $coinconf = Db::name('Coin')->where(['status' => 1, 'name' => $coin])->find();
        $coin = $coinconf['name'];
        $coinAddress = $coin.'b';
        if (!$coin) {
            exit('MM');
        }
        if ($coinconf['type'] == 'eth' ) {
            $dj_address = $coinconf['dj_zj'];
            $dj_port = $coinconf['dj_dk'];
            $CoinClient = EthClient($dj_address, $dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包连接失败');
            }

            //开始轮询
            $listtransactions = $CoinClient->listLocal($coin, $json);
            echo 'listtransactions:' . count($listtransactions) . "\n";
            if (empty($listtransactions)) {
                exit('高度太高，无法轮询。');
            }
            foreach ($listtransactions as $trans) {
                if (!$trans->to) {
                    echo 'empty to continue' . "<br>";
                    continue;
                }
                //转入

                $user = Db::name('UserCoin')->where([$coinAddress => $trans->to])->find();
                if ($user) {
                    if (Db::name('Myzr')->where(['txid' => $trans->hash, 'status' => '1'])->value('id')) {
                        echo 'txid had found continue' . "<br>";
                        continue;
                    }

                    echo 'start receive do:' . "<br>";
                    $sfee = 0;
                    $true_amount = $CoinClient->real_banlance($CoinClient->decode_hex($trans->value));
                    $final_amount = $true_amount - 0.001;

                    if ($final_amount > 0.002) {

                        $mo = Db::name('');
                        $mo->startTrans();
                        $zr_id = $mo->table('weike_myzr')->lock(true)->where(['txid' => $trans->hash])->value('id');
                        //事务中锁表，避免写入
                        if ($zr_id) {
                            $mo->rollback();
                            continue;
                        }

                        try{
                            $mo->table('weike_user_coin')->where(['userid' => $user['userid']])->setInc($coin, $final_amount);
                            $mo->table('weike_myzr')->insert([
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
                            $flag = true;
                            $mo->commit();
                        }catch (Exception $e){
                            $flag = true;
                            $mo->rollback();
                        }


                        if ($flag) {
                            echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;
                            echo 'commit ok' . "\n";
                        } else {
                            echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                            echo 'rollback ok' . "\n";
                        }
                    }
                }
                //转出
                if (!$trans->from) {
                    echo 'empty to continue' . "<br>";
                    continue;
                }
                if ($user = Db::name('UserCoin')->where([$coinAddress => $trans->from])->find()) {
                    echo 'start send do:' . "\n";
                    $myzc = Db::name('Myzc')->where(['txid' => $trans->hash])->find();
                    if ($myzc) {
                        if ($myzc['status'] == 0) {
                            Db::name('Myzc')->where(['txid' => $trans->hash])->update(['status' => 1]);
                            echo $true_amount . '成功转出' . $coin . ' 币确定';
                        }
                    }
                }
            }
        }elseif( $coinconf['type'] == 'token'){
            $dj_address = $coinconf['dj_zj'];
            $dj_port = $coinconf['dj_dk'];
            $CoinClient = EthClient($dj_address, $dj_port);
            $json = $CoinClient->eth_blockNumber(true);
            if (empty($json) || $json <= 0) {
                $this->error('钱包连接失败');
            }

            //开始轮询
            $listtransactions = $CoinClient->listLocal($coin, $json);
            echo 'listtransactions:' . count($listtransactions) . "\n";
            if (empty($listtransactions)) {
                exit('高度太高，无法轮询。');
            }

            foreach ($listtransactions as $trans) {
                if (!$trans->input) {
                    echo 'empty to continue' . "<br>";
                    continue;
                }
                $to = $trans->input;
                //判断发送的是否是代币    代币的input位数为18位
                if (strlen($to) == 138) {
                    $value = substr($trans->input, 74, 64);
                    $to = "0x" . substr($to, 34, 40);
                    //判断转入地址是否是平台地址
                    $user = Db::name('UserCoin')->where([$coinAddress => $to])->find();
                    if ($user) {
                        //根据合约地址判断  币种类型
                        if ($trans->to == $coinconf['token_address']) {
                            if (Db::name('Myzr')->where(['txid' => $trans->hash, 'status' => '1'])->value('id')) {
                                echo 'txid had found continue' . "<br>";
                                continue;
                            }
                            //按事务散列返回事务的接收。
                            $sts = $CoinClient->eth_getTransactionReceipt($trans->hash);
                            $sts_s = object_array($sts);
                            $sts = substr($sts_s['status'],2,1);
                            //判断区块上面的转入状态,status = 0 失败,  logs为空 失败
                            if($sts != 1 || !$sts_s['logs']){
                                echo '转入失败' . "<br>";
                                continue;
                            }
                            echo 'start receive do:' . "<br>";

                            $sfee = 0;
                            $true_amount = $CoinClient->real_banlance_token($CoinClient->decode_hex($value) , $coinconf['decimals']);
                            $final_amount = $true_amount - 0.001;
                            $yue = $CoinClient->eth_getBalance($to);
                            if( $yue < floatval('0.002')) {
                                //eth转账到token的eth手续费
                                $tradeInfo = [[
                                    'from' => $coinconf['dj_yh'],
                                    'to' => $to,
                                    'gas' => '0x76c0',
                                    'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval(0.002 - $yue))),
                                    'gasPrice' => $CoinClient->eth_gasPrice()
                                ]];
                                $sendrs = $CoinClient->eth_sendTransaction($coinconf['dj_yh'], $coinconf['dj_mm'], $tradeInfo);
                            }

                            $mo = Db::name('');
                            $mo->startTrans();

                            //事务中锁表，避免写入
                            $zr_id = $mo->table('weike_myzr')->lock(true)->where(['txid' => $trans->hash])->value('id');
                            if ($zr_id) {
                                $mo->rollback();
                                continue;
                            }

                            try{
                                $mo->table('weike_user_coin')->where(['userid' => $user['userid']])->setInc($coin, $final_amount);
                                $mo->table('weike_myzr')->insert([
                                    'userid' => $user['userid'],
                                    'username' => $to,
                                    'coinname' => $coin,
                                    'fee' => $sfee,
                                    'txid' => $trans->hash,
                                    'num' => $true_amount,
                                    'mum' => $final_amount,
                                    'addtime' => time(),
                                    'status' => 1
                                ]);

                                $flag = true;
                                $mo->commit();
                            }catch (Exception $e){
                                $flag = false;
                                $mo->rollback();
                            }


                            if ($flag) {
                                echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;
                                echo 'commit ok' . "\n";
                            } else {
                                echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                                echo 'rollback ok' . "\n";
                            }
                        }
                    }
                }
                //转出
                if (!$trans->from) {
                    echo 'empty to continue' . "<br>";
                    continue;
                }
                if ($user = Db::name('UserCoin')->where([$coinAddress => $trans->from])->find()) {
                    echo 'start send do:' . "\n";
                    $myzc = Db::name('Myzc')->where(['txid' => $trans->hash])->find();
                    if ($myzc) {
                        if ($myzc['status'] == 0) {
                            Db::name('Myzc')->where(['txid' => $trans->hash])->update(['status' => 1]);
                            echo $true_amount . '成功转出' . $coin . ' 币确定';
                        }
                    }
                }
            }
        }
    }

    //btm轮行
    public function qianbaob8c3b3d94512472db9()
    {
        $btmzData = Db::name('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
        if ($btmzData){
            $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
            $btmClient->income();
        }else{
            return false;
        }
    }

    //ETH 子主地址同步
    public function qianbaosync()
    {
        $coin = I('get.coin', 'eth', 'string');
        $coinconf = Db::name('coin')->where(['name' => $coin, 'status' => 1])->find();
        if($coinconf['type'] != 'eth' && $coinconf['type'] != 'token'){
            $this->error('不存在的币种类型。');
        }
        $dj_username = $coinconf['dj_yh'];
        $dj_address = $coinconf['dj_zj'];
        $dj_port = $coinconf['dj_dk'];
        $CoinClient = EthClient($dj_address,$dj_port);
        $json = $CoinClient->eth_blockNumber(true);

        if (empty($json) || $json <= 0) {
            $this->error('钱包连接失败');
        }

        //筛选数据库中钱包中大于 0.01 的用户 分组
        $offset = 1000;
        $coinb = $coin.'b';
        $coinp = $coin.'p';
        for ($i = 0; $i < 24; $i++) {
            if (date('H', time()) == $i && $coinconf['type'] == 'eth') {
                $accounts = Db::name('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->limit($i * $offset, 1000)->select();
            }elseif(date('H', time()) == 1 && $coinconf['type'] == 'token'){
                $accounts = Db::name('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->limit(($i-1) * $offset, 1000)->select();
            }
        }

        //筛选钱包中钱包中大于 0.01 的用户
        $fee = 0.002;
        if(count($accounts) > 0) {
            foreach ($accounts as $k => $v) {
                if($coinconf['type'] == 'eth'){
                    $num = $CoinClient->eth_getBalance($v[$coinb]);
                }elseif($coinconf['type'] == 'token'){
                    $call = [
                        'to' => $coinconf['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($v[$coinb])
                    ];
                    $num = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , $coinconf['decimals']);
                }

                if ($num > 0.01) {
                    //转币小脚本 进行同步
                    do {
                        if($coinconf['type'] == 'eth'){
                            $num = $num - $fee;
                            $tradeInfo = [[
                                'from' => $v[$coin . 'b'],
                                'to' => $dj_username,
                                'gas' => '0x76c0',
                                'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($num))),
                                'gasPrice' => $CoinClient->eth_gasPrice()
                            ]];
                            $sendrs = $CoinClient->eth_sendTransaction($v[$coinb], $v[$coinp], $tradeInfo);
                        }elseif($coinconf['type'] == 'token'){
                            $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($num) , $coinconf['decimals']));
                            $tradeInfo = [[
                                'from' => $v[$coin . 'b'],
                                'to' => $coinconf['token_address'],
                                'data' =>  '0xa9059cbb'. $CoinClient->data_pj($dj_username, $value),
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
    }

    //三日趋势图
    public function tendencyb8c3b3d94512472db8()
    {
        foreach (config('market') as $k => $v) {
            echo '----计算趋势----' . $v['name'] . '------------';
            $tendency_time = 4;
            $t = time();
            $tendency_str = $t - (24 * 60 * 60 * 3);
            $x = 0;

            for (; $x <= 18; $x++) {
                $na = $tendency_str + (60 * 60 * $tendency_time * $x);
                $nb = $tendency_str + (60 * 60 * $tendency_time * ($x + 1));
                $b = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market = \'%s\'', $na, $nb, $v['name'])->max('price');

                if (!$b) {
                    $houprice = Db::name('market')->field('hou_price')->where(['name'=>$v['name']])->value('hou_price');
                    $b = $houprice;
                }

                $rs[] = array($na, $b);
            }

            Db::name('Market')->where(array('name' => $v['name']))->setField('tendency', json_encode($rs));
            unset($rs);
            echo '计算成功!';
            echo "\n";
        }

        echo '趋势计算0k ' . "\n";
    }

    //计算行情
    public function chartb8c3b3d94512472db8()
    {

        if (date('i')%5 == 0){
            foreach (config('market') as $k => $v) {
                $this->setTradeJson($v['name']);
            }
        }
        echo '计算行情0k ' . "\n";
    }

    //计算行情
    public function setTradeJson($market)
    {
        $timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

        foreach ($timearr as $k => $v) {
            //,'addtime'=>['gt',time()-$v]
            $tradeJson = Db::name('TradeJson')->where(['market' => $market, 'type' => $v])->order('id desc')->find();
            file_put_contents('/tmp/queue.md', date('Y-m-d H:i:s',time()).Db::name('TradeJson')->getLastSql()."\n", FILE_APPEND | LOCK_EX);
            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
            } else {
                $addtime = Db::name('TradeLog')->where(['market' => $market])->order('id asc')->value('addtime');
            }

            if ($addtime) {
                $youtradelog = Db::name('TradeLog')->where('addtime >= %d and market =\'%s\'', $addtime, $market)->sum('num');
            }

            if ($youtradelog) {
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


                    $sum = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->sum('num');

                    if ($sum) {
                        $sta = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id asc')->value('price');
                        $max = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->max('price');
                        $min = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->min('price');
                        $end = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id desc')->value('price');
                        $d = array($na, $sum, $sta, $max, $min, $end);

                        if (Db::name('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->find()) {
                            Db::name('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->update(['data' => json_encode($d)]);
                        } else {
                            Db::name('TradeJson')->insert(['market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v]);
                            /* Db::name('TradeJson')->execute('commit');
                            Db::name('TradeJson')->where(array('market' => $market, 'data' => '', 'type' => $v))->delete();
                            Db::name('TradeJson')->execute('commit');*/
                        }
                    } /*else {
                        Db::name('TradeJson')->insert(array('market' => $market, 'data' => '', 'addtime' => $na, 'type' => $v));
                        Db::name('TradeJson')->execute('commit');
                    }*/
                }
            }
        }

        return '计算成功!';
    }

    public function setTradeOusJson($market,$timearr)
    {


        if (!$market){
            exit('请选择市场');
        }

        if (!$timearr){
            exit('请选择k线时间1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080');
        }
        $tradeJson = Db::name('TradeJson')->where(['market' => $market, 'type' => $timearr])->order('id desc')->find();
        if ($tradeJson) {
            $addtime = $tradeJson['addtime'];
        } else {
            $addtime = Db::name('TradeLog')->where(['market' => $market])->order('id asc')->value('addtime');
        }

        if ($addtime) {
            $youtradelog = Db::name('TradeLog')->where('addtime >= %d and market =\'%s\'', $addtime, $market)->sum('num');
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

                $sum = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->sum('num');

                if ($sum) {
                    $sta = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id asc')->value('price');
                    $max = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->max('price');
                    $min = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->min('price');
                    $end = Db::name('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id desc')->value('price');
                    $d = array($na, $sum, $sta, $max, $min, $end);

                    if (Db::name('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $timearr])->find()) {
                        Db::name('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $timearr])->update(['data' => json_encode($d)]);
                    } else {
                        Db::name('TradeJson')->insert(['market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $timearr]);
                    }
                } else {
                    Db::name('TradeJson')->insert(['market' => $market, 'data' => '', 'addtime' => $na, 'type' => $timearr]);
                }
            }
        }

        return '计算成功!';
    }

    //自动交易，只增加委单
    public function upTrade_weike_8a201aa602cd9448()
    {
        $market = I('market/s', NULL);
        $type = rand(1, 2);

        if (!$market) {
            $market = config('market_mr');
        }

        if (!config('market')[$market]) {
            echo '交易市场错误';
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        $url = 'http://www.jubi.com/api/v1/ticker/?coin='.$xnb;
        $content = file_get_contents($url);
        $content = json_decode($content, true);

        $min_price = floatval($content['buy'])*1000;
        $max_price = floatval($content['sell'])*1000;



        if($max_price<$min_price){
            $temps = $min_price;
            $min_price = $max_price;
            $max_price = $temps;
        }


        $price = round(rand($min_price, $max_price)/1000, 4);

        if($xnb == "btc"){
            $max_num = round(10.9999 * 10000, 4);
            $min_num = round(0.9999 * 10000, 4);
        }else{
            $max_num = round(99.9999 * 10000, 4);
            $min_num = round(1.9999 * 10000, 4);
        }

        $num = round(rand($min_num, $max_num) / 10000,4);

        if (!$price) {
            echo '交易价格格式错误';
        }

        if (!check($num, 'double')) {
            echo '交易数量格式错误';
        }

        if (($type != 1) && ($type != 2)) {
            echo '交易类型格式错误';
        }

        // TODO: SEPARATE
        $price = round(floatval($price), 4);

        if (!$price) {
            echo '交易价格错误';
        }

        $num = round(trim($num), 4);

        if (!check($num, 'double')) {
            echo '交易数量错误';
        }

        $mum = round($num * $price, 4);

        if (!$rmb) {
            echo '数据错误1';
        }

        if (!$xnb) {
            echo '数据错误2';
        }

        if (!$market) {
            echo '数据错误3';
        }

        if (!$price) {
            echo '数据错误4';
        }

        if (!$num) {
            echo '数据错误5';
        }

        if (!$mum) {
            echo '数据错误6';
        }

        if (!$type) {
            echo '数据错误7';
        }

        $mo = Db::name('');
        $mo->startTrans();

        try{
            if ($type == 1) {
                $mo->table('weike_trade')->insert(['userid' => 0, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => 0, 'type' => 1, 'addtime' => time(), 'status' => 0]);
            } else if ($type == 2) {
                $mo->table('weike_trade')->insert(['userid' => 0, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => 0, 'type' => 2, 'addtime' => time(), 'status' => 0]);
            }
            $flag = true;
            $mo->commit();
        }catch (Exception $e){
            $flag = false;
            $mo->rollback();
        }


        if ($flag) {
            Cache::store('redis')->set('getDepth', null);
            $this->matchingAutoTrade($market);
            echo '交易成功！';
        } else {
            echo  '交易失败！';
        }
    }

    //撮合自动交易，只增加委单
    public function matchingAutoTrade()
    {
        $market = I('market/s', NULL);

        if (!$market) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        $mo = Db::name();
        $new_trade_weike = 0;

        for (; true; ) {
            $buy = $mo->table('weike_trade')->where(array('market' => $market, 'type' => 1, 'userid'=>0,'status' => 0))->order('price desc,id asc')->find();
            $sell = $mo->table('weike_trade')->where(array('market' => $market, 'type' => 2, 'userid'=>0,'status' => 0))->order('price asc,id asc')->find();

            if ($sell['id'] < $buy['id']) {
                $type = 1;
            } else {
                $type = 2;
            }

            if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
                $rs = array();

                if ($buy['num'] <= $buy['deal']) {
                }

                if ($sell['num'] <= $sell['deal']) {
                }

                $amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']));
                $amount = round($amount, 8 - config('market')[$market]['round']);

                if ($amount <= 0) {
                    $log = '错误1交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . "\n";
                    $log .= 'ERR: 成交数量出错，数量是' . $amount;
                    Db::name('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    Db::name('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    break;
                }

                if ($type == 1) {
                    $price = $sell['price'];
                } else if ($type == 2) {
                    $price = $buy['price'];
                } else {
                    break;
                }


                if (!$price) {
                    $log = '错误2交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                    $log .= 'ERR: 成交价格出错，价格是' . $price;
                    break;
                } else {
                    // TODO: SEPARATE
                    $price = round($price, 4);
                }

                $mum = round($price * $amount, 4);
                if (!$mum) {
                    $log = '错误3交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                    $log .= 'ERR: 成交价格'.$price.'成交总额出错，总额是' . $mum;
                    mlog($log);
                    break;
                } else {
                    $mum = round($mum, 4);
                }

                if ($fee_buy) {
                    $buy_fee = round(($mum / 100) * $fee_buy, 4);
                    $buy_save = round(($mum / 100) * (100 + $fee_buy), 4);
                } else {
                    $buy_fee = 0;
                    $buy_save = $mum;
                }

                if (!$buy_save) {
                    $log = '错误4交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新数量出错，更新数量是' . $buy_save;
                    mlog($log);
                    break;
                }

                if ($fee_sell) {
                    $sell_fee = round(($mum / 100) * $fee_sell, 8);
                    $sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
                } else {
                    $sell_fee = 0;
                    $sell_save = $mum;
                }

                if (!$sell_save) {
                    $log = '错误5交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家更新数量出错，更新数量是' . $sell_save;
                    mlog($log);
                    break;
                }


                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_trade write ,weike_trade_log write ');

                $rs[] = $mo->table('weike_trade')->where(array('id' => $buy['id']))->setInc('deal', $amount);
                $rs[] = $mo->table('weike_trade')->where(array('id' => $sell['id']))->setInc('deal', $amount);

                $rs[] = $mo->table('weike_trade_log')->insert(array('userid' => 0, 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => 0, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1));

                $buy_list = $mo->table('weike_trade')->where(array('id' => $buy['id'], 'status' => 0))->find();

                if ($buy_list) {
                    if ($buy_list['num'] <= $buy_list['deal']) {
                        $rs[] = $mo->table('weike_trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    }
                }

                $sell_list = $mo->table('weike_trade')->where(array('id' => $sell['id'], 'status' => 0))->find();

                if ($sell_list) {
                    if ($sell_list['num'] <= $sell_list['deal']) {
                        $rs[] = $mo->table('weike_trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    }
                }


                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $new_trade_weike = 1;
                    $coin = $xnb;

                    $jiaoyiqu = config('market')[$market]['jiaoyiqu'];

                    Cache::store('redis')->set('weike_allcoin'.$jiaoyiqu,null);
                    Cache::store('redis')->set('marketjiaoyie24',null);


                    Cache::store('redis')->set('allsum', null);
                    Cache::store('redis')->set('getJsonTop' . $market, null);
                    Cache::store('redis')->set('getTradelog' . $market, null);
                    Cache::store('redis')->set('getDepth' . $market . '1', null);
                    Cache::store('redis')->set('getDepth' . $market . '3', null);
                    Cache::store('redis')->set('getDepth' . $market . '4', null);
                    Cache::store('redis')->set('ChartgetJsonData' . $market, null);
                    Cache::store('redis')->set('allcoin', null);
                    Cache::store('redis')->set('trends', null);
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                }
            } else {
                break;
            }

            unset($rs);
        }

        mlog("duqunew_trade_".$new_trade_weike);

        if ($new_trade_weike) {

            mlog("wojinlail".$new_trade_weike);


            $new_price = round(Db::name('TradeLog')->where(array('market' => $market, 'status' => 1))->order('id desc')->value('price'), 6);
            $buy_price = round(Db::name('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'), 6);
            $sell_price = round(Db::name('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'), 6);
            $min_price = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->min('price'), 6);
            $max_price = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->max('price'), 6);
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            //time() - (60 * 60 * 24)
            $volume = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt',$beginToday)
            ))->sum('num'), 6);
            $sta_price = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'status'  => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->order('id asc')->value('price'), 6);
            $Cmarket = Db::name('Market')->where(array('name' => $market))->find();

            if ($Cmarket['new_price'] != $new_price) {
                $upCoinData['new_price'] = $new_price;
            }

            if ($Cmarket['buy_price'] != $buy_price) {
                $upCoinData['buy_price'] = $buy_price;
            }

            if ($Cmarket['sell_price'] != $sell_price) {
                $upCoinData['sell_price'] = $sell_price;
            }

            if ($Cmarket['min_price'] != $min_price) {
                $upCoinData['min_price'] = $min_price;
            }

            if ($Cmarket['max_price'] != $max_price) {
                $upCoinData['max_price'] = $max_price;
            }

            if ($Cmarket['volume'] != $volume) {
                $upCoinData['volume'] = $volume;
            }

            $change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
            $upCoinData['change'] = $change;

            if ($upCoinData) {
                Db::name('Market')->where(array('name' => $market))->update($upCoinData);
                Db::name('Market')->execute('commit');
                Cache::store('redis')->set('home_market', null);
            }
        }
    }

    //设置队列
    public function queue_3a32849e0c77173c325c72a3c2d7aa49()
    {
        $time = time();
        if (Cache::store('redis')->get('queue_chk_'.$this->request->controller().'_'.$this->request->action())){
            exit('timeout');
        }else{
            Cache::store('redis')->set('queue_chk_'.$this->request->controller().'_'.$this->request->action(),$time,60);
        }
        $file_path = ROOT_PATH  . '/database/check_queue.json';
        $timeArr = [];

        if (file_exists($file_path)) {
            $timeArr = file_get_contents($file_path);
            $timeArr = json_decode($timeArr, true);
        }

        array_unshift($timeArr, $time);
        $timeArr = array_slice($timeArr, 0, 3);

        if (file_put_contents($file_path, json_encode($timeArr))) {
            exit('exec ok[' . $time . ']' . "\n");
        } else {
            exit('exec fail[' . $time . ']' . "\n");
        }
    }

    //设置自动交易的参考最低价和最高价
    public function set_api_hign_or_low_price()
    {
        $mo = Db::name();
        $market_data = Cache::store('redis')->get('set_api_hign_or_low_price');
        if (!$market_data) {
            $market_data = $mo->table('weike_market_control')->select();
            Cache::store('redis')->set('set_api_hign_or_low_price', $market_data);
        }

        $data = mCurl('http://data.gate.io/api2/1/tickers');
        foreach ($market_data as $k => $v){
            $xnb = explode('_', $v['name'])[0];
            $rmb = explode('_', $v['name'])[1];
            if($rmb === 'cny') {
                if($data[$xnb . '_usdt']['result'] !== 'true'){
                    continue;
                }
                $mo->table('weike_market_control')->where(['id' => $v['id']])->setField(['api_min_price' => $data[$xnb . '_usdt']['low24hr']]);
                $mo->table('weike_market_control')->where(['id' => $v['id']])->setField(['api_max_price' => $data[$xnb . '_usdt']['high24hr']]);
            } elseif ($rmb === 'btc') {
                if($data[$xnb . '_btc']['result'] !== 'true'){
                    continue;
                }
                $mo->table('weike_market_control')->where(['id' => $v['id']])->setField(['api_min_price' => $data[$xnb . '_btc']['low24hr']]);
                $mo->table('weike_market_control')->where(['id' => $v['id']])->setField(['api_max_price' => $data[$xnb . '_btc']['high24hr']]);
            }
        }
    }
}

?>