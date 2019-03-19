<?php
class UserModel extends Orm_Base{

    //333
	public $table = 'user';
	public $field = array(
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'pid' => array('type' => "int(11) unsigned", 'comment' => '介绍人'),
		'email' => array('type' => "char(60)", 'comment' => '邮箱'),
		'name' => array('type' => "char(32)", 'comment' => '名字'),
		'cardtype' => array('type' => "tinyint(1)", 'comment' => '1、身份证 2、驾驶证 3、护照 4、军官证 5、港澳台通行证'),
		'idcard' => array('type' => "char(30)", 'comment' => '身份证'),
		'pwd' => array('type' => "char(32)", 'comment' => '密码'),
		'pwdtrade' => array('type' => "char(32)", 'comment' => '交易密码'),
		'mo' => array('type' => "char(11)", 'comment' => '手机'),
		'area' => array('type' => "char(5)", 'comment' => '区号'),
		'role' => array('type' => "enum('admin','user', 'read')", 'comment' => '角色'),
		'prand' => array('typepe' => "varchar", 'comment' => '随机加密串'),
		'google_key' => array('typepe' => "char(30)", 'comment' => '谷歌验证码key'),
		'btc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => '剩余比特币'),
		'btc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => '冻结比特币'),
		'ltc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => '剩余莱特币'),
		'ltc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => '冻结莱特币'),
		'eth_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'eth_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'etc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'etc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'eos_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'eos_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'fdc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'fdc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mcc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mcc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'rss_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'rss_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'doge_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'doge_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'hxi_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'hxi_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'jjf_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'jjf_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'npc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'npc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'gts_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'gts_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'etc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'etc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'gthx_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'gthx_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'htc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'htc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mac_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mac_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'sbtc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'sbtc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ubtc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ubtc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'etf_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'etf_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'obtc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'obtc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'lbtc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'lbtc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bvt_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bvt_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'nano_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'nano_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dcon_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dcon_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'nbtc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'nbtc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'afc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'afc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'kkc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'kkc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ptoc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ptoc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bcd_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bcd_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'obc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'obc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'lcc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'lcc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'xtc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'xtc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'cash_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'cash_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ethms_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ethms_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dob_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dob_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'sw_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'sw_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mbt_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mbt_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'qaq_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'qaq_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'en_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'en_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ctz_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ctz_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'read_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'read_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'nrc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'nrc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ait_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ait_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bqt_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bqt_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'kycc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'kycc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dst_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dst_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'sec_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'sec_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ctzz_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ctzz_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'pal_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'pal_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'jc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'jc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ccl_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ccl_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bocc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bocc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dco_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dco_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ctm_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'ctm_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'uenc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'uenc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'zcc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'zcc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bta_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'bta_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'credit' => array('type' => "decimal(5,4) unsigned", 'comment' => '费率'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),
		'updateip' => array('type' => "char(15)", 'comment' => '修改ip'),
        'source'    => array('type'=>'int','comment'=>'来源'),
        'registertype' => array('type' => "tinyint(1)", 'comment' => '1.邮箱 2.手机注册'),
        'from_uid' => array('type' => "int(11) unsigned",'comment' => '邀请人'),
        'rebate' => array('type' => "varchar(200) unsigned",'comment' => '返佣'),
		'google_key' => array('type' => "char(15)", 'comment' => 'google_key'),
	);
	public $pk = 'uid';

	static function addRedis($db=2, $key,$field,$value)   //添加redis缓存
	{
		$redis = Cache_Redis::instance();
		$redis->select($db);
		$redis->hset($key, $field, $value);
		$redis->select(0);
	}

	static function lookRedis($db = 2, $key, $field)   //查看redis缓存
	{
		$redis = Cache_Redis::instance();
		$redis->select($db);
		$data = $redis->hget($key, $field);
		$redis->select(0);
		return $data;
	}

	static function delRedis($db = 2, $key, $field)   //删除redis缓存
	{
		$redis = Cache_Redis::instance();
		$redis->select($db);
		$data = $redis->hdel($key, $field);
		$redis->select(0);
		return $data;
	}

