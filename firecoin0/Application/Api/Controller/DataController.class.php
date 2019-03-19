<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/2
 * Time: 13:37
 */
namespace Api\Controller;

use Think\Controller;
use Home\Controller\AjaxController;

class DataController extends Controller
{
    //分发
    public function v1(){
        $url = $_SERVER['REQUEST_URI'];
        $api = trim(strrchr($url, '/'),'/');
        if(strpos($api,'?'))  $api = substr($api,0,strrpos($api,'?'));

        $api_list = ['markets','allTicker','ticker','depth','trades','kline'];

        if(in_array($api,$api_list)){
            $this->$api();
        }else{
            echo '请求地址错误';
        }
    }

    //获取已开启的市场信息，包括价格、数量小数点位数
    public function markets(){
        $markets = M('api_markets')->where(['status'=>0])->select();
        foreach ($markets as $v) $res[$v['market']] = ['amountscale'=>$v['amountscale'],'pricescale'=>$v['pricescale']];
        echo json($res);
    }

    //全币种行情
    public function allTicker(){
        $markets = M('api_markets')->field('market')->where(['status'=>0])->select();
        $markets = array_column($markets,'market');

        $allTicker = M('auto_trade')->where(['market'=>['in',$markets]])->field('market,volume,price,sell_price,buy_price,max_price,min_price')->select();
        foreach ($allTicker as $v){
            $markets = explode('_',$v['market']);
            $market = $markets[1].$markets[0];
            $res[$market] = [
                'vol'=>$v['volume'],                //成交量(最近的24小时)
                'last'=>$v['price'],               //最新成交价
                'sell'=>$v['sell_price'],         //卖一价
                'buy'=>$v['buy_price'],          //买一价
                'High'=>$v['max_price'],         //最高价
                'low'=>$v['min_price']           //最低价
            ];
        }
        echo json($res);
    }

    //获取行情
    public function ticker(){
        $market = I('market/s');
        $ticker = M('auto_trade')->where(['market'=>$market])->field('market,volume,price,sell_price,buy_price,max_price,min_price')->find();

        if($ticker){
            $res["ticker"] = [
                'vol'=>$ticker['volume'],
                'last'=>$ticker['price'],
                'sell'=>$ticker['sell_price'],
                'buy'=>$ticker['buy_price'],
                'high'=>$ticker['max_price'],
                'low'=>$ticker['min_price'],
            ];
            $res['date'] = time();
            echo json($res);
        }else{
            echo '请求地址错误';
        }
    }

    //市场深度
    public function depth(){
        $market = I('market/s');
        $limt = I('size/d',10);

        //获取最佳买入价、卖出价
        $buy = M('Trade')->field('id,price,sum(num-deal)as nums,addtime')->where(['status' => 0, 'type' => 1, 'market' => $market])->group('price')->order('price desc')->limit($limt)->select();
        $sell = M('Trade')->field('id,price,sum(num-deal)as nums,addtime')->where(['status' => 0, 'type' => 2, 'market' => $market])->group('price')->order('price asc')->limit($limt)->select();

        if ($sell) {
            foreach ($sell as $k => $v) {
                $res['asks'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1),$v['addtime']);
            }

            foreach ($res['asks'] as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if (preg_match('/E/',strtolower($vv))){
                        $a = explode("e",strtolower($vv));
                        $vv = bcmul($a[0], bcpow(10, $a[1], 9), 9);
                    }
                    if (strlen($vv) > 10){
                        $res['asks'][$k][$kk] = substr($vv,0,10);
                    }
                }
            }
        } else {
            $res['asks'] = [];
        }
        if ($buy) {
            foreach ($buy as $k => $v) {
                $res['bids'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1),$v['addtime']);
            }
            foreach ($res['bids'] as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if (preg_match('/E/',$vv)){
                        $a = explode("e",strtolower($vv));
                        $vv = bcmul($a[0], bcpow(10, $a[1], 9), 9);
                    }
                    if (strlen($vv) > 10){
                        $res['bids'][$k][$kk] = substr($vv,0,10);
                    }
                }
            }

        } else {
            $res['bids'] = [];
        }

        $res['timestamp'] = time();
        echo json($res);die;
    }

    //历史成交
    public function trades(){
        $market = I('market/s');

        $trade_log = M('trade_log')->where(['market'=>$market])->field('mum,addtime,price,id,type')->order('id desc')->limit(50)->select();

        if($trade_log){
            foreach ($trade_log as $v){
                $res[] = [
                    'amount'=>$v['mum'],
                    'date'=>$v['addtime'],
                    'price'=>$v['price'],
                    'tid'=>$v['id'],
                    'trade_type'=>$v['type']==1?'bid':'ask',
                    'type'=>$v['type']==1?'buy':'sell',
                ];
            }
            echo json($res);
        }else{
            echo '数据错误';
        }
    }

    //K线
    public function kline(){
        $market = I('market/s');
        $type = I('type/s','1min');
        $since = I('since/d');
        $size = I('size/d',100);

        $type_list = ['1min'=>1,'3min'=>3,'5min'=>5,'15min'=>15,'30min'=>30,'1day'=>'1440','3day'=>0,'1week'=>0,'1hour'=>0,'2hour'=>0,'4hour'=>0,'6hour'=>0,'12hour'=>0];

        if(isset($type_list[$type]) && $type_list[$type]){
            $where['type'] = $type_list[$type];
        }else{
            echo '暂无数据'; die;
        }

        $where['status'] = 0;
        if($since) $where['addtime'] = ['gt',$since];
        $where['market'] = $market;

        $kline = M('trade_json')->where($where)->field('data,market')->limit($size)->order('id desc')->select();

        if($kline){
            foreach ($kline as $k=>$v){
                $data = ltrim($v['data'],'[');
                $data = rtrim($data,']');
                $data_arr[] = explode(',',$data);
            }
            foreach ($data_arr as $k=>$v){
                $res[] = json(['Time'=>$v[0]]);
            }
            echo json($res);
        }else{
            echo '参数错误';
        }
    }

}