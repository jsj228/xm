<?php
class Ajax_MarketController extends Ajax_BaseController
{

    public function klineAction()
    {
// print_r($_POST);die;
        if (!isset($_POST['type']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $type = $_POST['type'];

        $arr = array(
            '1week'  => '1w',
            '3day'   => '3d',
            '1day'   => '1d',
            '12hour' => '12h',
            '6hour'  => '6h',
            '4hour'  => '4h',
            '2hour'  => '2h',
            '1hour'  => '1h',
            '30min'  => '30m',
            '15min'  => '15m',
            '5min'   => '5m',
            '3min'   => '3m',
            '1min'   => '1m',
        );

        $type = $arr[$type];
        $name = $_POST['coin'] . 'tradeline';
        $j    = $name . '_' . $type;
        $json = Cache_Redis::instance('quote')->get($j);
        $this->ajax('', 1, json_decode($json));
    }

    # get depth
    public function depthsAction()
    {
        $coin = $_GET['depth'];;
        $json = Cache_Redis::instance('quote')->get($coin . '_depth');
        $this->ajax('', 1, json_decode($json));
    }


    /**
     * 所有币的最新行情
     */
    public function getAllQuoteAction()
    {
        $data  = Coin_PairModel::getInstance()->getCoinPrice();
        foreach($data as $key=>&$v)
        {
            $currency = Cache_Redis::instance()->get(strtolower($key).'_rmb_price');
            foreach($v as &$vv)
            {
                $vv['ratio'] = sprintf('%.2f', $vv['ratio']);
                $vv['amount']= sprintf('%.2f', $vv['amount']);
                $vv['price']= sprintf('%.8f', $vv['price']);
                $vv['money'] = Tool_Math::format($vv['money']);
                $vv['currency'] = $currency?sprintf('%.2f', $currency*$vv['price'], 2):'';
            }
        }
        $this->ajax('', 1, $data);
    }


    /**
     * 所有币的最新行情排序
     */
    public function getAllQuoteV2Action()
    {
        $data  = Coin_PairModel::getInstance()->getCoinPrice();
        $redis = Cache_Redis::instance();
        $cKey  = 'ALL_COINS_INFO';
        $coinsMap = json_decode($redis->get($cKey), true);

        //币信息缓存
        if(!$coinsMap)
        {
            $coins = User_CoinModel::getInstance()->field('name,display,logo')->fList();
            $coinsMap = array();
            foreach($coins as $v)
            {
                $v['logo'] = Yaf_Registry::get("config")->domain.$v['logo'];
                $coinsMap[$v['name']] = $v;
            }
            $redis->set($cKey, json_encode($coinsMap), 86400 * rand(5, 10));
        }
        $return = array();

        foreach($data as $key=>&$v)
        {
            $currency = Cache_Redis::instance()->get(strtolower($key).'_rmb_price');
            foreach($v as $kk=>&$vv)
            {
                $vv['ratio'] = sprintf('%.2f', $vv['ratio']);
                $vv['amount']= sprintf('%.2f', $vv['amount']);
                $vv['price']= sprintf('%.8f', $vv['price']);
                $vv['money'] = Tool_Math::format($vv['money']);
                $vv['currency'] = $currency?sprintf('%.2f', $currency*$vv['price'], 2):'';
                $vv['coin'] = $kk;
                list($coinFrom, $coinTo)=explode('_', $kk);
                $vv['coinurl']= $coinsMap[$coinFrom]['logo']?:'';
                $vv['display'] = $coinsMap[$coinFrom]['display']?:'';
                if($vv['type']==1)
                {
                    $return[$key][] = $vv;
                }
                elseif($vv['type']==2)
                {
                    $return['new'][] = $vv;
                }
                
            }
        }

        //交易区排序
        $areaSort = array('cnyx','new');
        $temp = array();
        foreach ($areaSort as $v) 
        {
            if(!$return[$v])
            {
                continue;
            }
            $temp[$v] = $return[$v];
        }
        $return = $temp;
        
        $this->ajax('', 1, $return);
    }



     /**
     * 币指数
     */
    public function getCoinIndexAction()
    {
        $cKey = 'coin_index';
        $cache = Cache_Redis::instance()->get($cKey);
        if(!$cache)
        {
            $data = file_get_contents('https://www.allcoin.com/Callback/GetCoinIndex/');
            Cache_Redis::instance()->set($cKey, $data, 30);
            $data = json_decode($data, true);
        }
        else
        {
            $data = json_decode($cache, true);
        }
        
        $this->ajax('', 1, $data);
    }

    public function mccAction()
    {
        if(!isset($_GET['fn']) || !preg_match('/^[a-z1-9_\$\.]+$/i', $_GET['fn']))
        {
            die('参数错误');
        }
        $rmbPrice = $this->getBtcRmbPrice();

        $data = json_decode(Cache_Redis::instance('quote')->get('mcc_btc_quote'), true);
        $data['rmb_price'] = round($rmbPrice*$data['price'], 2);
        $data = json_encode($data);
        header('Content-Type:application/json; charset=utf-8');
        exit($_GET['fn']. "(" . $data . ")");
    }


    private function getBtcRmbPrice()
    {
        $cKey = 'btc_rmb_price';
        $cache = Cache_Redis::instance()->get($cKey);
        if(!$cache)
        {
            $opts = array(
              'http'=>array(
                'method'=>"GET",
                'header'=>"Referer:https://cn.investing.com/currencies/btc-cny\r\n".
                "User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36\r\n".
                "X-Requested-With:XMLHttpRequest\r\n"
              )
            );

            $context = stream_context_create($opts);
            $url = 'https://cn.investing.com/common/modules/js_instrument_chart/api/data.php?pair_id=53078&pair_id_for_news=53078&chart_type=area&pair_interval=900&candle_count=20&events=yes&volume_series=yes';
            $json = json_decode(file_get_contents($url, false, $context), true);
            $json = $json['attr']['last_value'];
            Cache_Redis::instance()->set($cKey, json_encode($json), 30);
        }
        else
        {   
            $json = json_decode($cache);
        }
        return $json;
    }


     /**
     * 获取coin法币价格
     */
    public function coinPriceAction()
    {
        $coin = $_GET['coin'];
        if(!$_GET['coin'])
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }
        $cKey = $coin.'_usd_price';
        $cache = Cache_Redis::instance()->get($cKey);
        $cache and $data = json_decode($cache);  
        $this->ajax("", 1, $data);
    }
}
