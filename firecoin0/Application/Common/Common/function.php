<?php

if (!function_exists('array_column')) {
	function array_column(array $input, $columnKey, $indexKey = NULL)
	{
		$result = array();

		if (NULL === $indexKey) {
			if (NULL === $columnKey) {
				$result = array_values($input);
			}
			else {
				foreach ($input as $row) {
					$result[] = $row[$columnKey];
				}
			}
		}
		else if (NULL === $columnKey) {
			foreach ($input as $row) {
				$result[$row[$indexKey]] = $row;
			}
		}
		else {
			foreach ($input as $row) {
				$result[$row[$indexKey]] = $row[$columnKey];
			}
		}

		return $result;
	}
}

function getUrl($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, '');
	$data = curl_exec($ch);
	return $data;
}

function huafei($moble = NULL, $num = NULL, $orderid = NULL)
{
	if (empty($moble)) {
		return NULL;
	}

	if (empty($num)) {
		return NULL;
	}

	if (empty($orderid)) {
		return NULL;
	}

	header('Content-type:text/html;charset=utf-8');
	$appkey = C('huafei_appkey');
	$openid = C('huafei_openid');
	$recharge = new \Common\Ext\Recharge($appkey, $openid);
	$telRechargeRes = $recharge->telcz($moble, $num, $orderid);

	if ($telRechargeRes['error_code'] == '0') {
		return 1;
	} else {
		return NULL;
	}
}

function mlog($text)
{
	$text = addtime(time()) . ' ' . $text . "\n";
	file_put_contents(APP_PATH . '/../move.log', $text, FILE_APPEND);
}

function authUrl($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, '');
	$data = curl_exec($ch);
	return $data;
}

function userid($username = NULL, $type = 'username')
{
	if ($username && $type) {
		$userid = S('userid' . $username . $type);

		if (!$userid) {
			$userid = M('User')->where(array($type => $username))->getField('id');
			S('userid' . $username . $type, $userid);
		}
	} else {
		$userid = session('userId');
	}

	return $userid ? $userid : NULL;
}

function username($id = NULL, $type = 'id')
{
	if ($id && $type) {
		$username = S('username' . $id . $type);

		if (!$username) {
			$username = M('User')->where(array($type => $id))->getField('username');
			S('username' . $id . $type, $username);
		}
	} else {
		$username = session('userName');
	}

	return $username ? $username : NULL;
}

function check_dirfile()
{
	die();
	define('INSTALL_APP_PATH', realpath('./') . '/');
	$items = array(
		array('dir', '可写', 'ok', './Database'),
		array('dir', '可写', 'ok', './Database/Backup'),
		array('dir', '可写', 'ok', './Database/Cloud'),
		array('dir', '可写', 'ok', './Database/Temp'),
		array('dir', '可写', 'ok', './Database/Update'),
		array('dir', '可写', 'ok', './Runtime'),
		array('dir', '可写', 'ok', './Runtime/Logs'),
		array('dir', '可写', 'ok', './Runtime/Cache'),
		array('dir', '可写', 'ok', './Runtime/Temp'),
		array('dir', '可写', 'ok', './Runtime/Data'),
		array('dir', '可写', 'ok', './Upload/ad'),
		array('dir', '可写', 'ok', './Upload/ad'),
		array('dir', '可写', 'ok', './Upload/bank'),
		array('dir', '可写', 'ok', './Upload/coin'),
		array('dir', '可写', 'ok', './Upload/face'),
		array('dir', '可写', 'ok', './Upload/footer'),
		array('dir', '可写', 'ok', './Upload/game'),
		array('dir', '可写', 'ok', './Upload/link'),
		array('dir', '可写', 'ok', './Upload/public'),
		);

	foreach ($items as &$val) {
		if ('dir' == $val[0]) {
			if (!is_writable(INSTALL_APP_PATH . $val[3])) {
				if (is_dir($items[1])) {
					$val[1] = '可读';
					$val[2] = 'remove';
					session('error', true);
				}
				else {
					$val[1] = '不存在或者不可写';
					$val[2] = 'remove';
					session('error', true);
				}
			}
		}
		else if (file_exists(INSTALL_APP_PATH . $val[3])) {
			if (!is_writable(INSTALL_APP_PATH . $val[3])) {
				$val[1] = '文件存在但不可写';
				$val[2] = 'remove';
				session('error', true);
			}
		}
		else if (!is_writable(dirname(INSTALL_APP_PATH . $val[3]))) {
			$val[1] = '不存在或者不可写';
			$val[2] = 'remove';
			session('error', true);
		}
	}

	return $items;
}

function op_t($text, $addslanshes = false)
{
	$text = nl2br($text);
	$text = real_strip_tags($text);

	if ($addslanshes) {
		$text = addslashes($text);
	}

	$text = trim($text);
	return $text;
}

function text($text, $addslanshes = false)
{
	return op_t($text, $addslanshes);
}

function html($text)
{
	return op_h($text);
}

function op_h($text, $type = 'html')
{
	$text_tags = '';
	$link_tags = '<a>';
	$image_tags = '<img>';
	$font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
	$base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
	$form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
	$html_tags = $base_tags . '<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
	$all_tags = $form_tags . $html_tags . '<!DOCTYPE><meta><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
	$text = real_strip_tags($text, $$type . '_tags');

	if ($type != 'all') {
		while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background[^-]|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
			$text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
		}

		while (preg_match('/(<[^><]+)(window\\.|javascript:|js:|about:|file:|document\\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
			$text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
		}
	}

	return $text;
}

function real_strip_tags($str, $allowable_tags = '')
{
	return strip_tags($str, $allowable_tags);
}

function clean_cache($dirname = './Runtime/')
{
	$dirs = array($dirname);

	foreach ($dirs as $value) {
		rmdirr($value);
	}

	@(mkdir($dirname, 511, true));
}

function getSubByKey($pArray, $pKey = '', $pCondition = '')
{
	$result = array();

	if (is_array($pArray)) {
		foreach ($pArray as $temp_array) {
			if (is_object($temp_array)) {
				$temp_array = (array) $temp_array;
			}

			if ((('' != $pCondition) && ($temp_array[$pCondition[0]] == $pCondition[1])) || ('' == $pCondition)) {
				$result[] = ('' == $pKey ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : '');
			}
		}

		return $result;
	} else {
		return false;
	}
}

function debug($value, $type = 'DEBUG', $verbose = false, $encoding = 'UTF-8')
{
	if (APP_DEBUG || 1) {
		if (!IS_CLI) {
			Common\Ext\FirePHP::getInstance(true)->log($value, $type);
		}
	}
}

function CoinClient($username, $password, $ip, $port, $timeout = 3, $headers = array(), $suppress_errors = false)
{
	return new \Common\Ext\CoinClient($username, $password, $ip, $port, $timeout, $headers, $suppress_errors);
}

function EthClient($ip, $port)
{
    return new \Common\Ext\EthClient($ip, $port);
}

function EosClient($ip, $port)
{
    return new \Common\Ext\EosClient($ip, $port);
}

function createQRcode($save_path, $qr_data = 'PHP QR Code :)', $qr_level = 'L', $qr_size = 4, $save_prefix = 'qrcode')
{
	if (!isset($save_path)) {
		return '';
	}

	$PNG_TEMP_DIR = &$save_path;
	vendor('PHPQRcode.class#phpqrcode');

	if (!file_exists($PNG_TEMP_DIR)) {
		mkdir($PNG_TEMP_DIR);
	}

	$filename = $PNG_TEMP_DIR . 'test.png';
	$errorCorrectionLevel = 'L';

	if (isset($qr_level) && in_array($qr_level, array('L', 'M', 'Q', 'H'))) {
		$errorCorrectionLevel = &$qr_level;
	}

	$matrixPointSize = 4;

	if (isset($qr_size)) {
		$matrixPointSize = &min(max((int) $qr_size, 1), 10);
	}

	if (isset($qr_data)) {
		if (trim($qr_data) == '') {
			exit('data cannot be empty!');
		}

		$filename = $PNG_TEMP_DIR . $save_prefix . md5($qr_data . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
		QRcode::png($qr_data, $filename, $errorCorrectionLevel, $matrixPointSize, 2, true);
	} else {
		QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2, true);
	}

	if (file_exists($PNG_TEMP_DIR . basename($filename))) {
		return basename($filename);
	} else {
		return false;
	}
}

