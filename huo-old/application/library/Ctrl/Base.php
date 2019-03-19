 <?php
/**
 * 基础类
 */
abstract class Ctrl_Base extends Yaf_Controller_Abstract{
	/**
	 * 开启 SESSION : 1
	 * 必须登录 : 2
	 * session登陆：3
	 * 必须管理员 : 4
	 */
	protected $_auth = 0;

	/**
	 * 当前登录用户
	 * @var array
	 */
	public $mCurUser = array();

	/**
	 * 构造函数
	 */
	public function init(){
		//过滤post, get
		$this->filterParam();

		//选择语言
	    $this->selectLang();
		(1 & $this->_auth) && $this->_session();
		(1 < $this->_auth) && $this->_role();
		#language choice,set view folder
		$this->setViewPath(PATH_TPL);
		//活动控制开关
		//注册有礼

		// $activeId = Orm_Base::getInstance()->query("select * from activity where name='赠送dob'");
		// if ($activeId[0]['status'] == 1 && $_SERVER['REQUEST_TIME'] > $activeId[0]['start_time'] && $_SERVER['REQUEST_TIME'] < $activeId[0]['end_time'])
		// {
		// 	$activeButton=1;
		// }
		// else{
		// 	$activeButton = 0;
		// }

		// $this->layout('activeButton', $activeButton);
		if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"]))
		{
			$areamo=new PhoneAreaCodeModel();
			$lang=LANG;
			if($lang!='cn'){
				$lang='en';
			}
			$areadata=$areamo->where(array('langue'=> $lang))->fList();//區號

			$this->layout('areadata', $areadata);

			//from uid
	        if(isset($_GET['regfrom']))
	        {
	        	setcookie('regfrom', $_GET['regfrom'], time()+864000, '/');
	        }

	        if(!$_COOKIE['WSTK'])
	        {
	        	setcookie('WSTK', md5(uniqid('WSTK')), 0, '/');
	        }

	        if($this->mCurUser)
	        {
	        	$this->layout('accountInfo', $this->mCurUser);
	        }
		}


		//强制重置密码
		if(!in_array(strtolower(REDIRECT_URL), ['/password_upgrade.html', '/ajax_user/resetpwds', '/ajax_user/sms', '/ajax_user/getcommonrsakey', '/user/logout','/emailverify/findpwd'])
			&& !preg_match('#^/index/captcha\?.+#i', REDIRECT_URL)
			&& $this->mCurUser['uid']
			&& $this->mCurUser['created']< 1532620800
			&& !Cache_Redis::instance()->hget('RESET_PWDS_DONE', $this->mCurUser['uid'])
		)
		{
			header('location:/password_upgrade.html');die;
		}



        //禁止被嵌入ifame
        header('X-Frame-Options:SAMEORIGIN');
        //只能通过HTTPS访问当前资源
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
		//清除页面缓存
		$clear_cache = '5.66';
		$this->layout('clear_cache', $clear_cache);
		$this->redefineView();

		$this->layout('controller', strtolower($this->_request->controller));
        $this->layout('action', strtolower($this->_request->action));
        $this->layout('version', 'v0.0.14');
	}

