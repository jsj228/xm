<?php
class Order_CoinModel extends Orm_Base
{
    public $table = 'order_coin';
    public $field = array(
        'id'        => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'price'     => array('type' => "decimal(20,8) unsigned", 'comment' => '单价'),
        'number'    => array('type' => "decimal(20,8) unsigned", 'comment' => '数量'),
        'buy_tid'   => array('type' => "int(11) unsigned", 'comment' => '购买委托id'),
        'buy_uid'   => array('type' => "int(11) unsigned", 'comment' => '购买用户id'),
        'sale_tid'  => array('type' => "int(11) unsigned", 'comment' => '出售委托id'),
        'sale_uid'  => array('type' => "int(11) unsigned", 'comment' => '出售用户id'),
        'created'   => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'opt'       => array('type' => "int", 'comment' => '0其他，1ybc交易，2ybc提现，3交易费，4融资风险金，5rmb提现'),
        'coin_from' => array('type' => "char", 'comment' => '要兑换的币'),
        'coin_to'   => array('type' => "char", 'comment' => '目标兑换'),
    );
    public $pk = 'id';

    const OPT_TRADE   = 1; #交易;
    const OPT_FEE_BUY = 2; #买入手续费;
    const OPT_FEE     = 3; #卖出手续费;
    /**
     * 指定表名
     */
    public function designTable($name) //chen
    {
        $this->table = 'order_' . $name . 'coin';
        return $this;
    }
    /**
     * 更新委托单
     */
    public function ajaxcoinTrustList($pair, $param='init')
    {
        // 交易统计

        list($coinFrom, $coinTo) = explode('_', $pair);

        $cKey  = $pair . '_sum';
        $tJson = array('buy'  => [], 'sale' => []);
        
        static $trustList;

        //直接处理
        if($trustList && $param!='init')
        {
            $changeData = [];
            $oldData = &$trustList;
            if($oldData)
            {
                if($param)
                {
                    foreach ($param as $v) 
                    {
                        if(isset($oldData[$v['f']][$v['p']]))
                        {
                            $oldData[$v['f']][$v['p']]['n'] = Tool_Math::add($v['n'], $oldData[$v['f']][$v['p']]['n'], 8, 0, false);
                            if($oldData[$v['f']][$v['p']]['n']<=0)
                            {
                                unset($oldData[$v['f']][$v['p']]);
                            }    
                        }
                        else
                        {
                            $v['n']>0 and $oldData[$v['f']][$v['p']] = array('n'=>$v['n'], 'p'=>$v['p']);
                        }
                        $changeData[$v['f']] = isset($oldData[$v['f']][$v['p']])?$oldData[$v['f']][$v['p']]:array('n'=>null, 'p'=>$v['p']);
                    }
                    krsort($oldData['buy']);
                    ksort($oldData['sale']);
                }
                
                $tJson = $oldData;
            }
        }
        //从数据库读取
        else
        {
            $table    = 'trust_' . $coinFrom . 'coin'; //chen
            $tSql     = "SELECT price p,sum(numberover) n FROM $table WHERE coin_from='%s' and coin_to='%s' and status < 2 and numberover>0 and flag='%s' AND isnew='N' GROUP BY price ORDER BY price %s LIMIT 200";

            $buy  = $this->query(sprintf($tSql, $coinFrom, $coinTo, 'buy', 'DESC'));
            $sale = $this->query(sprintf($tSql, $coinFrom, $coinTo, 'sale', 'ASC'));

            $buyList = $saleList = array();
            foreach ($buy as $v) 
            {
                $buyList[$v['p']] = $v;
            }
            foreach ($sale as $v) 
            {
                $saleList[$v['p']] = $v;
            }
           
            $tJson    = array(
                'buy'  => $buyList,
                'sale' => $saleList,
            );
            $trustList = $tJson;     
        }

        $redis = Cache_Redis::instance('quote');
        
        $redis->set($pair . '_orderbook_200', json_encode($tJson));

        $tJson['buy']  = array_splice($tJson['buy'], 0, 20);
        $tJson['sale'] = array_splice($tJson['sale'], 0, 20);

        $redis->set($cKey, json_encode($tJson));

        //推送
        $tJson['buy']  = array_values($tJson['buy']);
        $tJson['sale'] = array_values($tJson['sale']);

        //累积量
        $leijilb = '';
        foreach ($tJson['buy'] as $k => &$v)
        {
            $v['l'] = Tool_Math::add($leijilb, $v['n']);
            $leijilb = $v['l'];
        }
        unset($v);

        $leijils = array(0);
        array_multisort(array_column($tJson['sale'], 'p'), SORT_ASC, $tJson['sale']);
        foreach ($tJson['sale'] as $k => $v)
        {
            $leijils[] = Tool_Math::add($leijils[$k], $v['n']);
        }

        array_multisort(array_column($tJson['sale'], 'p'), SORT_DESC, $tJson['sale']);
        rsort($leijils);
        foreach ($tJson['sale'] as &$v)
        {
            $v['l'] = array_shift($leijils);
        }

        Tool_Push::send($pair, array('t'=>'trust', 'c'=>$tJson), array('group'=>'all'));
    }

