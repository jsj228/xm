<?php
class C2cController extends Ctrl_Base
{
    //入口
    function init()
    {
        parent::init();
        $this->assign('pageName', $this->_request->action);
    }

    //用户c2c
    public function indexAction($page=1)
    {
        $this->_ajax_islogin();
        $C2ctrade = new C2ctradeModel();
        // 当前总记录条数
        isset($_GET['p']) or $_GET['p'] = intval($page);
        //所有用户买记录
        $data['totalbuy']= $C2ctrade->where(['status' =>1,'type'=>1])->count();
        // 获取分页显示
        $tPage = new Tool_Page($data['totalbuy'],10);
        $data['pageinfobuy']= $tPage->show();
        $data['listbuy'] = $C2ctrade->field('*')
            ->where(['status' =>1,'type'=>1])
            ->limit(5)
            ->order('id desc')
            ->fList();
        //所有用户卖记录
        $data['totalsell']= $C2ctrade->where(['status' =>1,'type'=>21])->count();
        // 获取分页显示
        $tPage = new Tool_Page($data['totalsell'],10);
        $data['pageinfosell']= $tPage->show();
        $data['listsell'] = $C2ctrade->field('*')
            ->where(['status' =>1,'type'=>2])
            ->limit(5)
            ->order('id desc')
            ->fList();
        //用户所有订单
        $data['total']= $C2ctrade->where(['uid' =>$this->mCurUser['uid']])->count();
        // 获取分页显示
        $tPage = new Tool_Page($data['total'],10);
        $data['pageinfo']= $tPage->show();
        $data['list'] = $C2ctrade->field('*')
            ->where(['uid' =>$this->mCurUser['uid']])
            ->limit($tPage->limit())
            ->order('id desc')
            ->fList();
        $C2ctradelog = new C2ctradelogModel();
        foreach ($data['list'] as $k => $v) {
            $data['list'][$k]['addtime'] = date("Y-m-d H:i:s", $v['addtime']);
            $user= $C2ctradelog->where("buytradeno or selltradeno", $v['tradeno'])->order('id desc')->fRow();


            if($v['type']==1){
                $sell=$C2ctrade->where(['tradeno'=>$user['selltradeno']])->fRow();
                // var_dump($sell);die;
                if($v['matching']!=0){

                    $data['list'] [$k]['wx'] =$sell['wechat'];
                    $data['list'] [$k]['yhk'] = $sell['bank'];
                    $data['list'] [$k]['yfb'] = $sell['alipay'];

                }

            }
            if($v['type']==2){

                $buy=$C2ctrade->where(['tradeno'=>$user['buytradeno']])->fRow();
                if($v['matching']!=0){

                    $data['list'] [$k]['wx'] =$buy['wechat'];
                    $data['list'] [$k]['yhk'] = $buy['bank'];
                    $data['list'] [$k]['yfb'] = $buy['alipay'];

                }
            }

        }
//         Tool_Out::p($data);
        $this->assign('data', $data);
    }


