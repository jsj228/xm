<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;
use Common\Ext\BtmClient;
class Finance extends AdminCommon
{
	public function index()
	{
        $name =strval(input('name'));
        $field = strval(input('field'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		$list = DB::name('Finance')->where($where)->order('id desc')->paginate(15);
		$page = $list->render();

		$list=$list->all();
		$name_list = array('mycz' => 'CNY充值', 'mytx' => '提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');
		$nameid_list = array('mycz' => url('Mycz/index'), 'mytx' => url('Mytx/index'), 'trade' => url('Trade/index'), 'tradelog' => url('Tradelog/index'), 'issue' => url('Issue/index'));
         
		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
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
			$list[$k]['addtime'] =date('Y-m-d H:i:s',$v['addtime']);
		}
 		
	
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}

	public function mycz()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = DB::name('Mycz')->where($where)->count();
        $weike_sum = DB::name('Mycz')->where($where)->sum('num');
        $weike_num = DB::name('Mycz')->where($where)->sum('mum');
		// $Page = new \Think\Page($count, 15);
		// $show = $Page->show();
		$list = DB::name('Mycz')->where($where)->order('id desc')->paginate(15);
		$page = $list->render();
		$list=$list->all();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
			if ($v['type'] === 'overseas'){
                $list[$k]['type'] = '海外支付宝支付';
            } else {
                $list[$k]['type'] = DB::name('MyczType')->where(array('name' => $v['type']))->value('title');
            }
            //随机网银判断
            $list[$k]['bankcard'] = DB::name('MyczType')->where(array('id' => $v['bank_id']))->value('username');
		}
		$this->assign('list', $list);
		$this->assign('page', $page);
        $this->assign('weike_count', $count);
        $this->assign('weike_sum', $weike_sum);
        $this->assign('weike_num', $weike_num);
		return $this->fetch();
	}

	public function myczStatus()
	{
        $id = input('post.');
        foreach ($id as $key => $value) {
        	$ids=implode(',',$value);
        }
        $type = input('type');

        $moble = input('moble','Mycz');
		if (empty($ids)) {
			$this->error('参数错误！');
		}
 		
		if (empty($type)) {
			$this->error('参数错误1！');
		}
         
		$where['id'] = array('in', $ids);

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
			if (DB::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败1！');
		}

		if (DB::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}

		public function myzrQueren()
	{
		$id = input('id');
        $type = input('type');
        $tradeid = input('tradeid');
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$myzr = DB::name('Myzr')->where(array('id' => input('id')))->find();

		if (($myzr['status'] != 0)) {
			$this->error('已经处理，禁止再次操作！');
		}

		//华克金，交易 ID 判断
        if ($myzr['coinname'] === 'wcg' && $type === 1){
		    if ($tradeid !== $myzr['tradeid']) {
                $this->error('请输入正确的交易ID！');
            }
        }
    
        if ($type == 1) {
            $mo = DB::name();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables weike_user_coin write,weike_myzr write,weike_finance write,weike_invit write,weike_user write');
            $rs = [];

            $rs[] = $mo->table('weike_user_coin')->where(['userid' => $myzr['userid']])->setInc($myzr['coinname'], $myzr['num']);
            $rs[] = $mo->table('weike_myzr')->where(['id' => $myzr['id']])->update(['status' => 1, 'mum' => $myzr['num'], 'endtime' => time(), 'czr' => session('admin_username')]);

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->success('处理成功');
            } else {
                $mo->execute('rollback');
                $this->error('操作失败！');
            }
        } elseif($type == 2) {
     
            // 非 BOSS 不能撤销
            if (session('admin_id') != 11) {
                $this->error('非 BOSS 不能撤销！');
            }

            $mo = DB::name();
            $rs = $mo->table('weike_myzr')->where(['id' => $myzr['id']])->update(['status' => 2, 'mum' => 0, 'endtime' => time(), 'czr' => session('admin_username')]);
            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->success('处理成功');
            } else {
                $mo->execute('rollback');
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
		$id = intval(input('id'));

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mycz = DB::name('Mycz')->where(array('id' => input('id')))->find();

		if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
			$this->error('已经处理，禁止再次操作！');
		}

		$mo = DB::name();
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

		$rs[] = $mo->table('weike_finance')->insert(array('userid' => $mycz['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '港币充值-人工到账', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
		
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
        $rs[] = $mo->table('weike_mycz')->where(array('id' => $mycz['id']))->update(array('status' => 2, 'mum' => $mycz['num'], 'endtime' => time(), 'czr' => session('admin_username')));

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

				$rs[] = $mo->table('weike_finance')->insert(array('userid' => $cur_user_info['invit_1'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_1['cny'], 'num_b' => $finance_num_user_coin_1['cnyd'], 'num' => $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => '港币充值-一代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_1_jiner.'元', 'mum_a' => $finance_mum_user_coin_1['cny'], 'mum_b' => $finance_mum_user_coin_1['cnyd'], 'mum' => $finance_mum_user_coin_1['cny'] + $finance_mum_user_coin_1['cnyd'], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1));
				
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

				$rs[] = $mo->table('weike_finance')->insert(array('userid' => $cur_user_info['invit_2'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_2['cny'], 'num_b' => $finance_num_user_coin_2['cnyd'], 'num' => $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => '港币充值-二代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_2_jiner.'元', 'mum_a' => $finance_mum_user_coin_2['cny'], 'mum_b' => $finance_mum_user_coin_2['cnyd'], 'mum' => $finance_mum_user_coin_2['cny'] + $finance_mum_user_coin_2['cnyd'], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2));
				
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
				$rs[] = $mo->table('weike_invit')->insert(array('userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '三代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1));
			
				//处理后
				$finance_mum_user_coin_3 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();
				$finance_hash_3 = md5($cur_user_info['invit_3'].$finance_num_user_coin_3['cny'] . $finance_num_user_coin_3['cnyd'] . $invit_3_jiner . $finance_mum_user_coin_3['cny'] . $finance_mum_user_coin_3['cnyd'] . MSCODE . 'auth.weike.com');
				$finance_num_3 = $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'];

				if ($finance_3['mum'] < $finance_num_3) {
					$finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
				} else {
					$finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
				}

				$rs[] = $mo->table('weike_finance')->insert(array('userid' => $cur_user_info['invit_3'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_3['cny'], 'num_b' => $finance_num_user_coin_3['cnyd'], 'num' => $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => '港币充值-三代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_3_jiner.'元', 'mum_a' => $finance_mum_user_coin_3['cny'], 'mum_b' => $finance_mum_user_coin_3['cnyd'], 'mum' => $finance_mum_user_coin_3['cny'] + $finance_mum_user_coin_3['cnyd'], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3));
				
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
        $id = intval(input('id'));

        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $mycz = DB::name('Mycz')->where(array('id' => input('id')))->find();

        if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
            $this->error('已经处理，禁止再次操作！');
        }

        $sxfei = DB::name('MyczType')->where(['name'=>$mycz['type']])->value('sxfei');
        $mo = DB::name();
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

        $rs[] = $mo->table('weike_finance')->insert(array('userid' => $mycz['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => '港币充值-人工到账', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

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
        $rs[] = $mo->table('weike_mycz')->where(array('id' => $mycz['id']))->update(array('status' => 5, 'mum' => $dzjine, 'endtime' => time(), 'czr' => session('admin_username')));

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
                $rs[] = $mo->table('weike_invit')->insert(array('userid' => $cur_user_info['invit_1'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '一代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_1_jiner, 'addtime' => time(), 'status' => 1));

                //处理后
                $finance_mum_user_coin_1 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_1']))->find();
                $finance_hash_1 = md5($cur_user_info['invit_1'].$finance_num_user_coin_1['cny'] . $finance_num_user_coin_1['cnyd'] . $invit_1_jiner . $finance_mum_user_coin_1['cny'] . $finance_mum_user_coin_1['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num_1 = $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'];

                if ($finance_1['mum'] < $finance_num_1) {
                    $finance_status_1 = (1 < ($finance_num_1 - $finance_1['mum']) ? 0 : 1);
                } else {
                    $finance_status_1 = (1 < ($finance_1['mum'] - $finance_num_1) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->insert(array('userid' => $cur_user_info['invit_1'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_1['cny'], 'num_b' => $finance_num_user_coin_1['cnyd'], 'num' => $finance_num_user_coin_1['cny'] + $finance_num_user_coin_1['cnyd'], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => '港币充值-一代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_1_jiner.'元', 'mum_a' => $finance_mum_user_coin_1['cny'], 'mum_b' => $finance_mum_user_coin_1['cnyd'], 'mum' => $finance_mum_user_coin_1['cny'] + $finance_mum_user_coin_1['cnyd'], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1));

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
                $rs[] = $mo->table('weike_invit')->insert(array('userid' => $cur_user_info['invit_2'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '二代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_2_jiner, 'addtime' => time(), 'status' => 1));

                //处理后
                $finance_mum_user_coin_2 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_2']))->find();
                $finance_hash_2 = md5($cur_user_info['invit_2'].$finance_num_user_coin_2['cny'] . $finance_num_user_coin_2['cnyd'] . $invit_2_jiner . $finance_mum_user_coin_2['cny'] . $finance_mum_user_coin_2['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num_2 = $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'];

                if ($finance_2['mum'] < $finance_num_2) {
                    $finance_status_2 = (1 < ($finance_num_2 - $finance_2['mum']) ? 0 : 1);
                } else {
                    $finance_status_2 = (1 < ($finance_2['mum'] - $finance_num_2) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->insert(array('userid' => $cur_user_info['invit_2'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_2['cny'], 'num_b' => $finance_num_user_coin_2['cnyd'], 'num' => $finance_num_user_coin_2['cny'] + $finance_num_user_coin_2['cnyd'], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => '港币充值-二代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_2_jiner.'元', 'mum_a' => $finance_mum_user_coin_2['cny'], 'mum_b' => $finance_mum_user_coin_2['cnyd'], 'mum' => $finance_mum_user_coin_2['cny'] + $finance_mum_user_coin_2['cnyd'], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2));

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
                $rs[] = $mo->table('weike_invit')->insert(array('userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => 'cny', 'type' => '三代充值奖励', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1));

                //处理后
                $finance_mum_user_coin_3 = $mo->table('weike_user_coin')->where(array('userid' => $cur_user_info['invit_3']))->find();
                $finance_hash_3 = md5($cur_user_info['invit_3'].$finance_num_user_coin_3['cny'] . $finance_num_user_coin_3['cnyd'] . $invit_3_jiner . $finance_mum_user_coin_3['cny'] . $finance_mum_user_coin_3['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num_3 = $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'];

                if ($finance_3['mum'] < $finance_num_3) {
                    $finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
                } else {
                    $finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
                }

                $rs[] = $mo->table('weike_finance')->insert(array('userid' => $cur_user_info['invit_3'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin_3['cny'], 'num_b' => $finance_num_user_coin_3['cnyd'], 'num' => $finance_num_user_coin_3['cny'] + $finance_num_user_coin_3['cnyd'], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => '港币充值-三代充值奖励-充值ID'.$mycz['userid'].',订单'.$mycz['tradeno'].',金额'.$cz_jiner.'元,奖励'.$invit_3_jiner.'元', 'mum_a' => $finance_mum_user_coin_3['cny'], 'mum_b' => $finance_mum_user_coin_3['cnyd'], 'mum' => $finance_mum_user_coin_3['cny'] + $finance_mum_user_coin_3['cnyd'], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3));

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
        $id = input('post.id');
        $text = input('post.text');
        if (empty($id)) {
            die(json_encode(array('code'=>401,'msg'=>'请选择要操作的数据!','data'=>[])));
        }
        if (empty($text)) {
            die( json_encode(array('code'=>402,'msg'=>'请填写备注','data'=>[])));
        }
        $data = DB::name('Mycz')->where(['id' => $id])->update(['beizhu'=> $text]);
        if ($data){
            die(json_encode(array('code'=>200,'msg'=>'备注成功','data'=>[])));
        }else{
            die(json_encode(array('code'=>403,'msg'=>'备注失败','data'=>[])));
        }
    }

	public function myczType()
	{
		$where = array();
		$list = DB::name('MyczType')->where($where)->order('id desc')->paginate(15);
		$page = $list->render();
		$list = $list->all();
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch('mycztype');
	}

	public function myczTypeEdit($id = NULL)
	{
        $id = input('id');
        $_POST = input('post.');
		if (empty($_POST)) {
			if ($id) {
				$this->data = DB::name('MyczType')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch('mycztypeedit');
		} else {
			if ($_POST['id']) {
				$rs = DB::name('MyczType')->update($_POST);
			} else {
				$rs = DB::name('MyczType')->insert($_POST);
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
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

	public function myczTypeStatus()
	{
        $id = input('post.');
        foreach ($id as $key => $value) {
        	$ids=implode(',',$value);
        }
        $type = input('type');
        $moble = input('moble','MyczType');
	    if (empty($ids)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		$where['id'] = array('in', $ids);

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
			if (DB::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败1！');
		}

		if (DB::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}
	
	public function mytx()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}
        $summum = DB::name('Mytx')->where($where)->sum('num');

		$list = DB::name('Mytx')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
			$list[$k]['truename'] = DB::name('User')->where(array('id' => $v['userid']))->value('truename');
		}

		$this->assign('list', $list);
		$this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('summum', $summum);
		return $this->fetch();
	}
    
	public function mytxStatus()
	{
        $id = input('post.');
        foreach ($id as $key => $value) {
        	$ids=implode(',',$value);
        }
        $type = input('type');
        $moble = input('moble','Mytx');
		if (empty($ids)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}
		$where['id'] = array('in', $ids);

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
			if (DB::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败1！');
		}

		if (DB::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}

	public function mytxChuli()
	{
		$id = input('id');
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		if (DB::name('Mytx')->where(array('id' => $id))->update(array('status' => 3))) {
			return array('status'=>1,'msg'=>'操作成功！');
			// $this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
     //未修复
	public function mytxChexiao()
	{
		$id = input('id');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mytx = DB::name('Mytx')->where(array('id' => trim($id)))->find();
		$mo = DB::name();
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

		$rs[] = $mo->table('weike_finance')->insert(array('userid' => $mytx['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => '港币提现-撤销提现', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

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
        $id = input('id/d');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		if (DB::name('Mytx')->where(array('id' => $id))->update(array('status' => 1, 'czr' => session('admin_username')))) {
			return array('status'=>1,'msg'=>'操作成功！');
			// $this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function mytxExcel()
	{
        $_POST = input('post.');
        $_GET = input('get.');
		if ($this->request->isPost()) {
			$id = implode(',', $_POST['id']);
		} else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$list = DB::name('Mytx')->where($where)->select();

		foreach ($list as $k => $v) {
			$list[$k]['userid'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
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

		$zd = DB::name('Mytx')->getTableFields();
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
        $_GET = input('get.');

		if ($this->request->isPost()) {
			$id = implode(',', $_POST['id']);
		} else {
			$id = intval($_GET['id']);
		}
         // dump($id);die;
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
			
		$where['id'] = array('in', $id);
		$list = DB::name('Finance')->where($where)->select();
		$name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');
		
		foreach ($list as $k => $v) {
			$list[$k]['userid'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
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
		$list = DB::name('Finance')->select();
		$name_list = array('mycz' => '港币充值', 'mytx' => '港币提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购');

		foreach ($list as $k => $v) {
			$list[$k]['userid'] = DB::name('User')->field()->where(array('id' => $v['userid']))->value('username');
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

			return $this->fetch('mytxconfig');
	
		} 
		$data=array(
          'mytx_min'=>$_POST['mytx_min'],
          'mytx_max'=>$_POST['mytx_max'],
          'mytx_bei'=>$_POST['mytx_bei'],
          'mytx_fee'=>$_POST['mytx_fee'],
		);

		$tx=DB::name('config')->where(array('id' => 1))->update($data);

		if ($tx) {
			return array('status'=>1,'msg'=>'修改成功！');
			// $this->success('修改成功！');
		} else {
		
			$this->error('修改失败');
		}
		
	}

		public function myzr()
	{
        $name = input('name');
        $field = input('field');
        $coinname = input('coinname');
        $address = input('address');
        $tradeid = input('tradeid');
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else if ($field == 'sb_code') {
			    //添加识别码搜索
                $where['userid'] = DB::name('Myzr')->where(array('tradeno' => $name))->value('userid');
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

		$num = DB::name('Myzr')->where($where)->sum('mum');
		$list = DB::name('Myzr')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $page);
        $this->assign('num', $num);
        $this->assign('count', $count);
		return $this->fetch();
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
        $id = input('id');

        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = array();
            } else {
                $myzr_weike = DB::name('Myzr')->where(array('id' => $id))->find();
                $this->data = $myzr_weike;
            }
            return $this->fetch('myzredit');
        } else {
            $_POST = input('post.');
            $_POST['userid'] = DB::name('User')->where(['username' => $_POST['userid']])->value('id');
            if(!$_POST['userid']){
                $this->error('用户不存在!');
            }
            $_POST['addtime'] = time();
            $_POST['czr'] = session('admin_username');
            $_POST['mum'] = $_POST['num'];

            if ($_POST['id']) {
                $rs = DB::name('Myzr')->update($_POST);
            } else {
                $rs = DB::name('Myzr')->insert($_POST);
            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

	public function myzc()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $coinname = strval(input('coinname'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}
        $summum = DB::name('Myzc')->where($where)->sum('num');
		$count = DB::name('Myzc')->where($where)->count();


		$list = DB::name('Myzc')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();

		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
			$list[$k]['truename'] = DB::name('User')->where(array('id' => $v['userid']))->value('truename');
		}

		$this->assign('list', $list);
		$this->assign('page', $page);
        $this->assign('summum', $summum);
        $this->assign('count', $count);
		return $this->fetch();
	}

	public function myzcQueren()
	{
        $id = input('get.id');
        $pass = input('get.pass');
        $type = input('get.type');

		$myzc = DB::name('Myzc')->where(array('id' => trim($id)))->find();

		if (!$myzc) {
			$this->error('转出错误！');
		}

		if ($myzc['status'] != 0) {
			$this->error('已经处理过！');
		}

        if ($type == 1) {
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
            $dj_username = config('coin')[$coin]['dj_yh'];
            $dj_password = config('coin')[$coin]['dj_mm'];
            $dj_address = config('coin')[$coin]['dj_zj'];
            $dj_port = config('coin')[$coin]['dj_dk'];


            if (config('coin')[$coin]['type'] == 'bit') {

                $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);

                $json = $CoinClient->getinfo();

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

                $EosClient = EosClient($dj_address, $dj_port);
                $json = $EosClient->get_info();
                if (!$json) {
                    $this->error('钱包对接失败!');
                }
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
                if ($coin !== 'wcg' && $coin !== 'btmz' && $coin !== 'drt') {
                    $this->error('钱包对接失败！');
                }
            }

            $Coin = DB::name('Coin')->where(array('name' => $myzc['coinname']))->find();
            if(!$Coin['zc_user']){
                $this->error('官方手续费地址为空！');
            }


            if ($Coin['type'] == 'token' && !$Coin['token_address']) {
                $this->error('合同地址为空!');
            }

            if ($Coin['type'] == 'token' && !$Coin['decimals']) {
                $this->error('合同位数为空!');
            }

            $fee_user = DB::name('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = DB::name('UserCoin')->where(array('userid' => $myzc['userid']))->find();
            $zhannei = DB::name('UserCoin')->where(array($coin . 'b' => $myzc['username']))->find();

//            $mo = DB::name();
            Db::execute('set autocommit=0');
//            return 999;
//            Db::execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzr write , weike_myzc_fee write');
            Db::execute('LOCK TABLES weike_user_coin write,weike_myzc write  , weike_myzr write , weike_myzc_fee write');

            return 999;
            $rs = array();
            if ($zhannei) {
                $rs[] = DB::table('weike_myzr')->insert(array('userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1));
                if ($coin == 'btmz'){
                    $rs[] = $r = DB::table('weike_user_coin')->where(array('userid' => $zhannei['userid']))->setInc('btm', $myzc['mum']);
                }else{
                    $rs[] = $r = DB::table('weike_user_coin')->where(array('userid' => $zhannei['userid']))->setInc($coin, $myzc['mum']);
                }
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = DB::table('weike_myzc_fee')->insert(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if (DB::table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    if ($coin == 'btmz'){
                        $rs[] = DB::table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->setInc('btm', $myzc['fee']);
                    }else{
                        $rs[] = DB::table('weike_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    }
                    //debug(array('lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                } else {
                    if ($coin == 'btmz'){
                        $rs[] = DB::table('weike_user_coin')->insert(array($coin . 'b' => $Coin['zc_user'], 'btm' => $myzc['fee']));
                    }else{
                        $rs[] = $mo->table('weike_user_coin')->insert(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                    }
                }
            }

            $rs[] = DB::table('weike_myzc')->where(array('id' => trim($id)))->update(array('status' => 1, 'czr' => session("admin_username")));

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
                    $btmzData = DB::name('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
                    if ($btmzData) {
                        $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                        $res = $btmClient->outcome($myzc['username'],$myzc['mum']);
                        if ($res){
                            $sigRes = $btmClient->signTransaction($res);
                            if ($sigRes){
                                $resSub = $btmClient->submitTransaction($sigRes);
                                if ($resSub){
                                    DB::name('myzc')->where(array('id' => trim($id)))->update(['status'=>1,'txid'=>$resSub['tx_id']]);
                                    $mo->execute('commit');
                                    $this->success('提交成功');
                                    exit();
                                }else{
                                    DB::name('myzc')->where(array('id' => trim($id)))->update(['status'=>0]);
                                    $mo->execute('commit');
                                    $this->error('提交失败');
                                    exit();
                                }
                            }else{
                                DB::name('myzc')->where(array('id' => trim($id)))->update(['status'=>0]);
                                $mo->execute('commit');
                                $this->error('提交签名失败');
                                exit();
                            }
                        }else{
                            DB::name('myzc')->where(array('id' => trim($id)))->update(['status'=>0]);
                            $mo->execute('commit');
                            $this->error('提交转出失败');
                            exit();
                        }
                    }else{
                        DB::name('myzc')->where(array('id' => trim($id)))->update(['status'=>0]);
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
                }
                if ($sendrs) {
                    $flag = 1;
                    $arr = json_decode($sendrs, true);

                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } else {
                    if ($coin === 'wcg' || $coin === 'drt'){
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
        } elseif ($type == 2) {
            // 非 BOSS 不能撤销
            /*if (session('admin_id') != 11) {
                $this->error('非 BOSS 不能撤销！');
            }*/

            $mo = DB::name();
            if ($myzc['coinname'] == 'btmz'){
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $myzc['userid']))->setInc('btm', $myzc['num']);
            }else{
                $rs[] = DB::table('weike_user_coin')->where(array('userid' => $myzc['userid']))->setInc($myzc['coinname'], $myzc['num']);
            }
            $rs[] = DB::table('weike_myzc')->where(array('id' => trim($id)))->update(array('status' => 2, 'czr' => session("admin_username")));
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
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $list = DB::name('UserAward')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k => $v) {

            $list[$k]['username'] = DB::name('user')->where(array('id'=>$v['userid']))->value('username');
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    //注册奖励，充值奖励，分享奖励
    public function myshare()
    {
        $name = input('name/s');
        $field = input('field/s');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        // $count = M('UserAwardIfc')->where($where)->count();
        // $Page = new \Think\Page($count, 15);
        // $show = $Page->show();
        $list = DB::name('UserAwardIfc')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        foreach ($list as $k => $v){
            $list[$k]['username'] = DB::name('User')->where(['id' => $v['userid']])->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //注册奖励，充值奖励，分享奖励修改
    public function myshareEdit()
    {
        $id = input('id/d');

        if (empty($_POST)) {
            $_POST = input('post./a');
            if (empty($id)) {
                $this->data = array();
            } else {
                $award_weike = DB::anme('UserAwardIfc')->where(array('id' => $id))->find();
                $award_weike['username'] = DB::anme('User')->where(['id' => $award_weike['userid']])->value('username');
                $this->data = $award_weike;
            }
            return $this->fetch();
        } else {
            $_POST = input('post./a');
            $_POST['userid'] = DB::anme('User')->where(['username' => $_POST['username']])->value('id');
            unset($_POST['username']);

            //判断用户是否存在
            if ($_POST['userid'] < 0){
                $this->error('用户不存在!');
            }

            $_POST['addtime'] = time();
            $_POST['czr'] = session('admin_username');

            if ($_POST['id']) {
                $type = DB::anme('UserAwardIfc')->where(['id' => $_POST['id']])->value('type');
                if ($type != 3) {
                    $this->error('请操作分享赠送!');
                }
                $rs = DB::anme('UserAwardIfc')->update($_POST);
            } else {
                $_POST['type'] = 3;
                $_POST['status'] = 0;
                $rs = DB::anme('UserAwardIfc')->insert($_POST);
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
        $id = input('id/d');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        //必须在一月二十五号之后操作
        if (time() < 1516669200){
            $this->error('必须在一月二十三号九点之后操作！');
        }

        //分享赠送  判断总量
        $total = DB::anme('Coin')->where(['name' => 'ifc'])->value('cs_cl');
        $sum = DB::anme('UserAwardIfc')->sum('award_num');
        if ($total > $sum) {
            $mo = DB::anme();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables weike_user_coin write, weike_user_award_ifc write');
            $rs = array();

            $user_award = $mo->table('weike_user_award_ifc')->where(array('id' => $id))->find();
            $rs[] = $mo->table('weike_user_award_ifc')->where(array('id' => $id))->update(['status' => 1]);
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
        $param = input('post.');
        $param['admin']=session('admin_username');
        //$return = amqp_send_direct_create_csv($request);
        $return = amqp_publich_msg_direct($param);
        die($return);
    }

    public function is_excel(){
    	$name = input('post.name/s');
    	$key = session('admin_username').'_weike_'.$name;
    	if(!get_redis($key)){
    		die(json_encode(array('code'=>400,'msg'=>'excel生成中',data=>'')));
    	}else{
    		    $v = get_redis($key);
    		//if(file_exists($v)){
    			set_redis($key,0);
    			preg_match('/(Public[^\s]++)$/',$v,$arr);
    			die(json_encode(array('code'=>200,'msg'=>'下载',data=>array('url'=>config('csv_server').$arr[0]))));
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
		vendor("PHPExcelClass.PHPExcel");
        include ROOT_PATH."thinkphp/library/vendor/PHPExcel/PHPExcel.php";
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
        exit();
		$objWriter->update('php://output');
		exit();
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

}

?>