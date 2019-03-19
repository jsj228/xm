<?php
namespace Admin\Controller;
use Common\Ext\BtmClient;
use Common\Ext\XrpClient;

class FinanceController extends AdminController
{
	public function index()
	{
        $name =strval(I('name'));
        $field = strval(I('field'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}
        $usertpye=M('user')->field('id')->where(['usertype'=>1])->select();
		$id= array_column($usertpye,'id');
		$userid=implode(',',$id);
		if(empty($name)){
			$where=['userid'=>['not in',$userid]];
		}
		$count = M('Finance')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Finance')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$name_list = array('mycz' => 'CNY充值', 'mytx' => '提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');
		$nameid_list = array('mycz' => U('Mycz/index'), 'mytx' => U('Mytx/index'), 'trade' => U('Trade/index'), 'tradelog' => U('Tradelog/index'), 'issue' => U('Issue/index'));

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['num_a'] = Num($v['num_a']);
			$list[$k]['num_b'] = Num($v['num_b']);
			$list[$k]['num'] = Num($v['num']);
			$list[$k]['fee'] = Num($v['fee']);
			$list[$k]['type'] = ($v['type'] == 1 ? '收入' : '支出');
			$list[$k]['name'] = ($name_list[$v['name']] ? $name_list[$v['name']] : $v['name']);
			$list[$k]['nameid'] = ($name_list[$v['name']] ? $nameid_list[$v['name']] . '?id=' . $v['nameid'] : '');
			$list[$k]['mum_a'] = Num($v['mum_a']);
			$list[$k]['mum_b'] = Num($v['mum_b']);
			$list[$k]['mum'] = Num($v['mum']);
			$list[$k]['addtime'] = addtime($v['addtime']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function mycz()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Mycz')->where($where)->count();
        $weike_sum = M('Mycz')->where($where)->sum('num');
        $weike_num = M('Mycz')->where($where)->sum('mum');
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			if ($v['type'] === 'overseas'){
                $list[$k]['type'] = '海外支付宝支付';
            } else {
                $list[$k]['type'] = M('MyczType')->where(array('name' => $v['type']))->getField('title');
            }
            //随机网银判断
            $list[$k]['bankcard'] = M('MyczType')->where(array('id' => $v['bank_id']))->getField('username');
		}
		$myczs=M('tx_cz')->getField('cz');
		$this->assign('myczs',$myczs);
		$this->assign('list', $list);
		$this->assign('page', $show);
        $this->assign('weike_count', $count);
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
		$this->display();
	}

	public function myczStatus()
	{
        $id = I('id/a');
        $type = I('get.type/s');
        $moble = I('moble/s','Mycz');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
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
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败1！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}

    public function myzrQueren()
	{
		$id = I('id/d');
        $type = I('type/d');
        $tradeid = I('tradeid/s');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$myzr = M('Myzr')->where(array('id' => I('id/d')))->find();
		if (($myzr['status'] != 0)) {
			$this->error('已经处理，禁止再次操作！');
		}

		//华克金，交易 ID 判断
        if ($myzr['coinname'] === 'wcg' && $type === 1){
		    if ($tradeid !== $myzr['tradeid']) {
                $this->error('请输入正确的交易ID！');
            }
        }



        if ($type === 1) {
            $mo = M();
            $mo->startTrans();
            $myzr = M('Myzr')->lock(true)->where(array('id' => I('id/d')))->find();
            if (($myzr['status'] != 0)) {
                $mo->rollback();
                $this->error('已经处理，禁止再次操作！');
            }

            //定义活动时间
            $start_time = strtotime('2018-12-22 00:00:00');
            $end_time = strtotime('2018-12-25 23:59:59');
            try{
                //符合活动条件
                if($myzr['coinname'] == 'wcg' && $myzr['num']>=100 && $myzr['addtime']>=$start_time && $myzr['addtime']<= $end_time){
                    $act_xm = M('act_xm')->where(['uid'=>$myzr['userid']])->find();
                    if(isset($act_xm)){
                        $mo->table('weike_act_xm')->where(['uid'=>$myzr['userid']])->save(['zr_id'=>$act_xm['zr_id'].','.$myzr['id'],'xm_number'=>$act_xm['xm_number']+floor($myzr['num']/100)]);
                    }else{
                        $mo->table('weike_act_xm')->add(['uid'=>intval($myzr['userid']),'zr_id'=>intval($myzr['id']),'xm_number'=>floor($myzr['num']/100),'addtime'=>time()]);

                    }
                }
                $mo->table('weike_user_coin')->where(['userid' => $myzr['userid']])->setInc($myzr['coinname'], $myzr['num']);
                $mo->table('weike_myzr')->where(['id' => $myzr['id']])->save(['status' => 1, 'mum' => $myzr['num'], 'endtime' => time(), 'czr' => session('admin_username')]);
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }

            if ($flag) {
                $this->success('处理成功');
            } else {
                $this->error('操作失败！');
            }
        } elseif($type === 2) {
            // 非 BOSS 不能撤销
            if (session('admin_id') != 11) {
                $this->error('非 BOSS 不能撤销！');
            }

            $mo = M();
            $mo->startTrans();
            $myzr = M('Myzr')->lock(true)->where(array('id' => I('id/d')))->find();
            if (($myzr['status'] != 0)) {
                $mo->rollback();
                $this->error('已经处理，禁止再次操作！');
            }
            try{
                $rs = $mo->table('weike_myzr')->where(['id' => $myzr['id']])->save(['status' => 2, 'mum' => 0, 'endtime' => time(), 'czr' => session('admin_username')]);
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }
            if ($flag) {
                $this->success('处理成功');
            } else {
                $this->error('操作失败！');
            }
        }
	}


	/*
	public function myzrQueren()
	{
		$id = intval(I('id/d'));

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$myzr = M('Myzr')->where(array('id' => I('id/d')))->find();

		if (($myzr['status'] != 0)) {
			$this->error('已经处理，禁止再次操作！');
		}

		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables weike_user_coin write,weike_myzr write,weike_finance write,weike_invit write,weike_user write');
		$rs = array();

		$rs[] = $mo->table('weike_user_coin')->where(['userid' => $myzr['userid']])->setInc($myzr['coinname'], $myzr['num']);
		$rs[] = $mo->table('weike_myzr')->where(['id' => $myzr['id']])->save(['status' => 1, 'mum' => $myzr['num'], 'endtime' => time(), 'czr' => session('admin_username')]);

		$cz_mes="处理成功";
		
		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success($cz_mes);
		} else {
			$mo->execute('rollback');
			$this->error('操作失败！');
		}
	}
	*/

	//人工到账
	public function myczQueren()
	{
		$id = intval(I('id/d'));

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mycz = M('Mycz')->where(array('id' => I('id/d')))->find();

		if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
			$this->error('已经处理，禁止再次操作！');
		}

		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables weike_user_coin write,weike_mycz write,weike_finance write,weike_invit write,weike_user write,weike_user_award_ifc write,weike_user_award write');
		$rs = array();
		$finance = $mo->table('weike_finance')->where(array('userid' => $mycz['userid']))->order('id desc')->find();
		$finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->find();
		$rs[] = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('cny', $mycz['num']);
		$finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->find();
		$finance_hash = md5($mycz['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mycz['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
		$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

		if ($finance['mum'] < $finance_num) {
			$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
		} else {
			$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
		}

		$rs[] = $mo->table('weike_finance')->add(array('userid' => $mycz['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '港币充值-人工到账', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
		
		$cz_mes="成功充值[".$mycz['num']."]元.";
		
		$cur_user_info = $mo->table('weike_user')->where(array('id' => $mycz['userid']))->find();

		/*//充值赠送 10000 itc
        if($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0 && $cur_user_info['idcardauth'] == 1){
            //判断总量
            $sum = $mo->table('weike_user_award_ifc')->sum('award_num');
            if ($total > $sum) {
                $arr = [
                    'userid' => $cur_user_info['invit_1'],
                    'award_currency' => 'ifc',
                    'award_num' => 10000,
                    'addtime' => time(),
                    'type' => 2,
                    'status' => 0,
                    'czr' => session('admin_username'),
                ];
                $rs[] = $mo->table('weike_user_award_ifc')->add($arr);
            }
        }*/

        //第一次充值赠送 5000 itc, 上级赠送 50 bcx
        /*$myua_id = $mo->table('weike_user_award')->where(['userid' => $mycz['userid']])->getField('id');
        $mycz_id = $mo->table('weike_mycz')->where(array('userid' => $mycz['userid'], 'status' => ['exp', ' in (1, 2, 5) ']))->getField('id');
        if (!$myua_id && !$mycz_id && $mycz['userid'] > 5380) {
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('ifc', 5000);
            $mo->table('weike_user_award')->add([
                'userid' => $mycz['userid'],
                'award_currency' => 'ifc',
                'award_num' => 5000,
                'addtime' => time()
            ]);
            if ($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0) {
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->setInc('bcx', 50);
            }
        }*/
        $rs[] = $mo->table('weike_mycz')->where(array('id' => $mycz['id']))->save(array('status' => 2, 'mum' => $mycz['num'], 'endtime' => time(), 'czr' => session('admin_username')));

		//invit_1  invit_2  invit_3  以mum为准  为到账金额
		//推广佣金，一次推广，终身拿佣金    奖励下线充值金额的0.6%三级分红。    一代0.3%      二代0.2%      三代0.1%
		$cz_jiner = $mycz['num'];
		if($cur_user_info['invit_1']&&$cur_user_info['invit_1']>0&&1==2){
			//存在一级推广人
			$invit_1_jiner = round(($cz_jiner/100)*0.3, 6);
			
			if ($invit_1_jiner) {
				//处理前信息
				$finance_1 = $mo->table('weike_finance')->where(array('userid' => $cur_user_info['invit_1']))->order('id desc')->find();
		        $finance_num_user_coin_1 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();
				
				//开始处理
				$rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->setInc('cny',$invit_1_jiner);
				$rs[] = $mo->table('weike_invit')->add(array('userid' => $cur_user_info['invit_1'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '一代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_1_jiner, 'addtime' => time(), 'status' => 1));
				
				//处理后
				$finance_mum_user_coin_1 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();
				$finance_hash_1 = md5($cur_user_info['invit_1'].$finance_num_user_coin_1['cny'] . $finance_num_user_coin_1['cnyd'] . $invit_1_jiner . $finance_mum_user_coin_1['cny'] . $finance_mum_user_coin_1['cnyd'] . MSCODE . 'auth.weike.com');
				$finance_num_1 = $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'];

				if ($finance_1['mum'] < $finance_num_1) {
					$finance_status_1 = (1 < ($finance_num_1 - $finance_1['mum']) ? 0 : 1);
				} else {
					$finance_status_1 = (1 < ($finance_1['mum'] - $finance_num_1) ? 0 : 1);
				}

				$rs[] = $mo->table('weike_finance')->add(array('userid' => $cur_user_info['invit_1'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_1['cny'], 'num_b' => $finance_num_user_coin_1['cnyd'], 'num' => $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => '港币充值-一代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_1_jiner.'元', 'mum_a' => $finance_mum_user_coin_1['cny'], 'mum_b' => $finance_mum_user_coin_1['cnyd'], 'mum' => $finance_mum_user_coin_1['cny'] + $finance_mum_user_coin_1['cnyd'], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1));
				
				//处理结束提示信息
				$cz_mes = $cz_mes."一代推荐奖励[".$invit_1_jiner."]元.";
			}
		}
		
		if($cur_user_info['invit_2']&&$cur_user_info['invit_2']>0&&1==2){
			//存在二级推广人
			$invit_2_jiner = round(($cz_jiner/100)*0.2, 6);
			if ($invit_2_jiner) {
				
				//处理前信息
				$finance_2 = $mo->table('weike_finance')->where(array('userid' => $cur_user_info['invit_2']))->order('id desc')->find();
		        $finance_num_user_coin_2 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();
				
				//开始处理
				
				$rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->setInc('cny',$invit_2_jiner);
				$rs[] = $mo->table('weike_invit')->add(array('userid' => $cur_user_info['invit_2'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '二代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_2_jiner, 'addtime' => time(), 'status' => 1));
			
				//处理后
				$finance_mum_user_coin_2 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();
				$finance_hash_2 = md5($cur_user_info['invit_2'].$finance_num_user_coin_2['cny'] . $finance_num_user_coin_2['cnyd'] . $invit_2_jiner . $finance_mum_user_coin_2['cny'] . $finance_mum_user_coin_2['cnyd'] . MSCODE . 'auth.weike.com');
				$finance_num_2 = $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'];

				if ($finance_2['mum'] < $finance_num_2) {
					$finance_status_2 = (1 < ($finance_num_2 - $finance_2['mum']) ? 0 : 1);
				} else {
					$finance_status_2 = (1 < ($finance_2['mum'] - $finance_num_2) ? 0 : 1);
				}

				$rs[] = $mo->table('weike_finance')->add(array('userid' => $cur_user_info['invit_2'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_2['cny'], 'num_b' => $finance_num_user_coin_2['cnyd'], 'num' => $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => '港币充值-二代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_2_jiner.'元', 'mum_a' => $finance_mum_user_coin_2['cny'], 'mum_b' => $finance_mum_user_coin_2['cnyd'], 'mum' => $finance_mum_user_coin_2['cny'] + $finance_mum_user_coin_2['cnyd'], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2));
				
				//处理结束提示信息
				$cz_mes = $cz_mes."二代推荐奖励[".$invit_2_jiner."]元.";
			}
		}
		
		if($cur_user_info['invit_3']&&$cur_user_info['invit_3']>0&&1==2){
			//存在三级推广人
			$invit_3_jiner = round(($cz_jiner/100)*0.1, 6);
			if ($invit_3_jiner) {
				
				//处理前信息
				$finance_3 = $mo->table('weike_finance')->where(array('userid' => $cur_user_info['invit_3']))->order('id desc')->find();
		        $finance_num_user_coin_3 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();
				
				//开始处理
				$rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->setInc('cny',$invit_3_jiner);
				$rs[] = $mo->table('weike_invit')->add(array('userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '三代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1));
			
				//处理后
				$finance_mum_user_coin_3 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();
				$finance_hash_3 = md5($cur_user_info['invit_3'].$finance_num_user_coin_3['cny'] . $finance_num_user_coin_3['cnyd'] . $invit_3_jiner . $finance_mum_user_coin_3['cny'] . $finance_mum_user_coin_3['cnyd'] . MSCODE . 'auth.weike.com');
				$finance_num_3 = $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'];

				if ($finance_3['mum'] < $finance_num_3) {
					$finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
				} else {
					$finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
				}

				$rs[] = $mo->table('weike_finance')->add(array('userid' => $cur_user_info['invit_3'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_3['cny'], 'num_b' => $finance_num_user_coin_3['cnyd'], 'num' => $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => '港币充值-三代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_3_jiner.'元', 'mum_a' => $finance_mum_user_coin_3['cny'], 'mum_b' => $finance_mum_user_coin_3['cnyd'], 'mum' => $finance_mum_user_coin_3['cny'] + $finance_mum_user_coin_3['cnyd'], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3));
				
				//处理结束提示信息
				$cz_mes = $cz_mes."三代推荐奖励[".$invit_3_jiner."]元.";
			}
			
		}

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success($cz_mes);
		} else {
			$mo->execute('rollback');
			$this->error('操作失败！');
		}
	}

	//花呗到账
    public function myczhb()
    {
        $id = intval(I('id/d'));

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $mycz = M('Mycz')->where(array('id' => I('id/d')))->find();

        if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
            $this->error('已经处理，禁止再次操作！');
        }

        $sxfei = M('MyczType')->where(['name'=>$mycz['type']])->getField('sxfei');
        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_user_coin write,weike_mycz write,weike_finance write,weike_invit write,weike_user write,weike_user_award_ifc write,weike_user_award write');
        $dzjine = round($mycz['num']*(1-$sxfei),2);
        $rs = array();
        $finance = $mo->table('weike_finance')->where(array('userid' => $mycz['userid']))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->find();
        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('cny', $dzjine);
        $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->find();
        $finance_hash = md5($mycz['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mycz['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
        $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('weike_finance')->add(array('userid' => $mycz['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '港币充值-人工到账', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        $cz_mes="成功充值[".$dzjine."]元.";

        $cur_user_info = $mo->table('weike_user')->where(array('id' => $mycz['userid']))->find();

        /*//充值赠送 10000 itc
        if($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0 && $cur_user_info['idcardauth'] == 1){
            //判断总量
            $sum = $mo->table('weike_user_award_ifc')->sum('award_num');
            if ($total > $sum) {
                $arr = [
                    'userid' => $cur_user_info['invit_1'],
                    'award_currency' => 'ifc',
                    'award_num' => 10000,
                    'addtime' => time(),
                    'type' => 2,
                    'status' => 0,
                    'czr' => session('admin_username'),
                ];
                $rs[] = $mo->table('weike_user_award_ifc')->add($arr);
            }
        }*/

        //第一次充值赠送 5000 itc, 上级赠送 50 bcx
        /*$myua_id = $mo->table('weike_user_award')->where(['userid' => $mycz['userid']])->getField('id');
        $mycz_id = $mo->table('weike_mycz')->where(array('userid' => $mycz['userid'], 'status' => ['exp', ' in (1, 2, 5) ']))->getField('id');
        if (!$myua_id && !$mycz_id && $mycz['userid'] > 5380) {
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $mycz['userid']))->setInc('ifc', 5000);
            $mo->table('weike_user_award')->add([
                'userid' => $mycz['userid'],
                'award_currency' => 'ifc',
                'award_num' => 5000,
                'addtime' => time()
            ]);
            if ($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0) {
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->setInc('bcx', 50);
            }
        }*/
        $rs[] = $mo->table('weike_mycz')->where(array('id' => $mycz['id']))->save(array('status' => 5, 'mum' => $dzjine, 'endtime' => time(), 'czr' => session('admin_username')));

        //invit_1  invit_2  invit_3  以mum为准  为到账金额
        //推广佣金，一次推广，终身拿佣金    奖励下线充值金额的0.6%三级分红。    一代0.3%      二代0.2%      三代0.1%
        $cz_jiner = $mycz['num'];
        if($cur_user_info['invit_1']&&$cur_user_info['invit_1']>0&&1==2){
            //存在一级推广人
            $invit_1_jiner = round(($cz_jiner/100)*0.3, 6);

            if ($invit_1_jiner) {
                //处理前信息
                $finance_1 = $mo->table('weike_finance')->where(array('userid' => $cur_user_info['invit_1']))->order('id desc')->find();
                $finance_num_user_coin_1 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();

                //开始处理
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->setInc('cny',$invit_1_jiner);
                $rs[] = $mo->table('weike_invit')->add(array('userid' => $cur_user_info['invit_1'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '一代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_1_jiner, 'addtime' => time(), 'status' => 1));

                //处理后
                $finance_mum_user_coin_1 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();
                $finance_hash_1 = md5($cur_user_info['invit_1'].$finance_num_user_coin_1['cny'] . $finance_num_user_coin_1['cnyd'] . $invit_1_jiner . $finance_mum_user_coin_1['cny'] . $finance_mum_user_coin_1['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num_1 = $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'];

                if ($finance_1['mum'] < $finance_num_1) {
                    $finance_status_1 = (1 < ($finance_num_1 - $finance_1['mum']) ? 0 : 1);
                } else {
                    $finance_status_1 = (1 < ($finance_1['mum'] - $finance_num_1) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->add(array('userid' => $cur_user_info['invit_1'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_1['cny'], 'num_b' => $finance_num_user_coin_1['cnyd'], 'num' => $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => '港币充值-一代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_1_jiner.'元', 'mum_a' => $finance_mum_user_coin_1['cny'], 'mum_b' => $finance_mum_user_coin_1['cnyd'], 'mum' => $finance_mum_user_coin_1['cny'] + $finance_mum_user_coin_1['cnyd'], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1));

                //处理结束提示信息
                $cz_mes = $cz_mes."一代推荐奖励[".$invit_1_jiner."]元.";
            }
        }

        if($cur_user_info['invit_2']&&$cur_user_info['invit_2']>0&&1==2){
            //存在二级推广人
            $invit_2_jiner = round(($cz_jiner/100)*0.2, 6);
            if ($invit_2_jiner) {

                //处理前信息
                $finance_2 = $mo->table('weike_finance')->where(array('userid' => $cur_user_info['invit_2']))->order('id desc')->find();
                $finance_num_user_coin_2 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();

                //开始处理

                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->setInc('cny',$invit_2_jiner);
                $rs[] = $mo->table('weike_invit')->add(array('userid' => $cur_user_info['invit_2'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '二代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_2_jiner, 'addtime' => time(), 'status' => 1));

                //处理后
                $finance_mum_user_coin_2 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();
                $finance_hash_2 = md5($cur_user_info['invit_2'].$finance_num_user_coin_2['cny'] . $finance_num_user_coin_2['cnyd'] . $invit_2_jiner . $finance_mum_user_coin_2['cny'] . $finance_mum_user_coin_2['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num_2 = $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'];

                if ($finance_2['mum'] < $finance_num_2) {
                    $finance_status_2 = (1 < ($finance_num_2 - $finance_2['mum']) ? 0 : 1);
                } else {
                    $finance_status_2 = (1 < ($finance_2['mum'] - $finance_num_2) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->add(array('userid' => $cur_user_info['invit_2'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_2['cny'], 'num_b' => $finance_num_user_coin_2['cnyd'], 'num' => $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => '港币充值-二代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_2_jiner.'元', 'mum_a' => $finance_mum_user_coin_2['cny'], 'mum_b' => $finance_mum_user_coin_2['cnyd'], 'mum' => $finance_mum_user_coin_2['cny'] + $finance_mum_user_coin_2['cnyd'], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2));

                //处理结束提示信息
                $cz_mes = $cz_mes."二代推荐奖励[".$invit_2_jiner."]元.";
            }
        }

        if($cur_user_info['invit_3']&&$cur_user_info['invit_3']>0&&1==2){
            //存在三级推广人
            $invit_3_jiner = round(($cz_jiner/100)*0.1, 6);
            if ($invit_3_jiner) {

                //处理前信息
                $finance_3 = $mo->table('weike_finance')->where(array('userid' => $cur_user_info['invit_3']))->order('id desc')->find();
                $finance_num_user_coin_3 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();

                //开始处理
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->setInc('cny',$invit_3_jiner);
                $rs[] = $mo->table('weike_invit')->add(array('userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '三代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1));

                //处理后
                $finance_mum_user_coin_3 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();
                $finance_hash_3 = md5($cur_user_info['invit_3'].$finance_num_user_coin_3['cny'] . $finance_num_user_coin_3['cnyd'] . $invit_3_jiner . $finance_mum_user_coin_3['cny'] . $finance_mum_user_coin_3['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num_3 = $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'];

                if ($finance_3['mum'] < $finance_num_3) {
                    $finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
                } else {
                    $finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->add(array('userid' => $cur_user_info['invit_3'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_3['cny'], 'num_b' => $finance_num_user_coin_3['cnyd'], 'num' => $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => '港币充值-三代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_3_jiner.'元', 'mum_a' => $finance_mum_user_coin_3['cny'], 'mum_b' => $finance_mum_user_coin_3['cnyd'], 'mum' => $finance_mum_user_coin_3['cny'] + $finance_mum_user_coin_3['cnyd'], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3));

                //处理结束提示信息
                $cz_mes = $cz_mes."三代推荐奖励[".$invit_3_jiner."]元.";
            }

        }

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->success($cz_mes);
        } else {
            $mo->execute('rollback');
            $this->error('操作失败！');
        }
    }

    //客户对充值订单备注
    public function czbz(){
        $id = I('post.id/d');
        $text = I('post.text/s');
        if (empty($id)) {
            die(json_encode(array('code'=>401,'msg'=>'请选择要操作的数据!','data'=>[])));
        }
        if (empty($text)) {
            die( json_encode(array('code'=>402,'msg'=>'请填写备注','data'=>[])));
        }
        $data = M('Mycz')->where(['id' => $id])->save(['beizhu'=> $text]);
        if ($data){
            die(json_encode(array('code'=>200,'msg'=>'备注成功','data'=>[])));
        }else{
            die(json_encode(array('code'=>403,'msg'=>'备注失败','data'=>[])));
        }
    }

	public function myczType()
	{
		$where = array();
		$count = M('MyczType')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('MyczType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function myczTypeEdit($id = NULL)
	{
        $id = I('id/d');
        $_POST = I('post./a');
		if (empty($_POST)) {
			if ($id) {
				$this->data = M('MyczType')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			$this->display();
		} else {
			if ($_POST['id']) {
				$rs = M('MyczType')->save($_POST);
			} else {
				$rs = M('MyczType')->add($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
		}
	}

    public function myczTypeImage()
    {
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/public/';
        $filename = md5($_FILES['upload_file0']['name']. uniqid() . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

	public function myczTypeStatus()
	{
        $id = I('id/a');
        $type = I('get.type/s');
        $moble = I('moble/s','MyczType');
	    if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
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
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败1！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}
	
	public function mytx()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}
        $summum = M('Mytx')->where($where)->sum('num');
		$count = M('Mytx')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['truename'] = M('User')->where(array('id' => $v['userid']))->getField('truename');
		}
		$myctx=M('tx_cz')->getField('tx');
		$this->assign('mytx',$myctx);
		$this->assign('list', $list);
		$this->assign('page', $show);
        $this->assign('count', $count);
        $this->assign('summum', $summum);
		$this->display();
	}
    
	public function mytxStatus()
	{
        $id = I('id/a');
        $type = I('get.type/s');
        $moble = I('moble/s','Mytx');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
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
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败1！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}

	public function mytxChuli()
	{
		$id = I('id/d');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		if (M('Mytx')->where(array('id' => $id))->save(array('status' => 3))) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function mytxChexiao()
	{
		$id = I('id/d');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mytx = M('Mytx')->where(array('id' => trim($id)))->find();
		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables weike_user_coin write,weike_mytx write,weike_finance write');
		$rs = array();
		$finance = $mo->table('weike_finance')->where(array('userid' => $mytx['userid']))->order('id desc')->find();
		$finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mytx['userid']))->find();
		$rs[] = $mo->table('weike_user_coin')->where(array('userid' => $mytx['userid']))->setInc('cny', $mytx['num']);
		$rs[] = $mo->table('weike_mytx')->where(array('id' => $mytx['id']))->setField('status', 2);
		$finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mytx['userid']))->find();
		$finance_hash = md5($mytx['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mytx['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
		$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

		if ($finance['mum'] < $finance_num) {
			$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
		} else {
			$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
		}

		$rs[] = $mo->table('weike_finance')->add(array('userid' => $mytx['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => '港币提现-撤销提现', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success('操作成功！');
		} else {
			$mo->execute('rollback');
			$this->error('操作失败！');
		}
	}

	public function mytxQueren()
	{
        $id = I('id/d');;

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		if (M('Mytx')->where(array('id' => $id))->save(array('status' => 1, 'czr' => session('admin_username')))) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function mytxExcel()
	{
        $_POST = I('post./a');
        $_GET = I('get./a');
		if (IS_POST) {
			$id = implode(',', $_POST['id']);
		} else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$list = M('Mytx')->where($where)->select();

		foreach ($list as $k => $v) {
			$list[$k]['userid'] = M('User')->where(array('id' => $v['userid']))->getField('username');
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

		$zd = M('Mytx')->getDbFields();
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
        $_POST = I('post./a');
        $_GET = I('get./a');
		if (IS_POST) {
			$id = implode(',', $_POST['id']);
		} else {
			$id = intval($_GET['id']);
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
			
		$where['id'] = array('in', $id);
		$list = M('Finance')->where($where)->select();
		$name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');
		
		foreach ($list as $k => $v) {
			$list[$k]['userid'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['addtime'] = addtime($v['addtime']);
			$list[$k]['caozuoqian'] = "正常 : ".$v['num_a']."冻结 : ".$v['num_b']."总计 : ".$v['num'];
			$list[$k]['caozuohou'] = "正常 : ".$v['mum_a']."冻结 : ".$v['mum_b']."总计 : ".$v['mum'];
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
		
		//$zd = M('Finance')->getDbFields();
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
		$list = M('Finance')->select();
		$name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');

		foreach ($list as $k => $v) {
			$list[$k]['userid'] = M('User')->field()->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['addtime'] = addtime($v['addtime']);

			$list[$k]['caozuoqian'] = "正常 : ".$v['num_a']."冻结 : ".$v['num_b']."总计 : ".$v['num'];
			$list[$k]['caozuohou'] = "正常 : ".$v['mum_a']."冻结 : ".$v['mum_b']."总计 : ".$v['mum'];

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

		//$zd = M('Finance')->getDbFields();
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
			$this->display();
		} else if (M('Config')->where(array('id' => 1))->save($_POST)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}

		public function myzr($addtime='',$endtime='')
	    {
        $name = I('name/s');
        $field = I('field/s');
        $coinname = I('coinname/s');
        $address = I('address/s');
        $tradeid = I('tradeid/s');
		$addtime ? $addtime = urldecode($addtime):false;
		$endtime ? $endtime = urldecode($endtime):false;
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'sb_code') {
			    //添加识别码搜索
                $where['userid'] = M('Myzr')->where(array('tradeno' => $name))->getField('userid');
                $where['tradeno'] = $name ;
			}else{
                $where[$field] = $name;
            }
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
		if ($addtime && $endtime){
		  //转换时间戳
			$addtime = strtotime($addtime);
			$endtime = strtotime($endtime);
			//条件
			$where['addtime'] = array(array('gt',$addtime),array('lt',$endtime)) ;
		}
		$num = M('Myzr')->where($where)->sum('mum');

		$count = M('Myzr')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Myzr')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $userids = array_column($list,'userid');

        if($userids){
			$user =  M('User')->where(array('id' =>['in',$userids]))->field('id,username,truename')->select();
		}
        $usernames = array_column($user,'username','id');
        $truenames = array_column($user,'truename','id');

		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = $usernames[$v['userid']]?$usernames[$v['userid']]:'';
            $list[$k]['truename'] = $truenames[$v['userid']]?$truenames[$v['userid']]:'';
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
        $this->assign('num', $num);
        $this->assign('count', $count);
		$this->display();
	}

	/*public function myzr()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $coinname = strval(I('coinname'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}

		$count = M('Myzr')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Myzr')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}*/

    //加密货币转入增加，修改
    public function myzrEdit()
    {
        $id = I('id/d');

        if (empty($_POST)) {
            $_POST = I('post./a');
            if (empty($id)) {
                $this->data = array();
            } else {
                $myzr_weike = M('Myzr')->where(array('id' => $id))->find();
                $this->data = $myzr_weike;
            }
            $this->display();
        } else {
            $_POST = I('post./a');
            $_POST['userid'] = M('User')->where(['username' => $_POST['userid']])->getField('id');
            if(!$_POST['userid']){
                $this->error('用户不存在!');
            }
            $_POST['addtime'] = time();
            $_POST['czr'] = session('admin_username');
            $_POST['mum'] = $_POST['num'];

            if ($_POST['id']) {
                $rs = M('Myzr')->save($_POST);
            } else {
                $rs = M('Myzr')->add($_POST);
            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

	public function myzc($addtime='',$endtime='')
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $coinname = strval(I('coinname'));
		$where = array();
		$addtime ? $addtime = urldecode($addtime):false;
		$endtime ? $endtime = urldecode($endtime):false;
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}
		if ($addtime && $endtime){
		  //转换时间戳
			$addtime = strtotime($addtime);
			$endtime = strtotime($endtime);
			//条件
			$where['addtime'] = array(array('gt',$addtime),array('lt',$endtime)) ;

		}
		$status=I('status');
		$this->assign('status',$status);
		if($status!=''){
			$where['status'] = $status;
		}
		$summum = M('Myzc')->where($where)->sum('num');
		$sum = M('Myzc')->where($where)->sum('mum');
		$count = M('Myzc')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Myzc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['truename'] = M('User')->where(array('id' => $v['userid']))->getField('truename');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
        $this->assign('summum', $summum);
		$this->assign('sum', $sum);
        $this->assign('count', $count);
		$this->display();
	}

	public function myzcQueren()
	{
        $id = I('get.id/d');
        $pass = I('get.pass/s');
        $type = I('get.type/d');

        if(empty(session('trade_pass'))) $this->error('请先验证交易密码');

		$myzc = M('Myzc')->where(array('id' => trim($id)))->find();
		if (!$myzc) {
			$this->error('转出错误！');
		}

		if ($myzc['status'] != 0) {
			$this->error('已经处理过！');
		}

        if ($type === 1) {
            /*if ($myzc['coinname'] != 'wcg' && $myzc['coinname'] != 'drt') {
                // 非 BOSS 不能确认转出
                if (session('admin_id') != 11) {
                    $this->error('非 BOSS 不能确认提币！');
                }

                // 判断 BOSS 密码是否正确
                $password = M('Admin')->where(array('id' => 11))->getField('password');
                if (md5($pass) != $password) {
                    $this->error('BOSS 密码不正确！');
                }
            }*/

            $coin = $myzc['coinname'];
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];

            if (C('coin')[$coin]['type'] == 'bit') {
                $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $json = $CoinClient->getinfo();

                if (!isset($json['version']) || !$json['version']) {
                    $this->error('钱包对接失败！');
                }

            } elseif (C('coin')[$coin]['type'] == 'eth' || C('coin')[$coin]['type'] == 'token') {
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
                if (C('coin')[$coin]['type'] == 'eth') {
                    $yue = $CoinClient->eth_getBalance($dj_username);
                    if ($myzc['mum'] > $yue) {
                        $this->error('转出数量不足,当前可用余额:' . $yue);
                    }
                } else if (C('coin')[$coin]['type'] == 'token') {
                    $call = [
                        'to' => C('coin')[$coin]['token_address'],
                        'data' => '0x70a08231' . $CoinClient->data_pj($dj_username)
                    ];
                    $yue = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), C('coin')[$coin]['decimals']);
                    if ($myzc['mum'] > $yue) {
                        $this->error('转出数量不足,当前可用余额:' . $yue);
                    }
                }

            } elseif (C('coin')[$coin]['type'] == 'eos') {
                $EosClient = EosClient($dj_address, $dj_port);
                $json = $EosClient->get_info();

                if (!$json) {
                    $this->error('钱包对接失败!');
                }
                $tradeInfo = [
                    "account" => C('coin')[$coin]['dj_yh'],
                    "code" => C('coin')[$coin]['token_address'],
                    "symbol" => "eos",
                ];
                $account_info = $EosClient->get_currency_balance($tradeInfo);
                $yue =  trim(substr($account_info[0], 0, strlen($account_info[0]) - 3));
                if ($myzc['mum'] > $yue) {
                    $this->error('转出数量不足,当前可用余额:' . $yue);
                }
            }else {
                if ($coin !== 'wcg' && $coin !== 'btmz' && $coin !== 'drt' && $coin !== 'mat' && $coin !== 'mtr' && $coin !== 'xrp' && $coin !== 'unih' && $coin !== 'wos' && $coin!=='eqt') {
                    $this->error('钱包对接失败！');
                }
            }


            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();

            if(!$Coin['zc_user']){
                $this->error('官方手续费地址为空！');
            }

            if ($Coin['type'] == 'token' && !$Coin['token_address']) {
                $this->error('合同地址为空!');
            }

            if ($Coin['type'] == 'token' && !$Coin['decimals']) {
                $this->error('合同位数为空!');
            }

            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = M('UserCoin')->where(array('userid' => $myzc['userid']))->find();
            $zhannei = M('UserCoin')->where(array($coin . 'b' => $myzc['username']))->find();
            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzr write , weike_myzc_fee write');
            $rs = array();

            if ($zhannei) {
                $rs[] = $mo->table('weike_myzr')->add(array('userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1));
                if ($coin == 'btmz'){
                    $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => $zhannei['userid']))->setInc('btm', $myzc['mum']);
                }else{
                    $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => $zhannei['userid']))->setInc($coin, $myzc['mum']);
                }
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    if ($coin == 'btmz'){
                        $rs[] = $mo->table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->setInc('btm', $myzc['fee']);
                    }else{
                        $rs[] = $mo->table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    }
                    //debug(array('lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                } else {
                    if ($coin == 'btmz'){
                        $rs[] = $mo->table('weike_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], 'btm' => $myzc['fee']));
                    }else{
                        $rs[] = $mo->table('weike_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                    }
                }
            }

            $rs[] = $mo->table('weike_myzc')->where(array('id' => trim($id)))->save(array('status' => 1, 'czr' => session("admin_username")));

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');

                if ($Coin['type'] == 'bit') {
                    $passphrase = md5($Coin['passphrase']);
                    $CoinClient->walletpassphrase($passphrase, 60);
                    $sendrs = $CoinClient->sendtoaddress($myzc['username'], (double)$myzc['mum']);
                    $CoinClient->walletlock();
                } elseif ($Coin['type'] == 'eth') {
                    $tradeInfo = [[
                        'from' => $Coin['dj_yh'],
                        'to' => $myzc['username'],
                        'gas' => '0x76c0',
                        'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($myzc['mum']))),
                        'gasPrice' => $CoinClient->eth_gasPrice()
                    ]];
                    $sendrs = $CoinClient->eth_sendTransaction($Coin['dj_yh'], $Coin['dj_mm'], $tradeInfo);
                } elseif ($Coin['type'] == 'token') {
                    //判断转出地址
                    $is_address = substr($myzc['username'] ,0,2);
                    if (strlen($myzc['username']) != 42 || $is_address != '0x')  {
                        $this->error('转出地址有误');
                    }
                    $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($myzc['mum']),$Coin['decimals']));
                    $tradeInfo = [[
                        'from' => $Coin['dj_yh'],
                        'to' => $Coin['token_address'],
                        'data' =>  '0xa9059cbb'. $CoinClient->data_pj($myzc['username'], $value),
                    ]];
                    $sendrs = $CoinClient->eth_sendTransaction($Coin['dj_yh'], $Coin['dj_mm'], $tradeInfo);
                }elseif ($Coin['type'] == 'btm'){
                    $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
                    if ($btmzData) {
                        $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                        $res = $btmClient->outcome($myzc['username'],$myzc['mum']);
                        if ($res){
                            $sigRes = $btmClient->signTransaction($res);
                            if ($sigRes){
                                $resSub = $btmClient->submitTransaction($sigRes);
                                if ($resSub){
                                    M('myzc')->where(array('id' => trim($id)))->save(['status'=>1,'txid'=>$resSub['tx_id']]);
                                    $mo->execute('commit');
                                    $this->success('提交成功');
                                    exit();
                                }else{
                                    M('myzc')->where(array('id' => trim($id)))->save(['status'=>0]);
                                    $mo->execute('commit');
                                    $this->error('提交失败');
                                    exit();
                                }
                            }else{
                                M('myzc')->where(array('id' => trim($id)))->save(['status'=>0]);
                                $mo->execute('commit');
                                $this->error('提交签名失败');
                                exit();
                            }
                        }else{
                            M('myzc')->where(array('id' => trim($id)))->save(['status'=>0]);
                            $mo->execute('commit');
                            $this->error('提交转出失败');
                            exit();
                        }
                    }else{
                        M('myzc')->where(array('id' => trim($id)))->save(['status'=>0]);
                        $mo->execute('commit');
                        $this->error('链接钱包失败');
                        exit();
                    }
                }elseif ($Coin['type'] == 'eos') {
                    $arr = explode(" ", $myzc['username']);
                    $to = $arr[0];
                    $memo = $arr[1];
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
                    $sign_transaction = $EosClient->sign_transaction('huo', $Coin['dj_mm'], $sign_transaction_info);
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

                    $sendrs = $EosClient->push_transaction('huo', $Coin['dj_mm'],$push_transaction_info);
                    $sendrs = $sendrs->transaction_id;
                }elseif ($Coin['type'] == 'xrp'){
                    $xrpData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="xrp"')->find();
                    if ($xrpData){
                        $xrpClient = new XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['dj_yh'], $xrpData['dj_mm'], $xrpData['token_address']);
                        $num = $myzc['mum'];
                        $destination = explode(" ",$myzc['username'])[0];
                        $tag = explode(' ',$myzc['username'])[1];
                        $sign = $xrpClient->sign($num,$destination,$tag);
                        if (strtolower($sign['result']['status']) == 'success'){
                            $submit = $xrpClient->submit($sign['result']['tx_blob']);
                            if (strtolower($submit['result']['status']) == 'success'){
                                $this->success('转出成功');
                            }else{
                                $this->error('服务器转出失败');
                            }
                        }else{
                            $this->error('服务器转出失败');
                        }
                    }
                }
                if ($sendrs) {
                    $flag = 1;
                    $arr = json_decode($sendrs, true);

                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } else {
                    if ($coin === 'wcg' || $coin === 'drt' || $coin == 'mat' || $coin == 'mtr' || $coin == 'unih' || $coin == 'wos' || $coin=='eqt'){
                        $flag = 1;
                    }else{
                        $flag = 0;
                    }
                }

                if (!$flag) {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('钱包服务器转出币失败!');
                } else {
                    $this->success('转账成功！');
                }
            } else {
                $mo->execute('rollback');
                $mo->execute('unlock tables');
                $this->error('转出失败!' . implode('|', $rs) . $myzc['fee']);
            }
        } elseif ($type === 2) {
            // 非 BOSS 不能撤销
            /*if (session('admin_id') != 11) {
                $this->error('非 BOSS 不能撤销！');
            }*/

            $mo = M();
            if ($myzc['coinname'] == 'btmz'){
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $myzc['userid']))->setInc('btm', $myzc['num']);
            }else{
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $myzc['userid']))->setInc($myzc['coinname'], $myzc['num']);
            }
            $rs[] = $mo->table('weike_myzc')->where(array('id' => trim($id)))->save(array('status' => 2, 'czr' => session("admin_username")));
            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->success('撤销成功！');
            } else {
                $mo->execute('rollback');
                $mo->execute('unlock tables');
                $this->error('撤销失败!');
            }
        }
	}

    //游戏充值奖励
    public function myaward()
    {
        $name = strval(I('name'));
        $field = strval(I('field'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('UserAward')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('UserAward')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //注册奖励，充值奖励，分享奖励
    public function myshare()
    {
        $name = I('name/s');
        $field = I('field/s');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('UserAwardIfc')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('UserAwardIfc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v){
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //注册奖励，充值奖励，分享奖励修改
    public function myshareEdit()
    {
        $id = I('id/d');

        if (empty($_POST)) {
            $_POST = I('post./a');
            if (empty($id)) {
                $this->data = array();
            } else {
                $award_weike = M('UserAwardIfc')->where(array('id' => $id))->find();
                $award_weike['username'] = M('User')->where(['id' => $award_weike['userid']])->getField('username');
                $this->data = $award_weike;
            }
            $this->display();
        } else {
            $_POST = I('post./a');
            $_POST['userid'] = M('User')->where(['username' => $_POST['username']])->getField('id');
            unset($_POST['username']);

            //判断用户是否存在
            if ($_POST['userid'] < 0){
                $this->error('用户不存在!');
            }

            $_POST['addtime'] = time();
            $_POST['czr'] = session('admin_username');

            if ($_POST['id']) {
                $type = M('UserAwardIfc')->where(['id' => $_POST['id']])->getField('type');
                if ($type != 3) {
                    $this->error('请操作分享赠送!');
                }
                $rs = M('UserAwardIfc')->save($_POST);
            } else {
                $_POST['type'] = 3;
                $_POST['status'] = 0;
                $rs = M('UserAwardIfc')->add($_POST);
            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    //注册奖励，充值奖励，分享奖励状态
    public function myshareStatus()
    {
        $id = I('id/d');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        //必须在一月二十五号之后操作
        if (time() < 1516669200){
            $this->error('必须在一月二十三号九点之后操作！');
        }

        //分享赠送  判断总量
        $total = M('Coin')->where(['name' => 'ifc'])->getField('cs_cl');
        $sum = M('UserAwardIfc')->sum('award_num');
        if ($total > $sum) {
            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables weike_user_coin write, weike_user_award_ifc write');
            $rs = array();

            $user_award = $mo->table('weike_user_award_ifc')->where(array('id' => $id))->find();
            $rs[] = $mo->table('weike_user_award_ifc')->where(array('id' => $id))->save(['status' => 1]);
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $user_award['userid']))->setInc($user_award['award_currency'], $user_award['award_num']);

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->success('操作成功！');
            } else {
                $mo->execute('rollback');
                $mo->execute('unlock tables');
                $this->error('操作失败！');
            }
        }
    }

    public function excel(){
        $param = I('post.');
        $param['admin']=session('admin_username');
        //$return = amqp_send_direct_create_csv($request);
        $return = amqp_publich_msg_direct($param);
        die($return);
    }

    public function is_excel(){
    	$name = I('post.name/s');
    	$key = session('admin_username').'_weike_'.$name;
    	if(!get_redis($key)){
    		die(json_encode(array('code'=>400,'msg'=>'excel生成中',data=>'')));
    	}else{
    		    $v = get_redis($key);
    		//if(file_exists($v)){
    			set_redis($key,0);
    			preg_match('/(Public[^\s]++)$/',$v,$arr);
    			die(json_encode(array('code'=>200,'msg'=>'下载',data=>array('url'=>C('csv_server').$arr[0]))));
    		//}else{
    		//	die(json_encode(array('code'=>401,'msg'=>'excel生成中',data=>'')));
    		//}
    	}
    }
	public function exportExcel($expTitle, $expCellName, $expTableData)
	{
		ini_set('memory_limit','1024M');
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Writer.Excel5", '', '.php');
		import("Org.Util.PHPExcel.IOFactory", '', '.php');


		$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
		$fileName = $_SESSION['loginAccount'] . date('_YmdHis');
		$cellNum = count($expCellName);
		$dataNum = count($expTableData);
		vendor("PHPExcel.PHPExcel");
		$objPHPExcel = new \PHPExcel();
		$cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
		$objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', date('Y-m-d H:i:s') . '导出记录');
		$i = 0;

		for (; $i < $cellNum; $i++) {
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][2]);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($cellName[$i])->setWidth(12);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('D')->setWidth(20);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('H')->setWidth(30);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('M')->setWidth(30);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('O')->setWidth(20);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('L')->setWidth(30);
		}

		$i = 0;

		for (; $i < $dataNum; $i++) {
			$j = 0;

			for (; $j < $cellNum; $j++) {
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), (string)$expTableData[$i][$expCellName[$j][0]]);
			}
			unset($expTableData[$i]);
		}

		ob_end_clean();
		header('pragma:public');
		header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
		header('Content-Disposition:attachment;filename=' . $fileName . '.xls');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit();
	}
	//开关充值
	public function myczkg($type){
		if (!session('admin_id')) {
			$this->redirect(__MODULE__.'/Login/index');
		}
		if($type==0){
			$data=array('cz'=>$type);
           $myczkg=M('tx_cz')->where(['id'=>1])->save($data);
			session('myczs',$type);
			if($myczkg){
				$this->success('关闭成功！');
			}
		}elseif($type==1){
			$data=array('cz'=>$type);
			$myczkg=M('tx_cz')->where(['id'=>1])->save($data);
			session('myczs',$type);
			if($myczkg){
				$this->success('开启成功！');
			}
		}
	}

	//开关提现
	public function myuptx($type){
		if (!session('admin_id')) {
			$this->redirect(__MODULE__.'/Login/index');
		}
		if($type==0){
			$data=array('tx'=>$type);
			$myczkg=M('tx_cz')->where(['id'=>1])->save($data);
			if($myczkg){
				$this->success('关闭成功！');
			}
		}elseif($type==1){
			$data=array('tx'=>$type);
			$myczkg=M('tx_cz')->where(['id'=>1])->save($data);
			if($myczkg){
				$this->success('开启成功！');
			}
		}
	}
//  public function weike_financeAllExcel($head, $mark = 'attack_ip_info', $fileName = "test.csv")
//	{
//		set_time_limit(0);
//		$sqlCount = Db::table('ue_subscriber')->count();
//		//输出Excel文件头，可把user.csv换成你要的文件名
//		header('Content-Type: application/vnd.ms-excel;charset=utf-8');
//		header('Content-Disposition: attachment;filename="' . $fileName . '"');
//		header('Cache-Control: max-age=0');
//
//		$sqlLimit = 100000;//每次只从数据库取100000条以防变量缓存太大
//		//每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
//		$limit = 100000;
//		//buffer计数器
//		$cnt = 0;
//		$fileNameArr = array();
//		//逐行取出数据，不浪费内存
//		for ($i = 0; $i < ceil($sqlCount / $sqlLimit); $i++) {
//			$fp = fopen($mark . '_' . $i . '.csv', 'w'); //生成临时文件
//			//chmod('attack_ip_info_' . $i . '.csv',777);//修改可执行权限
//			$fileNameArr[] = $mark . '_' .  $i . '.csv';
//			//将数据通过fputcsv写到文件句柄
//			fputcsv($fp, $head);
//			$dataArr = Db::table('ue_subscriber')->limit($i * $sqlLimit,$sqlLimit)->select();
//			foreach ($dataArr as $a) {
//				$cnt++;
//				if ($limit == $cnt) {
//					//刷新一下输出buffer，防止由于数据过多造成问题
//					ob_flush();
//					flush();
//					$cnt = 0;
//				}
//				fputcsv($fp, $a);
//			}
//			fclose($fp);//每生成一个文件关闭
//		}
//		//进行多个文件压缩
//		$zip = new ZipArchive();
//		$filename = $mark . ".zip";
//		$zip->open($filename, ZipArchive::CREATE);//打开压缩包
//		foreach ($fileNameArr as $file) {
//			$zip->addFile($file, basename($file));//向压缩包中添加文件
//		}
//		$zip->close();//关闭压缩包
//		foreach ($fileNameArr as $file) {
//			unlink($file);//删除csv临时文件
//		}
//		//输出压缩文件提供下载
//		header("Cache-Control: max-age=0");
//		header("Content-Description: File Transfer");
//		header('Content-disposition: attachment; filename=' . basename($filename));
//		header("Content-Type: application/zip");
//		header("Content-Transfer-Encoding: binary");
//		header('Content-Length: ' . filesize($filename));
//		@readfile($filename);//输出文件;
//		unlink($filename); //删除压缩包临时文件
//	}

    public function wcgzc()
    {
        $this->wcglistzc('wcg');
    }
    public function mtrzc(){
        $this->wcglistzc('mtr');
    }

    public function drtzc()
    {
        $this->wcglistzc('drt');
    }

    public function matzc()
    {
        $this->wcglistzc('mat');
    }
    public function unihzc()
    {
        $this->wcglistzc('unih');
    }

    public function wcglistzc($wcg_list_name='wcg')
    {
        $name = strval(I('name'));
        $field = strval(I('field'));
        $coinname = strval(I('coinname'));
        switch ($wcg_list_name){
            case 'wcg':
                $where['coinname'] = 'wcg';
                break;
            case 'drt':
                $where['coinname'] = 'drt';
                break;
            case 'mat':
                $where['coinname'] = 'mat';
                break;
            case 'mtr':
                $where['coinname'] = 'mtr';
                break;
            case 'unih':
                $where['coinname'] = 'unih';
                break;
        }

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $summum = M('Myzc')->where($where)->sum('num');
        $count = M('Myzc')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Myzc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['usernamea'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['truename'] = M('User')->where(array('id' => $v['userid']))->getField('truename');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('wcg_list',$wcg_list_name);
        $this->assign('summum', $summum);
        $this->assign('count', $count);
        $this->display('wcgListzc');
    }

    //验证交易密码
    public function validate_trade_pass(){
        $trade_pass = md5(trim($_POST['trade_pass']));
        $valid_trade_pass = M('Admin')->where(['id'=>UID])->getField('trade_password');
        if(!$valid_trade_pass) $this->error('验证失败，您还没有设置交易密码');
        if($trade_pass == $valid_trade_pass){
            session('trade_pass',true);
            $this->success('交易密码验证成功！');
        }else{
            $this->error('交易密码错误，请重新输入！');
        }
    }
    //抽奖记录
	public function atc($addtime='',$endtime=''){
		$name = strval(I('name'));
		$where = array();
		if ($name) {
			$where['uid'] = M('User')->where(array('username' => $name))->getField('id');
		}
		$addtime ? $addtime = urldecode($addtime):false;
		$endtime ? $endtime = urldecode($endtime):false;

		if ($addtime && $endtime){
			//转换时间戳
			$addtime = strtotime($addtime);
			$endtime = strtotime($endtime);
			//条件
			$where['addtime'] = array(array('gt',$addtime),array('lt',$endtime)) ;

		}
		$status=I('status');
		$this->assign('status',$status);
		if($status!=''){
			$where['xm_status'] = $status;
		}
		$count = M('act_xm_prize')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('act_xm_prize')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v){
			$list[$k]['truename'] = M('User')->where(['id' => $v['uid']])->getField('truename');
			$list[$k]['usernamea'] = M('User')->where(['id' => $v['uid']])->getField('username');
		}
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
}

?>