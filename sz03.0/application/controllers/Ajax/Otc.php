<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/25
 * Time: 14:44
 */
class Ajax_OtcController extends Ajax_BaseController{

    protected $_auth = 0;
    public $pageSize = 10;

    //查看余额
    public function see_balanceAction(){
        $this->_ajax_islogin();

        $user_balance = UserModel::getInstance()->field("cnyx_over,cnyx_lock")->fRow($this->mCurUser['uid']);
        $this->ajax('',1,$user_balance);
    }

    //待交易币种
    public function coinsAction(){
        $coins = CoinModel::getInstance()->where(['otc_open'=>1])->field('name,type,display')->fList();
        $this->ajax('',1,$coins);
    }

    //委托列表
    public function trustsAction(){

        $coin = coll('post','coin');
        $type = coll('post','type');
        $page = coll('get','page')?:1;

        $mo = Orm_Base::getInstance();
        $data['total'] = $mo->table("otc_trust_{$coin}")->where("status=0 and type={$type} and numberdeal>0")->fOne("count(id)");

        $data['pages'] = ceil($data['total']/$this->pageSize);
        $order = $type==1?'desc':'asc';
        $data['list'] = $mo->table("otc_trust_{$coin}")->where("status=0 and type={$type} and numberdeal>0")->page($page,$this->pageSize)->order("price {$order}")->fList();
        foreach ($data['list'] as &$v){
            $v['total_price'] = Tool_Math::mul($v['nuumberdeal'],$v['price']);
            $v['mo'] = substr_replace($v['mo'],'****',2);
        }
        $this->ajax('',1,$data);
    }

    //订单列表
    public function ordersAction(){

        $this->_ajax_islogin();

        $coin = coll('post','coin');
        $uid = $this->mCurUser['uid'];
        $page = coll('post','page')?:1;

        $mo = Orm_Base::getInstance();

        $trusts = $mo->table("otc_trust_{$coin}")->field("*")->where("uid=$uid")->order("status asc,id desc")->fList();
        $orders_t = $mo->table("otc_order_{$coin}")->field("*")->where("(buy_uid=$uid and type='sale') or (sale_uid=$uid and type='buy')")->order("id desc")->fList();
        $orders_u = $mo->table("otc_order_{$coin}")->field("*")->where("(buy_uid=$uid and type='buy') or (sale_uid=$uid and type='sale')")->order("id desc")->fList();

        $trusts_on = [];$trusts_un=[];
        foreach ($trusts as $k=>$v){
            if($v['status']==0){
                $v['name'] = '未完成委托';
                $v['color'] = '黑色';
                $trusts_on[] = $v;
            }else{
                $v['name'] = '已完成委托';
                $v['color'] = '灰色';
                $trusts_un[] = $v;
            }
        }

        $orders_on = [];$orders_un=[];
        foreach ($orders_u as $k=>$v){
            $v['type'] = $v['buy_uid']==$uid?'buy':'sale';
            if($v['sale_uid'] == $this->mCurUser['uid']) {
                $v['walletList'] = $this->getTrustWallet($v['buy_uid'], $coin);
            }else{
                $v['walletList'] = $this->getTrustWallet($v['sale_uid'], $coin);
            }
            if($trusts['status']==0 || $trusts['status']==1){
                $v['name'] = '未完成订单';
                $v['color'] = '绿色';
                if($v['status']==0 && $v['sale_uid']==$uid) $v['operate'] = '确认转币';
                if($v['status']==1 && $v['buy_uid']==$uid) $v['operate'] = '确认收币';
                $orders_on[] = $v;
            }else{
                $v['name'] = '已完成订单';
                $v['color'] = '暗绿色';
                $orders_un[] = $v;
            }
        }

        $trust_ord_on = [];
        foreach ($trusts_on as $k=>$v){
            $v['name'] = '未完成委托';
            $v['color'] = '黑色';
            $trust_ord_on[]  = $v;
            foreach ($orders_t as $tv){
                $tv['walletList'] = $this->getOrderWallet($tv['trust_id'],$coin);
                if($tv['trust_id']==$v['id']){
                    if($tv['sale_uid'] == $this->mCurUser['uid']) {
                        $tv['walletList'] = $this->getTrustWallet($tv['buy_uid'], $coin);
                    }else{
                        $tv['walletList'] = $this->getTrustWallet($tv['sale_uid'], $coin);
                    }
                    $tv['type'] = $tv['buy_uid']==$uid?'buy':'sale';
                    $tv['name'] = '未完成子订单';
                    $tv['color'] = $tv['status']<2?'绿色':'暗绿色';
                    if($tv['status']==0 && $tv['sale_uid']==$uid) $tv['operate'] = '确认转币';
                    if($tv['status']==1 && $tv['buy_uid']==$uid) $tv['operate'] = '确认收币';
                    $trust_ord_on[] = $tv;
                }
            }
        }

        $trust_ord_un = [];
        foreach ($trusts_un as $k=>$v){
            $v['name'] = '已完成委托';
            $v['color'] = '灰色';
            $trust_ord_un[]  = $v;
            foreach ($orders_t as $tv){
                $tv['type'] = $tv['buy_uid']==$uid?'buy':'sale';
                $tv['name'] = '已完成子订单';
                $tv['color'] = '暗绿色';
                if($tv['sale_uid'] == $this->mCurUser['uid']) {
                    $tv['walletList'] = $this->getTrustWallet($tv['buy_uid'], $coin);
                }else{
                    $tv['walletList'] = $this->getTrustWallet($tv['sale_uid'], $coin);
                }
                if($tv['trust_id']==$v['id']) $trust_ord_un[] = $tv;
            }
        }

        $lists = array_merge($orders_on,$trust_ord_on,$trust_ord_un,$orders_un);
        $data['list'] = array_slice($lists,($page-1)*$this->pageSize,$this->pageSize);

        $data['total'] = count($lists);
        //总页数
        $data['pages'] = ceil($data['total']/$this->pageSize);

        $this->ajax('',1,$data);
    }

