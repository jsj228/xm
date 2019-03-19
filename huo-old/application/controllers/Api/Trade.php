<?php
class Api_TradeController extends Api_BaseController
{
	/**
     * 委托下单
     */
    public function order()
    {

        //身份验证
        $this->auth();

        //验证参数
        if (!isset($_POST['type'], $_POST['price'], $_POST['number'], $_POST['market']) 
        	|| !preg_match('/^[a-z\d]+_[a-z\d]+$/i', $_POST['market']) 
        	|| !is_numeric($_POST['number'])
        	|| !is_numeric($_POST['price'])
        	|| !in_array($_POST['type'], ['buy', 'sale', 'sell'])
            || (isset($_POST['out_trade_no']) && !preg_match('/^[a-z\d\-_]$/i', $_POST['out_trade_no']))
        )
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }
        //换参
        $_POST['type'] = $_POST['type']=='buy'?'in':'out';

        list($coinFrom, $coinTo) = explode('_', $_POST['market']);
        $_POST['coin_from'] = $coinFrom;
        $_POST['coin_to'] = $coinTo;
        if (!$pair = Coin_PairModel::getInstance()->getPair($_POST['market']))
        {
            $this->ajax('Market '.$GLOBALS['MSG']['NOT_EXISTED'], 2);
        }

        //验证输入价格
        if (0 >= ($_POST['price'] = (float) Tool_Str::format($_POST['price'], $pair['price_float'], 2)))
        {
            $this->ajax($GLOBALS['MSG']['PRICE_ERROR']);
        }

        //验证输入数量
        $_POST['number'] = (float) Tool_Str::format($_POST['number'], $pair['number_float'], 2);
        if (($pair['max_trade']>0 && ($_POST['number'] > $pair['max_trade']) || $_POST['number'] < $pair['min_trade']))
        {
            $this->ajax(sprintf($GLOBALS['MSG']['TRAND_NUM_RANGE'], $pair['min_trade'], $pair['max_trade']));
        }

        // 闭市
        if ($pair['rule_open'] == 1)
        {
            //周末休市
            $week = date('w');
            if (in_array($week, explode(',', $pair['open_week'])))
            {
                $this->ajax($GLOBALS['MSG']['DAY_OFF']);
            }
            //节假日休市
            $day = date('md');
            if (false !== strpos($pair['open_date'], $day))
            {
                $this->ajax($GLOBALS['MSG']['HOLIDAY_OFF']);
            }

            $nowHI = intval(date('Hi'));
            //开盘时间段
            if ($nowHI < intval($pair['open_start']) || $nowHI > intval($pair['open_end']))
            {
                $this->ajax($GLOBALS['MSG']['TRANS_TIME'] . trim(chunk_split($pair['open_start'], 2, ':'), ':') . ' - ' . trim(chunk_split($pair['open_end'], 2, ':'), ':'));
            }
        }

        //价格限制
        if ($pair['price_limit'] == 1)
        {
        	//涨跌幅限制
            $redis = Cache_Redis::instance();
            $hKey = sprintf('OpenPrice_%s_%s', $pair['name'] , date('Ymd'));
            $openPrice = $redis->get($hKey);
            if(!$openPrice)
            {
                $openOrder = Order_CoinModel::getInstance()->designTable($pair['coin_from'])->where("opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and created<$prevEndTime")->order('id DESC')->fRow();
                if(!$openPrice)
                {
                    $openPrice = Order_CoinModel::getInstance()->designTable($pair['coin_from'])->where("opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}'")->order('id')->fRow();
                }
                $openPrice = $openOrder['price'];
                $redis->set($hKey, $openPrice, 86400);
            }

            $price_up   = bcmul($openPrice, $pair['up_percent'], $pair['price_float']);
            $price_down = bcmul($openPrice, $pair['down_percent'], $pair['price_float']);

            //挂单价格超出限制
            if (($price_up >0 && (float) $_POST['price'] > $price_up) || (float) $_POST['price'] < $price_down)
            {
                $this->ajax(sprintf($GLOBALS['MSG']['PRICE_RANGE'], $price_down, $price_up));
            }
        }

