<?php
class Tool_Wallet
{
    /**
     * 判断该地址是否是钱包地址
     */
    public static function isYbcoin($wallet){
        if(Tool_Validate::az09($wallet) && strlen($wallet) == 34 && substr($wallet, 0, 1) == 'Y'){
            return  true;
        }
        return false;
    }
    /**
     * 获取该币种当前的产量
     * @param coin 币种
     */
    public static function getSupply($coin){
        //没有总量的币，写死
        switch($coin){
            case 'tmt':
                return 30000000;
                break;
            default:
                break;
        }
        $info = Api_Rpc_Client::instance($coin)->getinfo();
        if(empty($info)){
            return false;
        }
        $supply = isset($info['moneysupply']) ? $info['moneysupply'] : (isset($info['supply']) ? $info['supply'] : 0);
        return $supply;
    }
    /**
     * 获取钱包余额
     */
    public static function getBalance($coin){
        $info = Api_Rpc_Client::instance($coin)->getinfo();
        if(empty($info)){
            return false;
        }
        $balance = isset($info['balance']) ? $info['balance'] : 0;
        return $balance;
    }
}
