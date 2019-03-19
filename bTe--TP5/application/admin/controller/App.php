<?php
namespace app\admin\controller;

use think\Db;

class App extends Admin
{
	public function __construct()
	{
		parent::__construct();
        $this->redirect(__MODULE__.'/');
	}

	public function config()
	{
		if (empty($_POST)) {
			$appc = model('Appc')->find();
			$appc['pay'] = json_decode($appc['pay'], true);
			$show_coin = json_decode($appc['show_coin'], true);
			$Coin = model('coin')->where(['type'=> ['in', "rgb,bit,eth"], 'status' => 1])->select();
			$appc['show_coin'] = array();

			foreach ($Coin as $val) {
				$appc['show_coin'][] = array('id' => $val['id'], 'name' => $val['title'] . '(' . $val['name'] . ')', 'flag' => $show_coin ? (in_array($val['id'], $show_coin) ? 1 : 0) : 1);
			}

			$show_market = json_decode($appc['show_market'], true);
			$Market = model('Market')->where(['status' => 1])->select();
			$appc['show_market'] = array();

			foreach ($Market as $val) {
				$coin_name = explode('_', $val['name']);
				$xnb_name = model('Coin')->where(array('name' => $coin_name[0]))->find()['title'];
				$rmb_name = model('Coin')->where(array('name' => $coin_name[1]))->find()['title'];
				$appc['show_market'][] = array('id' => $val['id'], 'name' => $xnb_name . '/' . $rmb_name . '(' . $val['name'] . ')', 'flag' => $show_market ? (in_array($val['id'], $show_market) ? 1 : 0) : 1);
			}

			$this->assign('appCon', $appc);
			return $this->fetch();
		} else {
		    $_POST = input('post.');
			$_POST['pay'] = json_encode($_POST['pay']);
			$_POST['show_coin'] = json_encode($_POST['show_coin']);
			$_POST['show_market'] = json_encode($_POST['show_market']);

			if (model('Appc')->update($_POST)) {
				$this->success('保存成功！');
			}
			else {
				$this->error('没有修改');
			}
		}
	}

