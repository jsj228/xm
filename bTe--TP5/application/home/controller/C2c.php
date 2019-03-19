<?php

namespace app\home\controller;
use think\Db;
use think\Exception;

class C2c extends Home
{
    //设置交易币种价格
    private $currency = [
        'hkd' => [
            'buy_price' => 1,
            'sell_price' => 1,
        ],
        'usdt' => [
            'buy_price' => 6.70,
            'sell_price' => 6.63,
        ],
    ];

    public function __construct()
    {
        //判断登陆， 不登陆不能 直接 访问数据库
        parent::__construct();
        if (!userid()) {
            $this->redirect('/#login');
        }
    }

    //c2c index
    public function index()
    {
        //获取交易记录
        $coin = input('coin/s');
        $coin = $coin === 'usdt' ? $coin : 'hkd';
        $where['coin'] = $coin;
        $where['userid'] = userid();

        $list = Db::name('UserC2cTrade')->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
            //商户卖单（正处理，部分成交）
            if($item['type'] == 2 && $item['businessid'] != 0 && ($item['status'] == 0 || $item['status'] == 3)){
                $log = Db::name('UserC2cLog')->where(['selltradeno' => $item['tradeno']])->order('id desc')->value('num');
                $item['log'] = $log;

            }else{
                $item['log'] = 0;
            }
//            $bank = Db::name('UserBank')->field('name, bankcard')->where(['id' => $item['bankid']])->find();
//            $item['name'] = $bank['name'];
//            $item['bankcard'] = $bank['bankcard'];

            return $item;
        });

        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('coin', $coin);


        //获取买单
        $buy = Db::name('UserC2cTrade')->alias('uc2ct')->join("weike_user u","uc2ct.userid = u.id",'left')->field('uc2ct.id,u.username,uc2ct.num,uc2ct.price,uc2ct.paytype,uc2ct.deal')
            ->where(['uc2ct.type' => 1, 'uc2ct.status' => 0, 'uc2ct.order' => 0, 'uc2ct.businessid' => 0])->order('uc2ct.id asc')->limit(15)->select();
        foreach ($buy as $k => $v) {
            //处理用户名
           // $buy[$k]['username'] = Db::name('User')->where(['id' => $v['userid']])->value('username');
            if (strlen($buy[$k]['username']) > 11){
                $buy[$k]['username'] = substr_replace($buy[$k]['username'], '****', 3, strlen($buy[$k]['username'])-6);
            }else{
                $buy[$k]['username'] = substr_replace($buy[$k]['username'], '****', 3, 4);
            }

        }
        //获取卖单
        $sell = Db::name('UserC2cTrade')->alias('uc2ct')->join("weike_user u","uc2ct.userid = u.id",'left')->field('uc2ct.id,u.username,uc2ct.num,uc2ct.price,uc2ct.paytype,uc2ct.deal,uc2ct.min_num')
            ->where(['uc2ct.type' => 2, 'uc2ct.status' => 0, 'uc2ct.order' => 0, 'uc2ct.businessid' => 0])->order('uc2ct.id asc')->limit(15)->select();
        foreach ($sell as $k => $v) {
            //处理用户名
            if (strlen($sell[$k]['username']) > 11){
                $sell[$k]['username'] = substr_replace($sell[$k]['username'], '****', 3, strlen($sell[$k]['username'])-6);
            }else{
                $sell[$k]['username'] = substr_replace($sell[$k]['username'], '****', 3, 4);
            }

        }
        //获取买入商家
        $buy_sj = Db::name('UserC2c')->where(['type' => 1,'status' => 1,'deal' =>array('gt',100)])->select();
        $buy_list = count($buy_sj);
        foreach ($buy_sj as $k => $v) {
            $buy_sj[$k]['username'] = $v['moble'];
            $buy_sj[$k]['username'] = substr_replace($buy_sj[$k]['username'], '****', 3, 4);
        }
        //获取卖出商家
        $sell_sj = Db::name('UserC2c')->where(['type' => 2,'status' => 1,'deal' =>array('gt',100)])->select();
        $sell_list = count($sell_sj);
        foreach ($sell_sj as $key => $vo) {
            $sell_sj[$key]['username'] = $vo['moble'];
            $sell_sj[$key]['username'] = substr_replace($sell_sj[$key]['username'], '****', 3, 4);
        }
        $this->assign('buy', $buy);
        $this->assign('sell', $sell);
        $this->assign('buy_sj', $buy_sj);
        $this->assign('sell_sj', $sell_sj);
        $this->assign('buy_list', $buy_list);
        $this->assign('sell_list', $sell_list);
        //获取用户银行卡
        $bank = Db::name('UserBank')->field('id, bank, bankcard')->where(['userid' => userid()])->select();
        $this->assign('bank', $bank);

        //交易币种价格
        $this->assign('currency', $this->currency);
        return $this->fetch();
    }

    // 挂委单
    public function trade()
    {
        if (IS_POST) {
            //token验证
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $coin = input('coin/s');
            $price = input('price/f');
            $num = input('num/f');
            $bankid = input('bankid/d');
            $type = input('type/d');
            $paytype = input('paytype/s');
            $min_num = input('min_num/d');

            if (!userid()) {
                $this->error('您没有登录请先登录！');
            }
//            if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
//                $this->error('出现未知错误！');
//            }
            if (!check($num, 'double')) {
                $this->error('挂单数量格式错误');
            }
            if (empty($bankid)) {
                $this->error('请选择银行卡！');
            }
            if ($num < 100 ) {
                $this->error('交易的数量最少100！');
            }
            if ($num > 50000 ) {
                $this->error('单笔交易数量最多50000！');
            }
            if($num%100 != 0){
                $this->error('交易数量必须是100的整数倍');
            }
            if (!check($num, 'double')) {
                $this->error('挂单数量格式错误');
            }
            //判断 未付款买或卖订单，不能继续买卖
            $usertype = Db::name('User')->where(['id' => userid()])->find();
            if ($usertype['usertype'] != 1) {
                $count = Db::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' =>array('in',[0,3])])->count();
                if ($count >= 1 && $type == 1) {
                    $this->error('您有1条未处理的买单');
                }
                $count = Db::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 2, 'status' => array('in',[0,3])])->count();
                if ($count >= 1 && $type == 2) {
                    $this->error('您有1条未处理的卖单');
                }
            }
            if (!$usertype['moble']){
                $this->error('请先认证手机');
            }

            $bank = Db::name('UserBank')->where(['id' => $bankid])->value('bank');
            if ($bank === '支付宝' && $paytype !== '支付宝') {
                $this->error('您选择的银行卡和支付方式不匹配');
            } elseif ($bank !== '支付宝' && $paytype === '支付宝') {
                $this->error('您选择的银行卡和支付方式不匹配');
            }

            //支付宝限额提示
            if ($num > 50000 && $paytype === '支付宝') {
                $this->error('您购买的数量过大，请选择相匹配的支付方式');
            }

            //获取验证码
            for (; true;) {
                $tradeno = tradenoc();
                if (!Db::name('UserC2cTrade')->where(array('tradeno' => $tradeno))->find()) {
                    break;
                }
            }
            $endtime = time() + 1800;

            //匹配成功后撤销频繁用户当天不允许交易
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $time = time();
            if($usertype != 1 || $usertype != 9){
                $chexiao_num = Db::name('UserC2cTrade')->where(['userid' => userid(),'type' => 1, 'status' => 2, 'endtime' => array('between', "$beginToday,$time"),'businessid' => array('neq',0),'czr'=>''])->count();
                if (!config('app_is_local') && $chexiao_num >= 2) {
                    $this->error('因当日撤销操作过于频繁，已经暂停您与商户之间的交易。');
                }
            }
