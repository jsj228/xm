<?php

if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = NULL)
    {
        $result = array();

        if (NULL === $indexKey) {
            if (NULL === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else if (NULL === $columnKey) {
            foreach ($input as $row) {
                $result[$row[$indexKey]] = $row;
            }
        } else {
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
    $appkey = config('huafei_appkey');
    $openid = config('huafei_openid');
    $recharge = new \org\net\Recharge($appkey, $openid);
    $telRechargeRes = $recharge->telcz($moble, $num, $orderid);

    if ($telRechargeRes['error_code'] == '0') {
        return 1;
    } else {
        return NULL;
    }
}

/**
 * 记录异常信息
 * @param $e 异常对象
 * @param string $funName 方法名称，表示在那个方法下发生的异常
 * @param string $content 自定义的错误信息
 */
function exception_log($e,$funName='',$content=''){

    defined('EXCEPTION_PATH') or define('EXCEPTION_PATH', LOG_PATH.'exception'.DS);
    if(!is_dir(EXCEPTION_PATH)){
        @(mkdir(EXCEPTION_PATH));
    }

    if($funName){
        $fileName = EXCEPTION_PATH . MODULE_NAME.'-'.$funName.'_exception.log';
    }else{
        $fileName = EXCEPTION_PATH . MODULE_NAME.'-'.CONTROLLER_NAME.'-'.ACTION_NAME.'_exception.log';
    }

    $fp = fopen($fileName, 'a+b');
    if(flock($fp,LOCK_EX)) {
        $f_size = filesize($fileName);
        //超过2MB大小，就会把文件的内容清空
        if ($f_size && ($f_size > 0) && (round($f_size / pow(1024, 2), 2) > 2)) {
            ftruncate($fp, 0);
        }

        $msg = addtime(time()) . "\r\n";
        if ($content) {
            $msg .= '自定义错误信息： '.$content . "\r\n";
        }
        $msg .= '错误信息： ' . $e->getMessage() . "\r\n" . $e->getFile() . " --所在行： " . $e->getLine() . "\r\n" . $e->getTraceAsString() . "\r\n";

        fwrite($fp, pack("CCC", 0xef, 0xbb, 0xbf));
        fwrite($fp, $msg);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

}
//市场交易错误日志
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

/**
 * 得到用户id
 * @param null $username
 * @param string $type
 * @return mixed|null
 */
function userid($username = NULL, $type = 'username')
{
    if ($username && $type) {
        $userid = cache('userid' . $username . $type);

        if (!$userid) {
            $userid = db('User')->where(array($type => $username))->value('id');
            cache('userid' . $username . $type, $userid);
        }
    } else {
        $userid = session('userId');
    }

    return $userid ? $userid : NULL;
}

/**
 * 得到用户名
 * @param null $id 条件字段值
 * @param string $type 条件字段名
 * @return mixed|null 成功返回用户名 失败返回NULL
 */
function username($id = NULL, $type = 'id')
{
    if ($id && $type) {
        $username = cache('username' . $id . $type);

        if (!$username) {
            $username = db('User')->where(array($type => $id))->value('username');
            cache('username' . $id . $type, $username);
        }
    } else {
        $username = session('userName');
    }

    return $username ? $username : NULL;
}

/**
 * 检测目录权限
 * @return array
 */
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
                } else {
                    $val[1] = '不存在或者不可写';
                    $val[2] = 'remove';
                    session('error', true);
                }
            }
        } else if (file_exists(INSTALL_APP_PATH . $val[3])) {
            if (!is_writable(INSTALL_APP_PATH . $val[3])) {
                $val[1] = '文件存在但不可写';
                $val[2] = 'remove';
                session('error', true);
            }
        } else if (!is_writable(dirname(INSTALL_APP_PATH . $val[3]))) {
            $val[1] = '不存在或者不可写';
            $val[2] = 'remove';
            session('error', true);
        }
    }

    return $items;
}

/**
 * 安全过滤字符串
 * @param $text
 * @param bool $addslanshes
 * @return string
 */
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

/**
 * @param $text
 * @param bool $addslanshes
 * @return string
 */
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

