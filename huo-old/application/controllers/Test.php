<?php
/**
 *  静态资源
 */
class TestController extends Ctrl_Base
{
    protected $_auth = 0;



    public function indexAction($coin){

        $coin = $coin ? $coin : 'eth';

        $coinModel = CoinModel::getInstance();
        $coinconf = $coinModel->where(['status' => 0, 'name' => $coin])->fRow();

        if(!$coinconf || !in_array($coinconf['type'],['eth','token'])) die('币种错误');

        $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$coin;
        if (empty($rpcurl)) die('钱包地址不存在');

        $str = mb_substr($rpcurl, mb_strlen('http://'));
        $address = mb_substr($str, 0, mb_strpos($str, ':'));
        $port = mb_substr($str, mb_strpos($str, ':') + 1);

        $CoinClient = new Api_Rpc_EthClient($address, $port);
        $blockNumber = $CoinClient->eth_blockNumber(true);

        if (empty($blockNumber) || $blockNumber <= 0) die('钱包连接失败');

        //更新高度
        $mo = new Orm_Base();
        $block_num = $mo->exec("update coin set block_num=block_num+1 where `name`='{$coin}' and status=0");

        //开始轮询
        $listtransactions = $CoinClient->listLocal($blockNumber,(int)$coinconf['block_num']);//$blockNumber     (int)$coinconf['block_num']

        if (!$listtransactions) die('高度太高，无法轮询');

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
                        $yue_token = $CoinClient->eth_getBalance($to);

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
        die('成功');
    }


}