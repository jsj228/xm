<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;

class Vote extends Admin
{
	public function index()
	{
        $p = input('page/d',1);
        $r = input('r',15);
        $str_addtime = input('str_addtime') ;
        $end_addtime = input('end_addtime');
        $order = input('order');
        $status =input('status');
        $type =input('type');
        $field =input('field');
        $name =input('name');
		$map = array();

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
			if ($field == 'username') {
				$map['userid'] = userid($name);
			}
			else {
				$map[$field] = $name;
			}
		}

		$data = Db::name('Vote')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('Vote')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['query']=[
            'status'=>$status,
            'order'=>$order,
            'type'=>$type,
            'name'=>$name,
        ];
        $parameter['path']=url('Vote/index');

		$builder = new BuilderList();
		$builder->title('投票记录');
		$builder->titleList('投票列表', url('Vote/index'));
		$builder->button('delete', '删 除', url('Vote/status', array('model' => 'Vote', 'status' => -1)));
		$builder->setSearchPostUrl(url('Vote/index'));
		$builder->search('order', 'select', array('id_desc' => 'ID降序', 'id_asc' => 'ID升序'));
		$builder->search('status', 'select', array('全部状态', '禁用', '启用'));
		$builder->search('field', 'select', array('username' => '用户名'));
		$builder->search('name', 'text', '请输入查询内容');
		$builder->keyId();
		$builder->keyUserid();
		$builder->keyText('coinname', '币种');
		$builder->keyText('title', '名称');
		$builder->keyType('type', '类型', array(1 => '支持', 2 => '反对'));
		$builder->keyTime('addtime', '添加时间');
		$builder->keyStatus();
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		return $builder->display();
	}

	public function type()
	{
        $p = input('page/d',1);
        $r = input('r',15);
        $str_addtime = input('str_addtime') ;
        $end_addtime = input('end_addtime');
        $order = input('order');
        $status =input('status');
        $type =input('type');
        $field =input('field');
        $name =input('name');
		$map = array();
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
			if ($field == 'username') {
				$map['userid'] = userid($name);
			}
			else {
				$map[$field] = $name;
			}
		}

		$data = Db::name('VoteType')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('VoteType')->where($map)->count();

        foreach ($data as $k => $vv) {
            $data[$k]['zhichi'] = Db::name('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['zhichi'];
            $data[$k]['fandui'] = Db::name('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['fandui'];
            $data[$k]['zongji'] = $data[$k]['zhichi'] + $data[$k]['fandui'];
            $data[$k]['bili'] = round(($data[$k]['zhichi'] / $data[$k]['zongji']) * 100, 2);
        }

        $parameter['p'] = $p;
        $parameter['query']=[
            'status'=>$status,
            'order'=>$order,
            'type'=>$type,
            'name'=>$name,
        ];
        $parameter['path']=url('Vote/type');

		$builder = new BuilderList();
		$builder->title('投票类型');
		$builder->titleList('投票类型', url('Vote/type'));
		$builder->button('add', '添 加', url('Vote/edit'));
		$builder->button('delete', '删 除', url('Vote/status', array('model' => 'VoteType', 'status' => -1)));
		$builder->setSearchPostUrl(url('Vote/index'));
		$builder->search('order', 'select', array('id_desc' => 'ID降序', 'id_asc' => 'ID升序'));
		$builder->search('status', 'select', array('全部状态', '禁用', '启用'));
		$builder->search('field', 'select', array('coinname' => '币种'));
		$builder->search('name', 'text', '请输入查询内容');
		$builder->keyId();
		$builder->keyText('coinname', '币种');
		$builder->keyText('title', '名称');
		$builder->keyText('votecoin', '投票币种');
		$builder->keyText('assumnum', '扣除数量');
		$builder->keyText('zhichi', '支持票');
		$builder->keyText('fandui', '反对票');
		$builder->keyStatus();
		$builder->keyDoAction('Vote/edit?id=###', '编辑', '操作');
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		return $builder->display();
	}

	public function edit()
	{
        $id = input('id/d');
	    $_POST = input('post.');
		if (!empty($_POST)) {
			if (check($_POST('id'), 'd')) {
                $_POST['status'] = 1;
				$rs = Db::name('VoteType')->update($_POST);
			}
			else {
				if (Db::name('VoteType')->where(array('coinname' => $_POST['coinname']))->find()) {
					$this->error('已经存在');
				}

                $array = array(
                    'coinname' => $_POST['coinname'],
                    'title' => $_POST['title'],
					'votecoin' => $_POST['votecoin'],
					'assumnum' => $_POST['assumnum'],
                    'status' => 1,
                );
				$rs = Db::name('VoteType')->insert($array);
			}

			if ($rs) {
				$this->success('操作成功');
			}
			else {
				$this->error('操作失败');
			}
		}
		else {
			$builder = new BuilderEdit();
			$builder->title('投票类型管理');
			$builder->titleList('投票类型列表', url('Vote/type'));

			if ($id) {
				$builder->keyReadOnly('id', '类型id');
				$builder->keyHidden('id', '类型id');
				$data = Db::name('VoteType')->where(array('id' => input('param.id')))->find();
				$builder->data($data);
			}

			$coin_list = model('Coin')->get_all_name_list();
			//$builder->keySelect('coinname', '币种', '币种', $coin_list);
			$builder->keyText('coinname', '币种','英文名称');
			$builder->keyText('title', '币种名称','中文名称');
			$builder->keyText('zhichi', '增加支持票数', '整数');
			$builder->keyText('fandui', '增加反对票数', '整数');
			
			$builder->keySelect('votecoin', '投票币种', '投票需要扣除的币种', $coin_list);
			$builder->keyText('assumnum', '扣除个数', '整数,投一次票扣除的币种个数');
			
			
			
			$builder->savePostUrl(url('Vote/edit'));
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
		die();
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

	public function fenfa($id = NULL, $fid = NULL, $dange = NULL)
	{
		die();
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
			try {
                $rs = [];
                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $fenfa[$id]['userid']))->setInc($fenhong['coinjian'], $faduoshao);
                $rs[] = Db::table('weike_fenhong_log')->insert(array('name' => $fenhong['name'], 'userid' => $fenfa[$id]['userid'], 'coinname' => $fenhong['coinname'], 'coinjian' => $fenhong['coinjian'], 'fenzong' => $fenhong['num'], 'price' => $dange, 'num' => $fenfa[$id]['chiyou'], 'mum' => $faduoshao, 'addtime' => time(), 'status' => 1));

                if (check_arr($rs)) {
                    Db::commit();
                    echo json_encode(array('status' => 1, 'info' => '用户id' . $fenfa[$id]['userid'] . '，持有数量' . $fenfa[$id]['chiyou'] . '成功分红' . $faduoshao));
                    exit();
                } else {
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


}

?>