/**
 * 清除缓存
 * @param string $dirname
 */
function clean_cache($dirname = './../runtime/')
{
    $dirs = array($dirname);

    foreach ($dirs as $value) {
        rmdirr($value);
    }

    @(mkdir($dirname, 511, true));
}

/**
 * @param $pArray
 * @param string $pKey
 * @param string $pCondition
 * @return array|bool
 */
function getSubByKey($pArray, $pKey = '', $pCondition = '')
{
    $result = array();

    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            if (is_object($temp_array)) {
                $temp_array = (array)$temp_array;
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
    if (config('app_debug')) {
        if (!IS_CLI) {
            \org\coin\FirePHP::getInstance(true)->log($value, $type);
        }
    }
}

function CoinClient($username, $password, $ip, $port, $timeout = 3, $headers = array(), $suppress_errors = false)
{
    return new \org\coin\CoinClient($username, $password, $ip, $port, $timeout, $headers, $suppress_errors);
}

function EthClient($ip, $port)
{
    return new  \org\coin\EthClient($ip, $port);
}

function EosClient($ip, $port)
{
    return new \org\coin\EosClient($ip, $port);
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
        $matrixPointSize = &min(max((int)$qr_size, 1), 10);
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

/**
 * @param null $ip
 * @return string
 */
function get_city_ip($ip = NULL)
{
    if (empty($ip)) {
        $request = \think\Request::instance();
        $ip = $request->ip();
    }

    $Ip = new org\net\IpLocation();
    $area = $Ip->getlocation($ip);
    $str = $area['country'] . $area['area'];
    $str = mb_convert_encoding($str, 'UTF-8', 'GBK');

    if (($ip == '127.0.0.1') || ($str == false) || ($str == 'IANA保留地址用于本地回送')) {
        $str = '未分配或者内网IP';
    }

    return $str;
}

/**
 * 以post方式发送请求
 * @param $url  网址
 * @param $post_data 封装的参数
 * @return bool|string
 */
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

/**
 * 以curl方式发送请求
 * @param $remote_server
 * @param $post_string
 * @return mixed
 */
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

//充值订单号
function tradeno()
{
    return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 2) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 6);
}


/**
 * 邀请码
 * @return bool|string
 */
function tradenoa()
{
    return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 6);
}

//充值小数点
function tradenob()
{
    return substr(str_shuffle(str_repeat('123456789', 4)), 0, 2);
}

//C2c 交易订单号
function tradenoc()
{
    return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 3) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 2);
}


function get_user($id, $type = NULL, $field = 'id')
{
    $key = md5('get_user' . $id . $type . $field);
    $data = cache($key);

    if (!$data) {
        $data = db('User')->where(array($field => $id))->find();
        cache($key, $data);
    }

    if ($type) {
        $rs = $data[$type];
    } else {
        $rs = $data;
    }

    return $rs;
}

/**
 * 判断是不是手机用户访问
 * @return bool
 */
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


/**
 * 麦讯通 短信接口
 * @param string $moble
 * @param string $title
 * @param string $message
 * @param bool $time_unlimited
 * @return int
 */
function send_moble($moble = '',$title = '',  $message = '',$time_unlimited = false)
{
    if (MOBILE_CODE == 0) {
        $result=['code'=>1,'msg'=>''];
        return $result;
    }

    if(!$time_unlimited) {
        $time = session('sms_mxt_send_time');
        if (!empty($time) && time() - $time < 60) {
            $result = ['code' => 0, 'msg' => '发送过于频繁，请稍后再试'];
            return $result;
        }
        session('sms_mxt_send_time', time());
    }

    //国内账号
    $userId	= '195833';
    $account	= 'admin';
    $password	= '769909';

    //国际账号
    $account_INT ='MXT195833';
    $pswd_INT ='MXT123456mxt';
    $product_id = 141540786;

    $message_format = '【'.$title.'】'.$message ;//加上签名

    if(false !== stripos($moble,'+86') || !(false !== stripos($moble,'+'))){//国内
        $smsMaixuntong = new \org\util\SmsMaixuntong($userId,$account,$password);
        $sent_ret = $smsMaixuntong->sendSMS($moble,$message_format);
    }else{//国际
        $smsMaixuntong = new \org\util\SmsMaixuntong(null,$account_INT,$pswd_INT,$product_id);
        $sent_ret = $smsMaixuntong->sendSMSINT($moble,$message_format);
    }
    if($sent_ret && $sent_ret['errorno']==0){
        $result=['code'=>1,'msg'=>''];
    }else{
        //失败了用美联接口再试
        $result = send_moble_ml($moble , $title, $message,$time_unlimited);
        //$result=['code'=>0,'msg'=>'验证码发送失败,请重发'];
    }
    return $result;

}

