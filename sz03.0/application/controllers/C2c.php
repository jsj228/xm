<?php
class C2cController extends Ctrl_Base
{

    protected $_auth = 3;
    function init(){
        parent::init();
        $this->assign('pageName', $this->_request->action);
    }
    //用户c2c
    public function indexAction($page=1)
    {

        $this->_ajax_islogin();
        Tool_Session::mark($this->mCurUser['uid']);
        $C2ctrade = new C2ctradeModel();
        // 当前总记录条数
        isset($_GET['p']) or $_GET['p'] = intval($page);
        $data['totalsell']= $C2ctrade->where(['status' =>0,'type'=>2,'moble'=>15586991887,'deal_id'=>0])->count();
        // 获取分页显示
        $tPage = new Tool_Page($data['totalsell'],10);
        $data['pageinfosell']= $tPage->show();
        $data['listsell'] = $C2ctrade->field('*')
            ->where(['status' =>0,'type'=>2,'moble'=>15586991887,'deal_id'=>0])
            ->limit(5)
            ->order('id desc')
            ->fList();
        foreach($data['listsell'] as $k=>$v){
            $data['listsell'][$k]['sellmoble'] =substr_replace($v['moble'], '****', 3, 4);
        }
        $urgent = UrgentModel::getInstance()->where("id!=0")->fRow();

        //买单
        $UserModel = new UserModel();
        $usertype = $UserModel->where("uid={$this->mCurUser['uid']}")->fRow();
       if($usertype['mo']=='15586991887'){
           $atime = time()-(72000);
           //and addtime >$atime
           $data['buytsell'] = $C2ctrade ->where("status=0 and type=1 and moble!=15586991887 and deal_id=0 and addtime >$atime")->limit(5)->order('id desc')->fList();
           foreach($data['buytsell'] as $k=>$v){
               $data['buytsell'][$k]['buymoble'] =substr_replace($v['moble'], '****', 3, 4);
           }
       }
        $repeat_del = md5(time().rand(10000,9999));;
        $_SESSION['repeat_del'] = $repeat_del;
        $this->assign("repeat_del",$repeat_del);
        // Tool_Out::p( $data['buytsell']);die;
        $this->assign('urgent', $urgent);
        $this->assign('data', $data);

    }
    //商家
    public function businessAction(){
        $uid=$this->mCurUser['uid'];
        $atime = time()-(72000);
        if ($uid == 28830 || $uid == 28831 || $uid ==17797) {
            $this->_ajax_islogin();
            $C2ctrade = new C2ctradeModel();
            $C2ctradelog = new C2ctradelogModel();
            // 获取分页显示
            $page = $_REQUEST['page']?:1;
            $pageSize = 10;
            $where="status=0 and deal_id=0 and addtime < $atime";
            //用户所有订单
            $list = $C2ctrade->where($where)->page($page, $pageSize)->order('id desc')->fList();
            $count = $C2ctrade->where($where)->count();
            $data = array('list'=>array(), 'totalPage'=>ceil($count/$pageSize));
            foreach ($list as $k => $v) {
                $data['list'][] = array(
                    'id'=>$v['id'],
                    'price'=>$v['price'],
                    'num'=>$v['num'],
                    'deal'=>$v['deal'],
                    'coin'=>$v['coin'],
                    'tradeno'=>$v['tradeno'],
                    'type'=>$v['type'],
                    'fee'=>$v['fee'],
                    'moble'=>$v['moble'],
                    'deal_time'=>$v['deal_time'],
                    'deal_id'=>$v['deal_id'],
                    'bank'=>$v['bank'],
                    'wechat'=>$v['wechat'],
                    'alipay'=>$v['alipay'],
                    'selltype'=>$v['selltype'],
                    'addtime'=>date('Y-m-d H:i:s', $v['addtime']),
                    'status'=>$v['status'],
                );
                if($v['selltype']==1) {
                    $data['list'][$k]['username'] = '普通';
                }
                if($v['selltype']==2){
                    $data['list'][$k]['username'] = '加急';
                }

            }

            $this->ajax('',1,$data);

        }else{
            $this->ajax('1111',0);
        }

    }


    public function ajaxorderAction(){
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $C2ctrade = new C2ctradeModel();
        $C2ctradelog = new C2ctradelogModel();
        $Userbank = new UserbankModel();
        // 获取分页显示
        $page = $_REQUEST['page']?:1;
        $pageSize = 10;
        $where       = array(
            'uid' => $this->mCurUser['uid'],
        );
        //用户所有订单
        $list = $C2ctrade->where($where)->page($page, $pageSize)->order('id desc')->fList();
        $count = $C2ctrade->where($where)->count();
        $data = array('list'=>array(), 'totalPage'=>ceil($count/$pageSize));
        foreach ($list as $k => $v) {
            $data['list'][] = array(
                'id'=>$v['id'],
                'price'=>$v['price'],
                'num'=>$v['num'],
                'deal'=>$v['deal'],
                'coin'=>$v['coin'],
                'tradeno'=>$v['tradeno'],
                'type'=>$v['type'],
                'fee'=>$v['fee'],
                'moble'=>$v['moble'],
                'deal_time'=>$v['deal_time'],
                'deal_id'=>$v['deal_id'],
                'selltype'=>$v['selltype'],
                'addtime'=>date('Y-m-d H:i:s', $v['addtime']),
                'status'=>$v['status'],
            );
            if($v['type']==1){
                $bank = $Userbank->where(['uid' =>$v['deal_id'] ,'status'=>1, 'type'=>1])->fRow();
                $wechat = $Userbank->where(['uid' =>$v['deal_id'] ,'status'=>1, 'type'=>2])->fRow();
                $alipay = $Userbank->where(['uid' =>$v['deal_id'] ,'status'=>1, 'type'=>3])->fRow();
                if($v['deal_id']!=0){
                    $data['list'] [$k]['wx'] =$wechat['type'];
                    $data['list'] [$k]['yhk'] = $bank['type'];
                    $data['list'] [$k]['yfb'] = $alipay['type'];
                }
            }
            if($v['type']==2){
                $bank = $Userbank->where(['uid' =>$v['deal_id'] ,'status'=>1, 'type'=>1])->fRow();
                $wechat = $Userbank->where(['uid' =>$v['deal_id'] ,'status'=>1, 'type'=>2])->fRow();
                $alipay = $Userbank->where(['uid' =>$v['deal_id'] ,'status'=>1, 'type'=>3])->fRow();
                if($v['deal_id']!=0){
                    $data['list'] [$k]['wx'] =$wechat['type'];
                    $data['list'] [$k]['yhk'] = $bank['type'];
                    $data['list'] [$k]['yfb'] = $alipay['type'];
                }
            }


        }
        $user =  new UserModel();
        $usercoin = $user->where(['uid'=>$this->mCurUser['uid']])->fRow();
        $data['cnyx_over'] =  round($usercoin['cnyx_over'],2);
        $data['cnyx_lock'] =  round($usercoin['cnyx_lock'],2);
        // Tool_Out::p($data);die;
        $this->ajax('',1,$data);
    }

