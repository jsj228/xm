<?php
/**
 * 市场行情
 */
class Api_MarketController extends Api_BaseController
{
    /*
    * 最新行情
    */
    public function quote()
    {
        if(!isset($_GET['market']) || !preg_match('/^[a-z\d]+_[a-z\d]+$/i', $_GET['market']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }
        $redis = Cache_Redis::instance('quote');
        $quote = json_decode($redis->get($_GET['market'] . '_quote'), true);
        $trust  = json_decode($redis->get($_GET['market'] .'_sum'), true);
        $data = array(
            'last'=>$quote['price'],
            'ratio'=>$quote['ratio'] .'%',
            'eq'=>array('cny'=>$quote['currency'], 'usd'=>$quote['usd']),
            'high'=>$quote['max'],
            'low'=>$quote['min'],
            'buy'=>$trust['buy']?current($trust['buy'])['p']:'',
            'sale'=>$trust['sale']?current($trust['sale'])['p']:'',
            'sell'=>$trust['sale']?current($trust['sale'])['p']:'',
            'volume'=>$quote['amount'],
            'amount'=>$quote['money'],
        );
        $this->ajax('', 1, $data);
    }



    /*
    *  市场挂单
    */
    public function orderbook()
    {
        if(!isset($_GET['market']) || !preg_match('/^[a-z\d]+_[a-z\d]+$/i', $_GET['market']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $num = 20;
        if(isset($_GET['limit']))
        {
            $num = min(max($_GET['limit'], 1), 200);
        }

        $redis = Cache_Redis::instance('quote');
        $data  = $redis->get($_GET['market'] . '_orderbook_200');

        if ($data && $data = json_decode($data, true))
        {
            $data['buy'] = array_values($data['buy']);
            $data['buy'] = array_slice($data['buy'], 0, $num);
            $data['sale'] = array_values($data['sale']);  
            $data['sale'] = array_slice($data['sale'], 0, $num);   
        }
        else
        {
            $data = array('buy'=>[], 'sale'=>[]);
        }

        $data = array('bids'=>$data['buy'], 'asks'=>$data['sale']);

        $this->ajax('', 1, $data);
    }


    /*
    * kline
    */
    public function kline()
    {
        if (!isset($_GET['type'], $_GET['market']) || !preg_match('/^[a-z\d]+_[a-z\d]+$/i', $_GET['market']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $type = $_GET['type'];
        $maxSize = 1000;
        $size = isset($_GET['limit'])?intval($_GET['limit']):$maxSize;
        $timestamp = isset($_GET['timestamp'])?intval($_GET['timestamp']):0;

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
        $name = $_GET['market'] . 'tradeline';
        $j    = $name . '_' . $type;
        $data = Cache_Redis::instance('quote')->get($j);


        //返回大于timestamp的数据
        if($timestamp)
        {
            if(strlen($timestamp)>10)
            {
                $timestamp = substr($timestamp, 0, 10);
            }

            $data = json_decode($data, true);
            //格式化timestamp，匹配索引
            if(strpos($type, 'm'))
            {
                $timestamp = strtotime(date('Y-m-d H:i', $timestamp));
            }
            elseif(strpos($type, 'h'))
            {
                $timestamp = strtotime(date('Y-m-d H:00', $timestamp));
            }
            elseif(strpos($type, 'd'))
            {
                $timestamp = strtotime(date('Y-m-d', $timestamp));
            }

            $timestamp = $timestamp . '000';

            //如果时间戳参数小于数据库存储的最小时间戳，则返回全部数据
            if(isset($data['datas']['data'][0]) && $data['datas']['data'][0][0]>=$timestamp)
            {
                $idx = 0;
            }
            else
            {

                $timestampList = array_column($data['datas']['data'], 0);
                $idx = array_search($timestamp, $timestampList);

                //可能因为数据库漏点匹配不到数据，就近原则
                if($idx === false)
                {
                    foreach($timestampList as $k=>$v)
                    {
                        if($v>$timestamp)
                        {
                            $idx = $k-1;
                            break;
                        }
                    }
                }
            }

            
            if($idx !== false)
            {
                $data['datas']['data'] = array_slice($data['datas']['data'], $idx, $size);
                array_shift($data['datas']['data']);
            }
            else
            {
                $data['datas']['data'] = [];
            }
        }
        elseif($size && $size<=$maxSize)
        {
            $data = json_decode($data, true);
            $data['datas']['data'] = array_splice($data['datas']['data'], -$size);
        }
       
        $this->ajax('', 1, $data['datas']['data']);
    }


    /*
    *  市场成交
    */
    public function trades()
    {
        if(!isset($_GET['market']) || !preg_match('/^[a-z\d]+_[a-z\d]+$/i', $_GET['market']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $num = 20;
        if(isset($_GET['limit']))
        {
            $num = min(max($_GET['limit'], 1), 50);
        }

        $redis = Cache_Redis::instance('quote');
        $data  = $redis->get($_GET['market'] . '_trades');
        $data  = json_decode($data, true);
        $data  = array_slice($data, 0, $num);

        $this->ajax('', 1, $data);
    }

}
