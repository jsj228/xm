<?php
/*
* 交易模块逻辑
*/
class TradeLogic extends BaseLogic
{
	/*
	* 委托下单
	*/
    public function order($args, $user)
    {
    	//验证参数
        if (!isset($args['type'], $args['price'], $args['number'], $args['pwdtrade'], $args['coin_from'], $args['coin_to']))
        {
        	throw new DobiException($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        if (!Tool_Validate::az09($args['coin_from']) || !$pair = Coin_PairModel::getInstance()->getPair($args['coin_from'] . '_' . $args['coin_to']))
        {
            throw new DobiException($GLOBALS['MSG']['ILLEGAL'], 2);
        }

         //未開放交易
        if($pair['start']>0 && time()<$pair['start'])
        {
            throw new DobiException($GLOBALS['MSG']['NOT_OPEN_YET']);
        }

        //验证输入价格
        if (0 >= ($args['price'] = (float) Tool_Math::format($args['price'], $pair['price_float'], 2)))
        {
            throw new DobiException($GLOBALS['MSG']['PRICE_ERROR']);
        }

        //验证输入数量
        $args['number'] = (float) Tool_Math::format($args['number'], $pair['number_float'], 2);
        if (($pair['max_trade']>0 && ($args['number'] > $pair['max_trade']) || $args['number'] < $pair['min_trade']))
        {
            throw new DobiException(str_replace('<br>', ',', sprintf($GLOBALS['MSG']['TRAND_NUM_RANGE'], $pair['min_trade'], $pair['max_trade'])));
        }

        // 闭市
        if ($pair['rule_open'] == 1)
        {
            //周末休市
            $week = date('w');
            if (in_array($week, explode(',', $pair['open_week'])))
            {
                throw new DobiException($GLOBALS['MSG']['DAY_OFF']);
            }
            //节假日休市
            $day = date('md');
            if (false !== strpos($pair['open_date'], $day))
            {
                throw new DobiException($GLOBALS['MSG']['HOLIDAY_OFF']);
            }

            $nowHI = intval(date('Hi'));
            //开盘时间段
            if ($nowHI < intval($pair['open_start']) || $nowHI > intval($pair['open_end']))
            {
                throw new DobiException($GLOBALS['MSG']['TRANS_TIME'] . trim(chunk_split($pair['open_start'], 2, ':'), ':') . ' - ' . trim(chunk_split($pair['open_end'], 2, ':'), ':'));
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
            	$prevEndTime = strtotime('today');
                $openOrder = Order_CoinModel::getInstance()->designTable($pair['coin_from'])->where("opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and created<$prevEndTime")->order('id DESC')->fRow();
                if(!$openPrice)
                {
                    $openPrice = Order_CoinModel::getInstance()->designTable($pair['coin_from'])->where("opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}'")->order('id')->fRow();
                }
                $openPrice = $openOrder['price'];
                $redis->set($hKey, $openPrice, 86400);
            }

            $priceUp   = bcmul($openPrice, $pair['up_percent'], $pair['price_float']);
            $priceDown = bcmul($openPrice, $pair['down_percent'], $pair['price_float']);

            //挂单价格超出限制
            if (($priceUp >0 && (float) $args['price'] > $priceUp) || (float) $args['price'] < $priceDown)
            {
                throw new DobiException(sprintf($GLOBALS['MSG']['PRICE_RANGE'], $priceDown, $priceUp));
            }
        }

        //是否冻结/禁止交易
        $fData = Trust_CoinModel::getTradeStatus($user['uid']);
        if ($fData && $fData['canbuy'] == 0 && $fData['cansale'] == 0)
        {
            throw new DobiException($GLOBALS['MSG']['TRADE_FROZEN']);
        }

        if(!$user['pwdtrade'])
        {
            throw new DobiException($GLOBALS['MSG']['NEED_SET_TRADEPWD'], 0, array('need_set_tpwd'=>1));
        }

        //验证交易密码
        if (!Tool_Md5::pwdTradeCheck($user['uid']))
        {
            if(empty($args['pwdtrade']))
            {
                throw new DobiException($GLOBALS['MSG']['NEED_TRADE_PWD'], 0, array('need_trade_pwd'=>1));
            }

            $this->verifiyTradePwd($args['pwdtrade'], $user);

            Tool_Md5::pwdTradeCheck($user['uid'], 'add');
        }


        //买入
        if ('in' == $args['type'])
        {
            //冻结禁止买入
            if ($fData && $fData['canbuy'] == 0)
            {
                throw new DobiException($GLOBALS['MSG']['TRADE_BUY_FROZEN']);
            }

            $trustmoney = Tool_Math::mul($args['number'], $args['price']);
            //余额不足
            if (Tool_Math::comp($user[$args['coin_to'] . '_over'], $trustmoney)==-1)
            {
                throw new DobiException($args['coin_to'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        elseif('out' == $args['type'])
        {
            //冻结禁止卖出
            if ($fData && $fData['cansale'] == 0)
            {
                throw new DobiException($GLOBALS['MSG']['TRADE_SALE_FROZEN']);
            }

            if (Tool_Math::comp($user[$pair['coin_from'] . '_over'], $args['number'])==-1)
            {
                throw new DobiException($pair['coin_from'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        else
        {
            throw new DobiException($GLOBALS['MSG']['ILLEGAL'], 2);
        }


        //机器人可能成交的单自动撤销
        $trustMo = Trust_CoinModel::getInstance()->designTable($pair['coin_from']);
        $dealList = $trustMo->getDealList($args['price'], $pair, 'in' == $args['type']?'sale':'buy');
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
                        throw new DobiException('[rb]'.$trustMo->getError(2));
                    }
                }
            }
        }

        //相同类型的委托，价格优于机器人的，自动撤销机器人单
        $sameList = $trustMo->getDealList($args['price'], $pair, 'in' == $args['type']?'buy':'sale');
        if($sameList)
        {
            isset($robot) or $robot = Cache_Redis::instance('token')->keys('*');
            foreach($sameList as $v)
            {
                if('in' == $args['type'] && in_array($v['uid'], $robot) && Tool_Math::comp($args['price'], $v['price']) == 1)
                {
                    $thisUser = array('uid'=>$v['uid']);
                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
                    if(!$result)
                    {
                        throw new DobiException('[rb2]'.$trustCoinMo->getError(2));
                    }
                }
                elseif('out' == $args['type'] && in_array($v['uid'], $robot) && Tool_Math::comp($args['price'], $v['price']) == -1)
                {
                    $thisUser = array('uid'=>$v['uid']);
                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
                    if(!$result)
                    {
                        throw new DobiException('[rb3]'.$trustCoinMo->getError(2));
                    }
                }
            }
        }

        //入库
        $coinFrom = $args['coin_from'];
        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $r = $trustCoinMo->btc($args, $user);
        if(!$r)
        {
        	throw new DobiException($trustCoinMo->getError(2));
        }

        return true;
    }


    /*
	* 撤销委托单
	*/
    public function cancel($id, $coinFrom, $user)
    {
    	$trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $result = $trustCoinMo->cancel($id, $user);
        if(!$result)
        {
            throw new DobiException($trustCoinMo->getError(2));
        }

        return true;
    }



    /*
    * 校验交易密码
    */
    public function verifiyTradePwd($tradePwd, $user)
    {
        //错误次数
        $errorKey = 'TRADE_PWD_ERROR_' . $user['uid'];
        $errorNum = Cache_Redis::instance()->get($errorKey);

        if($errorNum>=5)
        {
            throw new DobiException($GLOBALS['MSG']['TRADE_PWD_ERROR']);
        }

        if (empty($tradePwd) || Tool_Md5::encodePwdTrade($tradePwd, $user['prand']) != $user['pwdtrade'])
        {
            Cache_Redis::instance()->set($errorKey, $errorNum+1, 7200);//两小时
            throw new DobiException($GLOBALS['MSG']['TRADE_PWD_ERROR'], 0, array('need_trade_pwd'=>1));
        }
        Tool_Md5::pwdTradeCheck($user['uid'], 'add');

        return true;
    }


}