//            买单
            if ($type == 1) {
                $buy_paypassword = input('buy_paypassword/s');
                if (!check($buy_paypassword, 'password')) {
                    $this->error('交易密码格式错误！');
                }
                $user = Db::name('User')->where(array('id' => userid()))->find();
                if (md5($buy_paypassword) != $user['paypassword']) {
                    $this->error('交易密码错误！');
                }
                //用户未付款点击已付款，只允许每个用户存在两笔这样的订单
                $two = Db::name('UserC2cTrade')->where(['userid' => userid(),'status' => 3])->count();
                if ($user['usertype'] == 0  || $user['usertype'] == 9){
                    if ($two - 2 >= 0) {
                        $this->error('您还有未完成交易的订单，请完成交易或者撤销订单后在进行交易');
                    }
                }

                $rs = Db::name('UserC2cTrade')->insert([
                    'userid' => userid(),
                    'coin' => $coin,
                    'price' => $this->currency[$coin]['buy_price'],
                    'num' => $num,
                    'mum' => $this->currency[$coin]['buy_price'] * $num,
                    'type' => 1,
                    'addtime' => time(),
                    'timeend' => $endtime,
                    'bankid' => $bankid,
                    'tradeno' => $tradeno,
                    'reminder_type' => 1,
                    'paytype' => $paytype,
                    'status' => 0,
                    'order'  => 0
                ]);
                if ($rs) {
                    $this->c2cmarket($tradeno);
                    $this->success('挂单成功！');
                } else {
                    $this->error('挂单失败！');
                }
            }

