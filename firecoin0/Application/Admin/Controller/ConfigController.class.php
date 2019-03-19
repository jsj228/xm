<?php
namespace Admin\Controller;
use Common\Ext\BtmClient;
use Common\Ext\XrpClient;

class ConfigController extends AdminController
{
    public function index()
    {
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function edit()
    {
        if (M('Config')->where(array('id' => 1))->save($_POST)) {
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
        $filename = md5($_FILES['upload_file0']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

    public function moble()
    {
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function mobleEdit()
    {
        if (M('Config')->where(array('id' => 1))->save($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function contact()
    {
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function contactEdit()
    {
        if (M('Config')->where(array('id' => 1))->save($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function coin()
    {
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
        $where = array();
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
        $count = M('Coin')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Coin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
//   public function sort(){
//       dump($_POST);die;
//   }
    public function coinEdit()
    {
        $id = I('id/d');
        $_POST = I('post./a');
        $_GET = I('get./a');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = array();
            } else {
                $this->data = M('Coin')->where(array('id' => trim($_GET['id'])))->find();
            }

            $weike_getCoreConfig = weike_getCoreConfig();
            if(!$weike_getCoreConfig){
                $this->error('核心配置有误');
            }

            $this->assign("weike_opencoin",$weike_getCoreConfig['weike_opencoin']);
            $this->display();
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
            $_POST['zc_fee'] = trim($_POST['zc_fee']);


            if ($_POST['zc_fee'] && (($_POST['zc_fee'] < 0.001) || (200 < $_POST['zc_fee']))) {
                $this->error('转出手续费只能是0.001--200之间(不用填写%)！');
            }
            if ($_POST['zc_user'] == '') {
                $this->error('请填写官方手续费地址！');
            }
            if ($_POST['zc_user']) {
                if (!check($_POST['zc_user'], 'dw')) {
                    $this->error('官方手续费地址格式不正确！');
                }


                $ZcUser = M('UserCoin')->where(array($_POST['name'] . 'b' => $_POST['zc_user']))->find();

                if (!$ZcUser) {
                    $this->error('在系统中查询不到[官方手续费地址],请务必填写正确！');
                }
            }

            $_POST['zc_min'] = floatval($_POST['zc_min']);
            $_POST['zc_max'] = floatval($_POST['zc_max']);
            if ($_POST['id']) {
                $rs = M('Coin')->save($_POST);
            } else {
                if (!check($_POST['name'], 'n')) {
                    $this->error('币种简称只能是小写字母！');
                }

                $_POST['name'] = strtolower($_POST['name']);

                if (check($_POST['name'], 'username')) {
                    $this->error('币种名称格式不正确！');
                }

                if (M('Coin')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('币种存在！');
                }

                $rea = M()->execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT \'0\' ');
                $reb = M()->execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT \'0\' ');
                $rec = M()->execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL DEFAULT \'\'');
                $red = M()->execute('ALTER TABLE  `weike_coinlog` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) NOT NULL DEFAULT \'0\' ');
                $_POST['status'] =1;
                $rs = M('Coin')->add($_POST);
            }

            if ($rs) {
                S('home_coin',null);
                $this->success('操作成功！');
            } else {
                $this->error('数据未修改！');
            }
        }
    }

    public function coinStatus()
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
        $method = $_GET['type'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                $rs = M('Coin')->where($where)->select();
                foreach ($rs as $k => $v) {
                    $rs[] = M()->execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name']);
                    $rs[] = M()->execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name'] . 'd');
                    $rs[] = M()->execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name'] . 'b');
                    $rs[] = M()->execute('ALTER TABLE  `weike_coinlog` DROP COLUMN ' . $v['name']);
                }

                if (M('Coin')->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('参数非法');
        }

        if (M('Coin')->where($where)->save($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coinInfo()
    {
        $coin =strval(I('coin'));
        $coinconf = M('coin')->where(array('name' => $coin))->find();
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];

        if($coinconf['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包对接失败！');
            }

            $info = $CoinClient->getinfo();
            $info['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');
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
            $info['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');
            $info['name'] = $coin;
        }else if ($coinconf['type'] == 'btm'){
            $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
            if ($btmzData){
                $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                $info['name'] = $coin;
                $info['version'] = $btmClient->getVersion();
                $info['headers'] = $btmClient->getBlockCount();
                $info['num'] = M('UserCoin')->sum('btm') + M('UserCoin')->sum('btm' . 'd');
                $info['balance'] = $btmClient->listBalance()['amount']/100000000;
                $info['gasPrice'] =$btmClient->gasRate();
                /*$info['accounts'] = $CoinClient->eth_accounts();*/
            }else{
                $this->error('获取钱包数据失败！');
            }
        } elseif ($coinconf['type'] == 'eos') {
            $EosClient = EosClient($dj_address, $dj_port);
            $json = $EosClient->get_info();
            if(!$json){
                $this->error('钱包对接失败');
            }
            $tradeInfo = [
                "account" => $coinconf['dj_yh'],
                "code" => $coinconf['token_address'],
                "symbol" => "eos",
            ];
            $account_info = $EosClient->get_currency_balance($tradeInfo);
            $info['name'] = $coin;
            $info['balance'] = $account_info[0];
            $info['version'] = $json->server_version;
            $info['headers'] = $json->last_irreversible_block_num;
            $info['headerss'] = $json->head_block_num;
            $info['verificationprogress'] = $info['headers'] / $info['headerss'];
            $info['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');
        }elseif ($coinconf['type'] == 'xrp') {
            $xrpData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="xrp"')->find();
            if ($xrpData){
                $xrpClient = new XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['dj_yh'], $xrpData['dj_mm'], $xrpData['token_address']);
                $xrpInfo = $xrpClient->accountInfo();
                if (strtolower($xrpInfo['result']['status']) == 'success'){
                    $info['name'] = $coin;
                    $info['balance'] = $xrpInfo['result']['account_data']['Balance']/1000000;
                    $info['version'] = '1.0';
                    $info['headers'] = '';
                    $info['headerss'] = '';
                    $info['verificationprogress'] = '';
                    $info['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');
                }else{
                    $this->error('获取钱包数据失败！');
                }
            }else{
                $this->error('获取钱包数据失败！');
            }
        }
        $this->assign('data', $info);
        $this->display();
    }

    public function coinUser()
    {
        $coin =strval(I('coin'));
        $coinconf = M('coin')->where(array('name' => $coin))->find();
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
                $userid = M('User')->where(array('username' => $k))->getField('id');
                $user_coin = M('UserCoin')->where(array('userid' => $userid))->find();
                $list[$k]['id'] = $userid;
                $list[$k]['xnb'] = $user_coin[$coin];
                $list[$k]['xnbd'] = $user_coin[$coin . 'd'];
                $list[$k]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
                $list[$k]['xnbb'] = $user_coin[$coin . 'b'];
                $list[$k]['username'] =M('user')->where(['id' => $user_coin['userid']])->getField('username');
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
                    $user_coin = M('user_coin')->where(array($coin . 'b' => $addr))->find();
                    $list[$key]['addr'] = $addr;
                    if ($user_coin) {
                        $list[$key]['id'] = $user_coin['userid'];
                        $list[$key]['xnb'] = $user_coin[$coin];
                        $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                        $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                        $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                        $list[$key]['username'] = M('user')->where(['id' => $user_coin['userid']])->getField('username');
                    }
                }
            } else {
                foreach ($addrs as $key => $addr) {
                    //钱包地址余额
                    $list[$key]['num'] = $CoinClient->eth_getBalance($addr);
                    $user_coin = M('user_coin')->where(array($coin . 'b' => $addr))->find();
                    $list[$key]['addr'] = $addr;
                    if ($user_coin) {
                        $list[$key]['id'] = $user_coin['userid'];
                        $list[$key]['xnb'] = $user_coin[$coin];
                        $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                        $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                        $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                        $list[$key]['username'] = M('user')->where(['id' => $user_coin['userid']])->getField('username');
                    }
                }
            }
        }elseif($coinconf['type'] == 'eos'){
            $coinb = $coin . 'b';
            $coinp = $coin . 'p';
            $coind = $coin . 'd';
            $addrs = M('user_coin')->field("userid, $coin , $coinb , $coind , $coinp ")->where(array($coinb => ['neq',''] , $coinp => ['neq', '']))->select();
            $Page = new \Think\Page(count($addrs), 15);
            $addrs = array_slice($addrs, $Page->firstRow, $Page->listRows, true);
            $Page = new \Think\Page(count($addrs), 15);
            $show = $Page->show();
            $arr = array_slice($addrs, $Page->firstRow, $Page->listRows, true);
            foreach ($addrs as $key => $addr) {
                $list[$key]['id'] = $addr['userid'];
                $list[$key]['xnb'] = $addr[$coin];
                $list[$key]['num'] = $addr[$coin];
                $list[$key]['addr'] = $addr[$coind];
                $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                $list[$key]['addr'] = $addr[$coinb];
                $list[$key]['xnbb'] = $addr[$coinp];
                $list[$key]['username'] = M('user')->where(['id' => $addr['userid']])->getField('username');
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function coinQing()
    {
        $coin =strval(I('coin'));
        $this->error('兄弟姐妹们别乱来！');
        if (!C('coin')[$coin]) {
            $this->error('参数错误！');
        }

        $info = M()->execute('UPDATE `weike_user_coin` SET `' . trim($coin) . 'b`=\'\' ;');

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
        $filename = md5($_FILES['upload_file0']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

    public function text()
    {
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
        $where = array();

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

        $count = M('Text')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Text')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function textEdit()
    {
        $id = I('id/d');
        $_GET = I('get./a');
        if (empty($_POST)) {
            if ($id) {
                $this->data = M('Text')->where(array('id' => trim($_GET['id'])))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            $_POST['endtime'] = time();
            if ($_POST['id']) {
                $rs = M('Text')->save($_POST);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = M('Text')->add($_POST);
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
        $type =I('GET.type/s','resume');
        $_POST = I('post./a');
        if (empty($_POST)) {
            $this->display();
        } else {
            foreach ($_POST['id'] as $key => $value) {
                if ($type == 'resume') {
                    $rs = M('text')->save(['id' => $value, 'status' => 1, 'endtime' => time()]);
                } else {
                    $rs = M('text')->save(['id' => $value, 'status' => 0, 'endtime' => time()]);
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
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function qitaEdit()
    {
        if (M('Config')->where(array('id' => 1))->save($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function daohang()
    {
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => I('get.name')))->getField('id');
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

        $count = M('Daohang')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Daohang')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function daohangEdit()
    {
        $id = I('id/d');
        if (empty($_POST)) {
            if ($id) {
                $this->data = M('Daohang')->where(array('id' => trim(I('get.id'))))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if ($_POST['id']) {
                $rs = M('Daohang')->save($_POST);
            } else {
                $_POST['addtime'] = time();
                $rs = M('Daohang')->add($_POST);
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
        $id = I('id/a');
        $type = I('get.type/s');
        $moble = I('moble/s','Daohang');
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
                    S('daohang',NULL);
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (M($moble)->where($where)->save($data)) {
            S('daohang',NULL);
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }
}

?>
