<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class C2c extends HomeCommon
{
    //设置交易币种价格

    public function _initialize()
    {
        //判断登陆， 不登陆不能 直接 访问数据库
        parent::_initialize();
        if (!userid()) {
            redirect('/#login');
        }
    }
    public function getList(){
        $uid = userid();
        $coin = input('coin');
        $coin = $coin === 'usdt' ? $coin : 'cny';
        $where['coin'] = $coin;
        $tradeno=input('tradeno');
        if($tradeno){
            $where['tradeno'] = $tradeno;
        }
        $where['userid'] = userid();
        $list = DB::name('UserC2cTrade')->where($where)->order('id desc')->select();

        $tradenos = array_column($list,'tradeno');

        if($tradenos){
            $logs_sell = DB::name('UserC2cLog')->where(['selltradeno' =>['IN',$tradenos]])->field('num,selltradeno')->select();
            $logs_buy = DB::name('UserC2cLog')->where(['buytradeno' =>['IN',$tradenos],'status'=>['neq',2]])->field('num,buytradeno')->order('id desc')->select();
            $logs_sell = array_column($logs_sell,'num','selltradeno');
            $logs_buy = array_column($logs_buy,'num','buytradeno');
        }


        $bankids = array_column($list,'bankid');
        if($bankids){
            $banks = DB::name('UserBank')->field('name, bankcard')->where(['id' =>['IN',$bankids]])->select();
            $names = array_column($banks,'name','bankid');
            $bankcards = array_column($banks,'bankcard','bankid');
        }


        foreach ($list as $k => $v) {
            if($v['type']==1) $list[$k]['match'] =($v['matchtime'])+(60*2*60);
            if($uid == 28830 || $uid == 28831) $list[$k]['username']= DB::name('user')->where(['id'=>$v['userid']])->value('truename');

            $list[$k]['log'] = 0;
            if($v['businessid'] != 0 && ($v['status'] == 0 || $v['status'] == 3 || $v['status']==1)) {
                if (isset($logs_sell[$v['tradeno']])) $logs = DB::name('UserC2cLog')->where(['selltradeno' => $v['tradeno']])->order('id desc')->field('status,num')->find();
                if (isset($logs) && ($logs['status'] == 3 || $logs['status']==0 || $logs['status']==1)) $list[$k]['log'] = $logs['num'];
                if(isset($logs_buy[$v['tradeno']])) $list[$k]['log'] = $logs_buy[$v['tradeno']];
            }

            $list[$k]['name'] = isset($names[$v['bankid']])?$names[$v['bankid']]:null;
            $list[$k]['bankcard'] = isset($bankcards[$v['bankid']])?$bankcards[$v['bankid']]:null;
            if($list[$k]['type']==2){
                $list[$k]['buji'] = $v['selltype'];
            }

//            $data = array(
//                'buytradeno' => $v['tradeno'],
//                'selltradeno' => $v['tradeno'],
//                '_logic' => 'OR',
//            );
            $user= DB::name('UserC2cLog')->where(['buytradeno'=>$v['tradeno'],'selltradeno'=>$v['tradeno']])->find();
            if($v['type']==1){
                if($v['businessid']!=0){

                    $list[$k]['weixin'] = DB::name('UserBank')->where(['userid' =>$user['sellid'],'status'=>2,'Paytype'=>1])->value('Paytype');
                    $list[$k]['bank'] = DB::name('UserBank')->where(['userid' => $user['sellid'],'status'=>2,'Paytype'=>0])->value('Paytype');
                    $list[$k]['aplay'] = DB::name('UserBank')->where(['userid' =>$user['sellid'],'status'=>2,'Paytype'=>2])->value('Paytype');
                }

            }
            if($v['type']==2){
                if($v['businessid']!=0){
                    $list[$k]['weixin'] = DB::name('UserBank')->where(['userid' =>$user['buyid'],'status'=>2,'Paytype'=>1])->value('Paytype');
                    $list[$k]['bank'] = DB::name('UserBank')->where(['userid' => $user['buyid'],'status'=>2,'Paytype'=>0])->value('Paytype');
                    $list[$k]['aplay'] = DB::name('UserBank')->where(['userid' =>$user['buyid'],'status'=>2,'Paytype'=>2])->value('Paytype');
                }
            }
        }
        $this->assign('show',$show);
        $this ->assign('list',$list);
        return $this->fetch('getList');
    }
    //c2c index
    public function index()
    {
        //获取交易记录
        $uid = userid();
        $coin = input('coin');
        $coin = $coin === 'usdt' ? $coin : 'cny';
        $where['coin'] = $coin;
        $where['userid'] = userid();

        $list = DB::name('UserC2cTrade')->where($where)->order('id desc')->paginate(15);
        foreach ($list as $k => $v) {
            if($uid == 28830 || $uid == 28831){
               $list[$k][username]= DB::name('user')->where(['id'=>$v['userid']])->value('truename');
            }
            if($v['type'] == 2 && $v['businessid'] != 0 && ($v['status'] == 0 || $v['status'] == 3)){
                $log = DB::name('UserC2cLog')->where(['selltradeno' => $v['tradeno']])->order('id desc')->value('num');
                $list[$k]['log'] = $log;
            }else{
                $list[$k]['log'] = 0;
            }
            $bank = DB::name('UserBank')->field('name, bankcard')->where(['id' => $v['bankid']])->find();
            $list[$k]['name'] = $bank['name'];
            $list[$k]['bankcard'] = $bank['bankcard'];
        }

       
        if($uid == 28830 || $uid == 28831){
            $uid=1;
        }else{
            $uid=0;
        }
        // dump($list);die;
        $this->assign('uid', $uid);

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('coin', $coin);
        //获取买单
        $buy = DB::name('UserC2cTrade')->field('id,userid,num,price,paytype,deal,addtime')->where(['type' => 1, 'status' => 0, 'order' => 0, 'businessid' => 0])->order('id asc')->limit(15)->select();
        foreach ($buy as $k => $v) {
            $buy[$k]['username'] = DB::name('User')->where(['id' => $v['userid']])->value('username');
            $buy[$k]['time'] = $v['addtime'];
            if (strlen($buy[$k]['username']) > 11){
                $buy[$k]['username'] = substr_replace($buy[$k]['username'], '****', 3, strlen($buy[$k]['username'])-6);
            }else{
                $buy[$k]['username'] = substr_replace($buy[$k]['username'], '****', 3, 4);
            }

        }

        //获取卖单
        $uid = userid();
        $t = time();
        if ($uid == 28830 || $uid == 28831){
            $sell = DB::name('UserC2cTrade')->field('id,userid,num,price,paytype,deal,min_num,addtime')->where(['type' => 2, 'status' => 0, 'order' => 0, 'businessid' => 0,'selltype' =>2])->order('id asc')->limit(15)->select();
            foreach ($sell as $k => $v) {
                $sell[$k]['username'] = DB::name('User')->where(['id' => $v['userid']])->value('username');
                $sell[$k]['time'] =$v['addtime'];
                $sell[$k]['username'] = $sell[$k]['username'] . '-' . ('加急');
            }
            if (count($sell) < 15){
                $sell_tmp = DB::name('UserC2cTrade')->field('id,userid,num,price,paytype,deal,min_num,addtime')->where(['type' => 2, 'status' => 0, 'order' => 0, 'businessid' => 0,'selltype' => 1])->order('id asc')->select();
                foreach ($sell_tmp as $k => $v) {
                    if ($t - $v['addtime'] >54000){
                        $sell_tmp[$k]['time'] =$v['addtime'];
                        $sell_tmp[$k]['username'] = DB::name('User')->where(['id' => $v['userid']])->value('username');
                        $sell_tmp[$k]['username'] = $sell_tmp[$k]['username'] . '-' . ('普通');
                        array_push($sell,$sell_tmp[$k]);
                        if (count($sell) > 15){
                            break;
                        }
                    }

                }
            }
        }else{
            $sell = DB::name('UserC2cTrade')->field('id,userid,num,price,paytype,deal,min_num')->where(['type' => 2, 'selltype' => 1, 'status' => 0, 'order' => 0, 'businessid' => 0])->order('id asc')->limit(15)->select();
            foreach ($sell as $k => $v) {
                $sell[$k]['time'] =$v['addtime'];
                $sell[$k]['username'] = DB::name('User')->where(['id' => $v['userid']])->value('username');
                $t = time();
                if ($t - $v['addtime'] > 54000) {
                        if (strlen($sell[$k]['username']) > 11) {
                            $sell[$k]['username'] = substr_replace($sell[$k]['username'], '****', 3, strlen($sell[$k]['username']) - 6);
                        } else {
                            $sell[$k]['username'] = substr_replace($sell[$k]['username'], '****', 3, 4);
                        }
                }
            }
          }

        //获取买入商家
        $buy_sj = DB::name('UserC2c')->where(['type' => 1,'status' => 1,'deal' =>array('gt',100)])->select();
        $buy_list = count($buy_sj);
        foreach ($buy_sj as $k => $v) {
            $buy[$k]['time'] = DB::name('user_c2c_log')->where(['buyid' => $v['userid']])->value('addtime');
            $buy_sj[$k]['username'] = $v['moble'];
            $buy_sj[$k]['username'] = substr_replace($buy_sj[$k]['username'], '****', 3, 4);
        }
        //获取卖出商家
        $sell_sj = DB::name('UserC2c')->where(['type' => 2,'status' => 1,'deal' =>array('gt',100)])->select();
        $sell_list = count($sell_sj);
        foreach ($sell_sj as $key => $vo) {
            $sell_sj[$key]['username'] = $vo['moble'];
            $sell_sj[$k]['time'] = DB::name('user_c2c_log')->where(['sellid' => $v['userid']])->value('addtime');
            $sell_sj[$key]['username'] = substr_replace($sell_sj[$key]['username'], '****', 3, 4);
        }
        // dump($sell);
        $this->assign('buy', $buy);
        $this->assign('sell', $sell);
        $this->assign('buy_sj', $buy_sj);
        $this->assign('sell_sj', $sell_sj);
        $this->assign('buy_list', $buy_list);
        $this->assign('sell_list', $sell_list);
        //获取用户银行卡
        $bank = DB::name('UserBank')->field('id, bank, bankcard')->where(['userid' => userid()])->select();
        $this->assign('bank', $bank);
        return $this->fetch();
    }

    // 挂委单
    public function trade()
    {
        if (IS_POST) {
            $coin = input('coin');
            $price = input('price');
            $type = input('type');
            $sell_type = input('selltype');
            $paytype = input('paytype');
            $min_num = input('min_num');
            if (!userid()) {
                $this->error('您没有登录请先登录！');
            }
            if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
                $this->error('出现未知错误！');
            }

            if ($price < 100 ) {
                $this->error('交易的金额最少100！');
            }

            if ($type == 2){
                if ($sell_type == 1){
                    if ($price > 100000 ) {
                        $this->error('普通单笔卖出交易数量最多100000！');
                    }
                }elseif ($sell_type == 2){
                    if ($price > 50000 ) {
                        $this->error('加急单笔卖出交易数量最多50000！');
                    }
                }
            }
            if ($type == 2){
                if($min_num%100 != 0){
                    $this->error('交易最小价格必须是100的整数倍');
                }
                if(!is_numeric($min_num)||strpos($min_num,".")!==false){
                    $this->error('交易最小价格必须是100的正整数的倍数');
                }
            }

            if($price%100 != 0){
                $this->error('交易价格必须是100的整数倍');
            }
            if(!is_numeric($price)||strpos($price,".")!==false){
                $this->error('交易价格必须是100的正整数的倍数');
            }
            //判断 未付款买或卖订单，不能继续买卖
            $usertype = DB::name('User')->where(['id' => userid()])->find();
//            if ($usertype['usertype'] != 1) {
//                $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' =>array('in',[0,3])])->count();
//                if ($count >= 1 && $type == 1) {
//                    $this->error('您有1条未处理的买单');
//                }
//                $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' => 2, 'status' => array('in',[0,3])])->count();
//                if ($count >= 1 && $type == 2) {
//                    $this->error('您有1条未处理的卖单');
//                }
//            }
            if (!$usertype['moble']){
                $this->error('请先认证手机');
            }
            $useridc=DB::name('user')->where(['id'=>userid(),'idcardauth'=>0])->find();
            if ($useridc){
                $this->error('请先实名认证');
            }

            //获取验证码
            for (; true;) {
                $tradeno = tradeno('c2c');
                if (!DB::name('UserC2cTrade')->where(array('tradeno' => $tradeno))->find()) {
                    break;
                }
            }

            if ($type == 1){
                $endtime = time() + 3600;
            }elseif ($type == 2){

                if ($sell_type == 1){
                    $endtime = time() + 3600*24;
                }elseif ($sell_type == 2){
                    $endtime = time() + 3600;
                }
            }


            //匹配成功后撤销频繁用户当天不允许交易
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $time = time();
            if($usertype != 1){
                $chexiao_num = DB::name('UserC2cTrade')->where(['userid' => userid(),'type' => 1, 'status' => 2, 'endtime' => array('between', "$beginToday,$time"),'businessid' => array('neq',0)])->count();
                if ($chexiao_num >= 2) {
                    $this->error('因当日撤销操作过于频繁，已经暂停您与商户之间的交易。');
                }
            }
//            买单
            if ($type == 1) {
                $bank = DB::name('UserBank')->where(['userid' => userid(),'status'=>2])->find();
                if (!$bank) {
                    $this->error('请绑定付款方式，是否开启状态');
                }
                $buy_paypassword = input('buy_paypassword/s');
                if (!check($buy_paypassword, 'password')) {
                    $this->error('交易密码格式错误！');
                }
                $user = DB::name('User')->where(array('id' => userid()))->find();
                if (md5($buy_paypassword) != $user['paypassword']) {
                    $this->error('交易密码错误！');
                }
                //用户未付款点击已付款，只允许每个用户存在两笔这样的订单
                $two = count(DB::name('UserC2cTrade')->where(['userid' => userid(),'status' => 3])->select());
                if ($user['usertype'] == 0){
                    if ($two - 2 >= 0) {
                        $this->error('您还有未完成交易的订单，请完成交易或者撤销订单后在进行交易');
                    }
                }


                $rs = DB::name('UserC2cTrade')->insert([
                    'userid' => userid(),
                    'coin' => $coin,
                    'price' => $price,
                    'num'   => 1,
                    'mum' => $price,
                    'type' => 1,
                    'addtime' => time(),
                    'timeend' => $endtime,
                    'bankid' =>  isset($bank['id'])?$bank['id']:0,
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
                $year = date("Y");
                $month = date("m");
                $day = date("d");
                $addti = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
                $endti= mktime(23,59,59,$month,$day,$year);//当天结束时间戳
                $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 2, 'status' => array('in',[0,1,3]),'addtime'=>['between', "$addti,$endti"]])->count();
                if ($usertype['usertype'] != 1){
                    if ($count >= 4 && $type == 2) {
                        $this->error('当天只能提现四笔交易');
                    }
                }

                //判断收款
                $bank = DB::name('UserBank')->where(['userid' => userid(),'status'=>2])->find();
                if (!$bank) {
                    $this->error('请绑定收款方式，是否开启状态');
                }
                $sell_paypassword = input('sell_paypassword');
                if (!check($sell_paypassword, 'password')) {
                    $this->error('交易密码格式错误！');
                }
                $user = DB::name('User')->where(array('id' => userid()))->find();
                if (md5($sell_paypassword) != $user['paypassword']) {
                    $this->error('交易密码错误！');
                }
                if ($min_num > $price){
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
                if ($usertype['usertype'] != 1){
                    $user_actual_finance = round(DB::name('UserCoin')->where(['userid' => userid()])->sum('cny + cnyd'),2);//用户实际财产
                    $user_tx = DB::name('Mytx')->where(['userid' => userid(), 'status' => 1])->sum('num');//用户提现
                    $user_c2c_pt = DB::name('UserC2cLog')->field('sum(fee_sell) as fee,sum(price) as price')->where(['sellid' => userid(),'status' =>1])->group('selltradeno')->select();//用户c2c提现
                    $user_c2c_tx = 0;
                    foreach ($user_c2c_pt as $k_pt=>$v_pt){
                        if ($v_pt['fee'] < 5){
                            $a_price = $v_pt['price'] + 5;
                            $user_c2c_tx += $a_price;
                        }else{
                            $a_price = $v_pt['price']+$v_pt['fee'];
                            $user_c2c_tx += $a_price;
                        }
                    }
                    $user_buy = DB::name('TradeLog')->where(['userid' => userid()])->sum('mum');//用户买入
                    $user_buy_fee = DB::name('TradeLog')->where(['userid' => userid()])->sum('fee_buy');//用户买入手续费
                    $user_sell_fee = DB::name('TradeLog')->where(['peerid' =>  userid()])->sum('fee_buy');//用户卖出手续费
                    $user_pay_cny = $user_tx + $user_c2c_tx + $user_buy + $user_buy_fee + $user_sell_fee ;//用户支出总计
                    $user_c2c_cz = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' => 1])->sum('price');//用户c2c充值
                    $user_cz_1 = DB::name('Mycz')->where(['userid' => userid(), 'status' => ['neq',4]])->sum('num');//用户充值
                    $user_sell = DB::name('TradeLog')->where(['peerid' => userid()])->sum('mum');//用户卖出
                    $user_invit =DB::name('Invit')->where(['userid' => userid()])->sum('fee');
                    $user_fenhong = DB::name('fenhong_log')->where(array('userid' => userid()))->sum('mum');
                    $user_income_cny = $user_c2c_cz + $user_cz_1 + $user_sell + $user_invit+$user_fenhong;//用户总收入
                    $user_predict_cny = round($user_income_cny - $user_pay_cny,2);
                    if ($user_actual_finance -  $user_predict_cny > 0.1 ){
                        $this->error('您暂时不能进行C2C交易 请联系客服处理');
                    }
                }

                $fee = 5;
                $txc_start = strtotime(date('Y-m-d',$time).' 00:00:00');
                $txc_end = strtotime(date('Y-m-d',$time).' 23:59:59');
                $counttx = DB::name('mytx')->where('userid='.userid().' and status in(0,1,3) and addtime between '.$txc_start.' and '.$txc_end)->count();
                $countc2c = DB::name('user_c2c_trade')->where('userid='.userid().' and type=2 and status!=2 and addtime between '.$txc_start.' and '.$txc_end)->count()
                    + DB::name('user_c2c_trade')->where('userid='.userid().' and type=2 and status=2 and is_sell=1 and addtime between '.$txc_start.' and '.$txc_end)->count();
                $tx_count = $counttx+$countc2c+1;
                if ($sell_type == 1){

                    if ($tx_count >= 2){
                        $bili = ($tx_count-1)*0.005;
                        $fee = $price * $bili < 5 ? 5 : $price *$bili;
                    }else{
                        $fee = $price * 0.005 < 5 ? 5 : $price *0.005;
                    }
                }elseif ($sell_type == 2){
                    if ($tx_count > 2){
                        $bili = ($tx_count-1)*0.01;
                        $fee = $price * $bili < 5 ? 5 : $price *$bili;
                    }else{
                        $fee = $price * 0.01 < 5 ? 5 : $price *0.01;
                    }
                }

                $total = $price + $fee;
                $user_total = DB::name('UserCoin')->where(['userid' => userid()])->value($coin);
                if ($total > $user_total) {
                    $this->error('您的币种余额不足！');
                }

                //减少用户余额，进行卖出
                $mo = DB::name('');
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_coin write, weike_user_c2c_trade write, weike_finance write');
                $finance = $mo->table('weike_finance')->where(array('userid' => userid()))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => userid()])->setDec($coin, $total);
                $rs[] = $finance_nameid = $mo->table('weike_user_c2c_trade')->insert([
                    'userid' => userid(),
                    'coin' => $coin,
                    'price' => $price,
                    'num' => 1,
                    'min_num' => $min_num,
                    'mum' => $price,
                    'type' => 2,
                    'addtime' => time(),
                    'timeend' => $endtime,
                    'bankid' => $bank['id'],
                    'tradeno' => $tradeno,
                    'reminder_type' => 1,
                    'paytype' => $paytype,
                    'status' => 0,
                    'order'  => 0,
                    'selltype'  =>  $sell_type,
                    'tx_num'    =>  $tx_count,
                    'fee_sell'  =>  $fee
                ]);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                $finance_hash = md5(userid() . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $total . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];
                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }
                $rs[] = $mo->table('weike_finance')->add(array('userid' => userid(), 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $price , 'type' => 2, 'name' => 'c2c', 'nameid' => $finance_nameid, 'remark' => '点对点交易-卖出提现', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->c2cmarket($tradeno);
                    $this->success('挂单成功！');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('挂单失败！');
                }
            }
        }
    }



    //撮合买卖
    public function c2cmarket($tradeno)
    {

        $trade = DB::name('UserC2cTrade')->where(['tradeno' => $tradeno])->find();
        while (!$trade){
            $trade = DB::name('UserC2cTrade')->where(['tradeno' => $tradeno])->find();
            sleep(1);
        }
        if ($trade['businessid'] != 0) {
            $this->error('订单已成功匹配');
        }
        //发送短信
        require_once COMMON_PATH . 'Ext/SmsMeilian.class.php';
        $username='dctx';  //用户名
        $password_md5='0b11ac988314c2399752d3b4d875b217';  //32位MD5密码加密，不区分大小写
        $apikey='e525954fc72f54324d3c4a7bd2fc20c6';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）//apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
        $smsMeilian = new \SmsMeilian();

        if ($trade['type'] == 1) {
            $mo = DB::name();
            $mo->startTrans();
            $sell = DB::name('UserC2cTrade')->lock(true)->where([ 'price - deal' => array('egt',$trade['price']),'min_num'=>array('elt',$trade['price']),'status' => 0, 'type' => 2,'selltype'=>1, 'businessid' => 0, 'order' => 0, 'paytype' => $trade['paytype']])->order('id asc')->find();
            if ($sell) {
                $buybank = DB::name('UserC2cTrade')
                    ->join('weike_user_bank on weike_user_c2c_trade.bankid = weike_user_bank.id')
                    ->join('weike_user on weike_user_c2c_trade.userid = weike_user.id')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user_c2c_trade.userid' => userid(), 'weike_user_c2c_trade.tradeno' => $tradeno, 'weike_user_c2c_trade.status' => 0])
                    ->find();
                $sellbank = DB::name('User')
                    ->join('weike_user_bank on weike_user.id = weike_user_bank.userid')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user.id' => $sell['userid'], 'weike_user_bank.id' => $sell['bankid']])
                    ->find();

                $sell_num = $sell['price']-$sell['deal'];
                $amount = min($trade['price'],$sell_num);

                //手续费计算
                $fee_sell = 0;
                if ($sell['selltype'] == 1){
                    if ($sell['tx_num'] > 2){
                        $bili = ($sell['tx_num']-1)*0.005;
                        $fee_sell = $amount * $bili;
                    }else{
                        $fee_sell = $amount * 0.005;
                    }
                }else{
                    if ($sell['tx_num'] > 2){
                        $bili = ($sell['tx_num']-1)*0.01;
                        $fee_sell = $amount * $bili;
                    }else{
                        $fee_sell = $amount * 0.01;
                    }
                }
                $rs = [];
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['userid' => $sell['userid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->order('id desc')->update(['businessid' => $trade['bankid'], 'matchtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['userid' => userid(), 'type' => 1, 'status' => 0, 'price' => $trade['price'], 'tradeno' => $tradeno])->update(['businessid' => $sell['bankid'], 'matchtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_log')->add([
                    'buyid' => userid(),
                    'sellid' => $sell['userid'],
                    'coinname' => $trade['coin'],
                    'price' => $amount,
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
                    'order'   => 0,
                    'selltype'  =>  $sell['selltype'],
                    'fee_sell'  =>  $fee_sell
                ]);

                if (check_arr($rs)) {
                    $mo->commit();
                    $message1 = '【火网】尊敬的火网用户，您的买单'.$tradeno.',成功匹配金额'.$amount.'，请及时向卖方打款，打款后请点击“我已付款”按钮；如有疑问，请联系官方客服。';
                    $message2 = '【火网】尊敬的火网用户，您的卖单'.$sell['tradeno'].',成功匹配金额'.$amount.'，请收到款后及时登入平台点击“确认收款”按钮完成交易；如有疑问，请联系官方客服。';
                    $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                    $smsMeilian->sendSMS($username, $password_md5, $apikey, $buybank['moble'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $contentUrlEncode = urlencode($message2);//执行URLencode编码  ，$content = urldecode($content);解码
                    $smsMeilian->sendSMS($username, $password_md5, $apikey, $sellbank['moble'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $this->success('订单匹配成功，请向商家' . $sellbank['truename'] . '付款，完成交易');
                } else {
                    $mo->rollback();
                    $this->success('下单成功！');
                }
            }else{
                $mo->rollback();
                $this->success('下单成功！');
            }
        } else if ($trade['type'] == 2) {
            $small = $trade['min_num'];
            $big = $trade['price'] - $trade['deal'];
            $mo = DB::name();
            $mo->startTrans();
            $buy = M('UserC2cTrade')->lock(true)->where(['price' => array('between',"$small,$big" ), 'status' => 0, 'order' => 0 ,'type' => 1, 'businessid' => 0, 'paytype' => $trade['paytype']])->order('num desc, id asc')->find();
            if ($buy) {
                $c2c = DB::name('UserC2cLog')->where(['buytradeno'=>$buy['tradeno']])->find();
                if ($c2c){
                    $mo->rollback();
                    $this->error('下单成功');
                }

                $sellbank = DB::name('UserC2cTrade')
                    ->join('weike_user_bank on weike_user_c2c_trade.bankid = weike_user_bank.id')
                    ->join('weike_user on weike_user_c2c_trade.userid = weike_user.id')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user_c2c_trade.userid' => userid(), 'weike_user_c2c_trade.tradeno' => $tradeno, 'weike_user_c2c_trade.status' => 0])
                    ->find();
                $buybank = DB::name('User')
                    ->join('weike_user_bank on weike_user.id = weike_user_bank.userid')
                    ->field('weike_user.moble , weike_user.truename , weike_user_bank.bank , weike_user_bank.bankcard')
                    ->where(['weike_user.id' => $buy['userid'], 'weike_user_bank.id' => $buy['bankid']])
                    ->find();
                $buy_num = $buy['price'] - $buy['deal'];
                $sell_num = $trade['price'] - $trade['deal'];
                $amount = min($sell_num, $buy_num);
                $fee_sell = 0;
                if ($trade['selltype'] == 1){
                    if ($trade['tx_num'] > 2){
                        $bili = ($trade['tx_num']-1)*0.005;
                        $fee_sell = $amount * $bili;
                    }else{
                        $fee_sell = $amount * 0.005;
                    }
                }else{
                    if ($trade['tx_num'] > 2){
                        $bili = ($trade['tx_num']-1)*0.01;
                        $fee_sell = $amount * $bili;
                    }else{
                        $fee_sell = $amount * 0.01;
                    }
                }
                $rs = array();
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['userid' => userid(), 'tradeno' => $tradeno, 'status' => 0,])->order('id desc')->update(['businessid' => $buy['bankid'], 'matchtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['userid' => $buy['userid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['businessid' => $trade['bankid'], 'matchtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_log')->insert([
                    'buyid' => $buy['userid'],
                    'sellid' => userid(),
                    'coinname' => $trade['coin'],
                    'price' => $amount,
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
                    'order' => 0,
                    'selltype'  =>  $trade['selltype'],
                    'fee_sell'  =>  $fee_sell
                ]);

                if (check_arr($rs)) {
                    $mo->commit();
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

                    $message1 = '【火网】尊敬的火网用户，您的买单' . $buy['tradeno'] . '，成功匹配金额' . $amount . '，请及时向卖方打款，转账后请点击“我已付款”按钮；如有疑问，请联系官方客服。';
                    $message2 = '【火网】尊敬的火网用户，您的卖单' . $tradeno . '成功匹配金额' . $amount . '，请收到款后及时登入平台点击“确认收款”按钮完成交易；如有疑问，请联系官方客服。';
                    $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                    $smsMeilian->sendSMS($username, $password_md5, $apikey, $buybank['moble'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $contentUrlEncode = urlencode($message2);//执行URLencode编码  ，$content = urldecode($content);解码
                    $smsMeilian->sendSMS($username, $password_md5, $apikey, $sellbank['moble'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $this->success('订单匹配成功，收到商户' . $buybank['truename'] . '付款后，请点击"确认收款"');
                } else {
                    $mo->rollback();
                    $this->success('下单成功！');
                }
            }else{
                $mo->rollback();
                $this->success('下单成功！');
            }
        } else {
            $this->error('交易类型不存在');
        }
    }

    //展示弹窗
    public function alert_tip()
    {
        if ($this->request->isPost()) {
            $id = I('id/d');
            $paytype = I('paytype');
            $trade = M('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['order'] == 1 || $trade['order'] == 0) {
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
                $data = array(
                    'buytradeno' => $trade['tradeno'],
                    'selltradeno' => $trade['tradeno'],
                    '_logic' => 'OR',
                );
                $log = M('UserC2cLog')->where($data)->order('id desc')->find();

                if ($trade['type'] == 1) {
                    $num = M('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->order('id desc')->getField('num');
                    $sellbank = M('UserBank')->where(array('userid' => $log['sellid'], 'status' => 2, 'Paytype' => $paytype))->find();
                } else {
                    $num = M('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->order('id desc')->getField('num');
                    $buybank = M('UserBank')->where(array('userid' => $log['buyid'], 'status' => 2, 'Paytype' => $paytype))->find();
                }

                if ($trade['businessid']==0){
                    echo json_encode([
                        'sts' => 1,
                        'num' => $trade['price'],
                        'tradeno' => $trade['tradeno'],
                        'type' => $trade['type'],
                        'status' => $status,
                        'businessid' => $trade['businessid'],
                        'matchtime' => $trade['matchtime'],
                        'paytype' => $trade['paytype'],
                    ]);
                    exit();
                } else {
                    if ($trade['type'] == 1) {
                        echo json_encode([
                            'tradeId' => $trade['id'],
                            'sts' => 1,
                            'name' => $log['selltruename'],
                            'sellid' => $sellbank['userid'],
                            'bankaddr' => $sellbank['bank'],
                            'bankcard' => $sellbank['bankcard'],
                            'num' => $num,
                            'tradeno' => $log['selltradeno'],
                            'type' => $trade['type'],
                            'status' => $status,
                            'moble' => $log['sellmoble'],
                            'image' => $sellbank['img'],
                            'businessid' => $trade['businessid'],
                            'matchtime' => $trade['matchtime'],
                            'paytype' => $sellbank['paytype'],
                            'bankstatus' => $trade['bankstatus'],
                        ]);
                        exit();
                    } else {
                        echo json_encode([
                            'tradeId' => $trade['id'],
                            'sts' => 1,
                            'name' =>$log['buytruename'],
                            'buyid' => $buybank['userid'],
                            'bankaddr' => $buybank['bank'],
                            'bankcard' => $buybank['bankcard'],
                            'num' => $num,
                            'tradeno' => $log['buytradeno'],
                            'type' => $trade['type'],
                            'status' => $status,
                            'moble' =>$log['buymoble'],
                            'image' => $buybank['img'],
                            'businessid' => $trade['businessid'],
                            'matchtime' => $trade['matchtime'],
                            'paytype' => $buybank['paytype'],
                            'bankstatus' => $trade['bankstatus'],
                        ]);
                        exit();
                    }

                }
            }
        }
    }

    //已付款
    public function pay()
    {
        if (IS_AJAX) {
            $id = I('id/d');
            //修改订单状态
            $trade = DB::name('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['status'] != 0) {
                $this->error('订单已经处理过！');
            }

            if ($trade['order'] == 0) {
                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_c2c_log write, weike_user_c2c_trade write');
                $rs = [];
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 3]);
                $sell = DB::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $sell['selltradeno']])->update(['status' => 3]);

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $sellmoble = $sell['sellmoble'];
                    $title = config('web_name');
                    $message = '【火网】尊敬的火网用户，您的卖单'.$sell['selltradeno'].'，成功匹配金额'.$sell['price'].'，买家已经“确认付款”，请收到款项后及时登陆平台点击“确认收款”按钮完成交易。如有疑问，请联系官方客服。';
                    send_moble($sellmoble, $title, $message);
                    $this->success('我已付款');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('付款失败！');
                }
            } else {
                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_c2c_log write, weike_user_c2c_trade write');
                $rs = [];
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 3]);
                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->success('我已付款');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('付款失败！');
                }
            }
        }
    }

    //催单
    public function reminder()
    {
        if (IS_AJAX) {
            $id = I('id/d');
            //修改订单状态
            $trade = DB::name('UserC2cTrade')->where(['id' => $id])->find();
            //判断是卖家还是买家催单
            if ($trade['type'] == 1) {
                $sell = DB::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                $reminder_type = DB::name('UserC2cTrade')->where(['id' => $id])->update(['reminder_type' => 0]);
                if ($reminder_type) {
                    $title = config('web_name');
                    $sellmoble = $sell['sellmoble'];
                    $message1 = '【火网】尊敬的火网用户，您的卖单'.$sell['selltradeno'].'，成功匹配金额'.$sell['price'].'，买家已经“确认付款”，请收到款项后及时登陆平台点击“确认收款”按钮完成交易。如有疑问，请联系官方客服。';
                    send_moble($sellmoble, $title, $message1);
                    $this->success('催单成功，请稍候');
                } else {
                    $this->error('催单失败，请联系客服人员');
                }
            } else {
                $buy = DB::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->find();
                $reminder_type = DB::name('UserC2cTrade')->where(['id' => $id])->update(['reminder_type' => 0]);
                if ($reminder_type) {
                    $title = config('web_name');
                    $buymoble = $buy['buymoble'];
                    $message2 = '【火网】尊敬的火网用户，您的买单'.$buy['buytradeno'].'成功匹配金额'.$buy['price'].'，若已经打款请点击“我已付款”按钮，未付款请及时向卖方账户打款或进行撤销。如有疑问，请联系官方客服。';
                    send_moble($buymoble, $title, $message2);
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
            $id = input('id');
            $trade = DB::name('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['businessid' == 0 && $trade['status'] ==0]){
                $this->error('订单正在匹配！');
            }
            require_once COMMON_PATH . 'Ext/SmsMeilian.class.php';
            $username='dctx';  //用户名
            $password_md5='0b11ac988314c2399752d3b4d875b217';  //32位MD5密码加密，不区分大小写
            $apikey='e525954fc72f54324d3c4a7bd2fc20c6';
            $smsMeilian = new \SmsMeilian();


            $c2c_log = DB::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno'],'status' =>  ['neq',2]])->order('id desc')->find();
            //判断是用户之间的交易还是用户和系统之间的交易
            if ($trade['order'] == 1) {
                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write');
                $rs = [];
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 1,'endtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->update(['status' => 1,'endtime' => time()]);
                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->success('卖出成功！');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('卖出失败！');
                }
            } else {
//                $userid = M('UserBank')->where(['id' => $trade['businessid']])->getField('userid');
                $buy = DB::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno']])->order('id desc')->find();
                $buyid = DB::name('UserC2cTrade')->where(['tradeno' => $buy['buytradeno']])->value('id');
                $trade = DB::name('UserC2cTrade')->where(['id' => $id])->find();
                if ($trade['businessid'] == 0 && $trade['status'] == 0){
                    $this->error('订单已确认收款，不可以重复操作！');
                }elseif ($trade['businessid'] != 0 && $trade['status'] == 1){
                    $this->error('订单已成交，不可以重复操作！');
                }
                //修改订单状态
                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_coin write, weike_user_c2c_trade write ,weike_user_bank write ,weike_user_c2c_log write,weike_finance write');
                $rs = [];
                $finance = $mo->table('weike_finance')->where(array('userid' => $buy['buyid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->setInc('deal',$c2c_log['price']);
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->setInc('deal',$c2c_log['price']);
                $rs[] = $deal = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->value('deal');
                if($trade['price'] - $deal == 0){
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['status' => 1, 'endtime' => time()]);
                }else{
                    if ($trade['price'] - $deal > 100 && $trade['price'] - $deal > $trade['min_num']){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0,'status' => 0]);
                    }else if ($trade['price'] - $deal > 100 && $trade['num'] - $deal <= $trade['min_num']){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0,'status' => 0,'min_num'=>$trade['price'] - $deal]);
                    }else if ($trade['price'] - $deal <= 100 && $trade['price'] - $deal <= $trade['min_num'] ){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0,'status' => 0,'min_num'=>$trade['price'] - $deal]);
                    }
                }
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->update(['status' => 1, 'endtime' => time()]);
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['buyid']])->setInc('cny', $c2c_log['price']);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->save(['status' => 1 ,'endtime' => time()]);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                $finance_hash = md5(userid() . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $trade['price'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }
                $rs[] = $mo->table('weike_finance')->add(array('userid' => $buy['buyid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $c2c_log['price'], 'type' => 1, 'name' => 'c2c', 'nameid' => $buyid, 'remark' => '点对点交易-买入交易', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->success('卖出成功！');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('卖出失败！');
                }
            }
        }
    }

    //撤单
    public function c2cchexiao()
    {
        if (IS_AJAX) {
            $id = input('id');
            $trade = DB::name('UserC2cTrade')->lock(true)->where(['id' => $id])->find();
            if ($trade['status'] != 0) {
                $this->error('订单已经处理过！');
            }
            $usertype = DB::name('User')->where(['id' => userid()])->value('usertype');
            if ($trade['order'] == 0){
                if ($trade['type'] == 1) {
                    //买单撤销
                    if ($trade['businessid'] == 0) {
                        $chage_status = DB::name('UserC2cTrade')->where(['id' => $id])->update(['status' => 2]);
                        if ($chage_status) {
                            $this->success('撤单成功');
                        } else {
                            $this->error('撤单失败，请联系客服人员');
                        }
                    } else {
                        $data = DB::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                        $sell = DB::name('UserC2cTrade')->where(['tradeno'=>$data['selltradeno']])->find();

                        $mo = DB::name();
                        $mo->execute('set autocommit=0');
                        $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write ,weike_user_coin write,weike_finance write');
                        $rs = [];
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2,'endtime'=>time()]);
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $data['selltradeno']])->update(['status' => 0, 'businessid'=>0]);
                        $rs[] = $mo->table('weike_user_c2c_log')->where(['id' => $data['id']])->update(['status' => 2]);
                        if (check_arr($rs)) {
                            $mo->execute('commit');
                            $mo->execute('unlock tables');
                            $title = C('web_name');
                            $sellmoble = $data['sellmoble'];
                            $message = '【火网】尊敬的火网用户，您的卖单'.$data['selltradeno'].'，成功匹配金额'.$data['price'].'，买家撤销了订单，请您重新下单。如有疑问，请联系官方客服。';
                            send_moble($sellmoble, $title, $message);
                            $this->success('撤单成功！');
                        } else {
                            $mo->execute('rollback');
                            $mo->execute('unlock tables');
                            $this->error('撤单失败！');
                        }
                    }
                } else {
                    //卖单撤销
                    if ($trade['businessid'] != 0) {
                        $this->error('订单已匹配成功，无法撤单');
                    }
                    //买家撤销卖家不能撤销
                    if ($trade['businessid'] == 2) {
                        $this->error('无法撤单');
                    }
                    $sell_num = $trade['price'] - $trade['deal'];
                    $fee = 0;
                    $sell_fee = 0;
                    $is_sell = 0;
                    if ($trade['deal'] == 0){
                        //在未有交易的情况
                        if ($trade['selltype'] == 1){
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.005;
                                $fee = $trade['price'] * $bili < 5 ? 5 : $trade['price'] *$bili;
                            }else{
                                $fee = $trade['price'] * 0.005 < 5 ? 5 : $trade['price'] *0.005;
                            }
                        }else{
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.01;
                                $fee = $trade['price'] * $bili < 5 ? 5 : $trade['price'] *$bili;
                            }else{
                                $fee = $trade['price'] * 0.01 < 5 ? 5 : $trade['price'] *0.01;
                            }
                        }

                    }else{
                        $is_sell = 1;
                        if ($trade['selltype'] == 1){
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.005;
                                if ($trade['price'] * $bili <= 5){
                                    $fee = 5 - ($trade['deal'] * $bili);
                                }else{
                                    $fee = ($trade['price'] * $bili) - ($trade['deal'] * $bili <= 5 ? 5 : $trade['deal'] * $bili);
                                }
                            }else{
                                if ($trade['price'] * 0.005 <= 5){
                                    $fee = 5 - ($trade['deal'] * 0.005);
                                }else{
                                    $fee = ($trade['price'] * 0.005) - ($trade['deal'] * 0.005 <= 5 ? 5 : $trade['deal'] * 0.005);
                                }
                            }
                        }elseif ($trade['selltype'] == 2){
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.01;
                                if ($trade['price'] * $bili <= 5){
                                    $fee = 5 - ($trade['deal'] * $bili);
                                }else{
                                    $fee = ($trade['price'] * $bili) - ($trade['deal'] * $bili <= 5 ? 5 : $trade['deal'] * $bili);
                                    $sell_fee = $trade['deal'] * $bili <= 5 ? 5 : $trade['deal'] * $bili;
                                }
                            }else{
                                if ($trade['price'] * 0.01 <= 5){
                                    $fee = 5 - ($trade['deal'] * 0.01);
                                }else{
                                    $fee = ($trade['price'] * 0.01) - ($trade['deal'] * 0.01 <= 5 ? 5 : $trade['deal'] * 0.01);
                                }
                            }
                        }
                    }
                    $total = $sell_num + $fee;
                    $mo = DB::name();
                    $mo->execute('set autocommit=0');
                    $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write ,weike_user_coin write,weike_finance write');
                    $rs = [];
                    $finance = $mo->table('weike_finance')->where(array('userid' => userid()))->order('id desc')->find();
                    $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2,'is_sell'=>$is_sell]);
                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $trade['userid']])->setInc('cny', $total);
                    $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                    $finance_hash = md5(userid() . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $trade['price'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    $rs[] = $mo->table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $sell_num, 'type' => 2, 'name' => 'c2c', 'nameid' => $id, 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    if (check_arr($rs)) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        $this->success('撤单成功！');
                    } else {
                        $mo->execute('rollback');
                        $mo->execute('unlock tables');
                        $this->error('撤单失败！');
                    }
                }
            }else{
                if ($trade['type'] == 1){
                    $c2c_log = DB::name('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                    $mo = DB::name();
                    $mo->execute('set autocommit=0');
                    $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write,weike_user_c2c write');
                    $rs = [];
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->save(['status' => 2]);
                    $rs[] = $mo->table('weike_user_c2c')->where(['id' => $c2c_log['sellid']])->setInc('deal',$trade['price']);
                    $rs[] = $mo->table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 2]);
                    if (check_arr($rs)) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        $this->success('撤单成功！');
                    } else {
                        $mo->execute('rollback');
                        $mo->execute('unlock tables');
                        $this->error('撤单失败！');
                    }
                }
            }
        }
    }

    //手动撮合买卖
    public function hand_trade(){
        if (IS_POST){
            $price     = input('price');
            $coin    = input('coin');
            $type    = input('type');
            $card_id = input('card_id');
            $paytype = input('paytype');
            $paypwd  = input('paypwd');
            $id      = input('id');
            $order   = input('order');
            //发送短信
            require_once COMMON_PATH . 'Ext/SmsMeilian.class.php';
            $username='dctx';  //用户名
            $password_md5='0b11ac988314c2399752d3b4d875b217';  //32位MD5密码加密，不区分大小写
            $apikey='e525954fc72f54324d3c4a7bd2fc20c6';
            $smsMeilian = new \SmsMeilian();  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
            if (!userid()) {
                $this->error('您没有登录请先登录！');
            }
            if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
                $this->error('出现未知错误！');
            }
            if (empty($card_id)) {
                $this->error('请选择账号！');
            }

            if ($price < 100 ) {
                $this->error('交易的金额最少100！');
            }

            if($price%100 != 0){
                $this->error('交易数量必须是100的整数倍');
            }
            if (!check($price, 'double')) {
                $this->error('挂单数量格式错误');
            }
            $bank = DB::name('UserBank')->where(['id' => $card_id])->find();
            if (!$bank){
                $this->error('银行卡不存在');
            }
            $usertype = M('User')->where(['id' => userid()])->find();
            if (!check($paypwd, 'password')) {
                $this->error('交易密码格式错误！');
            }
            $paypwd = md5($paypwd);
            if ($paypwd != $usertype['paypassword']) {
                $this->error('交易密码错误！');
            }
            if ($usertype['usertype'] != 1) {
                $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' => 1, 'status' => array('in',[0,3])])->count();
                if ($count >= 1 && $type == 1) {
                    $this->error('您有1条未处理的买单');
                }
                $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' => 2, 'status' => array('in',[0,3])])->count();
                if ($count >= 1 && $type == 2) {
                    $this->error('您有1条未处理的卖单');
                }
            }

            //获取验证码
            for (; true;) {
                $tradeno = tradeno('c2c');
                if (!DB::name('UserC2cTrade')->where(array('tradeno' => $tradeno))->find()) {
                    break;
                }
            }

            $time = time();
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $chexiao_num = DB::name('UserC2cTrade')->where(['userid' => userid(),'type' => 1, 'status' => 2, 'endtime' => array('between', "$beginToday,$time"),'businessid' => array('neq',0)])->count();
            if ($usertype['usertype'] != 1){
                if ($chexiao_num >= 2){
                    $this->error('因当日撤销操作过于频繁，已经暂停您与商家之间的交易。');
                }
            }

            if ($order == 'user'){
                if ($type == 1){

                    $mo = DB::name();
                    $mo->startTrans();
                    $sell = DB::name('UserC2cTrade')->lock(true)->where(['id' => $id,'businessid' => 0])->find();
                    if (!$sell){
                        $this->error('订单已匹配，请选择其他订单交易');
                        $mo->rollback();
                    }
                    $sell_user = DB::name('User')->where(['id' => $sell['userid']])->find();
                    $sell_bank = DB::name('UserBank')->where(['id' => $sell['bankid']])->find();
                    $min_num = $sell['min_num'];
                    $sy_num = $sell['price'] - $sell['deal'];
                    if ($sy_num - $min_num >= 0){
                        if ($price - $min_num < 0){
                            $this->error('交易数量不得低于最小匹配数量'.$min_num);
                            $mo->rollback();
                        }
                        if ($price > $sy_num){
                            $this->error('交易数量大于卖方的剩余可交易数量，无法交易');
                            $mo->rollback();
                        }
                    }else{
                        if ($price > $sy_num ){
                            $this->error('交易数量大于卖方的剩余可交易数量，无法交易');
                            $mo->rollback();
                        }
                    }
                    if ($paytype != $sell['paytype']){
                        $this->error('你选择的支付方式和卖家不一致，请重新选择');
                        $mo->rollback();
                    }

                    $fee_sell = 0;

                    if ($sell['selltype'] == 1){
                        if ($sell['tx_num'] > 2){
                            $bili = ($sell['tx_num']-1)*0.005;
                            $fee_sell = $price * $bili;
                        }else{
                            $fee_sell = $price * 0.005;
                        }
                    }else{
                        if ($sell['tx_num'] > 2){
                            $bili = ($sell['tx_num']-1)*0.01;
                            $fee_sell = $price * $bili;
                        }else{
                            $fee_sell = $price * 0.01;
                        }
                    }


                    if (!$sell){
                            $mo->rollback();
                            $this->error('订单已匹配，请选择其他订单交易');
                    }
                    $rs = [];
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['businessid' => $card_id,'matchtime' => time()]);
                    $rs[] = $mo->table('weike_user_c2c_trade')->add([
                        'userid' => userid(),
                        'coin' => $coin,
                        'price' => $price,
                        'num' => 1,
                        'mum' => $price,
                        'type' => 1,
                        'addtime' => time(),
                        'matchtime' =>time(),
                        'businessid'=>$sell['bankid'],
                        'bankid' => $card_id,
                        'tradeno' => $tradeno,
                        'paytype' => $paytype,
                        'status' => 0,
                        'order'  => 0,
                        'selltype'  =>  $sell['selltype'],
                    ]);
                    $rs[] = $mo->table('weike_user_c2c_log')->insert([
                        'buyid' => userid(),
                        'sellid' => $sell['userid'],
                        'coinname' => $coin,
                        'price' => $price,
                        'num' => $price,
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
                        'order'   => 0,
                        'selltype'  =>  $sell['selltype'],
                        'fee_sell'  =>  $fee_sell
                    ]);
                    if (check_arr($rs)){
                        $mo->commit();
                        $message1 = '【火网】尊敬的火网用户，您的卖单' . $sell['tradeno'] . ',成功匹配金额'.$price.'，请收到款后及时登入平台点击“确认收款”按钮完成交易；如有疑问，请联系官方客服。';
                        $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                        $smsMeilian->sendSMS($username, $password_md5, $apikey, $sell_user['moble'], $contentUrlEncode, 'UTF-8');  //进行发送
                        $this->success('订单匹配成功，请向商家' . $sell_user['truename'] . '付款，完成交易');
                    }else{
                        $mo->rollback();
                        $this->error('下单失败！');
                    }


                }elseif ($type == 2){
                    $mo = DB::name();
                    $mo->startTrans();
                    $buy = DB::name('UserC2cTrade')->lock(true)->where(['id' => $id,'businessid' => 0])->find();
                    if (!$buy){
                        $this->error('订单已匹配，请选择其他订单交易');
                        $mo->rollback();
                    }
                    $buy_user = DB::name('User')->where(['id' => $buy['userid']])->find();
                    $buy_bank = DB::name('UserBank')->where(['id' => $buy['bankid']])->find();
                    if ($price < $buy['price']){
                        $this->error('交易数量不可以小于买方挂单数量');
                        $mo->rollback();
                    }
                    if ($paytype != $buy['paytype']){
                        $this->error('你选择的支付方式和买家不一致，请重新选择');
                        $mo->rollback();
                    }

                    $txc_start = strtotime(date('Y-m-d',time()).' 00:00:00');
                    $txc_end = strtotime(date('Y-m-d',time()).' 23:59:59');
                    $counttx = DB::name('mytx')->where('userid='.userid().' and urgent=0 and status in(0,1,3) and addtime between '.$txc_start.' and '.$txc_end)->count();
                    $countc2c = DB::name('user_c2c_trade')->where('userid='.userid().' and type=2 and status!=2 and addtime between '.$txc_start.' and '.$txc_end)->count()
                        + DB::name('user_c2c_trade')->where('userid='.userid().' and type=2 and status=2 and is_sell=1 and addtime between '.$txc_start.' and '.$txc_end)->count();
                    $tx_count = $counttx+$countc2c+1;
                    if ($tx_count > 2){
                        $bili = ($tx_count-1)*0.005;
                        $fee = $price * $bili < 5 ? 5 : $price *$bili;
                    }else{
                        $fee = $price * 0.005 < 5 ? 5 : $price *0.005;
                    }

                    $total = $price + $fee;

                    if (!$buy){
                        $mo->rollback();
                        $this->error('订单已匹配，请选择其他订单交易');
                    }
                    $rs = [];
                    $finance = $mo->table('weike_finance')->where(array('userid' => userid()))->order('id desc')->find();
                    $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => userid()])->setDec($coin, $total);
                    $rs[] = $finance_nameid = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->save(['businessid'=> $card_id,'matchtime' => time()]);
                    $rs[] = $finance_nameid = $mo->table('weike_user_c2c_trade')->add([
                        'userid' => userid(),
                        'coin' => $coin,
                        'price' => $price,
                        'num' => 1,
                        'mum' => $price,
                        'type' => 2,
                        'addtime' => time(),
                        'matchtime' =>time(),
                        'businessid'=>$buy['bankid'],
                        'bankid' => $card_id,
                        'tradeno' => $tradeno,
                        'paytype' => $paytype,
                        'status' => 0,
                        'order'  => 0,
                        'selltype'  =>  1,
                        'tx_num'    =>  $tx_count,
                        'fee_sell'  =>  $fee
                    ]);
                    $rs[] = $mo->table('weike_user_c2c_log')->insert([
                        'buyid' => $buy['userid'],
                        'sellid' => userid(),
                        'coinname' => $coin,
                        'price' => $price,
                        'num' => $price,
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
                        'order'   => 0,
                        'selltype'  =>  1,
                        'fee_sell'  =>  $fee
                    ]);
                    $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                    $finance_hash = md5(userid() . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $total . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];
                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }
                    $rs[] = $mo->table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $total , 'type' => 2, 'name' => 'c2c', 'nameid' => $finance_nameid, 'remark' => '点对点交易-卖出提现', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    if (check_arr($rs)){
                        $mo->commit();
                        $message1 = '【火网】尊敬的火网用户，您的买单' . $buy['tradeno'] . ',成功匹配金额'.$num.'，请及时向卖方打款，打款后请点击“我已付款”按钮；如有疑问，请联系官方客服。';
                        $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                        $smsMeilian->sendSMS($username, $password_md5, $apikey, $buy_user['moble'], $contentUrlEncode, 'UTF-8');  //进行发送
                        $this->success('订单匹配成功，请收到商家' . $buy_user['name'] . '付款后，点击“确认收款”');
                    }else{
                        $mo->rollback();
                        $this->error('下单失败！');
                    }
                }else{
                    $this->error('交易类型不存在');
                }
            }else{
                $this->error('订单类型不存在');
            }
        }
    }
    public function bank($trade_id,$userid){
        $trade = M('user_c2c_trade')->where(['id'=>$trade_id])->find();
        $bank = M('user_bank')->where(['userid'=>$userid,'status'=>2])->select();
        //选择状态
        $t_status = [0=>'交易中',1=>'已成交',2=>'已撤销',3=>'已支付'];

        $log = M('UserC2cLog')->where(['buytradeno' => $trade['tradeno'],'selltradeno' => $trade['tradeno'],'_logic' => 'OR'])->order('id desc')->find();

        foreach ($bank as $k=>$v){
            $bank[$k]['tradeId'] = $trade_id;
            $bank[$k]['sts'] = 1;
            $bank[$k]['name'] = M('user')->where(['id'=>$log['sellid']])->getField('truename');

            $bank[$k]['sellid'] = $v['userid'];
            $bank[$k]['bankaddr'] = $v['bank'];
            $bank[$k]['image'] = $v['img'];
            $bank[$k]['num'] = $trade['price'];
            if($trade['type']==2){
                $bank[$k]['tradeno'] = $log['buytradeno'];
            }else{
                $bank[$k]['tradeno'] = $log['selltradeno'];
            }
            $bank[$k]['type'] = $trade['type'];
            $bank[$k]['status'] = isset($t_status[$trade['status']])?$t_status[$trade['status']]:'已成交';
            $bank[$k]['businessid'] = $trade['bankid'];
            $bank[$k]['matchtime'] = $trade['matchtime'];
            $bank[$k]['bankstatus'] = $trade['bankstatus'];
            $bank[$k]['moble'] = $trade['type']==1?$log['sellmoble']:$log['buymoble'];
        }
        return $bank;
    }
}