//            卖单
            if ($type == 2) {
                $sell_paypassword = input('sell_paypassword/s');
                if (!check($sell_paypassword, 'password')) {
                    $this->error('交易密码格式错误！');
                }
                $user = Db::name('User')->where(array('id' => userid()))->find();
                if (md5($sell_paypassword) != $user['paypassword']) {
                    $this->error('交易密码错误！');
                }

                if ($user['idcardauth'] != 1){
                    $this->error('您还没有实名认证无法卖出！');
                }
                if ($min_num > $num){
                    $this->error('最小限额不可以大于挂单数量');
                }
                if ($min_num < 100){
                    $this->error('最小限额不可以小于100');
                }
                if (!check($min_num, 'double')) {
                    $this->error('最小匹配数量格式不正确');
                }
                if ($min_num%100 != 0) {
                    $this->error('最小匹配数量必须是100的整数倍');
                }
                //对提现用户进行财产审核
                if ($usertype['usertype'] != 1 && $usertype['usertype'] != 9){
                    $user_actual_finance = round(Db::name('UserCoin')->where(['userid' => userid()])->sum('hkd + hkdd'),2);//用户实际财产
                    $user_tx = Db::name('Mytx')->where(['userid' => userid(), 'status' => 1])->sum('num');//用户提现hkd
                    $user_c2c_tx = Db::name('UserC2cLog')->where(['sellid' => userid(),'order' => 0,'status' =>1])->sum('num * 1.005') + Db::name('UserC2cLog')->where(['sellid' => userid(),'order' => 1,'status' =>1])->sum('num * 1.01');//用户c2c提现hkd
                    $user_buy = Db::name('TradeLog')->where(['userid' => userid()])->sum('mum');//用户买入
                    $user_buy_fee = Db::name('TradeLog')->where(['userid' => userid()])->sum('fee_buy');//用户买入手续费
                    $user_sell_fee = Db::name('TradeLog')->where(['peerid' =>  userid()])->sum('fee_buy');//用户卖出手续费
                    $user_pay_hkd = $user_tx + $user_c2c_tx + $user_buy + $user_buy_fee + $user_sell_fee ;//用户支出总计

                    $user_c2c_cz = Db::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' => 1])->sum('price * num');//用户c2c充值
                    $user_cz_1 = Db::name('Mycz')->where(['userid' => userid(), 'status' => 1])->sum('num');//用户充值
                    $user_cz_2 = Db::name('Mycz')->where(['userid' => userid(), 'status' => 2])->sum('num');//用户充值
                    $user_cz_5 = Db::name('Mycz')->where(['userid' => userid(), 'status' => 5])->sum('mum');//用户充值
                    $user_sell = Db::name('TradeLog')->where(['peerid' => userid()])->sum('mum');//用户卖出
                    $user_invit =Db::name('Invit')->where(['userid' => userid()])->sum('fee');
                    $user_income_hkd = $user_c2c_cz + $user_cz_1 + $user_cz_2 + $user_cz_5 + $user_sell + $user_invit;//用户总收入
                    $user_predict_hkd = round($user_income_hkd - $user_pay_hkd,2);
                    if ($user_predict_hkd - $user_actual_finance < 0){
                        $this->error('您的账号存在异常，请提交工单进行处理');
                    }
                }

                $fee = $num * 0.005 < 5 ? 5 : $num *0.005;
                $total = $num + $fee;
                $user_total = Db::name('UserCoin')->where(['userid' => userid()])->value($coin);
                if ($total > $user_total) {
                    $this->error('您的币种余额不足！');
                }

                //减少用户余额，进行卖出

                Db::startTrans();
                try {
                    $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => userid()))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
                    $rs[] = Db::table('weike_user_coin')->where(['userid' => userid()])->setDec($coin, $total);
                    $rs[] = $finance_nameid = Db::table('weike_user_c2c_trade')->insert([
                        'userid' => userid(),
                        'coin' => $coin,
                        'price' => $this->currency[$coin]['sell_price'],
                        'num' => $num,
                        'min_num' => $min_num,
                        'mum' => $this->currency[$coin]['sell_price'] * $num,
                        'type' => 2,
                        'addtime' => time(),
                        'timeend' => $endtime,
                        'bankid' => $bankid,
                        'tradeno' => $tradeno,
                        'reminder_type' => 1,
                        'paytype' => $paytype,
                        'status' => 0,
                        'order' => 0
                    ]);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => userid()))->find();
                    $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $total . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];
                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }
                    $rs[] = Db::table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $num, 'type' => 2, 'name' => 'c2c', 'nameid' => $finance_nameid, 'remark' => '点对点交易-卖出提现', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    if (check_arr($rs)) {
                        Db::commit();
                        $this->c2cmarket($tradeno);
                        $this->success('挂单成功！');
                    } else {
                        Db::rollback();
                        $this->error('挂单失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('挂单失败！');
                }
            }
        }
    }



    //撮合买卖
    public function c2cmarket($tradeno)
    {

        Db::startTrans();

        $trade = Db::name('UserC2cTrade')->lock(true)->where(['tradeno' => $tradeno,'businessid' => 0])->find();
        if (!$trade) {
            Db::rollback();
            $this->error('订单已成功匹配');
        }

        if ($trade['type'] == 1) {
            $sell = Db::name('user_c2c_trade')->lock(true)->whereExp('num',">= (`deal`+".$trade['num'].")")->where(['min_num'=>array('elt',$trade['num']),'status' => 0, 'type' => 2, 'businessid' => 0, 'order' => 0, 'paytype' => $trade['paytype']])->order('id asc')->find();
            //先找客户，没客户，再找商家匹配
            if (!$sell) {//客户对商家
                if ($trade['paytype'] == '支付宝'){
                    $btc_sj = Db::name('UserC2c')->where(['status' => 1 ,'type' => 2,'bankaddr'=>'支付宝','deal'=>array('gt',$trade['num'])])->order('deal desc')->find();
                }else{
                    $btc_sj = Db::name('UserC2c')->where(['status' => 1 ,'type' => 2,'bankaddr'=>array('neq','支付宝'),'deal'=>array('gt',$trade['num'])])->order('deal desc')->find();
                }
                if ($btc_sj){
                    try{
                        $buybank = Db::name('UserBank')->field('bank,bankcard')-where(['id' => $trade['bankid']])->find();
                        $buyuser = Db::name('User')->field('truename,moble')->where(['id' => $trade['userid']])->find();
                        $rs = [];
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $tradeno])->update(['order' => 1,'matchtime'=>time()]);
                        $rs[] = Db::table('weike_user_c2c')->where(['id' => $btc_sj['id']])->setDec('deal',$trade['num']);
                        $rs[] = Db::table('weike_user_c2c_log')
                            ->insert([
                                'buyid' => userid(),
                                'sellid' => $btc_sj['id'],
                                'coinname' => $trade['coin'],
                                'price' => $trade['price'],
                                'num' => $trade['num'],
                                'buytruename' => $buyuser['truename'],
                                'buybank' => $buybank['bank'],
                                'buybankcard' => $buybank['bankcard'],
                                'buymoble' => $buyuser['moble'],
                                'buytradeno' => $tradeno,
                                'selltruename' => $btc_sj['name'],
                                'sellbank' => $btc_sj['bankaddr'],
                                'sellbankcard' => $btc_sj['bankcard'],
                                'sellmoble' => $btc_sj['moble'],
                                'selltradeno' => $tradeno,
                                'addtime' => time(),
                                'type' => 1,
                                'status' => 0,
                                'order'   => 1
                            ]);
                        if (check_arr($rs)) {
                            Db::commit();
                            $this->success('订单匹配成功，请向商家' . $btc_sj['name'] . '付款，完成交易');
                        } else {
                            Db::rollback();
                            $this->error('挂单成功！');
                        }

                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('挂单成功！');
                    }

                }
            }else{ //客户对这客户
                $buybank = Db::name('UserC2cTrade')
                    ->join('weike_user_bank', 'weike_user_c2c_trade.bankid = weike_user_bank.id')
                    ->join('weike_user', 'weike_user_c2c_trade.userid = weike_user.id')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user_c2c_trade.userid' => userid(), 'weike_user_c2c_trade.tradeno' => $tradeno, 'weike_user_c2c_trade.status' => 0])
                    ->find();
                $sellbank = Db::name('User')
                    ->join('weike_user_bank', 'weike_user.id = weike_user_bank.userid')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user.id' => $sell['userid'], 'weike_user_bank.id' => $sell['bankid']])
                    ->find();

                $sell_num = $sell['num']-$sell['deal'];
                $amount = min($trade['num'],$sell_num);
                try{
                    $rs = [];
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['userid' => $sell['userid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->order('id desc')->update(['businessid' => $trade['bankid'], 'matchtime' => time()]);
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['userid' => userid(), 'type' => 1, 'status' => 0, 'num' => $trade['num'], 'tradeno' => $tradeno])->update(['businessid' => $sell['bankid'], 'matchtime' => time()]);
                    $rs[] = Db::table('weike_user_c2c_log')->insert([
                        'buyid' => userid(),
                        'sellid' => $sell['userid'],
                        'coinname' => $trade['coin'],
                        'price' => $trade['price'],
                        'num' => $amount,
                        'buytruename' => $buybank['truename'],
                        'buybank' => $buybank['bank'],
                        'buybankcard' => $buybank['bankcard'],
                        'buymoble' => $buybank['moble'],
                        'buytradeno' => $tradeno,
                        'selltruename' => $sellbank['truename'],
                        'sellbank' => $sellbank['bank'],
                        'sellbankcard' => $sellbank['bankcard'],
                        'sellmoble' => $sellbank['moble'],
                        'selltradeno' => $sell['tradeno'],
                        'addtime' => time(),
                        'type' => 2,
                        'status' => 0,
                        'order'   => 0
                    ]);

                    if (check_arr($rs)) {
                        $message1 = '尊敬的国际交易所用户，您的点对点买单'.$tradeno.',成功匹配金额'.$amount.'，请及时向卖方打款，打款后请点击“我已付款”按钮；此短信由平台发出，并非成功打款短信。';
                        $message2 = '尊敬的国际交易所用户，您的点对点卖单'.$sell['tradeno'].',成功匹配金额'.$amount.'，请收到款后及时登入平台点击“确认收款”按钮完成交易；此短信由平台发出，并非成功收款短信。';
                        send_moble($buybank['moble'], config('web_name'), $message1, true);
                        send_moble($sellbank['moble'], config('web_name'), $message2, true);
                        Db::commit();
                        $this->success('订单匹配成功，请向商家' . $sellbank['truename'] . '付款，完成交易');
                    } else {
                        Db::rollback();
                        $this->error('挂单成功！');
                    }

                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('挂单成功！');
                }
            }
        } else if ($trade['type'] == 2) {
            $small = $trade['min_num'];
            $big = $trade['num'] - $trade['deal'];

            $buy = Db::name('UserC2cTrade')->lock(true)->where(['num' => array('between',"$small,$big" ), 'status' => 0, 'order' => 0 ,'type' => 1, 'businessid' => 0, 'paytype' => $trade['paytype']])->order('num desc, id asc')->find();
            $c2c = Db::name('UserC2cLog')->where(['buytradeno'=>$buy['tradeno']])->find();
            if ($c2c){
                Db::rollback();
                $this->error('挂单成功！');
            }
            if ($buy) {
                $sellbank = Db::name('UserC2cTrade')
                    ->join('weike_user_bank', 'weike_user_c2c_trade.bankid = weike_user_bank.id')
                    ->join('weike_user', 'weike_user_c2c_trade.userid = weike_user.id')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user_c2c_trade.userid' => userid(), 'weike_user_c2c_trade.tradeno' => $tradeno, 'weike_user_c2c_trade.status' => 0])
                    ->find();
                $buybank = Db::name('User')
                    ->join('weike_user_bank', 'weike_user.id = weike_user_bank.userid')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user.id' => $buy['userid'], 'weike_user_bank.id' => $buy['bankid']])
                    ->find();
                $buy_num = $buy['num'] - $buy['deal'];
                $sell_num = $trade['num'] - $trade['deal'];
                $amount = min($sell_num, $buy_num);
                try{
                    $rs = [];
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['userid' => userid(), 'tradeno' => $tradeno, 'status' => 0,])->order('id desc')->update(['businessid' => $buy['bankid'], 'matchtime' => time()]);
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['userid' => $buy['userid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['businessid' => $trade['bankid'], 'matchtime' => time()]);
                    $rs[] = Db::table('weike_user_c2c_log')->insert([
                        'buyid' => $buy['userid'],
                        'sellid' => userid(),
                        'coinname' => $trade['coin'],
                        'price' => $trade['price'],
                        'num' => $amount,
                        'buytruename' => $buybank['truename'],
                        'buybank' => $buybank['bank'],
                        'buybankcard' => $buybank['bankcard'],
                        'buymoble' => $buybank['moble'],
                        'buytradeno' => $buy['tradeno'],
                        'selltruename' => $sellbank['truename'],
                        'sellbank' => $sellbank['bank'],
                        'sellbankcard' => $sellbank['bankcard'],
                        'sellmoble' => $sellbank['moble'],
                        'selltradeno' => $tradeno,
                        'addtime' => time(),
                        'type' => 1,
                        'status' => 0,
                        'order' => 0
                    ]);

                    if (check_arr($rs)) {
                        if (strlen($buybank['moble']) == 11) {
                            $buybank['moble'] = '+86' . $buybank['moble'];
                        }
                        if (strlen($sellbank['moble']) == 11) {
                            $sellbank['moble'] = '+86' . $sellbank['moble'];
                        }
                        if (empty($buybank['moble'])) {
                            $this->error('买家还没有通过手机认证');
                        }
                        if (empty($sellbank['moble'])) {
                            $this->error('卖还没有通过手机认证');
                        }

                        $message1 = '尊敬的国际交易所用户，您的点对点买单' . $buy['tradeno'] . '，成功匹配金额' . $amount . '，请及时向卖方打款，转账后请点击“我已付款”按钮；此短信由平台发出，并非成功打款短信。';
                        $message2 = '尊敬的国际交易所用户，您的点对点卖单' . $tradeno . '成功匹配金额' . $amount . '，请收到款后及时登入平台点击“确认收款”按钮完成交易；此短信由平台发出，并非成功收款短信。';
                        send_moble($buybank['moble'], config('web_name'), $message1, true);
                        send_moble($sellbank['moble'], config('web_name'), $message2, true);
                        Db::commit();
                        $this->success('订单匹配成功，收到商户' . $buybank['truename'] . '付款后，请点击"确认收款"');
                    } else {
                        Db::rollback();
                        $this->error('挂单成功！');
                    }

                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('挂单成功！');
                }
            }
        } else {
                $this->error('交易类型不存在');
        }


    }

    //展示弹窗
    public function alert_tip()
    {
        if (IS_AJAX) {
            $id = input('id/d');
            $trade = Db::name('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['order'] == 1){
                if ($trade['status'] == 0) {
                    $status = '交易中';
                } else if ($trade['status'] == 3) {
                    $status = '已支付';
                } else if ($trade['status'] == 2) {
                    $status = '已撤销';
                } else if ($trade['status']) {
                    $status = '已成交';
                }
                if ($trade['type'] == 1){
                    $c2c_log = Db::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                    $sj_bank = Db::name('UserC2c')->where(['id' => $c2c_log['sellid']])->find();
                    echo json_encode([
                        'sts' => 1,
                        'name' => $sj_bank['name'],
                        'bankaddr' => $sj_bank['bankaddr'],
                        'bankcard' => $sj_bank['bankcard'],
                        'image' => $sj_bank['image'],
                        'num' => $trade['num'],
                        'tradeno' => $trade['tradeno'],
                        'type' => $trade['type'],
                        'status' => $status,
                        'moble' => $sj_bank['moble'],
                        'businessid' => $trade['businessid'],
                        'matchtime' => $trade['matchtime'],
                        'paytype' => $trade['paytype'],
                    ]);
                    exit();
                }else{
                    $c2c_log = Db::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->find();
                    $sj_bank = Db::name('UserC2c')->where(['id' => $c2c_log['buyid']])->find();
                    echo json_encode([
                        'sts' => 1,
                        'name' => $sj_bank['name'],
                        'bankaddr' => $sj_bank['bankaddr'],
                        'bankcard' => $sj_bank['bankcard'],
                        'image' => $sj_bank['image'],
                        'num' => $trade['num'],
                        'tradeno' => $trade['tradeno'],
                        'type' => $trade['type'],
                        'status' => $status,
                        'moble' => $sj_bank['moble'],
                        'businessid' => $trade['businessid'],
                        'matchtime' => $trade['matchtime'],
                        'paytype' => $trade['paytype'],
                    ]);
                    exit();
                }
            }else{
                if ($trade['type'] == 3 || $trade['type'] == 4) {
                    $bank = Db::name('UserC2c')->where(['id' => $trade['businessid']])->find();
                } else {
                    $bank = Db::name('UserBank')->where(['id' => $trade['businessid']])->find();
                    $bank['name'] = Db::name('User')->where(['id' => $bank['userid']])->value('truename');
                }
                if ($trade['type'] == 1){
                    $num = Db::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->order('id desc')->value('num');
                }else{
                    $num = Db::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->order('id desc')->value('num');
                }
                //选择状态
                if ($trade['status'] == 0) {
                    $status = '交易中';
                } else if ($trade['status'] == 3) {
                    $status = '已支付';
                } else if ($trade['status'] == 2) {
                    $status = '已撤销';
                } else if ($trade['status']) {
                    $status = '已成交';
                }
                if ($trade['type'] == 1) {
                    $data = Db::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->order('id desc')->find();
                    $tradeno = $data['selltradeno'];
                    $moble = $data['sellmoble'];
                } else {
                    $data = Db::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->order('id desc')->find();
                    $tradeno = $trade['tradeno'];
                    $moble = $data['buymoble'];
                }

                echo json_encode([
                    'sts' => 1,
                    'name' => $bank['name'],
                    'bankaddr' => isset($bank['bank'])?$bank['bank']:'',
                    'bankcard' => isset($bank['bankcard'])?$bank['bankcard']:'',
                    'num' => $num,
                    'tradeno' => $tradeno,
                    'type' => $trade['type'],
                    'status' => $status,
                    'moble' => $moble,
                    'businessid' => $trade['businessid'],
                    'matchtime' => $trade['matchtime'],
                    'paytype' => $trade['paytype'],
                ]);
                exit;
            }

        }
    }

    //已付款
    public function pay()
    {
        if (IS_AJAX) {
            //token验证
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $id = input('id/d');
            //修改订单状态
            Db::startTrans();
            try {
                $trade = Db::name('UserC2cTrade')->lock(true)->where(['id' => $id])->find();
                if ($trade['status'] != 0) {
                    Db::rollback();
                    $this->error('订单已经处理过！');
                }

                if ($trade['order'] == 0) {
                    $rs = [];
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                    $rs[] = Db::table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 3]);
                    $sell = Db::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $sell['selltradeno']])->update(['status' => 3]);

                    if (check_arr($rs)) {
                        $sellmoble = $sell['sellmoble'];
                        $message = '尊敬的国际交易所用户，您的点对点卖单' . $sell['selltradeno'] . '，成功匹配金额' . $sell['num'] . '，买家已经“确认付款”，请收到款项后及时登陆平台点击“确认收款”按钮完成交易。';
                        send_moble($sellmoble, config('web_name'), $message,true);
                        Db::commit();
                        $this->success('我已付款');
                    } else {
                        Db::rollback();
                        $this->error('付款失败！');
                    }
                } else {
                    $rs = [];
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                    $rs[] = Db::table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 3]);
                    if (check_arr($rs)) {
                        Db::commit();
                        $this->success('我已付款');
                    } else {
                        Db::rollback();
                        $this->error('付款失败！');
                    }
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('付款失败！');
            }
        }
    }

    //催单
    public function reminder()
    {
        if (IS_AJAX) {
            //token验证
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $id = input('id/d');
            //修改订单状态
            $trade = Db::name('UserC2cTrade')->where(['id' => $id])->find();
            //判断是卖家还是买家催单
            if ($trade['type'] == 1) {
                $sell = Db::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                $reminder_type = Db::name('UserC2cTrade')->where(['id' => $id])->update(['reminder_type' => 0]);
                if ($reminder_type) {
                    $sellmoble = $sell['sellmoble'];
                    $message1 = '尊敬的国际交易所用户，您的点对点卖单'.$sell['selltradeno'].'，成功匹配金额'.$sell['num'].'，买家已经“确认付款”，请收到款项后及时登陆平台点击“确认收款”按钮完成交易。';
                    send_moble($sellmoble, config('web_name'), $message1,true);
                    $this->success('催单成功，请稍候');
                } else {
                    $this->error('催单失败，请联系客服人员');
                }
            } else {
                $buy = Db::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->find();
                $reminder_type = Db::name('UserC2cTrade')->where(['id' => $id])->update(['reminder_type' => 0]);
                if ($reminder_type) {
                    $title = config('web_name');
                    $buymoble = $buy['buymoble'];
                    $message2 = '尊敬的国际交易所用户，您的买单'.$buy['buytradeno'].'成功匹配金额'.$buy['num'].'，若已经打款请点击“我已付款”按钮，未付款请及时向卖方账户打款或进行撤销。';
                    send_moble($buymoble, config('web_name'), $message2, true);
                    $this->success('催单成功，请稍候');
                } else {
                    $this->error('催单失败，请联系客服人员');
                }
            }
        }
    }

    //确认收款
    public function confirm()
    {
        if (IS_AJAX) {
            //token验证
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $id = input('id/d');
            $trade = Db::name('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['businessid'] == 0 && $trade['status'] ==0){
                $this->error('订单正在匹配！');
            }

            $c2c_log = Db::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->order('id desc')->find();
            //判断是用户之间的交易还是用户和系统之间的交易
            Db::startTrans();
            if ($trade['order'] == 1) {
                try {
                    $rs = [];
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 1, 'endtime' => time()]);
                    $rs[] = Db::table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno']])->update(['status' => 1, 'endtime' => time()]);
                    if (check_arr($rs)) {
                        Db::commit();
                        $this->success('卖出成功！');
                    } else {
                        Db::rollback();
                        $this->error('卖出失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('卖出失败！');
                }
            } else {
                //修改订单状态
                try {
                    $trade = Db::name('UserC2cTrade')->lock(true)->where(['id' => $id])->find();
                    if ($trade['businessid'] == 0 && $trade['status'] == 0) {
                        Db::rollback();
                        $this->error('订单已确认收款，不可以重复操作！');
                    } elseif ($trade['businessid'] != 0 && $trade['status'] == 1) {
                        Db::rollback();
                        $this->error('订单已成交，不可以重复操作！');
                    }

                    $buy = Db::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->order('id desc')->find();
                    $buyid = Db::name('UserC2cTrade')->where(['tradeno' => $buy['buytradeno']])->value('id');
                    $rs = [];
                    $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $buy['buyid']))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $buy['buyid']))->find();
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->setInc('deal', $c2c_log['num']);
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->setInc('deal', $c2c_log['num']);
                    $rs[] = $deal = Db::table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->value('deal');
                    if ($trade['num'] - $deal == 0) {
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['status' => 1, 'endtime' => time()]);
                    } else {
                        if ($trade['num'] - $deal > 100 && $trade['num'] - $deal > $trade['min_num']) {
                            $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0, 'status' => 0]);
                        } else if ($trade['num'] - $deal > 100 && $trade['num'] - $deal <= $trade['min_num']) {
                            $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0, 'status' => 0, 'min_num' => $trade['num'] - $deal]);
                        } else if ($trade['num'] - $deal <= 100 && $trade['num'] - $deal <= $trade['min_num']) {
                            $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0, 'status' => 0, 'min_num' => $trade['num'] - $deal]);
                        }
                    }
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->update(['status' => 1, 'endtime' => time()]);
                    $rs[] = Db::table('weike_user_coin')->where(['userid' => $buy['buyid']])->setInc('hkd', $c2c_log['num']);
                    $rs[] = Db::table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno']])->update(['status' => 1, 'endtime' => time()]);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                    $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }
                    $rs[] = Db::table('weike_finance')->insert(array('userid' => $buy['buyid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $c2c_log['num'], 'type' => 1, 'name' => 'c2c', 'nameid' => $buyid, 'remark' => '点对点交易-买入交易', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                    if (check_arr($rs)) {
                        Db::commit();
                        $this->success('卖出成功！');
                    } else {
                        Db::rollback();
                        $this->error('卖出失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('卖出失败！');
                }
            }
        }
    }

    /**
     * 撤单
     * 没有匹配的单撤销直接改状态为2
     * 对有匹配成功的单，买卖双方都会设为状态为2，资财回退
     */
    public function c2cchexiao()
    {
        if (IS_AJAX) {
            //token验证
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $id = input('id/d');
            Db::startTrans();
            $trade = Db::table('weike_user_c2c_trade')->lock(true)->where(['id' => $id])->find();
            if ($trade['status'] != 0) {
                Db::rollback();
                $this->error('订单已经处理过！');
            }
           // $usertype = Db::name('User')->where(['id' => userid()])->value('usertype');
            if ($trade['order'] == 0){
                if ($trade['type'] == 1) {
                    if ($trade['businessid'] == 0) {
                        $chage_status = Db::name('UserC2cTrade')->where(['id' => $id])->update(['status' => 2]);
                        if ($chage_status) {
                            Db::commit();
                            $this->success('撤单成功');
                        } else {
                            Db::rollback();
                            $this->error('撤单失败，请联系客服人员');
                        }
                    } else {
                        $data = Db::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                        $sell = Db::name('UserC2cTrade')->where(['tradeno'=>$data['selltradeno']])->find();
                        $sell_num = $sell['num'] - $sell['deal'];
                        if ($sell['deal'] == 0){
                            $fee = $sell['num']* 0.005 < 5 ? 5 : $sell['num']* 0.005;
                        }else{
                            if ($sell['num']* 0.005 <= 5){
                                $fee = 5 - ($sell['deal'] *0.005);
                            }else{
                                $fee = ($sell['num']* 0.005 < 5 ? 5 : $sell['num']* 0.005) - ($sell['deal'] * 0.005 < 5 ? 5 : $sell['deal'] * 0.005);
                            }

                        }
                        $total = $sell_num + $fee;
                        $rs = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $data['sellid']))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $data['sellid']))->find();
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id,'status' => 0])->update(['status' => 2]);
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $data['selltradeno'],'status' => 0])->update(['status' => 2]);
                        $rs[] = Db::table('weike_user_coin')->where(['userid' => $data['sellid']])->setInc('hkd', $total);
                        $rs[] = Db::table('weike_user_c2c_log')->where(['id' => $data['id'],'status' => 0])->update(['status' => 2]);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $data['sellid']))->find();
                        $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }

                        $rs[] = Db::table('weike_finance')->insert(array('userid' => $data['sellid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $sell['id'], 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        if (check_arr($rs)) {
                            $sellmoble = $data['sellmoble'];
                            $message = '尊敬的国际交易所用户，您的点对点卖单'.$data['selltradeno'].'，成功匹配金额'.$data['num'].'，买家撤销了订单，请您重新下单。';
                            send_moble($sellmoble, config('web_name'), $message, true);
                            Db::commit();
                            $this->success('撤单成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤单失败！');
                        }
                    }
                } else {
                    //卖家未匹配撤销订单
                    if ($trade['businessid'] != 0) {
                        Db::rollback();
                        $this->error('订单已匹配成功，无法撤单');
                    }
                    $sell_num = $trade['num'] - $trade['deal'];
                    if ($trade['deal'] == 0){
                        $fee = $trade['num'] * 0.005 < 5 ? 5 : $trade['num']* 0.005;
                    }else{
                        if ($trade['num'] * 0.005 <= 5){
                            $fee = 5 - ($trade['deal'] * 0.005);
                        }else{
                            $fee = ($trade['num'] * 0.005) - ($trade['deal'] * 0.005 <= 5 ? 5 : $trade['deal'] * 0.005);
                        }

                    }
                    $total = $sell_num + $fee;
                    $rs = [];
                    $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => userid()))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2]);
                    $rs[] = Db::table('weike_user_coin')->where(['userid' => $trade['userid']])->setInc('hkd', $total);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => userid()))->find();
                    $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                     $rs[] = Db::table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $sell_num, 'type' => 2, 'name' => 'c2c', 'nameid' => $id, 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                    if (check_arr($rs)) {
                        Db::commit();
                        $this->success('撤单成功！');
                    } else {
                        Db::rollback();
                        $this->error('撤单失败！');
                    }
                }
            }else if($trade['order'] == 1){
                if ($trade['type'] == 1){
                    $c2c_log = Db::table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->find();
                    $rs = [];
                    $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2]);
                    $rs[] = Db::table('weike_user_c2c')->where(['id' => $c2c_log['sellid']])->setInc('deal',$trade['num']);
                    $rs[] = Db::table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 2]);
                    if (check_arr($rs)) {
                        Db::commit();
                        $this->success('撤单成功！');
                    } else {
                        Db::rollback();
                        $this->error('撤单失败！');
                    }
                }
            }else{
                Db::rollback();
                $this->error('订单类型不存在');
            }
        }
    }

    //手动撮合买卖
    public function hand_trade(){
//        $this->error('点对点交易已关闭');
        if (IS_POST){
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $num     = input('num/d');
            $coin    = input('coin/s');
            $type    = input('type/d');
            $card_id = input('card_id/d');
            $paytype = input('paytype/s');
            $paypwd  = input('paypwd/s');
            $id      = input('id/d');
            $order   = input('order/s');

            if (!userid()) {
                $this->error('您没有登录请先登录！');
            }
            if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
                $this->error('出现未知错误！');
            }
            if (empty($card_id)) {
                $this->error('请选择账号！');
            }
            if ($num < 100 ) {
                $this->error('交易的数量最少100！');
            }
            if ($num > 50000 ) {
                $this->error('单笔交易数量最多50000！');
            }
            if($num%100 != 0){
                $this->error('交易数量必须是100的整数倍');
            }
            if (!check($num, 'double')) {
                $this->error('挂单数量格式错误');
            }
            $bank = Db::name('UserBank')->where(['id' => $card_id])->find();
            if ($bank['bank'] === '支付宝' && $paytype !== '支付宝') {
                $this->error('您选择的账号和支付方式不匹配');
            } elseif ($bank['bank'] !== '支付宝' && $paytype === '支付宝') {
                $this->error('您选择的账号和支付方式不匹配');
            }
            $usertype = Db::name('User')->where(['id' => userid()])->find();
            if (!check($paypwd, 'password')) {
                $this->error('交易密码格式错误！');
            }
            $paypwd = md5($paypwd);
            if ($paypwd != $usertype['paypassword']) {
                $this->error('交易密码错误！');
            }
            if ($usertype['usertype'] != 1) {
                $count = Db::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' => array('in',[0,3])])->count();
                if ($count >= 1 && $type == 1) {
                    $this->error('您有1条未处理的买单');
                }
                $count = Db::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 2, 'status' => array('in',[0,3])])->count();
                if ($count >= 1 && $type == 2) {
                    $this->error('您有1条未处理的卖单');
                }
            }
            if ($num > 50000 && $paytype === '支付宝') {
                $this->error('您购买的数量过大，请选择相匹配的支付方式');
            }

            //获取验证码
            for (; true;) {
                $tradeno = tradenoc();
                if (!Db::name('UserC2cTrade')->where(array('tradeno' => $tradeno))->find()) {
                    break;
                }
            }