/**
 * 美联 短信接口
 * @param string $moble  手机号码
 * @param string $title  自定义签名
 * @param string $message 发送的短信内容
 * @param int $time_unlimited 60秒间隔控制 false 为限制（默认）true为无限制
 * @return array
 */
function send_moble_ml($moble = '', $title='', $message = '',$time_unlimited = false)
{

    if (MOBILE_CODE == 0) {
        $result=['code'=>1,'msg'=>''];
        return $result;
    }

    if(!$time_unlimited) {
        $time = session('sms_ml_send_time');
        if (!empty($time) && time() - $time < 60) {
            $result = ['code' => 0, 'msg' => '发送过于频繁，请稍后再试'];
            return $result;
        }
        session('sms_ml_send_time', time());
    }

    $username = 'hgxgr1';  //用户名
    $password_md5 = '0b11ac988314c2399752d3b4d875b217';  //32位MD5密码加密，不区分大小写
    $apikey = '02a327336f3761d2ca76f3a11f6d41cc';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
    $contentUrlEncode = urlencode($message);//执行URLencode编码  ，$content = urldecode($content);解码

    $smsMeilian = new \org\util\SmsMeilian();
    $res = $smsMeilian->sendSMS($username, $password_md5, $apikey, $moble, $contentUrlEncode, 'UTF-8');  //进行发送
    if (strpos($res, "success") !== false) {
        $result=['code'=>1,'msg'=>''];
    } else {
        $result=['code'=>0,'msg'=>'验证码发送失败,请重发'];
    }
    return $result;
}

//梦网
function send_moble_mw($moble = '', $title = '', $message = '')
{
    if (MOBILE_CODE == 0) {
        return 1;
        die();
    }
    $time =  session('sms_mw_send_time');
    if (!empty($time) && time() - $time < 60 ) {
        return 0;
    }
    session('sms_mw_send_time',time());

    //南方短信节点url地址
    $url = 'http://api01.monyun.cn:7901/sms/v2/std/';
    //北方短信节点url地址
    //$url = 'http://api02.monyun.cn:7901/sms/v2/std/';
    $smsSendConn = new \org\util\SmsSendConn($url);
    $data = [];
    //设置账号(必填)
    $data['userid'] = 'E100SC';
    //设置密码（必填.填写明文密码,如:1234567890）
    $data['pwd'] = 'bXwWaF';

    // 设置手机号码 此处只能设置一个手机号码(必填)
    $data['mobile'] = $moble;
    //设置发送短信内容(必填)
    $data['content'] = $message;
    // 业务类型(可选)
    $data['svrtype'] = '';
    // 设置扩展号(可选)
    $data['exno'] = '';
    //用户自定义流水编号(可选)
    $data['custid'] = '';
    // 自定义扩展数据(可选)
    $data['exdata'] = '';

    $result = $smsSendConn->singleSend($data);
    return $result['result'] === 0 ? 1 : 0;
}

//网易
function send_moble_wy($moble, $title = '', $message = '')
{
    if (MOBILE_CODE == 0) {
        return 1;
        die();
    }
    $p = new \org\util\SmsWyapi('0a8a2c3596400478fc40e85bf9a506d3', '88e93ca33530', 'fsockopen');     //fsockopen伪造请求
    $res = $p->sendSmsCode(3083164, $moble, '', '6');
    return $res ? 1 : 0;
}