    /**
     * 获取委托单钱包地址
     * @param int $trust_uid
     * @param string $coin
     * @return mixed
     */
    public function getTrustWallet($trust_uid = 0,$coin = ""){
        $mo = Orm_Base::getInstance();
        $walletList = $mo->table("otc_address")->field("id,coin,type")->where(['uid' => $trust_uid,"coin" => $coin,'status'=>1])->fList();
        return $walletList;
    }

    /**
     * 获取成交钱包地址
     * @param int $trust_id
     * @param string $coin
     * @return mixed
     */
    public function getOrderWallet($trust_id = 0,$coin = ""){
        $mo = Orm_Base::getInstance();
        $trustInfo = $mo->table("otc_trust_".$coin)->where(['id' => $trust_id])->fRow();
        $walletList = $this->getTrustWallet($trustInfo['uid'],$coin);
        return $walletList;
    }

    public function getWalletInfoAction(){
        $wallet_id = coll("post","wallet_id");
        $coin = coll("post","coin");
        $mo = Orm_Base::getInstance();
        $walletInfo = $mo->table("otc_address")->where(['id'=>$wallet_id,'coin'=>$coin])->fRow();

        $see_user = UserModel::getInstance()->field("uid,area,mo,email")->fRow($walletInfo['uid']);
        $name = AutonymModel::getInstance()->where(['uid' => $walletInfo['uid']])->fOne('name');

        $walletInfo['area'] = $see_user['area'];
        //BOSS手机号码对外展示 更改
        $walletInfo['mo'] = $see_user['uid']==2?'15651029170':$see_user['mo'];
        $walletInfo['email'] = $see_user['email'];

        if($name){
            $strlen     = mb_strlen($name, 'utf-8');
            $firstStr     = mb_substr($name, 0, 1, 'utf-8');
            if($strlen==2){
                $walletInfo['name'] = $firstStr.str_repeat('*', mb_strlen($name, 'utf-8') - 1);
            }else{
                $lastStr     = mb_substr($name, -1, 1, 'utf-8');
                $walletInfo['name'] = $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
            }
        }else{
            $walletInfo['name'] = '';
        }

        $this->ajax("",1,$walletInfo);
    }

    //发布订单
    public function issue_trustAction(){

        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $uid = $this->mCurUser['uid'];
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交",0,["repeat"=>$newRepeat]);
        }
        $type = coll('post','type');
        $coin = coll('post','coin');
        $number = coll('post','number');
        $price = coll('post','price');
        $pwdTrade = coll('post','pwdTrade');
        $minNum = coll('post','minNum');
        if(!$coin || !$number || !$price || !$pwdTrade) $this->ajax('参数错误',0,["repeat"=>$newRepeat]);

