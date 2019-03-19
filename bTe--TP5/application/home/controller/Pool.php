<?php
namespace app\home\controller;

use think\Db;
use think\Exception;

class Pool extends Home
{
	public function __construct()
	{
		parent::__construct();
		$this->title = '集市交易';
		exit();
	}

	public function index()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		if (IS_POST) {
			$input_val = input('post.');

			if (!check($input_val['num'], 'd')) {
				$this->error('购买数量格式错误！');
			}

			if ($input_val['num'] < 1) {
				$this->error('购买数量错误！');
			}

			if (!check($input_val['id'], 'd')) {
				$this->error('矿机类型格式错误！');
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			if (md5($input_val['paypassword']) != $user['paypassword']) {
				$this->error('交易密码错误！');
			}
			$pool = Db::name('Pool')->where(array('id' => $input_val['id']))->find();

			if (!$pool) {
				$this->error('矿机类型错误！');
			}

			if ($pool['status'] != 1) {
				$this->error('当前矿机没有开通购买！');
			}

			$mum = round($pool['price'] * $input_val['num'], 6);

			if ($user['coin'][config('rmb_mr')] < $mum) {
				$this->error('可用人民币余额不足');
			}

			$poolLog = Db::name('PoolLog')->where(array('userid' => $user['id'], 'name' => $pool['name']))->sum('num');

			if ($pool['limit']) {
				if ($pool['limit'] < ($poolLog + $input_val['num'])) {
					$this->error('购买总数量超过限制！');
				}
			}

            Db::startTrans();
			try {
                
                $rs =[];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $user['id']))->setDec(config('rmb_mr'), $mum);
                $rs[] = Db::table('weike_pool_log')->insert(array('userid' => $user['id'], 'coinname' => $pool['coinname'], 'name' => $pool['name'], 'ico' => $pool['ico'], 'price' => $pool['price'], 'num' => $input_val['num'], 'tian' => $pool['tian'], 'power' => $pool['power'], 'endtime' => time(), 'addtime' => time(), 'status' => 0));

                if (check_arr($rs)) {
                    Db::commit();
                    $this->success('购买成功！');
                } else {
                    Db::rollback();
                    $this->error('购买失败！');
                }
            }catch (Exception $e){
			    Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('购买失败！');
            }
		}
		else {
			$this->get_text();
			$list = Db::name('Pool')->where(array('status' => 1))->select();
			$this->assign('list', $list);
			return $this->fetch();
		}
	}

	public function log()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$user = $this->User();
		$where['status'] = array('egt', 0);
		$where['userid'] = $user['id'];
		import('ORG.Util.Page');
		$list = Db::name('PoolLog')->where($where)->order('id desc')->paginate(10);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function startpool()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		if (IS_POST) {
			$input_val = input('post.');

			if (!check($input_val['id'], 'd')) {
				$this->error('请选择要工作的矿机！');
			}

			$poolLog = Db::name('PoolLog')->where(array('id' => $input_val['id']))->find();

			if (!$poolLog) {
				$this->error('参数错误！');
			}

			if ($poolLog['status']) {
				$this->error('访问错误！');
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			if ($poolLog['userid'] != $user['id']) {
				$this->error('非法访问');
			}


			$rs = Db::table('weike_pool_log')->where(array('id' => $poolLog['id']))->update(array('endtime' => time(), 'status' => 1));

			if (false !== $rs) {
				$this->success('矿机已经开始工作！');
			} else {
				$this->error(APP_DEBUG ? implode('|', $rs) : '矿机工作失败！');
			}
		}
	}

	public function receiving()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		if (IS_POST) {
			$input_val = input('post.');

			if (!check($input_val['id'], 'd')) {
				$this->error('请选择要收矿的矿机！');
			}

			$poolLog = Db::name('PoolLog')->where(array('id' => $input_val['id']))->find();

			if (!$poolLog) {
				$this->error('参数错误！');
			}

			if ($poolLog['tian'] <= $poolLog['use']) {
				$this->error('非法访问！');
			}

			$tm = $poolLog['endtime'] + (60 * 60 * config('pool_jian'));

			if (time() < $tm) {
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			if ($poolLog['userid'] != $user['id']) {
				$this->error('非法访问');
			}

            Db::startTrans();
			try {
                $rs =[];
                $num = round($poolLog['num'] * config('pool_suan') * $poolLog['power'], 6);
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $user['id']))->setInc($poolLog['coinname'], $num);
                $rs[] = Db::table('weike_pool_log')->where(array('id' => $poolLog['id']))->update(array('use' => $poolLog['use'] + 1, 'endtime' => time()));

                if ($poolLog['tian'] <= $poolLog['use'] + 1) {
                    $rs[] = Db::table('weike_pool_log')->where(array('id' => $poolLog['id']))->update(array('status' => 2));
                }

                if (check_arr($rs)) {
                    Db::commit();
                    $this->success('收矿成功！获得' . $num . '个币');
                } else {
                    Db::rollback();
                    $this->error('收矿失败！');
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('收矿失败！');
            }
		}
	}
}

?>