function send_mail($moble, $subject = '', $message = '', $attachment = null, $config = "")
{
    exit("Don't Send Mails.");
    if ($config == '') {
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
        $i = rand(1, 2);
        $config = $arr[$i];
    }

    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
    $mail = new PHPMailer(); //PHPMailer对象
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug = 1;                     // 关闭SMTP调试功能	                               // 2 = messages only
    $mail->SMTPAuth = true;                  // 启用 SMTP 验证功能

    if ($config['SMTP_SSL']) {
        $mail->SMTPSecure = 'ssl';                 // 使用安全协议
    }

    $mail->Host = $config['server'];   // SMTP 服务器
    $mail->Port = $config['port'];   // SMTP服务器的端口号
    $mail->Username = $config['user'];  // SMTP服务器用户名
    $mail->Password = $config['pass'];  // SMTP服务器密码
    $mail->SetFrom($mail->Username, '');

    // $replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
    // $replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
    //  $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $title;
    $mail->MsgHTML($message);

    //发送邮件
    if (is_array($moble)) {
        //同时发送给多个人
        foreach ($moble as $key => $value) {
            $mail->AddAddress($value, "");  // 收件人邮箱和姓名
        }
    } else {    //只发送给一个人
        $mail->AddAddress($moble, "");  // 收件人邮箱和姓名
    }

    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    } else {
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

/**
 * 合法性验证 若满足用filter_var替换
 * @param $data
 * @param null $rule
 * @param null $ext
 * @return false|int
 */
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
    $validate['double'] = '/^[\\+]?\\d+(\\.\\d+)?$/';
    $validate['english'] = '/^[A-Za-z]+$/';
    $validate['idcard'] = '/^([0-9]{15}|[0-9]{17}[0-9a-zA-Z])$/';
    //$validate['truename'] = '/^[\\x{4e00}-\\x{9fa5}]{2,4}$/u';
    $validate['truename'] = '/^([\sA-Za-z]{5,20}||[\\x{4e00}-\\x{9fa5}]{2,8})$/u';//'/^[A-Za-z\\x{4e00}-\\x{9fa5}]{2,10}$/'
    $validate['username'] = '/^[a-zA-Z]{1}[0-9a-zA-Z_]{5,15}$/';
    $validate['email'] = '/^\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*$/';
    $validate['moble'] = '/^(((1[0-9][0-9]{1})|159|153|00852|09)+\\d{8,11})$/';
    $validate['moble2'] = '/^(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|0[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|1[987654321]|3[9643210]|2[70]|7|1)\d{1,14}$/';
    $validate['moble3'] = '/^(\\+)?(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|0[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|1[987654321]|3[9643210]|2[70]|7|1)\d{1,14}$/';
    $validate['password'] = '/^[a-zA-Z0-9_\\@\\#\\$\\%\\^\\&\\*\\(\\)\\!\\,\\.\\?\\-\\+\\|\\=]{6,16}$/';
    $validate['xnb'] = '/^[a-zA-Z]$/';

    if (isset($validate[strtolower($rule)])) {
        $rule = $validate[strtolower($rule)];
        return preg_match($rule, $data);
    }

    $Ap = '\\x{4e00}-\\x{9fff}' . '0-9a-zA-Z\\@\\#\\$\\%\\^\\&\\*\\(\\)\\!\\,\\.\\?\\-\\+\\|\\=';
    $Cp = '\\x{4e00}-\\x{9fff}';
    $Dp = '0-9';
    $Wp = 'a-zA-Z\\-';
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

function url_aid($link = '', $param = '', $default = '')
{
    return $default ? $default : url($link, $param);
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
            } else if (isset($refer[$parentId])) {
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
                } else if ($data[$field] == $value) {
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
        } else {
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

/**
 *
 * @param $fileName
 */
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
        } else {
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
        } else if (is_numeric($v)) {
            $strlenth += 0.54500000000000004;
        } else {
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
    } else if (function_exists('iconv_substr')) {
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

    $link = model('Url')->getById($link_id);

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

    $picture = model('Picture')->where(array('status' => 1))->getById($cover_id);

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
    $user = session(config('USER_AUTH_KEY'));
    return $user['admin_name'];
}

function is_login()
{
    $user = session(config('USER_AUTH_KEY'));

    if (empty($user)) {
        return 0;
    } else {
        return session(config('USER_AUTH_SIGN_KEY')) == user_auth_sign($user) ? $user['admin_id'] : 0;
    }
}

function is_administrator($uid = NULL)
{
    $uid = (is_null($uid) ? is_login() : $uid);
    return $uid && (intval($uid) === config('USER_ADMINISTRATOR'));
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

    $data = (array)$data;

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
    return \think\Hook::listen($hook, $params);
}

function get_addon_class($name)
{
    $type = (strpos($name, '_') !== false ? 'lower' : 'upper');

    if ('upper' == $type) {
        $dir = \think\Loader::parseName(lcfirst($name));
        $name = ucfirst($name);
    } else {
        $dir = $name;
        $name = \think\Loader::parseName($name, 1);
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
    $case = config('URL_CASE_INSENSITIVE');
    $addons = ($case ? parse_name($url['scheme']) : $url['scheme']);
    $controller = ($case ? parse_name($url['host']) : $url['host']);
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    $params = array('_addons' => $addons, '_controller' => $controller, '_action' => $action);
    $params = array_merge($params, $param);
    return url('Addons/execute', $params);
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
        $value = preg_replace_callback('/\\[([a-z_]+)\\]/', function ($match) use ($data2) {
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
            } else {
                $show = (isset($array[1]) ? $array[1] : $value);
                $href = str_replace(array('[DELETE]', '[EDIT]', '[ADDON]'), array('del?ids=[id]&name=[ADDON]', 'edit?id=[id]&name=[ADDON]', $addon), $href);
                $href = preg_replace_callback('/\\[([a-z_]+)\\]/', function ($match) use ($data) {
                    return $data[$match[1]];
                }, $href);
                $val[] = '<a href="' . url($href) . '">' . $show . '</a>';
            }
        }

        $value = implode(' ', $val);
    }

    return $value;
}

function get_config_type($type = 0)
{
    $list = config('CONFIG_TYPE_LIST');
    return $list[$type];
}

function get_config_group($group = 0)
{
    $list = config('CONFIG_GROUP_LIST');
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

function data_auth_sign($data)
{
    if (!is_array($data)) {
        $data = (array)$data;
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
        } else {
            @(file_put_contents($value, ''));
        }
    }
}

function get_table_field($value = NULL, $condition = 'id', $field = NULL, $table = NULL)
{
    if (empty($value) || empty($table)) {
        return false;
    }

    $map[$condition] = $value;
    $info = db(ucfirst($table))->where($map);

    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->value($field);
    }

    return $info;
}

