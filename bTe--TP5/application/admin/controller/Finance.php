<?php

namespace app\admin\controller;

use think\Db;
use think\Exception;

class Finance extends Admin
{
    public function index()
    {
        $name = input('name/s');
        $field = input('field/s');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }


        $name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购', 'c2c' => '点对点');
        $nameid_list = array('mycz' => url('Mycz/index'), 'mytx' => url('Mytx/index'), 'trade' => url('Trade/index'), 'tradelog' => url('Tradelog/index'), 'issue' => url('Issue/index'), 'c2c' => url('Trade/c2cTrade'));

        $list = Db::name('Finance')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key) {
            global $name_list;
            global $nameid_list;
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['num_a'] = Num($item['num_a']);
            $item['num_b'] = Num($item['num_b']);
            $item['num'] = Num($item['num']);
            $item['fee'] = Num($item['fee']);
            $item['type'] = ($item['type'] == 1 ? '收入' : '支出');
            $item['name'] = ($name_list[$item['name']] ? $name_list[$item['name']] : $item['name']);
            $item['nameid'] = ($name_list[$item['name']] ? $nameid_list[$item['name']] . '?id=' . $item['nameid'] : '');
            $item['mum_a'] = Num($item['mum_a']);
            $item['mum_b'] = Num($item['mum_b']);
            $item['mum'] = Num($item['mum']);
            $item['addtime'] = addtime($item['addtime']);
            return $item;
        });
        $show = $list->render();



        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function mycz()
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));
        $payment = input('payment/s');
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }
        if ($payment) {
            $where['type'] = $payment;
        }

        $count = Db::name('Mycz')->where($where)->count();
        $weike_sum = Db::name('Mycz')->where($where)->sum('num');
        $weike_num = Db::name('Mycz')->where($where)->sum('mum');

        $list = Db::name('Mycz')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            if ($item['type'] === 'overseas') {
                $item['type'] = '海外支付宝支付';
            } else {
                $item['type'] = Db::name('MyczType')->where(array('name' => $item['type']))->value('title');
            }
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('weike_count', $count);
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
        return $this->fetch();
    }

    public function myczStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'Mycz');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'chexiao':
                $data = array('status' => 4);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败1！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败2！');
        }
    }

    public function myzrQueren()
    {
        $id = input('id/d');
        $type = input('type/d');
        $tradeid = input('tradeid/s');

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $myzr = Db::name('Myzr')->where(array('id' => input('id/d')))->find();
        if (($myzr['status'] != 0)) {
            $this->error('已经处理，禁止再次操作！');
        }

        //华克金，交易 ID 判断
        if ($myzr['coinname'] === 'wcg' && $type === 1) {
            if ($tradeid !== $myzr['tradeid']) {
                $this->error('请输入正确的交易ID！');
            }
        }

        if ($type === 1) {
            
            Db::startTrans();
            try{
                $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(['userid' => $myzr['userid']])->setInc($myzr['coinname'], $myzr['num']);
                $rs[] = Db::table('weike_myzr')->where(['id' => $myzr['id']])->update(['status' => 1, 'mum' => $myzr['num'], 'endtime' => time(), 'czr' => session('admin_username')]);

                if (check_arr($rs)) {
                    Db::commit();
                    $this->success('处理成功');
                } else {
                    Db::rollback();
                    $this->error('操作失败！');
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('操作失败！');
            }
           
           
        } elseif ($type === 2) {
            // 非 BOSS 不能撤销
//            if (session('admin_id') != 11) {
//                $this->error('非 BOSS 不能撤销！');
//            }
            
            $rs = Db::table('weike_myzr')->where(['id' => $myzr['id']])->update(['status' => 2, 'endtime' => time(), 'czr' => session('admin_username')]);
            if (false !== $rs) {
                $this->success('处理成功');
            } else {
                $this->error('操作失败！');
            }
        }
    }

    //人工充值， 花呗充值
    public function myczQueren()
    {
        $id = input('id/d');
        $type = input('type/d');
        $text = input('text/s');

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $mycz = Db::name('Mycz')->where(array('id' => $id))->find();
        if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
            $this->error('已经处理，禁止再次操作！');
        }

        if ($text !== $mycz['tradeno']) {
            $this->error('请输入正确的订单号！');
        }

        //充值判断
        if ($type === 2) {
            $dzjine = Db::name('MyczType')->where(['name' => $mycz['type']])->value('sxfei');
            $dzjine = round($mycz['num'] * (1 - $dzjine), 2);
            $status = 5;
        } else {
            $dzjine = $mycz['num'];
            $status = 2;
        }
        
        Db::startTrans();
        try{

            $rs = [];
            $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $mycz['userid']))->order('id desc')->find();
            $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $mycz['userid']))->find();
            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('hkd', $dzjine);
            $rs[] = Db::table('weike_mycz')->where(array('id' => $mycz['id']))->update(array('status' => $status, 'mum' => $dzjine, 'endtime' => time(), 'czr' => session('admin_username')));
            $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $mycz['userid']))->find();
            $finance_hash = md5($mycz['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mycz['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
            $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = Db::table('weike_finance')->insert(array('userid' => $mycz['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '港币充值-人工到账', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

            $cz_mes = "成功充值[" . $dzjine . "]元.";

            $cur_user_info = Db::table('weike_user')->where(array('id' => $mycz['userid']))->find();
            //invit_1  invit_2  invit_3  以mum为准  为到账金额
            //推广佣金，一次推广，终身拿佣金    奖励下线充值金额的0.6%三级分红。    一代0.3%      二代0.2%      三代0.1%
            $cz_jiner = $mycz['num'];
            if ($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0 && 1 == 2) {
                //存在一级推广人
                $invit_1_jiner = round(($cz_jiner / 100) * 0.3, 6);

                if ($invit_1_jiner) {
                    //处理前信息
                    $finance_1 = Db::table('weike_finance')->where(array('userid' => $cur_user_info['invit_1']))->order('id desc')->find();
                    $finance_num_user_coin_1 = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();

                    //开始处理
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->setInc('hkd', $invit_1_jiner);
                    $rs[] = Db::table('weike_invit')->insert(array('userid' => $cur_user_info['invit_1'], 'invit' => $mycz['userid'], 'name' => 'hkd', 'type' => '一代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_1_jiner, 'addtime' => time(), 'status' => 1));

                    //处理后
                    $finance_mum_user_coin_1 = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();
                    $finance_hash_1 = md5($cur_user_info['invit_1'] . $finance_num_user_coin_1['hkd'] . $finance_num_user_coin_1['hkdd'] . $invit_1_jiner . $finance_mum_user_coin_1['hkd'] . $finance_mum_user_coin_1['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num_1 = $finance_num_user_coin_1['hkd'] + $finance_num_user_coin_1['hkdd'];

                    if ($finance_1['mum'] < $finance_num_1) {
                        $finance_status_1 = (1 < ($finance_num_1 - $finance_1['mum']) ? 0 : 1);
                    } else {
                        $finance_status_1 = (1 < ($finance_1['mum'] - $finance_num_1) ? 0 : 1);
                    }

                    $rs[] = Db::table('weike_finance')->insert(array('userid' => $cur_user_info['invit_1'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin_1['hkd'], 'num_b' => $finance_num_user_coin_1['hkdd'], 'num' => $finance_num_user_coin_1['hkd'] + $finance_num_user_coin_1['hkdd'], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => '港币充值-一代充值奖励-充值ID' . $mycz['userid'] . ',订单' . $mycz['tradeno'] . ',金额' . $cz_jiner . '元,奖励' . $invit_1_jiner . '元', 'mum_a' => $finance_mum_user_coin_1['hkd'], 'mum_b' => $finance_mum_user_coin_1['hkdd'], 'mum' => $finance_mum_user_coin_1['hkd'] + $finance_mum_user_coin_1['hkdd'], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1));

                    //处理结束提示信息
                    $cz_mes = $cz_mes . "一代推荐奖励[" . $invit_1_jiner . "]元.";
                }
            }

            if ($cur_user_info['invit_2'] && $cur_user_info['invit_2'] > 0 && 1 == 2) {
                //存在二级推广人
                $invit_2_jiner = round(($cz_jiner / 100) * 0.2, 6);
                if ($invit_2_jiner) {

                    //处理前信息
                    $finance_2 = Db::table('weike_finance')->where(array('userid' => $cur_user_info['invit_2']))->order('id desc')->find();
                    $finance_num_user_coin_2 = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();

                    //开始处理

                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->setInc('hkd', $invit_2_jiner);
                    $rs[] = Db::table('weike_invit')->insert(array('userid' => $cur_user_info['invit_2'], 'invit' => $mycz['userid'], 'name' => 'hkd', 'type' => '二代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_2_jiner, 'addtime' => time(), 'status' => 1));

                    //处理后
                    $finance_mum_user_coin_2 = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();
                    $finance_hash_2 = md5($cur_user_info['invit_2'] . $finance_num_user_coin_2['hkd'] . $finance_num_user_coin_2['hkdd'] . $invit_2_jiner . $finance_mum_user_coin_2['hkd'] . $finance_mum_user_coin_2['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num_2 = $finance_num_user_coin_2['hkd'] + $finance_num_user_coin_2['hkdd'];

                    if ($finance_2['mum'] < $finance_num_2) {
                        $finance_status_2 = (1 < ($finance_num_2 - $finance_2['mum']) ? 0 : 1);
                    } else {
                        $finance_status_2 = (1 < ($finance_2['mum'] - $finance_num_2) ? 0 : 1);
                    }

                    $rs[] = Db::table('weike_finance')->insert(array('userid' => $cur_user_info['invit_2'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin_2['hkd'], 'num_b' => $finance_num_user_coin_2['hkdd'], 'num' => $finance_num_user_coin_2['hkd'] + $finance_num_user_coin_2['hkdd'], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => '港币充值-二代充值奖励-充值ID' . $mycz['userid'] . ',订单' . $mycz['tradeno'] . ',金额' . $cz_jiner . '元,奖励' . $invit_2_jiner . '元', 'mum_a' => $finance_mum_user_coin_2['hkd'], 'mum_b' => $finance_mum_user_coin_2['hkdd'], 'mum' => $finance_mum_user_coin_2['hkd'] + $finance_mum_user_coin_2['hkdd'], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2));

                    //处理结束提示信息
                    $cz_mes = $cz_mes . "二代推荐奖励[" . $invit_2_jiner . "]元.";
                }
            }

            if ($cur_user_info['invit_3'] && $cur_user_info['invit_3'] > 0 && 1 == 2) {
                //存在三级推广人
                $invit_3_jiner = round(($cz_jiner / 100) * 0.1, 6);
                if ($invit_3_jiner) {

                    //处理前信息
                    $finance_3 = Db::table('weike_finance')->where(array('userid' => $cur_user_info['invit_3']))->order('id desc')->find();
                    $finance_num_user_coin_3 = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();

                    //开始处理
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->setInc('hkd', $invit_3_jiner);
                    $rs[] = Db::table('weike_invit')->insert(array('userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => 'hkd', 'type' => '三代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1));

                    //处理后
                    $finance_mum_user_coin_3 = Db::table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();
                    $finance_hash_3 = md5($cur_user_info['invit_3'] . $finance_num_user_coin_3['hkd'] . $finance_num_user_coin_3['hkdd'] . $invit_3_jiner . $finance_mum_user_coin_3['hkd'] . $finance_mum_user_coin_3['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num_3 = $finance_num_user_coin_3['hkd'] + $finance_num_user_coin_3['hkdd'];

                    if ($finance_3['mum'] < $finance_num_3) {
                        $finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
                    } else {
                        $finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
                    }

                    $rs[] = Db::table('weike_finance')->insert(array('userid' => $cur_user_info['invit_3'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin_3['hkd'], 'num_b' => $finance_num_user_coin_3['hkdd'], 'num' => $finance_num_user_coin_3['hkd'] + $finance_num_user_coin_3['hkdd'], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => '港币充值-三代充值奖励-充值ID' . $mycz['userid'] . ',订单' . $mycz['tradeno'] . ',金额' . $cz_jiner . '元,奖励' . $invit_3_jiner . '元', 'mum_a' => $finance_mum_user_coin_3['hkd'], 'mum_b' => $finance_mum_user_coin_3['hkdd'], 'mum' => $finance_mum_user_coin_3['hkd'] + $finance_mum_user_coin_3['hkdd'], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3));

                    //处理结束提示信息
                    $cz_mes = $cz_mes . "三代推荐奖励[" . $invit_3_jiner . "]元.";
                }

            }

            if (check_arr($rs)) {
                Db::commit();
                $this->success($cz_mes);
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

    //客户对充值订单备注
    public function czbz()
    {
        $id = input('post.id/d');
        $type = input('post.type/s');
        $text = input('post.text/s');
        if (empty($id)) {
            die(json_encode(array('code' => 401, 'msg' => '请选择要操作的数据!', 'data' => [])));
        }
        if (empty($text)) {
            die(json_encode(array('code' => 402, 'msg' => '请填写备注', 'data' => [])));
        }
        if (!$type) {
            die(json_encode(array('code' => 403, 'msg' => '请正确操作', 'data' => [])));
        }
        if ($type == 'czbz') {
            $data = Db::name('Mycz')->where(['id' => $id])->update(['beizhu' => $text]);
        } else {
            $data = Db::name('Myzr')->where(['id' => $id])->update(['beizhu' => $text]);
        }

        if ($data) {
            die(json_encode(array('code' => 200, 'msg' => '备注成功', 'data' => [])));
        } else {
            die(json_encode(array('code' => 403, 'msg' => '备注失败', 'data' => [])));
        }
    }

    public function myczType()
    {
        $where = array();
        $list = Db::name('MyczType')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function myczTypeEdit($id = NULL)
    {
        $id = input('id/d');
        $_POST = input('post.');
        if (empty($_POST)) {
            if ($id) {
                $this->data = Db::name('MyczType')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            if (!empty($_POST['id'])) {
                $rs = Db::name('MyczType')->update($_POST);
            } else {
                $rs = Db::name('MyczType')->insert($_POST);
            }

            if (false !== $rs) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }
        }
    }

    public function myczTypeImage()
    {
        if ($_FILES['upload_file0']['size'] > 3145728) {
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/public/';
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path . $filename, $_FILES['upload_file0']['tmp_name']);

        if (!$info) {
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

    public function myczTypeStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'MyczType');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::table($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败1！');
        }

        if (Db::table($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败2！');
        }
    }

    public function mytx()
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        $mcid = input('mcid/d');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($mcid) {
            $where['mcid'] = $mcid;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }
        $count = Db::name('Mytx')->where($where)->count();
        $weike_sum = Db::name('Mytx')->where($where)->sum('num');
        $weike_num = Db::name('Mytx')->where($where)->sum('mum');

        $list = Db::name('Mytx')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' =>$item['userid']))->value('username');
            $item['truename'] = Db::name('User')->where(array('id' => $item['userid']))->value('truename');
            $item['merchant'] = Db::name('MytxMerchant')->where(array('id' => $item['mcid']))->value('name');
            return $item;
        });
        $show = $list->render();



        //获取体现商户列表
        $merchants = Db::name('MytxMerchant')->where(['status' => 1])->select();
        $this->assign('merchants', $merchants);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_count', $count);
        return $this->fetch();
    }

    public function mytxStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'Mytx');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::table($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败1！');
        }

        if (Db::table($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败2！');
        }
    }

    public function mytxChuli()
    {
        $id = input('id/d');

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        if (Db::name('Mytx')->where(array('id' => $id))->update(['endtime' => time(), 'status' => 3, 'operator' => session('admin_username')])) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function mytxChexiao()
    {
        $id = input('id/d');

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        Db::startTrans();
        try{
            $mytx = Db::name('Mytx')->lock(true)->where(array('id' => trim($id)))->find();
            $rs = [];
            $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $mytx['userid']))->order('id desc')->find();
            $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $mytx['userid']))->find();
            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $mytx['userid']))->setInc('hkd', $mytx['num']);
            $rs[] = Db::table('weike_mytx')->where(array('id' => $mytx['id']))->update(['endtime' => time(), 'status' => 2, 'operator' => session('admin_username')]);
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

    public function mytxQueren()
    {
        $id = input('id/d');
        $mcid = input('mcid/d');

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $rs =Db::name('Mytx')->where(array('id' => $id))->update([
            'endtime' => time(),
            'status' => 1,
            'operator' => session('admin_username'),
            'mcid' => $mcid
        ]);
        if (false !== $rs) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function mytxExcel()
    {
        $_POST = input('post.');
        $_GET = input('param.');
        if (IS_POST) {
            $id = implode(',', $_POST['id']);
        } else {
            $id = input('param.id');
        }

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $where['id'] = array('in', $id);
        $list = Db::name('Mytx')->where($where)->select();

        foreach ($list as $k => $v) {
            $list[$k]['userid'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
            $list[$k]['addtime'] = addtime($v['addtime']);

            if ($list[$k]['status'] == 0) {
                $list[$k]['status'] = '未处理';
            } else if ($list[$k]['status'] == 1) {
                $list[$k]['status'] = '已划款';
            } else if ($list[$k]['status'] == 2) {
                $list[$k]['status'] = '已撤销';
            } else {
                $list[$k]['status'] = '错误';
            }

            $list[$k]['bankcard'] = ' ' . $v['bankcard'] . ' ';
        }

        $zd = Db::name('Mytx')->getTableFields();
        $xlsName = 'cade';
        $xls = array();

        foreach ($zd as $k => $v) {
            $xls[$k][0] = $v;
            $xls[$k][1] = $v;
        }

        $xls[0][2] = '编号';
        $xls[1][2] = '用户名';
        $xls[2][2] = '提现金额';
        $xls[3][2] = '手续费';
        $xls[4][2] = '到账金额';
        $xls[5][2] = '姓名';
        $xls[6][2] = '银行备注';
        $xls[7][2] = '银行名称';
        $xls[8][2] = '开户省份';
        $xls[9][2] = '开户城市';
        $xls[10][2] = '开户地址';
        $xls[11][2] = '银行卡号';
        $xls[12][2] = ' ';
        $xls[13][2] = '提现时间';
        $xls[14][2] = '导出时间';
        $xls[15][2] = '提现状态';
        $this->exportExcel($xlsName, $xls, $list);
    }

    public function weike_financeExcel()
    {
        $_POST = input('post.');
        $_GET = input('param.');
        if (IS_POST) {
            $id = implode(',', $_POST['id']);
        } else {
            $id = intval(input('param.id'));
        }

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $where['id'] = array('in', $id);
        $list = Db::name('Finance')->where($where)->select();
        $name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');

        foreach ($list as $k => $v) {
            $list[$k]['userid'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
            $list[$k]['addtime'] = addtime($v['addtime']);
            $list[$k]['caozuoqian'] = "正常 : " . $v['num_a'] . "冻结 : " . $v['num_b'] . "总计 : " . $v['num'];
            $list[$k]['caozuohou'] = "正常 : " . $v['mum_a'] . "冻结 : " . $v['mum_b'] . "总计 : " . $v['mum'];
            $list[$k]['name'] = ($name_list[$v['name']] ? $name_list[$v['name']] : $v['name']);

            if ($list[$k]['type'] == 1) {
                $list[$k]['type'] = '收入';
            } else if ($list[$k]['type'] == 2) {
                $list[$k]['type'] = '支出';
            }
            if ($list[$k]['status'] == 0) {
                $list[$k]['status'] = '异常';
            } else if ($list[$k]['status'] == 1) {
                $list[$k]['status'] = '正常';
            }

            unset($list[$k]['remark']);
            unset($list[$k]['nameid']);
            unset($list[$k]['move']);
            unset($list[$k]['num_a']);
            unset($list[$k]['mum_a']);
            unset($list[$k]['num_b']);
            unset($list[$k]['mum_b']);
            unset($list[$k]['num']);
            unset($list[$k]['mum']);

        }

        //$zd = Db::name('Finance')->getTableFields();
        $xlsName = 'finance';
        $xls = array();

        $xls[0][0] = "id";
        $xls[0][2] = '编号';
        $xls[1][0] = "userid";
        $xls[1][2] = '用户名';
        $xls[2][0] = "coinname";
        $xls[2][2] = '操作币种';
        $xls[3][0] = "fee";
        $xls[3][2] = '操作数量';
        $xls[4][0] = "type";
        $xls[4][2] = '操作类型';
        $xls[5][0] = "name";
        $xls[5][2] = '操作说明';
        $xls[6][0] = "addtime";
        $xls[6][2] = '操作时间';
        $xls[7][0] = "caozuoqian";
        $xls[7][2] = '操作前';
        $xls[8][0] = "caozuohou";
        $xls[8][2] = '操作后';
        $xls[9][0] = "status";
        $xls[9][2] = '状态';
        $this->exportExcel($xlsName, $xls, $list);
    }

    public function weike_financeAllExcel()
    {
        $list = Db::name('Finance')->select();
        $name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');

        foreach ($list as $k => $v) {
            $list[$k]['userid'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
            $list[$k]['addtime'] = addtime($v['addtime']);

            $list[$k]['caozuoqian'] = "正常 : " . $v['num_a'] . "冻结 : " . $v['num_b'] . "总计 : " . $v['num'];
            $list[$k]['caozuohou'] = "正常 : " . $v['mum_a'] . "冻结 : " . $v['mum_b'] . "总计 : " . $v['mum'];

            $list[$k]['name'] = ($name_list[$v['name']] ? $name_list[$v['name']] : $v['name']);

            if ($list[$k]['type'] == 1) {
                $list[$k]['type'] = '收入';
            } else if ($list[$k]['type'] == 2) {
                $list[$k]['type'] = '支出';
            }
            if ($list[$k]['status'] == 0) {
                $list[$k]['status'] = '异常';
            } else if ($list[$k]['status'] == 1) {
                $list[$k]['status'] = '正常';
            }

            unset($list[$k]['remark']);
            unset($list[$k]['nameid']);
            unset($list[$k]['move']);
            unset($list[$k]['num_a']);
            unset($list[$k]['mum_a']);
            unset($list[$k]['num_b']);
            unset($list[$k]['mum_b']);
            unset($list[$k]['num']);
            unset($list[$k]['mum']);

        }

        //$zd = Db::name('Finance')->getTableFields();
        $xlsName = 'finance';
        $xls = array();

        $xls[0][0] = "id";
        $xls[0][2] = '编号';
        $xls[1][0] = "userid";
        $xls[1][2] = '用户名';
        $xls[2][0] = "coinname";
        $xls[2][2] = '操作币种';
        $xls[3][0] = "fee";
        $xls[3][2] = '操作数量';
        $xls[4][0] = "type";
        $xls[4][2] = '操作类型';
        $xls[5][0] = "name";
        $xls[5][2] = '操作说明';
        $xls[6][0] = "addtime";
        $xls[6][2] = '操作时间';
        $xls[7][0] = "caozuoqian";
        $xls[7][2] = '操作前';
        $xls[8][0] = "caozuohou";
        $xls[8][2] = '操作后';
        $xls[9][0] = "status";
        $xls[9][2] = '状态';
        $this->exportExcel($xlsName, $xls, $list);
    }

    public function mytxConfig()
    {
        if (empty($_POST)) {
            return $this->fetch();
        } else if (false !== Db::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function myzr()
    {
        $status = input('status/d');
        $name = input('name/s');
        $field = input('field/s');
        $coinname = input('coinname/s');
        $address = input('address/s');
        $tradeid = input('tradeid/s');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else if ($field == 'sb_code') {
                //添加识别码搜索
                $where['userid'] = Db::name('Myzr')->where(array('tradeno' => $name))->value('userid');
                $where['tradeno'] = $name;
            } else {
                $where[$field] = $name;
            }
        }
        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($coinname) {
            $where['coinname'] = $coinname;
        }
        if ($address) {
            $where['username'] = $address;
        }
        if ($tradeid) {
            $where['tradeid'] = $tradeid;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }

        $count = Db::name('Myzr')->where($where)->count();

        $list = Db::name('Myzr')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['usernamea'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();



        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('weike_count', $count);
        $where['status'] = 1;
        $weike_sum = Db::name('Myzr')->where($where)->sum('num');
        $weike_num = Db::name('Myzr')->where($where)->sum('mum');
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
        return $this->fetch();
    }

    //加密货币转入增加，修改
    public function myzrEdit()
    {
        $id = input('id/d');

        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;
            } else {
                $myzr_weike = Db::name('Myzr')->where(array('id' => $id))->find();
                $this->data = $myzr_weike;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['userid'] = Db::name('User')->where(['username' => trim($_POST['userid'])])->value('id');
            if (!$_POST['userid']) {
                $this->error('用户不存在!');
            }
            $_POST['addtime'] = time();
            $_POST['czr'] = session('admin_username');
            $_POST['mum'] = trim($_POST['num']);
            $_POST['username'] = trim($_POST['username']);
            $_POST['coinname'] = trim($_POST['coinname']);
            $_POST['txid'] = trim($_POST['txid']);

            if (!empty($_POST['id'])) {
                $rs = Db::name('Myzr')->update($_POST);
            } else {
                $rs = Db::name('Myzr')->insert($_POST);
            }

            if (false !== $rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    public function myzc()
    {
        $status = input('status/d');
        $name = input('name/s');
        $field = input('field/s');
        $coinname = input('coinname/s');
        $address = input('address/s');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($coinname) {
            $where['coinname'] = $coinname;
        }
        if ($address) {
            $where['username'] = $address;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }

        $count = Db::name('Myzc')->where($where)->count();
        $weike_sum = Db::name('Myzc')->where($where)->sum('num');
        $weike_num = Db::name('Myzc')->where($where)->sum('mum');

        $list = Db::name('Myzc')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['usernamea'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['truename'] = Db::name('User')->where(array('id' => $item['userid']))->value('truename');
            return $item;
        });
        $show = $list->render();



        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('weike_count', $count);
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
        return $this->fetch();
    }

    public function myzcQueren()
    {
        $id = input('param.id/d');
        $pass = input('param.pass/s');
        $type = input('param.type/d');

        $myzc = Db::name('Myzc')->where(array('id' => trim($id)))->find();
        if (!$myzc) {
            $this->error('转出错误！');
        }

//        if ($myzc['coinname'] == 'vbc') {
//            $myzc['username'] = Db::name('UserCoin')->where(['userid' => $myzc['userid']])->value('vbcb');
//        }

        if ($myzc['status'] != 0 && $myzc['status'] != 3) {
            $this->error('已经处理过！');
        }

        if ($type === 1) {
            if (($myzc['coinname'] !== 'wcg' && $myzc['coinname'] !== 'vbc' && $myzc['coinname'] !== 'drt' && $myzc['coinname'] !== 'mat') ||
                (($myzc['coinname'] == 'wcg') && $myzc['num'] > config('coin')[$myzc['coinname']]['zc_zd']) ||
                (($myzc['coinname'] == 'vbc') && $myzc['num'] > config('coin')[$myzc['coinname']]['zc_zd']) ||
                (($myzc['coinname'] == 'drt') && $myzc['num'] > config('coin')[$myzc['coinname']]['zc_zd']) ||
                (($myzc['coinname'] == 'mat') && $myzc['num'] > config('coin')[$myzc['coinname']]['zc_zd'])
            ) {
                // 非 BOSS 不能确认转出
                if (session('admin_id') != 11) {
                    $this->error('非 BOSS 不能确认提币！');
                }

                // 判断 BOSS 密码是否正确
                $password = Db::name('Admin')->where(array('id' => 11))->value('password');
                if (md5($pass) != $password) {
                    $this->error('BOSS 密码不正确！');
                }
            }

            $coin = $myzc['coinname'];
            $dj_username = config('coin')[$coin]['dj_yh'];
            $dj_password = config('coin')[$coin]['dj_mm'];
            $dj_address = config('coin')[$coin]['dj_zj'];
            $dj_port = config('coin')[$coin]['dj_dk'];

            if (config('coin')[$coin]['type'] == 'bit') {
                //转出地址判断
                if  (substr($myzc['username'], 0, 2) == '0x') {
                    $this->error('请核对转出地址');
                }
                $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                if (config('coin')[$coin]['name'] == 'btc' || config('coin')[$coin]['name'] == 'ltc') {
                    $json = $CoinClient->getnetworkinfo();
                    $block_balance = $CoinClient->getwalletinfo();
                    $coin_balance = $block_balance['balance'];
                } else {
                    $json = $CoinClient->getinfo();
                    $coin_balance = $json['balance'];
                }
                if ($coin_balance < $myzc['mum']) {
                    $this->error('转出数量不足,当前可用余额:' . $coin_balance);
                }
                if (!isset($json['version']) || !$json['version']) {
                    $this->error('钱包对接失败！');
                }

            } elseif (config('coin')[$coin]['type'] == 'eth' || config('coin')[$coin]['type'] == 'token') {
                //判断转出地址
                $is_address = substr($myzc['username'], 0, 2);
                if (strlen($myzc['username']) != 42 || $is_address != '0x') {
                    $this->error('转出地址有误');
                }
                //判断钱包状态
                $CoinClient = EthClient($dj_address, $dj_port);
                $json = $CoinClient->eth_blockNumber(true);
                if (empty($json) || $json <= 0) {
                    $this->error('钱包对接失败！');
                }


                //判断可用余额
                if (config('coin')[$coin]['type'] == 'eth') {
                    $yue = $CoinClient->eth_getBalance($dj_username);
                    if ($myzc['mum'] > $yue) {
                        $this->error('转出数量不足,当前可用余额:' . $yue);
                    }
                } else if (config('coin')[$coin]['type'] == 'token') {
                    $call = [
                        'to' => config('coin')[$coin]['token_address'],
                        'data' => '0x70a08231' . $CoinClient->data_pj($dj_username)
                    ];
                    $yue = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), config('coin')[$coin]['decimals']);
                    if ($myzc['mum'] > $yue) {
                        $this->error('转出数量不足,当前可用余额:' . $yue);
                    }
                }

            } elseif (config('coin')[$coin]['type'] == 'eos') {
                //钱包对接判断
                $EosClient = EosClient($dj_address, $dj_port);
                $json = $EosClient->get_info();
                if (!$json) {
                    $this->error('钱包对接失败!');
                }
                //转出地址判断
                $arr = explode(" ", $myzc['username']);
                $to = $arr[0];
                $memo = $arr[1];
                if(strlen($to) != '12'){
                    $this->error('请核对转出地址');
                }
                //钱包可用余额判断
                $tradeInfo = [
                    "account" => config('coin')[$coin]['dj_yh'],
                    "code" => config('coin')[$coin]['token_address'],
                    "symbol" => "eos",
                ];
                $account_info = $EosClient->get_currency_balance($tradeInfo);
                $yue =  trim(substr($account_info[0], 0, strlen($account_info[0]) - 3));
                if ($myzc['mum'] > $yue) {
                    $this->error('转出数量不足,当前可用余额:' . $yue);
                }
            } else {
                if (config('coin')[$coin]['type'] !== 'rgb') {
                    $this->error('钱包对接失败！');
                }
            }

            $Coin = Db::name('Coin')->where(array('name' => $myzc['coinname']))->find();
            if (!$Coin['zc_user']) {
                $this->error('官方手续费地址为空！');
            }

            if ($Coin['type'] == 'token' && !$Coin['token_address']) {
                $this->error('合同地址为空!');
            }

            if ($Coin['type'] == 'token' && !$Coin['decimals']) {
                $this->error('合同位数为空!');
            }

            $fee_user = Db::name('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = Db::name('UserCoin')->where(array('userid' => $myzc['userid']))->find();
            if ($myzc['coinname'] == 'vbc') {
            } else {
                $zhannei = Db::name('UserCoin')->where(array($coin . 'b' => $myzc['username']))->find();
            }


            Db::startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = Db::table('weike_myzr')->insert(array('userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'endtime' => time(), 'status' => 1));
                $rs[] = $r = Db::table('weike_user_coin')->where(array('userid' => $zhannei['userid']))->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = Db::table('weike_myzc_fee')->insert(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));
                $user_coin_add=Db::table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
                if(false !== $user_coin_add){
                    $rs[] = Db::table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(array('lastsql' => Db::table('weike_user_coin')->getLastSql()), '新增费用');
                }else{
                    Db::rollback();
                    $this->error('手续费钱包地址为空，请添加手续费地址!');
                }

            }

            $rs[] = Db::table('weike_myzc')->where(array('id' => trim($id)))->update(array('status' => 1, 'endtime' => time(), 'qr_czr' => session("admin_username")));

            if (check_arr($rs)) {
                if ($Coin['type'] == 'bit' && $Coin['name'] != 'blc') {
                    $passphrase = md5($Coin['passphrase']);
                    $CoinClient->walletpassphrase($passphrase, 60);
                    $sendrs = $CoinClient->sendtoaddress(trim($myzc['username']), (double)$myzc['mum']);
                    $CoinClient->walletlock();
                } elseif ($Coin['name'] == 'blc') {
//                    $passphrase = md5($Coin['passphrase']);
//                    $CoinClient->walletpassphrase($passphrase, $timeout ='60');
                    $sendrs = $CoinClient->sendtoaddress(trim($myzc['username']), (double)$myzc['mum']);
                } elseif ($Coin['type'] == 'eth') {
                    $tradeInfo = [[
                        'from' => $Coin['dj_yh'],
                        'to' => trim($myzc['username']),
//                        'gas' => '0x1046a',
                        'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($myzc['mum']))),
//                        'gasPrice' => '0x189640200',//$CoinClient->eth_gasPrice()
                    ]];
                    $sendrs = $CoinClient->eth_sendTransaction($Coin['dj_yh'], $Coin['dj_mm'], $tradeInfo);
                    $sendrs = $sendrs->result;
                } elseif ($Coin['type'] == 'token') {

                    $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($myzc['mum']), $Coin['decimals']));
                    $tradeInfo = [[
                        'from' => $Coin['dj_yh'],
                        'to' => $Coin['token_address'],
                        'gas' => '0x1046a',
                        'data' => '0xa9059cbb' . $CoinClient->data_pj(trim($myzc['username']), $value),
                        'gasPrice' => '0x189640200',
                    ]];
                    $sendrs = $CoinClient->eth_sendTransaction($Coin['dj_yh'], $Coin['dj_mm'], $tradeInfo);
                    $sendrs = $sendrs->result;
                } elseif ($Coin['type'] == 'eos') {

                    $to_mun = substr($myzc['mum'] ,0,-4);
                    //数列化2bin->json
                    $coin_D = strtoupper($coin);
                    $abi_json_to_bin_Info = [
                        'code' => $Coin['token_address'],
                        'action' => 'transfer',
                        'args' => [
                            'from' => $Coin['dj_yh'],
                            'to' => $to,
                            'quantity' =>$to_mun . " $coin_D",
                            'memo' => $memo,
                        ]
                    ];
                    $binargs = $EosClient->abi_json_to_bin($abi_json_to_bin_Info);
                    $head_block_num = $json->head_block_num;
                    $get_block_info = $EosClient->get_block(['block_num_or_id' => $head_block_num]);
                    $timestamp = $get_block_info->timestamp;
                    $block_num = $get_block_info->block_num;
                    $ref_block_prefix = $get_block_info->ref_block_prefix;
                    $time_arr = explode('+',date('c', time($timestamp) - 28680));
                    $timestamp =  $time_arr[0] . '.500';
                    //解锁
                    //部署签名
                    $sign_transaction_info = [
                        [
                            'ref_block_num' => $block_num,
                            'ref_block_prefix' => $ref_block_prefix,
                            'expiration' => $timestamp,
                            'actions' => [
                                ['account' => $Coin['token_address'],
                                    'name' => 'transfer',
                                    'authorization' => [['actor' => $Coin['dj_yh'], 'permission' => 'active']],
                                    'data' => $binargs->binargs,]
                            ],
                            'signatures' => [],
                        ],
                        [$Coin['zc_user']],
                        'aca376f206b8fc25a6ed44dbdc66547c36c6c33e3a119ffbeaef943642f0e906'
                    ];
                    $sign_transaction = $EosClient->sign_transaction('cxz', $Coin['dj_mm'], $sign_transaction_info);
                    //发起事务
                    $push_transaction_info = [
                        'compression' => 'none',
                        'transaction' => [
                            'expiration' => $timestamp,
                            'ref_block_num' => $block_num,
                            'ref_block_prefix' => $ref_block_prefix,
                            'context_free_actions' => [],
                            'actions' => [
                                [
                                    'account' => $Coin['token_address'],
                                    'name' => 'transfer',
                                    'authorization' => [
                                        [
                                            'actor' => $Coin['dj_yh'],
                                            'permission' => 'active'
                                        ],
                                    ],
                                    'data' => $binargs->binargs,
                                ],
                            ],
                            'transaction_extensions' => [],
                        ],
                        'signatures' => [
                            $sign_transaction->signatures[0]
                        ]
                    ];

                    $sendrs = $EosClient->push_transaction('cxz', $Coin['dj_mm'],$push_transaction_info);
                    $sendrs = $sendrs->transaction_id;
                }
                if ($sendrs) {
                    $flag = 1;
                    Db::name('Myzc')->where(['id'=>$id])->update(['txid' => $sendrs]);
                    $arr = json_decode($sendrs, true);
                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } else {
                    $flag = 0;
                    //认购币
                    $coins_sub = ['wcg','vbc','drt','mat'];
                    if (in_array($coin,$coins_sub,true)) {
                        $flag = 1;
                    }
                }

                if (!$flag) {
                    Db::rollback();
                    $this->error('钱包服务器转出币失败!');
                } else {
                    Db::commit();
                    $this->success('转账成功！');
                }
            } else {
                Db::rollback();
                $this->error('转出失败!' . implode('|', $rs) . $myzc['fee']);
            }
        } elseif ($type === 2) {
            // 非 BOSS 不能撤销
//            if (session('admin_id') != 11) {
//                $this->error('非 BOSS 不能撤销！');
//            }
            Db::startTrans();
            try{
                $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $myzc['userid']))->setInc($myzc['coinname'], $myzc['num']);
                $rs[] = Db::table('weike_myzc')->where(array('id' => trim($id)))->update(array('status' => 2, 'endtime' => time(), 'cx_czr' => session("admin_username")));
                if (check_arr($rs)) {
                    Db::commit();
                    $this->success('操作成功！');
                } else {
                    Db::rollback();
                    $this->error('转出失败!');
                }

            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('转出失败!');
            }

        } else if ($type === 3) {

            $rs = Db::table('weike_myzc')->where(array('id' => trim($id)))->update(array('status' => 3, 'endtime' => time(), 'cl_czr' => session("admin_username")));
            if (false !== $rs) {
                $this->success('已经审核！');
            } else {
                $this->error('审核失败!');
            }
        }
    }

    //Epay充值
    public function epay()
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }

        $count = Db::name('Epaycz')->where($where)->count();
        $weike_sum = Db::name('Epaycz')->where($where)->sum('num');
        $weike_num = Db::name('Epaycz')->where($where)->sum('mum');

        $list = Db::name('Epaycz')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('weike_count', $count);
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
        return $this->fetch();
    }
}

?>