<?php
class App_RobotController extends App_BaseRobotController
{
    protected $_auth = 1;

    public function loginAction()
    {
        if(!isset($_POST['username'], $_POST['password']))
        {
            $this->ajax('参数错误');
        }

        if(!Cache_Redis::instance()->hGet('userphone', $_POST['username']))
        {
            $this->ajax('用户不存在或密码错误');
        }

        // 白名单
        // if(!Cache_Redis::instance()->hGet('userphone_for_app', $_POST['username']))
        // {
        //     $this->ajax('用户不存在或密码错误');
        // }

        $user = UserModel::getInstance()->where(array('mo'=>$_POST['username']))->fRow();
        if(!$user || Tool_Md5::encodePwd($_POST['password'], $user['prand']) != $user['pwd'] )
        {
            if($user)
            {
                $errorInfo = json_decode(Cache_Redis::instance('token')->hGet('PASSWORD_ERROR_COUNT', $user['uid']), true);
                if($errorInfo)
                {
                    if($errorInfo['num']>3 && time()-$errorInfo['update']<3600)
                    {
                        $this->ajax('错误次数过多，请稍后再试');    
                    }
                    Cache_Redis::instance('token')->hSet('PASSWORD_ERROR_COUNT', $user['uid'], ['num'=>intval($errorInfo['num'])+1, 'update'=>time()]);
                }
            }
            
            $this->ajax('用户不存在或密码错误');
        }

        //清除错误记录
        Cache_Redis::instance('token')->hDel('PASSWORD_ERROR_COUNT', $user['uid']);

        //实名信息
        $user['realInfo'] = AutonymModel::getInstance()->field('name')->where(['status'=>2, 'uid'=>$user['uid']])->fRow();

        //创建token
        $token = md5($user['uid'].'DOBI'.uniqid(microtime(true), true));

        $redis = Cache_Redis::instance('token');

        //不允许同时在线
        // if($tokenUser = $redis->get($user['uid']))
        // {
        //  $tokenUser = json_decode($tokenUser, true);
        //  $redis->hDel($this->tokenPoolKey, $tokenUser['token']);
        // } 
        

        //签名密钥
        $skey = substr(md5(microtime(true)), rand(1, 6));

        //存一个token=>uid,skey
        $redis->set($token, $user['uid'].','.$skey, 86400);
        
        //uid=>用户信息
        $redis->set($user['uid'], json_encode(array_merge($user, ['token'=>$token, 'skey'=>$skey, 'login_time'=>date('Y-m-d H:i:s')])));
        if(isset($_POST['expire']))
        {
            $redis->expire($user['uid'], intval($_POST['expire']));
        }
        
        $this->mCurUser = $user;
        //$this->log('登录', '');
        $this->ajax('登录成功', 1, array('token'=>$token, 'skey'=>$skey));
    }


    public function getTrustAction()
    {
        $coin = $_POST['coin'];

        if(!isset($_POST['coin']))
        {
            $this->ajax('参数错误');
        }

        list($coinFrom, $coinTo) = explode('_', $_POST['coin']);

        $table    = 'trust_' . $coinFrom . 'coin';
        $tSql     = "SELECT price p,numberover n, uid FROM $table WHERE coin_from='%s' and coin_to='%s' and status < 2 and flag='%s' ORDER BY price %s LIMIT 10";
        $orderMo = Order_CoinModel::getInstance();
        $data    = array(
            'buy'  => $orderMo->query(sprintf($tSql, $coinFrom, $coinTo, 'buy', 'DESC')),
            'sale' => $orderMo->query(sprintf($tSql, $coinFrom, $coinTo, 'sale', 'ASC')),
        );


        $this->ajax('', 1, $data);
    }


