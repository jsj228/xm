<?php
class Ajax_TradeController extends Ajax_BaseController
{
    //启用 SESSION
    protected $_auth = 1;

    /**
     * 我的委托
     */
    public function getMyTrustAction($return=false)
    {
        $this->_ajax_islogin();

        $coin = $_GET['coin'];
        list($coinFrom, $coinTo) = explode('_', $coin);

        if (!$coinFrom || !$coinTo)
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $where       = array(
            'uid'       => $this->mCurUser['uid'],
            'coin_from' => $coinFrom,
            'coin_to' => $coinTo,
        );
        $list = $trustCoinMo->field('coin_from,coin_to,flag,id,number,numberdeal,numberover,price,created,status')->where($where)->limit(200)->order('status asc,created desc')->fList();

        foreach ($list as &$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
        }

        if($return)
        {
            return $list;
        }
        $this->ajax('', 1, $list);

    }


    /**
     * 我的委托 version 2
     */
    public function getMyTrustV2Action($return=false)
    {
        $this->_ajax_islogin();

        $page = $_GET['page']?:1;
        $coin = $_GET['coin'];
        $pageSize = 60;
        list($coinFrom, $coinTo) = explode('_', $coin);

        if (!$coinFrom || !$coinTo)
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $where       = array(
            'uid'       => $this->mCurUser['uid'],
            'coin_from' => $coinFrom,
            'coin_to' => $coinTo,
        );
        $list = $trustCoinMo->field('coin_from,coin_to,flag,id,number,numberdeal,numberover,price,created,status')->where($where)->page($page, $pageSize)->order('status asc,created desc')->fList();
        $count = $trustCoinMo->where($where)->count();

        $data = array('list'=>array(), 'totalPage'=>ceil($count/$pageSize));
        foreach ($list as $v)
        {
            $data['list'][] = array(
                'i'=>$v['id'],
                'n'=>$v['number'],
                'd'=>$v['numberdeal'],
                'o'=>$v['numberover'],
                'p'=>$v['price'],
                's'=>$v['status'],
                'f'=>$v['flag'],
                'cf'=>$v['coin_from'],
                'ct'=>$v['coin_to'],
                't'=>date('Y-m-d H:i:s', $v['created']),
            );
        }
        // $data = array();
        // foreach ($list as $v)
        // {
        //     $data[] = array(
        //         'i'=>$v['id'],
        //         'n'=>$v['number'],
        //         'd'=>$v['numberdeal'],
        //         'o'=>$v['numberover'],
        //         'p'=>$v['price'],
        //         's'=>$v['status'],
        //         'f'=>$v['flag'],
        //         'cf'=>$v['coin_from'],
        //         'ct'=>$v['coin_to'],
        //         't'=>date('Y-m-d H:i:s', $v['created']),
        //     );
        // }

        if($return)
        {
            return $data;
        }
        $this->ajax('', 1, $data);

    }


    /**
     * 市场挂单
     */
    public function getTrustAction($return=false)
    {
        $coin  = $_GET['coin'];
        $redis = Cache_Redis::instance('quote');
        $data  = $redis->get($coin . '_sum');

        if ($data)
        {
            $data = json_decode($data, true);
            if($data)
            {
                $data['buy'] = array_values($data['buy']);

                array_multisort(array_column($data['buy'], 'p'), SORT_DESC, $data['buy']);

                $data['sale'] = array_values($data['sale']);
                $leijilb = '';
                foreach ($data['buy'] as $k => &$v)
                {
                    $v['l'] = Tool_Math::add($leijilb, $v['n']);
                    $leijilb = $v['l'];
                }
                unset($v);

                $leijils = array(0);
                array_multisort(array_column($data['sale'], 'p'), SORT_ASC, $data['sale']);
                foreach ($data['sale'] as $k => $v)
                {
                    $leijils[] = Tool_Math::add($leijils[$k], $v['n']);
                }

                array_multisort(array_column($data['sale'], 'p'), SORT_DESC, $data['sale']);
                rsort($leijils);
                foreach ($data['sale'] as &$v)
                {
                    $v['l'] = array_shift($leijils);
                }
                //临时
                $data['sale'] = array_slice($data['sale'], -20);

            }
        }

        $data or $data = array('buy'=>[], 'sale'=>[]);



        if($return)
        {
            return $data;
        }
        $this->ajax('', 1, $data);
    }






    /**
     * 市场成交
     */
    public function getOrdersAction($return=false)
    {
        $coin  = $_GET['coin'];
        $redis = Cache_Redis::instance('quote');
        $data  = $redis->get($coin . '_order');
        if ($data)
        {
            $data = json_decode($data, true);
        }
        if(!isset($data['price']))
        {
            $data['price'] = '';
        }
        if($return)
        {
            return $data;
        }
        $this->ajax('', 1, $data);
    }

