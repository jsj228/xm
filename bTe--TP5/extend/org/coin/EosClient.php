<?php


namespace org\coin;


class EosClient
{
    protected $host, $port;

    function __construct($host, $port, $version = "2.0")
    {
        $this->host = $host; //测试ip  api.eosnewyork.io
//        $this->host = 'api.eosnewyork.io'; //测试ip  api.eosnewyork.io
        $this->port = $port;
    }

    function request($url, $params = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if (substr($url, -8) == 'get_info') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $ret = curl_exec($ch);
        return @json_decode($ret);
    }

    //get
    public function get_request($method, $params = array())
    {
        $url = $this->host . ':' . $this->port . '/v1/chain/' . $method;
        $ret = $this->request($url, $params);
        return $ret;
    }

    //wallet
    public function wallet_request($method, $params = array())
    {
        $url = $this->host . ':' . '9999' . '/v1/wallet/' . $method;
        $ret = $this->request($url, $params);
        return $ret;
    }

    //history
    public function history_request($method, $params = array())
    {
        $url = $this->host . ':' . $this->port . '/v1/history/' . $method;
        $ret = $this->request($url, $params);
        return $ret;
    }

    //钱包最新信息
    function get_info()
    {
        return $this->get_request(__FUNCTION__);
    }

    //区块信息
    function get_block($add_block, $name = '')
    {
        $block_number = $this->get_info();
        if ($add_block['block_num_or_id'] > $block_number->head_block_num) {
            return [];
        }
        if ($name) {
            $block_info = $this->get_request(__FUNCTION__, $add_block);
            $block_transactions = $block_info->transactions;
            foreach ($block_transactions as $k => $v) {
                $block_trx[] = $v->trx;
            }
//            M('coin')->where(array('name' => $name))->setInc('block_num', 1);
            return $block_trx;
        } else {
            return $this->get_request(__FUNCTION__,
                $add_block
            );
        }
    }

    //获取tx
    function get_block_transaction($block_trx)
    {

    }


//查询信息
    function get_table_rows($add_block)
    {
        return $this->get_request(__FUNCTION__,
            $add_block
        );
    }

//查询钱包信息
    function get_account($account)
    {
        return $this->get_request(__FUNCTION__,
            $account
        );
    }

//查询合约信息
    function get_abi($abi)
    {
        return $this->get_request(__FUNCTION__,
            $abi
        );
    }

//获取abi or code
    function get_raw_code_and_abi($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }

//获取abi or code
    function get_currency_balance($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }

//json转换成二进制
    function abi_json_to_bin($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }

//二进制转换成json
    function abi_bin_to_json($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }

    //发送交易
    function push_transaction($wallet_account,$ealletp ,$wallet)
    {
        $this->open($wallet);
        $this->unlock($wallet_account, $ealletp);
        return $this->get_request(__FUNCTION__, $wallet);

    }    //发送交易
    function get_required_keys($wallet)
    {
        return $this->get_request(__FUNCTION__, $wallet);

    }

//
    function get_transaction($account_token)
    {
        return $this->history_request(__FUNCTION__,
            $account_token
        );
    }

    //查看交易信息
    function get_actions($account_token)
    {
        $actions = $this->history_request(__FUNCTION__, $account_token);
        return json_decode(json_encode($actions->actions, true), true);
    }

    //解锁
    function unlock($wallet, $walletp)
    {
        return $this->wallet_request(__FUNCTION__, array(
            $wallet,
            $walletp
        ));
    }

    //部署签名
    function sign_transaction($wallet, $ealletp, $account_token)
    {
        $this->open($wallet);
        $this->unlock($wallet, $ealletp);
        return $this->wallet_request(__FUNCTION__, $account_token);

    }

    //创建钱包
    function create($name)
    {
        return $this->wallet_request(__FUNCTION__, $name);
    }

    //导入密钥
    function import_key($name, $key)
    {
        return $this->wallet_request(__FUNCTION__, array(
            $name,
            $key
        ));
    }

    //列出钱包账号
    function list_wallets()
    {
        return $this->wallet_request(__FUNCTION__);
    }
    //列出钱包账号
    function get_public_keys()
    {
        return $this->wallet_request(__FUNCTION__);
    }

    //打开钱包
    function open($wallet_name)
    {
        return $this->wallet_request(__FUNCTION__, $wallet_name);
    }

    //单钱包上锁
    function lock($wallet_name)
    {
        return $this->wallet_request(__FUNCTION__, $wallet_name);
    }

    //全部钱包上锁
    function lock_all()
    {
        return $this->wallet_request(__FUNCTION__);
    }

}