<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/1/3
 * Time: 19:38
 */
/**
 *  钱包轮询
 */
class Cli_WalletOutController extends Ctrl_Cli{

    //bit系列
    public function  bitAction($coin){
        $coin = $coin ? $coin : 'btc';

        //产生一个锁文件
        $lockfile = $this->setLock($coin,'Out');

        try{
            $exchangCoin = 'Exchange_'.ucfirst($coin).'Model';
            $exchangCoinModel = new $exchangCoin();
            $mo = Orm_Base::getInstance();

            $exchanges = $exchangCoinModel->where(['opt_type'=>'out','status'=>'等待'])->fList();

            if(!$exchanges)  Tool_Del::lock($lockfile,'没有转出的数据');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;

            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            $coinConf = CoinModel::getInstance()->where(['name'=>$coin])->fRow();

            $CoinClient = new Api_Rpc_CoinClient($coinConf['account'],$coinConf['password'],$address,$port,5,[],1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) Tool_Del::lock($lockfile,'钱包对接失败');

//        if(!$coinConf['fee_address']) die('官方手续费地址为空');

            $userModel = UserModel::getInstance();
            $wallet_pass = md5($coinConf['wallet_pass']);

            //传入钱包密码
            $CoinClient->walletpassphrase($wallet_pass, 60);
            foreach ($exchanges as $k=>$v) {

                $lock_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'冻结中']);
                if(!$lock_id) continue;
                //站内互转
                if($v['is_out'] || $v['to_uid']){
                    $mo->begin();

                    try{
                        $in_id = $exchangCoinModel->insert(['uid' => $v['to_uid'], 'wallet' => $v['wallet'], 'txid' => md5($v['wallet'] . $v['address'] . time()), 'number' => $v['number'], 'platform_fee' => $v['platform_fee'], 'opt_type'=>'in','number_real' =>$v['number_real'], 'created' => time(), 'status' => '成功']);
                        $to_id = $userModel->exec("update user set {$coin}_over={$coin}_over+{$v['number_real']} where uid={$v['to_uid']}");
                        $ex_id = $exchangCoinModel->where(['uid'=>$v['uid']])->update(['status'=>'成功','txid' => md5($v['wallet'] . $v['address'] . time())]);
                        $sa_id = $userModel->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");
                        if($in_id && $sa_id && $ex_id && $to_id){
                            $mo->commit();
                        }else{
                            $mo->back();
//                            $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        }
                    }catch (Exception $e){
                        $mo->back();
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                    }
                }else{

                    //站外转出
                    $sendrs = $CoinClient->sendtoaddress($v['wallet'], (double)$v['number_real']);
                    $sendrs_arr = json_decode($sendrs, true);

                    if(($sendrs && !isset($sendrs_arr['status']))){
                        //手续费
                        $mo->begin();
                        try{
//                            if ($v['platform_fee'] > 0) $fee_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$v['platform_fee']} where uid=2");
                            $out_id = $exchangCoinModel->where(['id'=>$v['id']])->update(['status' => '成功','txid'=>$sendrs]);
                            $s_user = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");

                            if($out_id && $s_user){
                                $mo->commit();
                            }else{
                                $mo->back();
                            }
                        }catch (Exception $e){
                            $mo->back();
                        }

                        if($out_id) echo $v['id'].'订单转出成功'.'----';
                    }else{
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        echo $v['id'].'钱包服务器转出币失败';
                    }
                }
            }
            $CoinClient->walletlock();
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletOutError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }

    }

    //eth系列
    public function ethAction($coin){

        $coin = $coin ? $coin : 'eth';

        //产生一个锁文件
        $lockfile = $this->setLock($coin,'Out');
        try{
            $coinConf = CoinModel::getInstance()->where(['name'=>$coin])->fRow();
            if(!in_array($coinConf['type'],['eth','token']))  Tool_Del::lock($lockfile,'币种错误');

            $exchangCoin = 'Exchange_'.ucfirst($coin).'Model';

            if ($coinConf['type'] == 'token' && (!$coinConf['token_address'] || !$coinConf['number_float'])) Tool_Del::lock($lockfile,'合同地址为空');

            $exchangCoinModel = new $exchangCoin();
            $mo = Orm_Base::getInstance();

            $exchanges = $exchangCoinModel->where(['opt_type'=>'out','status'=>'等待'])->fList();

            if(!$exchanges)  Tool_Del::lock($lockfile,'没有转出的数据');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;

            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);


            //判断钱包状态
            $CoinClient = new Api_Rpc_EthClient($address, $port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) Tool_Del::lock($lockfile,'钱包对接失败');

            $userModel = UserModel::getInstance();

            if($coinConf['type'] == 'eth') $plat_balance = $CoinClient->eth_getBalance($coinConf['account']);
            if($coinConf['type'] == 'token'){
                $call = ['to' => $coinConf['token_address'], 'data' => '0x70a08231' . $CoinClient->data_pj($coinConf['account'])];
                $plat_balance = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), $coinConf['number_float']);
            }

