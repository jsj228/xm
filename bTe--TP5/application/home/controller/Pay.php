<?php

namespace app\home\controller;

use think\Db;
use think\Exception;

class Pay extends Home
{
    public function index()
    {
        if (IS_POST) {
            if (isset($_POST['alipay'])) {
                $alipay = input('alipay/s');
                $arr = explode('--', $alipay);

                if (md5('weike') != $arr[2]) {
                    echo -1;
                    exit();
                }

                if (!strstr($arr[0], 'Pay')) {
                }

                $arr[0] = trim(str_replace(PHP_EOL, '', $arr[0]));
                $arr[1] = trim(str_replace(PHP_EOL, '', $arr[1]));

                if (strstr($arr[0], '付款-')) {
                    $arr[0] = str_replace('付款-', '', $arr[0]);
                }

                $mycz = Db::name('Mycz')->where(array('tradeno' => $arr[0]))->find();

                if (!$mycz) {
                    echo -3;
                    exit();
                }

                if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
                    echo -4;
                    exit();
                }

                if ($mycz['num'] != $arr[1]) {
                    echo -5;
                    exit();
                }

                Db::startTrans();
                try {
                    
                    $rs = [];
                    $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $mycz['userid']))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $mycz['userid']))->find();
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('cny', $mycz['num']);
                    $rs[] = Db::table('weike_mycz')->where(array('id' => $mycz['id']))->update(array('status' => 1, 'mum' => $mycz['num'], 'endtime' => time()));
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $mycz['userid']))->find();
                    $finance_hash = md5($mycz['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mycz['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    $rs[] = Db::table('weike_finance')->insert(array('userid' => $mycz['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '人民币充值-人工到账', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                    if (check_arr($rs)) {
                        Db::commit();
                        echo 1;
                        exit();
                    } else {
                        Db::rollback();
                        echo -6;
                        exit();
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    echo -6;
                    exit();

                }
            }
        }
    }

    public function movepay()
    {
        if (IS_POST) {
            $movepay = input('movepay/s');
            $tradeno = input('tradeno/s');
            $num = input('num/s');

            if (md5('weike') != $movepay) {
                echo -1;
                exit();
            }

            $mycz = Db::name('Mycz')->where(array('tradeno' => $tradeno))->find();

            if (!$mycz) {
                echo -2;
                exit();
            }

            if ($mycz['status']) {
                echo -3;
                exit();
            }

            if ($mycz['num'] != $num) {
                echo -4;
                exit();
            }

            Db::startTrans();
            try {

                $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('cny', $mycz['num']);
                $rs[] = Db::table('weike_mycz')->where(array('id' => $mycz['id']))->update(array('status' => 1, 'mum' => $mycz['num'], 'endtime' => time()));

                if (check_arr($rs)) {
                    Db::commit();
                    $this->redirect('Mycz/log');
                    exit();
                } else {
                    Db::rollback();
                    echo -5;
                    exit();
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                echo -5;
                exit();
            }
        }
    }

    public function mycz()
    {
        $id = input('id/d', NULL);

        if ($id) {
            $mycz = Db::name('Mycz')->where(array('id' => $id))->find();
            if (!$mycz) {
                $this->redirect('Finance/mycz');
            }
            if ($mycz['userid'] != userid()) {
                $this->redirect('Finance/mycz');
            }

            if ($mycz['type'] == 'alipay') {
                if ('' == $mycz['alipay_id']) {
                    $new = Db::name('MyczType')->where(['name' => 'alipay', 'status' => 1])->select();
                    $num = $new[array_rand($new)]['id'];
                    $myczType = Db::name('MyczType')->where(['id' => $num])->find();
                    Db::name('Mycz')->where(['id' => $id])->update(['alipay_id' => $num]);
                } else {
                    $myczType = Db::name('MyczType')->where(array('id' => $mycz['alipay_id']))->find();
                }

            } else {
                $myczType = Db::name('MyczType')->where(array('name' => $mycz['type']))->find();
            }
            if ($mycz['type'] == 'bank') {
                $UserBankType = Db::name('UserBankType')->where(array('status' => 1))->order('id desc')->select();
                $this->assign('UserBankType', $UserBankType);
            }
            $this->assign('myczType', $myczType);
            $this->assign('mycz', $mycz);
            $this->display($mycz['type']);
        } else {
            $this->redirect('Finance/mycz');
        }
    }

    public function ecpss()
    {
        $id = input('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (empty($id)) {
            $this->error('参数错误！');
        }

        $mycz = Db::name('Mycz')->where(array('id' => $id))->find();

        if (!$mycz) {
            $this->error('订单不存在！');
        }

        if ($mycz['userid'] != userid()) {
            $this->error('参数非法！');
        }

        $this->error('订单不存在！');
    }
}

?>