<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/19
 * Time: 17:09
 */

/*

 *Google翻译PHP接口

 */
class Tool_Youdao
{
    //有道翻译
    function ydtranslate($query,$to,$from='AUTO')
    {
        $app_key = '46d4fd020512321a';//注册有道智云获取
        $sec_key = 'cLgDZ4lnm8TNQJ90p6UZfH78uigMtNfE';//注册有道智云获取
        $api_url = 'http://openapi.youdao.com/api';//可以使用https
        $args = [
            'q' => $query,
            'appKey' => $app_key,
            'salt' => rand(10000, 99999),
            'from' => 'AUTO',
            'to' => $to,

        ];
        $args['sign'] = self::buildSign($app_key, $query, $args['salt'], $sec_key);
        $ret = self::call($api_url, $args);
        //echo $ret;
        $ret = json_decode($ret, true)['translation'][0];
        return $ret;
    }

    //加密
    function buildSign($appKey, $query, $salt, $secKey)
    {
        $str = $appKey . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }

    //发起网络请求
    function call($url, $args = null, $method = "post", $testflag = 0, $timeout = 20, $headers = [])
    {
        $ret = false;
        $i = 0;
        while ($ret === false) {
            if ($i > 1)
                break;
            if ($i > 0) {
                sleep(1);
            }
            $ret = self::callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;
    }

    function callOnce($url, $args = null, $method = "post", $withCookie = false, $timeout = 20, $headers = [])
    {
        $ch = curl_init();
        if ($method == "post") {
            $data = self::convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            $data = self::convert($args);
            if ($data) {
                if (stripos($url, "?") > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    function convert(&$args)
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . "&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }
}