<?php

namespace app\home\controller;

use think\Db;
use think\Exception;

class Finance extends Home
{
    //财务中心-我的财产
    public function index()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $CoinList = Db::name('Coin')->where(array('status' => 1))->select();
        $UserCoin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $Market = Db::name('Market')->where(array('status' => 1))->select();



        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $cny['zj'] = 0;
        foreach ($CoinList as $k => $v) {
            if ($v['name'] == 'hkd') {
                $cny['ky'] = round($UserCoin[$v['name']], 2) * 1;
                $cny['dj'] = round($UserCoin[$v['name'] . 'd'], 2) * 1;
                $cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];

            } else {

                try{
                    if ($Market[config('market_type')[$v['name']]]['new_price']) {
                        $jia = $Market[config('market_type')[$v['name']]]['new_price'];
                    }
                }catch (\Exception $e){
                    $jia = 1;
                }

                //开启市场时才显示对应的币
                if (in_array($v['name'], config('coin_on'))) {
                    $coinList[$v['name']] = [
                        'name' => $v['name'],
                        'type' => $v['type'],
                        'img' => $v['img'],
                        'title' => $v['title'] . '(' . strtoupper($v['name']) . ')',
                        'xnb' => number_format($UserCoin[$v['name']], 6),
                        'xnbd' => number_format($UserCoin[$v['name'] . 'd'], 6),
                        'xnbz' => number_format($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd'], 6),
                        'jia' => $jia,
                        'zhehe' => number_format(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, 2)
                    ];
                }
                $cny['zj'] = round($cny['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2);
            }
        }

        //排序
        $xnbz = array_column($coinList,'xnbz');
        array_multisort($xnbz,SORT_DESC,$coinList );
        $coinListSort = $coinList;

        //分页
        $curPage = input('page') ? input('page') : 1;//当前第x页，有效值为：1,2,3,4,5...
        $listRow = 10;//每页2行记录

        $showData = array_chunk($coinListSort, $listRow, true);
        $showData = $showData[$curPage - 1];

        $p = \think\paginator\driver\Bootstrap::make($showData, $listRow, $curPage, count($coinListSort), false, [
            'var_page' => 'page',
            'path'     => url('finance/index'),//这里根据需要修改url
            'query'    => [],
            'fragment' => '',
        ]);

        $p->appends($_GET);

        $this->assign('page', $p->render());
        $this->assign('coinList', $p);
        $this->assign('prompt_text', model('Text')->get_content('finance_index'));
        return $this->fetch();
    }

    //分红中心
    public function fhindex()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('game_fenhong'));
        $coin_list = model('Coin')->get_all_xnb_list_allow();
        foreach ($coin_list as $k => $v) {
            $list[$k]['img'] = model('Coin')->get_img($k);
            $list[$k]['title'] = $v;
            $list[$k]['quanbu'] = model('Coin')->get_sum_coin($k);
            $list[$k]['wodi'] = model('Coin')->get_sum_coin($k, userid());
            $list[$k]['bili'] = round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%';
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    //我的分红
    public function myfhroebx()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('game_fenhong_log'));
        $where['userid'] = userid();
        $Model = Db::name('FenhongLog');
        $list = $Model->where($where)->order('id desc')->paginate(15);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //银行
    public function bank()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $UserBankType = Db::name('UserBankType')->where(array('status' => 1))->order('id desc')->select();
        $this->assign('UserBankType', $UserBankType);

        $user = Db::name('User')->where(array('id' => userid()))->find();

        $truename = $user['truename'];
        $this->assign('truename', $truename);
        $UserBank = Db::name('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();

        $this->assign('UserBank', $UserBank);
        $this->assign('prompt_text', model('Text')->get_content('user_bank'));
        return $this->fetch();
    }

