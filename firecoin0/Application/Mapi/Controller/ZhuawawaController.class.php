<?php
/**
 * Created by PhpStorm.
 * User: Slagga
 * Date: 9/30/2017
 * Time: 3:37 PM
 */

namespace Mapi\Controller;

class ZhuawawaController extends MapiController {

    protected $ip = ['119.23.152.43'];

    //Test
    private function index()
    {
        $this->auth();
        echo 'here';
    }

    //login  error : 300
    private function login()
    {
        $this->auth('post');

        $email = I('email/s');
        $password = I('password/s');

        if (check($email, 'email')) {
            $user = M('User')->where(['email' => $email])->find();
        }

        if (!$user) {
            $user = M('User')->where(['username' => $email])->find();
        }

        if (!$user) {
            $this->json(['status' => 300, 'message' => '用户不存在！']);
        }

        if (!check($password, 'password')) {
            $this->json(['status' => 301, 'message' => '登录密码格式错误！']);
        }

        if (md5($password) != $user['password']) {
            $this->json(['status' => 302, 'message' => '登录密码错误！']);
        }

        if ($user['status'] != 1) {
            $this->json(['status' => 303, 'message' => '你的账号已冻结请联系管理员！']);
        }

        $this->json(['status' => 200, 'message' => '登陆成功！', 'id' => $user['id']]);
    }