    //用户挂单
    public function tradeAction()
    {
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交");
        }
        $c2ctradeMo = new C2ctradeModel();
        $userMo = new UserModel();
        $userbankMo = new UserbankModel();

        $price = floatval($_POST['num']);//下单金额
        $type = trim($_POST['type']);//下单类型 1:买  2：卖
        $pwdtradeval = $_POST['val'];
        $selltype =intval($_POST['selltype']); //$_POST['selltype'];
        $uid = $this->mCurUser['uid'];


        if(!$type || !$price) $this->ajax('请输入金额');
        if ($price < 100) $this->ajax('交易的金额最低为100！');
        if ($price % 100 != 0) $this->ajax('交易价格必须是100的整数倍！');
        if (!is_numeric($price) || strpos($price, ".") !== false) $this->ajax('请检查交易的金额');

        $AutonymModel = new AutonymModel();
        if (!$idcard = $AutonymModel->where("uid={$uid} and status=2")->fRow()) $this->ajax('请先实名认证！');

        //获取验证码
        for (; true;) {
            $tradeno = $this->tradeno('c2c');
            if (!$c2ctradeMo->where(array('tradeno' => $tradeno))->fRow()) break;
        }

        $txt = $type==1?'买':'卖';

        $daytime = strtotime(date("Y-m-d"));
        $count = $c2ctradeMo->where("uid={$uid} and type=$type and addtime>$daytime")->fOne("count(id)");
//        $this->ajax($count,0,$c2ctradeMo->getLastSql());
        if ($count >= 5) $this->ajax("每天只能挂五笔{$txt}单");

        $userInfo = $userMo->fRow($uid);

        //验证交易密码
        if ($userInfo['pwdtrade'] != $pwdtrade = Tool_Md5::encodePwdTrade($pwdtradeval,$userInfo['prand'])) $this->ajax('交易密码错误',0);

        //买单用户 未付款点击已付款，只允许每个用户存在两笔这样的订单
        if($type==1){
            $buy_count = $c2ctradeMo->where("uid={$uid} and status!=1 and status!=3 and type=1")->fOne("count(id)");
            if ($buy_count) $this->ajax('您还有未完成交易的订单');
        }

        if($type==2){
            $fee_count = $c2ctradeMo->where("uid={$uid} and type=$type and addtime>$daytime")->fOne("count(id)");

            if($fee_count<=1) $fee = $price*0.005*$selltype<5?5:round($price*0.005*$selltype,2);
            if($fee_count==2) $fee = $price*0.01*$selltype<5?5:round($price*0.01*$selltype,2);
            if($fee_count>=3) $fee = $price*0.015*$selltype<5?5:round($price*0.015*$selltype,2);

            $fee_price = $fee+$price;
            if ($fee_price > floatval($userInfo['cnyx_over'])) $this->ajax("您的余额不足,该笔订单需要{$fee}CNYX");//余额不足

            $minnum = ceil(($price/100/5))*100;//计算最小匹配额
        }

        $userBank = $userbankMo->field('type')->where("uid = {$uid} and status=1")->fList();
        if(!$userBank) $this->ajax("请绑定并开启可用的付款方式");
        $userBank = array_column($userBank,'type');


        //写入数据
        # 事务开始
        $c2ctradeMo->begin();

        $data = [
            'uid' => $uid,
            'coin' => 'cnyx',
            'price' => $price,//总价格
            'num' => 0,//匹配价格
            'deal' => $price,//剩余数量
            'tradeno' => $tradeno,
            'min'=>$type==2?$minnum:0,
            'type' => $type,
            'fee'=>$type==2?$fee:0,
            'selltype'=>$type==2?$selltype:0,
            'moble' => $userInfo['mo'] ? $userInfo['mo'] : $userInfo['email'],
            'bank' => in_array(1,$userBank) ? 1 : 0,
            'wechat' => in_array(2,$userBank) ? 2 : 0,
            'alipay' => in_array(3,$userBank) ? 3 : 0,
            'addtime' => time(),
        ];