	/**
	 * 重新定义视图
	 */
	private function redefineView()
	{
        // if(Tool_Fnc::isMobile()) {
        //   // show(1);
        //   $this->display(PATH_TPL.'/index');die;
        //   // header('location:/');die;
        // }

        //手机访问
        if(Tool_Fnc::isMobile() && (!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])!="xmlhttprequest"))
        {
        	//exit($this->render($this->getViewPath().'/mobileEndDevice'));
        	//微信瀏覽器
        	$isWechatBrowser = stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false;
        	if($isWechatBrowser && stripos(REDIRECT_URL, 'wechat')===false)
        	{
        		header('location:/index.phtml');die;
        	}
        	elseif(!$isWechatBrowser && stripos(REDIRECT_URL, 'wechat.htm')!==false)
        	{
        		header('location:/');die;
        	}

        	// if(stripos(REDIRECT_URL, 'alert=register')!==false)
        	// {
        	// 	header('location:/register');die;
        	// }
        	// elseif(stripos(REDIRECT_URL, '?login')!==false)
        	// {
        	// 	header('location:/login');die;
        	// }

        }


        preg_match('/MSIE (\d+?\.\d)/i', $_SERVER['HTTP_USER_AGENT'], $ieVersion);
        //当前非browser_upgrade页面
        if(stripos(REDIRECT_URL, 'browser_upgrade')===false)
        {
        	//低版本浏览器(非蜘蛛)访问
            if($ieVersion && $ieVersion[1]<11 && stripos($_SERVER['HTTP_USER_AGENT'], 'spider') === false)
            {
                header('location:/browser_upgrade.htm');die;
            }
        }
        //高版本浏览器自动从browser_upgrade页跳到首页
        elseif(!$ieVersion || $ieVersion[1]>=11)
        {
            header('location:/');die;
        }
	}


	private function filterParam()
	{
		$data[] = &$_POST;
		$data[] = &$_GET;
		foreach ($data as &$v)
		{
			if(is_array($v))
			{
				$this->loopFilter($v);
			}	
		}
	}

	private function loopFilter(&$arr)
	{
		foreach ($arr as &$vv)
		{
			if(!is_string($vv))
			{
				$this->loopFilter($vv);
				continue;
			}
			$vv = addslashes($vv);
		}
		unset($vv);
	}

	/**
	 * 以某EMAIL身份登录
	 * @param bool $ip
	 * @param string $email
	 */
	private function _login_by_email($ip=false, $email=''){
		if(!$ip || !$email) return;
		if($ip == USER_IP){
			$_SESSION['user'] = array('email'=>$email);
			$_GET['yafphp_session'] = 1;
		}
	}

	/**
	 * 需要登录
	 */
	protected function _session(){
        $domain = Tool_Url::getDomain();
        $secure = $_SERVER['REQUEST_SCHEME']=='https';
		# 用户唯一标识
        if (empty($_COOKIE['USER_UNI']))
        {
            @setcookie('USER_UNI', $_COOKIE['USER_UNI'] = md5(uniqid()), 0, '/',$domain, $secure, true);
        }
        # 如果没有PHPSESSID，则程序给生成一个
        @$tSessId = md5($_SERVER['HTTP_USER_AGENT'] .$_COOKIE['USER_UNI']);
        if (empty($_COOKIE[SESSION_NAME]) || $_COOKIE[SESSION_NAME] != $tSessId)
        {
            @setcookie(SESSION_NAME, $_COOKIE[SESSION_NAME] = $tSessId, 0, '/',$domain, $secure, true);
        }

		new Tool_Session();
		$sessionId = session_id();

		# 当前登录用户
		if(!empty($_SESSION['user'])){
			# 正常用户处理
			$this->mCurUser = &$_SESSION['user'];
			$tRedis = Cache_Redis::instance();
			$client = $tRedis->hGet('usersession', $this->mCurUser['uid']);
			$client and $clientArr = (array)json_decode($client, true);
			if(isset($_GET['yafphp_session']) || $client==1 || (isset($clientArr[$sessionId]['status']) && $clientArr[$sessionId]['status']==1)){

            	// 判读是手机登录还邮箱登陆
				if($this->mCurUser['registertype']=='2'|| $this->mCurUser['mo'])
				{
					$this->mCurUser = $_SESSION['user'] = UserModel::getByPhone($this->mCurUser['mo'], false, $this->mCurUser['area']);
				}
				else
				{
					$this->mCurUser = $_SESSION['user'] = UserModel::getByEmail($this->mCurUser['email']);
				}
				if($realInfo = AutonymModel::getInstance()->field('name,cardtype,idcard')->where(['status'=>2, 'uid'=>$this->mCurUser['uid']])->fRow())
	            {
	            	$this->mCurUser['name'] = $realInfo['name'];
	            	$this->mCurUser['realInfo'] = $realInfo;
	            }
	            else
	            {
	            	unset($this->mCurUser['realInfo']);
	            }

	            $clientArr[$sessionId] = array('time'=>time(), 'status'=>0);
	            $tRedis->hSet('usersession', $this->mCurUser['uid'], json_encode($clientArr));

			}

            $this->layout('user', $this->mCurUser);

            if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])!="xmlhttprequest")
            {
				$this->setReqToken();
            }
        }

	}

	/**
	 * ajax 验证登录
	 */
	protected function _ajax_islogin(){
		$this->_session();
		empty($this->mCurUser) && $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, array('need_login'=>1));
	}

	/**
	 * 角色验证
	 * @param string $msg 提示消息
	 */
	protected function _role($msg = ''){
		if(empty($this->mCurUser) || (4 & $this->_auth && (('admin' != $this->mCurUser['role']) && ('read' != $this->mCurUser['role'])))){
			setcookie('reurl', $_SERVER['REQUEST_URI']);
			$this->showMsg($msg?:$GLOBALS['MSG']['NEED_LOGIN'], '/?login');
		}
	}

	/**
	 * 注册变量到模板
	 * @param str|array $pKey
	 * @param mixed $pVal
	 */
	protected function assign($pKey, $pVal = ''){
		if(is_array($pKey)){
			$this->_view->assign($pKey);
			return $pKey;
		}
		$this->_view->assign($pKey, $pVal);
		return $pVal;
	}

	/**
	 * 注册变量到布局
	 * @param str $k
	 * @param mixed $v
	 */
	protected function layout($k, $v){
		static $layout;
		$layout || $layout = Yaf_Registry::get('layout');
		@$layout->$k = $v;
		$this->assign($k, $v);

		$var = $this->_view->get();
		if(!isset($var['layout']))
		{
			$var['layout'] = array();
		}
		$var['layout'][$k] = $v;
		$this->assign('layout', $var['layout']);
	}

	/**
	 * SEO设置
	 *
	 * @param str $pTitle
	 * @param str $pKW
	 * @param str $pDes
	 */
	protected function seo($pTitle = '', $pKW = '', $pDes = '', $pBodyCss = ''){
		foreach(array('seot' => $pTitle, 'seok' => $pKW, 'seod' => $pDes, 'bodycss' => $pBodyCss) as $k=>$v)
		{
			$this->layout($k, $v);
		}
	}

	/**
	 * 提示信息
	 */
	protected function showMsg($pMsg, $pUrl = false){
		Tool_Fnc::showMsg($pMsg, $pUrl);
	}

	/**
	 * 退出消息
	 */
	protected function exitMsg($pMsg){
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />', $pMsg;
		exit;
	}

	/**
	 * AJAX返回
	 */
	protected function ajax($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		Tool_Fnc::ajaxMsg($pMsg, $pStatus, $pData, $pType);
	}

	/**
	 * 选择语言包
	 */
	protected function selectLang()
	{
		$lFile = CONF_PATH . 'language/view/'. LANG . '.json';
		if(!file_exists($lFile))
		{
			$lFile = CONF_PATH . 'language/view/en.json';
		}

		if(file_exists($lFile))
		{
			$langpg = json_decode(file_get_contents($lFile), true);
			if(!$langpg)
			{
				throw new Exception('cannot decode '.$lFile );
			}

			$langMap = array();
			$l1      = strtolower($this->_request->controller);
	        $l2      = strtolower($this->_request->action);
	        $curPage = isset($langpg[$l1][$l2]) ? $langpg[$l1][$l2] : array();

			if(isset($langpg['base']))
			{
				$curPage = array_merge($langpg['base'], $curPage);
			}

			$langMap = $curPage;
		}

		$this->layout('lang', $langMap);

    // 国家列表
    $countries = CONF_PATH . 'language/view/country.json';
    if(file_exists($countries)) {
      $countryJson = json_decode(file_get_contents($countries), true);
    }

    $this->layout('country', $countryJson);
	}

	/**
	 * 特殊页面
	 */
	protected function page($code)
    {
        switch ($code)
        {
            case 404:
                header("status: 404 Not Found");
                break;
            case 403:
                header("status: 403 Forbidden");
                break;
        }
        $this->display('../error/error' . $code);
        exit;
    }


    /**
	 * 设置请求token，防范CSRF
	 */
	protected function setReqToken()
	{
		$key = 'reqtk';
		if(!$reqToken = $_COOKIE[$key])
		{
			$reqToken = $_COOKIE['reqToken'] = substr(md5(uniqid().'ReqToken'), 18);
			@setcookie($key, $_COOKIE[$key] = $reqToken, 0, '/');
		}
		$this->layout('reqToken', $reqToken);
	}


    /**
	 * 校验请求token，防范CSRF
	 */
	protected function checkReqToken($token='')
	{
		$token or $token = $_POST['reqToken']?:$_GET['reqToken'];
		if($token)
		{
			$key = 'reqtk';
			if(!isset($_COOKIE[$key]) || $_COOKIE[$key]!=$token)
			{
				if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])!="xmlhttprequest")
				{
					die('<script>alert("'.$GLOBALS['MSG']['PAGE_EXPIRED'].'(security by www.dobitrade.com)")</script>');
				}
				else
				{
					$this->ajax($GLOBALS['MSG']['PAGE_EXPIRED']);
				}

			}
		}
		else
		{
			throw new Exception("token cannot be empty");
		}
	}


	/**
	 * rsa解密
	 */
	protected function rsaDecode($key='')
	{
		$path = $key?:CONF_PATH.'commonRsaPrivate.key';
    	is_file($path) and $privateKey = file_get_contents($path);
		$data = $_POST['data']?:$_GET['data'];
		if(($isArray = is_array($data)) || strpos($data, ','))
		{
			if(!$isArray)
			{
				$data = explode(',', $data);
			}

			$decrypted = '';
			foreach($data as $part)
			{
				openssl_private_decrypt(base64_decode($part), $decryptedPart, $privateKey);
				$decrypted .= $decryptedPart;
			}
		}
		elseif($data)
		{
			openssl_private_decrypt(base64_decode($data), $decrypted, $privateKey);
		}
		else
		{
			return null;
		}

		if($decrypted)
		{
			//url decode
			if(isset($_POST['ud'])&&$_POST['ud']==true)
			{
				$decrypted = urldecode($decrypted);
			}
			$decrypted = json_decode($decrypted, true);
		}
		else
		{
			return false;
		}

		return (array)$decrypted;
	}


}
