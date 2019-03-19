<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;

class Fenhong extends Admin
{
	public function index()
	{
        $p = input('page/d',1);
        $r = input('r',15);
        $str_addtime = input('str_addtime');
        $end_addtime =input('end_addtime');
        $order = input('order');
        $status = input('status');
        $field = input('field');
        $type = input('type');
        $name = input('name');
		$myczType = Db::name('MyczType')->select();
		$myczTypeList = [];
		$myczTypeListArr[0] = '全部方式';

		foreach ($myczType as $k => $v) {
			$myczTypeList[$v['name']] = $v['title'];
			$myczTypeListArr[$v['name']] = $v['title'];
		}

		$map = [];
		if ($str_addtime && $end_addtime) {
			$str_addtime = strtotime($str_addtime);
			$end_addtime = strtotime($end_addtime);

			if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
				$map['addtime'] = [['egt', $str_addtime], ['elt', $end_addtime]];
			}
		}

		if (empty($order)) {
			$order = 'id_desc';
		}
		$order_arr = explode('_', $order);
		if (count($order_arr) != 2) {
			$order = 'id_desc';
			$order_arr = explode('_', $order);
		}

		$order_set = $order_arr[0] . ' ' . $order_arr[1];
		if (empty($status)) {
			$map['status'] = array('egt', 0);
		}

		if (($status == 1) || ($status == 2) || ($status == 3)) {
			$map['status'] = $status - 1;
		}

		if (isset($myczTypeList[$type]) && $myczTypeList[$type]) {
			$map['type'] = $type;
		}

		if ($field && $name) {
			if ($field == 'username') {
				$map['userid'] = userid($name);
			} else {
				$map[$field] = $name;
			}
		}

		$data = Db::name('Fenhong')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('Fenhong')->where($map)->count();

		$parameter['p'] = $p;
        $parameter['query']=[
            'status'=>$status,
            'order'=>$order,
            'type'=>$type,
            'name'=>$name,
        ];
        $parameter['path']=url('Fenhong/index');