	public function vip_config_list()
	{
		$coin = model('coin')->select();
		$coinMap = array();

		foreach ($coin as $val) {
			$coinMap[$val['id']] = $val['title'];
		}

		$this->assign('coinMap', $coinMap);
		$this->Model = model('AppVip');
		$where = array();
		$list = $this->Model->where($where)->order('id desc')->paginate(15,false,['query'=>request()->input()])->each(function($item, $key){
            $item['rule'] = json_decode($item['rule'], true);
            return $item;
        });
		$show = $list->render();



		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function vip_config_edit()
	{
		if (empty($_POST)) {
			$coin = model('Coin')->where(['status' => 1])->select();
			$this->assign('coin', $coin);
            $_GET = input('param.');
			if (isset($_GET['id']) && $_GET['id']) {
				$vipArr = model('AppVip')->where(array('id' => trim($_GET['id'])))->find();
				$vipArr['rule'] = json_decode($vipArr['rule'], true);
				$this->assign('idi', count($vipArr['rule']));
				$rule_t = str_repeat('1,', count($vipArr['rule']));
				$rule_t = mb_substr($rule_t, 0, -1);
				$this->assign('rule_str', '[' . $rule_t . ']');
				$this->assign('data', $vipArr);
			}
			else {
				$this->assign('rule_str', '[]');
				$this->assign('idi', 0);
			}

			return $this->fetch();
		} else {
            $_POST = input('post.');
			if (!$_POST['tag']) {
				$this->error('等级次序不能为空');
			}

			if (!check($_POST['tag'], 'integer')) {
				$this->error('等级次序必须为整数！');
			}

			if ($res = model('AppVip')->where(array('tag' => $_POST['tag']))->find()) {
				if ($res['id'] != $_POST['id']) {
					$this->error('等级次序' . $_POST['tag'] . ' 已经存在！');
				}
			}

			$_POST['rule'] = json_decode($_POST['rule'], true);
			$key_map = array();
			$rule = array();

			foreach ($_POST['rule'] as $val) {
				if (!isset($key_map[$val['id']])) {
					$key_map[$val['id']] = 1;
					$rule[] = $val;
				}
				else {
					$this->error('升级币种不能相同');
				}
			}

			$_POST['rule'] = json_encode($rule);

			if (!empty($_POST['id'])) {
				$rs = model('AppVip')->update($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = model('AppVip')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改!');
			}
		}
	}

	public function vip_config_edit_status()
	{
		if (IS_POST) {
			$id = implode(',', input('id/d'));
		} else {
			$id = input('id/d');
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			if (model('Appadsblock')->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (model('Appadsblock')->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('没有任何修改！');
		}
	}

	public function adsblock_list()
	{
		$rankMap = array();
		$AppVip = model('AppVip')->where(array('status' => 1))->select();

		foreach ($AppVip as $val) {
			$rankMap[$val['id']] = $val['name'];
		}

		$this->assign('rankMap', $rankMap);
		$this->Model = model('Appadsblock');
		$where = array();
		$list = $this->Model->where($where)->order('id desc')->paginate(15);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function adsblock_edit()
	{
		if (empty($_POST)) {
			$AppVip = model('AppVip')->where(array('status' => 1))->select();
			$this->assign('AppVip', $AppVip);
            $_GET = input('param.');
			if (isset($_GET['id'])) {
				$this->data = model('Appadsblock')->where(array('id' => trim($_GET['id'])))->find();
			}
			else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
            $_POST = input('post.');
			if (!empty($_POST['id'])) {
				$rs = model('Appadsblock')->update($_POST);
			} else {
				$_POST['adminid'] = session('admin_id');
				$rs = model('Appadsblock')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			} else {
				$this->error('没有任何修改！');
			}
		}
	}

	public function adsblock_edit_status()
	{
		if (IS_POST) {
			$id = implode(',', input('id/d'));
		} else {
			$id = input('id/d');
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			if (model('Appadsblock')->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('没有任何修改！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (model('Appadsblock')->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('没有任何修改！');
		}
	}

	public function ads_list()
	{
        $block_id = input('block_id/d');
		if(empty($block_id) || !isset($block_id)){
			$block_id=1;
		}

		$ads_block = Db::name('Appadsblock')->where(array('id' => $block_id))->find();
		$this->assign('ads_block', $ads_block);
		$this->Model = model('Appads');

		if ($block_id) {
			$where['block_id'] = $block_id;
		}

		$list = $this->Model->where($where)->order('id desc')->paginate(15);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function ads_edit()
	{
		if (empty($_POST)) {
		    $id = input('id/d');
			if (isset($id)) {
				$this->data = model('Appads')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
            $_POST =input('post.');
			if (!empty($_POST['id'])) {
				$rs = model('Appads')->update($_POST);
			} else {
				$_POST['adminid'] = session('admin_id');
				$rs = model('Appads')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			} else {
				$this->error('没有任何修改！');
			}
		}
	}

	public function ads_edit_status()
	{
        $_GET = input('param.');
		if (IS_POST) {
			$id = implode(',', input('id/d'));
		} else {
			$id = $_GET['id'] ;
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			if (model('Appads')->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('没有任何修改！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (model('Appads')->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('没有任何修改！');
		}
	}

	public function ads_user()
	{
		$this->Model = Db::name('AppVipuser');
		$where = array();
		$list = $this->Model->join('weike_user', 'weike_user.id = weike_app_vipuser.uid')->join('weike_app_vip', 'weike_app_vip.id = weike_app_vipuser.vip_id')->field('weike_user.username,weike_app_vipuser.*,weike_app_vip.name as vip_name,weike_app_vip.tag')->where($where)->order('id desc')->paginate(15);
		$show = $list->render();

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function ads_user_detail()
	{
		$where = array();
		$this->Model = model('AppLog');
        $uid = intval(input('uid'));
		if ($uid) {
			$where['uid'] = $uid;
		}

		$list = $this->Model->where($where)->order('id desc')->paginate(15);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function upload()
	{
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/app/';
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo '/Upload/app/'.$filename;
        exit();
	}
}

?>