function NumToStr($num)
{
	if (!$num) {
		return $num;
	}

	if ($num == 0) {
		return 0;
	}

	$num = round($num, 8);
	$min = 0.0001;

	if ($num <= $min) {
		$times = 0;

		while ($num <= $min) {
			$num *= 10;
			$times++;

			if (10 < $times) {
				break;
			}
		}

		$arr = explode('.', $num);
		$arr[1] = str_repeat('0', $times) . $arr[1];
		return $arr[0] . '.' . $arr[1] . '';
	}

	return ($num * 1) . '';
}

function Num($num)
{
	if (!$num) {
		return $num;
	}

	if ($num == 0) {
		return 0;
	}

	$num = round($num, 8);
	$min = 0.0001;

	if ($num <= $min) {
		$times = 0;

		while ($num <= $min) {
			$num *= 10;
			$times++;

			if (10 < $times) {
				break;
			}
		}

		$arr = explode('.', $num);
		$arr[1] = str_repeat('0', $times) . $arr[1];
		return $arr[0] . '.' . $arr[1] . '';
	}

	return ($num * 1) . '';
}

function check_verify($code,$id = 1)
{
	$verify = new \Think\Verify();

	return $verify->check($code,$id);
}

function get_city_ip($ip = NULL)
{
	if (empty($ip)) {
		$ip = get_client_ip();
	}

	$Ip = new Org\Net\IpLocation();
	$area = $Ip->getlocation($ip);
	$str = $area['country'] . $area['area'];
	$str = mb_convert_encoding($str, 'UTF-8', 'GBK');

	if (($ip == '127.0.0.1') || ($str == false) || ($str == 'IANA保留地址用于本地回送')) {
		$str = '未分配或者内网IP';
	}

	return $str;
}

function send_post($url, $post_data)
{
	$postdata = http_build_query($post_data);
	$options = array(
		'http' => array('method' => 'POST', 'header' => 'Content-type:application/x-www-form-urlencoded', 'content' => $postdata, 'timeout' => 15 * 60)
		);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	return $result;
}

function request_by_curl($remote_server, $post_string)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $remote_server);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'mypost=' . $post_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, '');
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function tradeno($type='')
{
    if ($type == 'c2c'){
		$length = 5;
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$string = '';
		for ( $i = 0; $i < $length; $i++ )
		{
			// 取字符数组 $chars 的任意元素
			$string .= $chars[ mt_rand(0, strlen($chars) - 1) ];
		}
		return	$string;
	//return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 2) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 3);
    }else{
        return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 2) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 3);
    }
}

function tradenoa()
{
	return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 6);
}

function tradenob()
{
	return substr(str_shuffle(str_repeat('123456789', 4)), 0, 2);
}

function get_user($id, $type = NULL, $field = 'id')
{
	$key = md5('get_user' . $id . $type . $field);
	$data = S($key);

	if (!$data) {
		$data = M('User')->where(array($field => $id))->find();
		S($key, $data);
	}

	if ($type) {
		$rs = $data[$type];
	} else {
		$rs = $data;
	}

	return $rs;
}

