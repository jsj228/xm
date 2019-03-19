<?php


namespace app\mapi\controller;


class Recharge extends Mapi {
    //Amazon operate 外网 IP 地址
    protected $ip = ['34.214.116.151'];

    //Guo 接口操作充值
    private function index()
    {
        $this->auth();
        echo 'here';
    }

    //login  error : 300
    private function login()
    {
        $this->auth('post');

        $email = input('email/s');
        $password = input('password/s');
        $admin = null;

        if (check($email, 'email')) {
            $admin = db('Admin')->where(['email' => $email])->find();
        }

        if (!$admin) {
            $admin = db('Admin')->where(['username' => $email])->find();
        }

        if (!$admin) {
            $this->json(['status' => 300, 'message' => '用户不存在！']);
        }

        if (!check($password, 'password')) {
            $this->json(['status' => 301, 'message' => '登录密码格式错误！']);
        }

        if (md5($password) != $admin['password']) {
            $this->json(['status' => 302, 'message' => '登录密码错误！']);
        }

        if ($admin['status'] != 1) {
            $this->json(['status' => 303, 'message' => '你的账号已冻结请联系管理员！']);
        }

        $this->json(['status' => 200, 'message' => '登陆成功！', 'id' => $admin['id']]);
    }

    //recharge list and page
    private function recharge()
    {
        $this->auth();

        //列表筛选条件参数
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        //分页条件参数
        $num = input('num/d', 15);
        $start = input('start/d', 0);

        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = db('User')->where(['username' => $name])->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = db('Mycz')->where($where)->count();
        $list = db('Mycz')->where($where)->order('id desc')->limit($start . ',' . $num)->select();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = db('User')->where(['id' => $v['userid']])->value('username');
            if ($v['type'] === 'overseas'){
                $list[$k]['type'] = '海外支付宝支付';
            } else {
                $list[$k]['type'] = db('MyczType')->where(['name' => $v['type']])->value('title');
            }
        }

