<?php
/**
 * Created by PhpStorm.
 * User: Slagga
 * Date: 9/9/2017
 * Time: 11:21 AM
 */

namespace org\coin;

class EthClient
{
    protected $host, $port, $version, $key, $carry;
    protected $id = 0;

    function __construct($host, $port, $version = "2.0")
    {
        $this->host = $host;
        $this->port = $port;
        $this->version = $version;
        $this->key = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"; // 默认密钥
        $this->carry = 1; // 默认进位
    }

    // 普通整数N进制转换，将$raw转换为$ary，文本长度为$len
    public function changeInt($raw, $ary, $len)
    {
        // 变量初始化
        $result = ""; // 结果
        $variable = 1; // 临时变量
        $residue = 1; // 余数
        $median = 1; // 文本长度
        $verify = $raw; // 原数值
        if ($raw == 0)
            $result = substr($this->key, 0, $this->carry);
        while ($raw != 0) {
            $variable = intval($raw / $ary);
            $residue = $raw % $ary;
            $result = substr($this->key, $residue * $this->carry, $this->carry) . $result;
            $raw = $variable;
        }
        $median = strlen($result); // 取结果文本长度
        if ($median < $len) // 如果不够位数则补短
            $result = $this->fillPlace($len - $median) . $result;
        if ($this->revertInt($ary, $result) != $verify)
            return - 1;
        return $result;
    }

    // 普通整数N进制反转换
    public function revertInt($ary, $value)
    {
        // 变量初始化
        $result = "";
        $median = intval(strlen($value) / $this->carry);
        $character = "";
        for ($i = 1; $i <= $median; $i ++) {
            if ($this->carry > 1) { // 多进位进制转换
                $character = substr($value, $i * $this->carry - ($this->carry), $this->carry);
                $result += (intval(strpos($this->key, $character) / $this->carry)) * pow($ary, $median - $i);
            } else { // 单进位进制转换
                $character = substr($value, $i * $this->carry - 1, $this->carry);
                $result += intval(strpos($this->key, $character)) * pow($ary, $median - $i);
            }
        }
        return $result;
    }

    // 大整数N进制转换，将$raw转换为$ary，文本长度为$len
    public function changeBigInt($raw, $ary, $len)
    {
        // 变量初始化
        bcscale(0); // 设置没有小数位。
        $result = ""; // 结果
        $variable = 1; // 临时变量
        $residue = 1; // 余数
        $median = 1; // 文本长度
        $verify = $raw; // 原数值
        if ($raw == "0")
            $result = substr($this->key, 0, $this->carry);
        while ($raw != "0") {
            $variable = bcdiv($raw, $ary);
            $residue = bcmod($raw, $ary);
            $result = substr($this->key, $residue * $this->carry, $this->carry) . $result;
            $raw = $variable;
        }
        $median = strlen($result); // 取结果文本长度
        if ($median < $len) // 如果不够位数则补短
            $result = $this->fillPlace($len - $median) . $result;
        if ($this->revertBigInt($ary, $result) != $verify)
            return - 1;
        return $result;
    }

    // 大整数N进制反转换
    public function revertBigInt($ary, $value)
    {
        // 变量初始化
        bcscale(0); // 设置没有小数位。
        $result = "";
        $median = bcdiv(strlen($value), $this->carry);
        $character = "";
        for ($i = 1; $i <= $median; $i ++) {
            if ($this->carry > 1) { // 多进位进制转换
                $character = substr($value, $i * $this->carry - ($this->carry), $this->carry);
                $result = bcadd(bcmul(bcdiv(strpos($this->key, $character), $this->carry), bcpow($ary, $median - $i)), $result);
            } else { // 单进位进制转换
                $character = substr($value, $i * $this->carry - 1, $this->carry);
                $result = bcadd(bcmul(strpos($this->key, $character), bcpow($ary, $median - $i)), $result);
            }
        }
        return $result;
    }