function ismobile()
{
	if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
		return true;
	}

	if (isset($_SERVER['HTTP_CLIENT']) && ('PhoneClient' == $_SERVER['HTTP_CLIENT'])) {
		return true;
	}

	if (isset($_SERVER['HTTP_VIA'])) {
		return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
	}

	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');

		if (preg_match('/(' . implode('|', $clientkeywords) . ')/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}

	if (isset($_SERVER['HTTP_ACCEPT'])) {
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && ((strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}

	return false;
}

//美联 短信接口
function send_moble($moble = '', $title = '', $message = '')
{
    if(MOBILE_CODE == 0){
        return 1;
        die();
    }
    $time=$_SESSION['time'];
    if (time()-$time<60 && !empty($time)){
        return 0;
    }
    $_SESSION['time'] = time();

    require_once COMMON_PATH . 'Ext/SmsMeilian.class.php';

    $username='xzgr';  //用户名
    $password_md5='48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
    $apikey='b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
    $contentUrlEncode = urlencode($message);//执行URLencode编码  ，$content = urldecode($content);解码

    $smsMeilian = new SmsMeilian();
    $result = $smsMeilian->sendSMS($username, $password_md5, $apikey, $moble, $contentUrlEncode,'UTF-8');  //进行发送

    if(strpos($result,"success")>-1) {
        return 1;
    } else {
        return 0;
    }
}

//梦网短信接口
function send_moble_mw($moble = '', $title = '', $message = '')
{
    if(MOBILE_CODE == 0){
        return 1;
        die();
    }
	$time=$_SESSION['time'];
	if (time()-$time<60 && !empty($time)){
		return 0;
	}
	$_SESSION['time'] = time();

    require_once COMMON_PATH . 'Ext/SmsSendConn.class.php';
    //南方短信节点url地址
    $url = 'http://api01.monyun.cn:7901/sms/v2/std/';
    //北方短信节点url地址
    //$url = 'http://api02.monyun.cn:7901/sms/v2/std/';
    $smsSendConn = new SmsSendConn($url);
    $data=[];
    //设置账号(必填)
    $data['userid'] = 'E100SC';
    //设置密码（必填.填写明文密码,如:1234567890）
    $data['pwd'] = 'bXwWaF';

    // 设置手机号码 此处只能设置一个手机号码(必填)
    $data['mobile'] = $moble;
    //设置发送短信内容(必填)
    $data['content']=$message;
    // 业务类型(可选)
    $data['svrtype']='';
    // 设置扩展号(可选)
    $data['exno']='';
    //用户自定义流水编号(可选)
    $data['custid']='';
    // 自定义扩展数据(可选)
    $data['exdata']='';

    $result = $smsSendConn->singleSend($data);
    return $result['result'] === 0 ? 1 : 0;
}

//网易 短信接口
function send_moble_wy($moble, $title = '', $message = '') {
    if(MOBILE_CODE == 0){
        return 1;
        die();
    }
    require_once COMMON_PATH . 'Ext/SmsWyapi.class.php';
    $p = new ServerAPI('0a8a2c3596400478fc40e85bf9a506d3', '88e93ca33530', 'fsockopen');     //fsockopen伪造请求
    $res = $p->sendSmsCode(3083164, $moble,'','6');
    return $res ? 1 : 0;
}

function send_mail($moble, $subject = '', $message = '', $attachment = null,$config=""){
    exit("Don't Send Mails.");
    if($config==''){
        $arr = [
            '1' => [
                'user' => 'btchkgj1@btcd1.com',
                'pass' => 'Haizhongmingzhu2017..',
                'server' => 'imap.exmail.qq.com',
                'port' => '465',
                'personal' => 'bctd1',
                'SMTP_SSL' => true,
            ],
            '2' => [
                'user' => 'btchkgj2@btcd1.com',
                'pass' => 'Haizhongmingzhu2017..',
                'server' => 'imap.exmail.qq.com',
                'port' => '465',
                'personal' => 'bctd1',
                'SMTP_SSL' => true,
            ]
        ];
        $i = rand(1,2);
        $config = $arr[$i];
    }

    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
    $mail             = new PHPMailer(); //PHPMailer对象
    $mail->CharSet    = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug  = 1;                     // 关闭SMTP调试功能	                               // 2 = messages only
    $mail->SMTPAuth   = true;                  // 启用 SMTP 验证功能

    if($config['SMTP_SSL']){
        $mail->SMTPSecure = 'ssl';                 // 使用安全协议
    }

    $mail->Host       = $config['server'];   // SMTP 服务器
    $mail->Port       = $config['port'];   // SMTP服务器的端口号
    $mail->Username   = $config['user'];  // SMTP服务器用户名
    $mail->Password   = $config['pass'];  // SMTP服务器密码
    $mail->SetFrom($mail->Username, '');

    // $replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
    // $replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
    //  $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject    = $title;
    $mail->MsgHTML($message);

    //发送邮件
    if(is_array($moble)){
        //同时发送给多个人
        foreach($moble as $key=>$value){
            $mail->AddAddress($value,"");  // 收件人邮箱和姓名
        }
    }else{	//只发送给一个人
        $mail->AddAddress($moble, "");  // 收件人邮箱和姓名
    }

    if(is_array($attachment)){ // 添加附件
        foreach ($attachment as $file){
            is_file($file) && $mail->AddAttachment($file);
        }
    }else{
        is_file($attachment) && $mail->AddAttachment($attachment);
    }
    return $mail->Send() ? 1 : 0;
}

function addtime($time = NULL, $type = NULL)
{
	if (empty($time)) {
		return '---';
	}

	if (($time < 2545545) && (1893430861 < $time)) {
		return '---';
	}

	if (empty($type)) {
		$type = 'Y-m-d H:i:s';
	}

	return date($type, $time);
}

function check($data, $rule = NULL, $ext = NULL)
{
	$data = trim(str_replace(PHP_EOL, '', $data));

	if (empty($data)) {
		return false;
	}

	$validate['require'] = '/.+/';
	$validate['url'] = '/^http(s?):\\/\\/(?:[A-za-z0-9-]+\\.)+[A-za-z]{2,4}(?:[\\/\\?#][\\/=\\?%\\-&~`@[\\]\':+!\\.#\\w]*)?$/';
	$validate['currency'] = '/^\\d+(\\.\\d+)?$/';
	$validate['number'] = '/^\\d+$/';
	$validate['zip'] = '/^\\d{6}$/';
	$validate['cny'] = '/^(([1-9]{1}\\d*)|([0]{1}))(\\.(\\d){1,2})?$/';
	$validate['integer'] = '/^[\\+]?\\d+$/';
//	$validate['double'] = '/^[\\+]?\\d+(\\.\\d+)?$/';
    $validate['double'] = '/^[\d+$]|^[(\d+\.\d+)]$/';
	$validate['english'] = '/^[A-Za-z]+$/';
	$validate['idcard'] = '/^([0-9]{15}|[0-9]{17}[0-9a-zA-Z])$/';
	//$validate['truename'] = '/^[\\x{4e00}-\\x{9fa5}]{2,4}$/u';
    $validate['truename'] ='/^([\sA-Za-z]{5,20}||[\\x{4e00}-\\x{9fa5}]{2,8})$/u';//'/^[A-Za-z\\x{4e00}-\\x{9fa5}]{2,10}$/'
	$validate['username'] = '/^[a-zA-Z]{1}[0-9a-zA-Z_]{5,15}$/';
	$validate['email'] = '/^\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*$/';
	$validate['moble'] = '/^(((1[0-9][0-9]{1})|159|153|00852)+\\d{8})$/';
	$validate['moble2'] = '/^(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|0[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|1[987654321]|3[9643210]|2[70]|7|1)\d{1,14}$/';
	$validate['password'] = '/^[a-zA-Z0-9_\\@\\#\\$\\%\\^\\&\\*\\(\\)\\!\\,\\.\\?\\-\\+\\|\\=]{6,16}$/';
	$validate['xnb'] = '/^[a-zA-Z]$/';

	if (isset($validate[strtolower($rule)])) {
		$rule = $validate[strtolower($rule)];
		return preg_match($rule, $data);
	}

	$Ap = '\\x{4e00}-\\x{9fff}' . '0-9a-zA-Z\\@\\#\\$\\%\\^\\&\\*\\(\\)\\!\\,\\.\\?\\-\\+\\|\\=';
	$Cp = '\\x{4e00}-\\x{9fff}';
	$Dp = '0-9';
	$Wp = 'a-zA-Z\\-.';
	$Np = 'a-z';
	$Tp = '@#$%^&*()-+=';
	$_p = '_';
	$pattern = '/^[';
	$OArr = str_split(strtolower($rule));
	in_array('a', $OArr) && ($pattern .= $Ap);
	in_array('c', $OArr) && ($pattern .= $Cp);
	in_array('d', $OArr) && ($pattern .= $Dp);
	in_array('w', $OArr) && ($pattern .= $Wp);
	in_array('n', $OArr) && ($pattern .= $Np);
	in_array('t', $OArr) && ($pattern .= $Tp);
	in_array('_', $OArr) && ($pattern .= $_p);
	isset($ext) && ($pattern .= $ext);
	$pattern .= ']+$/u';
	return preg_match($pattern, $data);
}

function check_arr($rs)
{
	foreach ($rs as $v) {
		if (!$v) {
			return false;
		}
	}

	return true;
}

function maxArrayKey($arr, $key)
{
	$a = 0;

	foreach ($arr as $k => $v) {
		$a = max($v[$key], $a);
	}

	return $a;
}

function arr2str($arr, $sep = ',')
{
	return implode($sep, $arr);
}

function str2arr($str, $sep = ',')
{
	return explode($sep, $str);
}

function url($link = '', $param = '', $default = '')
{
	return $default ? $default : U($link, $param);
}

function rmdirr($dirname)
{
	if (!file_exists($dirname)) {
		return false;
	}

	if (is_file($dirname) || is_link($dirname)) {
		return unlink($dirname);
	}

	$dir = dir($dirname);

	if ($dir) {
		while (false !== $entry = $dir->read()) {
			if (($entry == '.') || ($entry == '..')) {
				continue;
			}

			rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
		}
	}

	$dir->close();
	return rmdir($dirname);
}

function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
	$tree = array();

	if (is_array($list)) {
		$refer = array();

		foreach ($list as $key => $data) {
			$refer[$data[$pk]] = &$list[$key];
		}

		foreach ($list as $key => $data) {
			$parentId = $data[$pid];

			if ($root == $parentId) {
				$tree[] = &$list[$key];
			}
			else if (isset($refer[$parentId])) {
				$parent = &$refer[$parentId];
				$parent[$child][] = &$list[$key];
			}
		}
	}

	return $tree;
}

function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
{
	if (is_array($tree)) {
		$refer = array();

		foreach ($tree as $key => $value) {
			$reffer = $value;

			if (isset($reffer[$child])) {
				unset($reffer[$child]);
				tree_to_list($value[$child], $child, $order, $list);
			}

			$list[] = $reffer;
		}

		$list = list_sort_by($list, $order, $sortby = 'asc');
	}

	return $list;
}

function list_sort_by($list, $field, $sortby = 'asc')
{
	if (is_array($list)) {
		$refer = $resultSet = array();

		foreach ($list as $i => $data) {
			$refer[$i] = &$data[$field];
		}

		switch ($sortby) {
		case 'asc':
			asort($refer);
			break;

		case 'desc':
			arsort($refer);
			break;

		case 'nat':
			natcasesort($refer);
		}

		foreach ($refer as $key => $val) {
			$resultSet[] = &$list[$key];
		}

		return $resultSet;
	}

	return false;
}

function list_search($list, $condition)
{
	if (is_string($condition)) {
		parse_str($condition, $condition);
	}

	$resultSet = array();

	foreach ($list as $key => $data) {
		$find = false;

		foreach ($condition as $field => $value) {
			if (isset($data[$field])) {
				if (0 === strpos($value, '/')) {
					$find = preg_match($value, $data[$field]);
				}
				else if ($data[$field] == $value) {
					$find = true;
				}
			}
		}

		if ($find) {
			$resultSet[] = &$list[$key];
		}
	}

	return $resultSet;
}

function d_f($name, $value, $path = DATA_PATH)
{
	if (APP_MODE == 'sae') {
		return false;
	}

	static $_cache = array();
	$filename = $path . $name . '.php';

	if ('' !== $value) {
		if (is_null($value)) {
		}
		else {
			$dir = dirname($filename);

			if (!is_dir($dir)) {
				mkdir($dir, 493, true);
			}

			$_cache[$name] = $value;
			$content = strip_whitespace('<?php' . "\t" . 'return ' . var_export($value, true) . ';?>') . PHP_EOL;
			return file_put_contents($filename, $content, FILE_APPEND);
		}
	}

	if (isset($_cache[$name])) {
		return $_cache[$name];
	}

	if (is_file($filename)) {
		$value = include $filename;
		$_cache[$name] = $value;
	} else {
		$value = false;
	}

	return $value;
}

function DownloadFile($fileName)
{
	ob_end_clean();
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Length: ' . filesize($fileName));
	header('Content-Disposition: attachment; filename=' . basename($fileName));
	readfile($fileName);
}

function download_file($file, $o_name = '')
{
	if (is_file($file)) {
		$length = filesize($file);
		$type = mime_content_type($file);
		$showname = ltrim(strrchr($file, '/'), '/');

		if ($o_name) {
			$showname = $o_name;
		}

		header('Content-Description: File Transfer');
		header('Content-type: ' . $type);
		header('Content-Length:' . $length);

		if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
			header('Content-Disposition: attachment; filename="' . rawurlencode($showname) . '"');
		}
		else {
			header('Content-Disposition: attachment; filename="' . $showname . '"');
		}

		readfile($file);
		exit();
	} else {
		exit('文件不存在');
	}
}

function wb_substr($str, $len = 140, $dots = 1, $ext = '')
{
	$str = htmlspecialchars_decode(strip_tags(htmlspecialchars($str)));
	$strlenth = 0;
	$output = '';
	preg_match_all('/[' . "\x1" . '-]|[' . "\xc2" . '-' . "\xdf" . '][' . "\x80" . '-' . "\xbf" . ']|[' . "\xe0" . '-' . "\xef" . '][' . "\x80" . '-' . "\xbf" . ']{2}|[' . "\xf0" . '-' . "\xff" . '][' . "\x80" . '-' . "\xbf" . ']{3}/', $str, $match);

	foreach ($match[0] as $v) {
		preg_match('/[' . "\xe0" . '-' . "\xef" . '][' . "\x80" . '-' . "\xbf" . ']{2}/', $v, $matchs);

		if (!empty($matchs[0])) {
			$strlenth += 1;
		}
		else if (is_numeric($v)) {
			$strlenth += 0.54500000000000004;
		}
		else {
			$strlenth += 0.47499999999999998;
		}

		if ($len < $strlenth) {
			$output .= $ext;
			break;
		}

		$output .= $v;
	}

	if (($len < $strlenth) && $dots) {
		$output .= '...';
	}

	return $output;
}

function msubstr($str, $start = 0, $length, $charset = 'utf-8', $suffix = true)
{
	if (function_exists('mb_substr')) {
		$slice = mb_substr($str, $start, $length, $charset);
	}
	else if (function_exists('iconv_substr')) {
		$slice = iconv_substr($str, $start, $length, $charset);

		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re['utf-8'] = '/[' . "\x1" . '-]|[' . "\xc2" . '-' . "\xdf" . '][' . "\x80" . '-' . "\xbf" . ']|[' . "\xe0" . '-' . "\xef" . '][' . "\x80" . '-' . "\xbf" . ']{2}|[' . "\xf0" . '-' . "\xff" . '][' . "\x80" . '-' . "\xbf" . ']{3}/';
		$re['gb2312'] = '/[' . "\x1" . '-]|[' . "\xb0" . '-' . "\xf7" . '][' . "\xa0" . '-' . "\xfe" . ']/';
		$re['gbk'] = '/[' . "\x1" . '-]|[' . "\x81" . '-' . "\xfe" . '][@-' . "\xfe" . ']/';
		$re['big5'] = '/[' . "\x1" . '-]|[' . "\x81" . '-' . "\xfe" . ']([@-~]|' . "\xa1" . '-' . "\xfe" . '])/';
		preg_match_all($re[$charset], $str, $match);
		$slice = join('', array_slice($match[0], $start, $length));
	}

	return $suffix ? $slice . '...' : $slice;
}

function highlight_map($str, $keyword)
{
	return str_replace($keyword, '<em class=\'keywords\'>' . $keyword . '</em>', $str);
}

function del_file($file)
{
	$file = file_iconv($file);
	@(unlink($file));
}

function status_text($model, $key)
{
	if ($model == 'Nav') {
		$text = array('无效', '有效');
	}

	return $text[$key];
}

function user_auth_sign($user)
{
	ksort($user);
	$code = http_build_query($user);
	$sign = sha1($code);
	return $sign;
}

function get_link($link_id = NULL, $field = 'url')
{
	$link = '';

	if (empty($link_id)) {
		return $link;
	}

	$link = D('Url')->getById($link_id);

	if (empty($field)) {
		return $link;
	} else {
		return $link[$field];
	}
}

function get_cover($cover_id, $field = NULL)
{
	if (empty($cover_id)) {
		return false;
	}

	$picture = D('Picture')->where(array('status' => 1))->getById($cover_id);

	if ($field == 'path') {
		if (!empty($picture['url'])) {
			$picture['path'] = $picture['url'];
		} else {
			$picture['path'] = __ROOT__ . $picture['path'];
		}
	}

	return empty($field) ? $picture : $picture[$field];
}

function get_admin_name()
{
	$user = session(C('USER_AUTH_KEY'));
	return $user['admin_name'];
}

function is_login()
{
	$user = session(C('USER_AUTH_KEY'));

	if (empty($user)) {
		return 0;
	} else {
		return session(C('USER_AUTH_SIGN_KEY')) == user_auth_sign($user) ? $user['admin_id'] : 0;
	}
}

function is_administrator($uid = NULL)
{
	$uid = (is_null($uid) ? is_login() : $uid);
	return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}

function show_tree($tree, $template)
{
	$view = new View();
	$view->assign('tree', $tree);
	return $view->fetch($template);
}

function int_to_string(&$data, $map = array(
	'status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')
	))
{
	if (($data === false) || ($data === NULL)) {
		return $data;
	}

	$data = (array) $data;

	foreach ($data as $key => $row) {
		foreach ($map as $col => $pair) {
			if (isset($row[$col]) && isset($pair[$row[$col]])) {
				$data[$key][$col . '_text'] = $pair[$row[$col]];
			}
		}
	}

	return $data;
}

