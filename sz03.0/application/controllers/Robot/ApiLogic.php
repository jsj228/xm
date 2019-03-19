<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/5
 * Time: 11:39
 */

class Robot_ApiLogicController extends Ctrl_Base
{
    //判断用户是买还是卖
    public function setTrust($input,$user){

        $_POST = $input;
        $this->mCurUser = $user;

        //验证参数
        if (!isset($_POST['type'], $_POST['price'], $_POST['number'],$_POST['coin_from'], $_POST['coin_to']))
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['PARAM_ERROR']];
//            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        if (!Tool_Validate::az09($_POST['coin_from']) || !$pair = Coin_PairModel::getInstance()->getPair($_POST['coin_from'] . '_' . $_POST['coin_to']))
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['ILLEGAL']];
//            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
        }

        //未開放交易
//        if($pair['start']>0 && time()<$pair['start'])
//        {
//            return ['code'=>0,'message'=>$GLOBALS['MSG']['NOT_OPEN_YET']];
////            $this->ajax($GLOBALS['MSG']['NOT_OPEN_YET']);
//        }

        //验证输入价格
        if (0 >= ($_POST['price'] = (float) Tool_Str::format($_POST['price'], $pair['price_float'], 2)))
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['PRICE_ERROR']];
//            $this->ajax($GLOBALS['MSG']['PRICE_ERROR']);
        }

        //验证输入数量
        $_POST['number'] = (float) Tool_Str::format($_POST['number'], $pair['number_float'], 2);
        if (($pair['max_trade']>0 && ($_POST['number'] > $pair['max_trade']) || $_POST['number'] < $pair['min_trade']))
        {
            return ['code'=>0,'message'=>sprintf($GLOBALS['MSG']['TRAND_NUM_RANGE'], $pair['min_trade'], $pair['max_trade'])];
//            $this->ajax(sprintf($GLOBALS['MSG']['TRAND_NUM_RANGE'], $pair['min_trade'], $pair['max_trade']));
        }

        // 闭市
//        if ($pair['rule_open'] == 1)
//        {
//            //周末休市
//            $week = date('w');
////            if (in_array($week, explode(',', $pair['open_week'])))
////            {
////                return ['code'=>0,'message'=>$GLOBALS['MSG']['DAY_OFF']];
//////                $this->ajax($GLOBALS['MSG']['DAY_OFF']);
////            }
//            //节假日休市
//            $day = date('md');
//
////            if (false !== strpos($pair['open_date'], $day))
////            {
////                return ['code'=>0,'message'=>$GLOBALS['MSG']['HOLIDAY_OFF']];
//////                $this->ajax($GLOBALS['MSG']['HOLIDAY_OFF']);
////            }
//
//            $nowHI = intval(date('Hi'));
//            //开盘时间段
//            if ($nowHI < intval($pair['open_start']) || $nowHI > intval($pair['open_end']))
//            {
//                return ['code'=>0,'message'=>$GLOBALS['MSG']['TRANS_TIME'] . trim(chunk_split($pair['open_start'], 2, ':'), ':') . ' - ' . trim(chunk_split($pair['open_end'], 2, ':'), ':')];
////                $this->ajax($GLOBALS['MSG']['TRANS_TIME'] . trim(chunk_split($pair['open_start'], 2, ':'), ':') . ' - ' . trim(chunk_split($pair['open_end'], 2, ':'), ':'));
//            }
//        }

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
                return ['code'=>0,'message'=>sprintf($GLOBALS['MSG']['PRICE_RANGE'], $price_down, $price_up)];