    // 更新交易
    public function ajaxcoinOrder($pair2)
    {
        $coinPariMo = new Coin_PairModel('', 'default', $pair2);
        if (!$pair = $coinPariMo->ffName($pair2))
        {
            return false;
        }
        $pair_arr = explode('_', $pair2); //chen
        $table    = 'order_' . $pair_arr[0] . 'coin'; //chen  'order_coin'替换为$table
        // 订单记录
        $tData = array('max' => 0, 'min' => 0, 'sum' => 0, 'ratio'=>0);
        $tradesCache = array();
        # 最新50个订单
        $tOrders = $this->query("SELECT * FROM $table WHERE opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' ORDER BY id DESC LIMIT 50");
        if ($tOrders)
        {
            $tTrades = array();
            foreach ($tOrders as $k1 => $v1)
            {
                if ($k1 < 30)
                {
                    $tData['d'][] = array(
                        't' => date('H:i:s', $v1['created']),
                        'p' => Tool_Str::format($v1['price'], $pair['price_float']),
                        'n' => Tool_Str::format($v1['number'], $pair['number_float']),
                        's' => $v1['buy_tid'] > $v1['sale_tid'] ? 'buy' : 'sell',
                    );
                }

                //缓存
                $tradesCache[] = array(
                        't' => $v1['created'].'000',
                        'p' => Tool_Math::format($v1['price'], $pair['price_float']),
                        'n' => Tool_Math::format($v1['number'], $pair['number_float']),
                        's' => $v1['buy_tid'] > $v1['sale_tid'] ? 'buy' : 'sell',
                );

                $tTrades[] = array(
                    'date'   => $v1['created'],
                    'price'  => Tool_Str::format($v1['price'], $pair['price_float']),
                    'amount' => Tool_Str::format($v1['number'], $pair['number_float']),
                    'tid'    => $v1['id'],
                    'type'   => $v1['buy_tid'] > $v1['sale_tid'] ? 'buy' : 'sell',
                );
            }
           


            //涨跌幅计算
            $prevEndTime = strtotime(date('Ymd', $tOrders[0]['created']));

            $openPrice = $this->fRow("SELECT * FROM $table WHERE opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and created<$prevEndTime ORDER BY id DESC LIMIT 1");

            if(!$openPrice)
            {
                $openPrice = $this->fRow("SELECT * FROM $table WHERE opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' ORDER BY id LIMIT 1"); 
            }
            $tData['prevClose']=Tool_Math::format($openPrice['price']);

            $tData['ratio'] = 0;
            if($openPrice['price']!=0)
            {
                $tData['ratio'] = round(($tOrders[0]['price']/$openPrice['price']-1)*100, 2);
            }

            //最新价格
            $tData['price'] = Tool_Str::format($tOrders[0]['price'], $pair['price_float']);
        }
        else
        {
            $tData['d']     = array(array('t' => '00:00:00', 'p' => 0, 'n' => 0, 's' => 'sell'));
            $tFullData['d'] = array(array('t' => '00:00:00', 'p' => 0, 'n' => 0, 's' => 'sell'));
        }

        # 24小时最大、最小
        if ($tMPrice = $this->fRow("SELECT max(price) max, min(price) min, sum(number) sum FROM $table WHERE created > " . (time() - 86400) . " AND opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' "))
        {
            $tData['max'] = Tool_Math::format($tMPrice['max'], 8)?:0;
            $tData['min'] = Tool_Math::format($tMPrice['min'], 8)?:0;
            $tData['sum'] = Tool_Math::format($tMPrice['sum'], 4)?:0;
        }

        //24小时成交额
        $tData['money'] = current($this->fRow("SELECT sum(money) FROM (SELECT number*price money FROM $table WHERE created > " . (time() - 86400) . " AND opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' ) t"));
        $tData['money'] = Tool_Math::format($tData['money'], 8)?:0;

        $redis = Cache_Redis::instance('quote');
        //最新订单
        $redis->set($pair2 . '_order', json_encode($tData));
        $redis->set($pair2 . '_trades', json_encode($tradesCache));
        //币最新行情数据
        $quoteData = array(
            'price'=>(string)$tData['price']?:0, 
            'ratio'=>(string)$tData['ratio'], 
            'amount'=>(string)$tData['sum']?:0,
            'money'=>(string)$tData['money']?:0,
            'max'=>(string)$tData['max']?:0,
            'min'=>(string)$tData['min']?:0,
        );
        //法币价
        $currency = Cache_Redis::instance()->get(strtolower($pair['coin_to']).'_rmb_price');
        $quoteData['currency'] = $currency?sprintf('%.2f', $currency*$quoteData['price'], 2):'';
        $usd = Cache_Redis::instance()->get(strtolower($pair['coin_to']).'_usd_price');
        $quoteData['usd'] = $currency?sprintf('%.3f', $usd*$quoteData['price'], 2):'';

        //k线更新最后一个点
        $this->updateKline($pair2, $quoteData);     

        $redis->set($pair2 . '_quote', json_encode($quoteData));


        $redis->hset('all_quotes', $pair2, json_encode($quoteData));

        //推送
        $quoteData['market'] = $pair2;
        $quoteData['area'] = strtolower($pair['coin_to']);
        Tool_Push::send('public', array('t'=>'quote', 'c'=>$quoteData), array('group'=>'all'));
        
    }


    private function updateKline($market, $newQuote)
    {
        $arr = array(
            '1w',
            '1d',
            '1h',
            '1m',
            '15m',
            '30m',
            '1h',
        );
        foreach($arr as $v)
        {
            $klineCacheKey = $market . 'tradeline_' . $v;
            $klineData = json_decode(Cache_Redis::instance('quote')->get($klineCacheKey), true);
            if($klineData)
            {
                end($klineData['datas']['data']);
                $lastKey = key($klineData['datas']['data']);
                $last = &$klineData['datas']['data'][$lastKey];
                $last[4] = (float)$newQuote['price'];
                $last[2] = (float)max($newQuote['price'], $last[2]);
                $last[3] = (float)min($newQuote['price'], $last[3]);
                Cache_Redis::instance('quote')->set($klineCacheKey, json_encode($klineData));
            }
            
        }
    }
}