function hook($hook, $params = array())
{
	return \Think\Hook::listen($hook, $params);
}

function get_addon_class($name)
{
	$type = (strpos($name, '_') !== false ? 'lower' : 'upper');

	if ('upper' == $type) {
		$dir = \Think\Loader::parseName(lcfirst($name));
		$name = ucfirst($name);
	} else {
		$dir = $name;
		$name = \Think\Loader::parseName($name, 1);
	}

	$class = 'addons\\' . $dir . '\\' . $name;
	return $class;
}

function get_addon_config($name)
{
	$class = get_addon_class($name);

	if (class_exists($class)) {
		$addon = new $class();
		return $addon->getConfig();
	} else {
		return array();
	}
}

function addons_url($url, $param = array())
{
	$url = parse_url($url);
	$case = C('URL_CASE_INSENSITIVE');
	$addons = ($case ? parse_name($url['scheme']) : $url['scheme']);
	$controller = ($case ? parse_name($url['host']) : $url['host']);
	$action = trim($case ? strtolower($url['path']) : $url['path'], '/');

	if (isset($url['query'])) {
		parse_str($url['query'], $query);
		$param = array_merge($query, $param);
	}

	$params = array('_addons' => $addons, '_controller' => $controller, '_action' => $action);
	$params = array_merge($params, $param);
	return U('Addons/execute', $params);
}