    public function setTrustAction()
    {

        //验证登录
        $this->auth();

        //实名认证
        if(!AutonymModel::getInstance()->field('uid')->where(['status'=>2, 'uid'=>$this->mCurUser['uid']])->fRow())
        {
            $this->ajax($GLOBALS['MSG']['NEED_REAL_AUTH'], 0, array('need_real_auth'=>1));
        }

        //验证参数
        if (!isset($_POST['type'], $_POST['price'], $_POST['number'], $_POST['pwdtrade'], $_POST['coin_from'], $_POST['coin_to']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        if (!Tool_Validate::az09($_POST['coin_from']) || !$pair = Coin_PairModel::getInstance()->getPair($_POST['coin_from'] . '_' . $_POST['coin_to']))
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
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
            $_POST['pwdtrade'] = urldecode($_POST['pwdtrade']);
            if(empty($_POST['pwdtrade']))
            {
                $this->ajax($GLOBALS['MSG']['NEED_TRADE_PWD'], 0, array('need_trade_pwd'=>1));
            }

            if (Tool_Md5::encodePwdTrade($_POST['pwdtrade'], $this->mCurUser['prand']) != $this->mCurUser['pwdtrade'])
            {
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

            $trustmoney = Tool_Math::mul($_POST['number'], $_POST['price']);
            //余额不足
            if (Tool_Math::comp($this->mCurUser[$_POST['coin_to'] . '_over'] , $trustmoney)==-1)
            {
                $this->ajax($GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        elseif('out' == $_POST['type'])
        {
            //冻结禁止卖出
            if ($fData && $fData['cansale'] == 0)
            {
                $this->ajax($GLOBALS['MSG']['TRADE_SALE_FROZEN']);
            }

            if (Tool_Math::comp($this->mCurUser[$pair['coin_from'] . '_over'], $_POST['number'])==-1) 
            {
                $this->ajax($GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        else
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
        }

        $trustMo = Trust_CoinModel::getInstance()->designTable($pair['coin_from']);
        $dealList = $trustMo->getDealList($_POST['price'], $pair, 'in' == $_POST['type']?'sale':'buy');
        if($dealList)
        {
            foreach($dealList as $v)
            {
                if($this->mCurUser['uid'] != $v['uid'])
                {
                    $this->ajax('订单自动撤销');
                }
            }
        }

        if(isset($_POST['coid']) && !$trustMo->where(['id'=>intval($_POST['coid']), 'status'=>['<', 2]])->fRow())
        {
            $this->ajax('1单没了老铁');
        }   

        //入库
        $coinFrom = $_POST['coin_from'];
        
        $trust_id = $this->btc($coinFrom, $_POST, $this->mCurUser) or $this->ajax($trustMo->getError(2));
        //$this->log('委托', json_encode($_POST), $pair['coin_from']);
        Cache_Redis::instance('token')->hSet('UPDATE_UID', $this->mCurUser['uid'], 1);
        $this->ajax($GLOBALS['MSG']['ORDER_SUCCESS'], 1, array_merge(['trust_id'=>$trust_id], $this->getUserCoinInfo($coinFrom, $_POST['coin_to'])));

    }


    public function btc($coinFrom, $pData, &$pUser, $api = false)
    {
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
            'createip'=>Tool_Fnc::realip()
        ))){
            $trustCoinMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_ERROR']);
        }

        //刷新委托列表
        $r = $trustCoinMo->pushInQueue($pData['coin_from'].'_'.$pData['coin_to'], array(
            'id'=>$tId,
        ), 'new');

        if(!$r)
        {
            $trustCoinMo->back();
            return $this->setError($GLOBALS['MSG']['SYS_ERROR'].'[2]');
        }

        # 提交数据
        $trustCoinMo->commit();
        
        return $tId;
    }


    /**
     * 委托撤销
     */
    public function trustcancelAction()
    {
        //验证登录
        $this->auth();

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
        Cache_Redis::instance('token')->hSet('UPDATE_UID', $this->mCurUser['uid'], 1);
        //$this->log('撤销委托', json_encode($_POST), $coinFrom);
        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));

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

    public function getCoinPairAction()
    {
        $coinPair = Coin_PairModel::getInstance()->field('name,coin_from,coin_to')->where('status='.Coin_PairModel::STATUS_ON)->fList();
        $this->ajax('', 1, $coinPair);
    }


    public function getUserInfoAction()
    {
        $data = $this->auth();

        $userinfo = [
            'uid'=>$data['uid'],
            'realInfo'=>$data['realInfo'],
        ];
        foreach($data as $k=>$v)
        {
            if(strpos($k, '_over') || strpos($k, '_lock'))
            {
                $userinfo[$k] = $v;
            }
        }

        $this->ajax('', 1, $userinfo);
    }


    /**
     * 我的委托
     */
    public function getMyTrustAction()
    {
        $this->auth();

        $coinFrom = $_POST['coin_from'];
        $coinTo = $_POST['coin_to'];

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
        $list = $trustCoinMo->field('coin_from,coin_to,flag,id,number,numberdeal,numberover,price,created,status')->where($where)->limit(30)->order('status asc,created desc')->fList();

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


    public function gettsAction()
    {
        $this->ajax('', 1, time());
    }
}