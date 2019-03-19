<?php
namespace Home\Controller;

class AjaxController extends HomeController
{
    public function imgUser(){
        if (!userid()) {
            echo "nologin";
        }
        $img1=I('data');
        $img2=I('data1');
        $img3=I('data2');
        if(M('user')->where(array('id'=>userid()))->getField('idcardimg1')){
            $this->ajaxReturn(array('status'=>0,'msg'=>'图片已上传，修改请联系客服'));
        }
        if(!$img1){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请选择身份证正面'));
        }
        if(!$img2){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请选择身份证背面'));
        }
        if(!$img3){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请选择手持身份证正面'));
        }

        $data=array($img1,$img2,$img3);
        $data=implode('_',$data);
        $data=array(
            'idcardimg1'=>$data,
        );
        $ret=M('user')->where(array('id'=>userid()))->save($data);
        if($ret){
//            $this->success('上传成功');
            $this->ajaxReturn(array('status'=>1,'msg'=>'上传成功'));
        }else{
//            $this->error('上传失败');
            $this->ajaxReturn(array('status'=>0,'msg'=>'上传失败'));
        }
    }
    function base64_upload($base64) {
        $base64_image = str_replace(' ', '+', $base64);
        //post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是post的数据，则注释掉这一行
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image, $result)){
            //匹配成功
            if($result[2] == 'jpeg'){
                $image_name = uniqid().'.jpg';
                //纯粹是看jpeg不爽才替换的
            }else{
                $image_name = uniqid().'.'.$result[2];
            }

            $image_file = "Upload/idcard/{$image_name}";
            $info = oss_upload_yz($image_file,base64_decode(end(explode(',',$base64_image))));
            if(!$info){
                return false;
            }else{
                return basename($info);
            }
        }else{
            return false;
        }
    }
    public function imgUp(){
        //上传用户工单
        if (!userid()) {
            $this->ajaxReturn(['msg' => "error"]);
        }

        //判断大小
        if($_FILES['upload_file0']['size'] > 2048000){
            $this->ajaxReturn(['msg' => "error"]);
        }

        //判断格式
        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->ajaxReturn(['msg' => "error"]);
        }

        $id = I('id/d');
        if (!$id){
            $arr = [
                'uid' => userid(),
                'emailAdd' => username(),
            ];
            $id = M('Order')->add($arr);
        }

        $userimg = M('Order')->where(array('id' => $id))->getField("attrimg");
        if($userimg){
            $img_arr = explode("_",$userimg);
            if(count($img_arr) >= 3){
                $this->ajaxReturn(['msg' => "error2"]);
            }
        }

        //上传图片
        $path = 'Upload/order/' . userid() . '/';
        $filename = md5($_FILES['upload_file0']['name']) . uniqid() . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);
        if(!$info){
            $this->ajaxReturn(['msg' => "error"]);
        }

        $userimg = M('Order')->where(array('id' => $id))->getField("attrimg");
        if($userimg){
            $path = $userimg . "_" . $filename;
        }else{
            $path = $filename;
        }

        M('Order')->where(array('id' => $id))->save(array('attrimg' => $path));
        $this->ajaxReturn(['name' => $filename, 'id' => $id]);
    }

    public function getJsonMenu()
    {
        $ajax = I('ajax/s','json');
        $data = S('getJsonMenu');

        if (!$data) {
            foreach (C('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['img'] = $v['xnbimg'];
                $data[$k]['title'] = $v['title'];
                $data[$k]['change'] = $v['change'];
            }
            //echo "getJsonnotcache";
            S('getJsonMenu', $data);
        }
        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function top_coin_menu()
    {
        $ajax = I('ajax/s','json');
        $data = S('weike_getTopCoinMenu');
        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('核心配置有误');
        }
        if (!$data) {
            $data = array();
            foreach($weike_getCoreConfig['weike_indexcat'] as $k=>$v){
                $data[$k]['title'] = $v;
            }
            foreach (C('market') as $k => $v) {
//                $auto_market =round( M('AutoTrade')->where(['market' => $v['name']])->getField('price'),5);
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$v['jiaoyiqu']]['data'][$k] = ['img' => $v['xnbimg'], 'title' => $v['navtitle'],'new_price' =>$v['new_price']];
            }
            S('weike_getTopCoinMenu', $data);
        }

        else {
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
        $type = I('type/s');
        if($type == "cny"){
            echo json_encode("");
        }else{
            $coin_price = D('Market')->get_new_price($type . '_cny');
            if ((floatval($coin_price)+0)>0) {
                echo json_encode($coin_price);
            }else{
                echo json_encode("nodata");
            }
        }
    }

    public function allfinance()
    {
        $ajax = I('ajax/s','json');
        if (!userid()) {
            return false;
        }

        $UserCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $cny['zj'] = 0;
//      $btc['zj'] = 0;

        foreach (C('coin') as $k => $v) {
            if ($v['name'] == 'btc') {
                $cny['ky'] = $UserCoin[$v['name']] * 1;
                $cny['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
                $cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];
//                $btc['ky'] = $UserCoin[$v['name']] * 1;
//                $btc['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
//                $btc['zj'] = $btc['zj'] +$btc['ky'] + $btc['dj'];
            } else {
                /*              if (C('market')[$v['name'] . '_cny']['new_price']) {
                                    $jia = C('market')[$v['name'] . '_cny']['new_price'];
                                } */

                if (C('market')[C('market_type')[$v['name']]]['new_price']) {
                    $jia = C('market')[C('market_type')[$v['name']]]['new_price'];
                } else {
                    $jia = 1;
                }

                $cny['zj'] = round($cny['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2) * 1;
//                $btc['zj'] = round($btc['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2) * 1;
            }
        }

        $data = round($cny['zj'], 8);
//      $data = round($btc['zj'], 8);
        $data = NumToStr($data);

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function allsum()
    {
        $ajax = I('ajax/s','json');
        $data = S('allsum');
        if (!$data) {
            $data = M('TradeLog')->sum('mum');
            S('allsum', $data);
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
        $ajax = I('ajax/s','json');
        $data = S('allcoin');

        // 市场交易记录
        $marketLogs = array();
        foreach (C('market') as $k => $v) {
            //S('getTradelog' . $market, null);
            //$_tmp = S('getTradelog' . $k);
            $_tmp = null;
            if (!empty($_tmp)) {
                $marketLogs[$k] = $_tmp;
            } else {
                $tradeLog = M('TradeLog')->where(array('status' => 1, 'market' => $k))->order('id desc')->limit(50)->select();
                $_data = array();
                foreach ($tradeLog as $_k => $v) {
                    $_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $_data['tradelog'][$_k]['type'] = $v['type'];
                    $_data['tradelog'][$_k]['price'] = $v['price'] * 1;
                    $_data['tradelog'][$_k]['num'] = round($v['num'], 6);
                    $_data['tradelog'][$_k]['mum'] = round($v['mum'], 2);
                }
                $marketLogs[$k] = $_data;
                S('getTradelog' . $k, $_data);
            }
        }

        $themarketLogs = array();
        if ($marketLogs) {
            $last24 = time() - 86400;
            $_date = date('m-d H:i:s', $last24);
            foreach (C('market') as $k => $v) {
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
            foreach (C('market') as $k => $v) {
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
            S('allcoin', $data);
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
        $id = I('id/d', 0);
        $ajax = I('ajax/s','json');
        $jiaoyiqu = S('jiaoyiqu');
        if(!$jiaoyiqu){
            foreach(C('market') as $k => $v){
                $jiaoyiqu[$v['jiaoyiqu']][] = $k;
            }
            S('jiaoyiqu',$jiaoyiqu);
        }
        // 市场交易记录
        $themarketLogs = S('marketjiaoyie24'.$id);
        if(!$themarketLogs){
            // 下午三点 更新交易额
            $beginToday=mktime(15,0,0,date('m'),date('d'),date('Y')) - 86400;

            foreach($jiaoyiqu[$id] as $k=>$v){
                $themarketLogs[$v] = round(M('TradeLog')->where(array(
                    'market'  => $v,
                    'addtime' => array('gt', $beginToday)
                ))->sum('mum'), 6);
            }
            S('marketjiaoyie24'.$id,$themarketLogs);
        }

        // 融合市场
        $data = S('weike_allcoin'.$id);
        if (!$data) {
            $data['info']="数据正常";
            $data['status']=1;
            $data['url']=[];

            foreach ($jiaoyiqu[$id] as $k => $v) {
//                $data['url'][$v][0] = C('market')[$v]['title'];
//                $data['url'][$v][1] = round(C('market')[$v]['new_price'], C('market')[$v]['round']);
//                $data['url'][$v][2] = C('market')[$v]['buy_price'] ? round(C('market')[$v]['buy_price'], C('market')[$v]['round']):0;
//                $data['url'][$v][3] = C('market')[$v]['sell_price']? round(C('market')[$v]['sell_price'],C('market')[$v]['round']):0;
//                $data['url'][$v][4] = isset($themarketLogs[$v]) ? $themarketLogs[$v] : 0;
//                $data['url'][$v][5] = '';
//                $data['url'][$v][6] = round(C('market')[$v]['volume'], 2) * 1;
//                $data['url'][$v][7] = round(C('market')[$v]['change'], 2);
//                $data['url'][$v][8] = C('market')[$v]['name'];
//                $data['url'][$v][9] = C('market')[$v]['xnbimg'];
//                $data['url'][$v][10] = '';
                $auto_data = M('AutoTrade')->where(['market' => $v])->find();
                $trade_log = M('TradeLog')->where(['market' => $v])->order('id desc')->find();
                $data['url'][$v][0] = C('market')[$v]['title'];
                if ($v == 'ifc_cny' || $v == 'eac_cny' || $v == 'oioc_cny'){
                    $data['url'][$v][1] = round($trade_log['price'], 5);
                }else if ($v == 'doge_cny'){
                    $data['url'][$v][1] = round($trade_log['price'], 4);
                }else{
                    $data['url'][$v][1] = round($trade_log['price'], 3);
                }
                $data['url'][$v][2] = C('market')[$v]['buy_price'] ? round(C('market')[$v]['buy_price'], C('market')[$v]['round']):0;
                $data['url'][$v][3] = C('market')[$v]['sell_price']? round(C('market')[$v]['sell_price'],C('market')[$v]['round']):0;
                $data['url'][$v][4] = round($auto_data['deal_toble']);
                $data['url'][$v][5] = '';
                $data['url'][$v][6] = round($auto_data['volume'], 2) * 1;
                $data['url'][$v][7] = round($auto_data['change'], 2);
                $data['url'][$v][8] = C('market')[$v]['name'];
                $data['url'][$v][9] = C('market')[$v]['xnbimg'];
                $data['url'][$v][10] = '';
            }
            S('weike_allcoin'.$id, $data);
        }
//        dd($data);
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
        $id = I('id/d', 0);
        $ajax = I('ajax/s','json');
        $jiaoyiqu = S('jiaoyiqu');
        if(!$jiaoyiqu){
            foreach(C('market') as $k => $v){
                $jiaoyiqu[$v['jiaoyiqu']][] = $k;
            }
            S('jiaoyiqu',$jiaoyiqu);
        }


        $data = S('weike_allcoin'.$id);

        $weike_data=array();
        $weike_data['info']="数据异常";
        $weike_data['status']=0;
        $weike_data['url']="";

        // 市场交易记录
        $themarketLogs = S('marketjiaoyie24');

        if(!$themarketLogs){
            foreach($jiaoyiqu[$id] as $k=>$v){
                $themarketLogs[$v] = round(M('TradeLog')->where(array(
                    'market'  => $v,
                    'addtime' => array('gt', time() - (60 * 60 * 24))
                ))->sum('mum'), 8);
            }
            S('marketjiaoyie24',$themarketLogs);
        }

        if (!$data) {
            $weike_data['info']="数据正常";
            $weike_data['status']=1;
            $weike_data['url']="";

            foreach ($jiaoyiqu[$id] as $k => $v) {

                if($v == "com_cny"){
                    var_dump(round(C('market')[$v]['buy_price'],2));
                }

                $weike_data['url'][$v][0] = C('market')[$v]['title'];
                $weike_data['url'][$v][1] = round(C('market')[$v]['new_price'], C('market')[$v]['round']);
                $weike_data['url'][$v][2] = C('market')[$v]['buy_price'] ? round(C('market')[$v]['buy_price'], C('market')[$v]['round']):0;
                $weike_data['url'][$v][3] = C('market')[$v]['sell_price']? round(C('market')[$v]['sell_price'],C('market')[$v]['round']):0;
                $weike_data['url'][$v][4] = isset($themarketLogs[$v]) ? $themarketLogs[$v] : 0;
                $weike_data['url'][$v][5] = '';
                $weike_data['url'][$v][6] = round(C('market')[$v]['volume'], 2) * 1;
                $weike_data['url'][$v][7] = round(C('market')[$v]['change'], 2);
                $weike_data['url'][$v][8] = C('market')[$v]['name'];
                $weike_data['url'][$v][9] = C('market')[$v]['xnbimg'];
                $weike_data['url'][$v][10] = '';
            }
            $data = $weike_data;
            S('weike_allcoin'.$id, $weike_data);
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
        $ajax = I('ajax/s','json');
        $data = S('trends');

        if (!$data) {
            foreach (C('market') as $k => $v) {
                $tendency = json_decode($v['tendency'], true);
                $tendency = array_slice($tendency,0,18);
                $data[$k]['data'] = $tendency;
                $data[$k]['yprice'] = $v['new_price'];
            }

            S('trends', $data);
        }
        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function trends()
    {
        $ajax = I('ajax/s','json');
        $data = S('trends');

        if (!$data) {
            foreach (C('market') as $k => $v) {
                $tendency = json_decode($v['tendency'], true);
                $data[$k]['data'] = $tendency;
                $data[$k]['yprice'] = $v['new_price'];
            }

            S('trends', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }
    //头部价格变动
    public function getJsonTop()
    {
        $market = I('market/s', NULL);
        $ajax = I('ajax/s','json');
        $data = S('getJsonTop' . $market);
        $auto_market = M('AutoTrade')->where(['market' => $market])->find();

            if ($market) {
                foreach (C('market') as $k => $v) {
                    $v['xnb'] = explode('_', $v['name'])[0];
                    $v['rmb'] = explode('_', $v['name'])[1];
                    $data['list'][$k]['name'] = $v['name'];
                    $data['list'][$k]['img'] = $v['xnbimg'];
                    $data['list'][$k]['title'] = $v['title'];
                    $data['list'][$k]['new_price'] = $v['new_price'];
                    $data['list'][$k]['xnb'] = strtoupper($v['xnb']);
                    $data['list'][$k]['rmb'] = strtoupper($v['rmb']);
                    $data['list'][$k]['change'] = strtoupper($v['change']);
                    $data['list'][$k]['new_price'] = strtoupper($v['new_price']);
                }

                $data['info']['img'] = C('market')[$market]['xnbimg'];
                $data['info']['title'] = C('market')[$market]['title'];
                $data['info']['new_price'] = C('market')[$market]['new_price'];
                $data['info']['max_price'] = C('market')[$market]['max_price'];
                $data['info']['min_price'] = C('market')[$market]['min_price'];
                $data['info']['buy_price'] = C('market')[$market]['buy_price'];
                $data['info']['sell_price'] = C('market')[$market]['sell_price'];
                $data['info']['volume'] = round($auto_market['volume'] , 2);
                $data['info']['change'] = round($auto_market['change'] , 2);
                $new_price = M('trade_log')->where(['market' => $market , 'status' => 1])->order('addtime desc')->find();
                $data['info']['new_price'] = round($new_price['price'] , 4);
                S('getJsonTop' . $market, $data);

        }
        $new_price = M('trade_log')->where(['market' => $market , 'status' => 1])->order('addtime desc')->find();
        $data['info']['new_price'] = round($new_price['price'] , 4);

        if ($ajax) {
            exit(json_encode($data));
        }
        else {
            return $data;
        }
    }

    public function getTradelog()
    {
        $market = I('market/s', NULL);
        $ajax = I('ajax/s','json');
        $data = S('getTradelog' . $market);
        if (!$data) {
            $tradeLog = M('TradeLog')->where(array('status' => 1,'market' => $market))->order('id desc')->limit(50)->select();

            if ($tradeLog) {
                foreach ($tradeLog as $k => $v) {
                    $data['tradelog'][$k]['addtime'] = date('H:i:s', $v['addtime']);
                    $data['tradelog'][$k]['type'] = $v['type'];
                    $data['tradelog'][$k]['price'] = $v['price'] * 1;
                    $data['tradelog'][$k]['num'] = round($v['num'], 6);
                    $data['tradelog'][$k]['mum'] = round($v['mum'], 6);
                }

                S('getTradelog' . $market, $data);
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
        $ajax = I('ajax/s','json');
        $data = S('getAwardInfo');
        if (!$data) {
            $awardInfo = M('UserAward')->order('id desc')->limit(50)->select();

            if ($awardInfo) {
                foreach ($awardInfo as $k => $v) {
                    $data['awardInfo'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $name_tmp = M('User')->where(array('id' => $v['userid']))->getField('username');
                    $data['awardInfo'][$k]['username'] = substr_replace($name_tmp, '****', 2, strlen($name_tmp)-4);
                    $data['awardInfo'][$k]['awardname'] = $v['awardname'];
                }
                S('getAwardInfo', $data,300);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }
    //已修复买卖记录
    public function getDepth()
    {
        $market = I('market/s', NULL);
        $trade_moshi = I('trade_moshi/d', 1);
        $ajax = I('ajax/s','json');
        if (!C('market')[$market]) {
            return null;
        }

        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('核心配置有误');
        }


            if ($trade_moshi == 1) {
                $limt = 10;
            }

            if (($trade_moshi == 3) || ($trade_moshi == 4)) {
                $limt = 15;
            }

            $trade_moshi = intval($trade_moshi);
            if ($trade_moshi == 1) {
                $buy = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit($limt)->select();
                $sell = array_reverse(M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit($limt)->select());
            }

            if ($trade_moshi == 3) {
                $buy = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit($limt)->select();
                $sell = null;
            }

            if ($trade_moshi == 4) {
                $buy = null;
                $sell = array_reverse(M('Trade')->field('id,price,sum(num-deal)as nums')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit($limt)->select());
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





        if ($data['depth']){
            foreach ($data['depth']['buy'] as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if (preg_match('/E/',$vv)){
                        $a = explode("e",strtolower($vv));
                        $vv = bcmul($a[0], bcpow(10, $a[1], 9), 9);
                    }
                    if (strlen($vv) > 10){
                        $data['depth']['buy'][$k][$kk] = substr($vv,0,10);
                    }
                }
            }

            foreach ($data['depth']['sell'] as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if (preg_match('/E/',strtolower($vv))){
                        $a = explode("e",strtolower($vv));
                        $vv = bcmul($a[0], bcpow(10, $a[1], 9), 9);
                    }
                    if (strlen($vv) > 10){
                        $data['depth']['sell'][$k][$kk] = substr($vv,0,10);
                    }
                }
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getEntrustAndUsercoin()
    {
        $market = I('market/s', NULL);
        $ajax = I('ajax/s','json');
        if (!userid()) {
            return null;
        }

        if (!C('market')[$market]) {
            return null;
        }

        $result = M('Trade')->field('id,price,num,deal,mum,type,fee,status,addtime')->where(['status' => 0, 'market' => $market, 'userid' => userid()])->order('id desc')->limit(10)->select();

        if ($result) {
            foreach ($result as $k => $v) {
                $data['entrust'][$k]['addtime'] = date('H:i:s', $v['addtime']);
                $data['entrust'][$k]['type'] = $v['type'];
                $data['entrust'][$k]['price'] = $v['price'] * 1;
                $data['entrust'][$k]['num'] = round($v['num'], 6);
                $data['entrust'][$k]['deal'] = round($v['deal'], 6);
                $data['entrust'][$k]['id'] = round($v['id']);
            }
        }
        else {
            $data['entrust'] = null;
        }

        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        if ($userCoin) {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
            $data['usercoin']['xnb'] = floatval($userCoin[$xnb]);
            $data['usercoin']['xnbd'] = floatval($userCoin[$xnb . 'd']);
            $data['usercoin']['cny'] = floatval($userCoin[$rmb]);
            $data['usercoin']['cnyd'] = floatval($userCoin[$rmb . 'd']);
        }
        else {
            $data['usercoin'] = null;
        }
        if ($ajax) {
            exit(json_encode($data));
        }
        else {
            return $data;
        }
    }

    public function getChat()
    {
        $ajax = I('ajax/s','json');
        $chat = S('getChat');

        if (!$chat) {
            $chat = M('Chat')->where(array('status' => 1))->order('id desc')->limit(500)->select();
            S('getChat', $chat);
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
        $content = I('content/s');
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

        $id = M('Chat')->add(array('userid' => userid(), 'username' => username(), 'content' => $content, 'addtime' => time(), 'status' => 1));

        if ($id) {
            S('getChat', null);
            session('chat' . userid(), time());
            $this->success($id);
        } else {
            $this->error('发送失败');
        }
    }

    public function upcomment()
    {
        $msgaaa = I('msgaaa/s');
        $xnb = I('xnb/s');
        $s1 = I('s1');
        $s2 = I('s2');
        $s3 = I('s3');
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

        if (M('CoinComment')->where(array(
            'userid'   => userid(),
            'coinname' => $xnb,
            'addtime'  => array('gt', time() - 60)
        ))->find()) {
            $this->error('请不要频繁提交！');
        }

        if (M('Coin')->where(array('name' => $xnb))->save(array(
            'tp_zs' => array('exp', 'tp_zs+1'),
            'tp_js' => array('exp', 'tp_js+' . $s1),
            'tp_yy' => array('exp', 'tp_yy+' . $s2),
            'tp_qj' => array('exp', 'tp_qj+' . $s3)
        ))) {
            if (M('CoinComment')->add(array('userid' => userid(), 'coinname' => $xnb, 'content' => $msgaaa, 'addtime' => time(), 'status' => 1))) {
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
        $id = I('id/d');
        $type = I('type/d');
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

        if (S('subcomment' . userid() . $id)) {
            $this->error('请不要频繁提交！');
        }

        if (M('CoinComment')->where(array('id' => $id))->setInc($type, 1)) {
            S('subcomment' . userid() . $id, 1);
            $this->success('提交成功');
        } else {
            $this->error('提交失败！');
        }
    }
}

?>