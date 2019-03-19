<?php
namespace Admin\Controller;
use  Common\Ext\BtmClient;
use  Common\Ext\XrpClient;
use Think\Db;

class Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439Controller extends \Think\Controller
{
    //实例化缓存数据
    protected function _initialize()
    {
        $config = S('home_config');
        if (!$config) {
            $config = M('Config')->where(array('id' => 1))->find();
            S('home_config', $config);
        }

        C($config);

        $coin = S('home_coin');
        if (!$coin) {
            $coin = M('Coin')->where(array('status' => 1))->select();
            S('home_coin', $coin);
        }

        $coinList = [];
        foreach ($coin as $k => $v) {
            
            if ($v['type'] == 'btm'){
                continue;
            }

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

        C($coinList);
        $market = S('home_market');
        $market_type = [];
        $coin_on = [];
        if (!$market) {
            $market = M('Market')->where(array('status' => 1))->select();
            S('home_market', $market);
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
            $v['xnbimg'] = C('coin')[$v['xnb']]['img'];
            $v['rmbimg'] = C('coin')[$v['rmb']]['img'];
            $v['volume'] = $v['volume'] * 1;
            $v['change'] = $v['change'] * 1;
            $v['title'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
            $v['navtitle'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']). ')';
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

        C('market_type',$market_type);
        C('coin_on',$coin_on);
        C($marketList);
    }

    //index OK
    public function index()
    {
        foreach (C('market') as $k => $v) {

        }

        foreach (C('coin_list') as $k => $v) {

        }
        echo "ok";
    }

    //检测异常，调整不正常的委单
    public function checkYichang()
    {

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_trade write');
        $Trade = M('Trade')->where('deal > num')->order('id desc')->find();

        if ($Trade) {
            if ($Trade['status'] == 0) {
                $mo->table('weike_trade')->where(array('id' => $Trade['id']))->save(array('deal' => Num($Trade['num']), 'status' => 1));
            } else {
                $mo->table('weike_trade')->where(array('id' => $Trade['id']))->save(array('deal' => Num($Trade['num'])));
            }

            $mo->execute('commit');
            $mo->execute('unlock tables');
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
        }
    }

    //检查大盘，调整不成交委单
    public function checkDapan()
    {
        $market = I('market/s', 'doge_hkd');

        $url = 'http://127.0.0.1/Trade/initMatchingTrade/market/'.$market;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_exec($ch);
        $this->success('检测成功！');
    }

    //检查币种
    public function checkUsercoin()
    {
        foreach (C('coin') as $k => $v) {

        }
    }

    //设置市场和币种
    public function marketandcoinb8c3b3d94512472db8()
    {

        foreach (C('market') as $k => $v) {
            $this->autoMatchingTrade($v['name']);
            $this->autoTrade($v['name']);
            $this->setMarket($v['name']);
        }
        
        foreach (C('coin_list') as $k => $v) {
            $this->setcoin($v['name']);
        }
    }

    private function autoMatchingTrade($market){

        if (!$market){
            return false;
        }

        $url = 'http://127.0.0.1/Trade/initMatchingTrade/market/'.$market;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_exec($ch);
    }

    //自动刷单功能
    private function autoTrade($market)
    {
        $time_i = time();
        $time60 = intval($time_i/60);

        $brush = M('auto_trade')->where(array('market'=>$market))->field('brush_interval')->limit(1)->find();
        $brush_interval = $brush['brush_interval']?intval($brush['brush_interval']):2; //默认2分钟一次

        if($time60 % $brush_interval !== 0) {
            return false;
        }else{
            $a_time = M('trade_log')->where('userid=5308 and market="'.$market.'"')->order('id desc')->limit(1)->find();
            if (date('i',$a_time['addtime']) == date('i',$time_i)){
                return ;
            }
        }

        $data = S('autoData'.$market);
        $market_config = C('market');
        $buy = M('Trade')->where(array('market' => $market, 'type' => 1,'userid' => array('gt',0), 'status' => 0))->order('price desc,id asc')->find();
        $sell = M('Trade')->where(array('market' => $market, 'type' => 2,'userid' => array('gt',0),'status' => 0))->order('price asc,id asc')->find();
        $auto = M('AutoTrade')->where(['market' => $market , 'status' => 1])->find();
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
//            if ($xnb == 'btc') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 6);
//                $sell_num = round(randomFloat(0.001, 0.01), 6);
//                $buy_num = round(randomFloat(0.001, 0.01), 6);
//            } else if ($xnb == 'eth') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 4);
//                $sell_num = round(randomFloat(0.01, 0.1), 4);
//                $buy_num = round(randomFloat(0.01, 0.1), 4);
//            } else if ($xnb == 'bcd') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 4);
//                $sell_num = round(randomFloat(0.05, 2), 4);
//                $buy_num = round(randomFloat(0.05, 2), 4);
//            } else if ($xnb == 'wc') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 4);
//                $sell_num = round(randomFloat(0.05, 2), 4);
//                $buy_num = round(randomFloat(0.05, 2), 4);
//            } else if ($xnb == 'etc' || $xnb == 'qtum' || $xnb == 'wcg' || $xnb == 'eos') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 2);
//                $sell_num = round(randomFloat(1, 20), 4);
//                $buy_num = round(randomFloat(1, 20), 4);
//            } else if ($xnb == 'doge') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 2);
//                $sell_num = round(randomFloat(100, 1000), 2);
//                $buy_num = round(randomFloat(100, 1000), 2);
//            } else if ($xnb == 'ifc' || $xnb == 'eac' || $xnb == 'oioc') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 2);
//                $sell_num = round(randomFloat(100, 1000), 2);
//                $buy_num = round(randomFloat(100, 1000), 2);
//            }else if ($xnb == 'bcx') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 2);
//                $sell_num = round(randomFloat(100, 1000), 2);
//                $buy_num = round(randomFloat(100, 1000), 2);
//            }else if ($xnb == 'ejf'|| $xnb == 'btm') {
//                $num = round(randomFloat($auto['min'], $auto['max']), 2);
//                $sell_num = round(randomFloat(100, 1000), 2);
//                $buy_num = round(randomFloat(100, 1000), 2);
//            }else {
//                echo "市场不存在";
//            }

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

            M('AutoTrade')->where(array('market' => $market ,'status' => 1))->save(['buy_price' => $buy_price, 'buy_num' => $buy_num ,'sell_price' => $sell_price, 'sell_num' => $sell_num , 'time' => time()]);

            $tradeLog = M('TradeLog')->where(['status' => 1, 'market' => $market])->order('id desc')->find();
            //成交最大价
            $max_price = $market_config[$market]['max_price'];
            //成交最小价
            $min_price = $market_config[$market]['min_price'];

            //获取已成交买卖最新价格
//            if($market_config[$market]['new_price'] == $market_config[$market]['max_price']){
//                $new_plus = $market_config[$market]['max_price'];
//            }else if ($market_config[$market]['new_price'] == $market_config[$market]['min_price']){
//                $new_plus = $market_config[$market]['max_price'];
//            }else{
//
//            }
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
            $all_market = $data = M('AutoTrade')->where(['market' => $market ,'status' => 1])->select();
            $suc_trade = M('AutoTrade')->where(array('market' => $market , 'status' => 1))->save(['price' => $new_plus , 'max_price' =>$max_price , 'num' => $num ,'type' =>$type , 'min_price' =>$min_price]);
            M('AutoTrade')->where(['market' => $market ,'status' => 1])->setInc('volume',$num);
            M('AutoTrade')->where(['market' => $market ,'status' => 1])->setInc('deal_toble',$mum);

            if ($suc_trade) {
                /*$old_time = M('TradeLog')->where(['market' => $market])->order('id desc')->getField('addtime');
                $new_time = rand($old_time, time());*/
                $new_time = time();
                M('TradeLog')->add(['userid' => 5308, 'peerid' =>5309, 'market' => $market , 'price' => $new_plus , 'num' => $num , 'mum' => $totle , 'fee_buy' => $fee1 , 'fee_sell' => $fee2 , 'type' => $type , 'addtime' => $new_time , 'status' => 1]);

            }
            $mum = $auto['price'] * $num ;

            //24成交量  和  成交额  归零处理
            if (date('H') == 0 && date('i') == 0){
                $volume = M('AutoTrade')->where(['market' => $market , 'status' => 1])->save(['volume' => 0]);
                $deal_toble = M('AutoTrade')->where(['market' => $market ,'status' => 1])->save(['deal_toble' => 0]);
            }

            //涨跌幅
            $hou_price = $market_config[$market]['hou_price'];
            $a_price = M('TradeLog')->where(['market' => $market])->order('id desc')->getField('price');
            $change = round(( ($a_price - $hou_price)/$hou_price ) *100,2 );
            $new_change = M('AutoTrade')->where(['market' => $market ,'status' => 1])->save(['change' => $change]);

            foreach ($all_market as $k =>$v){
                $data['list'][$k]['market'] = $v['market'];
                $data['list'][$k]['img'] = $v['img'];
                $data['list'][$k]['title'] = $v['title'];
                $data['list'][$k]['price'] = round( $v['price'],4);
            }
            $info = M('AutoTrade')->where(['market' => $market ,'status' => 1])->find();
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
            S('autoTrade' . $market, $data);

            //清理首页缓存
            $jiaoyiqu = C('market')[$market]['jiaoyiqu'];
            S('weike_allcoin'.$jiaoyiqu,null);
            S('getChartJson'.$market , null);
            S('getTradelog' . $market, null);
            S('getJsonTop' . $market, null);
        }
    }

    //设置市场
    private function setMarket($market = NULL)
    {
        if (!$market) {
            return null;
        }

        $market_json = M('Market_json')->where(array('name' => $market))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
        }



        $t = $addtime;
        $start = date('Y-m-d',$t).' 00:00:00';
        $start = strtotime($start);
        $end = date('Y-m-d',$t).' 23:59:59';
        $end = strtotime($end);
        /*$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));*/
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
            if (M('Market_json')->where(array('name' => $market, 'addtime' => $end))->find()) {
                M('Market_json')->where(array('name' => $market, 'addtime' => $end))->save(array('data' => json_encode($d)));
            } else {
                M('Market_json')->add(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
            }
        } else {
            $d = null;

            if (M('Market_json')->where(array('name' => $market, 'data' => ''))->find()) {
                M('Market_json')->where(array('name' => $market, 'data' => ''))->save(array('addtime' => $end));
            } else {
                M('Market_json')->add(array('name' => $market, 'data' => '', 'addtime' => $end));
            }
        }
    }

    //设置市场
    private function setcoin($coinname = NULL)
    {
        if (!$coinname) {
            return null;
        }

        $dj_username = C('coin')[$coinname]['dj_yh'];
        $dj_password = C('coin')[$coinname]['dj_mm'];
        $dj_address = C('coin')[$coinname]['dj_zj'];
        $dj_port = C('coin')[$coinname]['dj_dk'];
        if (C('coin')[$coinname]['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包连接失败！');
            }

            $data['trance_mum'] = $json['balance'];
        } elseif(C('coin')[$coinname]['type'] == 'eth' || C('coin')[$coinname]['type'] == 'token'){
            $CoinClient = EthClient($dj_address,$dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包连接失败');
            }

            $accounts = $CoinClient->eth_accounts();
            $sum = 0;
            foreach ($accounts as $key => $value) {
                if(C('coin')[$coinname]['type'] == 'eth'){
                    $sum += $CoinClient->eth_getBalance($value);
                } elseif ( C('coin')[$coinname]['type'] == 'token' ){
                    $call = [
                        'to' => C('coin')[$coinname]['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($value)
                    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , C('coin')[$coinname]['decimals']);
                }

            }
            $data['trance_mum'] = $sum;
        } else {
            $data['trance_mum'] = 0;
        }

        $market_json = M('CoinJson')->where(array('name' => $coinname))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('Myzr')->where(array('name' => $coinname))->order('id asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = date('Y-m-d',$t).' 00:00:00';
        $start = strtotime($start);
        $end = date('Y-m-d',$t).' 23:59:59';
        $end = strtotime($end);
        /*$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));*/

        if ($addtime) {
            if ((time() + (60 * 60 * 24)) < $addtime) {
                return null;
            }

            $aa = 0;
            $bb = $data['trance_mum'];

            $trade_fee_buy = M('Myzr')->where(array(
                'coinname'    => $coinname,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee');
            $trade_fee_sell = M('Myzc')->where(array(
                'coinname'    => $coinname,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee');
            $d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            if (M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
                M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->save(array('data' => json_encode($d)));
            } else {
                M('CoinJson')->add(array('name' => $coinname, 'data' => json_encode($d), 'addtime' => $end));
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
        foreach (C('market') as $k => $v) {

            if (!$v['hou_price'] || (date('H', time()) == '00')) {
                $t = time();
                $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
                if ($v['name'] == 'drt_cny'){
                    $twoStart = mktime(14, 0, 0, date('m', $t), date('d', $t)-1, date('Y', $t));
                    $hou_price = M('TradeLog')->where(array(
                        'market'  => $v['name'],
                        'addtime' => array('lt', $twoStart)
                    ))->order('id desc')->limit(1)->getField('price');
                }else{
                    $hou_price = M('TradeLog')->where(array(
                        'market'  => $v['name'],
                        'addtime' => array('lt', $start)
                    ))->order('id desc')->limit(1)->getField('price');
                }

                if (!$hou_price) {
                    $hou_price = $v['weike_faxingjia'];
                    M('Market')->where(array('name' => $v['name']))->setField('hou_price', $hou_price);
                    S('home_market', null);
                }elseif($hou_price != $v['hou_price']){
                    M('Market')->where(array('name' => $v['name']))->setField('hou_price', $hou_price);
                    S('home_market', null);
                }
            }
        }
    }

    //比特币系列的轮询
    public function qianbaob8c3b3d94512472db7()
    {
        $coin = I('get.coin', 'btc', 'string');
        if (!$coin) {
            exit('no coin name');
        }

        $coinconf = M('Coin')->where(['status' => 1, 'name' => $coin])->find();
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
                if (!($user = M('User')->where(array('username' => $trans['account']))->find())) {
                    echo 'no account find continue' . "\n";
                    continue;
                }

                //如果订单写入了数据库不处理
                if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1','username'=>$trans['address']))->find()) {
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
                    echo 'start receive do:' . "\n";
                    $sfee = 0;
                    $true_amount = $trans['amount'];

                    if (C('coin')[$coin]['zr_zs']) {
                        $song = round(($trans['amount'] / 100) * C('coin')[$coin]['zr_zs'], 8);

                        if ($song) {
                            $sfee = $song;
                            $trans['amount'] = $trans['amount'] + $song;
                        }
                    }

                    //第一次充值赠送 5000 itc, 上级赠送 50 bcx
                    /*$myzr_id = M('myzr')->where(['userid' => $user['id']])->getField('id');
                    $mycz_id = M('mycz')->where(['userid' => $user['id'], 'status' => ['exp', ' in (1, 2, 5) ']])->getField('id');
                    $myua_id = M('UserAward')->where(['userid' => $user['id']])->getField('id');
                    if (!$myzr_id && !$mycz_id && !$myua_id && $user['id'] > 5380) {
                        M('UserCoin')->where(array('userid' => $user['id']))->setInc('ifc', 5000);
                        M('UserAward')->add([
                            'userid' => $user['id'],
                            'award_currency' => 'ifc',
                            'award_num' => 5000,
                            'addtime' => time()
                        ]);
                        if ($user['invit_1'] && $user['invit_1'] > 0) {
                            M('UserCoin')->where(array('userid' => $user['invit_1']))->setInc('bcx', 50);
                        }
                    }*/

                    if ($trans['confirmations'] < C('coin')[$coin]['zr_dz']) {
                        echo $trans['account'] . ' confirmations ' . $trans['confirmations'] . ' not elengh ' . C('coin')[$coin]['zr_dz'] . ' continue ' . "\n";
                        echo 'confirmations <  c_zr_dz continue' . "\n";

                        $res = M('myzr')->where(array('txid' => $trans['txid'],'username'=>$trans['address']))->find();
                        if (!$res) {
                            M('myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => -1));
                        }

                        continue;
                    } else {
                        echo 'confirmations full' . "\n";
                    }

                    $mo = M();
                    $mo->execute('set autocommit=0');
                    $mo->execute('lock tables  weike_user_coin write , weike_myzr  write , weike_issue_ejf  write');
                    $rs = [];
                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $user['id']))->setInc($coin, $trans['amount']);

                    $res = $mo->table('weike_myzr')->where(array('txid' => $trans['txid'],'status' => ['neq',1],'username'=>$trans['address']))->find();
                    if ($res && $trans['blockhash']) {
                        echo 'weike_myzr find and set status 1';
                        $rs[] = $mo->table('weike_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                    } else {
                        echo 'weike_myzr not find and add a new weike_myzr' . "\n";
                        $rs[] = $mo->table('weike_myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => 1));
                    }

                    if (check_arr($rs)) {
                        $mo->execute('commit');
                        echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                        $mo->execute('unlock tables');
                        echo 'commit ok' . "\n";
                    } else {
                        echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                        echo var_export($rs, true);
                        $mo->execute('rollback');
                        $mo->execute('unlock tables');
                        print_r($rs);
                        echo 'rollback ok' . "\n";
                    }
                }

                if ($trans['category'] == 'send') {
                    echo 'start send do:' . "\n";

                    if (3 <= $trans['confirmations']) {
                        $myzc = M('Myzc')->where(array('txid' => $trans['txid']))->find();

                        if ($myzc) {
                            if ($myzc['status'] == 0) {
                                M('Myzc')->where(array('txid' => $trans['txid']))->save(array('status' => 1));
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

        $coinconf = M('Coin')->where(['status' => 1, 'name' => $coin])->find();
        $coin = $coinconf['name'];
        $coinAddress = $coin . 'b';
        if (!$coin) {
            exit('MM');
        }

        if ($coinconf['type'] == 'eth' || $coinconf['type'] == 'token') {
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
                //判断判断
                if (strlen($trans->input) == 138) {
                    $sts = $CoinClient->eth_getTransactionReceipt($trans->hash);
                    $sts_s = object_array($sts);
                    $sts = substr($sts_s['status'], 2, 1);
                    //判断区块上面的转入状态,status = 0 失败,  logs为空 失败
                    if ($sts != 1 || !$sts_s['logs']) {
//                        echo 'file' . "<br>";
                        continue;
                    }
                    $coinconf_token = M('Coin')->where(['status' => 1, 'token_address' => $trans->to])->find();
                    //数据库里面是否有token币种
                    if ($coinconf_token) {

                        $token_value = substr($trans->input, 74, 64);
                        $to = "0x" . substr($trans->input, 34, 40);
                        $mo = M();
                        $mo->startTrans();
                        //判断该交易是否轮询
                        if ($mo->table('weike_myzr')->lock(true)->where(['txid' => $trans->hash])->getField('id')) {
                            $mo->rollback();
                            continue;
                        }
                        //拿token 名称
                        $coinAddress_token = $coinconf_token['name'] . 'b';
                        $user_token = M('UserCoin')->where([$coinAddress_token => $to])->find();
                        if ($user_token) {
                            $sfee = 0;
                            $true_amount_token = $CoinClient->real_banlance_token($CoinClient->decode_hex($token_value), $coinconf_token['decimals']);
//                            $final_amount = $true_amount - 0.001;
//                            $res_token = M('Myzr')->where(['txid' => $trans->hash])->getField('id');
//                            if ($res_token) {
//                                continue;
//                            }

                            $yue_token = $CoinClient->eth_getBalance($to);
                            if ($yue_token < floatval('0.005')) {
                                //eth转账到token的eth手续费
                                $tradeInfo_token = [[
                                    'from' => $coinconf['dj_yh'],
                                    'to' => $to,
                                    'gas' => '0x4a380',
                                    'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval(0.005 - $yue_token))),
                                    'gasPrice' => $CoinClient->eth_gasPrice()
                                ]];
                                $sendrs = $CoinClient->eth_sendTransaction($coinconf['dj_yh'], $coinconf['dj_mm'], $tradeInfo_token);
                            }
                            $mo = M();
                            $mo->startTrans();
                            $rs = [];
                            $rs[] = $mo->table('weike_user_coin')->where(['userid' => $user_token['userid']])->setInc($coinconf_token['name'], $true_amount_token);
                            $txid = $mo->table('weike_myzr')->order('id desc')->limit(1)->getfield('txid');
                            if ($txid == $trans->hash) {
                                $mo->rollback();
                            }
                            $rs[] = $mo->table('weike_myzr')->add([
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
                                $mo->commit();
                            } else {
                                $mo->rollback();
                            }
                        }

                    } else {
                        continue;
                    }
                    //转出
                    if (!$trans->from) {
                        echo 'empty to continue' . "<br>";
                        continue;
                    }
                    if ($user_token = M('UserCoin')->where([$coinAddress_token => $trans->from])->find()) {
                        echo 'start send do:' . "\n";
                        $myzc_token = M('Myzc')->where(['txid' => $trans->hash])->find();
                        if ($myzc_token) {
                            if ($myzc_token['status'] == 0) {
                                M('Myzc')->where(['txid' => $trans->hash])->save(['status' => 1]);
                                echo $true_amount_token . '成功转出' . $coin . ' 币确定';
                            }
                        }
                    } else {
                        continue;
                    }

                } elseif ($coinconf['type'] == 'token') {
                    $coinAddress = 'ethb';
                    $coin = 'eth';
                } else {
                    $user = M('UserCoin')->where([$coinAddress => $trans->to])->find();
                    if ($user) {
                        echo 1;
                        if (M('Myzr')->where(['txid' => $trans->hash, 'status' => '1'])->getField('id')) {
                            continue;
                        }
                        $sfee = 0;
                        $true_amount = $CoinClient->real_banlance($CoinClient->decode_hex($trans->value));
                        $final_amount = $true_amount - 0.001;
                        if ($final_amount > 0.005) {
                            $mo = M();
                            $mo->startTrans();

                            //事务中锁表，避免写入
                            if ($mo->table('weike_myzr')->lock(true)->where(['txid' => $trans->hash])->getField('id')) {
                                $mo->rollback();
                                continue;
                            }

                            $rs = [];
                            $rs[] = $mo->table('weike_user_coin')->where(['userid' => $user['userid']])->setInc($coin, $final_amount);
                            $rs[] = $mo->table('weike_myzr')->add([
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
                                $mo->commit();
                            } else {
                                $mo->rollback();
                            }
                        }
                    }
                    //转出
                    if (!$trans->from) {
                        continue;
                    }
                    if ($user = M('UserCoin')->where([$coinAddress => $trans->from])->find()) {
                        $myzc = M('Myzc')->where(['txid' => $trans->hash])->find();
                        if ($myzc) {
                            if ($myzc['status'] == 0) {
                                M('Myzc')->where(['txid' => $trans->hash])->save(['status' => 1]);
                            }
                        }
                    }
                }
            }
        }
    }

    //btm轮行
    public function qianbaob8c3b3d94512472db9()
    {
        $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
        if ($btmzData){
            $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
            $btmClient->income();
        }else{
            return false;
        }
    }

    //EOS系列的轮询
    public function qianbaob8c3b3d94512472db10()
    {
        $coin = I('get.coin', 'eos', 'string');
        if (!$coin) {
            exit('no coin name');
        }
        $coinconf = M('Coin')->where(['status' => 1,  'zr_jz' => 1 , 'name' => $coin])->find();
        $coin = $coinconf['name'];
        if (!$coin) {
            exit('MM');
        }

        if ($coinconf['type'] == 'eos') {
            $dj_address = $coinconf['dj_zj'];
            $dj_port = $coinconf['dj_dk'];
            $CoinClient = EosClient($dj_address, $dj_port);
            $get_info = $CoinClient->get_info();
            if (!$get_info) {
                $this->error('钱包对接失败!!');
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
                $this->error('轮询出错!');
            }
            foreach ($block_info as $k => $v) {
                M('coin')->where(array('name' => $coin))->setInc('block_num', 1);
                //判断状态释放
                $token_action_trace = $v['action_trace'];
                if (M('Myzr')->where(['txid' => $token_action_trace['trx_id'], 'status' => '1'])->getField('id')) {
                    continue;
                }

                //判断该交易是否轮询
                $mo = M();
                $mo->startTrans();
                if ($mo->table('weike_myzr')->lock(true)->where(['txid' => $token_action_trace['trx_id']])->getField('id')) {
                    $mo->rollback();
                    continue;
                }
                $token_receipt = $token_action_trace['receipt'];
                //判断接受地址
                if ($token_receipt['receiver'] == $coinconf['dj_yh']) {
                    $token_act = $token_action_trace['act'];
                    $coinAddress = M('Coin')->where(['token_address' => $token_act['account']])->getField('name');
                    $coinAddressb = $coinAddress . 'b';
                    $coinAddressp = $coinAddress . 'p';
                    $sfee = 0;
                    //判断操作类型
                    if ($coinAddress && $token_act['name'] == 'transfer') {
                        $token_data = $token_act['data'];
                        if (!$token_data['memo']){
                            $mo->rollback();
                            continue;
                        }
                        $user = M('UserCoin')->where([$coinAddressp => $token_data['memo']])->find();
                        //判断地址和memo是否存在
                        $quantity = $token_data['quantity'];
                        if ($user && $user[$coinAddressb] == $token_data['to']) {
                            $final_amount = trim(substr($quantity, 0, strlen($quantity) - 3));
                            $rs = [];
                            $rs[] = $mo->table('weike_user_coin')->where(['userid' => $user['userid']])->setInc($coinAddress, $final_amount);
                            $username = $token_data['to'] . ' ' . $token_data['memo'];
                            $rs[] = $mo->table('weike_myzr')->add([
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
                                $mo->commit();
                                echo '轮询成功';
                            } else {
                                $mo->rollback();
                            }
                        }

                    }
                }
            }

        }
    }

    //XRP系列的轮询
    public function qianbaob8c3b3d94512472db11()
    {
        $xrpData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="xrp"')->find();
        if ($xrpData){
            $xrpClient = new XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['dj_yh'], $xrpData['dj_mm'], $xrpData['token_address']);
            $history = $xrpClient->history();
            if ($history['result']['status'] == 'success'){
                if (count($history['result']['transactions']) >= 1){
                    foreach ($history['result']['transactions'] as $k=>$v){

                        //如果不是转入地址退出
                        if ($v['tx']['Destination'] != $xrpData['dj_yh']){
                            continue;
                        }

                        //没填tag不转入
                        if (!$v['tx']['DestinationTag']){
                            continue;
                        }

                        $xrpb = M('user_coin')->where(['xrpb'=>$v['tx']['DestinationTag']])->find();
                        if ($xrpb){
                            $hasIncome = M('myzr')->where(['userid'=>$xrpb['userid'],'username'=>$v['tx']['Account'],
                                'coinname'=>'xrp','txid'=>$v['tx']['hash']])->find();
                            if ($hasIncome){
                                continue;
                            }else{

                                //校验hash是否存在
                                $xrpTx = $xrpClient->tx($v['tx']['hash']);

                                if ($xrpTx['result']['status'] != 'success' ){
                                    continue;
                                }

                                if (!$xrpTx['result']['validated']){
                                    continue;
                                }

                                $mo = M();
                                $mo->startTrans();
                                try{
                                    $mo->table('weike_user_coin')->where(['userid'=>$xrpb['userid']])->setInc('xrp',$v['tx']['Amount']/1000000);
                                    $mo->table('weike_myzr')->add([
                                        'userid'    =>  $xrpb['userid'],
                                        'username'  =>  $v['tx']['Account'],
                                        'coinname'  =>  'xrp',
                                        'txid'      =>  $v['tx']['hash'],
                                        'num'       =>  $v['tx']['Amount']/1000000,
                                        'mum'       =>  $v['tx']['Amount']/1000000,
                                        'addtime'   =>  time(),
                                        'status'    =>  1
                                    ]);
                                    $mo->commit();
                                }catch (\Exception $e){
                                    $mo->rollback();
                                }
                            }
                        }else{
                            continue ;
                        }
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    //ETH 子主地址同步
    public function qianbaosync()
    {
        $coin = I('get.coin', 'eth', 'string');
        $coinconf = M('coin')->where(['name' => $coin, 'status' => 1])->find();
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
                $accounts = M('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->limit($i * $offset, 1000)->select();
            }elseif(date('H', time()) == 1 && $coinconf['type'] == 'token'){
                $accounts = M('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->limit(($i-1) * $offset, 1000)->select();
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
        foreach (C('market') as $k => $v) {
            echo '----计算趋势----' . $v['name'] . '------------';
            $tendency_time = 4;
            $t = time();
            $tendency_str = $t - (24 * 60 * 60 * 3);
            $x = 0;

            for (; $x <= 18; $x++) {
                $na = $tendency_str + (60 * 60 * $tendency_time * $x);
                $nb = $tendency_str + (60 * 60 * $tendency_time * ($x + 1));
                $b = M('TradeLog')->where('addtime >= %d and addtime < %d and market = \'%s\'', $na, $nb, $v['name'])->max('price');

                if (!$b) {
                    $houprice = M('market')->field('hou_price')->where(['name'=>$v['name']])->getfield('hou_price');
                    $b = $houprice;
                }

                $rs[] = array($na, $b);
            }

            M('Market')->where(array('name' => $v['name']))->setField('tendency', json_encode($rs));
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
            foreach (C('market') as $k => $v) {
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
            $tradeJson = M('TradeJson')->where(array('market' => $market, 'type' => $v))->order('id desc')->find();
            file_put_contents('/tmp/queue.md', date('Y-m-d H:i:s',time()).M('TradeJson')->getLastSql()."\n", FILE_APPEND | LOCK_EX);
            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
            } else {
                $addtime = M('TradeLog')->where(array('market' => $market))->order('id asc')->getField('addtime');
            }

            if ($addtime) {
                $youtradelog = M('TradeLog')->where('addtime >= %d and market =\'%s\'', $addtime, $market)->sum('num');
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


                    $sum = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->sum('num');

                    if ($sum) {
                        $sta = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id asc')->getField('price');
                        $max = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->max('price');
                        $min = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->min('price');
                        $end = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id desc')->getField('price');
                        $d = array($na, $sum, $sta, $max, $min, $end);

                        if (M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->find()) {
                            M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->save(array('data' => json_encode($d)));
                        } else {
                            M('TradeJson')->add(array('market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v));
                            M('TradeJson')->execute('commit');
                           // M('TradeJson')->where(array('market' => $market, 'data' => '', 'type' => $v))->delete();
                           // M('TradeJson')->execute('commit');
                        }
                    }/* else {
                        M('TradeJson')->add(array('market' => $market, 'data' => '', 'addtime' => $na, 'type' => $v));
                        M('TradeJson')->execute('commit');
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
        $tradeJson = M('TradeJson')->where(array('market' => $market, 'type' => $timearr))->order('id desc')->find();
        if ($tradeJson) {
            $addtime = $tradeJson['addtime'];
        } else {
            $addtime = M('TradeLog')->where(array('market' => $market))->order('id asc')->getField('addtime');
        }

        if ($addtime) {
            $youtradelog = M('TradeLog')->where('addtime >= %d and market =\'%s\'', $addtime, $market)->sum('num');
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

                $sum = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->sum('num');

                if ($sum) {
                    $sta = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id asc')->getField('price');
                    $max = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->max('price');
                    $min = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->min('price');
                    $end = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id desc')->getField('price');
                    $d = array($na, $sum, $sta, $max, $min, $end);

                    if (M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $timearr))->find()) {
                        M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $timearr))->save(array('data' => json_encode($d)));
                    } else {
                        M('TradeJson')->add(array('market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $timearr));
                        M('TradeJson')->execute('commit');
                        M('TradeJson')->where(array('market' => $market, 'data' => '', 'type' => $v))->delete();
                        M('TradeJson')->execute('commit');
                    }
                } else {
                    M('TradeJson')->add(array('market' => $market, 'data' => '', 'addtime' => $na, 'type' => $timearr));
                    M('TradeJson')->execute('commit');
                }
            }
        }

        return '计算成功!';
    }

    //自动交易，只增加委单
    private function upTrade_weike_8a201aa602cd9448()
    {
        $market = I('market/s', NULL);
        $type = rand(1, 2);

        if (!$market) {
            $market = C('market_mr');
        }

        if (!C('market')[$market]) {
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

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_trade write ');
        $rs = array();

        if ($type == 1) {
            $rs[] = $mo->table('weike_trade')->add(array('userid' => 0, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => 0, 'type' => 1, 'addtime' => time(), 'status' => 0));
        } else if ($type == 2) {
            $rs[] = $mo->table('weike_trade')->add(array('userid' => 0, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => 0, 'type' => 2, 'addtime' => time(), 'status' => 0));
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
            echo '交易类型错误';
        }

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            S('getDepth', null);
            $this->matchingAutoTrade($market);
            echo '交易成功！';
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
            echo  '交易失败！';
        }
    }

    //撮合自动交易，只增加委单
    private function matchingAutoTrade()
    {
        $market = I('market/s', NULL);

        if (!$market) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        $mo = M();
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

                $amount = min(round($buy['num'] - $buy['deal'], 8 - C('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - C('market')[$market]['round']));
                $amount = round($amount, 8 - C('market')[$market]['round']);

                if ($amount <= 0) {
                    $log = '错误1交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . "\n";
                    $log .= 'ERR: 成交数量出错，数量是' . $amount;
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
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

                $rs[] = $mo->table('weike_trade_log')->add(array('userid' => 0, 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => 0, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1));

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

                    $jiaoyiqu = C('market')[$market]['jiaoyiqu'];

                    S('weike_allcoin'.$jiaoyiqu,null);
                    S('marketjiaoyie24',null);


                    S('allsum', null);
                    S('getJsonTop' . $market, null);
                    S('getTradelog' . $market, null);
                    S('getDepth' . $market . '1', null);
                    S('getDepth' . $market . '3', null);
                    S('getDepth' . $market . '4', null);
                    S('ChartgetJsonData' . $market, null);
                    S('allcoin', null);
                    S('trends', null);
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


            $new_price = round(M('TradeLog')->where(array('market' => $market, 'status' => 1))->order('id desc')->getField('price'), 6);
            $buy_price = round(M('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'), 6);
            $sell_price = round(M('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'), 6);
            $min_price = round(M('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->min('price'), 6);
            $max_price = round(M('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->max('price'), 6);
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            //time() - (60 * 60 * 24)
            $volume = round(M('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt',$beginToday)
            ))->sum('num'), 6);
            $sta_price = round(M('TradeLog')->where(array(
                'market'  => $market,
                'status'  => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->order('id asc')->getField('price'), 6);
            $Cmarket = M('Market')->where(array('name' => $market))->find();

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
                M('Market')->where(array('name' => $market))->save($upCoinData);
                M('Market')->execute('commit');
                S('home_market', null);
            }
        }
    }

    //设置队列
    public function queue_3a32849e0c77173c325c72a3c2d7aa49()
    {
        $time = time();
        if (S('queue_chk_'.CONTROLLER_NAME.'_'.ACTION_NAME)){
            exit('timeout');
        }else{
            S('queue_chk_'.CONTROLLER_NAME.'_'.ACTION_NAME,$time,60);
        }
        $file_path = DATABASE_PATH . '/check_queue.json';
        $timeArr = array();

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
        $mo = M();
        $market_data = S('set_api_hign_or_low_price');
        if (!$market_data) {
            $market_data = $mo->table('weike_market_control')->select();
            S('set_api_hign_or_low_price', $market_data);
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