    /**
     * 委托下单
     */
    public function setTrustAction()
    {

        //验证登录
        $this->_ajax_islogin();

        //rsa解密


        //安全校验
        $this->checkReqToken();

        //实名认证
       /* if(!AutonymModel::getInstance()->field('uid')->where(['status'=>2, 'uid'=>$this->mCurUser['uid']])->fRow())
        {
            $this->ajax($GLOBALS['MSG']['NEED_REAL_AUTH'], 0, array('need_real_auth'=>1));
        }*/


        //验证参数
        if (!isset($_POST['type'], $_POST['price'], $_POST['number'], $_POST['pwdtrade'], $_POST['coin_from'], $_POST['coin_to']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        if (!Tool_Validate::az09($_POST['coin_from']) || !$pair = Coin_PairModel::getInstance()->getPair($_POST['coin_from'] . '_' . $_POST['coin_to']))
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
        }

         //未開放交易
        if($pair['start']>0 && time()<$pair['start'])
        {
            $this->ajax($GLOBALS['MSG']['NOT_OPEN_YET']);
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

        if(!$this->mCurUser['pwdtrade'])
        {
            $this->ajax($GLOBALS['MSG']['NEED_SET_TRADEPWD'], 0, array('need_set_tpwd'=>1));
        }

        //验证交易密码
        if (!Tool_Md5::pwdTradeCheck($this->mCurUser['uid']))
        {
            if(empty($_POST['pwdtrade']))
            {
                $this->ajax($GLOBALS['MSG']['NEED_TRADE_PWD'], 0, array('need_trade_pwd'=>1));
            }

            //错误次数
            $ekey = 'TRADE_PWD_ERROR'.$this->mCurUser['uid'];
            $errorNum = $this->checkErrorNum($ekey);

            if (Tool_Md5::encodePwdTrade($_POST['pwdtrade'], $this->mCurUser['prand']) != $this->mCurUser['pwdtrade'])
            {
                //错误次数限制
                $this->setErrorNum($ekey, $errorNum);
                $this->ajax($GLOBALS['MSG']['TRADE_PWD_ERROR'], 0, array('need_trade_pwd'=>1));
            }
            Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'add');
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
            if ($this->mCurUser[$_POST['coin_to'] . '_over'] < $trustmoney)
            {
                $this->ajax($_POST['coin_to'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
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
        else
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
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
        $coinFrom = $_POST['coin_from'];
        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $trustCoinMo->btc($_POST, $this->mCurUser) or $this->ajax($trustCoinMo->getError(2));

        $this->ajax($GLOBALS['MSG']['ORDER_SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));

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

	/**
     * 委托撤销
     */
	public function trustcancelAction()
	{
		$this->_ajax_islogin();

        //安全校验
        $this->checkReqToken();

		if(!$_POST['id'] || !$_POST['coin_from'] || !$_POST['coin_to'])
		{
			$this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
		}

		$id = intval($_POST['id']);
		$coinFrom = $_POST['coin_from'];

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $result = $trustCoinMo->cancel($id, $this->mCurUser, 1);
        if(!$result)
        {
            $this->ajax($trustCoinMo->getError(2));
        }

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));

	}

    /**
     * 委托批量撤销
     */
    public function batchtrustcancelAction()
    {
        $this->_ajax_islogin();
        //$this->mCurUser['uid']= 13231175;
        if (!$_POST['coin_from'] || !$_POST['coin_to'] || !$_POST['flag']) {//参数错误
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 0);
        }
        $_POST['minprice'] = trim($_POST['minprice']);
        $_POST['maxprice'] = trim($_POST['maxprice']);
        if (!empty($_POST['minprice'])) {
            if (!is_numeric($_POST['minprice'])) {//判断是否是数字
                $this->ajax($GLOBALS['MSG']['PLEASE_ENTER_NUMBER'], 0);
            }
        }
        if (!empty($_POST['maxprice'])) {
            if (!is_numeric($_POST['maxprice'])) {//判断是否是数字
                $this->ajax($GLOBALS['MSG']['PLEASE_ENTER_NUMBER'], 0);
            }
        }
        if (!empty($_POST['minprice'])&& !empty($_POST['maxprice'])) {
            if($_POST['maxprice']- $_POST['minprice']<0){
                $this->ajax($GLOBALS['MSG']['MINPRICE_THAN_MINPRICE'], 0);//最低价不能高于最高价
            }
        }
        $id = intval($_POST['id']);
        $coinFrom = $_POST['coin_from'];

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $result = $trustCoinMo->batchcancel($id, $this->mCurUser, 1);
        if (!$result) {
            $this->ajax($trustCoinMo->getError(2));
        }

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));

    }

    /**
     * 交易密码校验
     */
    public function pwdtradeAuthAction()
    {
        $this->_ajax_islogin();

        //错误次数限制
        $ekey = 'TRADE_PWD_ERROR'.$this->mCurUser['uid'];
        $errorNum = $this->checkErrorNum($ekey);

        if (empty($_POST['pwdtrade']) || Tool_Md5::encodePwdTrade($_POST['pwdtrade'], $this->mCurUser['prand']) != $this->mCurUser['pwdtrade'])
        {
            $this->setErrorNum($ekey, $errorNum);
            $this->ajax($GLOBALS['MSG']['TRADE_PWD_ERROR']);
        }
        Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'add');
        $this->ajax('', 1);
    }

    /**
     * 获取btc实时价格, C++在用，不要改动数据结构
     */
    public function btcpriceAction()
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

        $this->ajax("", 1, $json);
    }


    /**
     * 交易中心合并接口
     */
    public function tradeDataAction()
    {
        //我的委托
        if($this->mCurUser)
        {
            $data['mytrust'] = $this->getMyTrustAction(true);
        }
        //市场挂单
        $data['trust'] = $this->getTrustAction(true);
        //市场成交
        $data['orders'] = $this->getOrdersAction(true);

        $this->ajax('', 1, $data);
    }


    /**
     * 交易中心合并接口 version 2
     */
    public function tradeDataV2Action()
    {
        //我的委托
        if($this->mCurUser)
        {
            $data['mytrust'] = $this->getMyTrustV2Action(true);
        }
        //市场挂单
        $data['trust'] = $this->getTrustAction(true);
        //市场成交
        $data['orders'] = $this->getOrdersAction(true);

        $this->ajax('', 1, $data);
    }

}
