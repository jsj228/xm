<?php
class Coin_PairModel extends Orm_Base
{
    public $table = 'coin_pair';
    public $field = array(
        'id'           => array('type' => "int", 'comment' => 'id'),
        'name'         => array('type' => "char", 'comment' => 'B名称'),
        'coin_from'    => array('type' => "char", 'comment' => ''),
        'coin_to'      => array('type' => "char", 'comment' => ''),
        'describe'     => array('type' => "char", 'comment' => '描述'),
        'display'      => array('type' => "char", 'comment' => '显示名字'),
        'display_to'   => array('type' => "char", 'comment' => '显示名字'),
        'status'       => array('type' => "int", 'comment' => ''),
        'rate'         => array('type' => "float", 'comment' => ''),
        'rate_buy'     => array('type' => "float", 'comment' => ''),
        'url'          => array('type' => "char", 'comment' => ''),
        'price_float'  => array('type' => "int", 'comment' => ''),
        'number_float' => array('type' => "int", 'comment' => ''),
        'order_by'     => array('type' => "int", 'comment' => ''),
        'price_limit'  => array('type' => "int", 'comment' => ''),
        'up_percent'   => array('type' => "char", 'comment' => ''),
        'down_percent' => array('type' => "char", 'comment' => ''),
        'rule_open'    => array('type' => "int", 'comment' => ''),
        'open_start'   => array('type' => "int", 'comment' => ''),
        'open_end'     => array('type' => "int", 'comment' => ''),
        'open_date'    => array('type' => 'char', 'comment' => '闭市日期'),
        'min_trade'    => array('type' => "int", 'comment' => '每笔最小交易数量'),
        'max_trade'    => array('type' => "int", 'comment' => '每笔最大交易数量'),
        'type'    => array('type' => "tinyint", 'comment' => '1普通区，2创新区'),
    );

    public $pk = 'id'; // 主键

    const STATUS_ON  = 1;
    const STATUS_OFF = 2;

    //交易区
    static $tradingArea = array('btc');
    /**
     * 当前已上线币列表
     */
    public function getList()
    {
        return $this->where("status=" . self::STATUS_ON)->order("order_by asc,id asc")->fList();
    }
    /**
     * 币信息
     * @param $pName
     * @return array
     */
    public static function getByName($name)
    {
        if (!$coin = Coin_PairModel::getInstance()->ffName($name))
        {
            return array();
        }
        return $coin;
    }
    public static function getAllCoinName()
    {
        if (!$list = Coin_PairModel::getInstance()->field('id,name,display')->fList())
        {
            return array();
        }
        $res = array();
        foreach ($list as $k1 => $v1)
        {
            $res[$v1['id']] = $v1['display'];
        }
        return $res;
    }
    /**
     * 判断当前币对是否存在
     */
    public function isPair($name)
    {
        return $this->where(array('name'=>$name,'status' => 1))->count();
    }
    /**
     * 币对信息
     */
    public function getPair($name)
    {
        return $this->where(array('name'=>$name))->fRow();
    }

