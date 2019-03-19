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
class Cli_WalletController extends Ctrl_Cli
{

    /**
     * bit系列
     * @param $coin
     */
    public function  bitAction($coin){
        $coin = $coin?$coin: 'btc';

        $lockfile = self::setLock($coin);

        try{
            $coinModel = CoinModel::getInstance();
            $coinconf = $coinModel->where(['status' => 0, 'name' => $coin])->fRow();

            if(!$coinconf || $coinconf['type']!='bit') Tool_Del::lock($lockfile,'币种错误');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            if (empty($rpcurl)) Tool_Del::lock($lockfile,'钱包地址不存在');

            $rpcMo = new Api_Rpc_Client($rpcurl);
            $json = $rpcMo->getinfo();

            if (!isset($json['version']) || !$json['version']) Tool_Del::lock($lockfile,'钱包连接失败');

            $listtransactions = $rpcMo->listtransactions('*', 100, 0);
            krsort($listtransactions);

            $userModel = UserModel::getInstance();
            $exchange = 'Exchange_' . ucfirst($coin) . 'Model';
            $exchangeModel = $exchange::getInstance();
            $addressModel = AddressModel::getInstance();
            $mo = Orm_Base::getInstance();

            foreach ($listtransactions as $trans) {
                //无blockhash  不处理
                if (!$trans['blockhash']) continue;

                //收到----对应转入
                if ($trans['category'] == 'receive') {
                    //区块地址不在地址表中不处理
                    if (!$uid = $addressModel->where(['address' => $trans['address'],'status'=>0])->fOne('uid')) continue;

                    //如果订单写入了数据库不处理
                    $change = $exchangeModel->where(['txid' => $trans['txid'], 'wallet' => $trans['address']])->fRow();
                    if (isset($change) && $change['status'] == '成功') continue;

//                Tool_Out::p($trans);die;

                    //如果小于确认数
                    if ($trans['confirmations'] < $coinconf['confirm']) {
                        if (!$change) $exchangeModel->insert(['uid'=>$uid,'wallet' =>$trans['address'],'txid' => $trans['txid'],'number' =>$trans['amount'], 'number_real' => $trans['amount'],'created'=>time(),'opt_type'=>'in','status'=>'等待']);
                        continue;
                    }

                    $mo->begin();
                    try{
                        if ($change && $change['status']=='等待') {
                            $in_id = $exchangeModel->where(['id'=>$change['id']])->update(['updated' => time(), 'opt_type' => 'in','status'=>'成功']);
                        } else {
                            $in_id = $exchangeModel->insert(['uid'=>$uid,'wallet'=>$trans['address'],'txid'=>$trans['txid'],'number'=>$trans['amount'],'number_real'=>$trans['amount'], 'created' => time(), 'opt_type' => 'in','status'=>'成功']);
                        }
                        $save_id = $mo->exec("update user set {$coin}_over = {$coin}_over+{$trans['amount']} where uid={$uid}");
                        if($in_id && $save_id) $mo->commit();
                    }catch (Exception $e){
                        $mo->back();
                    }
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }
    }

    //eth系列
    public function ethAction($coin){
        $coin = $coin ? $coin : 'eth';

        //产生一个锁文件
        $lockfile = self::setLock($coin);

        try{
            $coinModel = CoinModel::getInstance();
            $coinconf = $coinModel->where(['status' => 0, 'name' => $coin])->fRow();

            if(!$coinconf || !in_array($coinconf['type'],['eth','token'])) Tool_Del::lock($lockfile,'币种错误');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            if (empty($rpcurl)) Tool_Del::lock($lockfile,'钱包地址不存在');

            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            $CoinClient = new Api_Rpc_EthClient($address, $port);
            $blockNumber = $CoinClient->eth_blockNumber(true);

            if (empty($blockNumber) || $blockNumber <= 0) Tool_Del::lock($lockfile,'钱包连接失败');

            $block_num = (int)$coinconf['block_num'];
            //更新高度
            $mo = new Orm_Base();
            if($blockNumber>=$block_num) $mo->exec("update coin set block_num=block_num+1 where `name`='{$coin}' and status=0");

            //开始轮询
            $listtransactions = $CoinClient->listLocal($blockNumber,$block_num);//$blockNumber     (int)$coinconf['block_num']
            if (!$listtransactions) Tool_Del::lock($lockfile,'高度太高，无法轮询');

            $addressModel = AddressModel::getInstance();


            //区块高度加1


//        Tool_Out::p($listtransactions);die;

            foreach ($listtransactions as $trans) {
                if (!$trans->to) continue;

                //判断input长度
                if(strlen($trans->input) != 138){

                    if ($addr = $addressModel->where(['address' => $trans->to])->fRow()) {

                        $ex = 'Exchange_'.ucfirst($addr['coin']).'Model';
                        $exModel = $ex::getInstance();
                        if ($exModel->where(['txid' => $trans->hash, 'status' => '成功'])->fOne('id')) continue;

                        $true_amount = $CoinClient->real_banlance($CoinClient->decode_hex($trans->value));

                        //转入平台手续费
                        $platform_fee = 0.001;
                        $final_amount = $true_amount - $platform_fee;

//                    Tool_Out::p($final_amount);
                        if ($final_amount > 0.005) {

                            $mo->begin();
                            try {
                                $ad_id = $mo->exec("update user set {$addr['coin']}_over = {$addr['coin']}_over + ($final_amount) where uid={$addr['uid']}");
                                $ex_id = $exModel->insert([
                                    'uid' => $addr['uid'],
                                    'wallet' => $trans->to,
                                    'txid' => $trans->hash,
                                    'number' => $true_amount,
                                    'number_real' => $final_amount,
                                    'platform_fee' => $platform_fee,
                                    'created' => time(),
                                    'opt_type' => 'in',
                                    'status' => '成功'
                                ]);
                                if($ad_id && $ex_id) {
                                    $mo->commit();
                                }else {
                                    $mo->back();
                                }
                            }catch (Exception $e) {
                                $mo->back();
                            }
                        }
                    }

                    //转出
//                if (!$trans->from) continue;
//
//                if ($addr = $addressModel->where(['address' => $trans->from])->fRow()) {
//                    if ($out = $exchangeCoinModel->where(['txid' => $trans->hash])->fRow()) {
//                        if ($out['status'] == '确认中') {
//                            $rs[] = $exchangeCoinModel->where(['txid' => $trans->hash])->update(['status' => '成功']);
//                        }
//                    }
//                }
                }else {

                    $hashObj = $CoinClient->eth_getTransactionReceipt($trans->hash);

                    $hashArr = (array)$hashObj;
                    $status = substr($hashArr['status'], 2, 1);

                    if ($status != 1 || !$hashArr['logs']) continue;

//                Tool_Out::p($trans);
//
//                Tool_Out::p($coinconf_token = $coinModel->where(['status' => 0, 'token_address' => $trans->to])->fRow());
//                die;

                    if ($coinconf_token = $coinModel->where(['status' => 0, 'token_address' => $trans->to])->fRow()) {

                        $token_value = substr($trans->input, 74, 64);
                        $to = "0x" . substr($trans->input, 34, 40);

                        //实列化一个对应币种的类
                        $ex = "Exchange_" . ucfirst($coinconf_token['name']).'Model';
                        $exModel = new $ex();

//                    Tool_Out::p($exModel);

                        //判断该交易是否轮询
                        if ($exModel->where(['txid' => $trans->hash])->fOne()) continue;

//                    Tool_Out::p($to);die;

                        if ($user_token = $addressModel->where(['address' => $to])->fRow()) {
                            $true_amount_token = $CoinClient->real_banlance_token($CoinClient->decode_hex($token_value), $coinconf_token['number_float']);


//                            $yue_token = $CoinClient->eth_getBalance($to);
                        //eth转账到token的eth手续费
//                        if ($yue_token < floatval('0.005')) {
//                            $tradeInfo_token = [[
//                                'from' => $coinconf['account'],
//                                'to' => $to,
//                                'gas' => '0x4a380',
//                                'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval(0.005 - $yue_token))),
//                                'gasPrice' => $CoinClient->eth_gasPrice()
//                            ]];
//                            $sendrs = $CoinClient->eth_sendTransaction($coinconf['account'], $coinconf['password'], $tradeInfo_token);
//                        }

                            $txid = $exModel->fOne('txid');
                            if (isset($txid) && $txid == $trans->hash) continue;

                            $mo->begin();
                            try{
                                $u_id = $mo->exec("update user set {$coinconf_token['name']}_over={$coinconf_token['name']}_over+$true_amount_token where uid={$user_token['uid']}");
                                $in_id = $exModel->insert([
                                    'uid' => $user_token['uid'],
                                    'wallet' => $to,
                                    'txid' => $trans->hash,
                                    'number' => $true_amount_token,
                                    'number_real' => $true_amount_token,
                                    'created' => time(),
                                    'status' => '成功'
                                ]);

                                if ($u_id && $in_id) {
                                    $mo->commit();
                                } else {
                                    $mo->back();
                                }
                            }catch (Exception $e){
                                $mo->back();
                            }
                        }
                    }
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }

    }

    //EOS系列轮询
    public function eosAction($coin){
        $coin = $coin ? $coin :'eos';

        //产生一个锁文件
        $lockfile = $this->setLock($coin);

        try{
            $coinModel = CoinModel::getInstance();
            $coinConf = $coinModel->where(['name'=>$coin])->fRow();

            if($coinConf['type'] != 'eos') Tool_Del::lock($lockfile,'币种错误');


            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            $EosClient = new Api_Rpc_EosClient($address, $port);
            $json = $EosClient->get_info();

            if(!$json) Tool_Del::lock($lockfile,'钱包对接失败');

            //获取信息
            $offset = 10;
            $transfer = [
                'account_name' => $coinConf['account'],
                'pos' => $coinConf['block_num'],
                'offset' => $offset,
            ];

            $block_info = $EosClient->get_actions($transfer);
            if(!$block_info) Tool_Del::lock($lockfile,'该高度暂无数据');

//        Tool_Out::p($block_info);die;

            $block_count = count($block_info);
            //更新高度
            $up_block = $coinModel->exec("update coin set block_num=block_num+{$block_count} where `name`='{$coin}'");

            //实例化魔豆
            $exchange = 'Exchange_'.ucfirst($coin).'Model';
            $exchangeModel = $exchange::getInstance();
            $addressModel = AddressModel::getInstance();
            $mo = new Orm_Base();

//        Tool_Out::p($block_info);die;

            foreach ($block_info as $k=>$v) {
                //判断状态释放
                $token_action_trace = $v['action_trace'];

                //判断该交易是否轮询
                if($ex = $exchangeModel->where(['txid'=>$token_action_trace['trx_id']])->fOne('id')) continue;

                if ($token_action_trace['receipt']['receiver'] != $coinConf['account']) continue;

                //判断token_address
                if($token_action_trace['act']['account'] !=$coinConf['token_address']) continue;

                //判断操作类型
                if ($token_action_trace['act']['name'] != 'transfer')  continue;

                $token_data = $token_action_trace['act']['data'];
                if (!$token_data['memo']) continue;

                if(!$uid = $addressModel->where(['coin'=>$coin,'label'=>$token_data['memo']])->fOne('uid')) continue;

                $final_amount = trim(substr($token_data['quantity'], 0, strlen($token_data['quantity']) - strlen($coin)));

                $mo->begin();

                try{
                    $up_id = $mo->exec("update user set {$coin}_over={$coin}_over+$final_amount where uid={$uid}");
                    $in_id = $exchangeModel->insert([
                        'uid' => $uid,
                        'wallet' => $token_data['to'],
                        'label'=>$token_data['memo'],
                        'txid' => $token_action_trace['trx_id'],
                        'number' => $final_amount,
                        'number_real' => $final_amount,
                        'opt_type'=>'in',
                        'created' => time(),
                        'status' => '成功',
                    ]);
                    if($up_id && $in_id){
                        $mo->commit();
                    }else{
                        $mo->back();
                    }
                }catch (Exception $e){
                    $mo->back();
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }

    }

    //BTM系列
    public function btmAction($coin){
        $coin = $coin ? $coin:'btmz';

        //产生一个锁文件
        $lockfile = $this->setLock($coin);

        try{
            $CoinModel = CoinModel::getInstance();
            $coinConf = $CoinModel->where(['name'=>$coin])->fRow();

            if($coinConf['type'] != 'btm') Tool_Del::lock($lockfile,'币种错误');


            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            $btmClient = new Api_Rpc_BtmClient($address,$port, reset(explode('-', $coinConf['account'])),$coinConf['password'], end(explode('-',$coinConf['account'])),$coinConf['token_address']);

            $res = $btmClient->income();

            if(!in_array($res['status'],['success','SUCCESS'])) Tool_Del::lock($lockfile,'获取数据失败');

            $count_res = count($res['data']);
            if ($count_res> 10){
                $res_cut = array_slice($res['data'],0,10);
            }else{
                $res_cut = $res['data'];
            }
            unset($res);

            $addList = $btmClient->listAddresses();
            if (!$addList || count($addList)<=0) Tool_Del::lock($lockfile,'获取地址失败');


            $exchange = 'Exchange_'.ucfirst($coin).'Model';
            $exchangeModel = $exchange::getInstance();
            $mo = new Orm_Base();
            $userModel = UserModel::getInstance();
            $addressModel = AddressModel::getInstance();

            foreach ($res_cut as $k=>$v) {
                if ($v['status_fail']) continue;
                foreach ($v['outputs'] as $key => $val) {
                    if (!in_array($val['address'], $addList)) continue;
                    if (preg_match('/([1-9]|[a-z]|[A-Z])/', $v['block_hash']) && $v['block_height'] && $v['block_index']) {

                        if ($ex = $exchangeModel->where(['wallet' => $val['address'], 'txid' => $v['tx_id']])->fRow()) {
                            if ($ex['status'] == 1) continue;

                            $mo->begin();
                            try {
                                $amount = $val['amount'] / 100000000;
                                $ex_id = $exchangeModel->exec("update exchange_{$coin} set status='成功' where id={$ex['id']}");
                                $in_id = $userModel->exec("update user set {$coin}_over={$coin}_over+{$amount} where uid={$ex['uid']}");

                                if ($ex_id && $in_id) {
                                    $mo->commit();
                                } else {
                                    $mo->back();
                                }
                            } catch (Exception $e) {
                                $mo->back();
                            }
                        } else {
                            //数据库不存在
                            if ($uid = $addressModel->where(['address' => $val['address'], 'status' => 0])->fOne('uid')) {
                                $amount = $val['amount'] / 100000000;
                                $data = [
                                    'uid' => $uid,
                                    'wallet' => $val['address'],
                                    'txid' => $v['tx_id'],
                                    'number' => $amount,
                                    'number_real' => $amount,
                                    'created' => time(),
                                    'status' => '成功',
                                    'opt_type' => 'in'
                                ];

                                $mo->begin();
                                try {
                                    $ex_id = $exchangeModel->insert($data);
                                    $up_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$amount} where uid=$uid");

                                    if ($ex_id && $up_id) {
                                        $mo->commit();
                                    } else {
                                        $mo->back();
                                    }
                                } catch (Exception $e) {
                                    $mo->back();
                                }
                            }
                        }
                    }
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }

    }

    //XRP系列
    public function xrpAction($coin){
        $coin = $coin ? $coin : 'xrp';

        //产生一个锁文件
        $lockfile = $this->setLock($coin);
        try{
            $coinModel = CoinModel::getInstance();
            $coinconf = $coinModel->where(['status' => 0, 'name' => $coin])->fRow();

            if (!$coinconf || $coinconf['type'] != 'xrp') Tool_Del::lock($lockfile,'币种错误');

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
            if (empty($rpcurl)) Tool_Del::lock($lockfile,'钱包地址不存在');
            $str = mb_substr($rpcurl, mb_strlen('http://'));
            $address = mb_substr($str, 0, mb_strpos($str, ':'));
            $port = mb_substr($str, mb_strpos($str, ':') + 1);

            $xrpClient = new Api_Rpc_XrpClient($address, $port, $coinconf['account'], $coinconf['password'], $coinconf['token_address']);
            $history = $xrpClient->history();

            Tool_Out::p($history);

            if ($history['result']['status'] != 'success') Tool_Del::lock($lockfile,'钱包连接失败');

            $lists = $history['result']['transactions'];
            if (count($lists) < 1) Tool_Del::lock($lockfile,'钱包无数据');




            $addressModel = AddressModel::getInstance();
            $exchange = 'Exchange_' . ucfirst($coin) . 'Model';
            $exchangeModel = $exchange::getInstance();
            $mo = new Orm_Base();
//        Tool_Out::p($lists);die;

            foreach ($lists as $k => $v) {
                //如果不是转入地址退出
                if ($v['tx']['Destination'] != $coinconf['account']) continue;

                //没填tag不转入
                if (!$v['tx']['DestinationTag']) continue;
                if(isset($v['tx']['SendMax'])) continue;

                if ($uid = $addressModel->where(['label' => $v['tx']['DestinationTag'], 'coin' => $coin, 'status' => 0])->fOne('uid')) {

                    if ($hasIncome = $exchangeModel->where(['uid' => $uid, 'wallet' => $v['tx']['Account'], 'txid' => $v['tx']['hash']])->fRow()) continue;

                    //校验hash是否存在
                    $xrpTx = $xrpClient->tx($v['tx']['hash']);
                    if ($xrpTx['result']['status'] != 'success') continue;

                    if (!$xrpTx['result']['validated']) continue;

                    $mo->begin();
                    try {
                        $amount = $v['meta']['delivered_amount'] / 1000000;
                        $up_id = $mo->exec("update user set {$coin}_over={$coin}_over+{$amount} where uid={$uid}");

                        $ex_id = $exchangeModel->insert([
                            'uid' => $uid,
                            'wallet' => $v['tx']['Account'],
                            'txid' => $v['tx']['hash'],
                            'number' => $amount,
                            'number_real' => $amount,
                            'created' => time(),
                            'opt_type' => 'in',
                            'status' => '成功',
                        ]);

                        if ($up_id && $ex_id) {
                            $mo->commit();
                        } else {
                            $mo->back();
                        }
                    } catch (\Exception $e) {
                        $mo->back();
                    }
                }
            }
            Tool_Del::lock($lockfile,'成功');
        }catch (Exception $e){
            file_put_contents(APPLICATION_PATH.'/log/walletError.log',$coin.'异常:'.json_encode($e)."\n",8);
            Tool_Del::lock($lockfile,'异常');
        }
    }

    //ETH 子主地址同步
    public function ethMergeAction($coin)
    {
        $mo = Orm_Base::getInstance();
        $coin = $coin?$coin:'eth';
        $coinconf = $mo->table("coin")->where(['name' => $coin, 'status' => 0])->fRow();

        if(!$coinconf || !in_array($coinconf['type'],['eth','token'])) die('币种类型错误');

        $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
        if (empty($rpcurl)) die('钱包地址不存在');

        $str = mb_substr($rpcurl, mb_strlen('http://'));
        $address = mb_substr($str, 0, mb_strpos($str, ':'));
        $port = mb_substr($str, mb_strpos($str, ':') + 1);

        $CoinClient = new Api_Rpc_EthClient($address, $port);
        $blockNumber = $CoinClient->eth_blockNumber(true);

        if (empty($blockNumber) || $blockNumber <= 0) die('钱包连接失败');

        //筛选数据库中钱包中大于 0.01 的用户 分组
//        $offset = 1000;
//        for ($i = 0; $i < 24; $i++) {
            if ($coinconf['type'] == 'eth') {//date('H', time()) == $i &&
                $accounts = $mo->table("address")->where("coin='{$coin}' and address!='' and secret!=''")->fList();//->limit($i * $offset, 1000)
            }elseif($coinconf['type'] == 'token'){//date('H', time()) == $i &&
                $accounts =$mo->table("address")->where("coin='{$coin}' and address!='' and secret!=''")->fList();//->limit(($i-1) * $offset, 1000)
            }
//        }

        echo $mo->getLastSql().PHP_EOL;
        echo count($accounts).PHP_EOL;

        //筛选钱包中钱包中大于 0.01 的用户
        $fee = 0.002;
        if(count($accounts) > 0) {
            $mergeNum = 0;
            foreach ($accounts as $k => $v) {
                if($coinconf['type'] == 'eth'){
                    $num = $CoinClient->eth_getBalance($v['address']);
                }elseif($coinconf['type'] == 'token'){
                    $call = [
                        'to' => $coinconf['token_address'],
                        'data' => '0x70a08231'.$CoinClient->data_pj($v['address'])
                    ];
                    $num = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)) , $coinconf['number_float']);
                    echo $num.PHP_EOL;
                }

                if ($num >= $coinconf['merge_limit']) {
                    $mergeNum+=$num;


                    $yue_token = $CoinClient->eth_getBalance($v['address']);
                    if ($yue_token < floatval('0.005')) {

                        $s_fee = floatval(0.005 - $yue_token);
                        echo "手续费不足,只有{$yue_token}个，转入手续费{$s_fee}个".PHP_EOL;
                        //eth转账到token的eth手续费
                        $tradeInfo_token = [[
                            'from' => $coinconf['account'],
                            'to' => $v['address'],
                            'gas' => '0x4a380',
                            'value' => $CoinClient->encode_dec($CoinClient->to_real_value($s_fee)),
                            'gasPrice' => $CoinClient->eth_gasPrice()
                        ]];
                        $sendrs = $CoinClient->eth_sendTransaction($coinconf['account'], $coinconf['password'], $tradeInfo_token);
                        Tool_Out::p($sendrs);
                        echo PHP_EOL;
                    }

                    //转币小脚本 进行同步
                    do {
                        if($coinconf['type'] == 'eth'){
                            $num = $num - $fee;
                            $tradeInfo = [[
                                'from' => $v['address'],
                                'to' => $coinconf['account'],
                                'gas' => '0x76c0',
                                'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($num))),
                                'gasPrice' => $CoinClient->eth_gasPrice()
                            ]];
                            $sendrs = $CoinClient->eth_sendTransaction($v['address'], $v['secret'], $tradeInfo);
                        }elseif($coinconf['type'] == 'token'){
                            $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($num) , $coinconf['number_float']));
                            $tradeInfo = [[
                                'from' => $v['address'],
                                'to' => $coinconf['token_address'],
                                'data' =>  '0xa9059cbb'. $CoinClient->data_pj($coinconf['account'], $value),
                            ]];
                            $sendrs = $CoinClient->eth_sendTransaction($v['address'], $v['secret'], $tradeInfo);
                        }
                        Tool_Out::p($sendrs->error);
                        echo PHP_EOL;
                        echo "{$v['address']}:开始归集转币".PHP_EOL;

                    } while ($sendrs->error != '');//$sendrs->error->code != (-32000)
                }
            }
        }

        $info['name'] = $coin;
        $info['version'] = hexdec($CoinClient->eth_protocolVersion());
        $info['headers'] = hexdec($CoinClient->eth_blockNumber());
        $info['accounts'] = $CoinClient->eth_accounts();

        $sum = 0;
        foreach ($info['accounts'] as $key => $value) {
            $sum += $CoinClient->eth_getBalance($value);
        }
        $coinbase = $CoinClient->eth_getBalance($coinconf['account']);
        echo $coin . ' 账户总数量：' . $sum . PHP_EOL;
        echo $coinconf['account'] . ' 主地址总数量:' . $coinbase.PHP_EOL;
        echo "本次归集总数量:{$mergeNum}".PHP_EOL;
        die(date('H').'---'.time().'结束');
    }


    //运行
//    public function runAction(){
//        echo 'start';
//        $coins = CoinModel::getInstance()->where(['status'=>0,'in_status'=>0])->fList();
//
//
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