function get_addonlist_field($data, $grid, $addon)
{
	foreach ($grid['field'] as $field) {
		$array = explode('|', $field);
		$temp = $data[$array[0]];

		if (isset($array[1])) {
			$temp = call_user_func($array[1], $temp);
		}

		$data2[$array[0]] = $temp;
	}

	if (!empty($grid['format'])) {
		$value = preg_replace_callback('/\\[([a-z_]+)\\]/', function($match) use($data2) {
			return $data2[$match[1]];
		}, $grid['format']);
	} else {
		$value = implode(' ', $data2);
	}

	if (!empty($grid['href'])) {
		$links = explode(',', $grid['href']);

		foreach ($links as $link) {
			$array = explode('|', $link);
			$href = $array[0];

			if (preg_match('/^\\[([a-z_]+)\\]$/', $href, $matches)) {
				$val[] = $data2[$matches[1]];
			}
			else {
				$show = (isset($array[1]) ? $array[1] : $value);
				$href = str_replace(array('[DELETE]', '[EDIT]', '[ADDON]'), array('del?ids=[id]&name=[ADDON]', 'edit?id=[id]&name=[ADDON]', $addon), $href);
				$href = preg_replace_callback('/\\[([a-z_]+)\\]/', function($match) use($data) {
					return $data[$match[1]];
				}, $href);
				$val[] = '<a href="' . U($href) . '">' . $show . '</a>';
			}
		}

		$value = implode(' ', $val);
	}

	return $value;
}

function get_config_type($type = 0)
{
	$list = C('CONFIG_TYPE_LIST');
	return $list[$type];
}

function get_config_group($group = 0)
{
	$list = C('CONFIG_GROUP_LIST');
	return $group ? $list[$group] : '';
}

function parse_config_attr($string)
{
	$array = preg_split('/[,;\\r\\n]+/', trim($string, ',;' . "\r\n"));

	if (strpos($string, ':')) {
		$value = array();

		foreach ($array as $val) {
			list($k, $v) = explode(':', $val);
			$value[$k] = $v;
		}
	} else {
		$value = $array;
	}

	return $value;
}

function parse_field_attr($string)
{
	if (0 === strpos($string, ':')) {
		return eval(substr($string, 1) . ';');
	}

	$array = preg_split('/[,;\\r\\n]+/', trim($string, ',;' . "\r\n"));

	if (strpos($string, ':')) {
		$value = array();

		foreach ($array as $val) {
			list($k, $v) = explode(':', $val);
			$value[$k] = $v;
		}
	} else {
		$value = $array;
	}

	return $value;
}

function api($name, $vars = array())
{
	$array = explode('/', $name);
	$method = array_pop($array);
	$classname = array_pop($array);
	$module = ($array ? array_pop($array) : 'Common');
	$callback = $module . '\\Api\\' . $classname . 'Api::' . $method;

	if (is_string($vars)) {
		parse_str($vars, $vars);
	}

	return call_user_func_array($callback, $vars);
}

function think_encrypt($data, $key = '', $expire = 0)
{
	$key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
	$data = base64_encode($data);
	$x = 0;
	$len = strlen($data);
	$l = strlen($key);
	$char = '';
	$i = 0;

	for (; $i < $len; $i++) {
		if ($x == $l) {
			$x = 0;
		}

		$char .= substr($key, $x, 1);
		$x++;
	}

	$str = sprintf('%010d', $expire ? $expire + time() : 0);
	$i = 0;

	for (; $i < $len; $i++) {
		$str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)) % 256));
	}

	return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

function think_decrypt($data, $key = '')
{
	$key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
	$data = str_replace(array('-', '_'), array('+', '/'), $data);
	$mod4 = strlen($data) % 4;

	if ($mod4) {
		$data .= substr('====', $mod4);
	}

	$data = base64_decode($data);
	$expire = substr($data, 0, 10);
	$data = substr($data, 10);

	if ((0 < $expire) && ($expire < time())) {
		return '';
	}

	$x = 0;
	$len = strlen($data);
	$l = strlen($key);
	$char = $str = '';
	$i = 0;

	for (; $i < $len; $i++) {
		if ($x == $l) {
			$x = 0;
		}

		$char .= substr($key, $x, 1);
		$x++;
	}

	$i = 0;

	for (; $i < $len; $i++) {
		if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
			$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
		}
		else {
			$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
		}
	}

	return base64_decode($str);
}

function data_auth_sign($data)
{
	if (!is_array($data)) {
		$data = (array) $data;
	}

	ksort($data);
	$code = http_build_query($data);
	$sign = sha1($code);
	return $sign;
}

function format_bytes($size, $delimiter = '')
{
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	$i = 0;

	for (; $i < 5; $i++) {
		$size /= 1024;
	}

	return round($size, 2) . $delimiter . $units[$i];
}

function set_redirect_url($url)
{
	cookie('redirect_url', $url);
}

function get_redirect_url()
{
	$url = cookie('redirect_url');
	return empty($url) ? __APP__ : $url;
}

function time_format($time = NULL, $format = 'Y-m-d H:i')
{
	$time = ($time === NULL ? NOW_TIME : intval($time));
	return date($format, $time);
}

function create_dir_or_files($files)
{
	foreach ($files as $key => $value) {
		if ((substr($value, -1) == '/') && !is_dir($value)) {
			mkdir($value);
		}
		else {
			@(file_put_contents($value, ''));
		}
	}
}

function get_table_name($model_id = NULL)
{
	if (empty($model_id)) {
		return false;
	}

	$Model = M('Model');
	$name = '';
	$info = $Model->getById($model_id);

	if ($info['extend'] != 0) {
		$name = $Model->getFieldById($info['extend'], 'name') . '_';
	}

	$name .= $info['name'];
	return $name;
}

function get_model_attribute($model_id, $group = true)
{
	static $list;

	if (empty($model_id) || !is_numeric($model_id)) {
		return '';
	}

	if (empty($list)) {
		$list = S('attribute_list');
	}

	if (!isset($list[$model_id])) {
		$map = array('model_id' => $model_id);
		$extend = M('Model')->getFieldById($model_id, 'extend');

		if ($extend) {
			$map = array(
				'model_id' => array(
					'in',
					array($model_id, $extend)
					)
				);
		}

		$info = M('Attribute')->where($map)->select();
		$list[$model_id] = $info;
	}

	$attr = array();

	foreach ($list[$model_id] as $value) {
		$attr[$value['id']] = $value;
	}

	if ($group) {
		$sort = M('Model')->getFieldById($model_id, 'field_sort');

		if (empty($sort)) {
			$group = array(1 => array_merge($attr));
		}
		else {
			$group = json_decode($sort, true);
			$keys = array_keys($group);

			foreach ($group as &$value) {
				foreach ($value as $key => $val) {
					$value[$key] = $attr[$val];
					unset($attr[$val]);
				}
			}

			if (!empty($attr)) {
				$group[$keys[0]] = array_merge($group[$keys[0]], $attr);
			}
		}

		$attr = $group;
	}

	return $attr;
}

function get_table_field($value = NULL, $condition = 'id', $field = NULL, $table = NULL)
{
	if (empty($value) || empty($table)) {
		return false;
	}

	$map[$condition] = $value;
	$info = M(ucfirst($table))->where($map);

	if (empty($field)) {
		$info = $info->field(true)->find();
	} else {
		$info = $info->getField($field);
	}

	return $info;
}

