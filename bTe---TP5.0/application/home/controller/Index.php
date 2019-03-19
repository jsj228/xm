<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\sessoin;

class Index extends HomeCommon
{

    public function index()
    {
        //获取banner图片
        $indexAdver = Cache::store('redis')->get('index_indexAdver');
        if (!$indexAdver) {
            $indexAdver = Db::name('adver')->where('status',1)->order('sort desc')->select();
            Cache::store('redis')->set('index_indexAdver', $indexAdver);
        }
        $this->assign('indexAdver', $indexAdver);

        switch(config('index_html')){
            case "a":
                //如果a模版
                $indexArticle = Cache::store('redis')->get('index_indexArticle');
                $indexArticleType = array(
                    "gonggao" => "aaa",
                    "taolun"  => "币友说币",
                    "hangye"  => "bbb"
                );

                if (!$indexArticle) {
                    foreach ($indexArticleType as $k => $v) {
                        $indexArticle[$k] = Db::name('article')->where('type='.$v.' and status=1 and index=1')->order('id desc')->paginate(4);

                        foreach($indexArticle[$k] as $kk =>$vv){
                            $indexArticle[$k][$kk]['content'] = mb_substr(clear_html($vv['content']),0,40,'utf-8');
                            if($indexArticle[$k][$kk]['img']){
                                $indexArticle[$k][$kk]['img'] = config('TMPL_PARSE_STRING.__DOMAIN__') . "/public/upload/article/".$indexArticle[$k][$kk]['img'];
                            }else{
                                $indexArticle[$k][$kk]['img'] = "/comfile/default/defaultImg.jpg";
                            }
                        }
                    }
                     Cache::store('redis')->set('index_indexArticle', $indexArticle);
                }
                break;
            default:
                $indexArticleType = Cache::store('redis')->get('index_indexArticleType');

                if (!$indexArticleType) {
                    $indexArticleType = Db::name('article_type')->where(['status'=>1,'index'=>1])->order('sort asc,id desc')->paginate(3);
                    Cache::store('redis')->set('index_indexArticleType', $indexArticleType);
                }
                $indexArticle = Cache::store('redis')->get('index_indexArticle');

                if (!$indexArticle) {
                    foreach ($indexArticleType as $k => $v) {
                        $indexArticle[$k] = Db::name('article')->where(['type' => $v['name'], 'status' => 1, 'index' => 1])->order('id desc')->paginate(6);
                    }
                     Cache::store('redis')->set('index_indexArticle', $indexArticle);
                }
        }
        $this->assign('indexArticleType', $indexArticleType);
        $this->assign('indexArticle', $indexArticle);

        $indexLink = Cache::store('redis')->get('index_indexLink');
        if (!$indexLink) {
            $indexLink = Db::name('link')->where('status=1')->order('sort asc ,id desc')->select();
            Cache::store('redis')->set('index_indexLink',$indexLink);
        }

        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('核心配置有误');
        }

        $this->assign('weike_jiaoyiqu', $weike_getCoreConfig['weike_indexcat']);
        $this->assign('indexLink', $indexLink);


        if (config('index_html')) {
           // config('index_html') 
            return $this->fetch('index/' . 'b'. '/index');
        } else {
            return $this->fetch();
        }
    }
    
    public function index_b_trends()
    {
        $ajax = input('ajax','json');
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
    
    private function allCoinPrice()
    {
        $data = Cache::store('redis')->get('allcoin');
        if(!$data){
            // 市场交易记录
            $marketLogs = array();
            foreach (config('market') as $k => $v) {
                $tradeLog = Db::name('TradeLog')->where(['status' => 1, 'market' => $k])->order('id desc')->paginate(50)->select();
                $_data = [];
                if ($tradeLog){
                    foreach ($tradeLog as $_k => $v) {
                        $_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                        $_data['tradelog'][$_k]['type'] = $v['type'];
                        $_data['tradelog'][$_k]['price'] = $v['price'] * 1;
                        $_data['tradelog'][$_k]['num'] = round($v['num'], 6);
                        $_data['tradelog'][$_k]['mum'] = round($v['mum'], 2);
                    }
                    $marketLogs[$k] = $_data;
                }
            }

            $themarketLogs = [];
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

            foreach (config('market') as $k => $v) {
                $data[$k][0] = $v['title'];
                $data[$k][1] = round($v['new_price'], $v['round']);
                $data[$k][2] = round($v['buy_price'], $v['round']);
                $data[$k][3] = round($v['sell_price'], $v['round']);
                $data[$k][4] = isset($themarketLogs[$k]) ? $themarketLogs[$k] : 0;//round($v['volume'] * $v['new_price'], 2) * 1;
                $data[$k][5] = '';
                $data[$k][6] = round($v['volume'], 2) * 1;
                $data[$k][7] = round($v['change'], 2);
                $data[$k][8] = $v['name'];
                $data[$k][9] = $v['xnbimg'];
                $data[$k][10] = '';
            }
            Cache::store('redis')->set('allcoin',$data);

        }

        return $data;
    }

    public function newPrice()
    {
        ini_set('display_errors', 'on');
        error_reporting(0);
        $data = $this->allCoinPrice();
        $last_data =Cache::store('redis')->get('ajax_all_coin_last');
        $_result = array();
        if (empty($last_data)) {
            foreach (config('market') as $k => $v) {
                $_result[$v['id'] . '-' . strtoupper($v['xnb'])] =  $data[$k][1] . '-0.0';
            }
        } else {
            foreach (config('market') as $k => $v) {
                $_result[$v['id'] . '-' . strtoupper($v['xnb'])] =  $data[$k][1] . '-' . ($data[$k][1] - $last_data[$k][1]);
            }
        }
        Cache::store('redis')->set('ajax_all_coin_last', $data);
        $data = json_encode(
            array(
                'result' => $_result,
            )
        );
        exit($data);
    }
}