<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 1/26/2018
 * Time: 10:56 AM
 */

namespace app\admin\controller;

use think\Db;

class Activity extends Admin
{

    //游戏充值奖励
    public function myaward()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            $where[$field] = $name;
        }
        $list = Db::name('UserAward')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //游戏充值奖励修改
    public function myawardEdit()
    {
        $id = input('id/d');

        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;
            } else {
                $award_weike = Db::name('UserAward')->where(array('id' => $id))->find();
                $this->data = $award_weike;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = time();
            $_POST['czr'] = session('admin_username');

            if (!empty($_POST['id'])) {
                $rs = Db::name('UserAward')->update($_POST);
            } else {
                $rs = Db::name('UserAward')->insert($_POST);
            }

            if (false !== $rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    //游戏充值奖励状态
    public function myawardStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble =input('moble/s', 'UserAward');
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
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    //胶积分 释放
    public function issue()
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


        $list = Db::name('IssueEjf')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });

        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //游戏充值奖励修改
    public function issueEdit()
    {
        $id = input('id/d');

        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;
            } else {
                $award_weike = Db::name('IssueEjf')->where(array('id' => $id))->find();
                $award_weike['username'] = Db::name('User')->where(array('id' => $award_weike['userid']))->value('username');
                $this->data = $award_weike;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = time();
            $_POST['endtime'] = strtotime($_POST['endtime']);
            $_POST['userid'] = Db::name('User')->where(array('username' => $_POST['username']))->value('id');
            if (empty($_POST['userid'])){
                $this->error('用户不存在!');
            }
            unset($_POST['username']);

            if (!empty($_POST['id'])) {
                $rs = Db::name('IssueEjf')->update($_POST);
            } else {
                $rs = Db::name('IssueEjf')->insert($_POST);
            }

            if (false !== $rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    //华克金 利息
    public function issueCoin()
    {
        $name = input('name/s');
        $field = input('field/s');
        $coinname = input('coinname/s');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $count = Db::name('IssueCoin')->where($where)->count();

        $list = Db::name('IssueCoin')->field('weike_issue_coin.*,weike_user.username')->join('weike_user', 'weike_issue_coin.userid = weike_user.id')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('count', $count);

        $where['weike_user.usertype'] = ['neq', 1];
        $interestSum = Db::name('IssueCoin')->join('weike_user', 'weike_issue_coin.userid = weike_user.id')->where($where)->sum('interest');
        $this->assign('interestSum', $interestSum);
        return $this->fetch();
    }

    // 交易误差记录
    public function deal()
    {
        $name = input('name/s', null);
        $field = input('field/s', null);
        $where=[];

        $where['status'] = 1;
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = Db::name('Trade')->where($where)->whereExp('deal',"> `num`")->count();
        $weike_getSum = Db::name('Trade')->where($where)->whereExp('deal',"> `num`")->sum('mum');

        //获取已成交总数量，已成交总额额
        $weike_num = Db::name('Trade')->where($where)->whereExp('deal',"> `num`")->sum('deal');
        $weike_total = round(Db::name('Trade')->where($where)->whereExp('deal',"> `num`")->sum('price * deal'), 8);

        $list = Db::name('Trade')->where($where)->whereExp('deal',"> `num`")->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['usertype'] = Db::name('User')->where(array('id' => $item['userid']))->value('usertype');

            //获取已成交总额
            $item['deal_mum'] =$item['deal'] * $item['price'];
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('weike_count', $count);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('page', $show);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_total', $weike_total);
        return $this->fetch();
    }
    
    // 提现商户
    public function merchant()
    {
        $name = input('name/s');
        $field = input('field/s');
        $where = array();
        if ($field && $name) {
            $where[$field] = $name;
        }

        $list = Db::name('MytxMerchant')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    // 提现商户修改
    public function merchantEdit()
    {
        $id = input('id/d');

        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;
            } else {
                $award_weike = Db::name('MytxMerchant')->where(array('id' => $id))->find();
                $this->data = $award_weike;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['endtime'] = time();


            $MytxMerchant = model('MytxMerchant');

              // 或者$_POST

            if (!empty($_POST['id'])) {
                $rs=$MytxMerchant->update($_POST,['id'=>$_POST['id']],true);
            } else {
                $_POST['addtime'] = time();
                $rs = $MytxMerchant->create($_POST,true);

            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    //管理员 日志
    public function adminLog()
    {
        $name = input('name/s');
        $field = input('field/s');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('Admin')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $list = Db::name('AdminLog')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('Admin')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $count = $list->total();
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('count', $count);
        return $this->fetch();
    }
}