function get_tag($id, $link = true)
{
	$tags = D('Article')->getFieldById($id, 'tags');

	if ($link && $tags) {
		$tags = explode(',', $tags);
		$link = array();

		foreach ($tags as $value) {
			$link[] = '<a href="' . U('/') . '?tag=' . $value . '">' . $value . '</a>';
		}

		return join($link, ',');
	} else {
		return $tags ? $tags : 'none';
	}
}

function addon_model($addon, $model)
{
	$dir = \Think\Loader::parseName(lcfirst($addon));
	$class = 'addons\\' . $dir . '\\model\\' . ucfirst($model);
	$model_path = ONETHINK_ADDON_PATH . $dir . '/model/';
	$model_filename = \Think\Loader::parseName(lcfirst($model));
	$class_file = $model_path . $model_filename . '.php';

	if (!class_exists($class)) {
		if (is_file($class_file)) {
			\Think\Loader::import($model_filename, $model_path);
		}
		else {
			E('插件' . $addon . '的模型' . $model . '文件找不到');
		}
	}

	return new $class($model);
}

function check_server()
{
	//debug('开始checkAuth');

	//if (defined('JKHJ<KJJLKNJKHNKJL')) {
	//	return true;
	//}
}

function chkAuthCache()
{
    return true;
	$server_id = S('sjkdfhawefaefawe');
	$key1 = S('eiwuhuqwuiedfasn');
	$key2 = S('ueirfhk32yfsddsf');
	$expir_time = S('klysjdfweiofsetg');
	$times = S('edfqwerfqwrqrrqw');
	$md = S('fgserjkuiwerhwer');
	debug(array('$server_id' => $server_id, '$key1' => $key1, '$key2' => '$key2', '$expir_time' => $expir_time, '$times' => $times, '$md' => $md), 'check_get_S_vars');

	if (!$md) {
		debug('重新授权,构建S', '$md is null');
		msCheckAuth();
		return NULL;
	}

	debug('A');
	$md2 = md5(md5($key1) + md5('weike') + md5($expir_time) + md5($times) + md5(MSCODE) + md5($key2));

	if ($md != $md2) {
		debug('A Faill,Retry msCheckAuth');
		msCheckAuth($server_id);
		return NULL;
	}

	debug('B');
	debug('$times =' . $times, 'remain 次数');

	if ($times <= 0) {
		debug('B Faill Retry msCheckAuth');
		msCheckAuth($server_id);
		return NULL;
	}

	debug('C');
	debug('$expir_time = ' . $expir_time . '|time() =' . time() . '|step=' . ($expir_time - time()), '$expir_time');

	if ($expir_time < time()) {
		debug('C Faill,Retry msCheckAuth');
		msCheckAuth($server_id);
		return NULL;
	}

	debug('D');
	$times--;
	$md = md5(md5($key1) + md5('weike') + md5($expir_time) + md5($times) + md5(MSCODE) + md5($key2));
	S('fgserjkuiwerhwer', $md);
	S('edfqwerfqwrqrrqw', $times);
	debug('return true');
	return true;
}

function msgetUrl($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, '');
	$data = curl_exec($ch);
	return $data;
}

function msCheckAuth($server_id = '')
{
	$servers = C('__CLOUD__');
	$servers_url = S('servers_url');

	if (msgeturl($servers_url . '/Appstore/text') != 1) {
		S('servers_url', NULL);

		foreach ($servers as $k => $v) {
			if (msgeturl($v . '/Appstore/text') == 1) {
				S('servers_url', $v);
				break;
			}
		}
	}

	if (!S('servers_url')) {
		if (MODULE_NAME == 'Admin') {
			$url = U('Admin/Index/index');
		}
		else {
			$url = U('Home/Index/index');
		}

		redirect($url);
	}

	msCheckAuthDo();
}

function msCheckAuthDo($server_id = '')
{
	debug('come 开始连接授权', 'msCheckAuth');
	$des_key = md5('MS_C_TOKEN_KEY#' . MSCODE);

	if (!defined('MSCODE')) {
		msMes('MSCODE not Found!');
	}

	debug('first auth start');
	$c_rand1 = '###client#' . md5(uniqid(mt_rand(), 1) . '|' . rand(0, 10000) . '|' . time()) . '#client###';
	$timeRes = '';
	$server_api = S('servers_url') . '/Index/Server';
	$timeRes = msCurl($server_api, array('action' => 'get_token', 'c_token' => think_encrypt($c_rand1, md5($des_key . '#C001#'))), 1);
	debug(array('res' => $timeRes), 'First run res');
	$timeRes = json_decode($timeRes, true);

	if (!$timeRes || !isset($timeRes['code'])) {
		msMes('10001');
	}

	if ($timeRes['code'] !== 0) {
		msMes($timeRes['data'] . ' timeRes code != 0');
	}

	if (!isset($timeRes['data']['token'])) {
		msMes('10002');
	}

	$token = $timeRes['data']['token'];
	$res = think_decrypt($token, md5($des_key . '#S001#'));
	$res = explode('|', $res, 2);

	if (trim($res[0]) !== trim($c_rand1)) {
		debug(array($res, $c_rand1), 'first auth fail');
		msMes('10004');
	}

	debug('first auth end');
	$server_code = trim($res[1]);
	debug(array('$server_code' => $server_code), 'second auth start');
	$domain_rand = md5(uniqid(mt_rand(), 1) . time());
	$domain_rand_key = '###domain#' . $domain_rand . '#domain###';
	$randKey2 = uniqid(mt_rand(), 1) . '|' . rand(0, 10000) . '|' . time();
	$randKey2 = '###client2#' . md5($randKey2) . '#client2###';
	$code = think_encrypt($server_code . '|' . $randKey2 . '|' . $domain_rand_key, md5($des_key . '#C002#'));
	@($res = file_put_contents(UPLOAD_PATH . '/coin/coin.png', $domain_rand));

	if ($res) {
		debug(UPLOAD_PATH . '/coin/coin.png', 'file_put_contents');
	} else {
		debug(UPLOAD_PATH . '/coin/coin.png' . ' 必须写权限', '警告警告警告警告');
	}

	debug(array('$domain_rand' => $domain_rand, '$server_id' => $server_id), 'start save auth');
	debug('start second auth');
	$authRes = msCurl($server_api, array('action' => 'msauth', 'HOST' => $_SERVER['HTTP_HOST'], 'code' => $code));

	if (!$authRes || !isset($authRes['code'])) {
		msMes('10003');
	}

	if ($authRes['code'] !== 0) {
		msMes($authRes['data'] . ' Res code != 0');
	}

	if (!isset($authRes['data']['token2'])) {
		msMes('4');
	}

	$res = think_decrypt($authRes['data']['token2'], md5($des_key . '#S002#'));
	$res = explode('|', $res, 3);

	if ((trim($res[0]) === trim($server_code)) && (trim($res[1]) === trim($randKey2))) {
		saveAuthCache($domain_rand, $server_id, $authRes['data']['auth_config']);
	}
	else if (S('servers_url')) {
		exit('0o0');
	} else {
		if (MODULE_NAME == 'Admin') {
			$url = U('Admin/Index/index');
		}
		else {
			$url = U('Home/Index/index');
		}

		redirect($url);
	}
}

function msCurl($url, $data, $type = 0)
{
	debug(array('url' => $url, 'parm' => $data, 'type' => $type), 'msCurl start');
	$data = array_merge(array('MSCODE' => MSCODE), $data);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	$data = curl_exec($ch);
	debug($data, 'msCurl res');

	if ($type) {
		return $data;
	}

	$res = json_decode($data, true);

	if (!$res) {
		msMes('30001');
	}

	return $res;
}