//        $wallet_pass = md5($coinConf['wallet_pass']);

            //传入钱包密码
//        $CoinClient->walletpassphrase($wallet_pass, 60);
            foreach ($exchanges as $k=>$v) {

                //判断转出地址
                if (strlen($v['wallet']) != 42 || substr($v['wallet'],0,2) != '0x') continue;

                if ($v['number_real'] > $plat_balance){
                    file_put_contents(APPLICATION_PATH."/shell/{$coin}.log","{$coin}币余额不足，待转出{$v['number_real']}，余额：{$plat_balance}",8);
                    continue;
                }
                $lock_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'冻结中']);
                if(!$lock_id) continue;

                //站内互转
                if ($v['is_out'] || $v['to_uid']) {
                    echo '站内';
                    $mo->begin();

                    try{
                        $txid = md5($v['wallet'] . $v['address'] . time());

                        $in_id = $exchangCoinModel->insert(['uid' => $v['to_uid'], 'wallet' => $v['wallet'], 'txid' => $txid, 'number' => $v['number'], 'platform_fee' => $v['platform_fee'], 'opt_type'=>'in','number_real' =>$v['number_real'], 'created' => time(), 'status' => '成功']);
                        $to_id = $userModel->exec("update user set {$coin}_over={$coin}_over+{$v['number_real']} where uid={$v['to_uid']}");
                        $ex_id = $exchangCoinModel->where(['uid'=>$v['uid']])->update(['status'=>'成功','txid' => $txid]);
                        $sa_id = $userModel->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");
                        if($in_id && $sa_id && $ex_id && $to_id){
                            $mo->commit();
                        }else{
                            $mo->back();
//                            $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        }
                    }catch (Exception $e){
                        $mo->back();
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                    }
                }else{

                    //站外转出
                    //ETH系列
                    if($coinConf['type'] == 'eth'){
                        $tradeInfo = [[
                            'from' => $coinConf['account'],
                            'to' => $v['wallet'],
                            'gas' => '0x76c0',
                            'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($v['number_real']))),
                            'gasPrice' => $CoinClient->eth_gasPrice()
                        ]];
                        $sendrs = $CoinClient->eth_sendTransaction($coinConf['account'],$coinConf['password'], $tradeInfo);
                    }

                    //token系列
                    if($coinConf['type'] == 'token'){
                        $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($v['number_real']),$coinConf['number_float']));
                        $tradeInfo = [[
                            'from' => $coinConf['account'],
                            'to' => $coinConf['token_address'],
                            'data' =>  '0xa9059cbb'. $CoinClient->data_pj($v['wallet'], $value),
                        ]];
                        $sendrs = $CoinClient->eth_sendTransaction($coinConf['account'],$coinConf['password'], $tradeInfo);
                    }
                    if(($sendrs && !isset($sendrs_arr['status']))){
                        echo '执行OK';
                        //手续费
                        $mo->begin();
                        try{
//                            if ($v['platform_fee'] > 0) $fee_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$v['platform_fee']} where uid=2");
                            $out_id = $exchangCoinModel->where(['id'=>$v['id']])->update(['status' => '成功','txid'=>$sendrs->result]);
                            $s_user = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");

//                        echo $s_user.'--'.$out_id;die;
                            if($out_id && $s_user){
                                $mo->commit();
                            }else{
                                $mo->back();
                            }
                        }catch (Exception $e){
                            $mo->back();
                        }

                        if($out_id) echo $v['id'].'订单转出成功'.'----'.'txid:'.$sendrs->result;
                    }else{
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        echo $v['id'].'钱包服务器转出币失败';
                    }
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletOutError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }

    }

    //EOS系列
    public function eosAction($coin){
        $coin = $coin ? $coin : 'eos';

        //产生一个锁文件
        $lockfile = $this->setLock($coin,'Out');

        try{
            $coinConf = CoinModel::getInstance()->where(['name'=>$coin])->fRow();
            if($coinConf['type'] != 'eos') Tool_Del::lock($lockfile,'币种错误');

            $exchangCoin = 'Exchange_'.ucfirst($coin).'Model';


            $exchangCoinModel = new $exchangCoin();
            $mo = Orm_Base::getInstance();

            $exchanges = $exchangCoinModel->where(['opt_type'=>'out','status'=>'等待'])->fList();

//        Tool_Out::p($exchanges);die;
            if(!$exchanges)  Tool_Del::lock($lockfile,'没有转出的数据');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            $EosClient = new Api_Rpc_EosClient($address, $port);
            $json = $EosClient->get_info();

            if(!$json) Tool_Del::lock($lockfile,'钱包对接失败');

            $tradeInfo = [
                "account" => $coinConf['account'],
                "code" => $coinConf['token_address'],
                "symbol" => $coin,
            ];
            $account_info = $EosClient->get_currency_balance($tradeInfo);
            $plat_balance = trim(substr($account_info[0], 0, strlen($account_info[0]) - strlen($coin)));

            $userModel = UserModel::getInstance();

            //实列化另一个端口类
            $EosClient_Sign = new Api_Rpc_EosClient('18.224.30.250','9999');


            foreach ($exchanges as $k=>$v) {
                //判断可用余额
                if ($v['number_real'] > $plat_balance) continue;

                $lock_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'冻结中']);
                if(!$lock_id) continue;

                //站内互转
                if ($v['is_out'] || $v['to_uid']) {
                    $mo->begin();

                    try{
                        $in_id = $exchangCoinModel->insert(['uid' => $v['to_uid'], 'wallet' => $v['wallet'], 'txid' => md5($v['wallet'] . $v['address'] . time()), 'number' => $v['number'], 'platform_fee' => $v['platform_fee'], 'opt_type'=>'in','number_real' =>$v['number_real'], 'created' => time(), 'status' => '成功']);
                        $to_id = $userModel->exec("update user set {$coin}_over={$coin}_over+{$v['number_real']} where uid={$v['to_uid']}");
                        $ex_id = $exchangCoinModel->where(['uid'=>$v['uid']])->update(['status'=>'成功','txid' => md5($v['wallet'] . $v['address'] . time())]);
                        $sa_id = $userModel->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");
                        if($in_id && $sa_id && $ex_id && $to_id){
                            $mo->commit();
                        }else{
                            $mo->back();
//                            $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        }
                    }catch (Exception $e){
                        $mo->back();
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                    }
                }else{
                    //站外转出
                    $to = $v['wallet'];
                    $memo = $v['label'];
                    $to_mun = substr($v['number_real'] ,0,-4);

                    //数列化2bin->json
                    $coin_D = strtoupper($coin);

                    $abi_json_to_bin_Info = [
                        'code' => $coinConf['token_address'],
                        'action' => 'transfer',
                        'args' => [
                            'from' => $coinConf['account'],
                            'to' => $to,
                            'quantity' =>$to_mun . " $coin_D",
                            'memo' => $memo,
                        ]
                    ];

                    $binargs = $EosClient->abi_json_to_bin($abi_json_to_bin_Info);

                    $json = $EosClient->get_info();
                    $head_block_num = $json->head_block_num;
                    $get_block_info = $EosClient->get_block(['block_num_or_id' => $head_block_num]);
                    $timestamp = $get_block_info->timestamp;

                    $block_num = $get_block_info->block_num;
                    $ref_block_prefix = $get_block_info->ref_block_prefix;
                    $time_arr = explode('+',date('c', time($timestamp) - 26000));//28680
                    $timestamp =  $time_arr[0] . '.500';

                    //部署签名
                    $sign_transaction_info = [
                        [
                            'ref_block_num' => $block_num,
                            'ref_block_prefix' => $ref_block_prefix,
                            'expiration' => $timestamp,
                            'actions' => [
                                ['account' => $coinConf['token_address'],
                                    'name' => 'transfer',
                                    'authorization' => [['actor' => $coinConf['account'], 'permission' => 'active']],
                                    'data' => $binargs->binargs,]
                            ],
                            'signatures' => [],
                        ],
                        [$coinConf['fee_address']],
                        'aca376f206b8fc25a6ed44dbdc66547c36c6c33e3a119ffbeaef943642f0e906'
                    ];

                    $sign_transaction = $EosClient_Sign->sign_transaction('huo', $coinConf['password'],$sign_transaction_info);

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
                                    'account' => $coinConf['token_address'],
                                    'name' => 'transfer',
                                    'authorization' => [
                                        [
                                            'actor' => $coinConf['account'],
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

                    $sendrs = $EosClient->push_transaction('huo', $coinConf['password'],$push_transaction_info);
                    $sendrs = $sendrs->transaction_id;
                    if($sendrs){

                        //手续费
                        $mo->begin();
                        try{
//                            if ($v['platform_fee'] > 0) $fee_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$v['platform_fee']} where uid=2");
                            $out_id = $exchangCoinModel->where(['id'=>$v['id']])->update(['status' => '成功','txid'=>$sendrs]);
                            $s_user = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");

//                        echo $s_user.'--'.$out_id;die;
                            if($out_id && $s_user){
                                $mo->commit();
                            }else{
                                $mo->back();
                            }
                        }catch (Exception $e){
                            $mo->back();
                        }

                        if($out_id) echo $v['id'].'订单转出成功'.'----'.'txid:';
                    }else{
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        echo $v['id'].'钱包服务器转出币失败';
                    }
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletOutError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }
    }

    //BTM系列
    public function btmAction($coin){
        $coin = $coin ? $coin : 'btmz';

        //产生一个锁文件
        $lockfile = $this->setLock($coin,'Out');
        try{
            $CoinModel = CoinModel::getInstance();
            $coinConf = $CoinModel->where(['name' => $coin])->fRow();

            if ($coinConf['type'] != 'btm') Tool_Del::lock($lockfile,'币种错误');

            $exchangCoin = 'Exchange_'.ucfirst($coin). 'Model';
            $exchangCoinModel = new $exchangCoin();
            $mo = Orm_Base::getInstance();

            $exchanges = $exchangCoinModel->where(['opt_type' => 'out', 'status' => '等待'])->fList();
            if (!$exchanges) Tool_Del::lock($lockfile,'没有转出的数据');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;

            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);


            $btmClient = new Api_Rpc_BtmClient($address, $port, reset(explode('-', $coinConf['account'])), $coinConf['password'], end(explode('-', $coinConf['account'])), $coinConf['token_address']);


            foreach ($exchanges as $k => $v) {
                $lock_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'冻结中']);
                if(!$lock_id) continue;
                if ($res = $btmClient->outcome($v['wallet'], floatval($v['number_real']))) {

                    if ($sigRes = $btmClient->signTransaction($res)) {

                        if ($resSub = $btmClient->submitTransaction($sigRes)) {

                            //手续费
                            $mo->begin();
                            try {
//                                if ($v['platform_fee'] > 0) $fee_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$v['platform_fee']} where uid=2");
                                $out_id = $exchangCoinModel->where(['id' => $v['id']])->update(['status' => '成功', 'txid' => $resSub['tx_id']]);
                                $s_user = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");

                                if ($out_id && $s_user) {
//
                                        $mo->commit();
//
                                } else {
                                    $mo->back();
                                }
                            } catch (Exception $e) {
                                $mo->back();
                            }

                            if ($out_id) echo $v['id'] . '订单转出成功' . '----';
                        }else{
                            //失败解锁
//                            $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                        }
                    }else{
                        //失败解锁
//                        $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                    }
                }else{
                    //失败解锁
//                    $thaw_id = $exchangCoinModel->update(['id'=>$v['id'],'status'=>'等待']);
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletOutError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }

    }

    //XRP系列
    public function xrpAction($coin){
        $coin = $coin ? $coin : 'xrp';

        //产生一个锁文件
        $lockfile = $this->setLock($coin,'Out');

        try{
            $CoinModel = CoinModel::getInstance();
            $coinConf = $CoinModel->where(['name' => $coin])->fRow();

            if ($coinConf['type'] != 'xrp') Tool_Del::lock($lockfile,'币种错误');

            $exchangCoin = 'Exchange_'.ucfirst($coin).'Model';
            $exchangModel = new $exchangCoin();
            $mo = Orm_Base::getInstance();

            $exchanges = $exchangModel->where(['opt_type' => 'out', 'status' => '等待'])->fList();
            if (!$exchanges) Tool_Del::lock($lockfile,'没有转出的数据');

//        Tool_Out::p($exchanges);die;

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;

            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            //实例化钱包
            $xrpClient = new Api_Rpc_XrpClient($address,$port,$coinConf['account'],$coinConf['password'],$coinConf['token_address']);

            $mo = new Orm_Base();

            foreach ($exchanges as $k => $v) {

                $lock_id = $exchangModel->update(['id'=>$v['id'],'status'=>'冻结中']);
                if(!$lock_id) continue;

                $sign = $xrpClient->sign($v['number_real'],$v['wallet'],$v['label']);

                if (strtolower($sign['result']['status']) != 'success') continue;
                $submit = $xrpClient->submit($sign['result']['tx_blob']);

                if (strtolower($submit['result']['status']) == 'success'){
                    $tx_json = $submit['result']['tx_json'];

                    $mo->begin();
                    try {
//                        if ($v['platform_fee'] > 0) $fee_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$v['platform_fee']} where uid=2");
                        $out_id = $exchangModel->where(['id' => $v['id']])->update(['status' => '成功', 'txid' => $tx_json['hash']]);
                        $s_user = $mo->exec("update user set {$coin}_lock={$coin}_lock-{$v['number']} where uid={$v['uid']}");

                        if ($out_id && $s_user) {
                            $mo->commit();
                        } else {
                            $mo->back();
                        }
                    } catch (Exception $e) {
                        $mo->back();
                    }
                }else{
                    //失败解锁
//                    $thaw_id = $exchangModel->update(['id'=>$v['id'],'status'=>'等待']);
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletOutError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }
    }

    //运行
//    public function runAction(){
//        echo 'start';
//        $coins = CoinModel::getInstance()->where(['status'=>0,'out_status'=>0])->fList();
//        foreach ($coins as $k=>$v){
//            try{
//                $type = $v['type'];
//                $res = $this->$type($v['name']);
//                echo $v['name'].$res.'---';
//            }catch (Exception $e){
//                echo json_decode($e);
//            }
//        }
//        die('成功');
//    }
}