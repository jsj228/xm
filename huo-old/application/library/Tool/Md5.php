<?php
class Tool_Md5{
	const SEED_FOR_PASSWD = 'i7rvAOTGK&d@%KrTKFeQHLRd';
    /**
     * 邮箱激活码生成
     *
     * @param $pEmail 邮箱
     * @param int $pRegtime 注册时间
     * @return string
     */
    static function emailActivateKey($pEmail , $pRegtime){
        return md5(md5($pEmail) . $pRegtime . md5('ybex'));
    }

    /**
     * 权限code加密
     * @param string $var
     */
    static function rightsCodeCreate($var) {
        return md5(strtolower($var).'ftc');
    }

	/**
     * 随机种子,根据需要可以增加相应算法
     */
    public static function getUserRand($prefix='rand_', $more=false){
        return uniqid($prefix, $more);
    }

	/**
     * user加密key
     */
    public static function getUserSeed($rand){
        $str = $rand.self::SEED_FOR_PASSWD;
        if(!$str = base64_encode($str)){
            return false;
        }
        $str = trim($str, '=');
        $str = strtr($str, array('c'=>'=','b'=>'-','d'=>'_')).self::SEED_FOR_PASSWD;
        return $str;
    }

	/**
     * 获取加密后登陆密码
     * @param pwd 未加密的密码
     * @param rand 该用户随机码
     */
    public static function encodePwd($pwd, $rand){
        $seed = self::getUserSeed($rand).'login';
        return md5($seed.md5($pwd).self::SEED_FOR_PASSWD);
    }

    /**
     * 获取加密后登陆密码
     * @param pwd  MD5加密过的数据
     * @param rand 该用户随机码
     */
    public static function encodePwdMD5($pwd, $rand)
    {
        $seed = self::getUserSeed($rand) . 'login';
        return md5($seed . $pwd . self::SEED_FOR_PASSWD);
    }
	/**
     * 获取加密后交易密码
     */
    public static function encodePwdTrade($pwd, $rand){
        $seed = self::getUserSeed($rand).'trade';
        return md5($seed.md5($pwd).self::SEED_FOR_PASSWD);
    }

    /**
     * 获取加密后交易密码(yibi)
     */
    public static function encodePwdTrade1($pwd, $rand)
    {
        $seed = self::getUserSeed($rand) . 'trade';
        return md5($seed . $pwd . self::SEED_FOR_PASSWD);
    }
    /**
     * @desc 检测当前交易是否需输入交易密码
     * @param $act add增加,del退出删除,normal检测是否需要输入
     *
     * return 0输入,1不输入,false逻辑错误
     */
    public static function pwdTradeCheck($uid, $act='normal')
    {
        $redis = Cache_Redis::instance('common');
        if($act == 'normal'){
            $sid = $redis->hGet('pwdtrade', $uid);
            if($sid != session_id()){
                #$redis->hSet('pwdtrade', $uid, session_id());
                return 0;
            }
            return 1;
        } elseif($act == 'add'){
            return $redis->hSet('pwdtrade', $uid, session_id());
        } elseif($act == 'del'){
            return $redis->hSet('pwdtrade', $uid, 0);
        }
        return false;
    }


}