        //是否冻结禁止交易
        $fData = Trust_CoinModel::getTradeStatus($this->mCurUser['uid']);
        if ($fData && $fData['canbuy'] == 0 && $fData['cansale'] == 0)
        {
            $this->ajax($GLOBALS['MSG']['TRADE_FROZEN']);
        }

        //买入
        if ('in' == $_POST['type'])
        {
            //冻结禁止买入
            if ($fData && $fData['canbuy'] == 0)
            {
                $this->ajax($GLOBALS['MSG']['TRADE_BUY_FROZEN']);
            }

            $trustmoney = $_POST['number'] * $_POST['price'];
            //余额不足
            if ($this->mCurUser[$coinTo . '_over'] < $trustmoney)
            {
                $this->ajax($coinTo .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        elseif('out' == $_POST['type'])
        {
            //冻结禁止卖出
            if ($fData && $fData['cansale'] == 0)
            {
                $this->ajax($GLOBALS['MSG']['TRADE_SALE_FROZEN']);
            }

            if ($this->mCurUser[$pair['coin_from'] . '_over'] < $_POST['number'])
            {
                $this->ajax($pair['coin_from'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }


        //机器人可能成交的单自动撤销
        $trustMo = Trust_CoinModel::getInstance()->designTable($pair['coin_from']);
        $dealList = $trustMo->getDealList($_POST['price'], $pair, 'in' == $_POST['type']?'sale':'buy');
        if($dealList)
        {
            $robot = Cache_Redis::instance('token')->keys('*');
            foreach($dealList as $v)
            {
                if(in_array($v['uid'], $robot))
                {
                    $thisUser = array('uid'=>$v['uid']);
                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
                    if(!$result)
                    {
                        $this->ajax('[rb]'.$trustMo->getError(2));
                    }
                    $robotInfo = json_decode(Cache_Redis::instance('token')->get($v['uid']), true);
                    //推送通知机器人
                    $r = Tool_Push::send('auto_trade_robot', array('t'=>'withdrawal_trust', 'd'=>array(
                        'id'=>$v['id'],
                        'pair'=>$_POST['coin_from'].'/'.$_POST['coin_to'],
                        'username'=>$robotInfo['mo']?:$robotInfo['email']
                    )));
                }
            }
        }

        //相同类型的委托，价格优于机器人的，自动撤销机器人单
        $sameList = $trustMo->getDealList($_POST['price'], $pair, 'in' == $_POST['type']?'buy':'sale');
        if($sameList)
        {
            isset($robot) or $robot = Cache_Redis::instance('token')->keys('*');
            foreach($sameList as $v)
            {
                if('in' == $_POST['type'] && in_array($v['uid'], $robot) && Tool_Math::comp($_POST['price'], $v['price']) == 1)
                {
                    $thisUser = array('uid'=>$v['uid']);
                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
                    if(!$result)
                    {
                        $this->ajax('[rb2]'.$trustCoinMo->getError(2));
                    }
                    $robotInfo = json_decode(Cache_Redis::instance('token')->get($v['uid']), true);
                    //推送通知机器人
                    $r = Tool_Push::send('auto_trade_robot', array('t'=>'withdrawal_trust', 'd'=>array(
                        'id'=>$v['id'],
                        'pair'=>$_POST['coin_from'].'/'.$_POST['coin_to'],
                        'username'=>$robotInfo['mo']?:$robotInfo['email']
                    )));
                }
                elseif('out' == $_POST['type'] && in_array($v['uid'], $robot) && Tool_Math::comp($_POST['price'], $v['price']) == -1)
                {
                    $thisUser = array('uid'=>$v['uid']);
                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
                    if(!$result)
                    {
                        $this->ajax('[rb3]'.$trustCoinMo->getError(2));
                    }
                }
                else
                {
                    continue;
                }
                
                $robotInfo = json_decode(Cache_Redis::instance('token')->get($v['uid']), true);
                //推送通知机器人
                $r = Tool_Push::send('auto_trade_robot', array('t'=>'withdrawal_trust', 'd'=>array(
                    'id'=>$v['id'],
                    'pair'=>$_POST['coin_from'].'/'.$_POST['coin_to'],
                    'username'=>$robotInfo['mo']?:$robotInfo['email']
                )));

            }
        }

        //入库
        $trust_id = $this->btc($coinFrom, $_POST, $this->mCurUser) or $this->ajax($trustCoinMo->getError(2));

        $this->ajax($GLOBALS['MSG']['ORDER_SUCCESS'], 1, ['id'=>$trust_id]);

    }




    public function btc($coinFrom, $pData, &$pUser, $api = false)
    {
        $exists = OpenapiOrdersModel::getInstance()->where(['out_trade_no'=>$pData['out_trade_no']?:$pData['sign'],'uid'=>$this->mCurUser['uid']])->fRow();
        if($exists)
        {
            return $this->ajax($GLOBALS['MSG']['ORDER_EXISTED']);
        }
        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        # 保存DB
        $trustCoinMo->begin();
        # 买入
        if($pData['type']=='in'){
            $totalPrice = Tool_Math::mul($pData['price'], $pData['number']);
            $coinData = array($pData['coin_to'].'_lock' => $totalPrice, $pData['coin_to'].'_over' => Tool_Math::mul('-1', $totalPrice));
            $pData['type'] = 'buy';
        }
        # 卖出
        else {
            $number = $pData['number'];
            $coinData = array($pData['coin_from'].'_lock' => $number, $pData['coin_from'].'_over' => Tool_Math::mul('-1', $number));
            $pData['type'] = 'sale';
        }
        # 写入
        $userMo = UserModel::getInstance();
        if(!$userMo->safeUpdate($pUser, $coinData, $api)){
            $trustCoinMo->back();
            Tool_Fnc::ajaxMsg($userMo->error[2]);
        }
        # 写入委托
        if(!$tId = $trustCoinMo->insert(array(
            'uid'=>$pUser['uid'],
            'price'=>$pData['price'],
            'number'=>$pData['number'],
            'numberover'=>$pData['number'],
            'flag'=>$pData['type'],
            'status'=>0,
            'coin_from'=>$pData['coin_from'],
            'coin_to'=>$pData['coin_to'],
            'created'=>time(),
            'createip'=>Tool_Fnc::realip(),
            'trust_type'=>3
        ))){
            $trustCoinMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_ERROR']);
        }

        #流水
        $r = OpenapiOrdersModel::getInstance()->insert(array(
            'out_trade_no'=>$pData['out_trade_no']?:$pData['sign'],
            'uid'=>$this->mCurUser['uid'],
            'trust_id'=>$tId,
            'coin'=>$pData['coin_from'],
        ));

        if(!$r)
        {
            $trustCoinMo->back();
            return $this->ajax($GLOBALS['MSG']['SYS_ERROR'].'[2]');
        }

        //刷新委托列表
        $r = $trustCoinMo->pushInQueue($pData['coin_from'].'_'.$pData['coin_to'], array(
            'id'=>$tId,
        ), 'new');

        

        # 提交数据
        $trustCoinMo->commit();
        
        return $tId;
    }



    /**
     * 委托撤销
     */
    public function cancel()
    {
        //验证登录
        $this->auth();

        if(!$_POST['id'] || !$_POST['market'] || !preg_match('/^[a-z\d]+_[a-z\d]+$/i', $_POST['market']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $id = intval($_POST['id']);
        list($coinFrom, $coinTo) = explode('_', $_POST['market']);

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $result = $trustCoinMo->cancel($id, $this->mCurUser, 1);
        if(!$result)
        {
            $this->ajax($trustCoinMo->getError(2));
        }

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);

    }


    private function getUserCoinInfo($coinFrom, $coinTo)
    {
        return array(
            $coinTo . '_over'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinTo . '_over']), '.'),
            $coinTo . '_lock'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinTo . '_lock']), '.'),
            $coinFrom.'_over'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinFrom.'_over']), '.'),
            $coinFrom.'_lock'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinFrom.'_lock']), '.')
        );
    }


   	public function myInfo()
    {
        $data = $this->auth();

        $userinfo = [
            'phone'=>$data['area'] . $data['mo'],
        ];
        foreach($data as $k=>$v)
        {
            if(strpos($k, '_over') || strpos($k, '_lock'))
            {
                $userinfo['assets'][$k] = Tool_Math::eftnum($v);
            }
        }

        $this->ajax('', 1, $userinfo);
    }


    /**
     * 我的委托
     */
    public function myOrders()
    {
        $this->auth();

        $market = $_POST['market'];
        $page = isset($_POST['page']) ? max($_POST['page'], 1) : 1;  //页码
        $sortType = isset($_POST['sortType']) && in_array($_POST['sortType'], [1, 2]) ? $_POST['sortType'] : 1; //排序类型
        $pageSize = 10;

        if (!$market || !preg_match('/^[a-z]+_[a-z]+$/i', $market)) {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        list($coinFrom, $coinTo) = explode('_', $market);

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);

        if (isset($_POST['orderIds']) && !empty($_POST['orderIds'])) {//根据委托id 查询
            $orderIds = addslashes($_POST['orderIds']);
            if (!is_string($orderIds)) {
                $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
            }
            $trustarr = explode(',', $orderIds);
            if (count($trustarr) > 100) {
                $this->ajax($GLOBALS['MSG']['MAX_TRUST'], 2);//最多査詢100條委託
            }
            $str = '(';
            foreach ($trustarr as $v) {
                $str .= (int)$v . ',';
            }
            $str = substr($str, 0, -1) . ')';
            $list = $trustCoinMo->field('coin_from,coin_to,flag,id,number,numberdeal,numberover,price,created,status')->where("id in $str and uid={$this->mCurUser['uid']}")->fList();

        } else {//查询我的委托
            $where = array(
                'uid' => $this->mCurUser['uid'],
                'coin_from' => $coinFrom,
                'coin_to' => $coinTo,
            );

            $order = $sortType == 1 ? 'status asc,created desc' : 'created desc';
            $list = $trustCoinMo->field('coin_from,coin_to,flag,id,number,numberdeal,numberover,price,created,status')->where($where)->order($order)->page($page, $pageSize)->fList();

        }

        foreach ($list as &$v) {
            $v['market'] = $v['coin_from'] . '_' . $v['coin_to'];
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
            $v['flag'] = $v['flag'] == 'sale' ? 'sell' : 'buy';
            unset($v['coin_from'], $v['coin_to']);
        }

        $this->ajax('', 1, $list);

    }

    /**
     * 交易規則
     */
    public function rules()
    {
        $market = $_GET['market']; 
        if(!$market || !preg_match('/^[a-z]+_[a-z]+$/i', $market))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }
        $where = array('status'=>1, 'name'=>$market);
        $data = Coin_PairModel::getInstance()->field('name,price_float,number_float,min_trade,max_trade,rate,rate_buy')->where($where)->fRow();
        if(!$data)
        {
            $this->ajax('Market '.$GLOBALS['MSG']['NOT_EXISTED']);
        }
        $returnData = array(
            'market'=>$data['name'],
            'price_decimal_limit'=>$data['price_float'],
            'number_decimal_limit'=>$data['number_float'],
            'min'=>$data['min_trade'],
            'max'=>$data['max_trade'],
            'buy_rate'=>$data['rate_buy'],
            'sell_rate'=>$data['rate'],
        );
        $this->ajax('', 1, $returnData);
    }

    /**
     * 交易市场
     */
    public function markets()
    { 
        $where = array('status'=>1, 'name'=>$market);
        $data = Coin_PairModel::getInstance()->field('name')->where(['status'=>Coin_PairModel::STATUS_ON])->fList();
        $data = array_column($data, 'name');
        $this->ajax('', 1, $data);
    }


}