    //recharge  error : 400
    private function recharge()
    {
        $this->auth('post');

        $type = I('type/s');
        $num = I('num/s');
        $uid = I('uid/d');
        $tradeno = I('tradeno/d');

        $uid = M('User')->where(['id' => $uid])->getField('id');
        if (!$uid) {
            $this->json(['status' => 400, 'message' => '请先登录！']);
        }

        if (!check($type, 'n')) {
            $this->json(['status' => 401, 'message' => '充值方式格式错误！']);
        }

        if (!check($num, 'cny')) {
            $this->json(['status' => 402, 'message' => '充值金额格式错误！']);
        }

        $myczType = M('MyczType')->where(['name' => $type])->find();
        if ($type === 'alipay') {
            if (!$myczType) {
                $this->json(['status' => 403, 'message' => '充值方式不存在！']);
            }

            if ($myczType['status'] != 1) {
                $this->json(['status' => 404, 'message' => '充值方式没有开通！']);
            }
            $remark = '港币充值-支付宝支付';
            $num = $num * (1 - 0.01);
        } else {
            $remark = '港币充值-海外支付宝支付';
            $num = $num * (1 - 0.03);
        }

        $mycz_min = ($myczType['min'] ? $myczType['min'] : 1);
        $mycz_max = ($myczType['max'] ? $myczType['max'] : 100000);
        if ($num < $mycz_min) {
            $this->json(['status' => 405, 'message' => '充值金额不能小于' . $mycz_min . '元！']);
        }

        if ($mycz_max < $num) {
            $this->json(['status' => 406, 'message' => '充值金额不能大于' . $mycz_max . '元！']);
        }

        if (!check($tradeno, 'd')) {
            $this->json(['status' => 407, 'message' => '订单编号格式错误！']);
        }

        if (M('Mycz')->where(array('tradeno' => $tradeno))->getField('id')) {
            $this->json(['status' => 408, 'message' => '请勿重复充值！']);
        }

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_user_coin write,weike_mycz write,weike_finance write');
        $rs = [];

        $finance_num_user_coin = $mo->table('weike_user_coin')->where(['userid' => $uid])->find();
        $rs[] = $mo->table('weike_user_coin')->where(['userid' => $uid])->setInc('cny', $num);
        $rs[] = $finance_nameid = $mo->table('weike_mycz')->add(['userid' => $uid, 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 1, 'mum' => $num, 'endtime' => time()]);
        $finance_mum_user_coin = $mo->table('weike_user_coin')->where(['userid' => $uid])->find();
        $finance_hash = md5($uid . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $num . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');

        $rs[] = $mo->table('weike_finance')->add(['userid' => $uid, 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $num, 'type' => 1, 'name' => 'mycz', 'nameid' => $finance_nameid, 'remark' => $remark, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->json(['status' => 200, 'message' => '充值订单创建成功！']);
        } else {
            $mo->execute('rollback');
            $this->json(['status' => 407, 'message' => '提现订单创建失败！']);
        }
    }

    //withdraw  error : 500
    private function withdraw()
    {
        $this->auth('post');

        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $type = I('type/d');
        $uid = I('uid/d');

        $uid = M('User')->where(['id' => $uid])->getField('id');
        if (!$uid) {
            $this->json(['status' => 500, 'message' => '请先登录！']);
        }

        if (!check($num, 'd')) {
            $this->json(['status' => 501, 'message' => '提现金额格式错误！']);
        }

        if (!check($paypassword, 'password')) {
            $this->json(['status' => 502, 'message' => '交易密码格式错误！']);
        }

        if (!check($type, 'd')) {
            $this->json(['status' => 503, 'message' => '提现方式格式错误！']);
        }

        $userCoin = M('UserCoin')->where(['userid' => $uid])->find();
        if ($userCoin['cny'] < $num) {
            $this->json(['status' => 504, 'message' => '可用港币余额不足！']);
        }

        $user = M('User')->where(['id' => $uid])->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->json(['status' => 505, 'message' => '交易密码错误！']);
        }

        if ($user['idcardauth'] == 0) {
            $this->json(['status' => 506, 'message' => '请先进行身份认证！']);
        }

        $userBank = M('UserBank')->where(['id' => $type])->find();
        if (!$userBank) {
            $this->json(['status' => 507, 'message' => '提现地址错误！']);
        }

        $mytx_min = (C('mytx_min') ? C('mytx_min') : 1);
        $mytx_max = (C('mytx_max') ? C('mytx_max') : 1000000);
        $mytx_bei = C('mytx_bei');
        $mytx_fee = C('mytx_fee');

        if ($num < $mytx_min) {
            $this->json(['status' => 508, 'message' => '每次提现金额不能小于' . $mytx_min . '元！']);
        }

        if ($mytx_max < $num) {
            $this->json(['status' => 509, '每次提现金额不能大于' . $mytx_max . '元！']);
        }

        if ($mytx_bei) {
            if ($num % $mytx_bei != 0) {
                $this->json(['status' => 510, 'message' => '每次提现金额必须是' . $mytx_bei . '的整倍数！']);
            }
        }
        if(round(($num / 100) * $mytx_fee, 2) > 5){
            $fee = round(($num / 100) * $mytx_fee, 2);
            $mum = round(($num / 100) * (100 - $mytx_fee), 2);
        }else{
            $fee = 5;
            $mum =$num - 5;
        }

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_mytx write , weike_user_coin write ,weike_finance write');
        $rs = [];
        $finance_num_user_coin = $mo->table('weike_user_coin')->where(['userid' => $uid])->find();
        $rs[] = $mo->table('weike_user_coin')->where(['userid' => $uid])->setDec('cny', $num);
        $rs[] = $finance_nameid = $mo->table('weike_mytx')->add(['userid' => $uid, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $user['truename'], 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'endtime' => time(), 'status' => 1]);
        $finance_mum_user_coin = $mo->table('weike_user_coin')->where(['userid' => $uid])->find();
        $finance_hash = md5($uid . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');

        $rs[] = $mo->table('weike_finance')->add(['userid' => $uid, 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $finance_nameid, 'remark' => '港币提现-夹娃娃支付', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->json(['status' => 200, 'message' => '提现订单创建成功！']);
        } else {
            $mo->execute('rollback');
            $this->json(['status' => 511, 'message' => '提现订单创建失败！']);
        }
    }

    //withdraw address
    private function withdraw_address()
    {
        $this->auth('post');

        $uid = I('uid/d');

        $uid = M('User')->where(['id' => $uid])->getField('id');
        if (!$uid) {
            $this->json(['status' => 600, 'message' => '请先登录！']);
        }

        $userBankList = M('UserBank')->where(['userid' => $uid, 'status' => 1])->order('id desc')->select();

        if($userBankList) {
            $this->json(['status' => 200, 'message' => '获取提现地址成功！', 'data' => $userBankList]);
        } else {
            $this->json(['status' => 601, 'message' => '提现地址为空！']);
        }
    }
}