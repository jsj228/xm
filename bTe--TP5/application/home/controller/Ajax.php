<?php
namespace app\home\controller;

use think\Db;

class Ajax extends Home
{
    // 上传用户身份证
    public function imgUser(){
        if (!userid()) {
            echo "nologin";
        }
        $userimg = Db::name('User')->where(array('id' => userid()))->value("idcardimg1");
        $img_arr =[];
        if($userimg){
            $img_arr = explode("_",$userimg);
            if(count($img_arr)>=3){
                Db::name('User')->where(array('id' => userid()))->update(array('idcardimg1' => ''));
            }
        }

        if($_FILES['upload_file0']['size'] > 2048000){
            echo "error";
            exit();
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            echo "error";
            exit();
        }

        $path = 'Upload/newcard/';
        $filename = date('Ymd') . '/' . md5(count($img_arr) . userid()) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            echo "error";
            exit();
        }

        $userimg = Db::name('User')->where(array('id' => userid()))->value("idcardimg1");
        if($userimg){
            $img_arr = explode("_",$userimg);
            if(count($img_arr)>=3){
                echo "error2";
                exit();
            }

            $path = $userimg . "_" . $filename;
        }else{
            $path = $filename;
        }
        if(count($img_arr)>=2){
            Db::name('User')->where(array('id' => userid()))->update(array('idcardimg1' => $path,'idcardinfo'=>''));
        }else{
            Db::name('User')->where(array('id' => userid()))->update(array('idcardimg1' => $path));
        }
        echo $filename;
        exit();
    }

    // 上传微信和支付宝图片
    public function payUser(){
        if (!userid()) {
            echo "nologin";
        }

        $type = input('type/s');
        $count = Db::name('UserBank')->where(array('userid' => userid(), 'bank' => $type))->count();
        if($count > 0){
            echo "error2";
            exit();
        }

        if($_FILES['upload_file0']['size'] > 2048000){
            echo "error";
            exit();
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            echo "error";
            exit();
        }

        $path = 'Upload/c2c/';
        $filename = date('Ymd') . '/' . md5($type . userid()) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            echo "error";
            exit();
        }

        echo $filename;
        exit();
    }

    public function imgUp(){
        //上传用户工单
        if (!userid()) {
            $this->error(['msg' => "error"]);
        }

        //判断大小
        if($_FILES['upload_file0']['size'] > 2048000){
            $this->error("error");
        }

        //判断格式
        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $id = input('id/d');
        if (!$id){
            $arr = [
                'uid' => userid(),
                'emailAdd' => username(),
            ];
            $id = Db::name('Order')->insert($arr,false,true);
        }

        $userimg = Db::name('Order')->where(array('id' => $id))->value("attrimg");
        if($userimg){
            $img_arr = explode("_",$userimg);
            if(count($img_arr) >= 3){
                $this->error("error2");
            }
        }

        //上传图片
        $path = 'Upload/order/' . userid() . '/';
        $filename = md5($_FILES['upload_file0']['name']) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);
        if(!$info){
            $this->error("error");
        }

        $userimg = Db::name('Order')->where(array('id' => $id))->value("attrimg");
        if($userimg){
            $path = $userimg . "_" . $filename;
        }else{
            $path = $filename;
        }

        Db::name('Order')->where(array('id' => $id))->update(array('attrimg' => $path));


        $this->result(['name' => $filename, 'id' => $id]);
    }

    public function getJsonMenu()
    {
        $ajax = input('ajax/s','json');
        $data = cache('getJsonMenu');

        if (!$data) {
            foreach (config('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['img'] = $v['xnbimg'];
                $data[$k]['title'] = $v['title'];
            }
            //echo "getJsonnotcache";
            cache('getJsonMenu', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function top_coin_menu()
    {
        $ajax = input('ajax/s','json');
        $data = cache('weike_getTopCoinMenu');
        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('核心配置有误');
        }

        if (!$data) {
            $data = array();
            foreach($weike_getCoreConfig['weike_indexcat'] as $k=>$v){
                $data[$k]['title'] = $v;
            }

            foreach (config('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$v['jiaoyiqu']]['data'][$k] = ['img' => $v['xnbimg'], 'title' => $v['navtitle']];
            }

            cache('weike_getTopCoinMenu', $data);
        } else {
            foreach($weike_getCoreConfig['weike_indexcat'] as $k=>$v){
                $data[$k]['title'] = $v;
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    function getCurrentCny(){
        $type = input('type/s');
        if($type == "cny"){
            echo json_encode("");
        }else{
            $coin_price = model('Market')->get_new_price($type . '_cny');
            if ((floatval($coin_price)+0)>0) {
                echo json_encode($coin_price);
            }else{
                echo json_encode("nodata");
            }
        }
    }

    public function allfinance()
    {
        $ajax = input('ajax/s','json');
        if (!userid()) {
            return false;
        }

        $UserCoin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $cny['zj'] = 0;
//		$btc['zj'] = 0;

        foreach (config('coin') as $k => $v) {
            if ($v['name'] == 'btc') {
                $cny['ky'] = $UserCoin[$v['name']] * 1;
                $cny['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
                $cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];
//                $btc['ky'] = $UserCoin[$v['name']] * 1;
//                $btc['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
//                $btc['zj'] = $btc['zj'] +$btc['ky'] + $btc['dj'];
            } else {
                /* 				if (config('market')[$v['name'] . '_cny']['new_price']) {
                                    $jia = config('market')[$v['name'] . '_cny']['new_price'];
                                } */

                if (config('market')[config('market_type')[$v['name']]]['new_price']) {
                    $jia = config('market')[config('market_type')[$v['name']]]['new_price'];
                } else {
                    $jia = 1;
                }

                $cny['zj'] = round($cny['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2) * 1;
//                $btc['zj'] = round($btc['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2) * 1;
            }
        }

        $data = round($cny['zj'], 8);
//		$data = round($btc['zj'], 8);
        $data = NumToStr($data);

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function allsum()
    {
        $ajax = input('ajax/s','json');
        $data = cache('allsum');
        if (!$data) {
            $data = Db::name('TradeLog')->sum('mum');
            cache('allsum', $data);
        }

        $data = round($data);
        $data = str_repeat('0', 12 - strlen($data)) . (string) $data;

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function allcoin()
    {
        $ajax = input('ajax/s','json');
        $data = cache('allcoin');

        // 市场交易记录
        $marketLogs = array();
        foreach (config('market') as $k => $v) {
            //cache('getTradelog' . $market, null);
            //$_tmp = cache('getTradelog' . $k);
            $_tmp = null;
            if (!empty($_tmp)) {
                $marketLogs[$k] = $_tmp;
            } else {
                $tradeLog = Db::name('TradeLog')->where(array('status' => 1, 'market' => $k))->order('id desc')->limit(50)->select();
                $_data = array();
                foreach ($tradeLog as $_k => $v) {
                    $_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $_data['tradelog'][$_k]['type'] = $v['type'];
                    $_data['tradelog'][$_k]['price'] = $v['price'] * 1;
                    $_data['tradelog'][$_k]['num'] = round($v['num'], 6);
                    $_data['tradelog'][$_k]['mum'] = round($v['mum'], 2);
                }
                $marketLogs[$k] = $_data;
                cache('getTradelog' . $k, $_data);
            }
        }

        $themarketLogs = array();
        if ($marketLogs) {
            $last24 = time() - 86400;
            $_date = date('m-d H:i:s', $last24);
            foreach (config('market') as $k => $v) {
                $tradeLog = isset($marketLogs[$k]['tradelog']) ? $marketLogs[$k]['tradelog'] : null;
                if ($tradeLog) {
                    $sum = 0;
                    foreach ($tradeLog as $_k => $_v) {
                        if ($_v['addtime'] < $_date) {
                            continue;
                        }
                        $sum += $_v['mum'];
                    }
                    $themarketLogs[$k] = $sum;
                }
            }
        }

        if (!$data) {
            foreach (config('market') as $k => $v) {
                $data[$k][0] = $v['title'];
                $data[$k][1] = round($v['new_price'], $v['round']);//最新价格
                $data[$k][2] = round($v['buy_price'], $v['round']);//买入价格
                $data[$k][3] = round($v['sell_price'], $v['round']);//卖出价格
                $data[$k][4] = isset($themarketLogs[$k]) ? $themarketLogs[$k] : 0;//round($v['volume'] * $v['new_price'], 2) * 1;
                $data[$k][5] = '';
                $data[$k][6] = round($v['volume'], 2) * 1;
                $data[$k][7] = round($v['change'], 2);
                $data[$k][8] = $v['name'];
                $data[$k][9] = $v['xnbimg'];
                $data[$k][10] = '';
            }
            cache('allcoin', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    //新增自定义分区查询 2017-06-05
    public function allcoin_a()
    {
        $id = input('id/d', 0);
        $ajax = input('ajax/s','json');
        $jiaoyiqu = cache('jiaoyiqu');
        if(!$jiaoyiqu){
            foreach(config('market') as $k => $v){
                $jiaoyiqu[$v['jiaoyiqu']][] = $k;
            }
            cache('jiaoyiqu',$jiaoyiqu);
        }

        // 市场交易记录
        $themarketLogs = cache('marketjiaoyie24'.$id);
        if(!$themarketLogs){
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            if(isset($jiaoyiqu[$id])) {
                foreach ($jiaoyiqu[$id] as $k => $v) {
                    $themarketLogs[$v] = round(Db::name('TradeLog')->where(array(
                        'market' => $v,
                        'addtime' => array('gt', $beginToday)
                    ))->sum('mum'), 6);
                }
            }
            cache('marketjiaoyie24'.$id,$themarketLogs);
        }

        // 融合市场
        $data = cache('weike_allcoin'.$id);
        if (!$data) {
            $data['info']="数据正常";
            $data['status']=1;
            $data['url']=[];
            if(isset($jiaoyiqu[$id])){
                foreach ($jiaoyiqu[$id] as $k => $v) {
                    $auto_data = Db::name('AutoTrade')->where(['market' => $v])->find();
                    $trade_log = Db::name('TradeLog')->where(['market' => $v])->order('id desc')->find();
                    $data['url'][$v][0] = config('market')[$v]['title'];
                    $data['url'][$v][1] = round($trade_log['price'], 3);
                    $data['url'][$v][2] = config('market')[$v]['buy_price'] ? round(config('market')[$v]['buy_price'], config('market')[$v]['round']):0;
                    $data['url'][$v][3] = config('market')[$v]['sell_price']? round(config('market')[$v]['sell_price'],config('market')[$v]['round']):0;
                    $data['url'][$v][4] = round($auto_data['deal_toble']);
                    $data['url'][$v][5] = '';
                    $data['url'][$v][6] = round($auto_data['volume'], 2) * 1;
                    $data['url'][$v][7] = round($auto_data['change'], 2);
                    $data['url'][$v][8] = config('market')[$v]['name'];
                    $data['url'][$v][9] = config('market')[$v]['xnbimg'];
                    $data['url'][$v][10] = '';
                }
            }
            cache('weike_allcoin'.$id, $data);
        }
        if ($ajax) {
            echo json_encode($data);
            exit();
        } else {
            return $data;
        }
    }

    //新增自定义分区查询 2017-06-05
    public function allcoin_a_test()
    {
        $id = input('id/d', 0);
        $ajax = input('ajax/s','json');
        $jiaoyiqu = cache('jiaoyiqu');
        if(!$jiaoyiqu){
            foreach(config('market') as $k => $v){
                $jiaoyiqu[$v['jiaoyiqu']][] = $k;
            }
            cache('jiaoyiqu',$jiaoyiqu);
        }


        $data = cache('weike_allcoin'.$id);

        $weike_data=array();
        $weike_data['info']="数据异常";
        $weike_data['status']=0;
        $weike_data['url']="";

        // 市场交易记录
        $themarketLogs = cache('marketjiaoyie24');

        if(!$themarketLogs){
            foreach($jiaoyiqu[$id] as $k=>$v){
                $themarketLogs[$v] = round(Db::name('TradeLog')->where(array(
                    'market'  => $v,
                    'addtime' => array('gt', time() - (60 * 60 * 24))
                ))->sum('mum'), 8);
            }
            cache('marketjiaoyie24',$themarketLogs);
        }

        if (!$data) {
            $weike_data['info']="数据正常";
            $weike_data['status']=1;
            $weike_data['url']="";

            foreach ($jiaoyiqu[$id] as $k => $v) {

                if($v == "com_cny"){
                    var_dump(round(config('market')[$v]['buy_price'],2));
                }

                $weike_data['url'][$v][0] = config('market')[$v]['title'];
                $weike_data['url'][$v][1] = round(config('market')[$v]['new_price'], config('market')[$v]['round']);
                $weike_data['url'][$v][2] = config('market')[$v]['buy_price'] ? round(config('market')[$v]['buy_price'], config('market')[$v]['round']):0;
                $weike_data['url'][$v][3] = config('market')[$v]['sell_price']? round(config('market')[$v]['sell_price'],config('market')[$v]['round']):0;
                $weike_data['url'][$v][4] = isset($themarketLogs[$v]) ? $themarketLogs[$v] : 0;
                $weike_data['url'][$v][5] = '';
                $weike_data['url'][$v][6] = round(config('market')[$v]['volume'], 2) * 1;
                $weike_data['url'][$v][7] = round(config('market')[$v]['change'], 2);
                $weike_data['url'][$v][8] = config('market')[$v]['name'];
                $weike_data['url'][$v][9] = config('market')[$v]['xnbimg'];
                $weike_data['url'][$v][10] = '';
            }
            $data = $weike_data;
            cache('weike_allcoin'.$id, $weike_data);
        }

        if ($ajax) {
            echo json_encode($data);
            exit();
        } else {
            return $data;
        }
    }

    public function index_b_trends()
    {
        $ajax = input('ajax/s','json');
        $data = cache('trends');

        if (!$data) {
            foreach (config('market') as $k => $v) {
                if($v['tendency']){
                    $tendency = json_decode($v['tendency'], true);
                    $tendency = array_slice($tendency,0,18);
                    $data[$k]['data'] = $tendency;
                }else{
                    $data[$k]['data'] = [];
                }


                $data[$k]['yprice'] = $v['new_price'];
            }

            cache('trends', $data);
        }
        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function trends()
    {
        $ajax = input('ajax/s','json');
        $data = cache('trends');

        if (!$data) {
            foreach (config('market') as $k => $v) {
                $tendency = json_decode($v['tendency'], true);
                $data[$k]['data'] = $tendency;
                $data[$k]['yprice'] = $v['new_price'];
            }

            cache('trends', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    //交易页面头部交易信息
    public function getJsonTop()
    {
        $market = input('market/s', NULL);
        $ajax = input('ajax/s','json');
        $data = cache('getJsonTop' . $market);

        if (!$data) {
            if ($market) {
                $auto_market = Db::name('AutoTrade')->where(['market' => $market])->find();
                foreach (config('market') as $k => $v) {
                    $trade_log = Db::name('TradeLog')->where(['market' => $v['name']])->order('id desc')->find();
                    $v['xnb'] = explode('_', $v['name'])[0];
                    $v['rmb'] = explode('_', $v['name'])[1];
                    $data['list'][$k]['name'] = $v['name'];
                    $data['list'][$k]['img'] = $v['xnbimg'];
                    $data['list'][$k]['title'] = $v['title'];
                    $data['list'][$k]['new_price'] = round($trade_log['price'] ,4);
                }
                //获取当前市场最新成交价格
                $trade_price = Db::name('TradeLog')->where(['market' => $market])->order('id desc')->find();
                $data['info']['img'] = config('market')[$market]['xnbimg'];
                $data['info']['title'] = config('market')[$market]['title'];
                $data['info']['new_price'] = round($trade_price['price'] ,4);//最新交易价格

                if(config('market')[$market]['zhang']>0){
                    if(config('market')[$market]['hou_price']>0){
                        $data['info']['zhang'] = config('market')[$market]['hou_price']+floatval(config('market')[$market]['hou_price']*floatval((config('market')[$market]['zhang'])/100));
                    }
                }else{
                    $data['info']['zhang'] = '';
                }

                if(config('market')[$market]['die']>0){
                    if(config('market')[$market]['hou_price']>0){
                        $data['info']['die'] = config('market')[$market]['hou_price']-floatval(config('market')[$market]['hou_price']*floatval((config('market')[$market]['die'])/100));
                    }
                }else{
                    $data['info']['die'] = '';
                }


                //if(config('market')[$market]['max_price']){
                $data['info']['max_price'] = config('market')[$market]['max_price'];//最大交易价格
                //}else{
                //$weike_tempprice = round((config('market')[$market]['weike_faxingjia'] / 100) * (100 + config('market')[$market]['zhang']), config('market')[$market]['round']);
                //$data['info']['max_price'] = $weike_tempprice;
                //}

                //if(config('market')[$market]['min_price']){
                $data['info']['min_price'] = config('market')[$market]['min_price'];//最小交易价格
                //}else{
                //	$weike_tempprice = round((config('market')[$market]['weike_faxingjia'] / 100) * (100 - config('market')[$market]['die']), config('market')[$market]['round']);
                //	$data['info']['min_price'] = $weike_tempprice;
                //}
                $buy_price = Db::name('TradeLog')->where(['market' =>$market , 'type' => 1 ,'status' => 1])->order('id desc')->value('price');
                $sell_price = Db::name('TradeLog')->where(['market' =>$market , 'type' => 2 ,'status' => 1])->order('id desc')->value('price');
                $data['info']['buy_price'] = round($buy_price ,4);
                $data['info']['sell_price'] = round($sell_price , 4);
                $data['info']['volume'] = round($auto_market['volume'] , 2) * 1;
                $data['info']['change'] = round($auto_market['change'] , 2);
                cache('getJsonTop' . $market, $data);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getTradelog()
    {
        $market = input('market/s', NULL);
        $ajax = input('ajax/s','json');
        $data = cache('getTradelog' . $market);
        if (!$data) {
            $tradeLog = Db::name('TradeLog')->where(array('status' => 1,'market' => $market))->order('id desc')->limit(50)->select();

            if ($tradeLog) {
                foreach ($tradeLog as $k => $v) {
                    $data['tradelog'][$k]['addtime'] = date('H:i:s', $v['addtime']);
                    $data['tradelog'][$k]['type'] = $v['type'];
                    $data['tradelog'][$k]['price'] = round($v['price'] * 1,4);
                    $data['tradelog'][$k]['num'] = round($v['num'], 4);
                    $data['tradelog'][$k]['mum'] = round($v['mum'], 4);
                }

                cache('getTradelog' . $market, $data);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getAwardInfo()
    {
        $ajax = input('ajax/s','json');
        $data = cache('getAwardInfo');
        if (!$data) {
            $awardInfo = Db::name('UserAward')->order('id desc')->limit(50)->select();

            if ($awardInfo) {
                foreach ($awardInfo as $k => $v) {
                    $data['awardInfo'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $name_tmp = Db::name('User')->where(array('id' => $v['userid']))->value('username');
                    $data['awardInfo'][$k]['username'] = substr_replace($name_tmp, '****', 2, strlen($name_tmp)-4);
                    $data['awardInfo'][$k]['awardname'] = $v['awardname'];
                }
                cache('getAwardInfo', $data,300);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getDepth()
    {
        $market = input('market/s', NULL);
        $trade_moshi = input('trade_moshi/d', 1);
        $ajax = input('ajax/s','json');
        if (!config('market')[$market]) {
            return null;
        }

        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('核心配置有误');
        }

        $data_getDepth = cache('getDepth');
        if (!isset($data_getDepth[$market][$trade_moshi]) || !$data_getDepth[$market][$trade_moshi]) {
            if ($trade_moshi == 1) {
                $limt = 15;
            }else{
                $limt = 20;
            }

            $trade_moshi = intval($trade_moshi);

            if ($trade_moshi == 1) {
                $buy = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit($limt)->select();
                $sell = array_reverse(Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit($limt)->select());
            }

            if ($trade_moshi == 3) {
                $buy = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit($limt)->select();
                $sell = null;
            }

            if ($trade_moshi == 4) {
                $buy = null;
                $sell = array_reverse(Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit($limt)->select());
            }

            if ($buy) {
                foreach ($buy as $k => $v) {
                    $data['depth']['buy'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
                }
            } else {
                $data['depth']['buy'] = '';
            }

            if ($sell) {
                foreach ($sell as $k => $v) {
                    $data['depth']['sell'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
                }
            } else {
                $data['depth']['sell'] = '';
            }

            $data_getDepth[$market][$trade_moshi] = $data;
            cache('getDepth', $data_getDepth);
        } else {
            $data = $data_getDepth[$market][$trade_moshi];
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getEntrustAndUsercoin()
    {
        $market = input('market/s', NULL);
        $ajax = input('ajax/s','json');
        if (!userid()) {
            return null;
        }

        if (!isset(config('market')[$market]) || !config('market')[$market]) {
            return null;
        }

        $result = Db::name('Trade')->field('id,price,num,deal,mum,type,fee,status,addtime')->where(['status' => 0, 'market' => $market, 'userid' => userid()])->order('id desc')->limit(10)->select();

        if ($result) {
            foreach ($result as $k => $v) {
                $data['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                $data['entrust'][$k]['type'] = $v['type'];
                $data['entrust'][$k]['price'] = $v['price'] * 1;
                $data['entrust'][$k]['num'] = round($v['num'], 6);
                $data['entrust'][$k]['deal'] = round($v['deal'], 6);
                $data['entrust'][$k]['id'] = round($v['id']);
            }
        } else {
            $data['entrust'] = null;
        }

        $userCoin = Db::name('UserCoin')->where(array('userid' => userid()))->find();

        if ($userCoin) {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
            $data['usercoin']['xnb'] = floatval($userCoin[$xnb]);
            $data['usercoin']['xnbd'] = floatval($userCoin[$xnb . 'd']);
            $data['usercoin']['cny'] = floatval($userCoin[$rmb]);
            $data['usercoin']['cnyd'] = floatval($userCoin[$rmb . 'd']);
        } else {
            $data['usercoin'] = null;
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getChat()
    {
        $ajax = input('ajax/s','json');
        $chat = cache('getChat');

        if (!$chat) {
            $chat = Db::name('Chat')->where(array('status' => 1))->order('id desc')->limit(500)->select();
            cache('getChat', $chat);
        }

        asort($chat);

        if ($chat) {
            foreach ($chat as $k => $v) {
                $data[] = array((int) $v['id'], $v['username'], $v['content']);
            }
        } else {
            $data = '';
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function upChat()
    {
        $content = input('content/s');
        if (!userid()) {
            $this->error('请先登录...');
        }

        $content = msubstr($content, 0, 20, 'utf-8', false);

        if (!$content) {
            $this->error('请先输入内容');
        }

        if (time() < (session('chat' . userid()) + 10)) {
            $this->error('不能发送过快');
        }

        $id = Db::name('Chat')->insert(array('userid' => userid(), 'username' => username(), 'content' => $content, 'addtime' => time(), 'status' => 1));

        if ($id) {
            cache('getChat', null);
            session('chat' . userid(), time());
            $this->success($id);
        } else {
            $this->error('发送失败');
        }
    }

    public function upcomment()
    {
        $msgaaa = input('msgaaa/s');
        $xnb = input('xnb/s');
        $s1 = input('s1');
        $s2 = input('s2');
        $s3 = input('s3');
        if (empty($msgaaa)) {
            $this->error('提交内容错误');
        }

        if (!check($s1, 'd')) {
            $this->error('技术评分错误');
        }

        if (!check($s2, 'd')) {
            $this->error('应用评分错误');
        }

        if (!check($s3, 'd')) {
            $this->error('前景评分错误');
        }

        if (!userid()) {
            $this->error('请先登录！');
        }

        if (Db::name('CoinComment')->where(array(
            'userid'   => userid(),
            'coinname' => $xnb,
            'addtime'  => array('gt', time() - 60)
        ))->find()) {
            $this->error('请不要频繁提交！');
        }

        if (Db::name('Coin')->where(array('name' => $xnb))->update(array(
            'tp_zs' => array('exp', 'tp_zs+1'),
            'tp_js' => array('exp', 'tp_js+' . $s1),
            'tp_yy' => array('exp', 'tp_yy+' . $s2),
            'tp_qj' => array('exp', 'tp_qj+' . $s3)
        ))) {
            if (Db::name('CoinComment')->insert(array('userid' => userid(), 'coinname' => $xnb, 'content' => $msgaaa, 'addtime' => time(), 'status' => 1))) {
                $this->success('提交成功');
            } else {
                $this->error('提交失败！');
            }
        } else {
            $this->error('提交失败！');
        }
    }

    public function subcomment()
    {
        $id = input('id/d');
        $type = input('type/d');
        if ($type != 1) {
            if ($type != 2) {
                if ($type != 3) {
                    $this->error('参数错误！');
                } else {
                    $type = 'xcd';
                }
            } else {
                $type = 'tzy';
            }
        } else {
            $type = 'cjz';
        }

        if (!check($id, 'd')) {
            $this->error('参数错误1');
        }

        if (!userid()) {
            $this->error('请先登录！');
        }

        if (cache('subcomment' . userid() . $id)) {
            $this->error('请不要频繁提交！');
        }

        if (Db::name('CoinComment')->where(array('id' => $id))->setInc($type, 1)) {
            cache('subcomment' . userid() . $id, 1);
            $this->success('提交成功');
        } else {
            $this->error('提交失败！');
        }
    }
}

?>