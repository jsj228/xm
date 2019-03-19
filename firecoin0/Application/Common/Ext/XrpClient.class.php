<?php
namespace Common\Ext;

class XrpClient{

    private $host;
    private $port;
    private $account;
    private $password;
    private $token;

    public function __construct($host = 'http://18.188.242.103',$port = '15115',$account = 'rKDiQGzFLWud1YcbkBKvu5NgMzBBNQYDor',$password = '',$token=''){
        $this->host = $host;
        $this->port = $port;
        $this->account = $account;
        $this->password = $password;
        $this->token = $token;
    }

    //获取账户信息
    public function accountInfo()
    {
        $params = '{
            "method": "account_info",
            "params": [
                {
                    "account": "'.$this->account.'",
                    "strict": true,
                    "ledger_index": "current",
                    "queue": true
                }
            ]
        }';
        
        return $this->post($params);
    }

    public function sign($num=0,$destination='',$tag='')
    {
        $num = floor($num * 1000000);
        $tag = $tag ? $tag : '888888888';
        $params = '{
                    "method": "sign",
                    "params": [
                        {
                            "offline": false,
                            "secret": "'.$this->password.'",
                            "tx_json": {
                                "Account": "'.$this->account.'",
                                "Amount": '.$num.',
                                "Destination": "'.$destination.'",
                                "DestinationTag": "'.$tag.'",
                                "TransactionType": "Payment"
                            },
                            "fee_mult_max": 1000
                        }
                    ]
                }';


        return $this->post($params);
    }

    public function submit($tx_blob = '')
    {
        $params = '{
            "method": "submit",
            "params": [
                {
                    "tx_blob": "'.$tx_blob.'"
                }
            ]
        }';

        return $this->post($params);
    }

    //轮行订单
    public function history()
    {
        $params = '{
            "method": "account_tx",
            "params": [
                {
                    "account": "'.$this->account.'",
                    "binary": false,
                    "forward": false,
                    "ledger_index_max": -1,
                    "ledger_index_min": -1,
                    "limit": 10
                }
            ]
        }}';
        return $this->post($params);
    }

    //校验交易订单
    public function tx($transaction = '')
    {

        if (!$transaction){
            return false;
        }
        $params = '{
            "method": "tx",
            "params": [
                {
                    "transaction": "'.$transaction.'",
                    "binary": false
                }
            ]
        }';

        return $this->post($params);

    }

    //btm转入
    public function post($params = '')
    {
        if (!$params){
            return false;
        }

        $url = $this->host . ':' . $this->port;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data,true);
    }
}