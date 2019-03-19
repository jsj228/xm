<?php
namespace app\common\ext;
use think\Db;

class BtmClient{

    private $host;
    private $port;
    private $account;
    private $password;
    private $accountId;
    private $version;
    private $token;

    public function __construct($host,$port,$account,$password,$accountId,$token='',$version=1.03){
        $this->host = $host;
        $this->port = $port;
        $this->version = $version;
        $this->account = $account;
        $this->password = $password;
        $this->accountId = $accountId;
        $this->token = $token;
    }

    public function getVersion()
    {
        return $this->version;
    }

    //btm转入
    public function income()
    {
        $url = $this->host.':'.$this->port.'/list-transactions';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'account_id'    =>  $this->accountId,
            'unconfirmed'   => true,
            'detail'        => true
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            $count_res = count($res['data']);
            if ($count_res> 100){
                $res_cut = array_slice($res['data'],-100);
                unset($res);
            }else{
                $res_cut = $res['data'];
                unset($res);
            }
            $addList = $this->listAddresses();
            if (!$addList || count($addList)<=0){
                return false;
            }
            $txid = [];
            foreach ($res_cut as $k=>$v){
                if ($v['status_fail']){
                    continue;
                }

                $chkOut = end($v['outputs']);
                if (in_array($chkOut['address'],$addList)){
                    if (preg_match('/([1-9]|[a-z]|[A-Z])/',$v['block_hash']) && $v['block_height'] && $v['block_index']){
                        $zrData = Db::name('myzr')->where('username="'.$chkOut['address'].'" and coinname="btm" and txid="'.$v['tx_id'].'"')->find();
                        if ($zrData){
                            //数据已经存在
                            if ($zrData['status'] == 1){
                                continue;
                            }else{
                                $mo = Db::name('');
                                $mo->startTrans();
                                $zrUp = Db::name('myzr')->where('id='.$zrData['id'])->update(['status'=>1]);
                                $userUp = Db::name('user_coin')->where('userid='.$zrData['userid'])->setInc('btm',$chkOut['amount']/100000000);
                                if (!$zrUp || !$userUp){
                                    $mo->rollback();
                                }else{
                                    $mo->commit();
                                }
                            }
                        }else{
                            //数据库不存在
                            $uid = Db::name('user_coin')->where('btmzb="'.$chkOut['address'].'"')->getField('userid');

                            $zrUpData = [
                                'userid'        =>  $uid,
                                'username'      =>  $chkOut['address'],
                                'coinname'      =>  'btm',
                                'txid'          =>  $v['tx_id'],
                                'num'           =>  $chkOut['amount']/100000000,
                                'mum'           =>  $chkOut['amount']/100000000,
                                'addtime'       =>  time(),
                                'status'        =>  1
                            ];
                            $mo = Db::name('');
                            $mo->startTrans();
                            $zrUp = Db::name('myzr')->insertGetId($zrUpData);
                            $userUp = Db::name('user_coin')->where('userid='.$uid)->setInc('btm',$chkOut['amount']/100000000);
                            if (!$zrUp || !$userUp){
                                $mo->rollback();
                            }else{
                                $mo->commit();
                            }
                        }
                    }else{
                        $zrData = Db::name('myzr')->where('username="'.$chkOut['address'].'" and coinname="btm" and txid="'.$v['tx_id'].'"')->find();
                        if ($zrData){
                            continue;
                        }else{
                            //数据库不存在
                            $uid = Db::name('user_coin')->where('btmzb="'.$chkOut['address'].'"')->getField('userid');
                            $zrUpData = [
                                'userid'        =>  $uid,
                                'username'      =>  $chkOut['address'],
                                'coinname'      =>  'btm',
                                'txid'          =>  $v['tx_id'],
                                'num'           =>  $chkOut['amount']/100000000,
                                'mum'           =>  $chkOut['amount']/100000000,
                                'addtime'       =>  time(),
                                'status'        =>  -1
                            ];
                            $mo = Db::name('');
                            $mo->startTrans();
                            $zrUp = Db::name('myzr')->insertGetId($zrUpData);

                            if (!$zrUp){
                                $mo->rollback();
                            }else{
                                $mo->commit();
                            }
                        }
                    }
                }else{
                    continue;
                }
            }
        }else{
            return false;
        }
    }

    //btm转出
    public function outcome($address,$num)
    {
        if (!$address){
            return false;
        }

        //地址交易
        if (!$this->validateAddress($address)){
            return false;
        }

        if (!$num || $num <= 0){
            return false;
        }

        //获取交易手续费
        $gasRate = $this->gasRate();
        if (!$gasRate){
            return false;
        }

        //获取资产
        $balance = $this->listBalance();
        if (!$balance){
            return false;
        }

        $amount = $balance['amount'];
        $num *= 100000000;
        //判断余额是否足够
        if ($amount < $num){
            return false;
        }
        //创建交易
        $url = $this->host.':'.$this->port.'/build-transaction';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'base_transaction'     =>  null,
            'actions'        =>  [
                /*[
                    'account_id'    =>  $this->accountId,
                    'amount'        =>  900000,
                    'asset_id'      =>  $balance['asset_id'],
                    'type'          =>  'spend_account'
                ],*/
                [
                    'account_id'    =>  $this->accountId,
                    'amount'        =>  $num,
                    'asset_id'      =>  $balance['asset_id'],
                    'type'          =>  'spend_account'
                ],

                [
                    'amount'        =>  $num,
                    'asset_id'      =>  $balance['asset_id'],
                    'address'       =>  $address,
                    'type'          =>  'control_address'
                ]
            ],
            'ttl'           =>  5000,
            'time_range'    =>  time()+5*60
        ];

        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            $url = $this->host.':'.$this->port.'/estimate-transaction-gas';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
            curl_setopt($ch, CURLOPT_USERPWD, $this->token);
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            $postData = [
                'transaction_template'      =>  $res['data']
            ];

            $postData = json_encode($postData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $data = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($data,true);
            if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
                $url = $this->host.':'.$this->port.'/build-transaction';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
                curl_setopt($ch, CURLOPT_USERPWD, $this->token);
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                $postData = [
                    'base_transaction'     =>  null,
                    'actions'        =>  [
                        [
                            'account_id'    =>  $this->accountId,
                            'amount'        =>  $res['data']['total_neu'],
                            'asset_id'      =>  $balance['asset_id'],
                            'type'          =>  'spend_account'
                        ],
                        [
                            'account_id'    =>  $this->accountId,
                            'amount'        =>  $num,
                            'asset_id'      =>  $balance['asset_id'],
                            'type'          =>  'spend_account'
                        ],

                        [
                            'amount'        =>  $num,
                            'asset_id'      =>  $balance['asset_id'],
                            'address'       =>  $address,
                            'type'          =>  'control_address'
                        ]
                    ],
                    'ttl'           =>  5000,
                    'time_range'    =>  time()+5*60
                ];

                $postData = json_encode($postData);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                $data = curl_exec($ch);
                curl_close($ch);
                $res = json_decode($data,true);
                if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
                    return $res['data'];
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    //签署交易
    public function signTransaction($sign)
    {

        if (!$sign){
            return false;
        }

        $signData = [
            'allow_additional_actions'      =>  false,
            'local'                         =>  true,
            'raw_transaction'               =>  $sign['raw_transaction'],
            'signing_instructions'          =>  $sign['signing_instructions']
        ];


        $url = $this->host.':'.$this->port.'/sign-transaction';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'password'     =>  $this->password,
            'transaction'        =>  $signData
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return  $res['data']['transaction']['raw_transaction'];
        }else{
            return false;
        }
    }
    
    //提交转出
    public function submitTransaction($rawTransaction)
    {
        if (!$rawTransaction){
            return false;
        }
        $url = $this->host.':'.$this->port.'/submit-transaction';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'raw_transaction'     =>  $rawTransaction
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return  $res['data'];
        }else{
            return false;
        }

    }
    
    //创建地址
    public function createAcountAddress()
    {
        if (!$this->account){
            return false;
        }

        if (!$this->accountId){
            return false;
        }

        $url = $this->host.':'.$this->port.'/create-account-receiver';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'account_alias'     =>  $this->account,
            'account_id'        =>  $this->accountId
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return  $res['data']['address'];
        }else{
            return false;
        }
    }

    //校验地址
    public function validateAddress($address)
    {
        if (!$address){
            return false;
        }

        $url = $this->host.':'.$this->port.'/validate-address';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'address'     =>  $address
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return  $res['data']['valid'];
        }else{
            return false;
        }
    }

    //获取账户余额
    public function listBalance()
    {
        $url = $this->host.':'.$this->port.'/list-balances';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return $res['data'][0];
        }else{
            return false;
        }
    }

    //获取未确认事物
    public function listUnconfirmedTransactions()
    {
        $url = $this->host.':'.$this->port.'/get-unconfirmed-transaction';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'account_id'    =>  'd20bccfecf554ce2be19b4fa4c61ca5115c24030f3d226c5b279b59dd1940251'
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        print_r($res);die;
    }

    //获取区块
    public function getBlock()
    {
        $url = $this->host.':'.$this->port.'/get-block';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'block_height'    =>  61520
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return $res['data'];
        }else{
            return false;
        }
    }
    
    //获取手续费
    public function gasRate()
    {
        $url = $this->host.':'.$this->port.'/gas-rate';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return $res['data']['gas_rate'];
        }else{
            return false;
        }
    }

    //地址列表
    public function listAddresses()
    {
        $url = $url = $this->host.':'.$this->port.'/list-addresses';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'account_alias'     =>  $this->account,
            'account_id'        =>  $this->accountId
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            $addList = [];
            foreach ($res['data'] as $k=>$v){
                array_push($addList,$v['address']);
            }
            return $addList;
        }else{
            return false;
        }

    }

    //获取区块高度
    public function getBlockCount()
    {
        $url = $url = $this->host.':'.$this->port.'/get-block-count';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);

        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return $res['data']['block_count'];
        }else{
            return false;
        }
    }

    public function listUnspentOutputs()
    {
        $url = $url = $this->host.':'.$this->port.'/list-unspent-outputs';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = [
            'id'     =>  ""
        ];
        $postData = json_encode($postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($data,true);
        if ($res['status'] == 'success' || $res['status'] == 'SUCCESS'){
            return $res['data'];
        }else{
            return false;
        }
    }
}