function get_tag($id, $link = true)
{
    $tags = model('Article')->getFieldById($id, 'tags');

    if ($link && $tags) {
        $tags = explode(',', $tags);
        $link = array();

        foreach ($tags as $value) {
            $link[] = '<a href="' . url('/') . '?tag=' . $value . '">' . $value . '</a>';
        }

        return join($link, ',');
    } else {
        return $tags ? $tags : 'none';
    }
}

function addon_model($addon, $model)
{
    $dir = \think\Loader::parseName(lcfirst($addon));
    $class = 'addons\\' . $dir . '\\model\\' . ucfirst($model);
    $model_path = ONETHINK_ADDON_PATH . $dir . '/model/';
    $model_filename = \think\Loader::parseName(lcfirst($model));
    $class_file = $model_path . $model_filename . '.php';

    if (!class_exists($class)) {
        if (is_file($class_file)) {
            \think\Loader::import($model_filename, $model_path);
        } else {
            exception('插件' . $addon . '的模型' . $model . '文件找不到');
        }
    }

    return new $class($model);
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

    if (cache('servers_url')) {
        echo time();
    } else {
        if (MODULE_NAME == 'Admin') {
            $url = url('Admin/Index/index');
        } else {
            $url = url('Home/Index/index');
        }

        redirect($url);
    }

    exit();
}

