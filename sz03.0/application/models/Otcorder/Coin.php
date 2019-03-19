<?php
class Otcorder_CoinModel extends Orm_Base{
    protected $_config='otc';
    public $table = 'order_btc';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'm_id' => array('type' => "int(11) unsigned", 'comment' => '发布交易广告id'),
        'from_uid' => array('type' => "int(11) unsigned", 'comment' => '發佈廣告用戶id'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
        'order_sn' => array('type' => "varchar(20)", 'comment' => '订单号'),
        'price' => array('type' => "decimal(20,8) unsigned", 'comment' => '交易价格'),
        'order_price' => array('type' => "decimal(20,8) unsigned", 'comment' => '订单金额价格'),
        'number' => array('type' => "decimal(20,8) unsigned", 'comment' => '订单数量'),
        'opt' => array('type' => "tinyint(1)", 'comment' => '操作类型: 1.超时关闭，2.买家申诉，3卖家申诉，4.买家撤销申诉，5.卖家撤销申诉'),
        'status' => array('type' => "tinyint(1)", 'comment' => '状态.0:待付款，1:待确认，2:已完成，3:已关闭'),
        'type'   => array('type' => "tinyint(1)", 'comment' => '类型：0：求购的广告，点击購買，1出售广告，点击出售'),
        'flag' => array('type' => "enum('buy','sale')", 'comment' => '买卖标志'),
        'reupdated' => array('type' => "int(11) unsigned", 'comment' => '释放时间'),
        'appupdated' => array('type' => "int(11) unsigned", 'comment' => '申诉时间'),
        'payupdated' => array('type' => "int(11) unsigned", 'comment' => '支付时间'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
        'pay_type' => array('type' => "tinyint(1)", 'comment' => '支付方式：1.微信，2支付宝，3银行卡'),
        'pay_time' => array('type' => "int(11) unsigned", 'comment' => '付款时间'),
        'sale_fee' => array('type' => "decimal(20,8)", 'comment' => '卖手续费'),
        'buy_fee' => array('type' => "decimal(20,8)", 'comment' => '买手续费'),
        'userbak'=>array('type'=>"varchar(255)", "comment"=> '留言'),
        'unread'=>array('type'=>"tinyint(1)", "comment"=> '0未读，1已读'),
        'appealcontent' => array('type' => "varchar(600)", 'comment' => '申诉理由'),
        'appealphoto' => array('type' => "varchar(80)", 'comment' => '申诉圖片'),
    );

    public $pk = 'id';

    const OPT_TRADE = 1; #交易;
    const OPT_FEE_BUY = 2; #买入手续费;
    const OPT_FEE = 3; #卖出手续费;
    /**
     * 指定表名
     */
    public function designTable($name)	//chen
    {
        $this->table= 'order_'.$name;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * 取消交易
     * @param $orderId
     * @return array
     */
    public function orderCancel($orderId,$coin='btc')
    {
        if(empty($orderId)||!is_numeric($orderId))
        {
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['PARAM_ERROR'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }

        $coinName = strtolower($coin);
        $mkMo = new Market_BtcModel();
        // 实例化order模型
        $coinMoName = Order_.ucfirst($coin).Model;
        $orderMo  = new $coinMoName();
        $coinOver = $coinName."_over";
        $coinLock = $coinName."_lock";

        $time = time();

        $orderMo->begin();

        $data = $orderMo->lock()->field("id,m_id,uid,from_uid,price,number,status,sale_fee,buy_fee")->where(['id'=>$orderId])->fRow();

        // 订单不存在
        if(empty($data)||$data['status']!='0')
        {
            $orderMo->back();
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['ORDER_NO_EXIST'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }

        //取消 status=3
        $updata = [
            'id'  	   => $orderId,
            'updated'  => $time,
            'status'   => '3'
        ];

        // 更新訂單狀態
        if(!$orderMo->update($updata))
        {
            $orderMo->back();
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }

        // 查看该广告信息
        $marketData = $mkMo->lock()->field("id,uid,price,number,numberover,numberdeal,max_price,min_price,last_max_price,last_min_price,flag,coin,fee,feeover,feedeal")
            ->where(['id'=>$data['m_id']])->fRow();

        // 该广告信息不存在
        if(empty($marketData))
        {
            $orderMo->back();
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }

        // 计算交易限制价格
        $afterData = $this->tradeAfterPrice($marketData,$data,2);
        if(!$afterData)
        {
            $orderMo->back();
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }

        $numberOver = $afterData['numberOver'];
        $priceOver  = $afterData['priceOver'];

        if($marketData['flag']=='buy')
        {
            $fee = $data['buy_fee'];
        }
        elseif ($marketData['flag']=='sale')
        {
            $fee = $data['sale_fee'];
        }

        // 如果该广告信息是已全部成交status==2,撤销了交易就要把状态改成部分成交，status==1;其他不用更改状态
        if($marketData['status']==2)
        {
            if($numberOver==$marketData['number'])
            {
                $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$marketData['last_min_price'].",max_price=".$marketData['last_max_price'].",updated=".$time.",status=1,feeover=fee,feedeal=0 where id=".$data['m_id'];
            }
            else
            {
                // 如果剩餘量價值大於初始最大交易限制,交易限制不用修改
                if($priceOver>=$marketData['last_max_price'])
                {
                    $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
						        max_price=".$marketData['last_max_price'].",updated=".$time.",status=1,feeover=feeover+".$fee.",feedeal=feedeal-".$fee." where id=".$data['m_id'];
                }
                // 如果剩余量价值，在初始最大和最小交易限制之间，则只用修改最大交易限制
                else if($priceOver>=$marketData['last_min_price']&&$priceOver<$marketData['last_max_price'])
                {
                    // 返还广告剩余数量和修改交易限制
                    $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        max_price=".$priceOver.",updated=".$time.",status=1,feeover=feeover+".$fee.",feedeal=feedeal-".$fee." where id=".$data['m_id'];
                }
                else // 如果剩余量价值小于初始最小交易限制，则最大和最小都需要修改，且都一样
                {
                    $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$priceOver.",max_price=".$priceOver.",updated=".$time.",status=1,feeover=feeover+".$fee.",feedeal=feedeal-".$fee." where id=".$data['m_id'];
                }
            }
        }
        else
        {
            if($numberOver==$marketData['number'])
            {
                $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$marketData['last_min_price'].",max_price=".$marketData['last_max_price'].",updated=".$time.",feeover=fee,feedeal=0 where id=".$data['m_id'];
            }
            else
            {
                // 如果剩餘量價值大於初始最大交易限制,交易限制不用修改
                if($priceOver>=$marketData['last_max_price'])
                {
                    $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
						        max_price=".$marketData['last_max_price'].",updated=".$time.",feeover=feeover+".$fee.",feedeal=feedeal-".$fee." where id=".$data['m_id'];
                }
                // 如果剩余量价值，在初始最大和最小交易限制之间，则只用修改最大交易限制
                else if($priceOver>=$marketData['last_min_price']&&$priceOver<$marketData['last_max_price'])
                {
                    // 返还广告剩余数量和修改交易限制
                    $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        max_price=".$priceOver.",updated=".$time.",feeover=feeover+".$fee.",feedeal=feedeal-".$fee." where id=".$data['m_id'];
                }
                else // 如果剩余量价值小于初始最小交易限制，则最大和最小都需要修改，且都一样
                {
                    $sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$priceOver.",max_price=".$priceOver.",updated=".$time.",feeover=feeover+".$fee.",feedeal=feedeal-".$fee." where id=".$data['m_id'];
                }
            }
        }

        if(!$orderMo->exec($sql))
        {
            $orderMo->back();
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }

        $userMo = new UserModel();

        // 如果該廣告信息是出售廣告，且還是已經停止出售的，鎖定餘額，要返回給用戶
        if($marketData['status']==3&&$marketData['flag']=='sale')
        {
            $userInfo = $userMo->lock()->field("uid,".$coinLock)->where(['uid'=>$data['from_uid']])->fRow();
            $tradeNumber = bcadd(strval($data['number']),strval($data['sale_fee']),8);
            if($tradeNumber<=$userInfo[$coinLock])
            {
                $sql = "update user set ".$coinOver."=".$coinOver."+".$tradeNumber.",".$coinLock."=".$coinLock."-".$tradeNumber.",updated =".$time .",updateip='".Tool_Fnc::realip()."' where uid=".$data['from_uid'];
                if(!$this->exec($sql))
                {
                    $orderMo->back();
                    $dds =  [
                        'status' => 0,
                        'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                        'data'   => ['data'=>[],'reUrl'=>''],
                    ];
                    return $dds;
                }
            }
            else
            {
                $orderMo->back();
                $dds =  [
                    'status' => 0,
                    'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                    'data'   => ['data'=>[],'reUrl'=>''],
                ];
                return $dds;
            }

        }
        //  求购广告（发布广告的用户） // 返还卖家的币剩余数量
        elseif($marketData['flag']=='buy')
        {
            $userinfo = $userMo->lock()->field('uid,'.$coinOver.','.$coinLock)->where(['uid'=>$data['uid']])->fRow();
            $tradeNumber = bcadd(strval($data['number']),strval($data['sale_fee']),8);
            if($tradeNumber<=$userinfo[$coinLock])
            {
                $sql = "update user set ".$coinOver."=".$coinOver."+".$tradeNumber.",".$coinLock."=".$coinLock."-".$tradeNumber.",updated=".$time.",updateip='".Tool_Fnc::realip()."' where uid=".$data['uid'];
                if(!$this->exec($sql))
                {
                    $orderMo->back();
                    $dds =  [
                        'status' => 0,
                        'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                        'data'   => ['data'=>[],'reUrl'=>''],
                    ];
                    return $dds;
                }
            }
            else
            {
                $orderMo->back();
                $dds =  [
                    'status' => 0,
                    'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                    'data'   => ['data'=>[],'reUrl'=>''],
                ];
                return $dds;
            }
        }
        if(!$orderMo->commit())
        {
            $dds =  [
                'status' => 0,
                'msg'    => $GLOBALS['MSG']['USER_OPERATION_FAIL'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }
        else
        {
            $mkMo->saveMarkeBuyAction($marketData['coin']);
//				}
//				elseif($marketData['flag']=='sale')
//				{
            $mkMo->saveMarkeSaleAction($marketData['coin']);
            Tool_Session::mark($data['uid']);
            Tool_Session::mark($data['from_uid']);
            $dds =  [
                'status' => 1,
                'msg'    => $GLOBALS['MSG']['USER_OPERATION_SUCCESS'],
                'data'   => ['data'=>[],'reUrl'=>''],
            ];
            return $dds;
        }
    }

    /**
     * 计算交易后的价值
     * @param $marketData array()
     * @param $orderData array()
     * @param $action string 1：下单，2：取消
     * @return array|bool
     */
    public function tradeAfterPrice($marketData,$orderData,$action=1)
    {
        if(empty($marketData)||!is_array($marketData))
            return false;
        if(empty($orderData)||!is_array($orderData))
            return false;
        // 1.如果是溢价
        if($marketData['pricetype']==2)
        {
            if($action==1)
            {
                // 如果是求购信息
                if($marketData['flag']=='buy')
                {
                    // 如果判断溢价价格大于最低价格（用户设置的overflowprice）,则计算采用最低价格
                    if($marketData['price']>$marketData['overflowprice'])
                    {
                        // 取消后的剩余量
                        $numberOver = bcsub($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['overflowprice'],2);
                    }
                    else
                    {
                        // 取消后的剩余量
                        $numberOver = bcsub($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['price'],2);
                    }
                }
                elseif($marketData['flag']=='sale') //如果是出售信息
                {
                    // 如果判断溢价价格小于最低价格（用户设置的overflowprice）,则计算采用最低价格
                    if($marketData['price']<$marketData['overflowprice'])
                    {
                        // 取消后的剩余量
                        $numberOver = bcsub($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['overflowprice'],2);
                    }
                    else
                    {
                        // 取消后的剩余量
                        $numberOver = bcsub($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['price'],2);
                    }
                }
            }
            else if($action==2)
            {
                // 如果是求购信息
                if($marketData['flag']=='buy')
                {
                    // 如果判断溢价价格大于最低价格（用户设置的overflowprice）,则计算采用最低价格
                    if($marketData['price']>$marketData['overflowprice'])
                    {
                        // 取消后的剩余量
                        $numberOver = bcadd($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['overflowprice'],2);
                    }
                    else
                    {
                        // 取消后的剩余量
                        $numberOver = bcadd($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['price'],2);
                    }
                }
                elseif($marketData['flag']=='sale') //如果是出售信息
                {
                    // 如果判断溢价价格小于最低价格（用户设置的overflowprice）,则计算采用最低价格
                    if($marketData['price']<$marketData['overflowprice'])
                    {
                        // 取消后的剩余量
                        $numberOver = bcadd($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['overflowprice'],2);
                    }
                    else
                    {
                        // 取消后的剩余量
                        $numberOver = bcadd($marketData['numberover'],$orderData['number'],8);
                        // 取消后的剩余量计算出来的价格
                        $priceOver = bcmul($numberOver,$marketData['price'],2);
                    }
                }
            }

        }
        else //固定价格
        {
            // 取消后的剩余量
            if($action==1)
            {
                $numberOver = bcsub($marketData['numberover'],$orderData['number'],8);
            }
            else
            {
                $numberOver = bcadd($marketData['numberover'],$orderData['number'],8);
            }
            // 取消后的剩余量计算出来的价格
            $priceOver = bcmul($numberOver,$marketData['price'],2);
        }

        if(isset($numberOver)&&isset($priceOver))
        {
            $data = [
                'numberOver' => $numberOver,
                'priceOver'  => $priceOver,
            ];
            return $data;
        }
        else
            return false;
    }

    /**
     * 计算手续费
     * @param string $coin  币种
     * @param number $number  数量
     * @param string $type sale:卖币的，buy：买币的,all:买卖
     * @return bool|string
     */
    public function tradeFee($coin,$number,$type='all')
    {
        if($coin&&$number&&is_numeric($number))
        {
            $coinPairMo = new Coin_PairModel();
            $res = $coinPairMo->field("name,status,rate,sale_rate,buy_rate")->where(['name'=>$coin])->fRow();
            if($res)
            {
                if($type=='sale')
                {
                    if($res['sale_rate']==0)
                    {
                        return 0;
                    }
                    $rateNumber['sale'] = bcmul($number,$res['sale_rate'],8);
                    return $rateNumber;
                }
                elseif ($type=='buy')
                {
                    if($res['buy_rate']==0)
                    {
                        return 0;
                    }
                    $rateNumber['buy'] = bcmul($number,$res['buy_rate'],8);
                    return $rateNumber;
                }
                elseif ($type=='all')
                {
                    $rateNumber['buy'] = bcmul($number,$res['buy_rate'],8);
                    $rateNumber['sale'] = bcmul($number,$res['sale_rate'],8);
                    return $rateNumber;
                }
                else
                {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * 交易行情
     * @param string 币种
     */
    public function ajaxcoinOrder($coin)
    {
        $coinPariMo = new Coin_TradeModel();
        if (!$pair = $coinPariMo->ffName($coin))
        {
            return false;
        }
        $table    = 'order_' . $coin;
        // 订单记录
        $tData = array('max' => 0, 'min' => 0, 'sum' => 0, 'ratio'=>0);
        # 最新50个订单
        $tOrders = $this->query("SELECT * FROM $table WHERE status=2 ORDER BY id DESC LIMIT 100");
        if ($tOrders)
        {
            $tTrades = array();
            foreach ($tOrders as $k1 => $v1)
            {
//				if ($k1 < 30)
//				{
                $tData['d'][] = array(
                    'time' => date('H:i:s', $v1['created']),
                    'price' => trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v1['price'], $pair['price_float'])), '.'),
                    'number' => trim(preg_replace('/(\.\d*?)0+$/', '$1',  Tool_Str::format($v1['number'], $pair['number_float'])), '.'),
                    'total' => trim(preg_replace('/(\.\d*?)0+$/', '$1',  bcadd($v1['price'],$v1['number'],2)), '.'),
                    'flag' => $v1['flag']
                );
                //}

//				$tTrades[] = array(
//					'date'   => $v1['created'],
//					'price'  => Tool_Str::format($v1['price'], $pair['price_float']),
//					'amount' => Tool_Str::format($v1['number'], $pair['number_float']),
//					'tid'    => $v1['id'],
//					'type'   => $v1['buy_tid'] > $v1['sale_tid'] ? 'buy' : 'sell',
//				);
            }



            //涨跌幅计算
            $prevEndTime = strtotime(date('Ymd', $tOrders[0]['created']));

            $openPrice = $this->fRow("SELECT * FROM $table WHERE status=2  and created<$prevEndTime ORDER BY id DESC LIMIT 1");

            if(!$openPrice)
            {
                $openPrice = $this->fRow("SELECT * FROM $table WHERE status=2  ORDER BY id LIMIT 1");
            }

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
            $tData['d']     = array(array('time' => '00:00:00', 'price' => '0', 'number' => '0', 'total'=> '0', 'flag' => 'sell'));
            $tFullData['d'] = array(array('time' => '00:00:00', 'price' => '0', 'number' => '0', 'total'=> '0', 'flag' => 'sell'));
        }

        # 24小时最大、最小
        if ($tMPrice = $this->fRow("SELECT max(price) max, min(price) min, sum(number) sum FROM $table WHERE created > " . (time() - 86400) . " AND status=2  "))
        {
            $tData['max'] = Tool_Math::format($tMPrice['max'], 8);
            $tData['min'] = Tool_Math::format($tMPrice['min'], 8);
            $tData['sum'] = Tool_Math::format($tMPrice['sum'], 4);
        }

        if($coin=="btc")
        {
            $tData['sum']=$tData['sum']+10;
        }

        //24小时成交额
        $tData['total'] = current($this->fRow("SELECT sum(total) FROM (SELECT number*price total FROM $table WHERE created > " . (time() - 86400) . " AND status=2  ) t"));
        $tData['total'] = Tool_Math::format($tData['total'], 8)?:0;

        if($coin=="btc")
        {
            $tData['total']=($tData['sum']+10)*$tData['price'];
        }

        $redis = Cache_Redis::instance('quote');
        //最新订单
        $redis->set($coin . '_order', json_encode($tData));
        //币最新行情数据
        $quoteData = array(
            'price'=>floatval($tData['price']),
            'ratio'=>floatval($tData['ratio']),
            'amount'=>floatval($tData['sum']),
            'total'=>floatval($tData['total']),
        );
        $redis->set($coin . '_quote', json_encode($quoteData));
    }

    /**
     * 邀请返佣手续费
     * @param $coin 币种
     * @param $number 数量
     * @return bool|string
     */
    public function inviteFee($coin,$number)
    {
        $coinPairMo = new Coin_PairModel();
        $res = $coinPairMo->field("name,status,invite_rate")->where(['name'=>$coin])->fRow();
        if($res)
        {
            $mathNumber = bcmul($number,$res['invite_rate'],8);
            return $mathNumber;
        }
        else
            return false;
    }
}
