<?php
# Mobile class
abstract class Ctrl_Mobile extends Yaf_Controller_Abstract
{

    # 3 session and role
    protected $_auth = 3;

    # current user
    public $mCurUser = array();

    # init
    public function init()
    {
        // error_log(json_encode($_COOKIE). "\n", 3, '/tmp/wallet.log');
    	$auth		= $this->_apiKey();
    	$this->signValidate($auth);

        # ip Limit
        # $this->limitIp();

        (1 & $this->_auth) && $this->_session();
        (1 < $this->_auth) && $this->_role();
    }

    # ip limit 1s 10
    private function limitIp()
    {
        # 判断是否在API黑名单
        $redis = Cache_Redis::instance('service');
        if (1 == $redis->hGet('limitip', USER_IP)) {
            return Tool_Response::show(10006);
        }

        # limit 限制
        $value = Cache_Redis::limit('ip'.USER_IP, 1, 10);

        if (!$value) {
            $redis->hSet('limitip', USER_IP, 1);
        }

    }

	# 登录
    protected function _session()
    {
		$this->domain = Tool_Url::getDomain();
        # 用户唯一标识
        if(!isset($_COOKIE['USER_UNI']) || empty($_COOKIE['USER_UNI'])){
            @setcookie('USER_UNI', $_COOKIE['USER_UNI'] = md5(uniqid()), $_SERVER['REQUEST_TIME'] + 86400*30, '/', $this->domain);
        }
        # 如果没有PHPSESSID，则程序给生成一个
        $user_agent = (!isset($_SERVER['HTTP_USER_AGENT']) && empty($_SERVER['HTTP_USER_AGENT'])) ? USER_IP : $_SERVER['HTTP_USER_AGENT'];
        $tSessId = md5($user_agent.'Btc.com'.$_COOKIE['USER_UNI']);
        if(empty($_COOKIE['PHPSESSID']) || $_COOKIE['PHPSESSID'] != $tSessId){
            @setcookie('PHPSESSID', $_COOKIE['PHPSESSID'] = $tSessId, $_SERVER['REQUEST_TIME'] + 86400*30, '/', $this->domain);
        }
        new Tool_Session('session', 2592000);
        # 当前登录用户
        if(!empty($_SESSION[$_COOKIE['PHPSESSID']])){
            $this->mCurUser = $_SESSION[$_COOKIE['PHPSESSID']];
            //正常处理
            $redis = Cache_Redis::instance();
            if(isset($_GET['g_session']) || $redis->hGet('msession', $this->mCurUser['uid'])){
                $this->mCurUser = $_SESSION[$_COOKIE['PHPSESSID']] = UserModel::getByPhone($this->mCurUser['phone']);
                $redis->hSet('msession', $this->mCurUser['uid'], 0);
            }
		}
	}

	# 验证码
	protected function valiCaptcha(){
        $captcha = $_COOKIE['PHPSESSID'].'captcha';
		if(!isset($_POST['captcha'], $_SESSION[$captcha]) || (strtolower($_SESSION[$captcha]) != strtolower($_POST['captcha']))){
			$this->assign('captchamsg', '验证码错误');
			return false;
		}
		return true;
	}


    /**
     * 角色验证
     * @param string $msg 提示消息
     */
    protected function _role()
    {
        if (empty($this->mCurUser) || (4 & $this->_auth && ('admin' != $this->mCurUser['role']))) {
            self::exitjson('2', '请先登录再执行操作');
        }
    }

	/**
	 * out json
	 */
	static function exitjson($code = '1', $msg = '非法请求', $data = array())
	{
		echo header('Content-Type:application/json;charset=utf-8');
        if (!empty($data)) {
        	exit(json_encode(array('code' => $code, 'msg' => $msg, 'data' => $data)));
        } else {
        	exit(json_encode(array('code' => $code, 'msg' => $msg)));
        }
	}

	/**
	 * get param
	 */
	protected function getParam($name)
	{
		if ($this->getRequest()->isPost()) {
			$name = $this->getRequest()->getPost($name, null);
		} else if ($this->getRequest()->isGet()) {
			$name = $this->getRequest()->getQuery($name, null);
		} else {
			$name = null;
		}
		$name = Tool_Str::safestr($name);

		return $name;
	}

    protected function getParams()
    {
        if ($this->getRequest()->isPost()) {
            $data = $_POST;
        } else if ($this->getRequest()->isGet()) {
            $data = $_GET;
        }
        foreach ($data as $key => &$value) {
            $data[$key] = Tool_Str::safestr($value);
        }

        return $data;
    }

    /**
     * api key
     */
	protected function _apiKey()
	{
		/*if (!$this->getRequest()->isPost() || $this->getParam('access_key')) {
			self::exitjson();
		}*/

		$maMo = new Mobile_AuthModel();

		if (!$auth_info = $maMo->getAuthInfo($this->getParam('access_key'))) {
			$this->exitjson(1001, '公钥不合法');
		}

		return $auth_info;
	}

    /**
     * sign validate
     */
    protected function signValidate($auth)
    {
		$sign = $this->getParam('sign');
        $data = $this->getParams();
		unset($data['sign']);
        $data['method'] = $this->getRequest()->action;
		$data['secret_key'] = $auth['secret_key'];
		ksort($data);
		$params = urldecode(http_build_query($data));
		// error_log(urldecode($params)."\n", 3, '/tmp/wallet.log');
		$signature = md5($params);

        // echo $signature;exit;

		if ($sign != $signature) {
			$this->exitjson(1002, '签名校验失败');
		}

		return true;
    }

    /**
     * 浏览器友好的变量输出
     * @param data output
     */
    public function dump($data)
    {
        Tool_Fnc::dump($data);
        exit();
    }

}
