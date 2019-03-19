<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
class Ajax extends HomeCommon{

    public function imgUser(){
        $uid = userid();
        if (!$uid) {
            echo "nologin";
        }
        $img1=input('data');
        $img2=input('data1');
        $img3=input('data2');
        if(Db::name('user')->where(['id'=> $uid])->value('idcardimg1')){
            $this->error('图片已上传，修改请联系客服');
        }
        if(!$img1){
            $this->error('请选择身份证正面');
        }
        if(!$img2){
            $this->error('请选择身份证背面');
        }
        if(!$img3){
            $this->error('请选择手持身份证正面');
        }

        $data=[$img1,$img2,$img3];
        $data=implode('_',$data);
        $data=['idcardimg1'=>$data];

        $ret=Db::name('user')->where(['id'=> $uid])->update($data);
        if($ret){
            $this->success('上传成功');
        }else{
            $this->error('上传失败');
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
            exit(json_encode(['msg' => "error"]));
        }

        //判断大小
        if($_FILES['upload_file0']['size'] > 2048000){
            exit(json_encode(['msg' => "error"]));
        }

        //判断格式
        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            exit(json_encode(['msg' => "error"]));
        }

        $id = input('id');
        if (!$id){
            $arr = [
                'uid' => userid(),
                'emailAdd' => username(),
            ];
            $id = Db::name('Order')->insertGetId($arr);
        }

        $userimg = Db::name('Order')->where(array('id' => $id))->value("attrimg");
        if($userimg){
            $img_arr = explode("_",$userimg);
            if(count($img_arr) >= 3){
                exit(json_encode(['msg' => "error2"]));
            }
        }

