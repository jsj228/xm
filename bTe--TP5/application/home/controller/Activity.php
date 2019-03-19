<?php
/**
 * @author toby
 * review code 2018-08-23
 */

namespace app\home\controller;

use think\Db;
use think\Exception;

class Activity extends Home
{

    //夹娃娃充值奖励
    public function myaward()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }
        $this->assign('prompt_text', model('Text')->get_content('finance_myjp'));

        $id = input('post.id');
        $time = input('post.time');
        if ($id && $time) {
            $time = date('Y-m-d H:i:s', $time + 3600 * 24 * 365);
            $this->error('请在 ' . $time . ' 之后操作！');
        }

        $where['username'] = username();
        $list = Db::name('UserAward')->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
            $item['arrival_time'] = $item['addtime'] + 3600 * 24 * 365;
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //EJF
    public function myejf()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        if (IS_POST) {
            $id = input('id/d');

            if (!check($id, 'd')) {
                $this->error('请选择解冻项！');
            }

            $IssueEjf = Db::name('IssueEjf')->where(array('id' => $id))->find();

            if (!$IssueEjf) {
                $this->error('参数错误！');
            }

            if ($IssueEjf['status']) {
                $this->error('当前解冻已完成！');
            }

            if ($IssueEjf['ci'] <= $IssueEjf['unlock']) {
                $this->error('非法访问！');
            }

            $tm = $IssueEjf['endtime'] + (60 * 60 * $IssueEjf['jian']);
            if (time() < $tm) {
                $this->error('解冻时间还没有到,请在<br>【' . addtime($tm) . '】<br>之后再次操作');
            }

            if ($IssueEjf['userid'] != userid()) {
                $this->error('非法访问');
            }

            $jd_num = round($IssueEjf['num'] / $IssueEjf['ci'], 6);
            Db::startTrans();
            try {
                $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setInc($IssueEjf['coinname'], $jd_num);
                $rs[] = Db::table('weike_issue_ejf')->where(array('id' => $IssueEjf['id']))->update(array('unlock' => $IssueEjf['unlock'] + 1, 'endtime' => $tm));

                if ($IssueEjf['ci'] <= $IssueEjf['unlock'] + 1) {
                    $rs[] = Db::table('weike_issue_ejf')->where(array('id' => $IssueEjf['id']))->update(array('status' => 1));
                }

                if (check_arr($rs)) {
                    Db::commit();
                    $this->success('解冻成功！');
                } else {
                    Db::rollback();
                    $this->error('解冻失败！');
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('解冻失败！');
            }
        } else {
            $where = ['userid' => userid()];
            $list = Db::name('IssueEjf')->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
                $item['shen'] = round((($item['ci'] - $item['unlock']) * $item['num']) / $item['ci'], 6);
                $item['username'] = Db::name('User')->where(['id' => $item['userid']])->value('username');
                return $item;
            });
            $show = $list->render();


            $this->assign('list', $list);
            $this->assign('page', $show);
            return $this->fetch();
        }
    }

    //雷达钱包
    public function ldqb()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }
        $vbcb = Db::name('UserCoin')->where(array('userid' => userid()))->value('vbcb');
        $this->assign('vbcb', $vbcb);
        $moble = Db::name('User')->where(array('id' => userid()))->value('moble');
        if ($moble) {
            $moble = substr_replace($moble, '****', 3, 4);
        } else {
            $this->error('请先认证手机！', url('Home/Order/index'));
        }

        $this->assign('vbcb', $vbcb);
        $this->assign('moble', $moble);
        return $this->fetch();
    }

    //添加提现方式 微信 和 支付宝
    public function pay()
    {
        //判断登陆
        if (!userid()) {
            $this->redirect('/#login');
        }

        if (IS_POST) {
            $name = input('name/s');
            $bank = input('bank/s');
            $bankaddr = input('bankaddr/s','');
            $bankcard = input('bankcard/s');
            $paypassword = input('paypassword/s');

            if (!check($name, 'a')) {
                $this->error('备注名称格式错误！');
            }

            if (!check($bank, 'a')) {
                $this->error('支付方式格式错误！');
            }

            if (!check($bankcard, 'a')) {
                $this->error('账号格式错误！');
            }

            if (strlen($bankcard) < 5 || strlen($bankcard) > 29) {
                $this->error('账号格式错误！');
            }

            if (!check($paypassword, 'password')) {
                $this->error('交易密码格式错误！');
            }

            $user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');
            if (md5($paypassword) != $user_paypassword) {
                $this->error('交易密码错误！');
            }

            $userBank = Db::name('UserBank')->where(array('userid' => userid()))->select();
            foreach ($userBank as $k => $v) {
                if ($v['name'] == $name) {
                    $this->error('请不要使用相同的备注名称！');
                }

                if ($v['bankcard'] == $bankcard) {
                    $this->error('账号已存在！');
                }
            }

            if (10 <= count($userBank)) {
                $this->error('每个用户最多只能添加10个提现账户！');
            }

            //根据支付类型， 判断省市
            if ($bank === '微信') {
                $bankprov = '广东';
                $bankcity = '深圳';
            } elseif ($bank === '支付宝') {
                $bankprov = '浙江';
                $bankcity = '杭州';
            } else {
                $this->error('支付类型出错！');
            }

            //微信支付 和 支付宝支付 唯一性 确定
            $count = Db::name('UserBank')->where(array('userid' => userid(), 'bank' => $bank))->count();
            if ($count > 0) {
                $this->error('支付类型已存在！');
            }
            $rs = Db::name('UserBank')->insert(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1));
            if (false !== $rs) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $user = Db::name('User')->where(array('id' => userid()))->find();
//            if ($user['idcardauth'] == 0) {
//                $this->redirect('/user/nameauth');
//            }

            //渲染 图片
            $img_weixin = Db::name('UserBank')->where(['bank' => '微信', 'userid' => userid()])->value('bankaddr');
            $img_alipay = Db::name('UserBank')->where(['bank' => '支付宝', 'userid' => userid()])->value('bankaddr');
            $this->assign('img_weixin', $img_weixin);
            $this->assign('img_alipay', $img_alipay);


            $this->assign('prompt_text', model('Text')->get_content('user_bank'));
            return $this->fetch();
        }
    }

    /**
     * 获得利息
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function mycoin()
    {


        if (!userid()) {
            $this->redirect('/#login');
        }

        $coinname = input('coinname/s', 'wcg');

        // wcg 和 er 限制
        $coins_allowed = ['wcg','erc','ejf','drt','mat'];
        if (! in_array($coinname,$coins_allowed,true)) {
            $this->error('未知加密货币！');
        }

        if (IS_POST) {

            //token验证
            $token_str = input('post.token_str');
            if (!token_check($token_str)) {
                $this->error('令牌错误！');
            }
            //星期一开始
            if (time() < 1516579688) {
                $this->error('活动 一月二十二号之后开始！');
            }

            //用户判断
            $IssueCoin = Db::name('IssueCoin')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coinname))->order('addtime desc')->limit(1)->find();
            if ($IssueCoin && $IssueCoin['userid'] != userid()) {
                $this->error('非法访问');
            }

            //每天一次判断
            if ($IssueCoin) {
                if (86400 > (time() - $IssueCoin['addtime'])) {
                    $this->error("一天只能获取一次 {$coinname} 利息！");
                }
            }

            //获取可用数量，冻结数量，总数量，利息数量
            $total = Db::name('UserCoin')->field("{$coinname}, {$coinname}d, {$coinname} + {$coinname}d as sum")->where(['userid' => userid()])->find();
            if ($total['sum'] == 0) {
                $this->error("账户余额为零，不能获取 {$coinname} 利息！");
            }

            $fee = $total['sum'] * config("{$coinname}_interest");

            //获取币种名字
            $title = Db::name('Coin')->where(['name' => $coinname])->value('title');

            Db::startTrans();
            try {

                Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
                $last_time = Db::name('IssueCoin')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coinname))->order('addtime desc')->value('addtime');
                if ($last_time) {
                    if (86400 > (time() - $last_time)) {
                        Db::rollback();
                    }
                }

                $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setInc($coinname, $fee);
                $rs[] = Db::table('weike_issue_coin')->insert([
                    'userid' => userid(),
                    'name' => $title,
                    'coinname' => $coinname,
                    'num' => $total[$coinname],
                    'freeze' => $total["{$coinname}d"],
                    'interest' => $fee,
                    'count' => $IssueCoin['count'] == 0 ? 1 : $IssueCoin['count'] + 1,
                    'addtime' => time(),
                    'status' => 1
                ]);

                if (check_arr($rs)) {
                    Db::commit();
                    $this->success('操作成功！');
                } else {
                    Db::rollback();
                    $this->error('操作失败！');
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('操作失败！');
            }
        } else {
            $where = ['userid' => userid(), 'coinname' => $coinname];
            $list = Db::name('IssueCoin')->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
                $item['username'] = Db::name('User')->where(['id' => $item['userid']])->value('username');
                return $item;
            });
            $show = $list->render();

            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->assign('coinname', $coinname);
            return $this->fetch();
        }
    }

    //华克金转入分流
    public function wcgzr()
    {
        $coin = input('coin/s', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }
        //获取用户华克金数量
        $user_coin = Db::name('UserCoin')->where(['userid' => userid()])->find();
        $user_coin = $user_coin[$coin];
        //获取华克金配置
        $Coin = Db::name('Coin')->where(['type' => 'rgb', 'status' => 1])->select();
        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        unset($coin_list['ejf']);
        unset($coin_list['fil']);
        if ($coin_list[$coin]['zr_jz'] == '0') {
            $this->error('当前'.$coin_list[$coin]['title'].'禁止转入');
        } else {
            $moble = Db::name('User')->where(array('id' => userid()))->value('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', url('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }

        for (; true;) {
            $tradeno = self::get_code();
            if (!Db::name('Myzr')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }
        //用户转入记录
        $list = Db::name('Myzr')->where(['userid' => userid(), 'coinname' => $coin])->order('id desc')->select();

        $this->assign('weike_opencoin', 0);
        $this->assign('coin_list', $coin_list);
        $this->assign('coin', $coin);
        $this->assign('user_coin', $user_coin);
        $this->assign('tradeno', $tradeno);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function wcgzr_info()
    {
        $id = input('id/d', null);
        if (!userid()) {
            die(json_encode(array('code' => 400, 'msg' => '请先登录', 'data' => '')));
        }
        if (!check($id, 'd')) {
            die(json_encode(array('code' => 401, 'msg' => '参数错误', 'data' => '')));
        }
        $data = Db::name('Myzr')->where(['id' => $id, 'userid' => userid()])->find();
        if ($data) {
            die(json_encode(array('code' => 200, 'msg' => '查询成功', 'data' => $data)));
        } else {
            die(json_encode(array('code' => 402, 'msg' => '未查询到记录', 'data' => '')));
        }
    }


    static function get_code()
    {
        $tradeno = '';
        for ($i = 1; $i <= 8; $i++) {
            $tradeno .= chr(rand(65, 90));
        }
        return $tradeno;
    }

    //华克金转入撤销
    public function wcgChexiao()
    {
        $id = input('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $myzr = Db::name('Myzr')->where(array('id' => $id))->find();
        if (!$myzr) {
            $this->error('充值订单不存在！');
        }
        if ($myzr['status'] != 0){
            $this->error('订单已处理不可以撤销');
        }
        if ($myzr['userid'] != userid()) {
            $this->error('非法操作！');
        }
        //限定每天只能撤销两次
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $chexiao_num = count(Db::name('Myzr')->where(['userid' => userid(), 'status' => 2, 'addtime' => array('gt', $beginToday)])->select());
        if ($chexiao_num >= 5) {
            $this->error('您当天撤销操作过于频繁，请明天再进行尝试。');
        }

        $rs = Db::name('Myzr')->where(array('id' => $id))->update(array('status' => 2));
        if ($rs) {
            $this->success('操作成功', array('id' => $id));
        } else {
            $this->error('操作失败！');
        }

    }
    //epay
    public function epay()
    {
        $mer_account = '821207587@qq.com';
        $mer_name = '比特国际';
        for (; true;) {
            $tradeno = str_shuffle(substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 6) . substr(str_shuffle(str_repeat('123456789', 5)), 0, 4));
            if (!Db::name('Epaycz')->where(array('order_num' => $tradeno))->find()) {
                break;
            }
        }
        $a_parm['scur'] = 'USD';
        $a_parm['tcur'] = 'CNY';
        $a_parm['appkey'] = '34806';
        $a_parm['sign'] = '69f649c2704246ddfe60ea61364bd7f4';
        $a_parm['apiurl'] = 'http://api.k780.com/?app=finance.rate&';
        $a_parm['format'] = 'json';
        $json = nowapi_call($a_parm);
        $rate = $json['rate'];

        $list = Db::name('Epaycz')->where(['userid' => userid()])->order('id desc')->paginate(10);
        $show = $list->render();

        $this->assign('mer_account', $mer_account);
        $this->assign('mer_name', $mer_name);
        $this->assign('tradeno', $tradeno);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('rate', $rate);
        return $this->fetch();
    }


    //生成订单
    public function addRecord(){
        $num = input('num');
        $order_num = input('order_num/s');
        $btc_epay = input('btc_epay/s');
        $addorder = Db::name('epaycz')->insert(['userid' => userid(),'btc_epay' =>$btc_epay, 'num' => $num ,'order_num' => $order_num,'addtime' => time()]);
        if (false !== $addorder){
            $this->success('生成订单成功');
        }else{
            $this->error('生成订单出错');
        }
    }

    //add epay order
    public function addOrder(){
        $data = $_POST;
        if ($data['STATUS'] == 1){
            Db::name('Epaycz')->where(['order_num' => $data['PAYMENT_ID']])->update(['status' => 1]);
            $this->error('充值失败');
        }else{
            $a_parm['scur'] = 'USD';
            $a_parm['tcur'] = 'CNY';
            $a_parm['appkey'] = '34806';
            $a_parm['sign'] = '69f649c2704246ddfe60ea61364bd7f4';
            $a_parm['apiurl'] = 'http://api.k780.com/?app=finance.rate&';
            $a_parm['format'] = 'json';
            $json = nowapi_call($a_parm);
            $rate = $json['rate'];
            $num = $data['PAYMENT_AMOUNT'];
            $order_num = $data['PAYMENT_ID'];
            $coinname = $data['PAYMENT_UNITS'];
            $order = Db::name('Epaycz')->where(['order_num' => $order_num])->find();
            if ($order){
                if ($order['status'] == 0){
                    $mum = $num * $rate;
                    Db::startTrans();
                    try {
                        $rs = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $order['userid']))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $order['userid']))->find();
                        $rs[] = Db::table('weike_epaycz')->where(['order_num' => $order_num])->update(['num' => $num, 'mum' => $mum, 'endtime' => time(), 'coinname' => $coinname, 'status' => 2, 'rate' => $rate, 'user_epay' => $data['PAYER_ACCOUNT']]);
                        $rs[] = Db::table('weike_user_coin')->where(['userid' => $order['userid']])->setInc('hkd', $mum);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $order['userid']))->find();
                        $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $order['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];
                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }
                        $rs[] = Db::table('weike_finance')->insert(array('userid' => $order['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $mum, 'type' => 1, 'name' => 'c2c', 'nameid' => $order['id'], 'remark' => 'epay充值-成功到账', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                        if (check_arr($rs)) {
                            Db::commit();
                            $this->success('充值成功！');
                        } else {
                            Db::rollback();
                            $this->error('充值失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('充值失败！');
                    }
                }
            }else{
                $this->error('订单不存在');
            }
        }
    }
}