    /**
     * 获取币种最新成交价、24小时成交量/价、总市值等信息
     */
    public function getCoInfo($coin)
    {
        $caArr = json_decode(Cache_Memcache::get($coin . '_sum'), true);
        if ($caArr)
        {
            foreach ($caArr as $k => $v)
            {
                if ($v)
                {
                    if ($v[0]['p'])
                    {
                        $arr[$k . '_one'] = $v[0]['p'];
                    }
                    else
                    {
                        $arr[$k . '_one'] = 0;
                    }
                }
                else
                {
                    $arr[$k . '_one'] = 0;
                }
            }

            $coinArr             = explode('_', $coin);
            $pair                = Coin_PairModel::getByName($coin);
            $arr['price_float']  = $pair['price_float'];
            $arr['number_float'] = $pair['number_float'];

            // 最新成交价
            $orderArr = json_decode(Cache_Memcache::get($coin . "_order"), true);
            //$arr['now_price'] = $orderArr['d'][0]['p'] ? $orderArr['d'][0]['p'] : 0;
            $arr['now_price'] = $orderArr['d'][0]['p'] ? $orderArr['d'][0]['p'] : 0;


            // 24H成交量和成交额
            $nMO      = new Order_CoinModel();
            $coin_arr = explode('_', $coin);
            $table    = 'order_' . $coin_arr[0] . 'coin';

	    $orderCoinMo = Order_CoinModel::getInstance();
            $orderCoinMo->designTable($coin_arr[0]);

            //最新一笔交易
            $lastOrder = $orderCoinMo->field('created')->where("coin_from='{$pair['coin_from']}'")->order('id desc')->limit(1)->fRow();

            $time     = time() - ($lastOrder['created']<strtotime('today')?2*86400:86400);
		// $numberArr = $nMO->query("select sum(number) number, sum(number*price) money from {$table} where coin_from='{$pair['coin_from']}' and opt=1 and created >= {$time}");
            $numberArr        = $nMO->query("select sum(number) number, sum(number*price) money from {$table} where coin_from='{$pair['coin_from']}' and opt=1 and created >= {$time}");
            $arr['day_total'] = round($numberArr[0]['number'], $pair['number_float']);
            $arr['day_money'] = round($numberArr[0]['money'], $pair['price_float']);

            // 总市值
            $total = User_CoinModel::getByName($coinArr[0]);
            // $arr['total_money'] = round( ($arr['now_price'] * $total['total']) / 1E+8 , $pair['price_float']);
            $arr['total_money'] = $arr['now_price'] * $total['total'];

            // 日涨跌，周涨跌

            //$last_end = Coin_PairModel::getInstance()->where("status = 1 and name = '{$coin}'")->fOne('open_end');
            if (intval(date('Hi')) >= $open_end)
            {
                $period   = date('Ymd', strtotime('-1 day'));
                $period_7 = date('Ymd', strtotime('-7 day'));
            }
            else
            {

                $period   = date('Ymd', strtotime('-2 day'));
                $period_7 = date('Ymd', strtotime('-8 day'));
            }
$orderCoinMo = Order_CoinModel::getInstance();
            $orderCoinMo->designTable($coin_arr[0]);

            //非24小时交易
            if($pair['rule_open'])
            {
                $prevCreated = strtotime(date('Ymd', $lastOrder['created']));
                $prevOrder   = $orderCoinMo->field('created,price')->where("coin_from='{$pair['coin_from']}' and created<{$prevCreated}")->order('id desc')->limit(1)->fRow();

                $close = $prevOrder['price'];
            }
            else
            {
                //24小时前价格
                $prevCreated = intval($lastOrder['created']) - 86400;
                $prevOrder   = $orderCoinMo->field('created,price')->where("coin_from='{$pair['coin_from']}' and created>={$prevCreated}")->order('id')->limit(1)->fRow();

                $close = $prevOrder['price'];
            }


		$orderCoinMo = Order_CoinModel::getInstance();
            $orderCoinMo->designTable($coin_arr[0]);
            //最新一笔交易
            $lastOrder = $orderCoinMo->field('created')->where("coin_from='{$pair['coin_from']}'")->order('id desc')->limit(1)->fRow();

            // 昨日收盘价
            //$close = Coin_FloatModel::getInstance()->where("coin_from='{$pair['coin_from']}' and day = {$period}")->fOne('price_close');
            // 一周前收盘价
            $close_7 = Coin_FloatModel::getInstance()->where("coin_from='{$pair['coin_from']}' and day = {$period_7}")->fOne('price_close');
            if (!$close_7)
            {
                $close_7 = Coin_FloatModel::getInstance()->where("coin_from='{$pair['coin_from']}'")->order('id asc')->limit(1)->fOne('price_close');
            }

            if ($arr['now_price'] && $close)
            {
                $arr['day_float'] = round(($arr['now_price'] - $close) / $close, 4);
            }
            else
            {
                $arr['day_float'] = 0;
            }

            if ($arr['now_price'] && $close_7)
            {
                $arr['week_float'] = round(($arr['now_price'] - $close_7) / $close_7, 4);
            }
            else
            {
                $arr['week_float'] = 0;
            }
            //show($arr);
            return json_encode($arr);
        }
    }

    /**
     * 所有币种的当前价格
     */
    public function getCoinPrice($sort = true)
    {
        $redis = Cache_Redis::instance();
        $cKey = 'ACTIVE_COIN_PAIR';
        $pairs = json_decode($redis->get($cKey), true);
        if(!$pairs)
        {
            $pairs = $this->field('name,coin_to,order_by sort,type')->where(['status'=>Coin_PairModel::STATUS_ON])->getList();
            $redis->set($cKey, json_encode($pairs), 86400 * rand(5, 10));
        }

        $allQuotes = Cache_Redis::instance('quote')->hGetAll('all_quotes');

        $priceArr = array();
        foreach ($pairs as $k => $v) {
            if (isset($allQuotes[$v['name']])) {
                $priceArr[$v['coin_to']][$v['name']] = json_decode($allQuotes[$v['name']], true);
                $priceArr[$v['coin_to']][$v['name']]['sort'] = $v['sort'];
                $priceArr[$v['coin_to']][$v['name']]['type'] = $v['type'];
            }
        }
//        return $priceArr;
        if($sort)
        {
            foreach ($priceArr as $key => &$area )
            {
                array_multisort(array_column($area, 'sort'), SORT_ASC, array_column($area, 'money'), SORT_DESC, $area);
            }
            unset($area);
        }

        return $priceArr;
    }



    public static function getAllCoinPrice($sort=true)
    {
        $redis = Cache_Redis::instance();
        $cKey  = 'ACTIVE_COIN_PAIR';
        $pairs = json_decode($redis->get($cKey), true);
        if(!$pairs)
        {
            $pairs = self::getInstance()->field('name,coin_to,order_by sort,type')->where(['status'=>Coin_PairModel::STATUS_ON])->getList();
            $redis->set($cKey, json_encode($pairs), 86400 * rand(5, 10));
        }
        $allQuotes =  Cache_Redis::instance('quote')->hGetAll('all_quotes');


        $priceArr = array();
        foreach ($pairs as $k => $v)
        {
            if(isset($allQuotes[$v['name']]))
            {
                $priceArr[$v['coin_to']][$v['name']] = json_decode($allQuotes[$v['name']], true);
                $priceArr[$v['coin_to']][$v['name']]['sort'] = $v['sort'];
                $priceArr[$v['coin_to']][$v['name']]['type'] = $v['type'];
            }
        }

        if ($sort) {
            foreach ($priceArr as $key => &$area) {
                array_multisort(array_column($area, 'sort'), SORT_ASC, array_column($area, 'money'), SORT_DESC, $area);
            }
            unset($area);
        }

        return $priceArr;
    }
}