    //用户挂单
    public function tradeAction()
    {
        $this->_ajax_islogin();
        $C2ctrade = new C2ctradeModel();
        $UserModel = new UserModel();
        $Userbank = new UserbankModel();
        $price = $_POST['num'];
        $type = $_POST['type'];
        if ($type) {
            if ($price < 100) {
                $this->ajax('交易的金额最少100！');
            }
            if ($price % 100 != 0) {
                $this->ajax('交易价格必须是100的整数倍！');
            }
            if (!is_numeric($price) || strpos($price, ".") !== false) {
                $this->ajax('交易价格必须是100的正整数的倍数！');
            }
            //用户
            $user = new UserModel();
            $usertype = $user->where("uid={$this->mCurUser['uid']} and cardtype!=0")->fRow();
            if (!$usertype) {
                $this->ajax('请先认证！', 0);

            }
            //获取验证码
            for (; true;) {
                $tradeno = $this->tradeno('c2c');
                if (!$C2ctrade->where(array('tradeno' => $tradeno))->fRow()) {
                    break;
                }
            }
            // 买单
            if ($type == 1) {
                // $bank = $Userbank->where("uid={$this->mCurUser['uid']} and status=1")->fRow();
                // if (!$bank) {
                //     $this->ajax('请绑定付款方式，是否开启状态！', 0);
                // }

                // $count = $C2ctrade->where(['uid' => $this->mCurUser['uid'], 'type' => 1, 'status' =>0])->count();
                // if ($count >= 5) {
                //     $this->ajax('只能挂五笔买单', 0);
                // }
                $buy_paypassword = $_POST['val'];
                $userpassword = $user->where("uid={$this->mCurUser['uid']} and status!=0")->fList();
                $pwdtrade  = Tool_Md5::encodePwd($buy_paypassword, $userpassword['pwdtrade']);

                // if ($pwdtrade != $userpassword['pwdtrade']) {
                //     $this->ajax('交易密码错误',0);
                // }
                // 用户未付款点击已付款，只允许每个用户存在两笔这样的订单
                $two = count($C2ctrade->where("userid={$this->mCurUser['uid']} and status=0 and matching!=0")->fList());
                if ($two - 2 >= 0) {
                    $this->ajax('您还有未完成交易的订单', 0);
                }

                $bank = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='1'")->fRow();
                $wechat = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='2'")->fRow();
                $alipay = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='3'")->fRow();
                # 事务开始
                $C2ctrade->begin();
                $data = [
                    'uid' => $this->mCurUser['uid'],
                    'coin' => 'cnyx',
                    'price' => $price,//总价格
                    'num' => 0,//匹配价格
                    'deal' => $price,//剩余数量
                    'tradeno' => $tradeno,
                    'type' => 1,
                    'fee' => 0,
                    'moble' => $usertype['mo'] ? $usertype['mo'] : $usertype['email'],
                    'bank' => $bank['type'] ? $bank['type'] : 0,
                    'wechat' => $wechat['type'] ? $wechat['type'] : 0,
                    'alipay' => $alipay['type'] ? $alipay['type'] : 0,
                    'addtime' => time(),
                    'matchtime' => 0,
                    'matching' => 0,
                    'status' => 0,
                ];

                $rs = $C2ctrade->insert($data);
                if ($rs) {
                    $C2ctrade->commit();
                    $this->c2cmarket($tradeno);
                    $this->ajax('挂单成功', 0);
                } else {
                    $C2ctrade->back();
                    $this->ajax('挂单失败', 0);
                }
            }

            // 卖单
            if ($type == 2) {
                $count = $C2ctrade->where(['uid' => $this->mCurUser['uid'], 'type' => 2, 'status' => 0])->count();

                //    if ($count >= 2) {
                //        //$GLOBALS['MSG']['']
                //        $this->ajax('只能挂五笔卖单', 0);
                //    }
                $bank = $Userbank->where("uid={$this->mCurUser['uid']} and status=1")->fRow();
                //    if (!$bank) {
                //        $this->ajax('请绑定付款方式，是否开启状态！', 0);
                //    }
                $year = date("Y");
                $month = date("m");
                $day = date("d");
                $addti = mktime(0, 0, 0, $month, $day, $year);//当天开始时间戳
                $endti = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
                $count = $C2ctrade->where(['userid' => $this->mCurUser['uid'], 'type' => 2, 'status' => 1, 'addtime' => ['between', "$addti,$endti"]])->count();

                if ($count >= 4) {
                    $this->ajax('当天只能提现四笔交易');
                }

                $sell_paypassword = $_POST['val'];
                $userpassword = $user->where("uid={$this->mCurUser['uid']} and status!=0")->fList();
                $pwdtrade  = Tool_Md5::encodePwd($sell_paypassword, $userpassword['pwdtrade']);
                // if ($pwdtrade!= $userpassword['pwdtrade']) {
                //     $this->ajax('交易密码错误');
                // }
                if ($price < 100) {
                    $this->ajax('最小限额不可以小于100');
                }

                if ($price % 100 != 0) {
                    $this->ajax('最小匹配数量必须是100的整数倍');
                }
                $cnyx = $UserModel->where("uid={$this->mCurUser['uid']}")->fRow();//用户实际财产
                $usercnyx = floatval($cnyx['cnyx_over']);
                if (floatval($price) > $usercnyx) {
                    $this->ajax('您的余额不足!', 0);
                }
                # 事务开始
                $UserModel->begin();
                //用户余额冻结
                $rs = [];
                $mo = Orm_Base::getInstance();
                $rs[] = $mo->exec("update user set cnyx_over=cnyx_over-{$price} where uid={$this->mCurUser['uid']}");
                $rs[] = $mo->exec("update user set cnyx_lock=cnyx_lock+{$price} where uid={$this->mCurUser['uid']}");
                $bank = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='1'")->fRow();
                $wechat = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='2'")->fRow();
                $alipay = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='3'")->fRow();
                $data = [
                    'uid' => $this->mCurUser['uid'],
                    'coin' => 'cnyx',
                    'price' => $price,//总价格
                    'num' => 0,//匹配价格
                    'deal' => $price,//剩余价格
                    'tradeno' => $tradeno,
                    'type' => 2,
                    'fee' => 0,
                    'moble' => $usertype['mo'] ? $usertype['mo'] : $usertype['email'],
                    'bank' => $bank['type'] ? $bank['type'] : 0,
                    'wechat' => $wechat['type'] ? $wechat['type'] : 0,
                    'alipay' => $alipay['type'] ? $alipay['type'] : 0,
                    'addtime' => time(),
                    'matchtime' => 0,
                    'matching' => 0,
                    'status' => 0,
                ];
                $rs[] = $C2ctrade->insert($data);
                if ($rs) {
                    $UserModel->commit();
                    $this->c2cmarket($tradeno);
                    $this->ajax('挂单成功!');
                } else {
                    $UserModel->back();

                    $this->ajax('挂单失败!');

                }
            }
        }
    }