//get request
function mCurl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $data = curl_exec($ch);

    return json_decode($data, true);
}

//get request
function myCurl($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $data = curl_exec($ch);

    return json_decode($data, true);
}

function msMes($msg)
{
	debug($msg, 'Auth_RES');

	if (S('servers_url')) {
		echo time();
	} else {
		if (MODULE_NAME == 'Admin') {
			$url = U('Admin/Index/index');
		}
		else {
			$url = U('Home/Index/index');
		}

		redirect($url);
	}

	exit();
}

function saveAuthCache($code, $server_id, $auth_config)
{
	$key1 = md5(md5($code) . rand(0, 100) . time());
	$key2 = md5(md5($code) . rand(0, 100) . time());
	$expir_time = (APP_DEBUG ? time() + 10 : time() + (24 * 60 * 60));
	$times = (APP_DEBUG ? 100 : 10000);
	$md = md5(md5($key1) + md5('weike') + md5($expir_time) + md5($times) + md5(MSCODE) + md5($key2));
	debug(array('$auth_config' => $auth_config, 'key1' => $key1, 'key2' => $key2), 'saveAuthCache-auth_config');
	S(md5($key1 . 'JKJKHJNK' . $key2), $auth_config);
	S('sjkdfhawefaefawe', $server_id);
	S('eiwuhuqwuiedfasn', $key1);
	S('ueirfhk32yfsddsf', $key2);
	S('klysjdfweiofsetg', $expir_time);
	S('edfqwerfqwrqrrqw', $times);
	S('fgserjkuiwerhwer', $md);
}

function get_auth_config()
{
	$key1 = S('eiwuhuqwuiedfasn');
	$key2 = S('ueirfhk32yfsddsf');
	debug($key1 . '|' . $key2, 'get_auth_config');

	if (!$key1 || !$key2) {
		return -1;
	}

	return S(md5($key1 . 'JKJKHJNK' . $key2));
}



function get_auth_game($name = NULL)
{
	if (empty($name)) {
		return NULL;
	}

	$auth = get_auth_config();

	if (!is_array($auth)) {
		return NULL;
	}

	$auth_arr = explode('|', $auth['game']);

	if (!is_array($auth_arr)) {
		return NULL;
	}

	if (!in_array($name, $auth_arr)) {
		return NULL;
	} else {
		return true;
	}
}


function clear_html($str){
    $str = preg_replace("/<style .*?<\/style>/is", "", $str);
    $str = preg_replace("/<script .*?<\/script>/is", "", $str);
    $str = preg_replace("/<br \s*\/?\/>/i", "\n", $str);
    $str = preg_replace("/<\/?p>/i", "\n\n", $str);
    $str = preg_replace("/<\/?td>/i", "\n", $str);
    $str = preg_replace("/<\/?div>/i", "\n", $str);
    $str = preg_replace("/<\/?blockquote>/i", "\n", $str);
    $str = preg_replace("/<\/?li>/i", "\n", $str);
    $str = preg_replace("/\&nbsp\;/i", " ", $str);
    $str = preg_replace("/\&nbsp/i", " ", $str);
    $str = preg_replace("/\&amp\;/i", "&", $str);
    $str = preg_replace("/\&amp/i", "&", $str);
    $str = preg_replace("/\&lt\;/i", "<", $str);
    $str = preg_replace("/\&lt/i", "<", $str);
    $str = preg_replace("/\&ldquo\;/i", '"', $str);
    $str = preg_replace("/\&ldquo/i", '"', $str);
    $str = preg_replace("/\&lsquo\;/i", "'", $str);
    $str = preg_replace("/\&lsquo/i", "'", $str);
    $str = preg_replace("/\&rsquo\;/i", "'", $str);
    $str = preg_replace("/\&rsquo/i", "'", $str);
    $str = preg_replace("/\&gt\;/i", ">", $str);
    $str = preg_replace("/\&gt/i", ">", $str);
    $str = preg_replace("/\&rdquo\;/i", '"', $str);
    $str = preg_replace("/\&rdquo/i", '"', $str);
    $str = strip_tags($str);
    $str = html_entity_decode($str, ENT_QUOTES);
    $str = preg_replace("/\&\#.*?\;/i", "", $str);
    return $str;
}


