<?php
namespace app\admin\controller;

use think\Db;

class Config extends Admin
{
    public function index()
    {
        $this->data = Db::name('Config')->where(array('id' => 1))->find();
        $this->assign('data',$this->data);
        return $this->fetch();
    }

    public function edit()
    {
        if (Db::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    //upload config picture
    public function image()
    {
        if ($_FILES['upload_file0']['size'] > 3145728) {
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/public/';
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path . $filename, $_FILES['upload_file0']['tmp_name']);

        if (!$info) {
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }

    //weixin and alipay upload business prcture
    public function merchant()
    {
        $type = input('type/s');

        if ($_FILES['upload_file0']['size'] > 3145728) {
            echo "error";
            exit();
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            echo "error";
            exit();
        }

        $path = 'Upload/merchant/';
        $filename = date('Ymd') . '/' . md5($type . $_FILES['upload_file0']['name']) . '.' . $ext;
        $info = oss_upload($path . $filename, $_FILES['upload_file0']['tmp_name']);

        if (!$info) {
            echo "error";
            exit();
        }

        echo $filename;
        exit();
    }

    public function moble()
    {
        $this->data = Db::name('Config')->where(array('id' => 1))->find();
        return $this->fetch();
    }

    public function mobleEdit()
    {
        if (Db::name('Config')->where(array('id' => 1))->update($_POST)) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败');
        }
    }

    public function contact()
    {
        $this->data = Db::name('Config')->where(array('id' => 1))->find();
        $this->assign('data',$this->data);
        return $this->fetch();
    }

    public function contactEdit()
    {
        if (Db::name('Config')->where(array('id' => 1))->update($_POST)) {
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
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $list = Db::name('Coin')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function coinEdit()
    {
        $id = input('id/d');
        $_POST = input('post.');

        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = Db::name('Coin')->where(array('id' => $id))->find();
            }

            $weike_getCoreConfig = weike_getCoreConfig();
            if (!$weike_getCoreConfig) {
                $this->error('核心配置有误');
            }

            $this->assign("weike_opencoin", $weike_getCoreConfig['weike_opencoin']);
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST['fee_bili'] = floatval(input('post.fee_bili'));
            if (!empty($_POST['fee_bili']) && (($_POST['fee_bili'] < 0.01) || (100 < $_POST['fee_bili']))) {
                $this->error('挂单比例只能是0.01--100之间(不用填写%)！');
            }

            $_POST['zr_zs'] = floatval(input('post.zr_zs'));
            if (!empty($_POST['zr_zs']) && (($_POST['zr_zs'] < 0.01) || (100 < $_POST['zr_zs']))) {
                $this->error('转入赠送只能是0.01--100之间(不用填写%)！');
            }

            $_POST['zr_dz'] = intval(input('post.zr_dz'));
            $_POST['zc_fee'] = floatval(input('post.zc_fee'));
            if (!empty($_POST['zc_fee']) && (($_POST['zc_fee'] < 0.01) || (100 < $_POST['zc_fee']))) {
                $this->error('转出手续费只能是0.01--100之间(不用填写%)！');
            }

            $_POST['zc_user'] = input('post.zc_user');
            if ($_POST['zc_user'] == '') {
                $this->error('请填写官方手续费地址！');
            }
            if ($_POST['zc_user']) {
                if (!check($_POST['zc_user'], 'dw')) {
                    $this->error('官方手续费地址格式不正确！');
                }

                $ZcUser = Db::name('UserCoin')->where(array($_POST['name'] . 'b' => $_POST['zc_user']))->find();

                if (!$ZcUser) {
                    $this->error('在系统中查询不到[官方手续费地址],请务必填写正确！');
                }
            }

            $_POST['zc_min'] = floatval(input('post.zc_min'));
            $_POST['zc_max'] = floatval(input('post.zc_max'));
            if (!empty($_POST['id'])) {
                $rs = Db::name('Coin')->update($_POST);
            } else {
                $_POST['name']=input('post.name');
                if (!check($_POST['name'], 'n')) {
                    $this->error('币种简称只能是小写字母！');
                }

                $_POST['name'] = strtolower($_POST['name']);

                if (check($_POST['name'], 'username')) {
                    $this->error('币种名称格式不正确！');
                }

                if (Db::name('Coin')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('币种存在！');
                }

                $rea = Db::execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT \'0\' ');
                $reb = Db::execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT \'0\' ');
                $rec = Db::execute('ALTER TABLE  `weike_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL DEFAULT \'\'');
                $red = Db::execute('ALTER TABLE  `weike_coinlog` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) NOT NULL DEFAULT \'0\' ');
                $_POST['status'] = 1;
                $rs = Db::name('Coin')->insert($_POST);
            }

            if ($rs) {
                cache('home_coin', null);
                $this->success('操作成功！');
            } else {
                $this->error('数据未修改！');
            }
        }
    }