        $mo = Orm_Base::getInstance();
        $total_trust = $mo->table("otc_trust_{$coin}")->where(['uid'=>$uid,'status'=>0,'type'=>$type])->fOne("count(id)");
        $total_lock_trust = $mo->table("otc_trust_{$coin}")->where("uid=$uid and status=0 and type=$type and lock_cnyx>0")->fOne("count(id)");
        if($total_trust>=2) $this->ajax("您还有2笔相同类型的订单未完成",0,["repeat"=>$newRepeat]);


        if(!$idcard = $mo->table("autonym")->where("uid=$uid and status=2")->fOne("idcard")) $this->ajax("您还未通过实名认证，暂无法发起委托",0,["repeat"=>$newRepeat]);

        $coinS = strtoupper($coin);
        $otc_address = $mo->table("otc_address")->where(['coin'=>$coin,'uid'=>$uid,'status'=>1])->fOne("id");
        if(!$otc_address) $this->ajax("您还未绑定{$coinS}钱包地址，暂无法发起委托",0,["repeat"=>$newRepeat]);

        $userMo = UserModel::getInstance();
        $coinConf = CoinModel::getInstance()->where(['name'=>$coin])->field('otc_open,otc_buy_feerate,otc_sale_feerate')->fRow();
        if(!$coinConf['otc_open']) $this->ajax("该交易已关闭",0,["repeat"=>$newRepeat]);

        $userInfo = $userMo->fRow($this->mCurUser['uid']);
        if($type==2 && $userInfo['cnyx_over']<1000 && $total_trust==0) $this->ajax("您的资金余额不足1000，无法发起卖单",0,["repeat"=>$newRepeat]);


        //验证交易密码
        if ($userInfo['pwdtrade'] != $pwdTrade = Tool_Md5::encodePwdTrade($pwdTrade,$userInfo['prand'])) $this->ajax('交易密码错误',0,["repeat"=>$newRepeat]);

        $total_price = Tool_Math::mul($number,$price);
        //买入手续费
        $buy_fee = Tool_Out::fee_format($coinConf['otc_buy_feerate'],$total_price);
        $total = Tool_Math::add($total_price,$buy_fee);

        //卖出手续费
        $sale_fee = Tool_Out::fee_format($coinConf['otc_sale_feerate'],$total_price);

        if($type == 1 && $userInfo['cnyx_over']<$total) $this->ajax("您的余额不足",0,["repeat"=>$newRepeat]);

        $data = [
            'uid' => $uid,
            'mo' => $userInfo['mo'],
            'email' => $userInfo['email'],
            'type' => $type,
            'number' => $number,
            'numberdeal' =>$number,
            'min_number' =>$minNum,
            'price' => $price,
            'lock_cnyx'=>$type==2 && $total_trust==0?1000:0,
            'buy_fee' => $buy_fee,
            'sale_fee' => $sale_fee,
            'created' => time(),
            'created_date' => date("Y-m-d H:i:s")
        ];

        //事务开始
        $mo->begin();
        if($type==1){
            $up_id = $userMo->exec("update user set cnyx_over=cnyx_over-{$total},cnyx_lock=cnyx_lock+{$total} where uid={$uid}");
            if(!$up_id){
                $mo->back();
                $this->ajax("添加失败",0,["repeat"=>$newRepeat]);
            }
        }
        $mo->table = "otc_trust_$coin";
        $in_id = $mo->insert($data);
        if(!$in_id){
            $mo->back();
            $this->ajax("添加失败",0,["repeat"=>$newRepeat]);
        }