    // 补位函数
    public function fillPlace($number)
    {
        $character = substr($this->key, 0, $this->carry); // 取默认为0的字符。
        $result = $character;
        for ($i = 1; $i <= $number - 1; $i ++)
            $result .= $character;
        return $result;
    }

    function request($method, $params = array())
    {
        $data = array();
        $data['jsonrpc'] = $this->version;
        $data['id'] = $this->id ++;
        $data['method'] = $method;
        $data['params'] = $params;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $ret = curl_exec($ch);
        return @json_decode($ret);
    }

    public function ether_request($method, $params = array())
    {
        $ret = $this->request($method, $params);
        if($ret){
            return $ret->result;
        }
        return 0;

    }

    public function real_banlance($input)
    {
        if ($input > 1) {
            return $input / 1000000000000000000;
        }
        return 0;
    }

    public function to_real_value($input)
    {
        if ($input > 0) {
            return bcmul((string) $input, '1000000000000000000');
        }
        return 0;
    }
    //单位转换
    public function real_banlance_token($input , $number = '0')
    {
        if ($input > 1) {
            $decimals = 1;
            for ( $a = 0 ; $a < $number ; $a++) {
                $decimals .= '0';
            }
            return $input / $decimals;
        }
        return 0;
    }
    //单位转换
    public function to_real_value_token($input , $number = '0')
    {
        if ($input > 0) {
            $decimals = 1;
            for ($a = 0 ; $a < $number ; $a++) {
                $decimals .= '0';
            }
            return bcmul((string) $input, $decimals);
        }
        return 0;
    }
    //拼接data
    public function data_pj($address, $value = ''){
        $is_value = substr($value,0,2);
        if ($is_value == '0x') {
            $value = substr($value,2);
            for ($a  = 0;strlen($value) < 64 ; $a++){
                $value = '0'.$value;
            }
        }
        $is_address = substr($address ,0,2);
        if ($is_address == '0x') {
            $address = substr($address,2);
            for($a = 0 ; strlen($address) < 64 ; $a++){
                $address = '0'.$address;
            }
        }
        return $address.$value;
	
    }
    //查询余额
    function eth_call($address, $block = 'latest')
    {
        return $this->ether_request(__FUNCTION__, array(
            $address,
            $block
        ));
    }


    public function decode_hex($input)
    {
        if (preg_match('/[a-f0-9]+/', $input))
            return hexdec($input);

        return $input;
    }

    private function dec_to_hex($dec)
    {
        $sign = ""; // suppress errors
        if ($dec < 0) {
            $sign = "-";
            $dec = abs($dec);
        }

        $hex = Array(
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 'a',
            11 => 'b',
            12 => 'c',
            13 => 'd',
            14 => 'e',
            15 => 'f'
        );

        $h = '';
        do {
            $h = $hex[($dec % 16)] . $h;
            $dec /= 16;
        } while ($dec >= 1);

        return $sign . $h;
    }

    public function encode_dec($input)
    {
        if (preg_match('/[0-9]+/', $input)) {
            return '0x' . $this->changeBigInt($input, 16, 0);
        }

        return $input;
    }

    function personal_unlockAccount($address, $password, $time = 60)
    {
        return $this->ether_request(__FUNCTION__, array(
            $address,
            $password,
            $time
        ));
    }

    function personal_lockAccount($address)
    {
        return $this->ether_request(__FUNCTION__, array(
            $address
        ));
    }

    function eth_protocolVersion()
    {
        return $this->ether_request(__FUNCTION__);
    }

    function eth_accounts()
    {
        return $this->ether_request(__FUNCTION__);
    }

    function eth_blockNumber($decode_hex = FALSE)
    {
        $block = $this->ether_request(__FUNCTION__);
        if ($decode_hex)
            $block = $this->decode_hex($block);
        return $block;
    }

