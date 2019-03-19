<?php
/**
 * 交易中心
 */
class Api_QuoteController extends Api_BaseController
{
    function init()
    {
        if($this->_request->action!='quotes')
        {
            parent::init();
        }
    }
    /*
    * 单币行情
    */
    public function coinQuoteAction($coinPair)
    {
        if(!$coinPair)
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $coinFrom = $coinPair;
        $coinTo = 'btc';
        if(strpos($coinPair, '_'))
        {
            list($coinFrom, $coinTo) = explode('_', $coinPair);
        }
        else
        {
            $coinPair = $coinFrom .'_'. $coinTo;
        }

        $ciKey = 'COIN_INFO';
        $redis = Cache_Redis::instance();
        $coinInfo = json_decode($redis->hget($ciKey, strtolower($coinPair)), true);
        if(!$coinInfo)
        {
            $coinInfo = User_CoinModel::getInstance()->where(array('name'=>$coinFrom))->fRow();
            $redis->hset($ciKey, strtolower($coinPair), json_encode($coinInfo));
            $redis->expire($ciKey, 86400);
        }  

        if(!$coinInfo)
        {
            $this->ajax('Not provided');
        }

        $redis = Cache_Redis::instance('quote');

        $trust  = json_decode($redis->get($coinPair .'_sum'), true);
        $order  = json_decode($redis->get($coinPair . '_order'), true);
        $domain = Yaf_Registry::get("config")->dobidomain;
        
        if(!$order && $coinPair!='btc_btc')
        {
            $this->ajax('Not provided');
        }

        $rmbPrice = $this->getCoinRmbPrice(strtolower($coinTo));

        if($coinFrom=='btc')
        {
            $data = array(
                'buy_one'=>'',
                'sale_one'=>'',
                'now_price'=>'',
                'amount'=>'',
                'day_float'=>$redis->get('btc_usdt_ratio'),
                'max'=>'',
                'min'=>'',
                'total'=>'',
                'img'=>$domain.$coinInfo['logo'],
                'cn_name'=>$coinInfo['display'],
                'en_name'=>strtoupper($coinInfo['name']),
                'settlement'=>'BTC',
                'url'=>'',
                'total_money'=>'',
                'rmb_price'=>$rmbPrice,
            );
        }
        else
        {
            $data = array(
                'buy_one'=>$trust['buy']?current($trust['buy'])['p']:'',
                'sale_one'=>$trust['buy']?end($trust['sale'])['p']:'',
                'now_price'=>$order['price'],
                'amount'=>$order['money'],
                'day_float'=>$order['ratio'],
                'max'=>$order['max'],
                'min'=>$order['min'],
                'total'=>$order['sum'],
                'img'=>$domain.$coinInfo['logo'],
                'cn_name'=>$coinInfo['display'],
                'en_name'=>strtoupper($coinInfo['name']),
                'settlement'=>strtoupper($coinTo),
                'url'=>$domain.'trade/'. $coinPair,
                'total_money'=>$coinInfo['total'],
                'rmb_price'=>round($rmbPrice*$order['price'], 2),
            );
        }
        

        $this->ajax('', 1, $data);
    }


    /*
    * 所有币行情
    */
    public function allQuoteAction()
    {
        $domain = Yaf_Registry::get("config")->dobidomain;
        $all  = Coin_PairModel::getInstance()->getCoinPrice();

        $coins = User_CoinModel::getInstance()->field('name,display,logo')->fList();
        $coinsMap = array();
        foreach($coins as $v)
        {
            $coinsMap[$v['name']] = $v;
        }

        $data = array();
        foreach($all as $k=>$area)
        {
            $rmbPrice = $this->getCoinRmbPrice(strtolower($k));
            foreach($area as $coinPair=>$quote)
            {
                $coinName = str_replace('_'.$k, '', $coinPair);
                $data[$k][] = array(
                    'cn_name'=>$coinsMap[$coinName]['display'],
                    'en_name'=>strtoupper($coinName),
                    'volume'=>$quote['amount'],
                    'price'=>Tool_Math::format($quote['price']),
                    'money'=>Tool_Math::format($quote['money']),
                    'ratio'=>($quote['ratio']?:0).'%',
                    'url'=>$domain.'trade/'.$coinPair,
                    'img'=>$domain.$coinsMap[$coinName]['logo'],
                    'settlement'=>strtoupper($k),
                    'rmb_price'=>round($rmbPrice*$quote['price'], 2),
                );
            }
            
        }
        $this->ajax('', 1, $data);
    }