        try{
            if($type==2) $up_id = $c2ctradeMo->exec("update user set cnyx_over=cnyx_over-{$fee_price},cnyx_lock=cnyx_lock+{$fee_price} where uid={$uid}");
            $in_id = $c2ctradeMo->insert($data);

            if (($up_id && $in_id && $type==2) || ($type==1 && $in_id)){
                $c2ctradeMo->commit();
                $this->c2cmarket($in_id,$userBank);
                $this->ajax('挂单成功!',1,["repeat"=>$newRepeat]);
            } else {
                $c2ctradeMo->back();
                $this->ajax('挂单失败!');
            }
        }catch (Exception $e){
            $c2ctradeMo->back();
            $this->ajax('挂单失败!');
        }
    }

    //撮合买卖
    public function c2cmarket($id,$userBank)
    {
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $c2ctradeMo = new C2ctradeModel();
        $userMo = new UserModel();
        $c2ctradelogMo = new C2ctradelogModel();
        $mo = Orm_Base::getInstance();

        $uid = $this->mCurUser['uid'];

        //当前订单
        $order = $c2ctradeMo->where("id=$id")->fRow();

        if ($order['type'] == 1) {
            //and  price >= {$trade['price']} and min <= {$trade['price']}
            $atime=time()-86400;

            //找到待匹配的卖单
            $where = "status=0 and type=2 and moble!='15586991887' and uid!={$uid} and deal_id=0 and addtime >$atime and (bank={$order['bank']} or wechat={$order['wechat']} or alipay={$order['alipay']}) and ((min<={$order['price']} and deal>={$order['price']}) or deal={$order['price']})";
            $sell = $c2ctradeMo->lock()->where($where)->order('addtime asc,id asc')->fRow();

            $sale_price = $order['price']>=$sell['deal']?$sell['deal']:$order['price'];
            $fee = round($sale_price/$sell['price']*$sell['fee'],2);
            $time = time();

            $buy_user = $userMo->fRow($uid);
            $sell_user = $userMo->fRow($sell['uid']);

            if ($sell) {
                $datalog = [
                    'buyid' => $uid,
                    'sellid' => $sell['uid'],
                    'coinname' => $sell['coin'],
                    'price' => $sale_price,
                    'buymoble' => $buy_user['mo'] ? $buy_user['mo'] : $buy_user['email'],
                    'buytradeno' => $order['tradeno'],
                    'sellmoble' => $sell_user['mo'] ? $sell_user['mo'] : $sell_user['email'],
                    'selltradeno' => $sell['tradeno'],
                    'addtime' => $time,
                    'bank' => $sell['bank'],
                    'wechat' => $sell['wechat'],
                    'alipay' => $sell['alipay'],
                    'type' => 1,
                    'feesell' => $fee,
                ];

                # 事务开始
                $mo->begin();
                //匹配冻结
                try{
                    //匹配处理用户金额
                    $sell_id = $mo->exec("update c2c_trade set deal=deal-{$sale_price},num=num+{$sale_price},fee_on=fee_on+{$fee},deal_id={$uid},deal_time={$time} where id={$sell['id']}");

                    $buy_id = $mo->exec("update c2c_trade set deal=deal-{$sale_price},num=num+{$sale_price},deal_id={$sell['uid']},deal_time={$time} where id={$id}");
                    $in_id = $c2ctradelogMo->insert($datalog);

                    if ($sell_id && $buy_id && $in_id) {
                        $mo->commit();

                        $this->ajax('匹配成功!', 1);
                    } else {
                        $mo->rollback();
                    }
                }catch (Exception $e){
                    $mo->back();
                }
            }
        } else if ($order['type'] == 2) {
            $small = $order['min'];
            $big = $order['price'];

            $where = "status=0 and type=1 and moble!=15586991887 and uid!={$order['uid']} and (deal between $small and $big) and (bank={$order['bank']} or wechat={$order['wechat']} or alipay={$order['alipay']})";
            $buy = $c2ctradeMo->lock()->where($where)->order('addtime asc,id asc')->fRow();

            $sale_price = $order['price']>=$buy['deal']?$buy['deal']:$order['price'];
            $fee = round($sale_price/$order['price']*$order['fee'],2);

            $time = time();

            if ($buy){
                $sell_user = $userMo->fRow($uid);
                $buy_user = $userMo->fRow($buy['uid']);
                $datalog = [
                    'buyid' => $buy['uid'],
                    'sellid' => $uid,
                    'coinname' => $order['coin'],
                    'price' =>$sale_price,
                    'buymoble' => $buy_user['mo'] ? $buy_user['mo'] : $buy_user['email'],
                    'buytradeno' => $buy['tradeno'],
                    'sellmoble' => $sell_user['mo'] ? $sell_user['mo'] : $sell_user['email'],
                    'selltradeno' => $order['tradeno'],
                    'addtime' => time(),
                    'bank' => $order['bank'],
                    'wechat' => $order['wechat'],
                    'alipay' => $order['alipay'],
                    'type' => 2,
                    'feesell' => $fee,
                ];

                # 事务开始
                $mo->begin();
                try{
                    //匹配处理用户金额
                    $sell_id = $mo->exec("update c2c_trade set deal=deal-{$sale_price},num=num+{$sale_price},fee_on=fee_on+{$fee},deal_id={$buy['uid']},deal_time=$time where id={$order['id']}");
                    $buy_id = $mo->exec("update c2c_trade set deal=deal-{$sale_price},num=num+{$sale_price},deal_id=$uid,deal_time=$time where id={$buy['id']}");
                    $in_id = $c2ctradelogMo->insert($datalog);

                    if($sell_id && $buy_id && $in_id){
                        $mo->commit();

                        $this->ajax('匹配成功!', 1);
                    }else{
                        $mo->back();
                    }
                }catch (Exception $e){
                    $mo->back();
                }
            }
        }
    }


    //已付款
    public function payAction()
    {
        $id = $_POST['id'];
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交");
        }
        $C2ctrade = new C2ctradeModel();
        //修改订单状态

        if(!$id) $this->ajax("参数错误");

        $trade = $C2ctrade->where(['id' => $id,'type'=>1])->fRow();
        if(!$trade) $this->ajax('该订单不存在');
        if ($trade['status'] == 1) $this->ajax('该订单已完成!');
        if ($trade['status'] == 3) $this->ajax('该订单已撤销');
        if ($trade['deal_id'] == 0) $this->ajax('该订单正在匹配中');

        $mo = Orm_Base::getInstance();
        $log = $mo->table('c2c_trade_log')->where(['buytradeno' => $trade['tradeno'], 'status' => 0])->fRow();
        $sell_user = UserModel::getInstance()->fRow($log['sellid']);

        # 事务开始
        $mo->begin();
        $up_id = $mo->table('c2c_trade')->where(['id' => $id])->update(['status' => 2]);

        $up_sell_id = $mo->table('c2c_trade')->where(['tradeno' => $log['selltradeno']])->update(['status' => 2]);


        //短信通知
        $username = 'xzgr';  //用户名
        $password_md5 = '48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
        $apikey = 'b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）

        $message = '【火网】尊敬的火网用户，您的卖单' .$log['selltradeno'] . '已成功匹配金额' . $log['price'] . '，请收到款后及时登入平台点击“确认收款”按钮完成交易；如有疑问，请联系官方客服。';
        $contentUrlEncode = urlencode($message);//执行URLencode编码  ，$content = urldecode($content);解码
        $sms = $this->sendSMS($username, $password_md5, $apikey, $sell_user['area'] . $sell_user['mo'], $contentUrlEncode, 'UTF-8');  //进行发送

        if ($up_id && $up_sell_id) {
            $mo->commit();
            $this->ajax('付款成功!',1,["repeat"=>$newRepeat]);
        } else {
            $mo->rollback();
            $this->ajax('系统繁忙，请稍後再试!', 0);
        }
    }



    //确认收款
    public function confirmAction()
    {
        $id = trim($_POST['id']);
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交");
        }
        $C2ctrade = new C2ctradeModel();
        $C2ctradelog = new C2ctradelogModel();

        $order = $C2ctrade->where(['id' =>$id,'type'=>2])->fRow();

        if(!$order) $this->ajax('该订单不存在');
        if ($order['deal_id']==0) $this->ajax('订单还未匹配!');
        if ($order['status'] == 1 || $order['status'] == 3) $this->ajax('订单已处理，不可以重复操作！');

        //查找交易中的订单
        $log = $C2ctradelog->where(['selltradeno' => $order['tradeno']])->order('id desc')->fRow();

        //设置token值
        $token = $_REQUEST['token'];
        $_SESSION['token'] = mt_rand(0,1000000000);
        if ($_SESSION["token"] == $token) $this->ajax('该订单已完成,不可重复提交！');

        $buy = $C2ctrade->where(['tradeno' => $log['buytradeno']])->order('id desc')->fRow();

        //买家查找from_UID
        $from_uid = UserModel::getInstance()->where("uid={$buy['uid']}")->fOne("from_uid");

        //判断是用户之间的交易
        $mo = Orm_Base::getInstance();
        $mo->begin();
        try {
            //更改订单状态
            if ($order['deal'] <= 0) {
                $up_sale_id = $mo->table('c2c_trade')->where(['tradeno' => $order['tradeno']])->update(['status' => 1]);
            } else {
                $up_sale_id = $mo->table('c2c_trade')->where(['tradeno' => $order['tradeno']])->update(['deal_time' => 0, 'deal_id' => 0, 'status' => 0]);
            }
            $up_buy_id = $mo->table('c2c_trade')->where(['tradeno' => $log['buytradeno']])->update(['status' => 1]);

            $price = floatval($log['price']);
            $lock_cnyx = $price+$log['feesell'];

            $buy_id = $mo->exec("update user set cnyx_over=cnyx_over+{$price} where uid={$buy['uid']}");
            $sell_id = $mo->exec("update user set cnyx_lock=cnyx_lock-{$lock_cnyx} where uid={$order['uid']}");

            //买家写入邀请手续费
            if($from_uid){
                $fee = Tool_Math::mul($order['price'],0.001);
                $fee_data = [
                    'uid' =>$from_uid,
                    'origin_uid'=>$buy['uid'],
                    'type'=>'c2c',
                    'number'=>$order['price'],
                    'coin'=>'cnyx',
                    'oid'=>$order['id'],
                    'fee'=>$fee,
                    'created'=>time()
                ];
                $mo->table = "fee_bonus_recharge";
                if(!$fee_id = $mo->insert($fee_data)){
                    $mo->back();
                    $this->ajax('卖出失败');
                }

                //更新邀请人账户余额
                $fee_buy_id = $mo->exec("update user set cnyx_over=cnyx_over+{$fee} where uid=$from_uid");
                if(!$fee_buy_id){
                    $mo->back();
                    $this->ajax('卖出失败');
                }
            }


            $od_id = $C2ctradelog->where(['selltradeno' => $order['tradeno'], 'status' => 0])->update(['status' => 1]);//更新交易记录状态
            if ($up_sale_id && $up_buy_id && $buy_id && $sell_id && $od_id) {
                $mo->commit();
                $this->ajax('卖出成功', 1, ["repeat"=>$newRepeat,$_SESSION['token']]);
            } else {
                $mo->back();
                $this->ajax('卖出失败');
            }

        } catch (Exception $e) {
            $mo->back();
            $this->ajax('卖出失败');
        }


    }

    //撤单
    public function revokeAction()
    {
        $id = trim($_POST['id']);
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交");
        }
        if(!$id) $this->ajax("该订单号不存在");

        $trade = C2ctradeModel::getInstance()->where(['id' => $id])->fRow();

        if ($trade['status'] == 3) $this->ajax('订单已撤销！');
       // if ($trade['deal_id'] != 0) $this->ajax('订单已匹配不可撤销！', 0);
        if ($trade['status'] == 1) $this->ajax('订单已完成！');

        //买单撤销
        if ($trade['type'] == 1) {
            try{
                //匹配记录
                $data = C2ctradelogModel::getInstance()->where(['buytradeno' => $trade['tradeno'],'status'=>0])->fRow();
                # 事务开始
                $mo = Orm_Base::getInstance();

                $mo->begin();
                if($trade['deal_id'] != 0){
                    $up_id = $mo->table('c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                    //恢复卖家记录
                    $no_id = $mo->exec("update c2c_trade set deal=deal+{$data['price']},num=num-{$data['price']},status=0,deal_id=0,fee_on=fee_on-{$data['feesell']} where tradeno='{$data['selltradeno']}'");
                    //记录取消为2
                    $log_id = $mo->table('c2c_trade_log')->where(['id' => $data['id']])->update(['status' => 2]);

                }else{
                    $un_id = $mo->table('c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                }

                if (($trade['deal_id'] && $up_id && $no_id && $log_id) || (!$trade['deal_id'] && $un_id)) {
                    $mo->commit();
                    $this->ajax('撤单成功！', 1,["repeat"=>$newRepeat]);
                } else {
                    $mo->rollback();
                    $this->ajax('撤单失败！', 0);
                }
            }catch (Exception $e){
                $mo->back();
                $this->ajax('撤单失败！', 0);
            }
        } else {
            //卖单撤销
            if ($trade['deal_id'] != 0) $this->ajax('订单已匹配成功，无法撤单');

            try{
                //匹配记录
                $data = C2ctradelogModel::getInstance()->where(['selltradeno' => $trade['tradeno']])->fRow();
                # 事务开始
                $mo = Orm_Base::getInstance();
                $mo->begin();
                $userModel = new UserModel();
                $cnyx = $userModel->where("uid={$trade['uid']}")->fRow();
                //匹配撤单处理买家卖家金额
                if($trade['deal_id']>0){
                    if (floatval($trade['deal']+$trade['num']+$trade['fee']) > floatval($cnyx['cnyx_lock'])) $this->ajax('冻结金额不足!');
                    //买
                    $no_id = $mo->exec("update c2c_trade set deal=deal+{$data['deal']},status=0,deal_id=0,deal_time=0 where tradeno={$data['buytradeno']}");
                    //卖
                    $sell_id = $mo->exec("update user set cnyx_over=cnyx_over+({$trade['deal']}+{$data['num']}+{$trade['fee']}),cnyx_lock=cnyx_lock-({$trade['deal']}+{$data['num']}+{$trade['fee']}) where uid={$data['sellid']}");

                    //记录取消为2
                    $log_id = $mo->table('c2c_log')->where(['id' => $data['id']])->update(['status' => 2]);
                }else{
                    if (floatval($trade['deal']) > floatval($cnyx['cnyx_lock'])) $this->ajax('冻结金额不足!');
                    //卖家

                    $lock_price = $trade['deal']+($trade['fee']-$trade['fee_on']);


                    if($trade['fee_on']==0){
                        $over_price = $trade['deal']+$trade['fee'];
                    }else{
                        $fee = $trade['fee_on']>=5?$trade['fee']-$trade['fee_on']:$trade['fee']-5;
                        $over_price = $trade['deal']+$fee;
                    }

                    $user_id = $mo->exec("update user set cnyx_over=cnyx_over+{$over_price},cnyx_lock=cnyx_lock-{$lock_price} where uid={$trade['uid']}");
                    $up_id = $mo->table('c2c_trade')->where(['id' => $id])->update(['status' => 3]);
                }
                if (($trade['deal_id']&&$no_id&&$log_id&&$sell_id) || ($user_id&&$up_id&&!$trade['deal_id'])) {
                    $mo->commit();
                    $this->ajax('撤单成功！', 1,["repeat"=>$newRepeat]);
                } else {
                    $mo->back();
                    $this->ajax('撤单失败！', 0);
                }
            }catch (Exception $e){
                $mo->back();
                $this->ajax('撤单失败！', 0);
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
            if ($trade['deal_id'] !=0) {
                //选择状态
                if ($trade['status'] == 0) {
                    $status = '交易中';
                } else if ($trade['status'] == 2) {
                    $status = '已支付';
                } else if ($trade['status'] == 3) {
                    $status = '已撤销';
                } else if ($trade['status']) {
                    $status = '已成交';
                }

                $autonymModel = new AutonymModel();
                $buylog = $C2ctradelog->where(['selltradeno'=>$trade['tradeno']])->order('id desc')->fRow();
                $selllog = $C2ctradelog->where(['buytradeno'=>$trade['tradeno']])->order('id desc')->fRow();
                if ($trade['type'] == 1) {
                    $selltrade = $C2ctrade->where(array('tradeno' => $selllog['selltradeno']))->fRow();
                    $sellbank = $Userbank->where(array('uid' => $selllog['sellid'], 'status' => 1, 'type' => $paytype))->fRow();
                    $truenamesell = $autonymModel->where(array('uid' => $selllog['sellid']))->fOne('name');
                }else{
                    $buytrade = $C2ctrade->where(array('tradeno' => $buylog['buytradeno']))->fRow();
                    $buybank = $Userbank->where(array('uid' => $buylog['buyid'], 'status' => 1, 'type' => $paytype))->fRow();
                    $truenamebuy = $autonymModel->where(array('uid' => $selllog['buyid']))->fOne('name');
                }
                // echo $paytype;die;
//                 var_dump($selllog['sellid']);
//                 var_dump($truenamesell);die;
                if ($trade['type'] == 1) {

                    echo json_encode([
                        'tradeId' => $selltrade['id'],
                        'sts' =>1,
                        'name' => $selllog['selltruename'],
                        'truename'=>$truenamesell,
                        'sellid' => $sellbank['uid'],
                        'bankaddr' => $sellbank['bank'],
                        'bankcard' => $sellbank['bankcard'],
                        'num' => $selllog['price'],
                        'tradeno' => $selllog['selltradeno'],
                        'type'=>$selltrade['type'],
                        'paytype' => $sellbank['type'],
                        'status' => $status,
                        'moble' => $selllog['sellmoble'],
                        'img' =>$sellbank['img'],
                        'deal_time' => $trade['deal_id'],
                    ]);
                    exit();
                } else {
                    echo json_encode([
                        'tradeId' => $buytrade['id'],
                        'sts' => 1,
                        'name' => $buylog['buytruename'],
                        'truename'=>$truenamebuy,
                        'sellid' => $buybank['uid'],
                        'bankaddr' => $buybank['bank'],
                        'bankcard' => $buybank['bankcard'],
                        'num' => $buylog['price'],
                        'tradeno' => $buylog['buytradeno'],
                        'type'=>$buytrade['type'],
                        'paytype' => $buybank['type'],
                        'status' => $status,
                        'moble' => $buylog['buymoble'],
                        'img' =>$buybank['img'],
                        'deal_time' => $trade['deal_id'],


                    ]);
                    exit();
                }

            }else{
                $this->ajax('非法操作',0);
            }

        }
    }


    //手动撮合买卖
    public function handtradeAction(){
        $C2ctrade = new C2ctradeModel();
        $UserModel = new UserModel();
        $Userbank = new UserbankModel();
        $C2ctradelog = new C2ctradelogModel();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交");
        }
        $price     = $_REQUEST['price'];
        $type    = $_REQUEST['type'];
        $id      = $_REQUEST['id'];
        if ($id){
            $this->_ajax_islogin();
            if ($price < 100) {
                $this->ajax('交易的金额最少100！');
            }

            if ($price % 100 != 0) {
                $this->ajax('交易价格必须是100的整数倍！');
            }
            if (!is_numeric($price) || strpos($price, ".") !== false) {
                $this->ajax('交易价格必须是100的正整数的倍数！');
            }

            //获取验证码
            for (; true;) {
                $tradeno = $this->tradeno('c2c');
                if (!$C2ctrade->where(array('tradeno' => $tradeno))->fRow()) {
                    break;
                }
            }
            $AutonymModel = new AutonymModel();
            $idcard = $AutonymModel->where("uid={$this->mCurUser['uid']} and idcard!=0")->fRow();
            if (!$idcard) {
                $this->ajax('请先认证！', 0);
            }

            if ($type == 1){
                $bank = $Userbank->where("uid={$this->mCurUser['uid']} and status=1")->fRow();
                if (!$bank) {
                    $this->ajax('请绑定付款方式，是否开启状态！', 0);
                }

                $sell = $C2ctrade->where("id =$id and deal_id=0 and status=0")->fRow();
                if (!$sell){
                    $this->ajax('订单已匹配，请选择其他订单交易');

                }
                $sell_paypassword = $_POST['val'];
                $userpassword = $UserModel->where("uid={$this->mCurUser['uid']}")->fRow();
                $userInfo = $UserModel->field('email,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);
                $pwdtrade = Tool_Md5::encodePwdTrade($sell_paypassword,$userInfo['prand']);

                if ($pwdtrade!= $userpassword['pwdtrade']) {
                    $this->ajax('交易密码错误');
                }
                $user=$UserModel->where(['uid' =>$this->mCurUser['uid']])->fRow();
                $sell_user = $UserModel->where(['uid' => $sell['uid']])->fRow();
                $buybank = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='1'")->fRow();
                $buywechat = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='2'")->fRow();
                $buyalipay = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='3'")->fRow();
                $min_num = $sell['min'];
                $sy_num = $sell['deal'];

                if ($sy_num - $min_num >= 0){
                    if ($price - $min_num < 0){
                        $this->ajax('交易数量不得低于最小匹配数量');

                    }
                    if ($price > $sy_num){
                        $this->ajax('交易数量大于卖方的剩余可交易数量，无法交易');

                    }
                }else{
                    if ($price > $sy_num ){
                        $this->ajax('交易数量大于卖方的剩余可交易数量，无法交易');
                    }
                }

                //手续费计算
                $year = date("Y");
                $month = date("m");
                $day = date("d");
                $addti = mktime(0, 0, 0, $month, $day, $year);//当天开始时间戳
                $endti = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
                $countsellall = $C2ctrade->where("uid ={$sell['uid']}  and type=2 and status=1 and (addtime between $addti and $endti)")->count();
                if ($sell['selltype'] == 1){
                    if ($countsellall > 2){
                        $bili = ($countsellall-1)*0.005;
                        $fee_sell = $price * $bili;
                    }else{
                        $fee_sell = $price * 0.005;
                    }
                }else{
                    if ($countsellall > 2){
                        $bili = ($countsellall-1)*0.01;
                        $fee_sell = $price * $bili;
                    }else{
                        $fee_sell = $price * 0.01;
                    }
                }
                if (!$sell){
                    $this->ajax('订单已匹配，请选择其他订单交易');
                }

                $mo = Orm_Base::getInstance();
                # 事务开始
                $mo->begin();
                $num = $sell['deal'] - $price;

                //匹配处理用户金额
                $mo->table('c2c_trade')->where(['id'=>$id,'uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['deal' => $num,'num'=>$price]);
                $mo->table('c2c_trade')->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['fee' => $fee_sell, 'deal_id' => $this->mCurUser['uid'], 'deal_time' => time()]);

                $mo->exec("update user set cnyx_over=cnyx_over-{$fee_sell} where uid={$sell['uid']}");
                $mo->exec("update user set cnyx_lock=cnyx_lock+{$fee_sell} where uid={$sell['uid']}");
                $C2ctrade->insert([
                    'uid' =>$this->mCurUser['uid'],
                    'coin' => 'cnyx',
                    'price' => $price,
                    'num' => $price,
                    'type' => 1,
                    'moble'=>$user['mo']?$user['mo']:$user['email'],
                    'addtime' => time(),
                    'deal_id' =>$sell['uid'],
                    'deal_time' =>time(),
                    'bank'=>$buybank['type'],
                    'wechat'=>$buywechat['type'],
                    'alipay'=>$buyalipay['type'],
                    'tradeno' => $tradeno,
                    'status' => 0,
                ]);

                $C2ctradelog->insert([
                    'buyid' => $this->mCurUser['uid'],
                    'sellid' => $sell['uid'],
                    'coinname' => 'cnyx',
                    'price' => $price,
                    'buymoble' => $user['mo']?$user['mo']:$user['email'],
                    'buytradeno' => $tradeno,
                    'sellmoble' => $sell['moble'],
                    'selltradeno' => $sell['tradeno'],
                    'addtime' => time(),
                    'type' => 1,
                    'bank'=>$buybank['type']?$buybank['type']:0,
                    'wechat'=>$buywechat['type']?$buywechat['type']:0,
                    'alipay'=>$buyalipay['type']?$buyalipay['type']:0,
                    'status' => 0,
                    'feesell'  => $fee_sell,
                ]);
                if ($mo){
                    $mo->commit();
                    $username = 'xzgr';  //用户名
                    $password_md5 = '48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
                    $apikey = 'b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
                    $message1 = '【火网】尊敬的火网用户，您的卖单' . $sell['tradeno'] . ',成功匹配金额'.$price.'，请收到款后及时登入平台点击“确认收款”按钮完成交易；如有疑问，请联系官方客服。';
                    $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                    $this->sendSMS($username, $password_md5, $apikey, $sell_user['area'] . $sell_user['mo'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $this->ajax('匹配成功',1,["repeat"=>$newRepeat]);
                }else{
                    $mo->back();
                    $this->ajax('下单失败！');
                }

            }elseif ($type == 2){
                $bank = $Userbank->where("uid={$this->mCurUser['uid']} and status=1")->fRow();
                if (!$bank) {
                    $this->ajax('请绑定付款方式，是否开启状态！', 0);
                }
                $buy = $C2ctrade->where("id =$id and deal_id=0 and status=0")->fRow();
                if (!$buy){
                    $this->ajax('订单已匹配，请选择其他订单交易');

                }
                $mo = Orm_Base::getInstance();
                # 事务开始
                $mo->begin();
                $buy_user =$UserModel->where(['uid' => $buy['uid']])->fRow();
                $sell_user =$UserModel->where(['uid' =>$this->mCurUser['uid']])->fRow();
                $sellbank = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='1'")->fRow();
                $sellwechat = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='2'")->fRow();
                $sellalipay = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='3'")->fRow();
                if ($price < $buy['price']){
                    $this->ajax('交易数量不可以小于买方挂单数量');
                    $mo->back();
                }
                if ($price > $buy['price']){
                    $this->ajax('交易超过买方挂单数量');
                    $mo->back();
                }
                $userMo   = UserModel::getInstance();
                $sell_paypassword = $_REQUEST['sellpwds'];
                $userpassword = $userMo->where("uid={$this->mCurUser['uid']}")->fRow();
                $userInfo = $userMo->field('email,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);
                $pwdtrade = Tool_Md5::encodePwdTrade($sell_paypassword,$userInfo['prand']);

                if ($pwdtrade!= $userpassword['pwdtrade']) {
                    $this->ajax('交易密码错误');
                }
                //手续费计算
                $year = date("Y");
                $month = date("m");
                $day = date("d");
                $addti = mktime(0, 0, 0, $month, $day, $year);//当天开始时间戳
                $endti = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
                $countsellall = $C2ctrade->where("uid ={$this->mCurUser['uid']}  and type=2 and status=1 and (addtime between $addti and $endti)")->count();

                if ($countsellall > 2){
                    $bili = ($countsellall-1)*0.005;
                    $fee_sell = $price * $bili < 5 ? 5 : $price *$bili;
                }else{
                    $fee_sell = $price * 0.005 < 5 ? 5 : $price *0.005;
                }
                if (!$buy){
                    $mo->back();
                    $this->ajax('订单已匹配，请选择其他订单交易');
                }
                $mo->exec("update user set cnyx_over=cnyx_over-{$price} where uid={$this->mCurUser['uid']}");
                $mo->exec("update user set cnyx_lock=cnyx_lock+{$price} where uid={$this->mCurUser['uid']}");
                $mo->exec("update user set cnyx_over=cnyx_over-{$fee_sell} where uid={$this->mCurUser['uid']}");
                $mo->exec("update user set cnyx_lock=cnyx_lock+{$fee_sell} where uid={$this->mCurUser['uid']}");
                $mo->table('c2c_trade')->where(['id' => $id])->update(['deal_id' =>$this->mCurUser['uid'],'deal_time' => time(),'price'=>$price,'num'=>$price,'deal'=>0]);
                $C2ctrade->insert([
                    'uid' =>$this->mCurUser['uid'],
                    'coin' => 'cnyx',
                    'price' => $price,
                    'num' => $price,
                    'type' => 2,
                    'moble'=>$sell_user['mo']?$sell_user['mo']:$sell_user['email'],
                    'addtime' => time(),
                    'deal_time' =>time(),
                    'deal_id'  =>  $buy['uid'],
                    'tradeno' => $tradeno,
                    'fee'  =>  $fee_sell,
                    'bank'=>$sellbank['type']?$sellbank['type']:0,
                    'wechat'=>$sellwechat['type']?$sellwechat['type']:0,
                    'alipay'=>$sellalipay['type']?$sellalipay['type']:0,
                    'status' => 0,
                ]);
                $C2ctradelog->insert([
                    'buyid' => $buy['uid'],
                    'sellid' => $this->mCurUser['uid'],
                    'coinname' => 'cnyx',
                    'price' => $price,
                    'buymoble' => $buy['moble'],
                    'buytradeno' => $buy['tradeno'],
                    'sellmoble' => $sell_user['mo']?$sell_user['mo']:$sell_user['email'],
                    'selltradeno' => $tradeno,
                    'addtime' => time(),
                    'type' => 2,
                    'feesell'  =>$fee_sell,
                    'bank'=>$sellbank['type']?$sellbank['type']:0,
                    'wechat'=>$sellwechat['type']?$sellwechat['type']:0,
                    'alipay'=>$sellalipay['type']?$sellalipay['type']:0,
                    'status' => 0,

                ]);
                if ($mo){
                    $mo->commit();
                    $username = 'xzgr';  //用户名
                    $password_md5 = '48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
                    $apikey = 'b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
                    $message1 = '【火网】尊敬的火网用户，卖家订单号'.$tradeno.',成功匹配金额'.$price.'，请及时向卖方打款，打款后请点击“我已付款”按钮；如有疑问，请联系官方客服。';
                    $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                    $this->sendSMS($username, $password_md5, $apikey, $buy_user['area'] . $buy_user['mo'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $this->ajax('匹配成功！',1,["repeat"=>$newRepeat]);
                }else{
                    $mo->back();
                    $this->ajax('下单失败！');
                }
            }else{
                $this->ajax('交易类型不存在');
            }
        }

    }
    //内部吃单
    public function unregisterAction()
    {
        $C2ctrade = new C2ctradeModel();
        $UserModel = new UserModel();
        $C2ctradelog = new C2ctradelogModel();
        $Userbank = new UserbankModel();
        $price     = $_REQUEST['numwer'];
        $type    = $_REQUEST['type'];
        $paypwd  = $_REQUEST['sellpwds'];
        $id      = $_REQUEST['id'];

        if ($id){
            $this->_ajax_islogin();
            if ($price < 100) {
                $this->ajax('交易的金额最少100！',0,'price');
            }

            if ($price % 100 != 0) {
                $this->ajax('交易价格必须是100的整数倍！',0,'price');
            }
            if (!is_numeric($price) || strpos($price, ".") !== false) {
                $this->ajax('交易价格必须是100的正整数的倍数！',0,'price');
            }

            $userpassword = $UserModel->where("uid={$this->mCurUser['uid']}")->fRow();
            $userMo   = UserModel::getInstance();
            $userInfo = $userMo->field('email,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);
            $pwdtrade = Tool_Md5::encodePwdTrade($paypwd,$userInfo['prand']);

            if ($pwdtrade != $userpassword['pwdtrade']) {
                $this->ajax('交易密码错误',0,'psw');
            }

            //获取订单号
            for (; true;) {
                $tradeno = $this->tradeno('c2c');
                if (!$C2ctrade->where(array('tradeno' => $tradeno))->fRow()) {
                    break;
                }
            }
            //获取验证码
            for (; true;) {
                $tradeno1 = $this->tradeno('c2c');
                if (!$C2ctrade->where(array('tradeno' => $tradeno))->fRow()) {
                    break;
                }
            }
            if ($type == 1){
                $mo = Orm_Base::getInstance();
                # 事务开始
                $mo->begin();
                $sell =$C2ctrade->where(['id' => $id,'deal_id' => 0,'status'=>0,'moble'=>15586991887])->fRow();
                $sell_user = $UserModel->where(['uid' => $sell['uid']])->fRow();
                $buy_user = $UserModel->where(['uid' => $this->mCurUser['uid']])->fRow();
                $buybank = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='1'")->fRow();
                $buywechat = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='2'")->fRow();
                $buyalipay = $Userbank->where("uid = {$this->mCurUser['uid']} and status=1 and type='3'")->fRow();
//                $bank = $Userbank->where("uid={$this->mCurUser['uid']} and status=1")->fRow();
//                if(!$bank){
//                    $this->ajax('请绑定支付方式',0,'price');
//                }
                $min_num = $sell['min'];
                $fee_sell=0;
                if ($price - $min_num < 0){
                    $this->ajax('交易数量不得低于最小匹配数量'.$min_num,0,'price');
                    $mo->back();
                }
                if ($price > $sell['deal']){
                    $this->ajax('商家余额不足',0,'price');
                    $mo->back();
                }
                // 用户未付款点击已付款，只允许每个用户存在两笔这样的订单
                $two = count($C2ctrade->where("uid={$this->mCurUser['uid']} and status!=1 and status!=3 and type=1")->fList());
                if ($two - 2 >= 0) {
                    $this->ajax('您还有未完成交易的订单', 0);
                }
                $mo->exec("update user set cnyx_over=cnyx_over-{$price} where uid={$sell['uid']}");
                $mo->exec("update user set cnyx_lock=cnyx_lock+{$price} where uid={$sell['uid']}");
                $mo->exec("update user set cnyx_over=cnyx_over-{$fee_sell} where uid={$sell['uid']}");
                $mo->exec("update user set cnyx_lock=cnyx_lock+{$fee_sell} where uid={$sell['uid']}");
                $C2ctrade->insert([
                    'uid' => $sell['uid'],
                    'coin' => 'cnyx',
                    'price' => $price,
                    'num' => $price,
                    'moble'=>$sell['moble'],
                    'type' => 2,
                    'addtime' => time(),
                    'deal_time' =>time(),
                    'deal_id' =>$this->mCurUser['uid'],
                    'tradeno' => $tradeno1,
                    'fee'  =>$fee_sell,
                    'bank'=>$sell['bank']?$sell['bank']:0,
                    'wechat'=>$sell['wechat']?$sell['wechat']:0,
                    'alipay'=>$sell['alipay']?$sell['alipay']:0,
                    'status' => 0,
                ]);
                $C2ctrade->insert([
                    'uid' =>$this->mCurUser['uid'],
                    'coin' => 'cnyx',
                    'price' => $price,
                    'num' => $price,
                    'type' => 1,
                    'addtime' => time(),
                    'deal_time' =>time(),
                    'deal_id'    => $sell['uid'],
                    'tradeno' => $tradeno,
                    'bank'=>$buybank['type']?$buybank['type']:0,
                    'wechat'=>$buywechat['type']?$buywechat['type']:0,
                    'alipay'=>$buyalipay['type']?$buyalipay['type']:0,
                    'status' => 0,
                ]);
                $C2ctradelog->insert([
                    'buyid' =>$this->mCurUser['uid'],
                    'sellid' => $sell['uid'],
                    'coinname' => 'cnyx',
                    'price' => $price,
                    'buymoble' => $buy_user['mo']?$buy_user['mo']:$buy_user['email'],
                    'buytradeno' => $tradeno,
                    'sellmoble' => $sell['moble'],
                    'selltradeno' => $tradeno1,
                    'addtime' => time(),
                    'type' => 1,
                    'status' => 0,
                    'bank'=>$buybank['type']?$buybank['type']:0,
                    'wechat'=>$buywechat['type']?$buywechat['type']:0,
                    'alipay'=>$buyalipay['type']?$buyalipay['type']:0,
                    'feesell'  =>$fee_sell,

                ]);
                if ($mo->commit()){
                    $message1 = '【火网】尊敬的火网用户，您的卖单' . $sell['tradeno'] . ',成功匹配金额'.$price.'，请收到款后及时登入平台点击“确认收款”按钮完成交易；如有疑问，请联系官方客服。';
                    $username = 'xzgr';  //用户名
                    $password_md5 = '48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
                    $apikey = 'b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
                    $contentUrlEncode = urlencode($message1);//执行URLencode编码  ，$content = urldecode($content);解码
                    $this->sendSMS($username, $password_md5, $apikey, $sell_user['area'] . $sell_user['mo'], $contentUrlEncode, 'UTF-8');  //进行发送
                    $this->ajax('匹配成功！',1);
                }else{
                    $mo->back();
                    $this->ajax('下单失败！');
                }

            }else{
                $this->ajax('交易类型不存在');
            }
        }
    }
    //获取流水号
    function tradeno($type = '')
    {
        if ($type == 'c2c') {
            $length = 5;
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';//abcdefghijklmnopqrstuvwxyz
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
    //发送接口
    public function sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode)
    {
        //发送链接（用户名，密码，apikey，手机号，内容）
        $url = "http://m.5c.com.cn/api/send/index.php?";  //如连接超时，可能是您服务器不支持域名解析，请将下面连接中的：【m.5c.com.cn】修改为IP：【115.28.23.78】
        $data=array
        (
            'username'=>$username,
            'password_md5'=>$password_md5,
            'apikey'=>$apikey,
            'mobile'=>$mobile,
            'content'=>$contentUrlEncode,
            'encode'=>$encode,
        );
        $result = $this->curlSMS($url,$data);

        return $result;
    }

    private function curlSMS($url,$post_fields=array())
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);//用PHP取回的URL地址（值将被作为字符串）
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//使用curl_setopt获取页面内容或提交数据，有时候希望返回的内容作为变量存储，而不是直接输出，这时候希望返回的内容作为变量
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
        curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
        curl_setopt($ch,CURLOPT_POST,1);//设置这个选项为一个零非值，这个post是普通的application/x-www-from-urlencoded类型，多数被HTTP表调用。
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
        $data = curl_exec($ch);//抓取URL并把他传递给浏览器
        curl_close($ch);//释放资源

        $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
        return $res[2]; //然后在这里返回数组。
    }
}





////判断卖家资产
//$coin = $UserModel->where(['uid' => $sell['uid']])->fRow();
////手续费计算
//$year = date("Y");
//$month = date("m");
//$day = date("d");
//$addti = mktime(0, 0, 0, $month, $day, $year);//当天开始时间戳
//$endti = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
//$countsellall = $C2ctrade->where("uid ={$sell['uid']}  and type=2 and status!=3 and (addtime between $addti and $endti)")->count();
//
////手续费计算
//$num = $sell['deal'] > $trade['deal'] ? $trade['deal'] : $sell['deal'];
//if ($sell['selltype'] == 1){
//    if($num<=1000 && $countsellall<=2){
//        $fee_sell = 5;
//    }elseif($num>1000 && $countsellall<=2){
//        $bili = $num *0.005;
//        $fee_sell = $bili;
//    }elseif($num <=  500  && $countsellall>=3){
//        $fee_sell = 5;
//    }elseif($num >500  && $countsellall>=3){
//        $bili = ($countsellall-1)*0.005;
//        $fee_sell = $num * $bili;
//    }elseif($num <=300  && $countsellall>=4){
//        $fee_sell = 5;
//    } elseif($num >300  && $countsellall>=4) {
//        $bili = ($countsellall - 1) * 0.005;
//        $fee_sell = $num * $bili;
//    }
//}else{
//    if($num<=500 && $countsellall<=2){
//        $fee_sell = 5;
//    }elseif($num>500 && $countsellall<=2){
//        $bili = $num * 0.01;
//        $fee_sell = $bili;
//    }elseif($num <=  200  && $countsellall>=3){
//        $fee_sell = 5;
//    }elseif($num >200  && $countsellall>=3){
//        $bili = ($countsellall-1)*0.01;
//        $fee_sell = $num * $bili;
//    }elseif($num <=100  && $countsellall>=4){
//        $fee_sell = 5;
//    } elseif($num >100  && $countsellall>=4) {
//        $bili = ($countsellall - 1) *0.01;
//        $fee_sell = $num * $bili;
//    }
//
//}