    //添加银行
    public function upbank()
    {
        $name = input('name/s');
        $bank = input('bank/s');
        $bankprov = input('bankprov/s');
        $bankcity = input('bankcity/s');
        $bankaddr = input('bankaddr/s');
        $bankcard = input('bankcard/s');
        $paypassword = input('paypassword/s');

        if (!userid()) {
            $this->redirect('/#login');
        }

        if (!check($name, 'a')) {
            $this->error('备注名称格式错误！');
        }

        if (!check($bank, 'a')) {
            $this->error('开户银行格式错误！');
        }

        if (!check($bankprov, 'c')) {
            $this->error('开户省市格式错误！');
        }

        if (!check($bankcity, 'c')) {
            $this->error('开户省市格式错误2！');
        }

        if (!check($bankaddr, 'a')) {
            $this->error('开户行地址格式错误！');
        }

        if (!check($bankcard, 'd')) {
            $this->error('银行账号格式错误！');
        }

        if (strlen($bankcard) < 16 || strlen($bankcard) > 19) {
            $this->error('银行账号格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        $user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');
        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('UserBankType')->where(array('title' => $bank))->find()) {
            $this->error('开户银行错误！');
        }

        $userBank = Db::name('UserBank')->where(array('userid' => userid()))->select();
        foreach ($userBank as $k => $v) {
            if ($v['name'] == $name) {
                $this->error('请不要使用相同的备注名称！');
            }

            if ($v['bankcard'] == $bankcard) {
                $this->error('银行卡号已存在！');
            }
        }

        if (10 <= count($userBank)) {
            $this->error('每个用户最多只能添加10个银行卡账户！');
        }

        $rs = Db::name('UserBank')->insert(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1));
        if (false !== $rs) {
            $this->success('银行添加成功！');
        } else {
            $this->error('银行添加失败！');
        }
    }

    //删除银行
    public function delbank()
    {
        $id = input('id/d');
        $paypassword = input('paypassword/s');
        if (!userid()) {
            $this->redirect('/#login');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('UserBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error('非法访问！');
        } else if (Db::name('UserBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //HKD充值
    public function mycz()
    {
        $status = input('status/d', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mycz'));
        $alipaymycz = Db::name('MyczType')->where(['status' => 1, 'name' => 'alipay'])->find();
        $weixinmycz = Db::name('MyczType')->where(['status' => 1, 'name' => 'weixin'])->find();
        $bankmycz = Db::name('MyczType')->where(['status' => 1, 'name' => 'bank'])->find();
        $this->assign('alipaymycz', $alipaymycz);
        $this->assign('weixinmycz', $weixinmycz);
        $this->assign('bankmycz', $bankmycz);

        $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['hkd'] = round($user_coin['hkd'], 2);
        $user_coin['hkdd'] = round($user_coin['hkdd'], 2);
        $this->assign('user_coin', $user_coin);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4) || ($status == 5) || ($status == 6)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        $list = Db::name('Mycz')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['type'] = Db::name('MyczType')->where(array('name' => $item['type']))->value('title');
            $item['typeEn'] = $item['type'];
            $item['num'] = (Num($item['num']) ? Num($item['num']) : '');
            $item['mum'] = (Num($item['mum']) ? Num($item['mum']) : '');
            return $item;
        });

        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //充值汇款
    public function myczHuikuan()
    {
        $id = input('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mycz = Db::name('Mycz')->where(array('id' => $id))->find();
        if (!$mycz) {
            $this->error('充值订单不存在！');
        }

        if ($mycz['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mycz['status'] != 0) {
            $this->error('订单已经处理过！');
        }
        Db::startTrans();
        try{
            $rs = Db::name('Mycz')->where(array('id' => $id))->update(array('status' => 3));
            if (false !== $rs) {
                Db::commit();
                $this->success('操作成功');
            } else {
                Db::rollback();
                $this->error('操作失败！');
            }

        }catch (Exception $e){
            Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('操作失败！');
        }

    }

    //充值撤销
    public function myczChexiao()
    {
        $id = input('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mycz = Db::name('Mycz')->where(array('id' => $id))->find();
        if (!$mycz) {
            $this->error('充值订单不存在！');
        }

        if ($mycz['userid'] != userid()) {
            $this->error('非法操作！');
        }
        //限定每天只能撤销两次
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $chexiao_num = count(Db::name('Mycz')->where(['userid' => userid(), 'status' => 4, 'addtime' => array('gt', $beginToday)])->select());
        if ($chexiao_num >= 5) {
            $this->error('您当天撤销操作过于频繁，请明天再进行尝试。');
        }
        if ($mycz['status'] == 1 || $mycz['status'] == 2 || $mycz['status'] == 4 || $mycz['status'] == 5 || $mycz['status'] == 3) {
            $this->error('订单不能撤销！');
        }

        Db::startTrans();
        try{
            $rs = Db::name('Mycz')->where(array('id' => $id))->update(array('status' => 4));
            if (false !== $rs) {
                Db::commit();
                $this->success('操作成功', array('id' => $id));
            } else {
                Db::rollback();
                $this->error('操作失败！');
            }

        }catch (Exception $e){
            Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('操作失败！');
        }

    }

    //充值提交
    public function myczUp()
    {
        $this->error('充值系统升级中');
        $type = input('type/s');
        $num = input('num/s');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
            $this->error('出现未知错误！');
        }

        if (!check($type, 'n')) {
            $this->error('充值方式格式错误！');
        }

        if (!check($num, 'cny')) {
            $this->error('充值金额格式错误！');
        }

        $myczType = Db::name('MyczType')->where(array('name' => $type))->find();
        if (!$myczType) {
            $this->error('充值方式不存在！');
        }

        if ($myczType['status'] != 1) {
            $this->error('充值方式没有开通！');
        }

        $mycz_min = ($myczType['min'] ? $myczType['min'] : 1);
        $mycz_max = ($myczType['max'] ? $myczType['max'] : 100000);
        if ($num < $mycz_min) {
            $this->error('充值金额不能小于' . $mycz_min . '元！');
        }

//        if ($mycz_max < $num) {
//            $this->error('充值金额不能大于' . $mycz_max . '元！');
//        }

        if ($myczType = Db::name('Mycz')->where(array('userid' => userid(), 'status' => 0))->find()) {
            $this->error('您还有未付款的订单！');
        }

        if (Db::name('Mycz')->where(array('userid' => userid(), 'status' => 3))->find()) {
            $this->error('您还有未处理的订单！');
        }

        for (; true;) {
            $tradeno = tradeno();

            if (!Db::name('Mycz')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }

        $mycz = Db::name('Mycz')->insert(array('userid' => userid(), 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0));
        if ($mycz) {
            $this->success('充值订单创建成功！', array('id' => $mycz));
        } else {
            $this->error('提现订单创建失败！');
        }
    }

    //提现记录
    public function outlog()
    {
        $status = input('status/d', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mytx'));

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        $where['userid'] = userid();

        $list = Db::name('Mytx')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['num'] = (Num($item['num']) ? Num($item['num']) : '');
            $item['fee'] = (Num($item['fee']) ? Num($item['fee']) : '') > 5 ? (Num($item['fee']) ? Num($item['fee']) : '') : 5;
            $item['mum'] = (Num($item['mum']) ? Num($item['mum']) : '');


            return $item;
        });

        $show = $list->render();


        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //我的提现
    public function mytx()
    {
        $status = input('status/d', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mytx'));
        $moble = Db::name('User')->where(array('id' => userid()))->value('moble');

        if ($moble) {
            $moble = substr_replace($moble, '****', 3, 4);
        } else {
            $this->error('请先认证手机！', url('Home/Order/index'));
        }

        $this->assign('moble', $moble);
        $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['hkd'] = round($user_coin['hkd'], 2);
        $user_coin['hkdd'] = round($user_coin['hkdd'], 2);
        $this->assign('user_coin', $user_coin);
        $userBankList = Db::name('UserBank')->where(array('userid' => userid(), 'status' => 1, 'bank' => ['not in', ['微信', '支付宝']]))->order('id desc')->select();
        $this->assign('userBankList', $userBankList);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        $list = Db::name('Mytx')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['num'] = (Num($item['num']) ? Num($item['num']) : '');
            $item['fee'] = (Num($item['fee']) ? Num($item['fee']) : '');
            $item['mum'] = (Num($item['mum']) ? Num($item['mum']) : '');
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //提现提交
    public function mytxUp()
    {
        $this->error('提现系统升级中');
        $moble_verify = input('moble_verify/d');
        $num = input('num/f');
        $paypassword = input('paypassword/s');
        $type = input('type/d');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
            $this->error('出现未知错误！');
        }

        if (!check($moble_verify, 'd')) {
            $this->error('短信验证码格式错误！');
        }

        if (!check($num, 'd')) {
            $this->error('提现金额格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($type, 'd')) {
            $this->error('提现方式格式错误！');
        }

        if ($moble_verify != session('mytx_verify')) {
            $this->error('短信验证码错误！');
        }

        $userCoin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        if ($userCoin['hkd'] < $num) {
            $this->error('可用港币余额不足！');
        }

        $user = Db::name('User')->where(array('id' => userid()))->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }

        if ($user['idcardauth'] == 0) {
            $this->error('请先进行身份认证！');
        }

        $userBank = Db::name('UserBank')->where(array('id' => $type))->find();
        if (!$userBank) {
            $this->error('提现地址错误！');
        }

        $mytx_min = (config('mytx_min') ? config('mytx_min') : 1);
        $mytx_max = (config('mytx_max') ? config('mytx_max') : 1000000);
        $mytx_bei = config('mytx_bei');
        $mytx_fee = config('mytx_fee');

        if ($num < $mytx_min) {
            $this->error('每次提现金额不能小于' . $mytx_min . '元！');
        }

        if ($mytx_max < $num) {
            $this->error('每次提现金额不能大于' . $mytx_max . '元！');
        }

        if ($mytx_bei) {
            if ($num % $mytx_bei != 0) {
                $this->error('每次提现金额必须是' . $mytx_bei . '的整倍数！');
            }
        }

        $now = mktime(0, 0, 0, date('m', time()), date('d', time()), date('Y', time()));
        $count = Db::name('mytx')->where(['userid' => userid(), 'addtime' => ['gt', $now], 'status' => ['between', [0, 1]]])->count();
        if ($count >= 2) {
            $mytx_fee = $mytx_fee * $count;
        }

        if (round(($num / 100) * $mytx_fee, 2) > 5) {
            $fee = round(($num / 100) * $mytx_fee, 2);
            $mum = round(($num / 100) * (100 - $mytx_fee), 2);
        } else {
            $fee = 5;
            $mum = $num - 5;
        }

        Db::startTrans();
        try {
            $rs = [];
            $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => userid()))->order('id desc')->find();
            $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
            $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setDec('hkd', $num);
            $rs[] = $finance_nameid = Db::table('weike_mytx')->insert(array('userid' => userid(), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $user['truename'], 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'status' => 0), false, true);
            $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => userid()))->find();
            $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mum . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
            $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = Db::table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $finance_nameid, 'remark' => '港币提现-申请提现', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

            if (check_arr($rs)) {
                session('mytx_verify', null);
                Db::commit();
                $this->success('提现订单创建成功！');
            } else {
                Db::rollback();
                $this->error('提现订单创建失败！');
            }
        }catch (Exception $e){
            Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('提现订单创建失败！');
        }
    }

    //提现撤销
    public function mytxChexiao()
    {
        $id = input('id/d');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mytx = Db::name('Mytx')->where(array('id' => $id))->find();

        if (!$mytx) {
            $this->error('提现订单不存在！');
        }

        if ($mytx['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mytx['status'] != 0) {
            $this->error('订单不能撤销！');
        }

        Db::startTrans();
        try {
            $rs = [];
            $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $mytx['userid']))->order('id desc')->find();
            $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $mytx['userid']))->find();
            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $mytx['userid']))->setInc('hkd', $mytx['num']);
            $rs[] = Db::table('weike_mytx')->where(array('id' => $mytx['id']))->setField('status', 2);
            $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $mytx['userid']))->find();
            $finance_hash = md5($mytx['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mytx['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
            $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = Db::table('weike_finance')->insert(array('userid' => $mytx['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => '港币提现-撤销提现', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

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
    }

    //币种转入
    public function myzr()
    {
        $coin = input('coin/s', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }
        $this->assign('prompt_text', model('Text')->get_content('finance_myzr'));

        if (isset(config('coin')[$coin]) && !empty(config('coin')[$coin])) {
            $coin = trim($coin);
        } else {
            $coin = config('xnb_mr');
        }
        $this->assign('xnb', $coin);
        $Coin = Db::name('Coin')->where(array(
            'status' => 1,
            'type' => array('neq', 'rmb')
        ))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $this->assign('coin_list', $coin_list);
        $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $this->assign('user_coin', $user_coin);
        $Coin = Db::name('Coin')->where(array('name' => $coin))->find();
        $this->assign('zr_jz', $Coin['zr_jz']);


        $weike_getCoreConfig = weike_getCoreConfig();
        if (!$weike_getCoreConfig) {
            $this->error('核心配置有误');
        }

        $this->assign("weike_opencoin", $weike_getCoreConfig['weike_opencoin']);

        if ($weike_getCoreConfig['weike_opencoin'] == 1) {
            if (!$Coin['zr_jz']) {
                $qianbao = '当前币种禁止转入！';
            } else {
                $qbdz = $coin . 'b';

                if (empty($user_coin[$qbdz])) {
                    if ($Coin['type'] == 'rgb') {
                        if ($qbdz == 'wcgb') {
                            $qianbao = "WCG-XXXX-XXXX-XXXX-XXXX";
                            $tishi = '<a style="color: #FD2537;text-decoration: underline;font-weight: bold;font-size: 14px;" target="_blank" href="/Article/type/id/25">
                                    点击查看华克金充值指南(转入时请务必备注您在本平台的登录账号)</a>';
                        } elseif ($qbdz == 'vbcb') {
                            $qianbao = "r4uZofeNPTMsZP1aLHR3LAJx1HR6oemWm8";
                            $tishi = '<a style="color: #FD2537;text-decoration: underline;font-weight: bold;font-size: 14px;" target="_blank" href="/Article/type/id/26">
                                    点击查看雷达币充值指南(请先到财务中心绑定雷达币钱包再转入)</a>';
                        } else {
                            $qianbao = md5(username() . $coin);
                            $rs = Db::name('UserCoin')->where(array('userid' => userid()))->update(array($qbdz => $qianbao));
                            if (!$rs) {
                                $this->error('生成钱包地址出错！');
                            }
                        }
                    }

                    if ($Coin['type'] == 'bit') {
                        $data = myCurl('http://172.31.39.219/mapi/walletadd/generate', ['coin' => $coin, 'username' => username()]);

                        if ($data['status'] === 200) {
                            $qianbao = $data['qianbao'];
                            $rs = Db::name('UserCoin')->where(array('userid' => userid()))->update(array($qbdz => $qianbao));
                            if (!$rs) {
                                $this->error('钱包地址添加出错！');
                            }
                        } else {
                            $this->error($data['message']);
                        }
                    }
                    //地址生成
                    if ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                        if ($Coin['type'] == 'token') {
                            if ($user_coin['ethb']) {
                                $data = ['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $user_coin['ethb']];
                            } else {
                                $data = myCurl('http://172.31.39.219/mapi/walletadd/generate', ['coin' => 'eth', 'username' => username()]);
                                $qianbao = $data['qianbao'];
                                Db::name('UserCoin')->where(array('userid' => userid()))->update(array('ethb' => $qianbao, 'ethp' => md5(username())));
                                $data = ['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $user_coin['ethb']];
                            }
                        } else {
                            $data = myCurl('http://172.31.39.219/mapi/walletadd/generate', ['coin' => $coin, 'username' => username()]);
                        }

                        if ($data['status'] === 200) {
                            $qianbao = $data['qianbao'];
                            $rs = Db::name('UserCoin')->where(array('userid' => userid()))->update(array($qbdz => $qianbao, $coin . 'p' => md5(username())));
                            if (!$rs) {
                                $this->error('钱包地址添加出错！');
                            }
                        } else {
                            $this->error($data['message']);
                        }
                    }
                    //eos地址生成
                    if($Coin['type'] == 'eos'){
                        $qianbao = $Coin['dj_yh'];
                        $coinp =$coin . 'p';
                        for (; true;) {
                            $mome = substr(md5(userid().time()),0,16);
                            if (!Db::name('UserCoin')->where(array($coinp => $mome))->find()) {
                                break;
                            }
                        }
                        $rs = Db::name('UserCoin')->where(array('userid' => userid()))->update(array($qbdz => $qianbao, $coin . 'p' => $mome));
                        $this->assign('mome', $mome);
                        if (!$rs) {
                            $this->error('钱包地址添加出错！');
                        }
                    }
                } else {
                    if ($qbdz == 'vbcb') {
                        $qianbao = "r4uZofeNPTMsZP1aLHR3LAJx1HR6oemWm8";
                        $tishi = '<a style="color: #FD2537;text-decoration: underline;font-weight: bold;font-size: 14px;" target="_blank" href="/Article/type/id/26">
                                点击查看雷达币充值指南(请先到财务中心绑定雷达币钱包再转入)</a>';
                    } else {
                        $qianbao = $user_coin[$coin . 'b'];
                        if ($Coin['type'] == 'eos'){
                            $mome = $user_coin[$coin . 'p'];
                            $this->assign('mome', $mome);
                        }

                    }
                }
            }
        } else {
            if (!$Coin['zr_jz']) {
                $qianbao = '当前币种禁止转入！';
            } else {
                $qianbao = $Coin['weike_coinaddress'];

                $moble = Db::name('User')->where(array('id' => userid()))->value('moble');

                if ($moble) {
                    $moble = substr_replace($moble, '****', 3, 4);
                } else {
                    $this->redirect(url('Home/User/moble'));
                    exit();
                }

                $this->assign('moble', $moble);
            }
        }

        $this->assign('qianbao', $qianbao);
        $this->assign('tishi', isset($tishi)?$tishi:'');
        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = Db::name('Myzr');
        $list = $Moble->where($where)->order('id desc')->paginate(10);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //钱包
    public function qianbao()
    {
        $coin = input('coin/s', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }

        $Coin = Db::name('Coin')->where(array(
            'status' => 1,
            'type' => array('neq', 'rmb')
        ))->select();

        if (!$coin) {
            $coin = "";
        }

        $this->assign('xnb', $coin);

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        $where['status'] = 1;
        if (!empty($coin)) {
            $where['coinname'] = $coin;
        }
        $userQianbaoList = Db::name('UserQianbao')->where($where)->order('id desc')->paginate(15);
        $show = $userQianbaoList->render();

        $this->assign('page', $show);
        $this->assign('userQianbaoList', $userQianbaoList);
        $this->assign('prompt_text', model('Text')->get_content('user_qianbao'));
        return $this->fetch();
    }

    //更新钱包地址
    public function upqianbao()
    {
        $coin = input('coin/s');
        $name = input('name/s');
        $addr = trim(input('addr/s'));
        $paypassword = input('paypassword/s');
        if (!userid()) {
            $this->redirect('/#login');
        }

        if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
            $this->error('出现未知错误！');
        }

        if (!check($name, 'a')) {
            $this->error('备注名称格式错误！');
        }

        if (!check($addr, 'dw')) {
            $this->error('钱包地址格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        $user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('Coin')->where(array('name' => $coin))->find()) {
            $this->error('币种错误！');
        }

        $userQianbao = Db::name('UserQianbao')->where(array('userid' => userid(), 'coinname' => $coin))->select();
        foreach ($userQianbao as $k => $v) {
            if ($v['name'] == $name) {
                $this->error('请不要使用相同的钱包标识！');
            }

            if ($v['addr'] == $addr) {
                $this->error('钱包地址已存在！');
            }
        }

        if (10 <= count($userQianbao)) {
            $this->error('每个人最多只能添加10个地址！');
        }

        if (Db::name('UserQianbao')->insert(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1 ))) {
            $this->success('添加成功！');
        } else {
            $this->error('添加失败！');
        }
    }

    //删除钱包地址
    public function delqianbao()
    {
        $id = input('id/d');
        $paypassword = input('paypassword/s');

        if (!userid()) {
            $this->redirect('/#login');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');
        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('UserQianbao')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error('非法访问！');
        } else if (Db::name('UserQianbao')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //币种转出记录
    public function coinoutlog()
    {
        $coin = input('coin/s', NULL);
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_myzc'));
        if (isset(config('coin')[$coin]) && ! empty(config('coin')[$coin])) {
            $coin = trim($coin);
        } else {
            $coin = config('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin = Db::name('Coin')->where(array(
            'status' => 1,
            'type' => array('neq', 'rmb')
        ))->select();
        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $list = Db::name('Myzc')->where($where)->order('id desc')->paginate(10);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //币种转出
    public function myzc()
    {
        $coin = input('coin/s');

        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_myzc'));

        if (isset(config('coin')[$coin]) && ! empty(config('coin')[$coin])) {
            $coin = trim($coin);
        } else {
            $coin = config('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin = Db::name('Coin')->where(array(
            'status' => 1,
            'type' => array('neq', 'rmb')
        ))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $this->assign('coin_list', $coin_list);
        $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $this->assign('user_coin', $user_coin);

        if ($coin_list[$coin]['zc_jz'] == 0) {
            $this->assign('zc_jz', '当前币种禁止转出！');
        } else {
            $userQianbaoList = Db::name('UserQianbao')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coin))->order('id desc')->select();
            $this->assign('userQianbaoList', $userQianbaoList);
            $moble = Db::name('User')->where(array('id' => userid()))->value('moble');

            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', url('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }

        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = Db::name('Myzc');
        $list = $Moble->where($where)->order('id desc')->paginate(10);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //币种转出
    public function upmyzc()
    {
        if(IS_POST){
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $coin = input('coin/s');
            $num = input('num/f');
            $addr = input('addr/s');
            $memo = input('memo/s');
            $paypassword = input('paypassword/s');
            $moble_verify = input('moble_verify/d');
            $wcgkey = input('wcgkey/s');

            if (!userid()) {
                $this->error('您没有登录请先登录！');
            }

            if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
                $this->error('出现未知错误！');
            }

            if (!check($moble_verify, 'd')) {
                $this->error('手机验证码格式错误！');
            }

            if ($moble_verify != session('myzc_verify')) {
                $this->error('手机验证码错误！');
            }

            $num = abs($num);
            if (!check($num, 'currency')) {
                $this->error('数量格式错误！');
            }

            if (!check($addr, 'dw')) {
                $this->error('钱包地址格式错误！');
            }

            if (!check($paypassword, 'password')) {
                $this->error('交易密码格式错误！');
            }

            if (!check($coin, 'n')) {
                $this->error('币种格式错误！');
            }

            if (!config('coin')[$coin]) {
                $this->error('币种错误！');
            }

            $Coin = Db::name('Coin')->where(array('name' => $coin))->find();
            if (!$Coin) {
                $this->error('币种错误！');
            }

            $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.01);
            $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);
            if ($num < $myzc_min) {
                $this->error('转出数量超过系统最小限制！');
            }
            if ($myzc_max < $num) {
                $this->error('转出数量超过系统最大限制！');
            }

            $user = Db::name('User')->where(array('id' => userid()))->find();
            if (md5($paypassword) != $user['paypassword']) {
                $this->error('交易密码错误！');
            }

            if ($user['idcardauth'] == 0) {
                $this->error('请先进行身份认证！');
            }

            $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
            if ($user_coin[$coin] < $num) {
                $this->error('可用余额不足');
            }


            //收手续费的地址，找到后进行手续费添加
            $qbdz = $coin . 'b';
            $fee_user = Db::name('UserCoin')->where(array($qbdz => $Coin['zc_user']))->find();
            if ($fee_user) {
                $fee = round(($num / 100) * $Coin['zc_fee'], 8);
                $mum = round($num - $fee, 8);

                if ($mum < 0) {
                    $this->error('转出手续费错误！');
                }

                if ($fee < 0) {
                    $this->error('转出手续费设置错误！');
                }
            } else {
                $fee = 0;
                $mum = $num;
            }

            if ($Coin['type'] == 'rgb') {
                Db::startTrans();
                try {
                    $rs = [];
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);

                    if ($fee) {
                        if (Db::table('weike_user_coin')->lock(true)->where(array($qbdz => $Coin['zc_user']))->find()) {
                            $rs[] = Db::table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                        } else {
                            $rs[] = Db::table('weike_user_coin')->insert(array($qbdz => $Coin['zc_user'], $coin => $fee));
                        }
                    }

                    $arr = array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0);

                    if(!empty($wcgkey) && in_array($coin,['wcg','drt','mat'])){
                        $arr['wcgkey'] = $wcgkey;
                    }

                    $rs[] = Db::table('weike_myzc')->insert($arr);
                    if ($fee_user) {
                        $rs[] = Db::table('weike_myzc_fee')->insert(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                    }

                    if (check_arr($rs)) {
                        session('myzc_verify', null);
                        Db::commit();
                        $this->success('转账成功！');
                    } else {
                        Db::rollback();
                        $this->error('转账失败!');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('转账失败!');

                }
            }

            if ($Coin['type'] != 'rmb' && $Coin['type'] != 'rgb') {

                //站内互转
                if ($Coin['type'] == 'eos') {
                    $user_wallet =  Db::name('UserQianbao')->where(array('memo' => $addr))->find();
                    $addr = $user_wallet['addr'];
                    $memo = $user_wallet['memo'];
                }
                if (Db::table('weike_user_coin')->where(array($qbdz => $addr))->find() && $Coin['type'] != 'eos') {
                    $peer = Db::name('UserCoin')->where(array($qbdz => $addr))->find();
                    if (!$peer) {
                        $this->error('转出地址不存在！');
                    }
                    Db::startTrans();
                    try {
                        $rs = [];
                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                        if ($fee) {
                            if (Db::table('weike_user_coin')->lock(true)->where(array($qbdz => $Coin['zc_user']))->find()) {
                                $rs[] = Db::table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                            } else {
                                $rs[] = Db::table('weike_user_coin')->insert(array($qbdz => $Coin['zc_user'], $coin => $fee));
                            }
                        }

                        $rs[] = Db::table('weike_myzc')->insert(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                        $rs[] = Db::table('weike_myzr')->insert(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                        if ($fee_user) {
                            $rs[] = Db::table('weike_myzc_fee')->insert(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                        }

                        if (check_arr($rs)) {
                            session('myzc_verify', null);
                            Db::commit();
                            $this->success('转账成功！');
                        } else {
                            Db::rollback();
                            $this->error('转账失败!');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('转账失败!');
                    }
                } else {
                    $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币
                    Db::startTrans();
                    try {
                        $rs = [];
                        $rs[] = $r = Db::table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                        if ($Coin['type'] == 'eos') {
                            $addr_memo = $addr . ' ' . $memo;
                            $rs[] = $aid = Db::table('weike_myzc')->insert(array('userid' => userid(), 'username' => $addr_memo, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                        } else {
                            $rs[] = $aid = Db::table('weike_myzc')->insert(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                        }

                        if ($fee && $auto_status) {
                            $rs[] = Db::table('weike_myzc_fee')->insert(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                            if (Db::table('weike_user_coin')->lock(true)->where(array($qbdz => $Coin['zc_user']))->find()) {
                                $rs[] = $r = Db::table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                            } else {
                                $rs[] = $r = Db::table('weike_user_coin')->insert(array($qbdz => $Coin['zc_user'], $coin => $fee));
                            }
                        }

                        if (check_arr($rs)) {
                            if ($auto_status) {

                                if ($Coin['type'] == 'bit') {
                                    $data = myCurl('http://172.31.39.219/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);

                                    if ($data['status'] === 200) {
                                        $sendrs = $data['sendrs'];
                                    } else {
                                        Db::rollback();
                                        $this->error($data['message']);
                                    }
                                } elseif ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                                    $data = myCurl('http://172.31.39.219/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);

                                    if ($data['status'] === 200) {
                                        $sendrs = $data['sendrs'];
                                    } else {
                                        Db::rollback();
                                        $this->error($data['message']);
                                    }
                                } elseif ($Coin['type'] == 'eos') {
                                    $data = myCurl('http://172.31.39.219/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num, 'memo' => $memo]);

                                    if ($data['status'] === 200) {
                                        $sendrs = $data['sendrs'];
                                    } else {
                                        Db::rollback();
                                        $this->error($data['message']);
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
                                    Db::rollback();
                                    $this->error('钱包服务器转出币失败,请手动转出');
                                } else {
                                    Db::commit();
                                    $this->success('转出成功!');
                                }
                            }

                            if ($auto_status) {
                                session('myzc_verify', null);
                                Db::commit();
                                $this->success('转出成功!');
                            } else {
                                session('myzc_verify', null);
                                Db::commit();
                                $this->success('转出申请成功,请等待审核！');
                            }
                        } else {
                            Db::rollback();
                            $this->error('转出失败!');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('转出失败!');
                    }
                }
            }
        }
    }

    //钱包币转入
    public function upmyzr()
    {
        if(IS_POST){
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $coin = input('coin/s');
            $weike_dzbz = input('weike_dzbz/s');
            $num = input('num/f');
            $paypassword = input('paypassword/s');
            $moble_verify = input('moble_verify/d');
            $tradeno = input('post.tradeno/s');
            $wcg_qb = input('wcg_qb/s');
            $verify = input('verify/s');
            //$tradeid = input('tradeid/s');
            if (!userid()) {
                $this->error('您没有登录请先登录！');
            }
            if (config('login_verify')) {
                if (!captcha_check($verify,'coin_in')) {
                    $this->error('图形验证码错误!');
                }
            }


            //华克金系列标识地址
            if (in_array($coin,['wcg','drt','mat'])) {
                if (strlen(trim($weike_dzbz)) != 24) {
                    $this->error('标志地址输入有误');
                }
            }else if($coin == 'vbc'){
                if (strlen(trim($weike_dzbz)) != 34) {
                    $this->error('雷达币标志地址输入有误');
                }
            }

            //只能保留两位小数
            if (strpos($num, '.') !== false) {
                if (strlen($num) - (strpos($num, '.') + 1) > 2) {
                    $this->error('小数点后面只能保留两位小数');
                }
            }
            $num = abs($num);

            if (!check($num, 'currency')) {
                $this->error('数量格式错误！');
            }


            //判断转入地址是否正确
            if ($coin == 'wcg') {
                if ($wcg_qb == 'WCG-5YQZ-EEAL-HD9M-CAZH3') {
                    if ($num < 0 || $num >= 500) {
                        $this->error('转入数量错误，请输入0到499.99之间的数');
                    }
                } else if ($wcg_qb == 'WCG-BFLQ-BPFD-RQT7-DM9FS') {
                    if ($num < 500) {
                        $this->error('转入数量错误，请输入500或者500以上的数量');
                    }
                } else {
                    $this->error('转入钱包地址错误');
                }
            }

            //榴莲币
            if ($coin == 'drt') {
                if ($wcg_qb == 'WCG-8W8H-RJQN-PE4L-HFTSM') {
                    if ($num < 0 || $num >= 500) {
                        $this->error('转入数量错误，请输入0到499.99之间的数');
                    }
                } else if ($wcg_qb == 'WCG-D69K-MDAS-S5EW-4J4F8') {
                    if ($num < 500) {
                        $this->error('转入数量错误，请输入500或者500以上的数量');
                    }
                } else {
                    $this->error('转入钱包地址错误');
                }
            }

            //农业通证
            if ($coin == 'mat') {
                if ($wcg_qb == 'WCG-U6DC-79A8-YFHY-A3DL4') {
                    if ($num < 0 || $num >= 50) {
                        $this->error('转入数量错误，请输入0到49.99之间的数');
                    }
                } else if ($wcg_qb == 'WCG-K5KR-39U4-K5R5-BQ8BH') {
                    if ($num < 50) {
                        $this->error('转入数量错误，请输入50或者50以上的数量');
                    }
                } else {
                    $this->error('转入钱包地址错误');
                }
            }

            //雷达币转入地址判断
            if ($coin == 'vbc') {
                if ($wcg_qb != 'r4uZofeNPTMsZP1aLHR3LAJx1HR6oemWm8') {
                    $this->error('转入钱包地址错误');
                }
            }

            if (!check($paypassword, 'password')) {
                $this->error('交易密码格式错误！');
            }

            if (!check($coin, 'n')) {
                $this->error('币种格式错误！');
            }

            if (!isset(config('coin')[$coin]) || !config('coin')[$coin]) {
                $this->error('币种错误！');
            }

            $Coin = Db::name('Coin')->where(array('name' => $coin))->find();

            if (!$Coin) {
                $this->error('币种错误！');
            }


            $user = Db::name('User')->where(array('id' => userid()))->find();

            if (md5($paypassword) != $user['paypassword']) {
                $this->error('交易密码错误！');
            }

            if (Db::name('Myzr')->where(array('userid' => userid(), 'coinname' => $coin, 'tradeno' => $tradeno))->find()) {
                $this->error('请勿重复提交订单！');
            }

            if (Db::name('Myzr')->where(array('userid' => userid(), 'coinname' => $coin, 'status' => 0))->find()) {
                $this->error('您还有未处理的订单！');
            }


            if ($Coin['type'] == 'rgb') {
                Db::startTrans();
                try {
                    Db::table('weike_user')->lock(true)->where(['id' => userid()])->find();//阻塞进行判断
                    $rs = [];
                    //判断输入识别码有没有重复
                    if (in_array($coin,['wcg','drt','mat'])) {
                        $tradeno_v = Db::table('weike_myzr')->where(['tradeno' => $tradeno])->find();
                        if ($tradeno_v) {
                            Db::rollback();
                            $this->error('不可以重复提交订单');
                        }

                        $rs[] = Db::name('myzr')->insert(array('userid' => userid(), 'username' => $weike_dzbz, 'txid' => time(), 'coinname' => $coin, 'num' => $num, 'mum' => 0, 'addtime' => time(), 'status' => 0, 'tradeno' => $tradeno));
                    } else {

                        $rs[] = Db::name('myzr')->insert(array('userid' => userid(), 'username' => $weike_dzbz, 'txid' => time(), 'coinname' => $coin, 'num' => $num, 'mum' => 0, 'addtime' => time(), 'status' => 0));
                    }
                    if (check_arr($rs)) {
                        Db::commit();
                        $this->success('转入申请成功,等待客服处理！', '/Activity/wcgzr', 2);

                    } else {
                        Db::rollback();
                        $this->error('操作错误');
                    }

                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('操作错误');
                }

            } else {
                $this->error("钱包币不允许该操作!", '/Activity/wcgzr', 2);
            }

        }
    }

    //委托管理
    public function mywt()
    {
        $market = input('market/s', NULL);
        $type = input('type/d', NULL);
        $status = input('status/d', NULL);

        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mywt'));
        $Coin = Db::name('Coin')->where(array('status' => 1))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = Db::name('Market')->where(array('status' => 1))->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!(array_key_exists($market,$market_list)) || !$market_list[$market]) {
            $market = $Market[0]['name'];
        }

        $where['market'] = $market;
        if (($type == 1) || ($type == 2)) {
            $where['type'] = $type;
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $where['status'] = $status - 1;
        }

        $where['userid'] = userid();
        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $list = Db::name('Trade')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){

            $item['num'] = $item['num'] * 1;
            $item['price'] = $item['price'] * 1;
            $item['deal'] = $item['deal'] * 1;
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //成交查询
    public function mycj()
    {
        $market = input('market/s', NULL);
        $type = input('type/d', NULL);

        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mycj'));
        $Coin = Db::name('Coin')->where(array('status' => 1))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = Db::name('Market')->where(array('status' => 1))->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!(array_key_exists($market,$market_list)) || !$market_list[$market]) {
            $market = $Market[0]['name'];
        }

        if ($type == 1) {
            $where = ['userid' => userid(), 'market' => $market];
        } else if ($type == 2) {
            $where = ['peerid' => userid(), 'market' => $market];
        } else {
            $where = [
                'userid|peerid' =>userid(),
                'market' => $market
            ];

        }

        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('userid', userid());
        $list = Db::name('TradeLog')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){

            $item['num'] = $item['num'] * 1;
            $item['price'] = $item['price'] * 1;
            $item['mum'] = $item['mum'] * 1;
            $item['fee_buy'] = $item['fee_buy'] * 1;
            $item['fee_sell'] = $item['fee_sell'] * 1;

            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //邀请好友
    public function mytj()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mytj'));
        $user = Db::name('User')->where(array('id' => userid()))->find();

        if (!$user['invit']) {
            for (; true;) {
                $tradeno = tradenoa();

                if (!Db::name('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }

            Db::name('User')->where(array('id' => userid()))->update(array('invit' => $tradeno));
            $user = Db::name('User')->where(array('id' => userid()))->find();
        }

        $this->assign('user', $user);
        return $this->fetch();
    }

    //我的推荐
    public function mywd()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_mywd'));
        $where['invit_1'] = userid();
        $list = Db::name('User')->where(['invit'=>userid()])->order('id asc')->field('id,username,moble,addtime,invit_1')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
            $item['invits'] = Db::name('User')->where(array('invit_1' => $item['id']))->order('id asc')->field('id,username,moble,addtime,invit_1')->select();
            $item['invitss'] = count($item['invits']);

            foreach ($item['invits'] as $kk => $vv) {
                $item['invits'][$kk]['invits'] = Db::name('User')->where(array('invit_1' => $vv['id']))->order('id asc')->field('id,username,moble,addtime,invit_1')->select();
                $item['invits'][$kk]['invitss'] = count($item['invits'][$kk]['invits']);
            }
            return $item;
        });

        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //我的奖励
    public function myjp()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('finance_myjp'));

        $where['userid'] = userid();
        $list = Db::name('Invit')->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
            $item['invit'] = Db::name('User')->where(array('id' => $item['invit']))->value('username');
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
}

?>