    function eth_getBalance($address, $block = 'latest')
    {
        $balance = $this->ether_request(__FUNCTION__, array(
            $address,
            $block
        ));

        $balance = $this->decode_hex($balance);

        return $this->real_banlance($balance);
    }

    function eth_getTransactionReceipt($hash)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash
        ));
    }

    function eth_getTransactionCount($address, $block = 'latest', $decode_hex = FALSE)
    {
        $count = $this->ether_request(__FUNCTION__, array(
            $address,
            $block
        ));

        if ($decode_hex)
            $count = $this->decode_hex($count);

        return $count;
    }

    function eth_sign($address, $input)
    {
        return $this->ether_request(__FUNCTION__, array(
            $address,
            $input
        ));
    }

    function eth_sendTransaction($address, $password, $transaction)
    {
        $this->personal_unlockAccount($address, $password);
        $result = $this->request(__FUNCTION__, $transaction);
        $this->personal_lockAccount($address);
        return $result;
    }

    function eth_gasPrice()
    {
        return $this->ether_request(__FUNCTION__);
    }

    function eth_getBlockByHash($hash, $full_tx = TRUE)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash,
            $full_tx
        ));
    }

    function eth_getBlockByNumber($block = 'latest', $full_tx = TRUE)
    {
        return $this->ether_request(__FUNCTION__, array(
            $block,
            $full_tx
        ));
    }

    function eth_getTransactionByHash($hash)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash
        ));
    }

    /**
     * 列出单个高度，所有交易记录
     */
    function listLocal($name, $json)
    {
        //获取最新高度
        $blockNumber = M('coin')->where(array('name' => $name))->getField('block_num');
        //获取最新高度，数据库高度大于最新高度终止执行
        if($blockNumber > $json){
            return [];
        }

        $count = hexdec($this->eth_getBlockTransactionCountByNumber($blockNumber));
        for ($k = 0; $k < $count; $k++) {
            $transactions[] = $this->eth_getTransactionByBlockNumberAndIndex($blockNumber, $k);
        }
        M('coin')->where(array('name' => $name))->setInc('block_num', 1);
        return $transactions;
    }

    /**
     * 列出所有高度的交易
     */
    function listLocalTransactions($name)
    {
        $preBlockNumber = 5877889; // 编写代码时最新区块
        $currentBlockNumber = $this->eth_blockNumber(true);

        $blockNumber = M('coin')->where(array('name' => $name))->getField('block_num');
        if (is_null($blockNumber) || $blockNumber <= 0) {
            M('coin')->where(array('name' => $name))->save(array('block_num' => $preBlockNumber));
            $blockNumber = $preBlockNumber;
        }

        echo '<br>currentBlockNumber-blockNumber:' . ($currentBlockNumber-$blockNumber) .'<br>';
        $transactions = array();
        if ($currentBlockNumber >= $blockNumber) {
            for ($i = $blockNumber; $i <= $currentBlockNumber; $i++) {
                $count = hexdec($this->eth_getBlockTransactionCountByNumber($i));

                for ($k = 0; $k < $count; $k++) {
                    $transactions[] = $this->eth_getTransactionByBlockNumberAndIndex($i, $k);
                }
                M('coin')->where(array('name' => $name))->setInc('block_num', 1);
            }
        }
        return $transactions;
    }

    function eth_getTransactionByBlockNumberAndIndex($blockNumber, $index)
    {
        return $this->ether_request(__FUNCTION__, array(
            $this->encode_dec($blockNumber),
            $this->encode_dec($index)
        ));
    }

    function eth_getBlockTransactionCountByNumber($index)
    {
        return $this->ether_request(__FUNCTION__, array(
            $this->encode_dec($index)
        ));
    }

    /**
     * 创建地址
     *
     * @param $pass
     */
    function personal_newAccount($pass)
    {
        return $this->ether_request(__FUNCTION__, array(
            $pass
        ));
    }
}