<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 12/26/2017
 * Time: 1:50 PM
 */

class Tool_SmsMeilian {


    // 美联 开发者主账号
    public static $username = "xzgr";//用户名

    public static $password_md5 = "48bc19c3d2e6763b31c0583aae5e457d";//32位MD5密码加密，不区分大小写

    public static $apikey = "b943b645e1cc2fb7850abc06aaff975b";//apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）


    //发送接口
    public static function sendSMS($mobile,$message,$encode='UTF-8')
    {
        //发送链接（用户名，密码，apikey，手机号，内容）
        $url = "http://m.5c.com.cn/api/send/index.php?";  //如连接超时，可能是您服务器不支持域名解析，请将下面连接中的：【m.5c.com.cn】修改为IP：【115.28.23.78】
        $data=array
        (
            'username'=>self::$username,
            'password_md5'=>self::$password_md5,
            'apikey'=>self::$apikey,
            'mobile'=>$mobile,
            'content'=>urlencode($message),//执行URLencode编码  ，$content = urldecode($content);解码,
            'encode'=>$encode,
        );
        $result = self::curlSMS($url,$data);
        return $result;
    }

    private static function curlSMS($url,$post_fields=array())
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);//用PHP取回的URL地址（值将被作为字符串）
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//使用curl_setopt获取页面内容或提交数据，有时候希望返回的内容作为变量存储，而不是直接输出，这时候希望返回的内容作为变量
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
        curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
        curl_setopt($ch,CURLOPT_POST,1);//设置这个选项为一个零非值，这个post是普通的application/x-www-from-urlencoded类型，多数被HTTP表调用。
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
        $data = curl_exec($ch);//抓取URL并把他传递给浏览器
        curl_close($ch);//释放资源

        $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
        return $res[2]; //然后在这里返回数组。
    }
}