    //撮合买卖
    public function c2cmarket($tradeno)
    {
        $this->_ajax_islogin();
        $C2ctrade = new C2ctradeModel();
        $UserModel = new UserModel();
        $C2ctradelog = new C2ctradelogModel();
        $trade = $C2ctrade->where(['tradeno' => $tradeno])->fRow();
        while (!$trade) {
            $trade = $C2ctrade->where(['tradeno' => $tradeno])->fRow();
            sleep(1);
        }
        if ($trade['matching'] != 0) {
            $this->ajax('订单已成功匹配!');
        }

        if ($trade['type'] == 1) {
            $where = "status=0 and type=2 and matching=0 and deal>=100 and (bank={$trade['bank']} or wechat={$trade['wechat']} or alipay={$trade['alipay']})";
            $C2ctrade->begin();
            $sell = $C2ctrade->where($where)->order('addtime asc,id asc')->fRow();
            //判断卖家资产
            $coin = $UserModel->where(['uid' => $sell['uid']])->fRow();
            //手续费计算
            $year = date("Y");
            $month = date("m");
            $day = date("d");
            $addti = mktime(0, 0, 0, $month, $day, $year);//当天开始时间戳
            $endti = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
            $count = $C2ctrade->where(['uid' =>$sell['uid'], 'type' => 2, 'status' => 1, 'addtime' => ['between', "$addti,$endti"]])->count();
            //手续费计算
            if($sell['deal'] > $trade['deal']){
                $feenum= $sell['deal'] - $trade['deal'] ;
            }else if($sell['deal'] < $trade['deal']){
                $feenum= $trade['deal'] - $sell['deal'];
            }else{
                $feenum= $trade['deal'];
            }
            if ($count > 2){
                $bili = ($count-1)*0.005;
                $fee_sell = $feenum * $bili < 5 ? 5 : $count *$bili;
            }else{
                $fee_sell = $feenum * 0.005 < 5 ? 5 : $count *0.005;
            }
            if ($sell && $coin['cnyx_over'] > $fee_sell) {
                //匹配冻结
                $mo = Orm_Base::getInstance();
                $rs[] = $mo->exec("update user set cnyx_over=cnyx_over-{$fee_sell} where uid={$sell['uid']}");
                $rs[] = $mo->exec("update user set cnyx_lock=cnyx_lock+{$fee_sell} where uid={$sell['uid']}");
                //匹配处理用户金额
                if ($sell['deal'] > $trade['deal']) {
                    $rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['deal' => $sell['deal'] - $trade['deal']]);
                    $rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' => 0]);
                } else {
                    $rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['deal' => 0]);
                    $rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' => $trade['deal'] - $sell['deal']]);
                }
                $rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['num' => $feenum]);
                $rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['num' =>$feenum]);
                $rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['fee' => $fee_sell, 'matching' => $this->mCurUser['uid'], 'matchtime' => time()]);
                $rs[] = $C2ctrade->where(['uid' => $this->mCurUser['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $tradeno])->update(['matching' => $sell['uid'], 'matchtime' => time()]);
                $buyname = $UserModel->where(['uid' => $this->mCurUser['uid']])->fRow();
                $sellname = $UserModel->where(['uid' => $sell['uid']])->fRow();
                $datalog = [
                    'buyid' => $this->mCurUser['uid'],
                    'sellid' => $sell['uid'],
                    'coinname' => $trade['coin'],
                    'price' =>$feenum,
                    'buytruename' => 0,
                    'buymoble' => $buyname['mo'] ? $buyname['mo'] : $buyname['email'],
                    'buytradeno' => $tradeno,
                    'selltruename' => 0,
                    'sellmoble' => $sellname['mo'] ? $sellname['mo'] : $sellname['email'],
                    'selltradeno' => $sell['tradeno'],
                    'addtime' => time(),
                    'bank' => $sell['bank'],
                    'wechat' => $sell['wechat'],
                    'alipay' => $sell['alipay'],
                    'type' => 2,
                    'feesell' => $fee_sell,
                    'status' => 0,
                ];
                $rs[] = $C2ctradelog->insert($datalog);
                if ($rs) {
                    $C2ctrade->commit();
                    $this->ajax('匹配成功!', 1);
                } else {
                    $C2ctrade->rollback();
                    $this->ajax('下单成功!', 1);
                }
            }
        } else if ($trade['type'] == 2) {
            $where = "status=0 and type=1 and matching=0  and deal>=100 and (bank={$trade['bank']} or wechat={$trade['wechat']} or alipay={$trade['alipay']})";
            $C2ctrade->begin();
            $buy = $C2ctrade->where($where)->order('addtime asc,id asc')->fRow();
            //用户财产
            $cnyx = $UserModel->where("uid={$this->mCurUser['uid']}")->fRow();
            //手续费计算
            $year = date("Y");
            $month = date("m");
            $day = date("d");
            $addti = mktime(0, 0, 0, $month, $day, $year);//当天开始时间戳
            $endti = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
            $count = $C2ctrade->where(['uid' =>$this->mCurUser['uid'], 'type' => 2, 'status' => 1, 'addtime' => ['between', "$addti,$endti"]])->count();
            //手续费计算

            if($buy['deal'] > $trade['deal']){
                $feenum= $buy['deal'] - $trade['deal'] ;
            }else if($buy['deal'] < $trade['deal']){
                $feenum= $trade['deal'] - $buy['deal'];
            }else{
                $feenum= $trade['deal'];
            }

            if ($count > 2){
                $bili = ($count-1)*0.005;
                $fee_sell = $feenum * $bili < 5 ? 5 : $count *$bili;
            }else{
                $fee_sell = $feenum * 0.005 < 5 ? 5 : $count *0.005;
            }
            $usercnyx = floatval($cnyx['cnyx_over']);
            if (floatval($fee_sell) > $usercnyx) {
                $this->ajax('您的余额不足!');
            }

            if ($buy && $cnyx['cnyx_over'] > $fee_sell) {
                //匹配手续费冻结
                $mo = Orm_Base::getInstance();
                $rs[] = $mo->exec("update user set cnyx_over=cnyx_over-{$fee_sell} where uid={$this->mCurUser['uid']}");
                $rs[] = $mo->exec("update user set cnyx_lock=cnyx_lock+{$fee_sell} where uid={$this->mCurUser['uid']}");
                //匹配处理用户金额
                if ($buy['deal'] > $trade['deal']) {
                    $rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['deal' => $buy['deal'] - $trade['deal']]);
                    $rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' => 0]);
                } else {
                    $rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['deal' => 0]);
                    $rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' => $trade['deal'] - $buy['deal']]);
                }
                $rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['num' =>$feenum]);
                $rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['num' =>$feenum]);
                $rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['matching' => $this->mCurUser['uid'], 'matchtime' => time()]);
                $rs[] = $C2ctrade->where(['uid' => $this->mCurUser['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $tradeno])->update(['fee' => $fee_sell, 'matching' => $buy['uid'], 'matchtime' => time()]);
                $sellname = $UserModel->where(['uid' => $this->mCurUser['uid']])->fRow();
                $buyname = $UserModel->where(['uid' => $buy['uid']])->fRow();
                $datalog = [
                    'buyid' => $buy['uid'],
                    'sellid' => $this->mCurUser['uid'],
                    'coinname' => $trade['coin'],
                    'price' => $feenum,
                    'buytruename' => 0,
                    'buymoble' => $buyname['mo'] ? $buyname['mo'] : $buyname['email'],
                    'buytradeno' => $buy['tradeno'],
                    'selltruename' => 0,
                    'sellmoble' => $sellname['mo'] ? $sellname['mo'] : $sellname['email'],
                    'selltradeno' => $tradeno,
                    'addtime' => time(),
                    'bank' => $buy['bank'],
                    'wechat' => $buy['wechat'],
                    'alipay' => $buy['alipay'],
                    'type' => 2,
                    'feesell' => $fee_sell,
                    'status' => 0,
                ];

                $rs[] = $C2ctradelog->insert($datalog);
                if ($rs) {
                    $C2ctrade->commit();
                    $this->ajax('匹配成功!', 1);
                } else {
                    $C2ctrade->rollback();
                    $this->ajax('下单成功!', 1);
                }
            }
        }
    }
    //已付款
    public function payAction()
    {
        $id = $_POST['id'];

        $C2ctrade = new C2ctradeModel();
        $C2ctradelog = new C2ctradelogModel();
        //修改订单状态
        if ($id) {
            $trade = $C2ctrade->where(['id' => $id])->fRow();
            if ($trade['status'] != 0) {
                $this->ajax('订单已经处理过!', 0);
            }

            if ($trade['type'] == 1) {
                # 事务开始
                $C2ctrade->begin();
                $rs = [];
                $rs[] = $C2ctrade->where(['id' => $id])->update(['status' => 2]);
                $rs[] = $C2ctradelog->where(['buytradeno' => $trade['tradeno']])->update(['status' => 2]);
                $sell = $C2ctradelog->where(['buytradeno' => $trade['tradeno']])->fRow();
                $rs[] = $C2ctrade->where(['tradeno' => $sell['selltradeno']])->update(['status' => 2]);
                if ($rs) {
                    $C2ctrade->commit();
                    $sellmoble = $sell['sellmoble'];
                    //短信通知暂无
                    // -------

                    $this->ajax('付款成功!', 0);

                } else {
                    $C2ctrade->rollback();
                    $this->ajax('付款失败!', 0);
                }
            }
        }
    }

    //确认收款

    public function confirmAction()
    {
        $id = $_POST['id'];

        $C2ctrade = new C2ctradeModel();
        $C2ctradelog = new C2ctradelogModel();
        if ($id) {
            $trade = $C2ctrade->where(['id' => $id])->fRow();
            if ($trade['matching' == 0 && $trade['status'] == 0]) {

                $this->ajax('订单正在匹配！,0');

            }
            $log = $C2ctradelog->where(['selltradeno' => $trade['tradeno']])->order('id desc')->fRow();
            $buy = $C2ctrade->where(['tradeno' => $log['buytradeno']])->order('id desc')->fRow();
            //判断是用户之间的交易
            if ($trade['type'] == 2) {
                if ($trade['matching'] != 0 && $trade['status'] == 1) {

                    $this->ajax('订单已成交，不可以重复操作！', 0);

                }
                //修改订单状态
                $C2ctrade->begin();
                if ($trade && ($trade['status'] == 1 || $trade['status'] == 3)) {
                    $C2ctrade->rollback();
                    $this->ajax('卖出失败,0');

                }
                $rs = [];

                //卖家判断是否卖完

                if ($trade['deal'] <= 0) {

                    $rs[] = $C2ctrade->where(['tradeno' => $trade['tradeno']])->update(['status' => 1]);

                } else {

                    $rs[] = $C2ctrade->where(['tradeno' => $trade['tradeno']])->update(['matchtime' => 0, 'matching' => 0, 'status' => 0]);
                }
                //买家判断是否买完

                if ($buy['deal'] <= 0) {

                    $rs[] = $C2ctrade->where(['tradeno' => $log['buytradeno']])->update(['status' => 1]);
                } else {

                    $rs[] = $C2ctrade->where(['tradeno' => $log['buytradeno']])->update(['matchtime' => 0, 'matching' => 0, 'status' => 0]);

                }
                $coin = 'cnyx';
                $price = floatval($log['price']);
                $mo = Orm_Base::getInstance();
                $rs[] = $mo->exec("update user set {$coin}_over={$coin}_over+{$price} where uid={$buy['uid']}");
                $rs[] = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$price} where uid={$trade['uid']}");
                $rs[] = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$trade['fee']} where uid={$trade['uid']}");
                $C2ctradelog->where(['selltradeno' => $trade['tradeno'], 'status' => ['!=', 2]])->update(['status' => 1]);
                if ($rs) {
                    $C2ctrade->commit();

                    $this->ajax('卖出成功', 1);

                } else {
                    $C2ctrade->rollback();
                    $this->ajax('卖出失败', 0);
                }
            }
        }
    }

    //撤单
    public function revokeAction()
    {
        $id = $_POST['id'];
        if ($id) {
            $trade = C2ctradeModel::getInstance()->where(['id' => $id])->fRow();
            if ($trade['status'] != 0 && $trade['matching'] == 0) {
                $this->ajax('订单不可撤销！', 0);
            }
            if ($trade['status'] == 2) {
                $this->ajax('订单已撤销！', 0);
            }
            if ($trade['status'] == 1) {
                $this->ajax('订单已完成！', 0);
            }
            //买单撤销
            if ($trade['type'] == 1) {
                if ($trade['matching'] != 0) {
                    $this->ajax('订单已匹配成功，无法撤单', 0);
                }
                //匹配记录
                $data = C2ctradelogModel::getInstance()->where(['buytradeno' => $trade['tradeno']])->fRow();
                //卖家
                $sell = C2ctradeModel::getInstance()->where(['tradeno' => $data['selltradeno']])->fRow();
                # 事务开始
                $mo = Orm_Base::getInstance();
                $mo->begin();
                $rs = [];
                if($trade['matching'] != 0){
                    $rs[] = $mo->table('c2c_trade')->where(['id' => $id])->update(['status' => 3,'deal'=>0]);
                    $rs[] = $mo->table('c2c_trade')->where(['tradeno' => $data['selltradeno']])->update(['status' => 0,'matching'=>0,'matchtime'=>0,'num'=>0]);
                    //卖
                    $rs[] = $mo->exec("update c2c_trade set deal=deal+{$data['price']}) where tradeno={$data['selltradeno']}");
                    //记录取消为2
                    $rs[] = $mo->table('c2c_log')->where(['id' => $data['id']])->update(['status' => 2]);
                }else{
                    $rs[] = C2ctradeModel::getInstance()->where(['id' => $id])->update(['status' => 3, 'deal' => 0]);
                }
                //买家


                if ($rs) {
                    $mo->commit();
                    $this->ajax('撤单成功！', 1);
                } else {
                    $mo->rollback();
                    $this->ajax('撤单失败！', 0);
                }

            } else {
                //卖单撤销
                if ($trade['matching'] != 0) {
                    $this->ajax('订单已匹配成功，无法撤单', 0);
                }
                //匹配记录
                $data = C2ctradelogModel::getInstance()->where(['selltradeno' => $trade['tradeno']])->fRow();
                //买家
                $buy = C2ctradeModel::getInstance()->where(['tradeno' => $data['selltradeno']])->fRow();
                # 事务开始
                $mo = Orm_Base::getInstance();
                $mo->begin();
                $rs = [];
                //匹配撤单处理买家卖家金额
                 if($trade['matching'] != 0){
                     //买
                     $rs[] = C2ctradeModel::getInstance()->where(['tradeno' => $data['buytradeno']])->update(['status' => 0,'matching'=>0,'matchtime'=>0,'num'=>0]);
                     $rs[] = $mo->exec("update c2c_trade set deal=deal+{$data['price']}) where tradeno={$data['buytradeno']}");
                     //卖
                     //$rs[] = $mo->exec("update c2c_trade set deal=0 where uid={$data['sellid']}");
                     $rs[] = $mo->table('c2c_log')->where(['id' => $id['id']])->update(['status' => 3,'deal'=>0]);
                     $rs[] = $mo->exec("update user set cnyx_over=cnyx_over+({$trade['deal']}+{$data['price']}+{$trade['fee']}) where uid={$data['sellid']}");
                     $rs[] = $mo->exec("update user set cnyx_lock=cnyx_lock-({$trade['deal']}+{$data['price']}+{$trade['fee']}) where uid={$data['sellid']}");

                     //记录取消为2
                     $rs[] = $mo->table('c2c_log')->where(['id' => $data['id']])->update(['status' => 2]);
                 }else{
                     //卖家
                     $rs[] = $mo->exec("update user set cnyx_over=cnyx_over+({$trade['deal']}) where uid={$data['sellid']}");
                     $rs[] = $mo->exec("update user set cnyx_lock=cnyx_lock-({$trade['deal']}) where uid={$data['sellid']}");
                     $rs[] = C2ctradeModel::getInstance()->where(['id' => $id])->update(['status' => 3, 'deal' => 0]);
                 }
                if ($rs) {
                    $mo->commit();
                    $this->ajax('撤单成功！', 1);
                } else {
                    $mo->rollback();
                    $this->ajax('撤单失败！', 0);
                }
            }
        }
    }

    //展示弹窗
    public function alertAction()
    {
        $id = $_POST['id'];
        $paytype = $_POST['type'];

        $C2ctrade = new C2ctradeModel();
        $C2ctradelog = new C2ctradelogModel();
        $Userbank = new UserbankModel();
        if ($id && $paytype) {
            $trade = $C2ctrade->where(['id' => $id])->fRow();
            if ($trade['matching'] !=0) {
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
                $log = $C2ctradelog->where("buytradeno or selltradeno", $trade['tradeno'])->order('id desc')->fRow();
//               var_dump($trade['tradeno']);
                if ($trade['type'] == 1) {
                    $selltrade = $C2ctrade->where(array('tradeno' => $log['selltradeno']))->fRow();
                    $sellbank = $Userbank->where(array('uid' => $log['sellid'], 'status' => 1, 'type' => $paytype))->fRow();
                } else {
                    $buytrade = $C2ctrade->where(array('tradeno' => $log['buytradeno']))->fRow();
                    $buybank = $Userbank->where(array('uid' => $log['buyid'], 'status' => 1, 'type' => $paytype))->fRow();
                }

                // var_dump($buybank);die;

                if ($trade['type'] == 1) {

                    echo json_encode([
                        'tradeId' => $trade['id'],
                        'sts' => 1,
                        'name' => $log['selltruename'],
                        'sellid' => $sellbank['uid'],
                        'bankaddr' => $sellbank['bank'],
                        'bankcard' => $sellbank['bankcard'],
                        'num' => $log['price'],
                        'tradeno' => $log['selltradeno'],
                        'type'=>$selltrade['type'],
                        'paytype' => $sellbank['type'],
                        'status' => $status,
                        'moble' => $log['sellmoble'],
                        'img' => 'https://firecoin.oss-cn-shenzhen.aliyuncs.com/Upload/public/' . $sellbank['img'],
                        'matchtime' => $trade['matchtime'],
                    ]);
                    exit();
                } else {
                    echo json_encode([
                        'tradeId' => $trade['id'],
                        'sts' => 1,
                        'name' => $log['buytruename'],
                        'sellid' => $buybank['userid'],
                        'bankaddr' => $buybank['bank'],
                        'bankcard' => $buybank['bankcard'],
                        'num' => $log['price'],
                        'tradeno' => $log['buytradeno'],
                        'type'=>$buytrade['type'],
                        'paytype' => $buybank['type'],
                        'status' => $status,
                        'moble' => $log['buymoble'],
                        'img' => 'https://firecoin.oss-cn-shenzhen.aliyuncs.com/Upload/public/' . $buybank['img'],
                        'matchtime' => $trade['matchtime'],


                    ]);
                    exit();
                }

            }else{
                $this->ajax('非法操作',0);
            }

        }
    }

    //获取流水号
    function tradeno($type = '')
    {
        if ($type == 'c2c') {
            $length = 5;
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $string = '';
            for ($i = 0; $i < $length; $i++) {
                // 取字符数组 $chars 的任意元素
                $string .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            return $string;
        } else {
            return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 2) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 3);
        }
    }
}