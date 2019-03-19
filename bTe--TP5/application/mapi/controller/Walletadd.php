<?php

namespace app\mapi\controller;

class Walletadd extends Mapi
{
    //亚马孙 服务器内网 IP 地址
    protected $ip = ['172.31.39.219', '172.31.40.184', '172.31.32.41', '172.31.32.44', '172.31.45.96' , '172.31.33.131' , '172.31.41.3'];

    public function index()
    {
        $this->auth();
        echo 'here';
    }

    //wallet generate
    public function generate()
    {
        $this->auth('post');

        $coin = input('coin/s');
        $username = input('username/s');
        $Coin = db('Coin')->where(['name' => $coin])->find();

        if ($Coin['type'] == 'bit') {
            $dj_username = $Coin['dj_yh'];
            $dj_password = $Coin['dj_mm'];
            $dj_address = $Coin['dj_zj'];
            $dj_port = $Coin['dj_dk'];
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            if ($Coin['name'] == 'btc' || $Coin['name'] == 'ltc') {
                $json = $CoinClient->getnetworkinfo();
            } else {
                $json = $CoinClient->getinfo();
            }

            if (!isset($json['version']) || !$json['version']) {
                $this->json(['status' => 500, 'message' => '钱包连接失败！']);
            }

            $qianbao_addr = $CoinClient->getaddressesbyaccount($username);
            if (!is_array($qianbao_addr)) {
                $qianbao_ad = $CoinClient->getnewaddress($username);
                if (!$qianbao_ad) {
                    $this->json(['status' => 501, 'message' => '生成钱包地址出错1！']);
                } else {
                    $qianbao = $qianbao_ad;
                }
            } else {
                $qianbao = $qianbao_addr[0];
            }

            if (!$qianbao) {
                $this->json(['status' => 502, 'message' => '生成钱包地址出错2！']);
            }

            $this->json(['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $qianbao]);
        }

        if ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
            $dj_address = $Coin['dj_zj'];
            $dj_port = $Coin['dj_dk'];
            $CoinClient = EthClient($dj_address, $dj_port);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->json(['status' => 503, 'message' => '钱包连接失败！']);
            }

            $dj_password = md5($username);
            $qianbao = $CoinClient->personal_newAccount($dj_password);
            if (!$qianbao) {
                $this->json(['status' => 504, 'message' => '生成钱包地址出错1！']);
            }

            $this->json(['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $qianbao]);
        }

        $this->json(['status' => 505, 'message' => '无效的钱包系列!']);
    }

    //wallet withdraw
    public function withdraw()
    {
        $this->auth('post');

        $coin = input('coin/s');
        $addr = input('addr/s');
        $num = input('num/f');
        $memo = input('memo/s');
        $Coin = db('Coin')->where(['name' => $coin])->find();
        if ($Coin['type'] == 'bit') {
            $CoinClient = CoinClient($Coin['dj_yh'], $Coin['dj_mm'], $Coin['dj_zj'], $Coin['dj_dk'], 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->json(['status' => 300, 'message' => '钱包连接失败！']);
            }

            $valid_res = $CoinClient->validateaddress($addr);
            if (!$valid_res['isvalid']) {
                $this->json(['status' => 301, 'message' => $addr . '不是一个有效的钱包地址！']);
            }

            if ($json['balance'] < $num) {
                $this->json(['status' => 302, 'message' => '钱包余额不足']);
            }

            $sendrs = $CoinClient->sendtoaddress($addr, floatval($num));
            $this->json(['status' => 200, 'message' => '转币成功！', 'sendrs' => $sendrs]);
        }

        if ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
            $CoinClient = EthClient($Coin['dj_zj'], $Coin['dj_dk']);
            $json = $CoinClient->eth_blockNumber(true);

            if (empty($json) || $json <= 0) {
                $this->json(['status' => 303, 'message' => '钱包连接失败！']);
            }

            $valid_res = strpos($addr, '0x') === 0 ? true : false;
            if (!$valid_res || strlen($addr) < 10) {
                $this->json(['status' => 304, 'message' => $addr . '不是一个有效的钱包地址！']);
            }

            $banlance = $CoinClient->eth_getBalance($Coin['dj_yh']);
            if ($banlance < $num) {
                $this->json(['status' => 305, 'message' => '钱包余额不足']);
            }
            if ($Coin['type'] == 'token') {
                $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($num), $Coin['decimals']));
                $tradeInfo = [[
                    'from' => $Coin['dj_yh'],
                    'to' => $Coin['token_address'],
                    'data' => '0xa9059cbb' . $CoinClient->data_pj($addr, $value),
                ]];
                $sendrs = $CoinClient->eth_sendTransaction($Coin['dj_yh'], $Coin['dj_mm'], $tradeInfo);
            } elseif ($Coin['type'] == 'eth') {
                $tradeInfo = [[
                    'from' => $Coin['dj_yh'],
                    'to' => $addr,
                    'gas' => '0x1046a',
                    'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($num))),
                    'gasPrice' => '0x189640200', //$CoinClient->eth_gasPrice()
                ]];
                $sendrs = $CoinClient->eth_sendTransaction($Coin['dj_yh'], $Coin['dj_mm'], $tradeInfo);
            }
            $this->json(['status' => 200, 'message' => '转币成功！', 'sendrs' => $sendrs]);
        }
        //eos转出
        if ($Coin['type'] == 'eos') {

            $EosClient = EosClient($Coin['dj_zj'], $Coin['dj_dk']);
            $json = $EosClient->get_info();
            if (!$json) {
                $this->error('钱包对接失败!');
            }
            //数列化2bin->json
            $abi_json_to_bin_Info = [
                'code' => $Coin['token_address'],
                'action' => 'transfer',
                'args' => [
                    'from' => $Coin['dj_yh'],
                    'to' => trim($addr),
                    'quantity' => $num . ' EOS',
                    'memo' => $memo,
                ]
            ];
            $binargs = $EosClient->abi_json_to_bin($abi_json_to_bin_Info);
            $head_block_num = $json->head_block_num;
            $get_block_info = $EosClient->get_block(['block_num_or_id' => $head_block_num]);
            $timestamp = $get_block_info->timestamp;
            $block_num = $get_block_info->block_num;
            $ref_block_prefix = $get_block_info->ref_block_prefix;
            //部署签名
            $sign_transaction_info = [
                [
                    'ref_block_num' => $block_num,
                    'ref_block_prefix' => $ref_block_prefix,
                    'expiration' => $timestamp,
                    'actions' => [
                        ['account' => $Coin['token_address'],
                            'name' => 'transfer',
                            'authorization' => [['actor' => $Coin['dj_yh'], 'permission' => 'active']],
                            'data' => $binargs->binargs,]
                    ],
                    'signatures' => [],
                ],
                ['EOS75CgiqRrGMvqcZNxm2BEPv3JjFuGYtE4DYFwJufJSxXtEHEgqX'],
                'aca376f206b8fc25a6ed44dbdc66547c36c6c33e3a119ffbeaef943642f0e906'
            ];
            $sign_transaction = $EosClient->sign_transaction('cxz', $Coin['dj_mm'], $sign_transaction_info);
            $push_transaction_info = [
                'compression' => 'none',
                'transaction' => [
                    'expiration' => $timestamp,
                    'ref_block_num' => $block_num,
                    'ref_block_prefix' => $ref_block_prefix,
                    'context_free_actions' => [],
                    'actions' => [
                        [
                            'account' => $Coin['token_address'],
                            'name' => 'transfer',
                            'authorization' => [
                                [
                                    'actor' => $Coin['dj_yh'],
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
            $sendrs = $EosClient->push_transaction($push_transaction_info);
        }
        $this->json(['status' => 200, 'message' => '转币成功！', 'sendrs' => $sendrs]);
    }
}

?>