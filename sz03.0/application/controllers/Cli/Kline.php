<?php
/**
 * Kline
 *
 */
ini_set ('memory_limit', '2046M');
class Cli_KlineController extends Ctrl_Cli
{
    # Run
    public function runAction($type = '5m')
    {

        $db = new Orm_Base();
        // 获取要生成的币K线图
        $cDatas = $db->query("select * from coin_pair where status=1");
        foreach ($cDatas as $v)
        {
            $coin_from = $v['coin_from'];
            $coin_to   = $v['coin_to'];
            $coin      = $v['name'];
            $k         = Cache_Redis::instance('quote')->get("{$coin}tradeline_{$type}_cache");
            // 判断缓存中是否有记录
            if (!empty($k))
            {
                // 有,追加数据
                $cacheKeys = array_keys(json_decode($k, true));
                $time = end($cacheKeys);

            }
            else
            {
                // 没有,重新获取.重新获取开始时间
                $time = 0;
            }
            // 获取交易流水
            $table = 'order_' . $coin_from . 'coin'; //chen
            $data  = $db->query("select * from $table where created>={$time} and coin_from='{$coin_from}' and coin_to='{$coin_to}' and opt=1");

            $tOrders = array();
            // 格式化交易流水信息
            foreach ($data as $v1)
            {
                $tTime = strtotime($this->ctime($v1['created'], $type));
                if (time() < $tTime)
                {
                    break;
                }
                // format $tOrders['unix_time'] = ['unix_time','成交量','open价格','high价格','low价格','close价格'];
                if (empty($tOrders[$tTime]))
                {
                    # open
                    $tOrders[$tTime] = array(($tTime) . '000', $v1['price'], $v1['price'], $v1['price'], $v1['price'], $v1['number']);
                }
                else
                {
                    $tOrders[$tTime][5] += $v1['number'];
                    # high
                    $v1['price'] > $tOrders[$tTime][2] && $tOrders[$tTime][2] = $v1['price'];
                    # low
                    $v1['price'] < $tOrders[$tTime][3] && $tOrders[$tTime][3] = $v1['price'];
                    # close
                    $tOrders[$tTime][4] = $v1['price'];
                }
            }

            if(substr($type,-1)=='m')
            {
                $timeout=substr($type,0,-1)*60;
            }
            else if(substr($type,-1)=='h')
            {
                $timeout=substr($type,0,-1)*3600;
            }
            else if(substr($type,-1)=='d')
            {
                $timeout=substr($type,0,-1)*86400;
            }
            // K线数据
            $kArray = json_decode($k, true);
            //如果$type没有成交数据，则存上一笔的成交记录，数量为0
            if(empty($data))
            {
                if(time()>end($kArray)[0]/1000+$timeout) {
                    $tOrders[end($kArray)[0] / 1000 + $timeout] = array(
                        0 => (end($kArray)[0] / 1000 + $timeout) . '000',
                        1 => end($kArray)[2],
                        2 => end($kArray)[2],
                        3 => end($kArray)[2],
                        4 => end($kArray)[2],
                        5 => '0'
                    );
                }
            }
            
            // 判断是追加数据还是重新生成
            $tOrders = is_array($kArray) ? ($kArray + $tOrders) : $tOrders;
            ksort($tOrders);
            // 截取最后80条数据
            $tOrders = array_slice($tOrders, -1000, 1000, true);
            if (!empty($tOrders))
            {
                // 插入到缓存中去
                Cache_Redis::instance('quote')->set("{$coin}tradeline_{$type}_cache", json_encode($tOrders));
            }
            $tJS = array();
            foreach ($tOrders as $v1)
            {
                $tJS[]   = '[' . implode(',', $v1) . ']';
                $mstJS[] = array('time' => $v1[0], 'open' => $v1[1], 'high' => $v1[2], 'low' => $v1[3], 'close' => $v1[4], 'num' => $v1[5]);
            }

            Cache_Redis::instance('quote')->set("{$coin}tradeline_{$type}", '{"des" : "" , "isSuc" : true  , "datas" : {"USDCNY":6.5746,"contractUnit":"' . strtoupper($coin_from) . '","data" :[' . trim(implode(',', $tJS), ',') . ']}, "marketName": "QQIBTC", "moneyType": "' . $coin_to . '", "symbol": "' . $coin . '", "url": "https://www.huocoin.com"}');
            Cache_Redis::instance('quote')->set("{$coin}_btctradeline_{$type}", '{"des" : "" , "isSuc" : true  , "datas" : {"USDCNY":6.5746,"contractUnit":"' . strtoupper($coin_from) . '","data" :[' . trim(implode(',', $tJS), ',') . ']}, "marketName": "QQIBTC", "moneyType": "' . $coin_to . '", "symbol": "' . $coin . '", "url": "https://www.huocoin.com"}');
        }
        exit($type);
    }