		$builder = new BuilderList();
		$builder->title('分红管理');
		$builder->titleList('分红列表', url('Fenhong/index'));
		$builder->button('add', '添 加', url('Fenhong/edit'));
		$builder->keyId();
		$builder->keyText('coinname', '分红币种');
		$builder->keyText('coinjian', '奖励币种');
		$builder->keyText('name', '分红名称');
		$builder->keyText('num', '分红数量');
		$builder->keyTime('addtime', '添加时间');
		$builder->keyTime('endtime', '操作时间');
		$builder->keyDoAction('Fenhong/kaishi?id=###', '开始分红|---|kaishi|1', '操作');
		$builder->keyDoAction('Fenhong/edit?id=###', '编辑', '操作');
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		return $builder->display();
	}

	public function edit()
	{
	    $id = input('id/d');
        $_POST = input('post.');
		if (!empty($_POST)) {
			if (!check($_POST['name'], 'a')) {
				$this->error('分红名称格式错误');
			}

			if (!check($_POST['coinname'], 'w')) {
				$this->error('分红币种格式错误');
			}

			if (!check($_POST['num'], 'd')) {
				$this->error('分红数量格式错误');
			}
			
			$_POST['sort'] =1;
			if ($_POST['addtime']) {
				if (addtime(strtotime($_POST['addtime'])) == '---') {
					$this->error('添加时间格式错误');
				} else {
					$_POST['addtime'] = strtotime($_POST['addtime']);
				}
			} else {
				$_POST['addtime'] = time();
			}

			if ($_POST['endtime']) {
				if (addtime(strtotime($_POST['endtime'])) == '---') {
					$this->error('编辑时间格式错误');
				} else {
					$_POST['endtime'] = strtotime($_POST['endtime']);
				}
			} else {
				$_POST['endtime'] = time();
			}

            $FenhongModel = model('Fenhong');
            if(isset($_POST['id']) && check($_POST['id'], 'd')){
                $rs=$FenhongModel->update($_POST,['id'=>$_POST['id']],true);
             }else{
                $rs=$FenhongModel->create($_POST,true);
            }

			if ($rs) {
				$this->success('操作成功');
			} else {
				$this->error('操作失败');
			}
		} else {
			$coin_list = model('Coin')->get_all_name_list();
			foreach ($coin_list as $k => $v) {
				$coin_list[$k] = $v . '-全站持有' . model('Coin')->get_sum_coin($k);
				$coin_lista[$k] = $v;
			}

			$builder = new BuilderEdit();
			$builder->title('分红管理');
			$builder->titleList('分红列表', url('Fenhong/index'));

			if ($id) {
				$builder->keyReadOnly('id', '类型id');
				$builder->keyHidden('id', '类型id');
				$data = Db::name('Fenhong')->where(array('id' => $id))->find();
				$data['addtime'] = addtime($data['addtime']);
				$data['endtime'] = addtime($data['endtime']);
				$builder->data($data);
			}

			$builder->keyText('name', '分红名称', '可以中中文');
			$builder->keySelect('coinname', '分红币种', '分红币种', $coin_list);
			$builder->keySelect('coinjian', '奖励币种', '奖励币种', $coin_lista);
			$builder->keyText('num', '分红数量', '整数');
			$builder->keyTextarea('content', '分红介绍');
			//$builder->keyText('sort', '排序', '整数');
			$builder->keyAddTime();
			$builder->keyEndTime();
			$builder->savePostUrl(url('Fenhong/edit'));
			return $builder->display();
		}
	}

	public function status($id, $status, $model)
	{
		$builder = new BuilderList();
		$builder->doSetStatus($model, $id, $status);
	}

	public function kaishi()
	{
        $id = input('id/d');
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$data = Db::name('Fenhong')->where(array('id' => $id))->find();

		if ($data['status'] != 0) {
			$this->error('已经处理，禁止再次操作！');
		}

		$a = Db::name('UserCoin')->sum($data['coinname']);
		$b = Db::name('UserCoin')->sum($data['coinname'] . 'd');
		$data['quanbu'] = $a + $b;
		$data['meige'] = round($data['num'] / $data['quanbu'], 8);
		$data['user'] = Db::name('UserCoin')->where(array(
			$data['coinname'] => array('gt', 0),
			$data['coinname'] . 'd' => array('gt', 0),
			'_logic' => 'OR'
			))->count();
		$this->assign('data', $data);
		return $this->fetch();
	}

	public function fenfa()
	{
        $id = intval(input('id/d'));
        $fid = input('fid');
        $dange = input('dange');
		if ($id === null) {
			echo json_encode(array('status' => -2, 'info' => '参数错误'));
			exit();
		}

		if ($fid === null) {
			echo json_encode(array('status' => -2, 'info' => '参数错误2'));
			exit();
		}

		if ($dange === null) {
			echo json_encode(array('status' => -2, 'info' => '参数错误3'));
			exit();
		}

		if ($id == -1) {
			cache('fenhong_fenfa_j', null);
			cache('fenhong_fenfa_c', null);
			cache('fenhong_fenfa', null);
			$fenhong = Db::name('Fenhong')->where(array('id' => $fid))->find();

			if (!$fenhong) {
				echo json_encode(array('status' => -2, 'info' => '分红初始化失败'));
				exit();
			}

			cache('fenhong_fenfa_j', $fenhong);
			$usercoin = Db::name('UserCoin')->where(array(
				$fenhong['coinname'] => array('gt', 0),
				$fenhong['coinname'] . 'd' => array('gt', 0),
				'_logic' => 'OR'
				))->select();

			if (!$usercoin) {
				echo json_encode(array('status' => -2, 'info' => '没有用户持有'));
				exit();
			}

			$a = 1;

			foreach ($usercoin as $k => $v) {
				$shiji[$a]['userid'] = $v['userid'];
				$shiji[$a]['chiyou'] = $v[$fenhong['coinname']] + $v[$fenhong['coinname'] . 'd'];
				$a++;
			}

			if (!$shiji) {
				echo json_encode(array('status' => -2, 'info' => '计算错误'));
				exit();
			}

			cache('fenhong_fenfa_c', count($usercoin));
			cache('fenhong_fenfa', $shiji);
			echo json_encode(array('status' => 1, 'info' => '分红初始化成功'));
			exit();
		}

		if ($id == 0) {
			echo json_encode(array('status' => 1, 'info' => ''));
			exit();
		}

		if (cache('fenhong_fenfa_c') < $id) {
			echo json_encode(array('status' => 100, 'info' => '分红全部完成'));
			exit();
		}

		if ((0 < $id) && ($id <= cache('fenhong_fenfa_c'))) {
			$fenhong = cache('fenhong_fenfa_j');
			$fenfa = cache('fenhong_fenfa');
			$cha = Db::name('FenhongLog')->where(array('name' => $fenhong['name'], 'coinname' => $fenhong['coinname'], 'userid' => $fenfa[$id]['userid']))->find();

			if ($cha) {
				echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '本次分红已经发过'));
				exit();
			}

			$faduoshao = round($fenfa[$id]['chiyou'] * $dange, 8);

			if (!$faduoshao) {
				echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '分红数量太小不用发了，持有数量' . $fenfa[$id]['chiyou']));
				exit();
			}

			Db::startTrans();
			try{
			    $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $fenfa[$id]['userid']))->setInc($fenhong['coinjian'], $faduoshao);
                $rs[] = Db::table('weike_fenhong_log')->insert(array('name' => $fenhong['name'], 'userid' => $fenfa[$id]['userid'], 'coinname' => $fenhong['coinname'], 'coinjian' => $fenhong['coinjian'], 'fenzong' => $fenhong['num'], 'price' => $dange, 'num' => $fenfa[$id]['chiyou'], 'mum' => $faduoshao, 'addtime' => time(), 'status' => 1));

                if (check_arr($rs)) {
                    Db::commit();
                    echo json_encode(array('status' => 1, 'info' => '用户id' . $fenfa[$id]['userid'] . '，持有数量' . $fenfa[$id]['chiyou'] . '成功分红' . $faduoshao));
                    exit();

                }else{
                    Db::rollback();
                    echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '，持有数量' . $fenfa[$id]['chiyou'] . '分红失败'));
                    exit();
                }

            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '，持有数量' . $fenfa[$id]['chiyou'] . '分红失败'));
                exit();

            }

		}
	}

	public function log()
	{
        $p = input('page/d',1);
        $r = input('r',15);
        $str_addtime = input('str_addtime');
        $end_addtime =input('end_addtime');
		$map = array();
        $order = input('order');
        $status = input('status');
        $field = input('field');
        $type = input('type');
        $name = input('name');
        $coinname = input('coinname');
        $coinjian = input('coinjian');
		if ($str_addtime && $end_addtime) {
			$str_addtime = strtotime($str_addtime);
			$end_addtime = strtotime($end_addtime);

			if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
				$map['addtime'] = array(
					array('egt', $str_addtime),
					array('elt', $end_addtime)
					);
			}
		}

		if (empty($order)) {
			$order = 'id_desc';
		}

		$order_arr = explode('_', $order);

		if (count($order_arr) != 2) {
			$order = 'id_desc';
			$order_arr = explode('_', $order);
		}

		$order_set = $order_arr[0] . ' ' . $order_arr[1];

		if (empty($status)) {
			$map['status'] = array('egt', 0);
		}

		if (($status == 1) || ($status == 2) || ($status == 3)) {
			$map['status'] = $status - 1;
		}

		if ($field && $name) {
			if ($field == 'userid') {
				$map['userid'] = model('User')->get_userid($name);
			}
			else {
				$map[$field] = $name;
			}
		}

		if ($coinname) {
			$map['coinname'] = $coinname;
		}

		if ($coinjian) {
			$map['coinjian'] = $coinjian;
		}

		$data = Db::name('FenhongLog')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('FenhongLog')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['query']=[
            'status'=>$status,
            'order'=>$order,
            'type'=>$type,
            'name'=>$name,
            'coinname'=>$coinname,
            'coinjian'=>$coinjian,
        ];
        $parameter['path']=url('Fenhong/log');

		$builder = new BuilderList();
		$builder->title('分红记录');
		$builder->titleList('记录列表', url('Fenhong/log'));
		$builder->setSearchPostUrl(url('Fenhong/log'));
		$builder->search('order', 'select', array('id_desc' => 'ID降序', 'id_asc' => 'ID升序'));
		$coinname_arr = array('' => '分红币种');
		$coinname_arr = array_merge($coinname_arr, model('Coin')->get_all_name_list());
		$builder->search('coinname', 'select', $coinname_arr);
		$coinjian_arr = array('' => '奖励币种');
		$coinjian_arr = array_merge($coinjian_arr, model('Coin')->get_all_name_list());
		$builder->search('coinjian', 'select', $coinjian_arr);
		$builder->search('field', 'select', array('name' => '分红名称', 'userid' => '用户名'));
		$builder->search('name', 'text', '请输入查询内容');
		$builder->keyId();
		$builder->keyText('name', '分红名称');
		$builder->keyUserid();
		$builder->keyText('coinname', '分红币种');
		$builder->keyText('coinjian', '奖励币种');
		$builder->keyText('fenzong', '分红总数');
		$builder->keyText('price', '每个奖励');
		$builder->keyText('num', '持有数量');
		$builder->keyText('mum', '分红数量');
		$builder->keyTime('addtime', '分红时间');
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		return $builder->display();
	}
}

?>