//                $this->ajax(sprintf($GLOBALS['MSG']['PRICE_RANGE'], $price_down, $price_up));
            }
        }

        //是否冻结禁止交易
        $fData = Trust_CoinModel::getTradeStatus($this->mCurUser['uid']);
        if ($fData && $fData['canbuy'] == 0 && $fData['cansale'] == 0)
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['TRADE_FROZEN']];
//            $this->ajax($GLOBALS['MSG']['TRADE_FROZEN']);
        }

        if(!$this->mCurUser['pwdtrade'])
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['NEED_SET_TRADEPWD'], 0, array('need_set_tpwd'=>1)];
//            $this->ajax($GLOBALS['MSG']['NEED_SET_TRADEPWD'], 0, array('need_set_tpwd'=>1));
        }

        //买入
        if ('in' == $_POST['type'])
        {
            //冻结禁止买入
            if ($fData && $fData['canbuy'] == 0)
            {
                return ['code'=>0,'message'=>$GLOBALS['MSG']['TRADE_BUY_FROZEN']];
//                $this->ajax($GLOBALS['MSG']['TRADE_BUY_FROZEN']);
            }

            $trustmoney = $_POST['number'] * $_POST['price'];
            //余额不足
            if ($this->mCurUser[$_POST['coin_to'] . '_over'] < $trustmoney)
            {
                return ['code'=>0,'message'=>$_POST['coin_to'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']];
//                $this->ajax($_POST['coin_to'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        elseif('out' == $_POST['type'])
        {
            //冻结禁止卖出
            if ($fData && $fData['cansale'] == 0)
            {
                return ['code'=>0,'message'=>$GLOBALS['MSG']['TRADE_SALE_FROZEN']];
//                $this->ajax($GLOBALS['MSG']['TRADE_SALE_FROZEN']);
            }

            if ($this->mCurUser[$pair['coin_from'] . '_over'] < $_POST['number'])
            {
                return ['code'=>0,'message'=>$pair['coin_from'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']];
//                $this->ajax($pair['coin_from'] .' '.$GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        }
        else
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['ILLEGAL']];
//            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
        }

//        Tool_Out::p($user);die;

        //机器人可能成交的单自动撤销
//        $trustMo = Trust_CoinModel::getInstance()->designTable($pair['coin_from']);
//        $dealList = $trustMo->getDealList($_POST['price'], $pair, 'in' == $_POST['type']?'sale':'buy');
//        if($dealList)
//        {
//            $robot = Cache_Redis::instance('token')->keys('*');
//            foreach($dealList as $v)
//            {
//                if(in_array($v['uid'], $robot))
//                {
//                    $thisUser = array('uid'=>$v['uid']);
//                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
//                    if(!$result)
//                    {
//                        $this->ajax('[rb]'.$trustMo->getError(2));
//                    }
//                    $robotInfo = json_decode(Cache_Redis::instance('token')->get($v['uid']), true);
//                    //推送通知机器人
//                    $r = Tool_Push::send('auto_trade_robot', array('t'=>'withdrawal_trust', 'd'=>array(
//                        'id'=>$v['id'],
//                        'pair'=>$_POST['coin_from'].'/'.$_POST['coin_to'],
//                        'username'=>$robotInfo['mo']?:$robotInfo['email']
//                    )));
//
//                }
//            }
//        }

        //相同类型的委托，价格优于机器人的，自动撤销机器人单
//        $sameList = $trustMo->getDealList($_POST['price'], $pair, 'in' == $_POST['type']?'buy':'sale');
//        if($sameList)
//        {
//            isset($robot) or $robot = Cache_Redis::instance('token')->keys('*');
//            foreach($sameList as $v)
//            {
//                if('in' == $_POST['type'] && in_array($v['uid'], $robot) && Tool_Math::comp($_POST['price'], $v['price']) == 1)
//                {
//                    $thisUser = array('uid'=>$v['uid']);
//                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
//                    if(!$result)
//                    {
//                        $this->ajax('[rb2]'.$trustCoinMo->getError(2));
//                    }
//                }
//                elseif('out' == $_POST['type'] && in_array($v['uid'], $robot) && Tool_Math::comp($_POST['price'], $v['price']) == -1)
//                {
//                    $thisUser = array('uid'=>$v['uid']);
//                    $result = $trustMo->cancel($v['id'], $thisUser, 1);
//                    if(!$result)
//                    {
//                        $this->ajax('[rb3]'.$trustCoinMo->getError(2));
//                    }
//                }
//                else
//                {
//                    continue;
//                }
//
//                $robotInfo = json_decode(Cache_Redis::instance('token')->get($v['uid']), true);
//                //推送通知机器人
//                $r = Tool_Push::send('auto_trade_robot', array('t'=>'withdrawal_trust', 'd'=>array(
//                    'id'=>$v['id'],
//                    'pair'=>$_POST['coin_from'].'/'.$_POST['coin_to'],
//                    'username'=>$robotInfo['mo']?:$robotInfo['email']
//                )));
//
//            }
//        }



        //入库
        $coinFrom = $_POST['coin_from'];
        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $tId = $trustCoinMo->btc($_POST, $this->mCurUser) or $this->ajax($trustCoinMo->getError(2));

        return ['code'=>1000,'message'=>$GLOBALS['MSG']['ORDER_SUCCESS'],'id'=>$tId];
//        return ['code'=>1,msg]
//        $this->ajax($GLOBALS['MSG']['ORDER_SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));
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
    public function trustcancel($input,$user)
    {
        $_POST = $input;
        $this->mCurUser = $user;

//        $this->_ajax_islogin();
        //安全校验
//        $this->checkReqToken();

        if(!$_POST['id'] || !$_POST['coin_from'] || !$_POST['coin_to'])
        {
            return ['code'=>0,'message'=>$GLOBALS['MSG']['PARAM_ERROR']];
//            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $id = intval($_POST['id']);
        $coinFrom = $_POST['coin_from'];

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $result = $trustCoinMo->cancel($id, $this->mCurUser, 1);
        if(!$result)
        {
            return ['code'=>0,'message'=>$trustCoinMo->getError(2)];
//            $this->ajax($trustCoinMo->getError(2));
        }
        return ['code'=>1000,'message'=>$GLOBALS['MSG']['SUCCESS']];
    }

    //查询订单
    public function getOrder($input){
        $_POST = $input;

        $order = Orm_Base::getInstance()->table("trust_{$input['coin_from']}coin")->fRow();

        if($order){
            $status = [0=>0,1=>0,2=>1,3=>2];//成交状态切换
            $type = ['buy'=>1,'sale'=>0];
            $res = [
                'id'=>$input['id'],
                'currency'=>$input['coin_from'],
                'price'=>$order['price'],
                'status'=>isset($status[$order['status']])?$status[$order['status']]:$order['status'],
                'total_amount'=>$order['number'],
                'trade_amount'=>$order['numberdeal'],
                'trade_date'=>$order['created'],
                'trade_money'=>$order['numberdeal']*$order['price'],
                'type'=>isset($type[$order['flag']])?$type[$order['flag']]:1
            ];
            return ['code'=>1,'data'=>$res];
        }else{
            return ['code'=>0,'msg'=>'未查询到该订单'];
        }
    }

    //获取多个委托买单或卖单，每次请求返回10条记录
    public static function getOrders($input){

        $types = [1=>'buy',0=>'sale'];//类型切换
        if(!isset($types[$input['tradeType']])) return ['code'=>0,'msg'=>'交易类型错误'];

        $start = ($input['pageIndex']-1)*10;
        $orders = Orm_Base::getInstance()->table("trust_{$input['coin_from']}coin")->order('id desc')->limit("$start,10")->fList();

        if(!$orders) return ['code'=>0,'msg'=>'未查询到该订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>0,2=>1,3=>2];//成交状态切换
            $types = ['buy'=>1,'sale'=>0];//类型切换
            $res[] = [
                'id'=>$v['id'],
                'currency'=>$v['market'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['number'],
                'trade_amount'=>$v['numberdeal'],
                'trade_date'=>$v['created'],
                'trade_money'=>$v['numberdeal']*$v['price'],
                'type'=>isset($types[$v['flag']])?$types[$v['flag']]:1
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //(新)获取多个委托买单或卖单，每次请求返回pageSize<100条记录
    public static function getOrdersNew($input){
        if($input['pageSize']>=100) return ['code'=>0,'msg'=>'每页数量超出限制'];
        $types = [1=>'buy',0=>'sale'];//类型切换
        if(!isset($types[$input['tradeType']])) return ['code'=>0,'msg'=>'交易类型错误'];
        $start = 1;
        $orders = Orm_Base::getInstance()->table("trust_{$input['coin_from']}coin")->order('id desc')->limit("$start,10")->fList();
            M('Trade')->where(['userid'=>userid(),'market'=>$input['currency'],'type'=>$types[$input['tradeType']]])
            ->field('id,market,price,status,num,deal,addtime,type')
            ->order('id desc')->limit(($input['pageIndex']-1)*$input['pageSize'],$input['pageSize'])->select();

        if(!$orders) return ['code'=>0,'msg'=>'未查询到该订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $types = [1=>1,2=>0];//类型切换
            $res[] = [
                'currency'=>$v['market'],
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['num'],
                'trade_amount'=>$v['deal'],
                'trade_date'=>$v['addtime'],
                'trade_money'=>$v['deal']*$v['price'],
                'type'=>$types[$v['type']]
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //与getOrdersNew的区别是取消tradeType字段过滤，可同时获取买单和卖单，每次请求返回pageSize10条记录
    public static function getOrdersIgnoreTradeType($input){
        if($input['pageSize']>=100) return ['code'=>0,'msg'=>'每页数量超出限制'];

        $orders = M('Trade')->where(['userid'=>userid(),'market'=>$input['currency']])
            ->field('id,market,price,status,num,deal,addtime,type')
            ->order('id desc')->limit(($input['pageIndex']-1)*$input['pageSize'],$input['pageSize'])->select();

        if(!$orders) return ['code'=>0,'msg'=>'未查询到该订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $types = [1=>1,2=>0];//类型切换
            $res[] = [
                'currency'=>$v['market'],
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['num'],
                'trade_amount'=>$v['deal'],
                'trade_date'=>$v['addtime'],
                'trade_money'=>$v['deal']*$v['price'],
                'type'=>isset($types[$v['type']])?$types[$v['type']]:''
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //获取未成交或部份成交的买单和卖单，每次请求返回pageSize<=10条记录
    public static function getUnfinishedOrdersIgnoreTradeType($input,$uid){

        $coin = strtolower(substr($input['currency'],0,strrpos($input['currency'],"_")));
        $mo = new Orm_Base();
        $orders = $mo->table("trust_{$coin}coin")->where("uid=$uid and (status=0 or status=1)")
            ->field("id,price,status,number,numberdeal,created,flag")
            ->order('id desc')->limit(($input['pageIndex']-1)*$input['pageSize'],$input['pageSize'])
            ->fList();


        if(!$orders) return ['code'=>1013,'msg'=>'未查询到订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>0,2=>1,3=>2];//成交状态切换
            $types = [1=>'buy',2=>'sale'];//类型切换
            $res[] = [
                'currency'=>$coin,
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['number'],
                'trade_amount'=>$v['numberdeal'],
                'trade_date'=>$v['created'],
                'trade_money'=>Tool_Math::mul($v['numberdeal'],$v['price']),
                'type'=>isset($types[$v['type']])?$types[$v['type']]:'buy'
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //获取用户信息
    public static function getAccountInfo($uid){

        $user = UserModel::getInstance()->where("uid=$uid")->fRow();
        $coinconf = CoinModel::getInstance()->where("status=0")->field('name,display')->fList();

        foreach ($coinconf as $v){
            $market[] = [
                'key'=>$v['name'],
                'enName'=>strtoupper($v['name']),
                'cnName'=>$v['display'].'('.strtoupper($v['name']).')',
                'showName'=>strtoupper($v['name']),
                'unitTag'=>strtoupper($v['name']),
                'available'=>$user[$v['name'].'_over'],
                'freez'=>$user[$v['name'].'_lock'],
                'unitDecimal'=>8,
                'isCanRecharge'=>true,
                'isCanWithdraw'=>true,
                'canLoan'=>true
            ];
        }

        $base = [
            'username'=>$user['name'],
            'trade_password_enabled'=>true,
            'auth_google_enabled'=>true,
            'auth_mobile_enabled'=>true,
        ];
        $res = [
            'result'=>[
                'coins' => $market,
                'base' => $base,
            ],
            'assetPerm'=>$user['cnyx_lock'],
            'leverPerm'=>true,
            'entrustPerm'=>true,
            'moneyPerm'=>$user['cnyx_over']
        ];
        return ['code'=>1,'data'=>$res];
    }

    //获取用户充值地址
    public static function getUserAddress($input){
        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'key'=>$userCoin[$input['currency'].'b']
                ]
            ]

        ];
        return $res;
    }

    //获取用户认证的提现地址
    public static function getWithdrawAddress($input){
        $addr = M('User_qianbao')->where(['userid' => userid(),'coinname'=>$input['currency']])->getField('addr');
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'key'=>$addr
                ]
            ]

        ];
        return $res;
    }

    //获取数字资产提现记录
    public static function getWithdrawRecord($input){
        $datas = M('myzc')->where(['userid'=>userid(),'coinname'=>$input['currency']])->limit(($input['pageIndex']-1)*$input['pageSize'])->order('id desc')->select();
        $count = M('myzc')->where(['userid'=>userid(),'coinname'=>$input['currency']])->count();
        $totalPage = ceil($count/$input['pageSize']);

        $status = [0=>0,1=>2,2=>1];
        foreach ($datas as $v){
            $list[] = [
                'amount'=>$v['num'],
                'fees'=>$v['fee'],
                'id'=>$v['id'],
                'manageTime'=>$v['endtime'],
                'status'=>$status[$v['status']],
                'submitTime'=>$v['addtime'],
                'toAddress'=>$v['username'],
            ];
        }
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'list'=>$list,
                    'pageIndex'=>$input['pageIndex'],
                    'pageSize'=>$input['pageSize'],
                    'totalCount'=>$count,
                    'totalPage'=>$totalPage
                ]
            ]
        ];
        return $res;
    }

    //获取数字资产充值记录
    public static function getChargeRecord($input){
        $datas = M('myzr')->where(['userid'=>userid(),'coinname'=>$input['currency']])->limit(($input['pageIndex']-1)*$input['pageSize'])->order('id desc')->select();
        $count = M('myzr')->where(['userid'=>userid(),'coinname'=>$input['currency']])->count();

        $status = [0=>0,1=>2,2=>1,4=>2];
        foreach ($datas as $v){
            $list[] = [
                'address'=>$v['txid'],
                'amount'=>$v['num'],
                'confirmTimes'=>1,
                'currency'=>$input['currency'],
                'description'=>'确认成功',
                'hash'=>$v['txid'],
                'id'=>$v['id'],
                'itransfer'=>true,
                'status'=>isset($status[$v['status']])?$status[$v['status']]:'',
                'submit_time'=>date('Y-m-d H:i:s',$v['addtime'])
            ];
        }
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'list'=>$list,
                    'pageIndex'=>$input['pageIndex'],
                    'pageSize'=>$input['pageSize'],
                    'total'=>$count,
                ]
            ]
        ];
        return $res;
    }

    //提现
    public static function withdraw($input)
    {
        $coin = $input['currency'];
        $num = $input['amount'];
        $addr = $input['receiveAddr'];
        $paypassword = $input['safePwd'];

        $wcgkey = $input['wcgkey'];

        if (!userid()) return ['code'=>0,'msg'=>'数据错误'];
        $num = abs($num);
        if (!check($num, 'currency')) return ['code'=>0,'msg'=>'数量格式错误！'];
        if (!check($addr, 'dw')) return ['code'=>0,'msg'=>'钱包地址格式错误！'];
        if (!check($paypassword, 'password')) return ['code'=>0,'msg'=>'交易密码格式错误！'];
        if (!check($coin, 'n'))  return ['code'=>0,'msg'=>'币种格式错误！'];
        if (!C('coin')[$coin]) return ['code'=>0,'msg'=>'币种错误！'];

        $Coin = M('Coin')->where(array('name' => $coin))->find();
        if (!$Coin) return ['code'=>0,'msg'=>'币种错误！'];

        $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.01);
        $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);
        if ($num < $myzc_min) return ['code'=>0,'msg'=>'转出数量超过系统最小限制！'];
        if ($myzc_max < $num) return ['code'=>0,'msg'=>'转出数量超过系统最大限制！'];

        $user = M('User')->where(array('id' => userid()))->find();
        if (md5($paypassword) != $user['paypassword']) return ['code'=>0,'msg'=>'交易密码错误！'];

        if ($user['idcardauth'] == 0) return ['code'=>0,'msg'=>'请先进行身份认证！'];

        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        if ($coin == 'btmz'){
            if ($user_coin['btm'] < $num) return ['code'=>0,'msg'=>'可用余额不足！'];
        }else{
            if ($user_coin[$coin] < $num) return ['code'=>0,'msg'=>'可用余额不足！'];
        }

        //收手续费的地址，找到后进行手续费添加
        $qbdz = $coin . 'b';
        $fee_user = M('UserCoin')->where(array($qbdz => $Coin['zc_user']))->find();
        if ($fee_user) {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 存在,有手续费');
            $usercoin=M('coin')->where(array('name'=>$coin))->getField('zc_fee');

            if($str_len = strpos($usercoin,'%')){
                $usercoin = substr($usercoin,0,$str_len);
                $fee = round(($num / 100) * ($usercoin), 8);
            }else{
                $fee =  $usercoin;
            }


//            if($coin=='wc' || $coin=='wcg' || $coin=='oioc' || $coin=='eac' || $coin == 'sie' || $coin == 'drt' || $coin == 'mat' || $coin == 'ifc' || $coin == 'mtr' || $coin == 'xrp'){
//                $fee = round(($num / 100) * ($usercoin), 8);
//            }else{
//                $fee =  $usercoin;
//            }
            //无限币提币费率：就是500W以下 0.2%+200个.   500W以上 10000个+200 个
            /*if($coin=='ifc'){
                if($num<=5000000) {
                    $fee = round(($num * 0.002)+200,8);
                } elseif($num>5000000){
                    $fee = 10200;
                }
            }*/
            $mum = round($num - $fee, 8);
            if ($mum < 0) return ['code'=>0,'msg'=>'转出手续费错误！'];
            if ($fee < 0) return ['code'=>0,'msg'=>'转出手续费设置错误！'];
        } else {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 不存在,无手续费');
            $fee = 0;
            $mum = $num;
        }

        if ($Coin['type'] == 'rgb') {
            debug($Coin, '开始认购币转出');

            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzc_fee write');
            $rs = array();
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);

            if ($fee) {
                if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                    debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                } else {
                    $rs[] = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                }
            }

            $arr = array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0);
            if (($coin === 'wcg' && !empty($wcgkey)) || ($coin === 'drt' && !empty($wcgkey)) || ($coin === 'mat' && !empty($wcgkey))) {
                $arr['wcgkey'] = $wcgkey;
            }
            $rs[] = $mo->table('weike_myzc')->add($arr);
            if ($fee_user) {
                $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
            }

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                session('myzc_verify', null);
                return ['code'=>1,'msg'=>'转账成功！'];
            } else {
                $mo->execute('rollback');
                return ['code'=>0,'msg'=>'转账失败！'];
            }
        }

        if ($Coin['type'] == 'bit' || $Coin['type'] == 'eth' || $Coin['type'] == 'token' || $Coin['type'] == 'eos') {
            $mo = M();
            if ($Coin['type'] == 'eos') {
                $user_wallet =  M('UserQianbao')->where(array('memo' => $addr,'userid'=>userid(),'coinname'=>'eos'))->find();
                $addr = $user_wallet['addr'];
                $memo = $user_wallet['memo'];
            }
            if ($mo->table('weike_user_coin')->where(array($qbdz => $addr))->find() && $Coin['type'] != 'eos') {
                //禁止站内互转！
                return ['code'=>0,'msg'=>'禁止站内互转！'];
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();
                if (!$peer) {
                    return ['code'=>0,'msg'=>'转出地址不存在！'];
                }

                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzr write , weike_myzc_fee write');
                $rs = array();
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee) {
                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                    } else {
                        $rs[] = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    }
                }

                $rs[] = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('weike_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    session('myzc_verify', null);
                    return ['code'=>1,'msg'=>'转账成功！'];
                } else {
                    $mo->execute('rollback');
                    return ['code'=>0,'msg'=>'转账失败！'];
                }
            } else {
                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write ,weike_myzr write, weike_myzc_fee write');
                $rs = array();
                $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //$rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                if ( $Coin['type'] == 'eos') {
                    $addr_memo = $addr.' '.$memo;
                    $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr_memo, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                } else {
                    $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                }

                if ($fee && $auto_status) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $r = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                        debug(array('res' => $r, 'lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                    } else {
                        $rs[] = $r = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    }
                }
                if (check_arr($rs)) {
                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        if ($Coin['type'] == 'bit') {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);
                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                return ['code'=>0,'msg'=>$data['message']];
                            }
                        } elseif ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);

                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                return ['code'=>0,'msg'=>$data['message']];
                            }
                        } elseif ($Coin['type'] == 'eos') {
                            $data = myCurl('http://172.31.39.219/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num , 'memo' => $memo]);

                            if ($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                return ['code'=>0,'msg'=>$data['message']];
                            }
                        }

                        if ($sendrs) {
                            $flag = 1;
                            $arr = json_decode($sendrs, true);

                            if (isset($arr['status']) && ($arr['status'] == 0)) {
                                $flag = 0;
                            }
                        } else {
                            $flag = 0;
                        }

                        if (!$flag) {
                            return ['code'=>0,'msg'=>'钱包服务器转出币失败,请手动转出'];
                        } else {
                            return ['code'=>1,'msg'=>'转出成功'];
                        }
                    }

                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        return ['code'=>1,'msg'=>'转出成功'];
                    } else {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        return ['code'=>1,'msg'=>'转出申请成功,请等待审核！'];
                    }
                } else {
                    $mo->execute('rollback');
                    return ['code'=>0,'msg'=>'转出失败!'];
                }
            }
        }

        if ($Coin['type'] == 'btm'){
            $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
            if ($btmzData){
                $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                $chkAddr = $btmClient->validateAddress($addr);
                if ($chkAddr){
                    if ($chkAddr['valid'] && $chkAddr['is_local']){
                        return ['code'=>0,'msg'=>'禁止站内互转!'];
                    }
                }else{
                    return ['code'=>0,'msg'=>'地址错误!'];
                }

                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币

                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write ,weike_myzr write, weike_myzc_fee write');
                $rs = array();
                $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('btm', $num);
                $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $r = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc('btm', $fee);
                        debug(array('res' => $r, 'lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                    } else {
                        $rs[] = $r = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], 'btm' => $fee));
                    }
                }
                if (check_arr($rs)) {
                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');

                        $res = $btmClient->outcome($addr,$mum);
                        if ($res){
                            $sigRes = $btmClient->signTransaction($res);
                            if ($sigRes){
                                $resSub = $btmClient->submitTransaction($sigRes);
                                if ($resSub){
                                    M('myzc')->where(array('id' => trim($aid)))->save(['status'=>1,'txid'=>$resSub['tx_id']]);
                                    $flag = true;
                                }else{
                                    M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                                    $flag = false;
                                }
                            }else{
                                M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                                $flag = false;
                            }
                        }else{
                            M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                            $flag = false;
                        }
                    }

                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        if ($flag){
                            return ['code'=>1,'msg'=>'转出成功!'];
                        }else{
                            return ['code'=>0,'msg'=>'转出成功，请等待确认!'];
                        }
                    } else {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        return ['code'=>1,'msg'=>'转出申请成功,请等待审核！'];
                    }
                } else {
                    $mo->execute('rollback');
                    return ['code'=>0,'msg'=>'转出失败！'];
                }

            }else{
                return ['code'=>0,'msg'=>'转出失败！'];
            }
        }

        if ($Coin['type'] == 'xrp'){
            $user_wallet =  M('UserQianbao')->where(array('addr' => $addr,'userid'=>userid(),'coinname'=>'xrp'))->find();
            $addr = $user_wallet['memo'] ? $user_wallet['addr'] . ' ' .$user_wallet['memo'] :  $user_wallet['addr'];
            $mo = M();
            $mo->startTrans();
            try{
                $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('xrp', $num);
                $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));

                if ($fee) {
                    $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc('xrp', $fee);
                    } else {
                        $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], 'xrp' => $fee));
                    }
                }
                $mo->commit();
                $flag = true;
            }catch (\Exception $e){
                $mo->rollback();
                $flag = false;
            }

            if ($flag){
                return ['code'=>1,'msg'=>'添加成功！'];
            }else{
                return ['code'=>0,'msg'=>'添加失败！'];

            }

        }
    }
}