    //判断时间的范围 确定时间
    private function ctime($time, $min = "30m")
    {
        switch ($min)
        {
            case '1m':
                $tTime = date('Y-m-d H:i:00', strtotime('+1 minute', $time));
                break;
            case '3m':
            case '5m':
            case '15m':
            case '30m':
                $in = intval($min);
                $i  = date('i', $time);
                $c  = $i - $i % $in + $in;
                if ($c == 60)
                {
                    $tTime = date('Y-m-d H:i:00', strtotime('+1 hour', strtotime(date('Y-m-d H:00:00', $time))));
                }
                else
                {
                    if ($c < 10)
                    {
                        $c = '0' . $c;
                    }
                    $tTime = date('Y-m-d H', $time) . ':' . $c . ':00';
                }
                break;
            case '1h':
            case '2h':
            case '4h':
            case '6h':
            case '12h':
                $in = intval($min);
                $h  = date('H', $time);
                $c  = $h - $h % $in + $in;
                if ($c == 24)
                {
                    $tTime = date('Y-m-d 00:00:00', strtotime('+1 day', strtotime(date('Y-m-d', $time))));
                }
                else
                {
                    if ($c < 10)
                    {
                        $c = '0' . $c;
                    }
                    $tTime = date('Y-m-d', $time) . ' ' . $c . ':00:00';
                }
                break;
            case '1d':
                $c     = date('Y-m-d', $time);
                $tTime = date('Y-m-d 00:00:00', strtotime('+1 day', strtotime($c)));
                break;
        }
        return $tTime;
    }

    public function clearAction($type = '5m')
    {
        $mem = Cache_Redis::instance('quote');
        $db  = new Orm_Base();
        // 获取要生成的币K线图
        $cDatas = $db->query("select * from coin_pair where status=1");
        foreach ($cDatas as $v)
        {
            $coin = $v['name'];
            $mem->del("{$coin}tradeline_{$type}_cache");
        }

        exit($type);

    }

    public function depthsAction()
    {
        $db     = new Orm_Base();
        $cDatas = $db->query("select * from coin_pair where status=1");
        foreach ($cDatas as $v1)
        {
            $coin      = $v1['name'];
            $table     = 'trust_' . $v1['coin_from'] . 'coin'; //chen
            $asks      = '{"asks":[';
            $sale_data = $db->query("select price, sum(numberover) number from $table where coin_from = '{$v1['coin_from']}' and coin_to = '{$v1['coin_to']}' and status in(0, 1) and flag = 'sale' group by price order by price limit 100");
            $count     = count($sale_data) - 1;
            for ($i = $count; $i >= 0; $i--)
            {
                $asks .= "[{$sale_data[$i]['price']}, {$sale_data[$i]['number']}],";
            }
            $asks = rtrim($asks, ',');
            $asks .= ']';

            $bids     = '"bids":[';
            $buy_data = $db->query("select price, sum(numberover) number from $table where coin_from = '{$v1['coin_from']}' and coin_to = '{$v1['coin_to']}' and status in(0, 1) and flag = 'buy' group by price order by price desc limit 100");
            foreach ($buy_data as $v3)
            {
                $bids .= "[{$v3['price']}, {$v3['number']}],";
            }
            $bids = rtrim($bids, ',');
            $bids .= '],"date":' . time() . '}';

            Cache_Redis::instance('quote')->set("{$coin}_depth", $asks . ',' . $bids);
        }
        exit('success');
    }

}