        if($type==2 && $total_lock_trust==0){
            $uup_id = $userMo->exec("update user set cnyx_over=cnyx_over-1000,cnyx_lock=cnyx_lock+1000 where uid={$uid}");
            if(!$uup_id){
                $mo->back();
                $this->ajax("添加失败",0,["repeat"=>$newRepeat]);
            }
        }
        $mo->commit();
        $this->ajax("添加成功",1,["repeat"=>$newRepeat,'data'=>$data]);
    }

    //交易
    public function dealAction(){

        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交",0,["repeat"=>$newRepeat]);
        }
        $uid = $this->mCurUser['uid'];

        $type = coll('post','type');
        $coin = coll('post','coin');
        $number = coll('post','number');
        $tid = coll('post','tid');
        $pwdTrade = coll('post','pwdTrade');

        $mo = Orm_Base::getInstance();
        if(!$idcard = $mo->table("autonym")->where("uid=$uid and status=2")->fOne("idcard")) $this->ajax("您还未通过实名认证，暂无法发起交易",0,["repeat"=>$newRepeat]);

        $coinS = strtoupper($coin);
        $otc_address = $mo->table("otc_address")->where(['coin'=>$coin,'uid'=>$uid,'status'=>1])->fOne("id");
        if(!$otc_address) $this->ajax("您还未绑定{$coinS}钱包地址，暂无法发起交易",0,["repeat"=>$newRepeat]);
        $mo->table = "otc_trust_$coin";
        $trust = $mo->where("id=$tid")->fRow();
        if(!$trust) $this->ajax("未查询到该委托",0,["repeat"=>$newRepeat]);
        if($trust['uid']==$uid) $this->ajax("不可以交易自己的订单",0,["repeat"=>$newRepeat]);
        if($trust['numberdeal']<$number) $this->ajax("该委托剩余数量不足",0,["repeat"=>$newRepeat]);
        if($trust['min_number']>$number && $trust['numberdeal']>$number) $this->ajax("最小交易数量为{$trust['min_number']}个",0,["repeat"=>$newRepeat]);
        if(($type==1 && $trust['type']!=2) || ($type==2 && $trust['type']!=1)) $this->ajax("交易类型不匹配",0,["repeat"=>$newRepeat]);

        $userInfo = UserModel::getInstance()->fRow($uid);

        $coinConf = CoinModel::getInstance()->where(['name'=>$coin])->field('otc_open,otc_buy_feerate,otc_sale_feerate')->fRow();
        if(!$coinConf['otc_open']) $this->ajax("该交易已关闭",0,["repeat"=>$newRepeat]);

        if ($userInfo['pwdtrade'] != $pwdTrade = Tool_Md5::encodePwdTrade($pwdTrade,$userInfo['prand'])) $this->ajax('交易密码错误',0,["repeat"=>$newRepeat]);

        //总金额
        $total_price = Tool_Math::mul($number,$trust['price']);

        //买入手续费
        $buy_fee = Tool_Math::format(($number/$trust['number'])*$trust['buy_fee'],2);

        //卖出手续费
        $sale_fee = Tool_Math::format($number/$trust['number']*$trust['sale_fee'],2);

        $buy_total = Tool_Math::add($total_price,$buy_fee);

        //如果是买入判断用户账户余额是否足够
        if($type==1 && $userInfo['cnyx_over']<$buy_total) $this->ajax("您的余额不足",0,["repeat"=>$newRepeat]);

        //生生订单号
        $order_number = self::get_order_number($coin);

        //事务开启
        $mo->begin();
        $up_id = $mo->exec("update otc_trust_{$coin} set numberover=numberover+{$number},numberdeal=numberdeal-{$number},buy_fee_on=buy_fee_on+{$buy_fee},sale_fee_on=sale_fee_on+{$sale_fee} where id={$tid}");
        //如果是买入冻结买家资金
        if($type==1) $lock_id = $mo->exec("update user set cnyx_over=cnyx_over-{$buy_total},cnyx_lock=cnyx_lock+{$buy_total} where uid={$uid}");

        //生成订单
        $data = [
            'trust_id'=>$tid,
            'buy_uid'=>$type==1?$uid:$trust['uid'],
            'sale_uid'=>$type==1?$trust['uid']:$uid,
            'order_number'=>$order_number,
            'type' => $type==1?'buy':'sale',
            'price'=>$trust['price'],
            'number'=>$number,
            'buy_fee' => $buy_fee,
            'sale_fee' => $sale_fee,
            'status'=>0,
            'created'=>time(),
            'created_date'=>date('Y-m-d H:i:s')
        ];

        $mo->table = "otc_order_{$coin}";
        $in_id = $mo->insert($data);

        if(($type==1 && $up_id && $lock_id && $in_id) || ($type==2 && $up_id && $in_id)){
            $mo->commit();

           if(MOBILE_CODE){
               $sale_uid = $type==1?$trust['uid']:$uid;
               $sell_user = UserModel::getInstance()->fRow($sale_uid);
               $message = "【火网】尊敬的火网用户，您的OTC({$coin})交易，订单号：{$order_number}成功匹配{$number}{$coin}，请转完币后及时登入平台点击“确认转币”按钮；如有疑问，请联系官方客服。";
               $phone = $sell_user['area']=='+86'?'86'.$sell_user['mo']:$sell_user['area'].$sell_user['mo'];//国内或国外
                $returnMsg = Tool_SmsMeilian::sendSMS($phone,$message);
//                if(strpos($returnMsg,"success")>-1) {
////                    Tool_Log::wlog();
//                }
            }


            $this->ajax("交易成功",1,["repeat"=>$newRepeat]);
        }else{
            $this->ajax("交易失败",0,[$type==2,$up_id,$in_id,["repeat"=>$newRepeat]]);
        }
    }

    //获取订单号
    private static function get_order_number($coin)
    {
        $length = 8;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';//abcdefghijklmnopqrstuvwxyz
        $number = '';
        for ($i = 0; $i < $length; $i++) {
            // 取字符数组 $chars 的任意元素
            $number .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        if(Orm_Base::getInstance()->table("otc_order_{$coin}")->where(['tradeno' => $number])->fRow()) $number=self::get_order_number($coin);
        return $number;

    }

    //卖家确认转币
    public function sale_confirmAction(){
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交",0,["repeat"=>$newRepeat]);
        }
        $oid = coll('post','oid');
        $coin = coll('post','coin');

        if(!$oid || !$coin) $this->ajax('参数错误',0,["repeat"=>$newRepeat]);

        $mo = Orm_Base::getInstance();
        $mo->table = "otc_order_{$coin}";

        $order = $mo->table("otc_order_{$coin}")->where(['id'=>$oid,'status'=>0])->fRow();
        if($order['sale_uid'] != $this->mCurUser['uid']) $this->ajax("未查询到该订单");

        if($up_id = $mo->table("otc_order_{$coin}")->where(['id'=>$oid])->update(['status'=>1])){
            if(MOBILE_CODE){
                $sell_user = UserModel::getInstance()->fRow($order['buy_uid']);
                $message = "【火网】尊敬的火网用户，您的OTC({$coin})交易，订单号：{$order['order_number']},卖家已转币{$order['number']}{$coin}，请收到币后及时登入平台点击“确认收币”按钮；如有疑问，请联系官方客服。";
                $phone = $sell_user['area']=='+86'?'86'.$sell_user['mo']:$sell_user['area'].$sell_user['mo'];//国内或国外
                $returnMsg = Tool_SmsMeilian::sendSMS($phone,$message);
//                if(strpos($returnMsg,"success")>-1) {
////                    Tool_Log::wlog();
//                }
            }


            $this->ajax("确认成功",1,["repeat"=>$newRepeat]);
        };
        $this->ajax("确认失败",0,["repeat"=>$newRepeat]);
    }

    //买家确认收币
    public function buy_confirmAction(){
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交",0,["repeat"=>$newRepeat]);
        }
        $oid = coll('post','oid');
        $coin = coll('post','coin');
        if(!$oid || !$coin) $this->ajax('参数错误',0,["repeat"=>$newRepeat]);

        $mo = Orm_Base::getInstance();
        $order = $mo->table("otc_order_{$coin}")->where(['id'=>$oid,'status'=>1])->fRow();
        if($order['buy_uid'] != $this->mCurUser['uid']) $this->ajax("未查询到该订单",0,["repeat"=>$newRepeat]);

        $trust = $mo->table("otc_trust_{$coin}")->where("id={$order['trust_id']}")->fRow();
        if($trust['numberdeal']==0){
            $un_count = $mo->table("otc_order_{$coin}")->where("trust_id={$order['trust_id']} and (status=0 or status=1)")->fOne("count(id)");
        }

        //事务开始
        $mo->begin();

        $total_price = Tool_Math::mul($order['number'],$order['price']);
        $buy_price = Tool_Math::add($total_price,$order['buy_fee']);
        $sale_price = Tool_Math::sub($total_price,$order['sale_fee']);

