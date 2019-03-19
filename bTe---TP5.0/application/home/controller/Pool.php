<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Pool extends HomeCommon
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
			redirect('/#login');
		}

		if (IS_POST) {
			$input = input('post./a');

			if (!check($input['num'], 'd')) {
				$this->error('购买数量格式错误！');
			}

			if ($input['num'] < 1) {
				$this->error('购买数量错误！');
			}

			if (!check($input['id'], 'd')) {
				$this->error('矿机类型格式错误！');
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			if (md5($input['paypassword']) != $user['paypassword']) {
				$this->error('交易密码错误！');
			}

			$pool = Db::name('Pool')->where(['id' => $input['id']])->find();

			if (!$pool) {
				$this->error('矿机类型错误！');
			}

			if ($pool['status'] != 1) {
				$this->error('当前矿机没有开通购买！');
			}

			$mum = round($pool['price'] * $input['num'], 6);

			if ($user['coin'][config::get('rmb_mr')] < $mum) {
				$this->error('可用人民币余额不足');
			}

			$poolLog = Db::name('PoolLog')->where(['userid' => $user['id'], 'name' => $pool['name']])->sum('num');

			if ($pool['limit']) {
				if ($pool['limit'] < ($poolLog + $input['num'])) {
					$this->error('购买总数量超过限制！');
				}
			}

			$mo = Db::name('');
			$mo->startTrans();
			try{
                $mo->table('weike_user_coin')->where(['userid' => $user['id']])->setDec(config('rmb_mr'), $mum);
                $mo->table('weike_pool_log')->insert([
                    'userid' => $user['id'],
                    'coinname' => $pool['coinname'],
                    'name' => $pool['name'],
                    'ico' => $pool['ico'],
                    'price' => $pool['price'],
                    'num' => $input['num'],
                    'tian' => $pool['tian'],
                    'power' => $pool['power'],
                    'endtime' => time(),
                    'addtime' => time(),
                    'status' => 0
                ]);
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
			    $flag = false;
			    $mo->rollback();
            }


			if ($flag) {
				$this->success('购买成功！');
			} else {
				$this->error('购买失败！');
			}
		} else {
			$this->get_text();
			$list = Db::name('Pool')->where(['status' => 1])->select();
			$this->assign('list', $list);
			return $this->fetch();
		}
	}

	public function log()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$user = $this->User();
		$where['status'] = array('egt', 0);
		$where['userid'] = $user['id'];
		import('ORG.Util.Page');
		$Model = Db::name('PoolLog');
		$page = $list->render();
		$list = $Model->where($where)->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}

	public function startpool()
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (IS_POST) {
			$input = input('post./a');

			if (!check($input['id'], 'd')) {
				$this->error('请选择要工作的矿机！');
			}

			$poolLog = Db::name('PoolLog')->where(['id' => $input['id']])->find();

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

			$mum = round($poolLog['price'] * $poolLog['num'], 6);
			$mo = Db::name('');
			$mo->startTrans();
			try{
                Db::table('weike_pool_log')->where(['id' => $poolLog['id']])->save(['endtime' => time(), 'status' => 1]);
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
			    $flag = false;
			    $mo->rollback();
            }

			if ($flag) {
				$this->success('矿机已经开始工作！');
			} else {
				$this->error( '矿机工作失败！');
			}
		}
	}

	public function receiving()
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (IS_POST) {
			$input = input('post./a');

			if (!check($input['id'], 'd')) {
				$this->error('请选择要收矿的矿机！');
			}

			$poolLog = Db::name('PoolLog')->where(['id' => $input['id']])->find();

			if (!$poolLog) {
				$this->error('参数错误！');
			}

			if ($poolLog['tian'] <= $poolLog['use']) {
				$this->error('非法访问！');
			}

			$tm = $poolLog['endtime'] + (60 * 60 * config::get('pool_jian'));

			if (time() < $tm) {
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			if ($poolLog['userid'] != $user['id']) {
				$this->error('非法访问');
			}

			$mo = Db::name('');
			$mo->startTrans();
			try{
                $num = round($poolLog['num'] * config::get('pool_suan') * $poolLog['power'], 6);
                Db::table('weike_user_coin')->where(['userid' => $user['id']])->setInc($poolLog['coinname'], $num);
                Db::table('weike_pool_log')->where(['id' => $poolLog['id']])->update(['use' => $poolLog['use'] + 1, 'endtime' => time()]);

                if ($poolLog['tian'] <= $poolLog['use'] + 1) {
                   Db::table('weike_pool_log')->where(['id' => $poolLog['id']])->update(['status' => 2]);
                }
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
			    $flag = false;
			    $mo->rollback();
            }


			if ($flag) {
				$this->success('收矿成功！获得' . $num . '个币');
			} else {
				$this->error('收矿失败！');
			}
		}
	}
}

?>