        //上传图片
        $path = 'Upload/order/' . userid() . '/';
        $filename = md5($_FILES['upload_file0']['name']) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);
        if(!$info){
            exit(json_encode(['msg' => "error"]));
        }

        $userimg = Db::name('Order')->where(array('id' => $id))->value("attrimg");
        if($userimg){
            $path = $userimg . "_" . $filename;
        }else{
            $path = $filename;
        }

        Db::name('Order')->where(array('id' => $id))->update(array('attrimg' => $path));
        exit(json_encode(['name' => $filename, 'id' => $id]));
    }

    public function getJsonMenu()
    {
        $ajax = input('ajax');
        $data = Cache::store('redis')->get('getJsonMenu');

        if (!$data) {
            foreach (config('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['img'] = $v['xnbimg'];
                $data[$k]['title'] = $v['title'];
                $data[$k]['change'] = $v['change'];
            }
            Cache::store('redis')->set('getJsonMenu', $data);
        }
        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function top_coin_menu()
    {

        $ajax = input('ajax');
        $data=Cache::store('redis')->get('weike_getTopCoinMenu'); 
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
                $data[$v['jiaoyiqu']]['data'][$k] = ['img' => $v['xnbimg'], 'title' => $v['navtitle'],'new_price' =>$v['new_price']];
            }
            Cache::store('redis')->set('weike_getTopCoinMenu', $data);
        }else {
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
        $type = input('type');
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
        $uid = userid();
        $ajax = input('ajax');
        if (!$uid) {
            return false;
        }

        $UserCoin = Db::name('UserCoin')->where(['userid' => $uid])->find();
        $cny['zj'] = 0;

        foreach (config('coin') as $k => $v) {
            if ($v['name'] == 'btc') {
                $cny['ky'] = $UserCoin[$v['name']] * 1;
                $cny['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
                $cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];
            } else {
                if (config('market')[config('market_type')[$v['name']]]['new_price']) {
                    $jia = config('market')[config('market_type')[$v['name']]]['new_price'];
                } else {
                    $jia = 1;
                }

                $cny['zj'] = round($cny['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2) * 1;
            }
        }

        $data = round($cny['zj'], 8);
        $data = NumToStr($data);

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function allsum()
    {
        $ajax = input('ajax');
        $data =Cache::store('redis')->get('allsum');
        if (!$data) {
            $data = Db::name('TradeLog')->sum('mum');
            Cache::store('redis')->set('allsum', $data);
        }

        $data = round($data);
        $data = str_repeat('0', 12 - strlen($data)) . (string) $data;

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getJsonTop()
    {
        $market = input('market');
        $ajax = input('ajax');
        $data = Cache::store('redis')->get('getJsonTop' . $market);
        $auto_market = Db::name('AutoTrade')->where(['market' => $market])->find();
        if (!$data) {
            if ($market) {
                foreach (config('market') as $k => $v) {
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

                $data['info']['img'] = config('market')[$market]['xnbimg'];
                $data['info']['title'] = config('market')[$market]['title'];
                $data['info']['new_price'] = config('market')[$market]['new_price'];
                $data['info']['max_price'] = config('market')[$market]['max_price'];
                $data['info']['min_price'] = config('market')[$market]['min_price'];
                $data['info']['buy_price'] = config('market')[$market]['buy_price'];
                $data['info']['sell_price'] = config('market')[$market]['sell_price'];
                $data['info']['volume'] = round($auto_market['volume'] , 2);
                $data['info']['change'] = round($auto_market['change'] , 2);
                $new_price = Db::name('trade_log')->where(['market' => $market , 'status' => 1])->order('addtime desc')->find();
                $data['info']['new_price'] = round($new_price['price'] , 4);
                Cache::store('redis')->set('getJsonTop' . $market, $data);

            }
        }else{
            $new_price = Db::name('trade_log')->where(['market' => $market , 'status' => 1])->order('addtime desc')->find();
            $data['info']['new_price'] = round($new_price['price'] , 4);
        }
        if ($ajax) {
            exit(json_encode($data));
        }
        else {
            return $data;
        }
    }

    public function index_b_trends()
    {
        $ajax = input('ajax');
        $data=Cache::store('redis')->get('trends'); 
        if (!$data) {
            foreach (config('market') as $k => $v) {
                $tendency = json_decode($v['tendency'], true);
                $tendency = array_slice($tendency,0,18);
                $data[$k]['data'] = $tendency;
                $data[$k]['yprice'] = $v['new_price'];
            }
            Cache::store('redis')->set('trends',$data,3600);
        }
        if ($ajax) {
            exit(json_encode($data));
        } else {
            return json($data);
        }
    }

  //新增自定义分区查询 2017-06-05
    public function allcoin_a()
    {
        $id = input('id/d', 0);
        $ajax = input('ajax/s','json');
        $jiaoyiqu  = Cache::store('redis')->get('jiaoyiqu');
        if(!$jiaoyiqu){
            foreach(config('market') as $k => $v){
                $jiaoyiqu[$v['jiaoyiqu']][] = $k;
            }
            Cache::store('redis')->set('jiaoyiqu',$jiaoyiqu);
        }
        // 市场交易记录
          $themarketLogs = Cache::store('redis')->get('marketjiaoyie24'.$id);
        // $themarketLogs = S('marketjiaoyie24'.$id);
        if(!$themarketLogs){
            // 下午三点 更新交易额
            $beginToday=mktime(15,0,0,date('m'),date('d'),date('Y')) - 86400;

            foreach($jiaoyiqu[$id] as $k=>$v){
                $themarketLogs[$v] = round(Db::name('TradeLog')->where(array(
                    'market'  => $v,
                    'addtime' => array('gt', $beginToday)
                ))->sum('mum'), 6);
            }
             Cache::store('redis')->set('marketjiaoyie24'.$id,$themarketLogs);
        }

        // 融合市场
          $data  = Cache::store('redis')->get('weike_allcoin'.$id);
        if (!$data) {
            $data['info']="数据正常";
            $data['status']=1;
            $data['url']=[];

            foreach ($jiaoyiqu[$id] as $k => $v) {
                $auto_data = Db::name('AutoTrade')->where(['market' => $v])->find();
                $trade_log = Db::name('TradeLog')->where(['market' => $v])->order('id desc')->find();
                $data['url'][$v][0] = config('market')[$v]['title'];
                if ($v == 'ifc_cny' || $v == 'eac_cny' || $v == 'oioc_cny'){
                    $data['url'][$v][1] = round($trade_log['price'], 5);
                }else if ($v == 'doge_cny'){
                    $data['url'][$v][1] = round($trade_log['price'], 4);
                }else{
                    $data['url'][$v][1] = round($trade_log['price'], 3);
                }
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
            Cache::store('redis')->set('weike_allcoin'.$id,$data);
        }
        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getTradelog()
    {
        $market = input('market');
        $ajax = input('ajax');
        $data = Cache::store('redis')->get('getTradelog' . $market);
        if (!$data) {
            $tradeLog = Db::name('TradeLog')->where(array('status' => 1,'market' => $market))->order('id desc')->limit(50)->select();

            if ($tradeLog) {
                foreach ($tradeLog as $k => $v) {
                    $data['tradelog'][$k]['addtime'] = date('H:i:s', $v['addtime']);
                    $data['tradelog'][$k]['type'] = $v['type'];
                    $data['tradelog'][$k]['price'] = $v['price'] * 1;
                    $data['tradelog'][$k]['num'] = round($v['num'], 6);
                    $data['tradelog'][$k]['mum'] = round($v['mum'], 6);
                }
                Cache::store('redis')->set('getTradelog' . $market, $data);
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
        $market = input('market');
        $trade_moshi = input('trade_moshi');
        $ajax = input('ajax');
        if (!config('market')[$market]) {
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
        $market = input('market');
        $ajax = input('ajax');
        if (!userid()) {
            return null;
        }

        if (!config('market')[$market]) {
            return null;
        }

        $result = Db::name('Trade')->field('id,price,num,deal,mum,type,fee,status,addtime')->where(['status' => 0, 'market' => $market, 'userid' => userid()])->order('id desc')->limit(10)->select();

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

        $userCoin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
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
        $ajax = input('ajax');
        $chat =Cache::store('redis')->get('getChat');

        if (!$chat) {
            $chat = Db::name('Chat')->where(['status' => 1])->order('id desc')->limit(500)->select();
            Cache::store('redis')->set('getChat', $chat);
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
        $content = input('content');
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

        $id = Db::name('Chat')->insert(['userid' => userid(), 'username' => username(), 'content' => $content, 'addtime' => time(), 'status' => 1]);

        if ($id) {
            Cache::rm('getChat');
            session('chat' . userid(), time());
            $this->success($id);
        } else {
            $this->error('发送失败');
        }
    }

    //评分，未使用
    public function upcomment()
    {
        $msgaaa = input('msgaaa');
        $xnb = input('xnb');
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

        if (Db::name('Coin')->where(array('name' => $xnb))->insert(array(
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
        $id = input('id');
        $type = input('type');
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

        if (Cache::store('redis')->get('subcomment' . userid() . $id)) {
            $this->error('请不要频繁提交！');
        }

        if (Db::name('CoinComment')->where(array('id' => $id))->setInc($type, 1)) {
            Cache::store('redis')->set('subcomment' . userid() . $id, 1);
            $this->success('提交成功');
        } else {
            $this->error('提交失败！');
        }
    }
}

?>