	/**
	 * 写入(Reids): 基本用户信息
	 */
	static function saveRedis(&$pUser, &$pRedis = false){
		if($pRedis){
			$pRedis->hSet('useremail', $pUser['email'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		} else {
			static $redis;
			$redis || $redis = &Cache_Redis::instance();
			$redis->hSet('useremail', $pUser['email'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		}
	}
	/**
	 * 写入(Reids): 基本用户信息
	 */
	static function phonesaveRedis(&$pUser, &$pRedis = false){
		if($pRedis){
			// $pRedis->hSet('useremail', $pUser['email'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
			$redis->hSet('userphone', $pUser['mo'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		} else {
			static $redis;
			$redis || $redis = &Cache_Redis::instance();
			// $redis->hSet('useremail', $pUser['email'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
			$redis->hSet('userphone', $pUser['mo'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		}
	}

	/**
	 * 写入(Reids): 基本用户信息 phone
	 */
	static function phonesaveRedis1(&$pUser, &$pRedis = false){
		if($pRedis){
			$redis->hSet('userphone', $pUser['mo'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		} else {
			static $redis;
			$redis || $redis = &Cache_Redis::instance();
			$redis->hSet('userphone', $pUser['mo'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		}
	}


    /**
     * 写入redis:邮件
     *
     * @param string $pData
     * @return bool
     */

	//2018/5  邮箱注册
	static function saveEmailRedis($pData)
	{
		if ($pData['find'] == 1) //找回登入密码或交易密码
		{
			$code = Tool_Fnc::mailto($pData['email'], $pData['tltle'] ,$pData['msg']);
            return $code;
		} else          //邮箱注册
		{
			$pUrlparam = array(
				'email' => $pData['email'],
				'key'   => $pData['key'],
			);
			$host = Yaf_Registry::get("config")->domain;
	   //	$url = ((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'https') !== false)) ? 'https://' : 'http://';
            $pActivateurl =  $host . '/Emailverify/emailactivate?id=' . base64_encode(serialize($pUrlparam));
			$statusMap = array(
				'yi'    =>$GLOBALS['MSG']['EMAIL_NIHAO'],
				'er'    => $GLOBALS['MSG']['EMAIL_ZHUCE'],
				'san'   => $GLOBALS['MSG']['EMAIL_DIANJ'],
				'si'    => $GLOBALS['MSG']['EMAIL_CJ'],
				'wu'    =>  $GLOBALS['MSG']['EMAIL_TD'],
				'liu'    =>  $GLOBALS['MSG']['EMAIL_ZHUC'],
				'qi'    =>  $GLOBALS['MSG']['EMAIL_BUB'],
				'activate_url' => $pActivateurl
			);

            if ($pHtml = Tool_Fnc::emailTemplate($statusMap, 'activate')) {
            //if ($pHtml = Tool_Fnc::emailTemplate(array('name' => empty($pData['name']) ? '尊敬的会员1' : $pData['name'], 'activate_url' => $pActivateurl), 'activate')) {
                $eData = array(
                    'title' => "{$GLOBALS['MSG']['EMAIL_HY']}",
                    'body'  => $pHtml,
                    'email' => $pData['email'],
                );
            }
			$eRedis = Cache_Redis::instance('email');
			$eRedis->lpush('sentemail', json_encode($eData));

            $pData = $eRedis->rpop('sentemail');
            $pData = json_decode($pData, true);

            $code = Tool_Fnc::mailto($pData['email'] , $pData['title'] , $pData['body']);
            if($code != 1)
            {
                $logDir = 'email/'.date('Ymd');
                Tool_Log::wlog(sprintf("sent failed : %s, [%s]", $pData['email'], $code), $logDir, true);
            }

            $_POST['regtype'] = trim($_POST['regtype']);

			}

		}



    /*static function saveEmailRedis($pData){
	$pUrlparam = array(
		'uid' => $pData['uid'],
		'email' => $pData['email'],
		'key' => $pData['key'],
	);
	$url = isset($_SERVER['HTTPS']) ? 'https://': 'http://';
	$pActivateurl = $url . $_SERVER['HTTP_HOST'] .'/user/emailactivate?id='.base64_encode(serialize($pUrlparam));
	if($pHtml = Tool_Fnc::emailTemplate(array('name' => empty($pData['name'])?'尊敬的会员':$pData['name'], 'activate_url' => $pActivateurl) , 'activate')){
		if(UserModel::getInstance()->where("uid={$pData['uid']}")->count()){
			$eData = array(
				'title' => '请您激活币交所账号',
				'body' => $pHtml,
				'email' => $pData['email'],
			);
		}else{
			$eData = array(
				'title' => '欢迎注册币交所',
				'body' => $pHtml,
				'email' => $pData['email'],
			);
		}

		$eRedis = Cache_Redis::instance('default');
		$eRedis->lpush('sentemaillist' , json_encode($eData));
	}
}*/
    /**
     * 写入redis:邮件 手機注冊
     *
     * @param string $pData
     * @return bool
     */
    static function regsaveEmailRedis($pData){
        $pUrlparam = array(
            'uid' => $pData['uid'],
            'email' => $pData['email'],
            'mo' => $pData['phone'],
            'key' => $pData['key'],
        );
        $url = isset($_SERVER['HTTPS']) ? 'https://': 'http://';
        $pActivateurl = $url . $_SERVER['HTTP_HOST'] .'/user/emailactivate?id='.base64_encode(serialize($pUrlparam));
        if($pHtml = Tool_Fnc::emailTemplate(array('name' => empty($pData['name'])?'尊敬的会员':$pData['name'], 'activate_url' => $pActivateurl) , 'activate')){
			if(UserModel::getInstance()->where("uid={$pData['uid']}")->count()){
				$eData = array(
					'title' => '请您激活币交所账号',
					'body' => $pHtml,
					'email' => $pData['email'],
				);
			}else{
				$eData = array(
					'title' => '欢迎注册币交所',
					'body' => $pHtml,
					'email' => $pData['email'],
				);
			}

            $eRedis = Cache_Redis::instance('default');
            $eRedis->lpush('sentemaillist' , json_encode($eData));
        }
    }


	/**
	 * 得到(Redis): 用户基本信息
	 * @param $pEmail
	 * @param bool $pPos : pwd, uid, role | false | array
	 * @return array|bool
	 */
	static function getRedis($pEmail, $pPos = false,$isphone = '1'){
		static $redis;
		$redis || $redis = &Cache_Redis::instance();
		# 验证数据是否存在
		// 郵箱

		if($isphone == 1)
		{
			if(!$tUser = $redis->hGet('useremail', $pEmail)){
				return false;
			}
		}
		// 手機
		else
		{
			if(!$tUser = $redis->hGet('userphone', $pEmail)){
				return false;
			}
		}

		# 只验证是否存在
		if(!$pPos) return $tUser;
		# 返回数组
		$tHash = array();
		list($tHash['pwd'], $tHash['uid'], $tHash['role'], $tHash['prand']) = explode(',', $tUser);
		if($pPos == 'array') return $tHash;
		# 返回指定字段
		return $tHash[$pPos];
	}
	/**
	 * 得到(Redis): 用户基本信息
	 * @param $pphone
	 * @param bool $pPos : pwd, uid, role | false | array
	 * @return array|bool
	 */
	static function getphoneRedis($pphone, $pPos = false){
		static $redis;
		$redis || $redis = &Cache_Redis::instance();
		# 验证数据是否存在
		if(!$tUser = $redis->hGet('userphone', $pphone)){
			return false;
		}
		$data = $redis->hget('userphone', $pphone);
		$d = explode(',', $data);
		$da['pwd'] =  $d[0];
		$da['uid'] =  $d[1];
		$da['role'] =  $d[2];
		$da['prand'] =  $d[3];
		return $da;
		# 只验证是否存在
		// if(!$pPos) return $tUser;
		// # 返回数组
		// $tHash = array();
		// list($tHash['pwd'], $tHash['uid'], $tHash['role'], $tHash['prand']) = explode(',', $tUser);
		// if($pPos == 'array') return $tHash;
		// # 返回指定字段
		// return $tHash[$pPos];
	}

	/**
	 * 用户信息
	 * @param $pEmail
	 * @return array
	 */
	static function getByEmail($pEmail, $pGA = false){
		# 查询DB
		if(!$tUser = UserModel::getInstance()->ffEmail($pEmail)){
			return array();
		}
		# 用户双重认证标识
		if($pGA){
			$tGA = Api_Google_Authenticator::getByUid($tUser['uid']);
			@setcookie('GA_'.$tUser['uid'], $tGA['open'], $_SERVER['REQUEST_TIME']+8640000, '/');
		}

		return $tUser;
	}

	/**
	 * 用户信息
	 * @param $phone
	 * @return array
	 */
	static function getByPhone($phone, $pGA = false, $area='+86'){
		# 查询DB
		if(!$tUser = UserModel::getInstance()->where("mo={$phone} and area='{$area}'")->fRow()){
			return array();
		}
		# 用户双重认证标识
		if($pGA){
			$tGA = Api_Google_Authenticator::getByUid($tUser['uid']);
			@setcookie('GA_'.$tUser['uid'], $tGA['open'], $_SERVER['REQUEST_TIME']+8640000, '/');
		}

		return $tUser;
	}

	/**
	 * 用户信息
	 * @param $pEmail
	 * @return array
	 */
	static function getByuserEmail($uid){
		# 查询DB
		if(!$tUser = UserModel::getInstance()->field('')->fRow($uid)){
			return array();
		}
		return $tUser;
	}

	/**
	 * 用户信息
	 * @param $pEmail
	 * @return array
	 */
	static function getById($uid){
		# 查询DB
		if(!$tUser = UserModel::getInstance()->field('*')->fRow($uid)){
			return array();
		}
		return $tUser;
	}

	/**
	 * 保存 富途币
	 * @param $pUser 用户数组
	 * @param array $pData : rmb_lock, rmb_over, btc_lock, btc_over
	 * @return bool
	 */
	public function safeUpdate(&$pUser, $pData, $forced = false, $pushChannel=''){
		if(!$pUser = $this->lock()->fRow($pUser['uid'])){
			return $this->setError($GLOBALS['MSG']['SYS_BUSY']);
		}

        /*操作间隔*/
		if( !$forced && isset($_SESSION['last_user_updated'])){
			$tTime = max($_SERVER['REQUEST_TIME'] - $_SESSION['last_user_updated'], 0);
			$tMinTime = Yaf_Registry::get("config")->opt_mintime;
			if($tTime < $tMinTime){
				return $this->setError($GLOBALS['MSG']['WAIT_SEC'], $tMinTime-$tTime);
			}
			//记录本次操作时间
			$_SESSION['last_user_updated'] = time();
		}
		# 必更新字段
		$tData = array('uid' => $pUser['uid'], 'updated' => time(), 'updateip' => Tool_Fnc::realip());
		$pushData = array();


		# 重要数据更新
        foreach($pData as $k1 => $v1)
        {
			if(strpos($k1, "lock")){//判断是冻结还是解冻 c++
				$ars=explode( '_',$k1);
				$ccoin= $ars[0];
				$cnumber= $pData[$k1];
				if($pData[$k1]>0){
					$command='lock';
				}else{
					$command = 'unlock';
				}
			}

            $tData[$k1] = Tool_Math::add($pUser[$k1], $v1, 20);
            $pushData[$k1] = $tData[$k1];
            if(Tool_Math::comp(0, $tData[$k1])==1)
            {
                $tBName = explode('_', $k1);
                $errorMsg = sprintf($GLOBALS['MSG']['THIS_COIN_NOT_ENOUGH'], $tBName[0]);
                return $this->setError($errorMsg);
            }
		}

		# 更新数据库
		if(!$this->update($tData)){
			# 出现错误回滚
			return $this->setError($GLOBALS['MSG']['SYS_ERROR'], 550, $this->getLastSql().' '.json_encode($tData));
		}
		# 合并用户数据
		$pUser = array_merge($pUser, $tData);
		Tool_Session::mark($pUser['uid']);

		//PUSH
		if($pushChannel)
		{
			Tool_Push::one2nSend($pushChannel, array('t'=>'balance', 'c'=>$pushData), array($pUser['uid']));
		}
		//找到钱包地址
		$addrMo = new AddressModel();
		$addr = $addrMo->getAddrMap($pUser['uid'], $ccoin);
		if (!empty($addr)) {
			$addr = $addr[$pUser['uid']][$ccoin];
		}

		if($command)
		{
			//找到钱包地址
			$c_data = array(
				'command' => $command,
				'coin' => $ccoin,
				'number' => Tool_Math::format(abs($cnumber)),
				'addr' => $addr
			);
			$response=Api_Trans_Client::request($c_data);//调c++
			if($response['code']!=0){//失败记录日志
				$errlogdir = APPLICATION_PATH .  '/log/cc/' . date('Ymd');//日志文件
				Tool_Log::wlog(sprintf("c++操作失败,uid： %s,发送数据：%s,响应结果：%s", $pUser['uid'], json_encode($c_data), json_encode($response)), $errlogdir, true);
			}
		}

		return TRUE;
	}


	/**
	 *
	 * cli 模式下使用
	 * @param $pUser 用户数组
	 * @param array $pData : rmb_lock, rmb_over, btc_lock, btc_over
	 * @return bool
	 */
	public function safeUpdateCli(&$pUser, $pData, $forced = false, $pushChannel=''){
		if(!$pUser = $this->lock()->fRow($pUser['uid'])){
			return $this->setError($GLOBALS['MSG']['SYS_BUSY']);
		}

		# 必更新字段
		$tData = array('uid' => $pUser['uid'], 'updated' => time());
		$pushData = array();


		# 重要数据更新
        foreach($pData as $k1 => $v1)
        {
            $tData[$k1] = Tool_Math::add($pUser[$k1], $v1, 20);
            $pushData[$k1] = $tData[$k1];
            if(Tool_Math::comp(0, $tData[$k1])==1)
            {
                $tBName = explode('_', $k1);
                $errorMsg = sprintf($GLOBALS['MSG']['THIS_COIN_NOT_ENOUGH'], $tBName[0]);
                return $this->setError($errorMsg);
            }
		}

		# 更新数据库
		if(!$this->update($tData)){
			# 出现错误回滚
			return $this->setError($GLOBALS['MSG']['SYS_ERROR'], 550, ((string)$this->getError(2)).' '.$this->getLastSql().' '.json_encode($tData));
		}
		# 合并用户数据
		$pUser = array_merge($pUser, $tData);
		Tool_Session::mark($pUser['uid']);

		//PUSH
		if($pushChannel)
		{
			Tool_Push::one2nSend($pushChannel, array('t'=>'balance', 'c'=>$pushData), array($pUser['uid']));
		}

		return TRUE;
	}

	/**
	 * 用户JSON数据
	 * @param $pUser 用户数组
	 * @return array
	 */
	static function userjson($pUser){
		$tUser = array('uid'=>0, 'cny_over'=>0, 'cny_lock'=>0, 'goc_over'=>0, 'goc_lock'=>0, 'btc_over'=>0, 'btc_lock'=>0, 'ltc_over'=>0, 'ltc_lock'=>0, 'email'=>'', 'name'=>'');
		if($pUser) foreach($tUser as $k1 => $v1){
            if(isset($pUser[$k1])){
			    $tUser[$k1] = $pUser[$k1];
            }
		}
		return $tUser;
	}
	/**
	 * 用户邀请人信息
	 */
	public static function userPid($pid){
        return UserModel::getInstance()->field('uid, email, name, created')->where("pid={$pid}")->order('created desc')->fList();
	}

	/*
     * isModify
     */
    public static function isModify($uid){

	//	$userInfo = AutonymModel::getInstance()->where('status=2 and uid = '.$uid)->fRow();
		$userInfo = AutonymModel::getInstance()->where('uid = '.$uid)->fRow();
         // SHOW($userInfo);

		  if (empty($userInfo)){
				return 4;
			}else if($userInfo['status']==0){
			  return 0;
		  }else if($userInfo['status']==3){
				return 3;
			}else {//show($userInfo);
				return 2;
			}

		}
	public static function isxin($uid) {
		$userInfo = UserModel::getInstance()->field('name, idcard, pwdtrade, mo')->where('uid = '.$uid)->fRow();
		if ($userInfo['name'] == '' || $userInfo['idcard'] == '' || $userInfo['pwdtrade'] == '' || $userInfo['mo'] == '') {
			return true;
		}

		return false;
	}

     /**
     * 用户资产余额
     */
    public function getBalance($uid){
        $field = 'uid,email,';
        foreach($this->field as $k=>$v){
            if(strpos($k, '_over') || strpos($k, '_lock')){
                $field .= $k.',';
            }
        }
        $field = rtrim($field, ',');
        if($balance = $this->field("{$field}")->where("uid={$uid}")->fRow()){
            $balance['cny_over'] = bcmul($balance['cny_over'], 1, 2);
            $balance['cny_lock'] = bcmul($balance['cny_lock'], 1, 2);
        }
        return $balance;
    }

	/**
	 * 返回当前所有可用的币名称
	 * @return mixed
	 */
	public function getAllCoin()
	{
		$coinMo = new CoinModel();
		return $coinMo->field('name')->fList();
	}


	/*
	 * 获取用户资金快照
	*/
	public function getCoinSnapshot($uid, $coin, $date)
	{
		if(!preg_match('/^\d{10}$/', $date))
		{
			$date = strtotime($date);
		}
		$data = Cache_Redis::instance()->hGet('USER_COIN_SNAPSHOT', $uid.'_'.$coin.'_'.$date);
		if(!$data)
		{
			$data = 0;

			$mo = 'Exchange_'.ucfirst($coin).'Model';
			$temp = $mo::getInstance()->field('sum(number) total, opt_type')->where(['uid'=>$uid, 'status'=>'成功', 'created'=>['<', $date]])->group('opt_type')->fList();
			$temp = array_column($temp, 'total', 'opt_type');
			$data = Tool_Math::sub((string)$temp['in'], (string)$temp['out']);
			//如果是交易币,要查该交易区下所有币的交易所得
			if(in_array($coin, Coin_PairModel::$tradingArea))
			{
				$coinList = User_CoinModel::getInstance()->field('name')->getList();
				foreach($coinList as $v)
				{
					if($v['name']==$coin)
					{
						continue;
					}
					$in = Order_CoinModel::getInstance()->designTable($v['name'])->field('sum(number * price - sale_fee) total')->where(['sale_uid'=>$uid, 'coin_from'=>$v['name'], 'coin_to'=>$coin, 'created'=>['<', $date]])->fRow();
					$out = Order_CoinModel::getInstance()->designTable($v['name'])->field('sum(number * price) total')->where(['buy_uid'=>$uid, 'coin_from'=>$v['name'], 'coin_to'=>$coin, 'created'=>['<', $date]])->fRow();
					$data = Tool_Math::add($data, Tool_Math::sub((string)$in['total'], (string)$out['total']));
				}
			}
			//单币，查自己就OK
			else
			{
				$in = Order_CoinModel::getInstance()->designTable($coin)->field('sum(number * price - sale_fee) total')->where(['sale_uid'=>$uid, 'created'=>['<', $date]])->fRow();
				$out = Order_CoinModel::getInstance()->designTable($coin)->field('sum(number * price) total')->where(['buy_uid'=>$uid, 'created'=>['<', $date]])->fRow();
				$data = Tool_Math::add($data, Tool_Math::sub((string)$in['total'], (string)$out['total']));
			}

			Cache_Redis::instance()->hSet('USER_COIN_SNAPSHOT', $uid.'_'.$coin.'_'.$date, $data);
		}
		return $data;
	}


	/**
	 * 转换币
	 */
	public function convertCoin($userData, $toCoin='btc')
	{
		$newPrice = Coin_PairModel::getInstance()->getCoinPrice();
        $convertCoin =Tool_Math::add($userData[ $toCoin.'_over'], $userData[$toCoin.'_lock']);
        foreach ($newPrice as $coin=> $area)
        {
            if($coin==$toCoin)
            {
                foreach($area as $k=>$v)
                {
                    $name=explode('_', $k);
                    $coinNum = Tool_Math::add($userData[$name[0] . '_over'], $userData[$name[0] . '_lock']);
                    $coinValue = Tool_Math::mul($coinNum, $v['price']);
                    $convertCoin = Tool_Math::add($convertCoin, $coinValue);
                }
            }
            elseif(isset($newPrice[$toCoin][$coin.'_'.$toCoin]))
            {
            	$toCoinPrice = $newPrice[$toCoin][$coin.'_'.$toCoin]['price'];
            	foreach($area as $k=>$v)
                {
                    $name=explode('_', $k);
                    $coinNum = Tool_Math::add($userData[$name[0] . '_over'], $userData[$name[0] . '_lock']);
                    $coinValue = Tool_Math::mul($coinNum, $v['price']);
                    $coinValue =  Tool_Math::mul($coinValue, $toCoinPrice);
                    $convertCoin = Tool_Math::add($convertCoin, $coinValue);
                }
            }

        }
        return $convertCoin;
	}

	//短信验证码错误次数
	public function finderror($key)
	{
		$redis = Cache_Redis::instance();
		$check = $redis->exists($key);
		if($check)
		{
			$redis->incr($key);
		}
		else
		{
			$redis->incr($key);
			$redis->expire($key,300);	//限制时间为5分钟
		}

    }
}
