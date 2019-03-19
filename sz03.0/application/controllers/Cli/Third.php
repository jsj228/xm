<?php
/**
 *第三方接口数据缓存
 */
class Cli_ThirdController extends Ctrl_Cli
{
    //缓存时长
    protected $expiry = 864000;
    //缓存key名(独立处理function名)  =>  url
    protected $keyUrlMap = array(
        'coin_index'=>array(//币指数
            'url'=>'https://www.allcoin.com/Callback/GetCoinIndex/'
        ),
        'btc_rmb_price'=>array(//比特币兑人民币价格
            'url'=>'https://www.bitstamp.net/api/v2/ticker/btcusd/',
        ),
        'eth_rmb_price'=>array(//eth兑人民币价格
            'url'=>'https://www.bitstamp.net/api/v2/ticker/ethusd/',
        ),
        'ebtcotc_price'=>array(//易币价格
            'url'=>'https://otc.huocoin.com/ajax_market/getAllQuote',
        ),
        'coin_branch_sbtc'=>array(//分叉币是否分叉
            'url'=>'https://blockchain.info/block-height/500944'
        ),
        'coin_branch_lbtc'=>array(//分叉币是否分叉
            'url'=>'https://blockchain.info/block-height/50000'
        ),

    );

    public function runAction($key)
    {
    	$this->$key();
    	exit;	
    }

    protected function request($req)
    {

        $opts = array(
          'http'=>array(
            'method'=>isset($req['method'])?$req['method']:'GET',
            'header'=>"Accept-language: en\r\n" .
                      "Cookie: foo=bar\r\n".
                      "Content-Type:application/x-www-form-urlencoded\r\n".
                      "User-Agent:".$this->getRandomUA()."\r\n",
          )
        );

        if(isset($req['content']))
        {
            $opts['http']['content'] = http_build_query($req['content']);
        }

        if(isset($req['header_add']))
        {
            $opts['http']['header'] .= $req['header_add'];
        }
        elseif(isset($req['header']))
        {
            $opts['http']['header'] = $req['header'];
        }
        $context = stream_context_create($opts);
        return file_get_contents($req['url'], false, $context);
    }

    protected function btc_rmb_price()
    {
        while (true) 
        {
            $data = $this->request($this->keyUrlMap['btc_rmb_price']);
            if($data)
            {
                $usdRate = file_get_contents('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b');
                $usdRate = iconv("GBK", "UTF-8//IGNORE", $usdRate);
                $usdRate = preg_replace('/.+?美元.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate)/100;
                if(is_numeric($usdRate) && $usdRate>0)
                {
                    $repObj = json_decode($data, true);
                    $data = round($repObj['last']*$usdRate, 2);
                    if(is_numeric($data))
                    {
                        $result = Cache_Redis::instance()->set('btc_rmb_price', $data, $this->expiry);
                        $result = Cache_Redis::instance()->set('btc_usd_price', $repObj['last'], $this->expiry);
                        $result = Cache_Redis::instance()->set('dob_usd_price', 1/$usdRate, $this->expiry);
                        $btcRmb1H24H = 'btc_rmb_1h_24h';
                        if(time()<=strtotime(date('Y-m-d 01:00:00')))
                        {
                            $oldData = Cache_Redis::instance('quote')->hGetAll($btcRmb1H24H);
                            ksort($oldData);
                            $oldData = array_slice($oldData, -24, 24, true);
                            Cache_Redis::instance('quote')->del($btcRmb1H24H);
                            Cache_Redis::instance('quote')->hmset($btcRmb1H24H, $oldData);
                        }
                        Cache_Redis::instance('quote')->hset($btcRmb1H24H, strtotime(date('Y-m-d H:00:00')).'', $data);
                        Cache_Redis::instance('quote')->set('btc_usdt_ratio', round(($repObj['last']/$repObj['open']-1)*100, 2), $this->expiry);
                    } 
                } 
            }
            sleep(60);
        }
        exit;
    }


    protected function eth_rmb_price()
    {
        while (true) 
        {
            $data = $this->request($this->keyUrlMap['eth_rmb_price']);
            if($data)
            {
                $usdRate = file_get_contents('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b');
                $usdRate = iconv("GBK", "UTF-8//IGNORE", $usdRate);
                $usdRate = preg_replace('/.+?美元.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate)/100;
                if(is_numeric($usdRate) && $usdRate>0)
                {
                    $data = json_decode($data, true);
                    if(is_numeric($data['last']))
                    {
                        $result = Cache_Redis::instance()->set('eth_usd_price', $data['last'], $this->expiry);
                    }
                    $data = round($data['last']*$usdRate, 2);
                    if(is_numeric($data))
                    { 
                        $result = Cache_Redis::instance()->set('eth_rmb_price', $data, $this->expiry);
                    } 
                } 
            }
            sleep(rand(10, 60));
        }
        exit;
    }
 
    protected function ebtcotc_price()
    {
        while (true) 
        {
            $data = $this->request($this->keyUrlMap['ebtcotc_price']);
            if($data)
            {
                $data = json_decode(preg_replace('/^.*?\{/','{', $data), true);
                if($data['data'])
                {
                    foreach($data['data'] as $coin=>$info)
                    {
                        $coin = strtolower($coin);
                        if(!in_array($coin, ['btc','eth']))
                        {
                            $result = Cache_Redis::instance()->set($coin.'_rmb_price', $info['price'], $this->expiry);
                        }
                    }
                }
                
            }
            sleep(rand(1, 3));
        }
    }

    protected function coin_branch_sbtc()
    {
        while (true) 
        {
            $cKey  = 'coin_branch';
            $secKey = 'coin_branch_sbtc';
            $cache = Cache_Redis::instance()->hGet($cKey, $secKey);
            if($cache)
            {
                exit;
            }
            //缓存没有就去区块浏览器查
            else
            {
                $rep = $this->request($this->keyUrlMap['coin_branch_sbtc']);
                //正则匹配
                preg_match('/(?<date>\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2})/i', $rep, $match);
                $date = $match['date'];
                Cache_Redis::instance()->hSet($cKey, $secKey, json_encode(array('date'=>$date)));
            }
            sleep(2);
        }
    }

    protected function coin_branch_lbtc()
    {
        while (true) 
        {
            $cKey  = 'coin_branch';
            $secKey = 'coin_branch_lbtc';
            $cache = Cache_Redis::instance()->hGet($cKey, $secKey);
            if($cache)
            {
                exit;
            }
            //缓存没有就去区块浏览器查
            else
            {
                $rep = $this->request($this->keyUrlMap['coin_branch_lbtc']);
                if(!$rep || preg_match('/(?<date>\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2})/i', $rep, $match))
                {
                    $this->warning(sprintf('coin_branch_lbtc异常, data:%s', $rep));
                    continue;
                }
                $date = $match['date'];
                Cache_Redis::instance()->hSet($cKey, $secKey, json_encode(array('date'=>$date)));
            }
            sleep(2);
        }
    }

    /*
     * 随机UA
     */
    protected function getRandomUA()
    {
        $ua = [
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1',
        ];
        return $ua[rand(0, count($ua)-1)];
    }


    function __call($method, $args)
    {
        while (true) 
        {
            $data = $this->request($this->keyUrlMap[$method]);
            if($data)
            {
                Cache_Redis::instance()->set($method, $data, $this->expiry);
            }
            sleep(rand(1, 3));
        }
        exit;
    }

}