function clear_html($str)
{
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

function weike_getCoreConfig()
{
    $file_path = DATABASE_PATH . '/weike.json';
    if (file_exists($file_path)) {
        $weike_CoreConfig = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($file_path));
        $weike_CoreConfig = json_decode($weike_CoreConfig, true);
        $weike_CoreConfig['weike_indexcat'] = lang('WEIKE_INDEXCAT');
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
    $logpath = $_SERVER["DOCUMENT_ROOT"] . "/runtime/Log/log.html";
    $log_f = fopen($logpath, "a+");
    fputs($log_f, $log . "\r\n");
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
function oss_upload($path, $tmp_name)
{
    if (empty($_FILES)) {
        return false;
    }

    require_once VENDOR_PATH . 'oss/autoload.php';
    $accessKeyId = 'LTAIFjBKJtvMTGXa';
    $accessKeySecret = 'Ml2WvEnzzQBe4MI5ihS3F3jMvLO63i';
    $endpoint = 'oss-cn-shenzhen.aliyuncs.com';
    $bucket = 'newcoin';

    $object = $path;
    $content = file_get_contents($tmp_name);

    $ossClient = new OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $ossClient->putObject($bucket, $object, $content);
//        $content = $ossClient->getObject($bucket, $object);
//        $content = $ossClient->deleteObject($bucket, $object);

    return 'http://' . $bucket . '.' . $endpoint . '/' . $object;
}


//时间格式化
function formatTime($time)
{
    $time = strtotime($time);
    $t = time() - $time;
    $f = array(
        '31536000' => '年',
        '2592000' => '个月',
        '604800' => '星期',
        '86400' => '天',
        '3600' => '小时',
        '60' => '分钟',
        '1' => '秒'
    );
    foreach ($f as $k => $v) {
        if (0 != $c = floor($t / (int)$k)) {
            return $c . $v . '前';
        }
    }
}

//返回当前的毫秒时间戳
function msectime() {
    list($msec, $sec) = explode(' ', microtime());
    $msectime = sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000000);
    return $msectime;
}

//获取随机的小数
function randomFloat($min = 0, $max = 1)
{
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

//获取 CSRF
function csrf_generate($length = 16)
{
    $string = '';

    while (($len = strlen($string)) < $length) {
        $size = $length - $len;

        $bytes = random_bytes($size);

        $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
    }


    session('csrf_token', $string);
    return $string;
}

//生成令牌
function token_generate(){

    $token_str = \think\Request::instance()->token('coin_tk');
    return $token_str;
}

//验证令牌
function token_check($val=''){
    $t_key = 'coin_tk';
    if(!$val||!session($t_key)){
        return false;
    }
    // 令牌验证
    if (session($t_key) === $val) {
        // 防止重复提交
        session($t_key,null);// 验证完成销毁session
        return true;
    }
    // 开启TOKEN重置
    session($t_key,null);
    return false;
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

//汇率接口
function nowapi_call($a_parm){
    if(!is_array($a_parm)){
        return false;
    }
    //combinations
    $a_parm['format']=empty($a_parm['format'])?'json':$a_parm['format'];
    $apiurl=empty($a_parm['apiurl'])?'http://api.k780.com/?':$a_parm['apiurl'];
    unset($a_parm['apiurl']);
    foreach($a_parm as $k=>$v){
        $apiurl.=$k.'='.$v.'&';
    }
    $apiurl=substr($apiurl,0,-1);
    if(!$callapi=file_get_contents($apiurl)){
        return false;
    }
    //format
    if($a_parm['format']=='base64'){
        $a_cdata=unserialize(base64_decode($callapi));
    }elseif($a_parm['format']=='json'){
        if(!$a_cdata=json_decode($callapi,true)){
            return false;
        }
    }else{
        return false;
    }
    //array
    if($a_cdata['success']!='1'){
        echo $a_cdata['msgid'].' '.$a_cdata['msg'];
        return false;
    }
    return $a_cdata['result'];
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code)
{
    $_status = array(
        // Success 2xx
        200 => 'OK',
        // Redirection 3xx
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ', // 1.1
        // Client Error 4xx
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        // Server Error 5xx
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    );
    if (isset($_status[$code])) {
        header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}