    public function coinStatus()
    {
        $_POST = input('post.');
        $_GET = input('param.');
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
                $rs = Db::name('Coin')->where($where)->select();
                foreach ($rs as $k => $v) {
                    $rs[] = Db::execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name']);
                    $rs[] = Db::execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name'] . 'd');
                    $rs[] = Db::execute('ALTER TABLE  `weike_user_coin` DROP COLUMN ' . $v['name'] . 'b');
                    $rs[] = Db::execute('ALTER TABLE  `weike_coinlog` DROP COLUMN ' . $v['name']);
                }

                if (Db::name('Coin')->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('参数非法');
        }

        if (Db::name('Coin')->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coinInfo()
    {
        $coin = strval(input('coin'));
        $coinconf = Db::name('coin')->where(array('name' => $coin))->find();
        $dj_username = config('coin')[$coin]['dj_yh'];
        $dj_password = config('coin')[$coin]['dj_mm'];
        $dj_address = config('coin')[$coin]['dj_zj'];
        $dj_port = config('coin')[$coin]['dj_dk'];

        if ($coinconf['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
            if ($coinconf['name'] == 'ltc' || $coinconf['name'] == 'btc') {
                $block_info = $CoinClient->getblockchaininfo();
                $block_version = $CoinClient->getnetworkinfo();
                $block_balance = $CoinClient->getwalletinfo();
                $info['name'] = $coin;
                $info['version'] = $block_version['version'];
                $info['headers'] = $block_info['blocks'];
                if (!isset($block_version['version']) || !$info['version']) {
                    $this->error('钱包对接失败！');
                }
                $info['headerss'] = $block_info['headers'];
                $info['balance'] = $block_balance['balance'];
                $info['verificationprogress'] = $info['headers'] / $info['headerss'];
                $info['num'] = Db::name('UserCoin')->sum($coin) + Db::name('UserCoin')->sum($coin . 'd');
            } else {
                $json = $CoinClient->getinfo();

                if (!isset($json['version']) || !$json['version']) {
                    $this->error('钱包对接失败！');
                }

                $info = $CoinClient->getinfo();
                $info['num'] = Db::name('UserCoin')->sum($coin) + Db::name('UserCoin')->sum($coin . 'd');
                $info['name'] = $coin;
            }

        } elseif ($coinconf['type'] == 'eth' || $coinconf['type'] == 'token') {
            $CoinClient = EthClient($dj_address, $dj_port);
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
            if ($coinconf['type'] === 'token') {
                foreach ($info['accounts'] as $key => $value) {
                    $call = [
                        'to' => $coinconf['token_address'],
                        'data' => '0x70a08231' . $CoinClient->data_pj($value)
                    ];
                    $sum += $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), $coinconf['decimals']);
                }
            } else {
                foreach ($info['accounts'] as $key => $value) {
                    $sum += $CoinClient->eth_getBalance($value);
                }
            }
            $info['balance'] = $sum;
            $info['num'] = Db::name('UserCoin')->sum($coin) + Db::name('UserCoin')->sum($coin . 'd');
            $info['name'] = $coin;
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
            $info['num'] = Db::name('UserCoin')->sum($coin) + Db::name('UserCoin')->sum($coin . 'd');
        }
        $this->assign('data', $info);
        return $this->fetch();
    }

    public function coinUser()
    {
        $coin = strval(input('coin'));
        $coinconf = Db::name('coin')->where(array('name' => $coin))->find();
        $dj_username = $coinconf['dj_yh'];
        $dj_password = $coinconf['dj_mm'];
        $dj_address = $coinconf['dj_zj'];
        $dj_port = $coinconf['dj_dk'];
        $list = [];
        $show = '';
        if ($coinconf['type'] == 'bit') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
            if ($coinconf['name'] == 'btc' || $coinconf['name'] == 'ltc') {
                $block_version = $CoinClient->getnetworkinfo();
                $info['version'] = $block_version['version'];
                if (!isset($block_version['version']) || !$info['version']) {
                    $this->error('钱包对接失败！');
                }
            } else {
                $json = $CoinClient->getinfo();
                if (!isset($json['version']) || !$json['version']) {
                    $this->error('钱包对接失败！');
                }
            }

            //获取钱包所有地址
            $arr = $CoinClient->listaccounts();
            $curPage = input('page') ? input('page') : 1;
            $listRow = 15;
            $showData = array_chunk($arr, $listRow, true);

            $showData = $showData[$curPage - 1];

            $Page = Bootstrap::make($showData, $listRow, $curPage, count($arr), false, [
                'var_page' => 'page',
                'path'     => url('Config/coinUser'),//这里根据需要修改url
                'query'    => [],
                'fragment' => '',
            ]);
            $Page->appends($_GET);
            $show = $Page->render();

            foreach ($showData as $k => $v) {
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
                $userid = Db::name('User')->where(array('username' => $k))->value('id');
                $user_coin = Db::name('UserCoin')->where(array('userid' => $userid))->find();
                $list[$k]['id'] = $userid;
                $list[$k]['xnb'] = $user_coin[$coin];
                $list[$k]['xnbd'] = $user_coin[$coin . 'd'];
                $list[$k]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
                $list[$k]['xnbb'] = $user_coin[$coin . 'b'];
                $list[$k]['username'] = Db::name('user')->where(['id' => $user_coin['userid']])->value('username');
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
            $curPage = input('page') ? input('page') : 1;
            $listRow = 15;
            $showData = array_chunk($addrs, $listRow, true);
            $showData = $showData[$curPage - 1];
            $Page = Bootstrap::make($showData, $listRow, $curPage, count($addrs), false, [
                'var_page' => 'page',
                'path'     => url('Config/coinUser'),//这里根据需要修改url
                'query'    => [],
                'fragment' => '',
            ]);
            $Page->appends($_GET);
            $show = $Page->render();

            if ($coinconf['type'] === 'token') {
                foreach ($showData as $key => $addr) {
                    //钱包地址余额
                    $call = [
                        'to' => $coinconf['token_address'],
                        'data' => '0x70a08231' . $CoinClient->data_pj($addr)
                    ];
                    $list[$key]['num'] = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), $coinconf['decimals']);
                    $user_coin = Db::name('user_coin')->where(array($coin . 'b' => $addr))->find();
                    $list[$key]['addr'] = $addr;
                    if ($user_coin) {
                        $list[$key]['id'] = $user_coin['userid'];
                        $list[$key]['xnb'] = $user_coin[$coin];
                        $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                        $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                        $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                        $list[$key]['username'] = Db::name('user')->where(['id' => $user_coin['userid']])->value('username');
                    }
                }
            } else {
                foreach ($showData as $key => $addr) {
                    //钱包地址余额
                    $list[$key]['num'] = $CoinClient->eth_getBalance($addr);
                    $user_coin = Db::name('user_coin')->where(array($coin . 'b' => $addr))->find();
                    $list[$key]['addr'] = $addr;
                    if ($user_coin) {
                        $list[$key]['id'] = $user_coin['userid'];
                        $list[$key]['xnb'] = $user_coin[$coin];
                        $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                        $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                        $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                        $list[$key]['username'] = Db::name('user')->where(['id' => $user_coin['userid']])->value('username');
                    }
                }
            }
        }elseif($coinconf['type'] == 'eos'){
            $coinb = $coin . 'b';
            $coinp = $coin . 'p';
            $coind = $coin . 'd';

            $addrs = Db::name('user_coin')->field("userid, $coin , $coinb , $coind , $coinp ")->where(array($coinb => ['neq',''] , $coinp => ['neq', '']))->paginate(15);
            $show = $addrs->render();
            $showData = $addrs->all();
            foreach ($showData as $key => $addr) {
                $list[$key]['id'] = $addr['userid'];
                $list[$key]['xnb'] = $addr[$coin];
                $list[$key]['num'] = $addr[$coin];
                $list[$key]['xnbd'] = $addr[$coind];
                $list[$key]['zj'] = $list[$key]['xnb'] + $list[$key]['xnbd'];
                $list[$key]['addr'] = $addr[$coinb];
                $list[$key]['xnbb'] = $addr[$coinp];
                $list[$key]['username'] = Db::name('user')->where(['id' => $addr['userid']])->value('username');

             }



        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function coinQing()
    {
        $coin = strval(input('coin'));
        $this->error('兄弟姐妹们别乱来！');
        if (!config('coin')[$coin]) {
            $this->error('参数错误！');
        }

        $info = Db::execute('UPDATE `weike_user_coin` SET `' . trim($coin) . 'b`=\'\' ;');

        if ($info) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function coinImage()
    {
        if ($_FILES['upload_file0']['size'] > 3145728) {
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/coin/';
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path . $filename, $_FILES['upload_file0']['tmp_name']);

        if (!$info) {
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
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }


        $list = Db::name('Text')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function textEdit()
    {
        $id = input('id/d');
        $_POST = input('post.');
        $_GET = input('param.');
        if (empty($_POST)) {
            if ($id) {
                $this->data = Db::name('Text')->where(array('id' => trim($_GET['id'])))->find();
            } else {
                $this->data = null;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST['endtime'] = time();
            if (!empty($_POST['id'])) {
                $rs = Db::name('Text')->update($_POST);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = Db::name('Text')->insert($_POST);
            }

            if ($rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }

    public function textStatus($type = 'resume')
    {
        $type = input('type', 'resume');
        $_POST = input('post.');
        if (empty($_POST)) {
            return $this->fetch();
        } else {
            foreach ($_POST['id'] as $key => $value) {
                if ($type == 'resume') {
                    $rs = Db::name('text')->update(['id' => $value, 'status' => 1, 'endtime' => time()]);
                } else {
                    $rs = Db::name('text')->update(['id' => $value, 'status' => 0, 'endtime' => time()]);
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
        $this->data = Db::name('Config')->where(array('id' => 1))->find();
        return $this->fetch();
    }

    public function qitaEdit()
    {
        if (Db::name('Config')->where(array('id' => 1))->update($_POST)) {
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
                $where['userid'] = Db::name('User')->where(array('username' => input('param.name')))->value('id');
            } else if ($field == 'title') {
                $where['title'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        } else {
            $where['status'] = array('neq', -1);
        }

        $list = Db::name('Daohang')->where($where)->order('id desc')->paginate(15);
        $show = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function daohangEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            if ($id) {
                $this->data = Db::name('Daohang')->where(array('id' => trim(input('param.id'))))->find();
            } else {
                $this->data = null;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            if (!empty($_POST['id'])) {
                $rs = Db::name('Daohang')->update($_POST);
            } else {
                $_POST['addtime'] = time();
                $rs = Db::name('Daohang')->insert($_POST);
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
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'Daohang');
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
                    cache('daohang', NULL);
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            cache('daohang', NULL);
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }
}

?>