        $this->json(['status' => 200, 'message' => '列表获取成功！',  'count' => $count, 'list' => $list]);
    }

    //rechargeUp
    private function rechargeUp()
    {
        $this->auth('post');

        $id = input('id/d');
        $type = input('type/d', 1); //1 人工到账 2 花呗到账

        if (empty($id)) {
            $this->json(['status' => 501, 'message' => '请选择要操作的数据！']);
        }

        $mycz = db('Mycz')->where(['id' => $id])->find();
        if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
            $this->json(['status' => 502, 'message' => '已经处理，禁止再次操作！']);
        }

        //充值判断
        if ($type === 2) {
            $dzjine = db('MyczType')->where(['name' => $mycz['type']])->value('sxfei');
            $dzjine = round($mycz['num'] * (1 - $dzjine), 2);
            $status = 5;
        } elseif ($type === 1) {
            $dzjine = $mycz['num'];
            $status = 2;
        } else {
            $this->json(['status' => 503, 'message' => '到账类型错误！']);
        }

        $mo = db();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_user_coin write,weike_mycz write,weike_finance write,weike_invit write,weike_user write');
        $rs = [];
        $finance = $mo->table('weike_finance')->where(['userid' => $mycz['userid']])->order('id desc')->find();
        $finance_num_user_coin = $mo->table('weike_user_coin')->where(['userid' => $mycz['userid']])->find();
        $rs[] = $mo->table('weike_user_coin')->where(['userid' => $mycz['userid']])->setInc('hkd', $dzjine);
        $rs[] = $mo->table('weike_mycz')->where(['id' => $mycz['id']])->update(['status' => $status, 'mum' => $dzjine, 'endtime' => time()]);
        $finance_mum_user_coin = $mo->table('weike_user_coin')->where(['userid' => $mycz['userid']])->find();
        $finance_hash = md5($mycz['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mycz['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('weike_finance')->insert(['userid' => $mycz['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '港币充值-人工到账', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
        $cz_mes = "成功充值[" . $dzjine . "]元.";

        $cur_user_info = $mo->table('weike_user')->where(['id' => $mycz['userid']])->find();
        //invit_1  invit_2  invit_3  以mum为准  为到账金额
        //推广佣金，一次推广，终身拿佣金    奖励下线充值金额的0.6%三级分红。    一代0.3%      二代0.2%      三代0.1%
        $cz_jiner = $mycz['num'];
        if ($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0 && 1 == 2) {
            //存在一级推广人
            $invit_1_jiner = round(($cz_jiner / 100) * 0.3, 6);

            if ($invit_1_jiner) {
                //处理前信息
                $finance_1 = $mo->table('weike_finance')->where(['userid' => $cur_user_info['invit_1']])->order('id desc')->find();
                $finance_num_user_coin_1 = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_1']])->find();

                //开始处理
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_1']])->setInc('hkd', $invit_1_jiner);
                $rs[] = $mo->table('weike_invit')->insert(['userid' => $cur_user_info['invit_1'], 'invit' => $mycz['userid'], 'name' => 'hkd', 'type' => '一代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_1_jiner, 'addtime' => time(), 'status' => 1]);

                //处理后
                $finance_mum_user_coin_1 = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_1']])->find();
                $finance_hash_1 = md5($cur_user_info['invit_1'] . $finance_num_user_coin_1['hkd'] . $finance_num_user_coin_1['hkdd'] . $invit_1_jiner . $finance_mum_user_coin_1['hkd'] . $finance_mum_user_coin_1['hkdd'] . MSCODE . 'auth.weike.com');
                $finance_num_1 = $finance_num_user_coin_1['hkd'] + $finance_num_user_coin_1['hkdd'];

                if ($finance_1['mum'] < $finance_num_1) {
                    $finance_status_1 = (1 < ($finance_num_1 - $finance_1['mum']) ? 0 : 1);
                } else {
                    $finance_status_1 = (1 < ($finance_1['mum'] - $finance_num_1) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->insert(['userid' => $cur_user_info['invit_1'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin_1['hkd'], 'num_b' => $finance_num_user_coin_1['hkdd'], 'num' => $finance_num_user_coin_1['hkd'] + $finance_num_user_coin_1['hkdd'], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => '港币充值-一代充值奖励-充值ID' . $mycz['userid'] . ',订单' . $mycz['tradeno'] . ',金额' . $cz_jiner . '元,奖励' . $invit_1_jiner . '元', 'mum_a' => $finance_mum_user_coin_1['hkd'], 'mum_b' => $finance_mum_user_coin_1['hkdd'], 'mum' => $finance_mum_user_coin_1['hkd'] + $finance_mum_user_coin_1['hkdd'], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1]);

                //处理结束提示信息
                $cz_mes = $cz_mes . "一代推荐奖励[" . $invit_1_jiner . "]元.";
            }
        }

        if ($cur_user_info['invit_2'] && $cur_user_info['invit_2'] > 0 && 1 == 2) {
            //存在二级推广人
            $invit_2_jiner = round(($cz_jiner / 100) * 0.2, 6);
            if ($invit_2_jiner) {

                //处理前信息
                $finance_2 = $mo->table('weike_finance')->where(['userid' => $cur_user_info['invit_2']])->order('id desc')->find();
                $finance_num_user_coin_2 = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_2']])->find();

                //开始处理

                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_2']])->setInc('hkd', $invit_2_jiner);
                $rs[] = $mo->table('weike_invit')->insert(['userid' => $cur_user_info['invit_2'], 'invit' => $mycz['userid'], 'name' => 'hkd', 'type' => '二代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_2_jiner, 'addtime' => time(), 'status' => 1]);

                //处理后
                $finance_mum_user_coin_2 = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_2']])->find();
                $finance_hash_2 = md5($cur_user_info['invit_2'] . $finance_num_user_coin_2['hkd'] . $finance_num_user_coin_2['hkdd'] . $invit_2_jiner . $finance_mum_user_coin_2['hkd'] . $finance_mum_user_coin_2['hkdd'] . MSCODE . 'auth.weike.com');
                $finance_num_2 = $finance_num_user_coin_2['hkd'] + $finance_num_user_coin_2['hkdd'];

                if ($finance_2['mum'] < $finance_num_2) {
                    $finance_status_2 = (1 < ($finance_num_2 - $finance_2['mum']) ? 0 : 1);
                } else {
                    $finance_status_2 = (1 < ($finance_2['mum'] - $finance_num_2) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->insert(['userid' => $cur_user_info['invit_2'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin_2['hkd'], 'num_b' => $finance_num_user_coin_2['hkdd'], 'num' => $finance_num_user_coin_2['hkd'] + $finance_num_user_coin_2['hkdd'], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => '港币充值-二代充值奖励-充值ID' . $mycz['userid'] . ',订单' . $mycz['tradeno'] . ',金额' . $cz_jiner . '元,奖励' . $invit_2_jiner . '元', 'mum_a' => $finance_mum_user_coin_2['hkd'], 'mum_b' => $finance_mum_user_coin_2['hkdd'], 'mum' => $finance_mum_user_coin_2['hkd'] + $finance_mum_user_coin_2['hkdd'], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2]);

                //处理结束提示信息
                $cz_mes = $cz_mes . "二代推荐奖励[" . $invit_2_jiner . "]元.";
            }
        }

        if ($cur_user_info['invit_3'] && $cur_user_info['invit_3'] > 0 && 1 == 2) {
            //存在三级推广人
            $invit_3_jiner = round(($cz_jiner / 100) * 0.1, 6);
            if ($invit_3_jiner) {

                //处理前信息
                $finance_3 = $mo->table('weike_finance')->where(['userid' => $cur_user_info['invit_3']])->order('id desc')->find();
                $finance_num_user_coin_3 = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_3']])->find();

                //开始处理
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_3']])->setInc('hkd', $invit_3_jiner);
                $rs[] = $mo->table('weike_invit')->insert(['userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => 'hkd', 'type' => '三代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1]);

                //处理后
                $finance_mum_user_coin_3 = $mo->table('weike_user_coin')->where(['userid' => $cur_user_info['invit_3']])->find();
                $finance_hash_3 = md5($cur_user_info['invit_3'] . $finance_num_user_coin_3['hkd'] . $finance_num_user_coin_3['hkdd'] . $invit_3_jiner . $finance_mum_user_coin_3['hkd'] . $finance_mum_user_coin_3['hkdd'] . MSCODE . 'auth.weike.com');
                $finance_num_3 = $finance_num_user_coin_3['hkd'] + $finance_num_user_coin_3['hkdd'];

                if ($finance_3['mum'] < $finance_num_3) {
                    $finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
                } else {
                    $finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->insert(['userid' => $cur_user_info['invit_3'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin_3['hkd'], 'num_b' => $finance_num_user_coin_3['hkdd'], 'num' => $finance_num_user_coin_3['hkd'] + $finance_num_user_coin_3['hkdd'], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => '港币充值-三代充值奖励-充值ID' . $mycz['userid'] . ',订单' . $mycz['tradeno'] . ',金额' . $cz_jiner . '元,奖励' . $invit_3_jiner . '元', 'mum_a' => $finance_mum_user_coin_3['hkd'], 'mum_b' => $finance_mum_user_coin_3['hkdd'], 'mum' => $finance_mum_user_coin_3['hkd'] + $finance_mum_user_coin_3['hkdd'], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3]);

                //处理结束提示信息
                $cz_mes = $cz_mes . "三代推荐奖励[" . $invit_3_jiner . "]元.";
            }
        }

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->json(['status' => 200, 'message' => '操作成功！']);
        } else {
            $mo->execute('rollback');
            $this->json(['status' => 504, 'message' => '操作失败！']);
        }
    }
}