//            $mstrat =mktime(0,0,0,date('m'),1,date('Y'));
//           $mend =mktime(23,59,59,date('m'),date('t'),date('Y'));
            $time = time();
//            $cxdd = Db::name('UserC2cTrade')->where(['order' => 1,'status' => 2,'endtime' => array('between',"$mstrat,$time"),'userid' => userid()])->count();
//           if ($cxdd >= 3){
//               $this->error('因当月撤销操作过于频繁，已经暂停您与商家的交易，请使用商户匹配系统进行交易。');
//           }
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $chexiao_num = Db::name('UserC2cTrade')->where(['userid' => userid(),'type' => 1, 'status' => 2, 'endtime' => array('between', "$beginToday,$time"),'businessid' => array('neq',0),'czr'=>''])->count();
            if (!config('app_is_local') && $chexiao_num >= 2){
                $this->error('因当日撤销操作过于频繁，已经暂停您与商家之间的交易。');
            }

            Db::startTrans();
            if ($order == 'user'){
                if ($type == 1){
                    $sell = Db::name('UserC2cTrade')->lock(true)->where(['id' => $id,'businessid' => 0])->find();
                    if (!$sell){
                        Db::rollback();
                        $this->error('订单已匹配，请选择其他订单交易');
                    }
                    $sell_user = Db::name('User')->where(['id' => $sell['userid']])->find();
                    $sell_bank = Db::name('UserBank')->where(['id' => $sell['bankid']])->find();
                    $min_num = $sell['min_num'];
                    $sy_num = $sell['num'] - $sell['deal'];
                    if ($sy_num - $min_num >= 0){
                        if ($num - $min_num < 0){
                            Db::rollback();
                            $this->error('交易数量不得低于最小匹配数量'.$min_num);
                        }
                        if ($num > $sy_num){
                            Db::rollback();
                            $this->error('交易数量大于卖方的剩余可交易数量，无法交易');
                        }
                    }else{
                        if ($num > $sy_num ){
                            Db::rollback();
                            $this->error('交易数量大于卖方的剩余可交易数量，无法交易');
                        }
                    }
                    if ($paytype != $sell['paytype']){
                        Db::rollback();
                        $this->error('你选择的支付方式和卖家不一致，请重新选择');
                    }

                    try {
                        $rs = [];
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['businessid' => $card_id, 'matchtime' => time()]);
                        $rs[]=Db::table('weike_user_c2c_trade')->insert([
                            'userid' => userid(),
                            'coin' => $coin,
                            'price' => $this->currency[$coin]['buy_price'],
                            'num' => $num,
                            'mum' => $this->currency[$coin]['buy_price'] * $num,
                            'type' => 1,
                            'addtime' => time(),
                            'matchtime' => time(),
                            'businessid' => $sell['bankid'],
                            'bankid' => $card_id,
                            'tradeno' => $tradeno,
                            'paytype' => $paytype,
                            'status' => 0,
                            'order' => 0
                        ]);
                        $rs[] = Db::table('weike_user_c2c_log')->insert([
                            'buyid' => userid(),
                            'sellid' => $sell['userid'],
                            'coinname' => $coin,
                            'price' => 1,
                            'num' => $num,
                            'buytruename' => $usertype['truename'],
                            'buybank' => $bank['bank'],
                            'buybankcard' => $bank['bankcard'],
                            'buymoble' => $usertype['moble'],
                            'buytradeno' => $tradeno,
                            'selltruename' => $sell_user['truename'],
                            'sellbank' => $sell_bank['bank'],
                            'sellbankcard' => $sell_bank['bankcard'],
                            'sellmoble' => $sell_user['moble'],
                            'selltradeno' => $sell['tradeno'],
                            'addtime' => time(),
                            'type' => 1,
                            'status' => 0,
                            'order' => 0
                        ]);
                        if (check_arr($rs)) {
                            $message1 = '尊敬的国际交易所用户，您的点对点卖单' . $sell['tradeno'] . ',成功匹配金额' . $num . '，请收到款后及时登入平台点击“确认收款”按钮完成交易；此短信由平台发出，并非成功收款短信。';
                            send_moble($sell_user['moble'], config('web_name'), $message1, true);
                            Db::commit();
                            $this->success('订单匹配成功，请向商家' . $sell_user['truename'] . '付款，完成交易');
                        } else {
                            Db::rollback();
                            $this->error('下单失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('下单失败！');

                    }
                }elseif ($type == 2){
                    $buy = Db::name('UserC2cTrade')->lock(true)->where(['id' => $id,'businessid' => 0])->find();
                    if (!$buy){
                        Db::rollback();
                        $this->error('订单已匹配，请选择其他订单交易');
                    }
                    if ($num < $buy['num']){
                        Db::rollback();
                        $this->error('交易数量不可以小于买方挂单数量');
                    }
                    if ($paytype != $buy['paytype']){
                        Db::rollback();
                        $this->error('你选择的支付方式和买家不一致，请重新选择');
                    }
                    $buy_user = Db::name('User')->where(['id' => $buy['userid']])->find();
                    $buy_bank = Db::name('UserBank')->where(['id' => $buy['bankid']])->find();
                    $fee = $num * 0.005 < 5 ? 5: $num * 0.005;
                    $total = $num + $fee;
                    try {
                        $rs = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => userid()))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
                        $rs[] = Db::table('weike_user_coin')->where(['userid' => userid()])->setDec($coin, $total);
                        $rs[] = $finance_nameid = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['businessid' => $card_id, 'matchtime' => time()]);
                        $rs[] = $finance_nameid = Db::table('weike_user_c2c_trade')->insert([
                            'userid' => userid(),
                            'coin' => $coin,
                            'price' => $this->currency[$coin]['buy_price'],
                            'num' => $num,
                            'mum' => $this->currency[$coin]['buy_price'] * $num,
                            'type' => 2,
                            'addtime' => time(),
                            'matchtime' => time(),
                            'businessid' => $buy['bankid'],
                            'bankid' => $card_id,
                            'tradeno' => $tradeno,
                            'paytype' => $paytype,
                            'status' => 0,
                            'order' => 0
                        ]);
                        $rs[] = Db::table('weike_user_c2c_log')->insert([
                            'buyid' => $buy['userid'],
                            'sellid' => userid(),
                            'coinname' => $coin,
                            'price' => 1,
                            'num' => $num,
                            'buytruename' => $buy_user['truename'],
                            'buybank' => $buy_bank['bank'],
                            'buybankcard' => $buy_bank['bankcard'],
                            'buymoble' => $buy_user['moble'],
                            'buytradeno' => $buy['tradeno'],
                            'selltruename' => $usertype['truename'],
                            'sellbank' => $bank['bank'],
                            'sellbankcard' => $bank['bankcard'],
                            'sellmoble' => $usertype['moble'],
                            'selltradeno' => $tradeno,
                            'addtime' => time(),
                            'type' => 2,
                            'status' => 0,
                            'order' => 0
                        ]);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => userid()))->find();
                        $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $total . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];
                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }
                        $rs[] = Db::table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $finance_nameid, 'remark' => '点对点交易-卖出提现', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        if (check_arr($rs)) {
                            $message1 = '尊敬的国际交易所用户，您的点对点买单' . $buy['tradeno'] . ',成功匹配金额' . $num . '，请及时向卖方打款，打款后请点击“我已付款”按钮；此短信由平台发出，并非成功打款短信。';
                            send_moble($buy_user['moble'], config('web_name'), $message1, true);
                            Db::commit();
                            $this->success('订单匹配成功，请收到商家' . $buy_user['truename'] . '付款后，点击“确认收款”');
                        } else {
                            Db::rollback();
                            $this->error('下单失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('下单失败！');
                    }

                }else{
                    Db::rollback();
                    $this->error('交易类型不存在');
                }
            }else if ($order == 'business'){
                $shop_info = Db::name('UserC2c')->lock(true)->where(['id' => $id])->find();
                if ($paytype == '支付宝' && $shop_info['paytype'] != 1){
                    Db::rollback();
                    $this->error('商家只支持网银收付款');
                }
                if ($paytype == '网银' && $shop_info['paytype'] != 0){
                    Db::rollback();
                    $this->error('商家只支持支付宝收付款');
                }

                if ($shop_info['deal'] < $num){
                    Db::rollback();
                    $this->error('商家可交易数量不足，请选择其他商家');
                }
                if ($type == 1){
                    try {
                        $rs = [];
                        $rs[] = Db::table('weike_user_c2c')->where(['id' => $id])->setDec('deal', $num);
                        $rs[] = Db::table('weike_user_c2c_trade')->insert([
                            'userid' => userid(),
                            'coin' => $coin,
                            'price' => $this->currency[$coin]['buy_price'],
                            'num' => $num,
                            'mum' => $this->currency[$coin]['buy_price'] * $num,
                            'type' => 1,
                            'addtime' => time(),
                            'matchtime' => time(),
                            'bankid' => $card_id,
                            'tradeno' => $tradeno,
                            'paytype' => $paytype,
                            'status' => 0,
                            'order' => 1
                        ]);
                        $rs[] = Db::table('weike_user_c2c_log')->insert([
                            'buyid' => userid(),
                            'sellid' => $id,
                            'coinname' => $coin,
                            'price' => 1,
                            'num' => $num,
                            'buytruename' => $usertype['truename'],
                            'buybank' => $bank['bank'],
                            'buybankcard' => $bank['bankcard'],
                            'buymoble' => $usertype['moble'],
                            'buytradeno' => $tradeno,
                            'selltruename' => $shop_info['name'],
                            'sellbank' => $shop_info['bankaddr'],
                            'sellbankcard' => $shop_info['bankcard'],
                            'sellmoble' => $shop_info['moble'],
                            'selltradeno' => $tradeno,
                            'addtime' => time(),
                            'type' => 1,
                            'status' => 0,
                            'order' => 1
                        ]);
                        if (check_arr($rs)) {
                            Db::commit();
                            $this->success('订单匹配成功，请向商家' . $shop_info['name'] . '付款，完成交易');
                        } else {
                            Db::rollback();
                            $this->error('交易失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('交易失败！');

                    }
                }elseif ($type == 2){
                    $fee = $num * 0.01 < 10 ? 10 : $num *0.01;
                    $total = $num + $fee;
                    $user_total = Db::name('UserCoin')->where(['userid' => userid()])->value($coin);
                    if ($total > $user_total) {
                        Db::rollback();
                        $this->error('您的币种余额不足！');
                    }
                    if ($usertype['usertype'] != 9 && $usertype['usertype'] != 1){
                        $user_actual_finance = round(Db::name('UserCoin')->where(['userid' => userid()])->sum('hkd + hkdd'),2);//用户实际财产
                        $user_tx = Db::name('Mytx')->where(['userid' => userid(), 'status' => 1])->sum('num');//用户提现hkd
                        $user_c2c_tx = Db::name('UserC2cLog')->where(['sellid' => userid(),'type' => 2, 'status' => 1,'order' => 0])->sum('num') + Db::name('UserC2cLog')->where(['sellid' => userid(),'type' => 2, 'status' => 1,'order' => 0])->sum('num') * 0.005 + Db::name('UserC2cLog')->where(['sellid' => userid(),'type' => 2, 'status' => 1,'order' => 1])->sum('num') + Db::name('UserC2cLog')->where(['sellid' => userid(),'type' => 2, 'status' => 1,'order' => 1])->sum('num') * 0.01;//用户c2c提现hkd
                        $user_buy = Db::name('TradeLog')->where(['userid' => userid()])->sum('mum');//用户买入
                        $user_buy_fee = Db::name('TradeLog')->where(['userid' => userid()])->sum('fee_buy');//用户买入手续费
                        $user_sell_fee = Db::name('TradeLog')->where(['peerid' =>  userid()])->sum('fee_buy');//用户卖出手续费
                        $user_pay_hkd = $user_tx + $user_c2c_tx + $user_buy + $user_buy_fee + $user_sell_fee ;//用户支出总计

                        $user_c2c_cz = Db::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' => 1])->sum('price * num');//用户c2c充值
                        $user_cz_1 = Db::name('Mycz')->where(['userid' => userid(), 'status' => 1])->sum('num');//用户充值
                        $user_cz_2 = Db::name('Mycz')->where(['userid' => userid(), 'status' => 2])->sum('num');//用户充值
                        $user_cz_5 = Db::name('Mycz')->where(['userid' => userid(), 'status' => 5])->sum('mum');//用户充值
                        $user_sell = Db::name('TradeLog')->where(['peerid' => userid()])->sum('mum');//用户卖出
                        $user_invit =Db::name('Invit')->where(['userid' => userid()])->sum('fee');
                        $user_income_hkd = $user_c2c_cz + $user_cz_1 + $user_cz_2 + $user_cz_5 + $user_sell + $user_invit;//用户总收入
                        $user_predict_hkd = round($user_income_hkd - $user_pay_hkd,2);
                        if ($user_predict_hkd - $user_actual_finance < 0){
                            Db::rollback();
                            $this->error('您的账号存在异常，请提交工单进行处理');
                        }
                    }
                    try {
                        $rs = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => userid()))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
                        $rs[] = Db::table('weike_user_c2c')->where(['id' => $id])->setDec('deal', $num);
                        $rs[] = Db::table('weike_user_coin')->where(['userid' => userid()])->setDec($coin, $total);
                        $rs[] = $finance_nameid = Db::table('weike_user_c2c_trade')->insert([
                            'userid' => userid(),
                            'coin' => $coin,
                            'price' => $this->currency[$coin]['buy_price'],
                            'num' => $num,
                            'mum' => $this->currency[$coin]['buy_price'] * $num,
                            'type' => 2,
                            'addtime' => time(),
                            'matchtime' => time(),
                            'bankid' => $card_id,
                            'tradeno' => $tradeno,
                            'paytype' => $paytype,
                            'status' => 0,
                            'order' => 1
                        ]);
                        $rs[] = Db::table('weike_user_c2c_log')->insert([
                            'buyid' => $id,
                            'sellid' => userid(),
                            'coinname' => $coin,
                            'price' => 1,
                            'num' => $num,
                            'buytruename' => $shop_info['name'],
                            'buybank' => $shop_info['bankaddr'],
                            'buybankcard' => $shop_info['bankcard'],
                            'buymoble' => $shop_info['moble'],
                            'buytradeno' => $tradeno,
                            'selltruename' => $usertype['truename'],
                            'sellbank' => $bank['bank'],
                            'sellbankcard' => $bank['bankcard'],
                            'sellmoble' => $usertype['moble'],
                            'selltradeno' => $tradeno,
                            'addtime' => time(),
                            'type' => 2,
                            'status' => 0,
                            'order' => 1
                        ]);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => userid()))->find();
                        $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $total . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];
                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }
                        $rs[] = Db::table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $finance_nameid, 'remark' => '点对点交易-卖出提现', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        if (check_arr($rs)) {
                            Db::commit();
                            $this->success('订单匹配成功，请收到商家' . $shop_info['name'] . '付款后，点击“确认收款”');
                        } else {
                            Db::rollback();
                            $this->error('交易失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('交易失败！');
                    }
                }else{
                    Db::rollback();
                    $this->error('交易类型不存在');
                }
            }else{
                Db::rollback();
                $this->error('订单类型不存在');
            }
            
        }
    }
}