function weike_getCoreConfig(){
	$file_path = DATABASE_PATH . '/weike.json';
	if (file_exists($file_path)) {
		$weike_CoreConfig = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($file_path));
		$weike_CoreConfig = json_decode($weike_CoreConfig, true);
        $weike_CoreConfig['weike_indexcat'] = L('WEIKE_INDEXCAT');
		if ($weike_CoreConfig['auth'] == base64_decode("d2Vpa2U=")) {
			return $weike_CoreConfig;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function W_log($log)
{
	$logpath=$_SERVER["DOCUMENT_ROOT"]."/Runtime/Logs/log.html";
	$log_f=fopen($logpath,"a+");
	fputs($log_f,$log."\r\n");
	fclose($log_f);
}

//PHP 程序调试函数
function dd($str = 0)
{
    if (is_array($str) || is_object($str)) {
        echo "<pre>";
        var_dump($str);
        echo "</pre>";
        die;
    } else {
        echo "<pre>{$str}</pre>";
        exit;
    }
}

//阿里云 OSS　上传函数
function oss_upload($path, $tmp_name){
    if(empty($_FILES)){
        return false;
    }

    require_once VENDOR_PATH . 'oss/autoload.php';
    $accessKeyId = 'LTAIFjBKJtvMTGXa';
    $accessKeySecret = 'Ml2WvEnzzQBe4MI5ihS3F3jMvLO63i';
    $endpoint = 'oss-cn-shenzhen.aliyuncs.com';
    $bucket= 'firecoin';

    $object = $path;
    $content = file_get_contents($tmp_name);

    $ossClient = new OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $ossClient->putObject($bucket, $object, $content);
//        $content = $ossClient->getObject($bucket, $object);
//        $content = $ossClient->deleteObject($bucket, $object);

    return 'http://' . $bucket . '.' .$endpoint . '/' . $object;
}

function oss_upload_yz($path, $tmp_name){
    if(empty($tmp_name)){
        return false;
    }

    require_once VENDOR_PATH . 'oss/autoload.php';
    $accessKeyId = 'LTAIFjBKJtvMTGXa';
    $accessKeySecret = 'Ml2WvEnzzQBe4MI5ihS3F3jMvLO63i';
    $endpoint = 'oss-cn-shenzhen.aliyuncs.com';
    $bucket= 'firecoin';

    $object = $path;
    $content = $tmp_name;

    $ossClient = new OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $ossClient->putObject($bucket, $object, $content);
//        $content = $ossClient->getObject($bucket, $object);
//        $content = $ossClient->deleteObject($bucket, $object);

    return 'http://' . $bucket . '.' .$endpoint . '/' . $object;
}

//亚马孙 OSS　上传函数
function oss_upload_old($path, $tmp_name){
    if(empty($_FILES)){
        return false;
    }
    require VENDOR_PATH . '/aws/aws-autoloader.php';

    $s3 = new \Aws\S3\S3Client([
        'version'     => 'latest',
        'region'      => 'ap-northeast-1',
        'credentials' => [
            'key'    => 'AKIAIHQMIH3PWG5ATVFQ',
            'secret' => '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',
        ],
    ]);

    try {
        $s3->putObject([
            'Bucket' => 'babycoin',
            'Key'    => $path,
            'Body'   => fopen($tmp_name, 'r'),
            'ACL'    => 'public-read',
        ]);
    } catch (\Aws\S3\S3Exception $e) {
        echo "There was an error uploading the file.\n";
    }
    return 'https://s3-ap-northeast-1.amazonaws.com/babycoin/' . $path;
}

//时间格式化
function formatTime($time){
    $time=strtotime($time);
    $t=time()-$time;
    $f=array(
        '31536000'=>'年',
        '2592000'=>'个月',
        '604800'=>'星期',
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    foreach ($f as $k=>$v)    {
        if (0 !=$c=floor($t/(int)$k)) {
            return $c.$v.'前';
        }
    }
}

//获取随机的小数
function randomFloat($min = 0, $max = 1) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

function write_log($data,$root,$status=0){
    $time = date('Y-m-d H:i:s',time());
    if($status==0){
    	system("echo $time -- '$data' >> $file");    	
    }else{
    	system("rm -rf $file");
    }
}

function get_redis($key){
    $redis = new Redis();
    $redis->connect(C('redis')['host'],C('redis')['port']);
    $redis->auth(C('redis')['auth']);
    if(!$redis->exists($key)){
        set_redis($key,0);
    }
    return $redis->get($key);
}

function set_redis($key,$value){
    $redis = new Redis();
    $redis->connect(C('redis')['host'],C('redis')['port']);
    $redis->auth(C('redis')['auth']);
    return $redis->set($key,json_encode($value));
}

function amqp_send_direct_create_csv($arr,$routing_key='rpc_queue'){
    $num=json_encode($arr);
    // 建立TCP连接
    $connection = new AMQPConnection(C('arg_amqp'));
    $connection->connect() or die("Cannot connect to the broker!\n");

    $channel = new AMQPChannel($connection);

    $client_queue = new AMQPQueue($channel);
    $client_queue->setFlags(AMQP_EXCLUSIVE);
    $client_queue->declareQueue();
    $callback_queue_name = $client_queue->getName();

    $corr_id = uniqid();
    $properties = [
        'correlation_id' => $corr_id,
        'reply_to' => $callback_queue_name
    ];

    $exchange = new AMQPExchange($channel);
    $exchange->publish($num, $routing_key, AMQP_NOPARAM, $properties);

    $client_queue->consume(function($envelope, $queue) use ($corr_id){
        if ($envelope->getCorrelationId() == $corr_id) {
            $msg = $envelope->getBody();
            define('__MSG__',$msg);
            file_put_contents('/tmp/test.md', $msg."\n");
            $queue->nack($envelope->getDeliveryTag());
            return false;
        }
    });
    // 断开连接
    $connection->disconnect();
    return __MSG__;
}

function amqp_publich_msg_direct($msg,$k_route='weike_mycz',$exchange='huocoin_csv'){
        //创建连接和channel
        if(!is_string($msg)){
            $msg = json_encode($msg);
        }
        $conn = new \AMQPConnection(C('arg_amqp'));
        if (!$conn->connect()) {
            return false;
        }
        $channel = new \AMQPChannel($conn);

        //创建交换机
        $ex = new \AMQPExchange($channel);
        $ex->setName($exchange);
        $ex->setType(AMQP_EX_TYPE_DIRECT); //direct类型
        $ex->setFlags(AMQP_DURABLE); //持久化
        //$ex->setFlags(AMQP_PASSIVE); //已存在抛出异常
        $ex->declare();
        //创建队列
        /*$q = new \AMQPQueue($channel);
        $q->setName($queue);
        $q->setFlags(AMQP_DURABLE); //持久化
        $q->declare();
        //绑定交换机与队列，并指定路由键
        $q->unbind($ex, $k_route);*/
        $ex->publish($msg,$k_route);
        $conn->disconnect();
        return true;
}
//obj转array
function object_array($array) {
    if(is_object($array)) {
        $array = (array)$array;
    }
    if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}

function new_master_order_status($master){

}
//--------------------》支付
function checkorderstatus($ordid){
	$Ord=M('Mycz');
	$ordstatus=$Ord->where('ordid='.$ordid)->getField('ordstatus');
	if($ordstatus==1){
		return true;
	}else{
		return false;
	}
}
//处理订单函数
//更新订单状态，写入订单支付后返回的数据
function orderhandle($parameter){
	$ordid=$parameter['out_trade_no'];
	$data['payment_trade_no']      =$parameter['trade_no'];
	$data['payment_trade_status']  =$parameter['trade_status'];
	$data['payment_notify_id']     =$parameter['notify_id'];
	$data['payment_notify_time']   =$parameter['notify_time'];
	$data['payment_buyer_email']   =$parameter['buyer_email'];
	$data['ordstatus']             =1;
	$Ord=M('Mycz');
	$Ord->where('ordid='.$ordid)->save($data);
}



/*-----------------------------------
2013.8.13更正
下面这个函数，其实不需要，大家可以把他删掉，
具体看我下面的修正补充部分的说明
------------------------------------*/

//获取一个随机且唯一的订单号；
function getordcode(){
	$Ord=M('mycz');
	$numbers = range (10,99);
	shuffle ($numbers);
	$code=array_slice($numbers,0,4);
	$ordcode=$code[0].$code[1].$code[2].$code[3];
	$oldcode=$Ord->where("ordcode='".$ordcode."'")->getField('ordcode');
	if($oldcode){
		getordcode();
	}else{
		return $ordcode;
	}
}
//阿里云接口
function sendSms($mobile,$code){
	Vendor('Alisms.Core.Config');
	//use Aliyun\Core\Profile\DefaultProfile;
	Vendor('Alisms.Core.Profile.DefaultProfile');
	//use Aliyun\Core\DefaultAcsClient;
	Vendor('Alisms.Core.DefaultAcsClient');
	//use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
	Vendor('Alisms.Api.Sms.Request.V20170525.SendSmsRequest');
	//use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
	Vendor('Alisms.Api.Sms.Request.V20170525.QuerySendDetailsRequest');
	// 加载区域结点配置
	\Aliyun\Core\Config::load();
	// 初始化用户Profile实例
	$profile = \Aliyun\Core\Profile\DefaultProfile::getProfile(C('ALI_SMS.REGION'), C('ALI_SMS.KEY_ID'), C('ALI_SMS.KEY_SECRET'));


	// 增加服务结点
	\Aliyun\Core\Profile\DefaultProfile::addEndpoint(C('ALI_SMS.END_POINT_NAME'), C('ALI_SMS.REGION'), C('ALI_SMS.PRODUCT'), C('ALI_SMS.DOMAIN'));
	// 初始化AcsClient用于发起请求
	$acsClient = new \Aliyun\Core\DefaultAcsClient($profile);
//	dump($acsClient);die;
	// 初始化SendSmsRequest实例用于设置发送短信的参数
	$request = new \Aliyun\Api\Sms\Request\V20170525\SendSmsRequest();
	// 必填，设置雉短信接收号码
	$request->setPhoneNumbers($mobile);
	// 必填，设置签名名称
//	$request->setSignName('火网');
	// 必填，设置模板CODE
	$request->setTemplateCode('SMS_143610110');

	$params = array(
		'code' => $code
	);

	// 可选，设置模板参数
	$request->setTemplateParam(json_encode($params));

	// 可选，设置流水号
	//if($outId) {
	//    $request->setOutId($outId);
	//}
	// 发起访问请求
	$acsResponse = $acsClient->getAcsResponse($request);

	// 打印请求结果
	// var_dump($acsResponse);
	return $acsResponse;
}

function getHttpType()
{
    return $type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
}

//格式化输出数组
function p($arr,$var_dump=false){
    echo '<pre>';
    if($var_dump){
        var_dump($arr);
    }else{
        print_r($arr);
    }
}

//该数组是否存在
function is_set($arr,$str=''){
    return isset($arr)?$arr:$str;
}

//替换array_column函数
function arrar_column_s($arr,$val,$key=''){
    $new_arr = [];
    foreach ($arr as $k=>$v){
        if($key){
            $new_arr[$v[$key]] = $arr[$k][$val];
        }else{
            $new_arr[] = $arr[$k][$val];
        }
    }
    return $new_arr;
}

function json($arr){
    return json_encode($arr);
}