//        $this->ajax(1,0,[$numberdeal,$order_un]);

        //更新
        if($trust['numberdeal']==0 && $un_count==1){

            //卖家最后一笔委托退回1000冻结
            if($trust['type']==2){
                $trust_count = $mo->table("otc_trust_{$coin}")->where("uid={$order['sale_uid']} and status=0 and id!={$order['trust_id']} and type=2")->fOne("id");
                if(!$trust_count) {
                    $lock_cnyx = $mo->table("otc_trust_{$coin}")->where("uid={$order['sale_uid']} and lock_cnyx>0 and type=2")->fOne("lock_cnyx");
                }else{
                    $remoke = $mo->table("otc_trust_{$coin}")->where(['id'=>$trust_count])->update(['lock_cnyx'=>$trust['lock_cnyx']]);
                }
            }


            if(isset($lock_cnyx) && $lock_cnyx && $trust['type']==2){
                $tup_id = $mo->exec("update otc_trust_{$coin} set status=1,thaw_cnyx={$lock_cnyx} where id={$order['trust_id']}");
            }else{
                $tup_id = $mo->exec("update otc_trust_{$coin} set status=1 where id={$order['trust_id']}");
            }
            if(!$tup_id){
                $mo->back();
                $this->ajax("确认失败",0,["repeat"=>$newRepeat]);
            }
        }

        //更新卖家资金
        if(isset($lock_cnyx) && $lock_cnyx && $trust['type']==2){
            $sale_price = Tool_Math::add($sale_price,$lock_cnyx);
            $sale_id = $mo->exec("update user set cnyx_over=cnyx_over+{$sale_price},cnyx_lock=cnyx_lock-{$lock_cnyx} where uid={$order['sale_uid']}");
        }else{
            $sale_id = $mo->exec("update user set cnyx_over=cnyx_over+{$sale_price} where uid={$order['sale_uid']}");
        }
        if(!$sale_id){
            $mo->back();
            $this->ajax("确认失败",0,["repeat"=>$newRepeat]);
        }

        //更新买家资金
        if(!$buy_id = $mo->exec("update user set cnyx_lock=cnyx_lock-{$buy_price} where uid={$order['buy_uid']}")){
            $mo->back();
            $this->ajax("确认失败",0,["repeat"=>$newRepeat]);
        }

        //更新订单状态为已完成
        if(!$up_id = $mo->table("otc_order_{$coin}")->where(['id'=>$oid])->update(['status'=>2])){
            $mo->back();
            $this->ajax("确认失败",0,["repeat"=>$newRepeat]);
        }

        $mo->commit();
        if(MOBILE_CODE){
            $sell_user = UserModel::getInstance()->fRow($order['sale_uid']);
            $message = "【火网】尊敬的火网用户，您的OTC({$coin})交易，订单号：{$order['order_number']},买家已确认收币，您的平台账户到账{$sale_price}CNYX，您可以登录平台进行查看；如有疑问，请联系官方客服。";
            $phone = $sell_user['area']=='+86'?'86'.$sell_user['mo']:$sell_user['area'].$sell_user['mo'];//国内或国外
            $returnMsg = Tool_SmsMeilian::sendSMS($phone,$message);
//                if(strpos($returnMsg,"success")>-1) {
////                    Tool_Log::wlog();
//                }
        }

        $this->ajax("操作成功",1,["repeat"=>$newRepeat]);
    }


    //订单撤销
    public function orderCancelAction(){
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交",0,["repeat"=>$newRepeat]);
        }
        $oid = coll('post','oid');
        $coin = coll('post','coin');
        $uid = $this->mCurUser['uid'];

        if(!$oid || !$coin) $this->ajax("未查询到该订单",0,["repeat"=>$newRepeat]);

        $mo = Orm_Base::getInstance();
        $order = $mo->table("otc_order_{$coin}")->where(['id'=>$oid])->fRow();

        if($order['status']==2) $this->ajax("该订单已完成，无法撤销",0,["repeat"=>$newRepeat]);
        if($order['status']==3) $this->ajax("该订单已撤销，不可以重复操作",0,["repeat"=>$newRepeat]);

        if($uid==$order['buy_uid'] && $order['status']==0) $this->ajax("该订单只能由卖家撤销",0,["repeat"=>$newRepeat]);
        if($uid==$order['sale_uid'] && $order['status']==1) $this->ajax("该订单您已确认转币，无法撤销",0,["repeat"=>$newRepeat]);

        if($order['status']){
            //订单已匹配由买家撤销
            //事务开始
            $mo->begin();
            //更新订单
            $oup_id = $mo->table("otc_order_{$coin}")->where(['id'=>$oid])->update(['status'=>3,'updated'=>time(),'updated_date'=>date('Y-m-d H:i:s'),'change_uid'=>$uid]);
            //恢复委托
            $tup_id = $mo->exec("update otc_trust_{$coin} set numberover=numberover-{$order['number']},
numberdeal=numberdeal+{$order['number']},buy_fee_on=buy_fee_on-{$order['buy_fee']},sale_fee_on=sale_fee_on-{$order['sale_fee']},status=0 where id={$order['trust_id']}");
            //更新买家资金
            $total_price = Tool_Math::add(Tool_Math::mul($order['number'],$order['price']),$order['buy_fee']);
            $buy_id = $mo->exec("update user set cnyx_over=cnyx_over+{$total_price},cnyx_lock=cnyx_lock-{$total_price} where uid={$order['buy_uid']}");


//            $this->ajax(1,0,[$oup_id,$tup_id,$buy_id]);
            if($oup_id && $tup_id && $buy_id){
                $mo->commit();
                $this->ajax("撤销成功",1,["repeat"=>$newRepeat]);
            }else{
                $mo->back();
                $this->ajax("撤销失败，请重试",0,["repeat"=>$newRepeat]);
            }
        }else{
            //订单未匹配由卖家撤销

            //事务开始
            $mo->begin();

            //更新订单
            $oup_id = $mo->table("otc_order_{$coin}")->where(['id'=>$oid])->update(['status'=>3,'updated'=>time(),'updated_date'=>date('Y-m-d H:i:s'),'change_uid'=>$uid]);
            //恢复委托
            $tup_id = $mo->exec("update otc_trust_{$coin} set numberover=numberover-{$order['number']},
numberdeal=numberdeal+{$order['number']},buy_fee_on=buy_fee_on-{$order['buy_fee']},sale_fee_on=sale_fee_on-{$order['sale_fee']},status=0 where id={$order['trust_id']}");
            //更新买家资金
            $total_price = Tool_Math::add(Tool_Math::mul($order['number'],$order['price']),$order['buy_fee']);
            $buy_id = $mo->exec("update user set cnyx_over=cnyx_over+{$total_price},cnyx_lock=cnyx_lock-{$total_price} where uid={$order['buy_uid']}");

            if($oup_id && $tup_id && $buy_id){
                $mo->commit();
                $this->ajax("撤销成功",1,["repeat"=>$newRepeat]);
            }else{
                $mo->back();
                $this->ajax("撤销失败，请重试",0,["repeat"=>$newRepeat]);
            }
        }
    }

    //委托撤销
    public function trustCancelAction(){
        $this->_ajax_islogin();
        $this->ajax_validata_user();
        $post_repeat = coll("post","repeat_del");
        $newRepeat = Tool_Request::valiRepeatData($post_repeat);
        if($newRepeat === false){
            $this->ajax("请勿重复提交",0,["repeat"=>$newRepeat]);
        }
        $tid = coll('post','tid');
        $coin = coll('post','coin');
        $uid = $this->mCurUser['uid'];

        if(!$tid || !$coin) $this->ajax("参数错误",0,["repeat"=>$newRepeat]);

        $mo = Orm_Base::getInstance();
        $trust = $mo->table("otc_trust_{$coin}")->where(['id'=>$tid,'uid'=>$uid])->fRow();

        if(!$trust) $this->ajax("未查询到该委托",0,["repeat"=>$newRepeat]);
        if($trust['status']>0) $this->ajax("该委托不能撤销",0,["repeat"=>$newRepeat]);

        $order = $mo->table("otc_order_{$coin}")->where("trust_id={$tid} and (status=0 or status=1)")->fRow();
        if($order) $this->ajax("该委托还有未完成的订单，无法撤销",0,["repeat"=>$newRepeat]);
        $mo->begin();
        if($trust['type']==2){
            //转移冻结资金
            $un_trust = $mo->table("otc_trust_{$coin}")->where("uid=$uid and id!=$tid and status=0 and type=2")->fOne("id");
            if(!$un_trust){
                $lock_cnyx = $mo->table("otc_trust_{$coin}")->where("uid=$uid and lock_cnyx>0 and type=2")->fOne("lock_cnyx");
                if($lock_cnyx){
                    //退回冻结资金
                    $uup_id = $mo->exec("update user set cnyx_over=cnyx_over+{$lock_cnyx},cnyx_lock=cnyx_lock-{$lock_cnyx} where uid=$uid");
                    $thaw_cnyx = $lock_cnyx;
                    if(!$uup_id){
                        $mo->back();
                        $this->ajax("撤销失败",0,["repeat"=>$newRepeat]);
                    }
                }
            }else{
                $remoke = $mo->table("otc_trust_{$coin}")->where(['id'=>$un_trust])->update(['lock_cnyx'=>$trust['lock_cnyx']]);
            }


            $thaw_cnyx = isset($thaw_cnyx)?$thaw_cnyx:0;
            $up_id = $mo->table("otc_trust_{$coin}")->where(['id'=>$tid])->update(['status'=>2,'thaw_cnyx'=>$thaw_cnyx,'updated'=>time(),'updated_date'=>date('Y-m-d H:i:s')]);
            if(!$up_id){
                $mo->back();
                $this->ajax("撤销失败",0,["repeat"=>$newRepeat]);
            }

            $mo->commit();
            $this->ajax("撤销成功",1,["repeat"=>$newRepeat]);
        }else{

//            $this->ajax("买家无法撤销订单，请联系客服");

            //更新买家订单
            $tup_id = $mo->table("otc_trust_{$coin}")->where(['id'=>$tid])->update(['status'=>2,'updated'=>time(),'updated_date'=>date('Y-m-d H:i:s')]);
            //更新买家资产
            $lock_price = Tool_Math::mul($trust['numberdeal'],$trust['price'])+$trust['buy_fee']-$trust['buy_fee_on']+$trust['lock_cnyx'];
            $uup_id = $mo->exec("update user set cnyx_over=cnyx_over+{$lock_price},cnyx_lock=cnyx_lock-{$lock_price} where uid={$uid}");

            if($tup_id && $uup_id){
                $mo->commit();
                $this->ajax("撤销成功，您的账户已退回冻结资金{$lock_price}CNYX",1,["repeat"=>$newRepeat]);
            }else{
                $mo->back();
                $this->ajax("撤销失败",0,["repeat"=>$newRepeat]);
            }
        }
    }

    //查看钱包地址
    public function see_addressAction(){
        $this->_ajax_islogin();

        $coin = coll('post','coin');
        $oid = coll('post','oid');

        $uid = $this->mCurUser['uid'];
        if(!$coin && !$oid) $this->ajax("参数错误");

        $mo = Orm_Base::getInstance();
        $order = $mo->table("otc_order_{$coin}")->where("id=$oid and (buy_uid={$uid} or sale_uid={$uid})")->fRow();
        if(!$order) $this->ajax("未查询到该订单");

        if($order['buy_uid']==$uid){
            $see_uid = $order['sale_uid'];
            $res = ['type'=>'buy'];
        }else{
            $see_uid = $order['buy_uid'];
            $res = $mo->table("otc_address")->field("address,img")->where("coin='{$coin}' and uid=$see_uid and status=1")->fRow();
            $res['type'] = 'sale';
        }

        $see_user = UserModel::getInstance()->field("uid,area,mo,email")->fRow($see_uid);
        $name = AutonymModel::getInstance()->where(['uid' => $see_uid])->fOne('name');

        $res['area'] = $see_user['area'];
        //BOSS手机号码对外展示 更改
        $res['mo'] = $see_user['uid']==2?'15651029170':$see_user['mo'];
        $res['email'] = $see_user['email'];

        if($name){
            $strlen     = mb_strlen($name, 'utf-8');
            $firstStr     = mb_substr($name, 0, 1, 'utf-8');
            if($strlen==2){
                $res['name'] = $firstStr.str_repeat('*', mb_strlen($name, 'utf-8') - 1);
            }else{
                $lastStr     = mb_substr($name, -1, 1, 'utf-8');
                $res['name'] = $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
            }
        }else{
            $res['name'] = '';
        }

        $this->ajax('',1,$res);
    }
}
