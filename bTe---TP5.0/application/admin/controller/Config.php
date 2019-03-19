<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;
use Common\Ext\BtmClient;

class Config extends AdminCommon
{
    public function index()
    {
        $this->data = DB::name('Config')->where(array('id' => 1))->find();
        return $this->fetch();
    }

    public function edit()
    {
        if (DB::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function image()
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

    public function moble()
    {
        $this->data = DB::name('Config')->where(array('id' => 1))->find();
         return $this->fetch();
    }

    public function mobleEdit()
    {
        if (DB::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function contact()
    {
        $this->data = DB::name('Config')->where(array('id' => 1))->find();
         return $this->fetch();
    }

    public function contactEdit()
    {
        if (DB::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function coin()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
        $where = array();
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

        $list = DB::name('Coin')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function coinEdit()
    {
        $id = input('id');
        $_POST = input('post.');
        // dump($_POST);die;
        // $_GET = input('get.');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = array();
            } else {
                $this->assign('data',DB::name('Coin')->where(array('id' => trim($id)))->find());
            }

            $weike_getCoreConfig = weike_getCoreConfig();
            if(!$weike_getCoreConfig){
                $this->error('核心配置有误');
            }

            $this->assign("weike_opencoin",$weike_getCoreConfig['weike_opencoin']);
            return $this->fetch('coinEdit');
        } else {
            $_POST['fee_bili'] = floatval($_POST['fee_bili']);
            if ($_POST['fee_bili'] && (($_POST['fee_bili'] < 0.001) || (200 < $_POST['fee_bili']))) {
                $this->error('挂单比例只能是0.001--200之间(不用填写%)！');
            }

            $_POST['zr_zs'] = floatval($_POST['zr_zs']);
            if ($_POST['zr_zs'] && (($_POST['zr_zs'] < 0.001) || (200 < $_POST['zr_zs']))) {
                $this->error('转入赠送只能是0.001--200之间(不用填写%)！');
            }

            $_POST['zr_dz'] = intval($_POST['zr_dz']);
            $_POST['zc_fee'] = floatval($_POST['zc_fee']);
            if ($_POST['zc_fee'] && (($_POST['zc_fee'] < 0.001) || (200 < $_POST['zc_fee']))) {
                $this->error('转出手续费只能是0.001--200之间(不用填写%)！');
            }


            if ($_POST['zc_user']) {
                if (!check($_POST['zc_user'], 'dw')) {
                    $this->error('官方手续费地址格式不正确！');
                }


                $ZcUser = DB::name('UserCoin')->where(array($_POST['name'] . 'b' => $_POST['zc_user']))->find();

                if (!$ZcUser) {
                    $this->error('在系统中查询不到[官方手续费地址],请务必填写正确！');
                }
            }

            $_POST['zc_min'] = floatval($_POST['zc_min']);
            $_POST['zc_max'] = floatval($_POST['zc_max']);
            if ($_POST['id']) {
                $rs = DB::name('Coin')->update($_POST);
            } else {
                if (!check($_POST['name'], 'n')) {
                    $this->error('币种简称只能是小写字母！');
                }

                $_POST['name'] = strtolower($_POST['name']);

                if (check($_POST['name'], 'username')) {
                    $this->error('币种名称格式不正确！');
                }

                if (DB::name('Coin')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('币种存在！');
                }

                $rea = DB::name()->execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT \'0\' ');
                $reb = DB::name()->execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT \'0\' ');
                $rec = DB::name()->execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL DEFAULT \'\'');
                $red = DB::name()->execute('ALTER TABLE  `weike_coinlog` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) NOT NULL DEFAULT \'0\' ');
                $_POST['status'] =1;
                $rs = DB::name('Coin')->insert($_POST);
            }

            if ($rs) {
                Cache::rm('home_coin');
                $this->success('操作成功！');
            } else {
                $this->error('数据未修改！');
            }
        }
    }

    public function coinStatus()
    {
        $_POST = input('post.');
        $_GET = input('get.');

        if ($this->request->isPost()) {
            foreach ($_POST as $key => $value) {
                $ids=implode(',', $value);
            }
        } else {
            $ids = $_GET['id'];
        } 
        if (empty($ids)) {
            $this->error('请选择要操作的数据!');
        }

        $where['id'] = array('in', $ids);
        $method = input('type');
         // dump($method);die;
        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                $rs = DB::name('Coin')->where($where)->select();
                foreach ($rs as $k => $v) {
                    $rs[] = DB::name()->execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name']);
                    $rs[] = DB::name()->execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name'] . 'd');
                    $rs[] = DB::name()->execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name'] . 'b');
                    $rs[] = DB::name()->execute('ALTER TABLE  `weike_coinlog` DROP COLUMN ' . $v['name']);
                }

                if (DB::name('Coin')->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('参数非法');
        }

        if (DB::name('Coin')->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coinInfo()
    {
        $coin =strval(input('coin'));
        $coinconf = DB::name('coin')->where(array('name' => $coin))->find();
        $dj_username = config('coin')[$coin]['dj_yh'];
        $dj_password = config('coin')[$coin]['dj_mm'];
        $dj_address = config('coin')[$coin]['dj_zj'];
        $dj_port = config('coin')[$coin]['dj_dk'];

        if($coinconf['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包对接失败！');
            }

            $info = $CoinClient->getinfo();
            $info['num'] = DB::name('UserCoin')->sum($coin) + DB::name('UserCoin')->sum($coin . 'd');
            $info['name'] = $coin;
        } elseif ($coinconf['type'] == 'eth' ||$coinconf['type'] == 'token') {           
	        $CoinClient = EthClient($dj_address,$dj_port);
            $json = $CoinClient->eth_blockNumber(true);
            if (empty($json) || $json <= 0) {
                $this->error('钱包对接失败！');
            }
            $info['name'] = $coin;
            $info['version'] = hexdec($CoinClient->eth_protocolVersion());
            $info['headers'] = hexdec($CoinClient->eth_blockNumber());
            $info['gasPrice'] = hexdec($CoinClient->eth_gasPrice());
            $info['accounts'] = $CoinClient->eth_accounts();
            $sum = 0;
            if($coinconf['type'] === 'token'){
                foreach ($info['accounts'] as $key => $value) {
                    $call = [
                        'to' => $coinconf['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($value)
			    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , $coinconf['decimals']);
                }
            }else {
                foreach ($info['accounts'] as $key => $value) {
                    $sum += $CoinClient->eth_getBalance($value);
                }
            }
            $info['balance'] = $sum;
            $info['num'] = DB::name('UserCoin')->sum($coin) + DB::name('UserCoin')->sum($coin . 'd');
            $info['name'] = $coin;
        }else if ($coinconf['type'] == 'btm'){
            $btmzData = DB::name('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
            if ($btmzData){
                $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                $info['name'] = $coin;
                $info['version'] = $btmClient->getVersion();
                $info['headers'] = $btmClient->getBlockCount();
                $info['num'] = DB::name('UserCoin')->sum('btm') + DB::name('UserCoin')->sum('btm' . 'd');
                $info['balance'] = $btmClient->listBalance()['amount']/100000000;
                $info['gasPrice'] =$btmClient->gasRate();
                /*$info['accounts'] = $CoinClient->eth_accounts();*/
            }else{
                $this->error('获取钱包数据失败！');
            }
        }
        $this->assign('data', $info);
        return $this->fetch();
    }

    public function coinUser()
    {
        $coin =strval(input('coin'));
        $coinconf = DB::name('coin')->where(array('name' => $coin))->find();
        $dj_username = $coinconf['dj_yh'];
        $dj_password = $coinconf['dj_mm'];
        $dj_address = $coinconf['dj_zj'];
        $dj_port = $coinconf['dj_dk'];

        if ($coinconf['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包对接失败！');
            }

            //获取钱包所有地址
            $arr = $CoinClient->listaccounts();
            $Page = new \Think\Page(count($arr), 15);
            $show = $Page->show();
            $arr = array_slice($arr, $Page->firstRow, $Page->listRows, true);

            foreach ($arr as $k => $v) {
                if ($v < 1.0000000000000001E-5) {
                    $v = 0;
                }

                $list[$k]['num'] = $v;
                $str = '';
                $addr = $CoinClient->getaddressesbyaccount(strval($k));

                foreach ($addr as $kk => $vv) {
                    $str .= $vv . '<br>';
                }

                $list[$k]['addr'] = $str;
                $userid = DB::name('User')->where(array('username' => $k))->value('id');
                $user_coin = DB::name('UserCoin')->where(array('userid' => $userid))->find();
                $list[$k]['id'] = $userid;
                $list[$k]['xnb'] = $user_coin[$coin];
                $list[$k]['xnbd'] = $user_coin[$coin . 'd'];
                $list[$k]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
                $list[$k]['xnbb'] = $user_coin[$coin . 'b'];
                $list[$k]['username'] =DB::name('user')->where(['id' => $user_coin['userid']])->value('username');
                unset($str);
            }
        } elseif ($coinconf['type'] == 'eth' || $coinconf['type'] == 'token') {
            $CoinClient = EthClient($dj_address, $dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->error('钱包对接失败！');
            }

            //获取钱包所有地址
            $addrs = $CoinClient->eth_accounts();
            $Page = new \Think\Page(count($addrs), 15);
            $show = $Page->show();
            $addrs = array_slice($addrs, $Page->firstRow, $Page->listRows, true);

            if ($coinconf['type'] === 'token') {
                foreach ($addrs as $key => $addr) {
                    //钱包地址余额
                    $call = [
                        'to' => $coinconf['token_address'],
                        'data' => '0x70a08231'. $CoinClient->data_pj($addr)
                    ];
                    $list[$key]['num'] = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , $coinconf['decimals']);
                    $user_coin = DB::name('user_coin')->where(array($coin . 'b' => $addr))->find();
                    $list[$key]['addr'] = $addr;
                    if ($user_coin) {
                        $list[$key]['id'] = $user_coin['userid'];
                        $list[$key]['xnb'] = $user_coin[$coin];
                        $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                        $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                        $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                        $list[$key]['username'] = DB::name('user')->where(['id' => $user_coin['userid']])->value('username');
                    }
                }
            } else {
                foreach ($addrs as $key => $addr) {
                    //钱包地址余额
                    $list[$key]['num'] = $CoinClient->eth_getBalance($addr);
                    $user_coin = DB::name('user_coin')->where(array($coin . 'b' => $addr))->find();
                    $list[$key]['addr'] = $addr;
                    if ($user_coin) {
                        $list[$key]['id'] = $user_coin['userid'];
                        $list[$key]['xnb'] = $user_coin[$coin];
                        $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                        $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                        $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                        $list[$key]['username'] = DB::name('user')->where(['id' => $user_coin['userid']])->value('username');
                    }
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
       return $this->fetch();
    }

    public function coinQing()
    {
        $coin =strval(input('coin'));
        $this->error('兄弟姐妹们别乱来！');
        if (!config('coin')[$coin]) {
            $this->error('参数错误！');
        }

        $info = DB::name()->execute('UPDATE `weike_user_coin` SET `' . trim($coin) . 'b`=\'\' ;');

        if ($info) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coinImage()
    {
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/coin/';
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

    public function text()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
        $where = array();

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

        $list = DB::name('Text')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function textEdit()
    {
        $id = input('id');
        $_GET = input('get.');
        if (empty($_POST)) {
            if ($id) {
                $this->data = DB::name('Text')->where(array('id' => trim($_GET['id'])))->find();
            } else {
                $this->data = null;
            }

            return $this->fetch();
        } else {
            $_POST['endtime'] = time();
            if ($_POST['id']) {
                $rs = DB::name('Text')->update($_POST);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = DB::name('Text')->insert($_POST);
            }

            if ($rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }

    public function textStatus()
    {
        $type =input('GET.type');
        $_POST = input('post.');
        if (empty($_POST)) {
            return $this->fetch();
        } else {
            foreach ($_POST['id'] as $key => $value) {
                if ($type == 'resume') {
                    $rs = DB::name('text')->update(['id' => $value, 'status' => 1, 'endtime' => time()]);
                } else {
                    $rs = DB::name('text')->update(['id' => $value, 'status' => 0, 'endtime' => time()]);
                }
            }
            if ($rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }

    public function qita()
    {
        $this->data = DB::name('Config')->where(array('id' => 1))->find();
    
        return $this->fetch();
    }

    public function qitaEdit()
    {
        if (DB::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function daohang()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => input('get.name')))->value('id');
            } else if ($field == 'title') {
                $where['title'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        } else {
            $where['status'] = array('neq',-1);
        }

        $list = DB::name('Daohang')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->All();

        $this->assign('list', $list);
        $this->assign('page', $page);
       return $this->fetch();
    }

    public function daohangEdit()
    {
        $id = input('id');
        if (empty($_POST)) {
            if ($id) {
                $this->assign('data',DB::name('Daohang')->where(array('id' => trim(input('get.id'))))->find());
            } else {
                $this->data = null;
            }

            return $this->fetch('daohangEdit');
        } else {
            if ($_POST['id']) {
                $rs = DB::name('Daohang')->update($_POST);
            } else {
                $_POST['addtime'] = time();
                $rs = DB::name('Daohang')->insert($_POST);
            }

            if ($rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }

    public function daohangStatus()
    {
        $id = input('post.');
        foreach ($id as $key => $value) {
           $ids=implode(',', $value);
        }
        $type = input('type');
        $moble = input('moble','Daohang');
        if (empty($id)) {
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
                    Cache::rm('daohang');
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
             Cache::rm('daohang');
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }
}

?>
