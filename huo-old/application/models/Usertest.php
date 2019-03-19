<?php
class UserModel extends Orm_Base{
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
		'mo' => array('type' => "int(11) unsigned", 'comment' => '手机'),
		'role' => array('type' => "enum('admin','user', 'read')", 'comment' => '角色'),
		'prand' => array('typepe' => "varchar", 'comment' => '随机加密串'),
		'cny_over' => array('type' => "decimal(8,5) unsigned", 'comment' => '剩余人民币'),
		'cny_lock' => array('type' => "decimal(8,5) unsigned", 'comment' => '冻结人民币'),
		'goc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => '剩余富途币'),
		'goc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => '冻结富途币'),
		'btc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => '剩余比特币'),
		'btc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => '冻结比特币'),
		'ltc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => '剩余莱特币'),
		'ltc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => '冻结莱特币'),
		'lsk_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lsk_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'eth_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'eth_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'etc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'etc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'mtc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'mtc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'uc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'uc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lbc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lbc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'dsc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'dsc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'mac_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'mac_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lcc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'lcc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'tur_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'tur_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'ecf_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'ecf_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'osc_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'osc_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'gec_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'gec_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'hxi_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'hxi_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'tge_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'tge_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'dstb_over' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'dstb_lock' => array('type' => "decimal(7,5) unsigned", 'comment' => ''),
		'credit' => array('type' => "decimal(5,4) unsigned", 'comment' => '费率'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),
		'updateip' => array('type' => "char(15)", 'comment' => '修改ip'),
        'source'    => array('type'=>'int','comment'=>'来源')
	);
	public $pk = 'uid';


	
	static function _requestGet($url, $ssl=true) {
		// curl完成
		$curl = curl_init();

		//设置curl选项
		curl_setopt($curl, CURLOPT_URL, $url);//URL
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
                 Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
		//SSL相关
		if ($ssl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//禁用后cURL将终止从服务端进行验证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);//检查服务器SSL证书中是否存在一个公用名(common name)。
		}
		curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果

		// 发出请求
		$response = curl_exec($curl);
		if (false === $response) {
			echo '<br>', curl_error($curl), '<br>';
			return false;
		}
		return $response;
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
			$pRedis->hSet('useremail', $pUser['email'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
			$redis->hSet('userphone', $pUser['mo'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		} else {
			static $redis;
			$redis || $redis = &Cache_Redis::instance();
			$redis->hSet('useremail', $pUser['email'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
			$redis->hSet('userphone', $pUser['mo'], $pUser['pwd'].','.$pUser['uid'].','.$pUser['role'].','.$pUser['prand']);
		}
	}

    /**
     * 写入redis:邮件
     *
     * @param string $pData
     * @return bool
     */
    static function saveEmailRedis($pData){
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
    }
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
		foreach($tUser as $k=>&$v){
            if(strpos($k, '_over') || strpos($k, '_lock')){
                $v = floatval($v);
            }
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
		foreach($tUser as $k=>&$v){
            if(strpos($k, '_over') || strpos($k, '_lock')){
                $v = floatval($v);
            }
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
		foreach($tUser as $k=>&$v){
            if(strpos($k, '_over') || strpos($k, '_lock')){
                $v = floatval($v);
            }
        }
		return $tUser;
	}

	/**
	 * 保存 富途币
	 * @param $pUser 用户数组
	 * @param array $pData : rmb_lock, rmb_over, btc_lock, btc_over
	 * @return bool
	 */
	public function safeUpdate(&$pUser, $pData, $forced = false){
		if(!$pUser = $this->lock()->fRow($pUser['uid'])){
			return $this->setError('系统错误，请重新操作');
		}
		$showold = isset($pUser['showold']);
		# DB获取用户
		if($showold){
			echo "<div>操作前：RMB余额[{$pUser['cny_over']}], RMB冻结[{$pUser['cny_lock']}], GOC余额[{$pUser['goc_over']}], GOC冻结[{$pUser['goc_lock']}]</div>";
		}
        /*操作间隔*/
		if( !$forced && ($tTime = $_SERVER['REQUEST_TIME'] - $pUser['updated']) < ($tMinTime = Yaf_Registry::get("config")->opt_mintime)){
			return $this->setError('请等待'.($tMinTime-$tTime).'秒再进行操作');
		}
		# 必更新字段
		$tData = array('uid' => $pUser['uid'], 'updated' => time(), 'updateip' => Tool_Fnc::realip());
		# 重要数据更新
        foreach($pData as $k1 => $v1){
            $tData[$k1] = bcadd($pUser[$k1], $v1, 5);
            if(0 > $tData[$k1]){
                $tBName = explode('_', $k1);
                if(strpos($k1, '_lock') !== FALSE && $v1 < 0){
                    if(bcadd($tData[$k1], 0.1, 5) < 0){
                        return $this->setError('您的'.$tBName[0].'不足');
                    }elseif($tData[$k1] >= -0.01){
                        $tData[$k1] = 0;
                    }
                } else {
                    return $this->setError('您的'.$tBName[0].'不足');
                }
            }
		}
		# 更新数据库
		if(!$this->update($tData)){
			# 出现错误回滚
			return $this->setError('系统错误，请重新尝试操作');
		}
		# 合并用户数据
		$pUser = array_merge($pUser, $tData);
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
    public static function isModify($uid) {
        $userInfo = UserModel::getInstance()->field('name, idcard, pwdtrade, mo')->where('uid = '.$uid)->fRow();

        if ($userInfo['name'] == '' || $userInfo['idcard'] == '' || $userInfo['pwdtrade'] == '' || $userInfo['mo'] == '') {
            return false;
        }

        return true;
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

}