    /*
    * btc兑换人民币
    */
    public function btc2rmbAction()
    {
        $json = $this->getBtcRmbPrice();
        $this->ajax("", 1, $json);
    }


    private function getBtcRmbPrice()
    {
        $cKey = 'btc_rmb_price';
        $cache = Cache_Redis::instance()->get($cKey);
        $json = json_decode($cache);     
        return $json;
    }


    private function getCoinRmbPrice($coin)
    {
        $cKey = $coin . '_rmb_price';
        $cache = Cache_Redis::instance()->get($cKey);
        $json = json_decode($cache);     
        return $json;
    }


    /*
    * 历史行情
    */
    public function lineAction($coin, $span='1h')
    {
        //btc暂时特殊处理
        if($coin=='btc')
        {
            $returnData = $this->getBtcLine('rmb', $span);
            $this->ajax("", 1, $returnData);
        }

        $cKey = strtolower($coin) . '_btctradeline_' . $span;
        $cache = Cache_Redis::instance('quote')->get($cKey);
        if(!$cache && $span!='1h')
        {
            $cKey = strtolower($coin) . '_btctradeline_1h';
            $cache = Cache_Redis::instance('quote')->get($cKey);
        }

        $cache = json_decode($cache, true);

        $returnData = array();
        if($cache)
        {   
            $rmbPrice = $this->getBtcRmbPrice();
            if(stripos($span, 'h'))
            {
                $spanNum = str_replace('h', '', $span);
                $todayBegin = false;
                $lastest = array_slice($cache['datas']['data'], -24);
                $startTime = strtotime('today');
                foreach ($lastest as $v) 
                {
                    // if($v[0]/1000==$startTime)
                    //     $todayBegin = true;
                    if((($v[0]/1000-$startTime)/3600)%$spanNum==0)
                        $returnData[] = [$v[0], round($rmbPrice*$v[4], 2)];
                }
            }
            
        }
        
        $this->ajax("", 1, $returnData);
    }


    private function getBtcLine($c='rmb', $span='1h')
    {
        $cKey = 'btc_rmb_1h_24h';
        $cache = Cache_Redis::instance('quote')->hGetAll($cKey);
        
        ksort($cache);
        $cache = array_slice($cache, -24, 24, true);
        $returnData = array();
        if(stripos($span, 'h'))
        {
            $startTime = strtotime('today');
            $spanNum = str_replace('h', '', $span);
            foreach ($cache as $k=>$v) 
            {
                if((($k-$startTime)/3600)%$spanNum==0)
                    $returnData[] = [$k*1000, round($v, 2)];
            }
        }

        return $returnData;
    }


     /*
    * 最新行情(全部)
    */
    public function quotesAction()
    {
        $domain = Yaf_Registry::get("config")->dobidomain;
        $all  = Coin_PairModel::getAllCoinPrice();

        $redis = Cache_Redis::instance();
        $cKey  = 'ALL_COINS_INFO';
        $coinsMap = json_decode($redis->get($cKey), true);

        //币信息缓存
        if(!$coinsMap)
        {
            $coins = User_CoinModel::getInstance()->field('`name`,`asset_name`,`logo`')->fList();
            $coinsMap = array();
            foreach($coins as $v)
            {
                $coinsMap[$v['name']] = $v;
            }
            $redis->set($cKey, json_encode($coinsMap), 86400 * rand(5, 10));
        }
        

        $data = array();
        foreach($all as $k=>$area)
        {
            foreach($area as $coinPair=>$quote)
            {
                $coinName = str_replace('_'.$k, '', $coinPair);
                $data[] = array(
                    'lastPrice'=>$quote['price'],
                    'volume'=>$quote['amount'],
                    'high'=>$quote['max'],
                    'low'=>$quote['min'],
                    'amount'=>$quote['money'],
                    'ratio'=>$quote['ratio'] .'%',
                    'tradeUrl'=>$domain.'trade/'.$coinPair,
                    'baseAssetLogo'=>$coinsMap[$coinName]['logo']?$domain.$coinsMap[$coinName]['logo']:'',
                    'baseAsset'=>strtoupper($coinName),
                    'baseAssetName'=>$coinsMap[$coinName]['asset_name'],
                    'quoteAsset'=>strtoupper($k),
                    'quoteAssetName'=>$coinsMap[$k]['asset_name'],  
                );
            }
            
        }

        $this->ajax('', 1, $data);
    }

}
