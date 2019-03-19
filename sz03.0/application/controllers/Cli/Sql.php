<?php
/**
 * 导入HUO数据
 *
 */
class Cli_SqlController extends Ctrl_Cli
{

    public function init()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1280000000M');


        //连接数据库
        $this->huo = new Orm_Base(0, 'huo', 'weike');
        $this->mo = new Orm_Base();

        //单次最大插入数据
        $this->max_num = 1000;
        $this->max_count = 10000;
    }

    //单次执行
    public function runAction()
    {
        $this->userAction();
        $this->orderAction();
        $this->exchangeAction();
        $this->user_loginAction();
        $this->autonymAction();
        $this->addressAction();
    }

    //user表
    public function userAction()
    {
        //连接数据库
        $huo = new Orm_Base(0, 'huo', 'weike');
        $mo = new Orm_Base();

        $mo->table = "user";
        $huo->table = "weike_user";

        $mo->exec("truncate table user");


        $max_uid = $mo->order('uid desc')->fOne('uid');
        if (!$max_uid) $max_uid = 0;


        $userids = $huo->field('id')->where("id>{$max_uid}")->fList();

        foreach ($userids as $v) {
            $huo->table = "weike_user";
            $u = $huo->where("id={$v['id']}")->fRow();

            $huo->table = "weike_user_coin";
            $ucoin = $huo->where("userid={$v['id']}")->fRow();


            $area = substr($u['moble'], 0, 3);
            $prand = Tool_Md5::getUserRand();
            $password = md5(Tool_Md5::getUserSeed($prand) . 'login' . $u['password'] . Tool_Md5::SEED_FOR_PASSWD);

            $pwdtrade = md5(Tool_Md5::getUserSeed($prand) . 'trade' . $u['paypassword'] . Tool_Md5::SEED_FOR_PASSWD);


            $in_data = [
                'uid' => $u['id'],
                'mo' => $u['username'],
                'area' => $area,
                'name' => $u['username'],
                'idcard' => $u['idcard'],
                'pwd' => $password,
                'pwdtrade' => $pwdtrade,
                'role' => 'user',
                'prand' => $prand,
                'btc_over' => $ucoin['btc'] ? $ucoin['btc'] : 0,
                'btc_lock' => $ucoin['btcd'] ? $ucoin['btcd'] : 0,
                'eth_over' => $ucoin['eth'] ? $ucoin['eth'] : 0,
                'eth_lock' => $ucoin['ethd'] ? $ucoin['ethd'] : 0,
                'eos_over' => $ucoin['eos'] ? $ucoin['eos'] : 0,
                'eos_lock' => $ucoin['eosd'] ? $ucoin['eosd'] : 0,
                'etc_over' => $ucoin['etc'] ? $ucoin['etc'] : 0,
                'etc_lock' => $ucoin['etcd'] ? $ucoin['etcd'] : 0,
                'xrp_over' => $ucoin['xrp'] ? $ucoin['xrp'] : 0,
                'xrp_lock' => $ucoin['xrpd'] ? $ucoin['xrpd'] : 0,
                'doge_over' => $ucoin['doge'] ? $ucoin['doge'] : 0,
                'doge_lock' => $ucoin['doged'] ? $ucoin['doged'] : 0,
                'ifc_over' => $ucoin['ifc'] ? $ucoin['ifc'] : 0,
                'ifc_lock' => $ucoin['ifcd'] ? $ucoin['ifcd'] : 0,
                'wc_over' => $ucoin['wc'] ? $ucoin['wc'] : 0,
                'wc_lock' => $ucoin['wcd'] ? $ucoin['wcd'] : 0,
                'wcg_over' => $ucoin['wcg'] ? $ucoin['wcg'] : 0,
                'wcg_lock' => $ucoin['wcgd'] ? $ucoin['wcgd'] : 0,
                'bcd_over' => $ucoin['bcd'] ? $ucoin['bcd'] : 0,
                'bcd_lock' => $ucoin['bcdd'] ? $ucoin['bcdd'] : 0,
                'qtum_over' => $ucoin['qtum'] ? $ucoin['qtum'] : 0,
                'qtum_lock' => $ucoin['qtumd'] ? $ucoin['qtumd'] : 0,
                'oioc_over' => $ucoin['oioc'] ? $ucoin['oioc'] : 0,
                'oioc_lock' => $ucoin['oiocd'] ? $ucoin['oiocd'] : 0,
                'bcx_over' => $ucoin['bcx'] ? $ucoin['bcx'] : 0,
                'bcx_lock' => $ucoin['bcxd'] ? $ucoin['bcxd'] : 0,
                'btm_over' => $ucoin['btm'] ? $ucoin['btm'] : 0,
                'btm_lock' => $ucoin['btmd'] ? $ucoin['btmd'] : 0,
                'btmz_over' => $ucoin['btmz'] ? $ucoin['btmz'] : 0,
                'btmz_lock' => $ucoin['btmzd'] ? $ucoin['btmzd'] : 0,
                'mtr_over' => $ucoin['mtr'] ? $ucoin['mtr'] : 0,
                'mtr_lock' => $ucoin['mtrd'] ? $ucoin['mtrd'] : 0,
                'drt_over' => $ucoin['drt'] ? $ucoin['drt'] : 0,
                'drt_lock' => $ucoin['drtd'] ? $ucoin['drtd'] : 0,
                'mat_over' => $ucoin['mat'] ? $ucoin['mat'] : 0,
                'mat_lock' => $ucoin['matd'] ? $ucoin['matd'] : 0,
                'eac_over' => $ucoin['eac'] ? $ucoin['eac'] : 0,
                'eac_lock' => $ucoin['eacd'] ? $ucoin['eacd'] : 0,
                'sie_over' => $ucoin['sie'] ? $ucoin['sie'] : 0,
                'sie_lock' => $ucoin['sied'] ? $ucoin['sied'] : 0,
                'eqt_over' => $ucoin['eqt'] ? $ucoin['eqt'] : 0,
                'eqt_lock' => $ucoin['eqtd'] ? $ucoin['eqtd'] : 0,
                'unih_over' => $ucoin['unih'] ? $ucoin['unih'] : 0,
                'unih_lock' => $ucoin['unihd'] ? $ucoin['unihd'] : 0,
                'wos_over' => $ucoin['wos'] ? $ucoin['wos'] : 0,
                'wos_lock' => $ucoin['wosd'] ? $ucoin['wosd'] : 0,
                'ctm_over' => $ucoin['ctm'] ? $ucoin['ctm'] : 0,
                'ctm_lock' => $ucoin['ctmd'] ? $ucoin['ctmd'] : 0,
                'credit' => 0,
                'created' => $u['addtime'],
                'createip' => $u['addip'],
                'updated' => 0,
                'updateip' => '',
                'source' => 0,
                'registertype' => 2,
                'from_uid' => $u['invit_1'] ? $u['invit_1'] : 0,
                'rebate' => 0,
                'google_key' => '',
                'cnyx_over' => $ucoin['cny'] ? $ucoin['cny'] : 0,
                'cnyx_lock' => $ucoin['cnyd'] ? $ucoin['cnyd'] : 0,
            ];
            $id = $mo->insert($in_data);


            if (!$id) {
                echo $mo->getLastSql();
                die("\n" . $v['id'] . '时出错');
            }

        }


        $mo_count = $mo->fOne("count(uid)");
        $huo_count = $huo->fOne("count(id)");

        echo $mo_count . '---' . $huo_count;

        die;
        "USER表导入结束";
    }

    //trust 委托订单表
    public function trustAction()
    {

        //连接数据库
//        $huo = new Orm_Base(0,'huo','weike');
        $mo = new Orm_Base();

        $coins = $mo->table('coin')->field('name')->fList();

//        $time = strtotime("2019-01-01 00:00:00");
        foreach ($coins as $val) {

            $this->mo->exec("truncate table trust_{$val['name']}coin");
            continue;
            $huo->table = 'weike_trade';
            echo $val['name'] . "\n";

            $mo->table = 'trust_' . $val['name'] . 'coin';


            $tra_count = $huo->where("market='{$val['name']}_cny'")->fOne("count(id)");
            echo $tra_count . "\n";

            if ($tra_count > $this->max_count) {
                $num = ceil($tra_count / $this->max_count);
            } else {
                $num = 1;
            }


            for ($i = 1; $i <= $num; $i++) {
                $start = ($i - 1) * $this->max_count;
                echo $start . '---' . $this->max_count . "\n";
                $tras = $huo->where("market='{$val['name']}_cny'")->field('userid,price,num,deal,type,status,addtime,endtime')->limit("$start,$this->max_count")->fList();
                foreach ($tras as $kk => $v) {
                    $numberover = $v['num'] - $v['deal'] < 0 ? 0 : round($v['num'] - $v['deal'], 8);
                    $flag = $v['type'] == 1 ? 'buy' : 'sale';

                    $status = [0 => 0, 1 => 2, 2 => 3];
                    $sta = isset($status[$v['status']]) ? $status[$v['status']] : 3;
//                    if($sta==0 && $v['num']>$v['deal']) $sta = 1;

                    if ($kk % $this->max_num == 0) {
                        $sql = "insert into trust_{$val['name']}coin (uid,price,`number`,numberover,numberdeal,flag,status,isnew,coin_from,coin_to,created,updated,trust_type) values ";
                    }
                    $sql .= "('{$v['userid']}','{$v['price']}','{$v['num']}','{$numberover}','{$v['deal']}','{$flag}','{$sta}','N','{$val['name']}','cnyx','{$v['addtime']}','{$v['endtime']}','0'),";

                    if ($kk && $kk % $this->max_num == ($this->max_num - 1) || $kk == (count($tras) - 1)) {
                        echo '$i:' . $i . "\n";
                        echo '$kk:' . $kk . "--count:" . count($tras) . "\n";
                        $sql = substr($sql, 0, strlen($sql) - 1);
                        $mo->exec($sql);
                    }
                }

            }


            echo "{$val['name']}结束" . "\n";
        }

        die('成功');
    }

    //order订单表
    public function orderAction()
    {

        $coins = $this->mo->table('coin')->field('name')->fList();

//        Tool_Out::p($coins);die;

        foreach ($coins as $val) {
            $this->mo->exec("truncate table order_{$val['name']}coin");

            $this->huo->table = 'weike_trade_log';
            echo $val['name'] . "\n";

            $this->mo->table = 'order_' . $val['name'] . 'coin';

            $tra_count = $this->huo->where("market='{$val['name']}_cny'")->fOne("count(id)");
            echo $tra_count . "\n";
            $max_count = 30000;
            if ($tra_count > $max_count) {
                $num = ceil($tra_count / $max_count);
            } else {
                $num = 1;
            }

            for ($i = 1; $i <= $num; $i++) {
                $start = ($i - 1) * $max_count;
                echo $start . '---' . $max_count . "\n";

                $tras = $this->huo->where("market='{$val['name']}_cny'")->field('price,num,userid,peerid,type,addtime,fee_buy,fee_sell')->limit("$start,$max_count")->fList();
                echo "count:" . count($tras) . "\n";

                foreach ($tras as $kk => $v) {
                    $opt = $v['type'] == 1 ? '1' : '2';

                    if ($kk % $this->max_num == 0) {
                        $sql = "insert into order_{$val['name']}coin (price,`number`,buy_tid,buy_uid,sale_tid,sale_uid,opt,coin_from,coin_to,created,buy_fee,sale_fee) values ";
                    }
                    $sql .= "('{$v['price']}','{$v['num']}','0','{$v['userid']}','0','{$v['peerid']}','{$opt}','{$val['name']}','cnyx','{$v['addtime']}','{$v['fee_buy']}','{$v['fee_sell']}'),";

                    if ($kk && $kk % ($this->max_num) == ($this->max_num - 1) || $kk == (count($tras) - 1)) {
                        $sql = substr($sql, 0, strlen($sql) - 1);
                        $this->mo->exec($sql);
                    }
                }


            }

            echo "{$val['name']}结束" . "\n";
        }

        die('成功');
    }

    //exchange表
    public function exchangeAction()
    {
        $coins = $this->mo->table('coin')->field('name')->fList();





        $status = ['0'=>'待审核','1'=>'成功','2'=>'已取消'];
        foreach ($coins as $val) {
            $this->mo->exec("truncate table exchange_{$val['name']}");

            echo $val['name'] . "\n";

            $this->mo->table = 'exchange_' . $val['name'];

//            $tra_count = $this->huo->where("coinname='{$val['name']}'")->fOne("count(id)");
//            echo $tra_count . "\n";

            $tras = $this->huo->table('weike_myzr')->where("coinname='{$val['name']}'")->fList();
            echo "count:" . count($tras) . "\n";

//            Tool_Out::p($tras);die;

            foreach ($tras as $kk => $v) {


                if($v['status']==0) $statu = '待审核';
                if($v['status']==1) $statu = '成功';
                if($v['status']==2) $statu = '已取消';
                $v['num'] = $v['num'] ? $v['num'] : 0;
                $v['mum'] = $v['mum'] ? $v['mum'] : 0;

                if ($kk % $this->max_num == 0) {
                    $sql = "insert into exchange_{$val['name']} (uid,wallet,label,txid,confirm,`number`,opt_type,status,created,updated,platform_fee,number_real,bak) values ";
                }

                $sql .= "('{$v['userid']}','{$v['username']}','{$v['tradeno']}','{$v['txid']}','0','{$v['num']}','in','{$statu}','{$v['addtime']}','{$v['endtime']}','{$v['fee']}','{$v['mum']}','{$v['tradeno']}'),";

                if ($kk && $kk % ($this->max_num) == ($this->max_num - 1) || $kk == (count($tras) - 1)) {
                    $sql = substr($sql, 0, strlen($sql) - 1);
                    $zrids[$kk] = $this->mo->exec($sql);
                    if(!$zrids[$kk]) exit($this->mo->getLastSql());
                }
            }

            $traszc = $this->huo->table('weike_myzc')->where("coinname='{$val['name']}'")->fList();

//            Tool_Out::p($traszc[0]);die;
            foreach ($traszc as $kk => $v) {
                if($v['status']==0) $statu = '待审核';
                if($v['status']==1) $statu = '成功';
                if($v['status']==2) $statu = '已取消';
                $v['num'] = $v['num'] ? $v['num'] : 0;
                $v['mum'] = $v['mum'] ? $v['mum'] : 0;

                if ($kk % $this->max_num == 0) {
                    $sql = "insert into exchange_{$val['name']} (uid,wallet,txid,confirm,`number`,opt_type,status,created,updated,platform_fee,number_real) values ";
                }
                $sql .= "('{$v['userid']}','{$v['username']}','{$v['txid']}','0','{$v['num']}','out','{$statu}','{$v['addtime']}','{$v['endtime']}','{$v['fee']}','{$v['mum']}'),";

                if ($kk && $kk % ($this->max_num) == ($this->max_num - 1) || $kk == (count($traszc) - 1)) {
                    $sql = substr($sql, 0, strlen($sql) - 1);
                    $zcids[$kk] = $this->mo->exec($sql);
                    echo '$kk:'.$kk;
                    if(!$zcids[$kk]) exit($this->mo->getLastSql());
                }
            }

            echo "{$val['name']}结束" . "\n";
        }

        die('成功');


    }

    //登录日志表
    public function user_loginAction()
    {


        $log_count = $logins = $this->huo->table('weike_user_log')->fOne("count(id)");

        echo $log_count . "\n";
        $this->mo->table('user_login')->exec("truncate table user_login");

        $max_count = 500000;
        if ($log_count > $max_count) {
            $num = ceil($log_count / $max_count);
        } else {
            $num = 1;
        }
        for ($i = 0; $i < $num; $i++) {
            $start = $i * $max_count;
            echo $start . '---' . $max_count . "\n";

            $logins = $this->huo->table('weike_user_log')->field('userid,addtime,addip,addr')->limit("$start,$max_count")->fList();

            //清空表
            foreach ($logins as $k => $v) {

                if ($k % $this->max_num == 0) {
                    $sql = "insert into user_login (uid,created,createdip,area) values ";
                }

                $sql .= "('{$v['userid']}','{$v['addtime']}','{$v['addip']}','{$v['addr']}'),";

                if ($k && $k % ($this->max_num) == ($this->max_num - 1) || $k == (count($logins) - 1)) {
                    $sql = substr($sql, 0, strlen($sql) - 1);
                    $this->mo->exec($sql);
                }
            }
        }
        die('成功');
    }

    //实名认证表
    public function autonymAction()
    {
        //清空表
        $this->mo->exec("truncate table autonym");

        $count = $this->huo->table('weike_user')->where("idcard != ''")->fOne("count(id)");


        echo "count:" . $count . "\n";

        $admins = $this->huo->table('weike_admin')->fList();

        $admins = array_column($admins, 'id', 'username');

        if ($count > $this->max_count) {
            $num = ceil($count / $this->max_count);
        } else {
            $num = 1;
        }

        for ($i = 0; $i < $num; $i++) {
            $start = $i * $this->max_count;
            echo $start . '---' . $this->max_count . "\n";

            $users = $this->huo->table('weike_user')->where("idcard != ''")->field('id,truename,idcard,idcardauth,idcardimg1,idcardinfo,czr')->limit("$start,$this->max_count")->fList();

            foreach ($users as $k => $v) {

                if ($k % $this->max_num == 0) {
                    $sql = "insert into autonym (uid,`name`,cardtype,idcard,status,frontFace,backFace,handkeep,admin,content) values ";
                }

                $status = $v['idcardauth'] == 1 ? 2 : 1;
                $admin = isset($admins[$v['czr']]) ? $admins[$v['czr']] : 0;
                $imgs = explode("_", $v['idcardimg1']);
                $path = "https://firecoin.oss-cn-shenzhen.aliyuncs.com/Upload/idcard/";
                $img1 = isset($imgs[0]) && $imgs[0] ? $path . $imgs[0] : '';
                $img2 = isset($imgs[1]) && $imgs[1] ? $path . $imgs[1] : '';
                $img3 = isset($imgs[2]) && $imgs[2] ? $path . $imgs[2] : '';

                $sql .= "('{$v['id']}','{$v['truename']}','1','{$v['idcard']}','{$status}','{$img1}','{$img2}','{$img3}','{$admin}','{$v['idcardinfo']}'),";


                if ($k && $k % ($this->max_num) == ($this->max_num - 1) || $k == (count($users) - 1)) {
                    $sql = substr($sql, 0, strlen($sql) - 1);
                    $this->mo->exec($sql);
                }
            }
        }
        die('成功');
    }

    //address表
    public function addressAction()
    {
        //连接数据库
        $huo = new Orm_Base(0, 'huo', 'weike');
        $mo = new Orm_Base();

        $mo->table = "address";
        $huo->table = "weike_user_coin";

        $mo->exec("truncate table address");


        //记录最大ID
        $max_uid = $mo->order('id desc')->fOne('uid');
        if (!$max_uid) $max_uid = 0;


        $usercoins = $huo->field('id')->where("userid>{$max_uid}")->fList();
        foreach ($usercoins as $v) {
            $v = $huo->where("id={$v['id']}")->fRow();

            if ($v['btcb']) {
                $btc = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['btcb'],
                    'coin' => 'btc',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$btc) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . 'btc错误');
                }
            }

            if ($v['ethb']) {
                $eth = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['ethb'],
                    'coin' => 'eth',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$eth) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$eth错误');
                }
            }

            if ($v['etcb']) {
                $etc = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['etcb'],
                    'coin' => 'etc',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$etc) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$etc错误');
                }
            }

            if ($v['dogeb']) {
                $doge = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['dogeb'],
                    'coin' => 'doge',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$doge) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$doge错误');
                }
            }
            if ($v['wcb']) {
                $wc = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['wcb'],
                    'coin' => 'wc',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$wc) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$wc错误');
                }
            }
            if ($v['ifcb']) {
                $ifc = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['ifcb'],
                    'coin' => 'ifc',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$ifc) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$ifc错误');
                }
            }
            if ($v['qtumb']) {
                $qtum = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['qtumb'],
                    'coin' => 'qtum',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$qtum) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$qtum错误');
                }
            }

            if ($v['bcdb']) {
                $bcd = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['bcdb'],
                    'coin' => 'bcd',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$bcd) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$bcd错误');
                }
            }

            if ($v['bcxb']) {
                $bcx = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['bcxb'],
                    'coin' => 'bcx',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$bcx) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$bcx错误');
                }
            }

            if ($v['eacb']) {
                $eac = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['eacb'],
                    'coin' => 'eac',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$eac) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$eac错误');
                }
            }

            if ($v['ejfb']) {
                $ejf = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['ejfb'],
                    'coin' => 'ejf',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$ejf) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$ejf错误');
                }
            }

            if ($v['oiocb']) {
                $oioc = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['oiocb'],
                    'coin' => 'oioc',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$oioc) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$oioc错误');
                }
            }

            if ($v['wcgb']) {
                $wcg = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['wcgb'],
                    'coin' => 'wcg',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$wcg) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$wcg错误');
                }
            }

            if ($v['btmb']) {
                $btm = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['btmb'],
                    'coin' => 'btm',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);

                if (!$btm) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$btm错误');
                }
            }

            if ($v['eosb']) {
                $eos = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['eosb'],
                    'coin' => 'eos',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$eos) die($v['userid'] . '$eos错误');
            }

            if (isset($v['eicb']) && $v['eicb']) {
                $eic = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['eicb'],
                    'coin' => 'eic',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$eic) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$eic错误');
                }
            }

            if (isset($v['sieb']) && $v['sieb']) {
                $sie = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['sieb'],
                    'coin' => 'sie',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$sie) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$sie错误');
                }
            }

            if (isset($v['drtb']) && $v['drtb']) {
                $drt = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['drtb'],
                    'coin' => 'drt',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$drt) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$drt错误');
                }
            }

            if (isset($v['matb']) && $v['matb']) {
                $mat = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['matb'],
                    'coin' => 'mat',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$mat) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$mat错误');
                }
            }

            if (isset($v['mtrb']) && $v['mtrb']) {
                $mtr = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['mtrb'],
                    'coin' => 'mtr',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$mtr) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$mtr错误');
                }
            }
            if (isset($v['xrpb']) && $v['xrpb']) {
                $xrp = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['xrpb'],
                    'coin' => 'xrp',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$xrp) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$xrp错误');
                }
            }

            if (isset($v['unihb']) && $v['unihb']) {
                $unih = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['unihb'],
                    'coin' => 'unih',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$unih) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$unih错误');
                }
            }

            if (isset($v['wosb']) && $v['wosb']) {
                $wos = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['wosb'],
                    'coin' => 'wos',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$wos) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$wos错误');
                }
            }

            if (isset($v['eqtb']) && $v['eqtb']) {
                $eqt = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['eqtb'],
                    'coin' => 'eqt',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$eqt) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$eqt错误');
                }
            }

            if (isset($v['ctmb']) && $v['ctmb']) {
                $ctm = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['ctmb'],
                    'coin' => 'ctm',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$ctm) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$ctm错误');
                }
            }
            if (isset($v['btmzb']) && $v['btmzb']) {
                $btmz = $mo->insert([
                    'uid' => $v['userid'],
                    'address' => $v['btmz'],
                    'coin' => 'btmz',
                    'status' => 0,
                    'created' => time(),
                    'updated' => time(),
                ]);
                if (!$btmz) {
                    echo $mo->getLastSql() . "\n";
                    die($v['userid'] . '$btmz错误');
                }
            }
        }
        $mo_count = $mo->fOne("count(id)");
        $huo_count = $huo->fOne("count(id)");

        echo $mo_count . '---' . $huo_count;

        die('成功');
    }


    //用户支付表
    public function user_bankAction(){
        $this->mo->exec("truncate table user_bank");

        $userBanks = $this->huo->query("select u.username,u.truename,ub.* from weike_user_bank ub left join weike_user u on ub.userid=u.id");
        $path = "https://firecoin.oss-cn-shenzhen.aliyuncs.com/Upload/public/";

        $types = [0=>1,1=>2,2=>3];
        $status = [0=>0,1=>1,2=>0];
        foreach ($userBanks as $k=>$v){
            if ($k % $this->max_num == 0) {
                $sql = "insert into user_bank (uid,username,type,name,bankcard,addtime,img,status) values ";
            }

            $type = $types[$v['Paytype']];
            $img = $v['img']?$path.$v['img']:'';
            $sql .= "('{$v['userid']}','{$v['username']}','{$type}','{$v['truename']}','{$v['bankcard']}','{$v['addtime']}','{$img}','{$status[$v['status']]}'),";

            if ($k && $k % ($this->max_num) == ($this->max_num - 1) || $k == (count($userBanks) - 1)) {
                $sql = substr($sql, 0, strlen($sql) - 1);
                $this->mo->exec($sql);
            }
        }

        die('成功');
    }


    //冻结用户表
    public function user_forbiddenAction(){

        $user_for = $this->huo->table('weike_user')->where("status=0")->fList();

        $this->mo->table('user_forbidden');
        foreach ($user_for as $k=>$v){

            if ($k % $this->max_num == 0) {
                $sql = "insert into user_forbidden (uid) values ";
            }

            $sql .= "('{$v['id']}'),";

            if ($k && $k % ($this->max_num) == ($this->max_num - 1) || $k == (count($user_for) - 1)) {
                $sql = substr($sql, 0, strlen($sql) - 1);
                $this->mo->exec($sql);
            }

        }

        die('成功');
    }


    //更新user表 手机号码归属地
    public function up_userarea(){

        $huo = $this->huo->query("SELECT id. FROM weike_user WHERE moble LIKE '%+852%'");
//            table('weike_user')->SELECT * FROM weike_user WHERE moble LIKE '%+852%'

    }

    //老平台ETH地址密码转移
    public function eth_passwordAction($coin){

        $eth_huo = $this->huo->table('weike_user_coin')->field("userid,{$coin}b,{$coin}p")->where("{$coin}b!='' and {$coin}p!=''")->fList();

        echo count($eth_huo).PHP_EOL;
        $i=0;
        foreach ($eth_huo as $k=>$v){

            $eth_address  = $this->mo->table("address")->where("uid={$v['userid']} and coin='{$coin}'")->fOne("address");

            if($eth_address==$v["{$coin}b"]){
                $i++;
                $this->mo->table("address")->where("uid={$v['userid']} and coin='{$coin}'")->update(['secret'=>$v["{$coin}p"]]);
            }else{
                echo "新平台地址ID{$eth_address['id']}...老平台userid:{$v['userid']} 老平台地址:{$v["{$coin}b"]}---新平台地址:{$eth_address}".PHP_EOL;
            }
        }

        echo "改变了{$i}条数据".PHP_EOL;
        die;
    }

    //新平台ETH地址写入   必须在老平台密码转移之后执行
    public function eth_secretAction($coin){

        $eth_adds = $this->mo->table("address")->field("id")->where("coin='{$coin}' and secret=''")->fList();

        echo count($eth_adds).PHP_EOL;
        $i=0;
        foreach ($eth_adds as $v){
            if($this->mo->table("address")->where("id={$v['id']}")->update(['secret'=>'bjs88888'])){
                $i++;
            }
        }
        echo "改变了{$i}条数据".PHP_EOL;
        die;
    }
}
