<?php
class Tool_Message {

    // 云片网
    public static $apikey = "a5c1428d05e0701a128871b6a70ba374"; //修改为您的apikey(https://www.yunpian.com)登陆官网后获取

    // 智语 接入号，
    public static $extno = "10690000000001";
    // 智语 账号，
    public static $account = "000002";
    // 智语 密码，
    public static $password = "123456";

    // 智语 开发者主账号
    public static $apiAccount = "ACCc1a0fa02871c4089b3c9d014655a643e";

    public static $zhiyuApiKey = "APId9da1971ea99424fb66307dc8608c03f";

    public static $baseurl = "http://www.zypaas.com:9988/V1";

    public static $appId = "APP0ae793164fc54093aa465fe2326caa33";

    public static $accountt  = "18098911995";

    /**
     * 智语短信平台接入， 发送请求
     * time : 2017-5-26
     * @param $action
     * @param string $mobile
     * @param string $text
     * @param string $sign
     * @return mixed
     */
    static function zhiyurun($action, $mobile = '', $text = '', $sign = '智语')
    {
        $ch = curl_init();

        # 设置验证方式 json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:*/*;charset=utf-8', 'Content-Type:application/json','charset=utf-8'));

        # 设置返回结果为流
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        # 设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        # 设置通信方式
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $tmMo = new Tool_Message;
        $time = $tmMo->microtime_float();
        switch ($action) {
            case 'get':

                $json_data = $tmMo->getUser($ch, self::$apikey);
                break;

            case 'send':

                $data = array(
                    "apiAccount"=> self::$apiAccount,
                    "appId"=> self::$appId,
                    "sign"=> $tmMo->getMd5Sign(self::$apiAccount,self::$zhiyuApiKey,$time),
                    "timeStamp"=> $time,
                    "smsType"=> "8",
                    "mobile"=> $mobile,
                    "content"=> $text,
                );

                $json_data = $tmMo->zhiyusend($ch, $data);

                break;

            case 'voice_send':
                $data = array(
                    'apiAccount' => self::$apiAccount,
                    'appId' => self::$appId,
                    'requestId' => "123",
                    'callee' => $mobile,
                    'calleeDisplay' => '01057694466',
                    'verifyCode' => $text,
                    'playTimes' => 3,
                    'timeStamp' => $time,
                    'sign'      => $tmMo->getMd5Sign(self::$apiAccount,self::$zhiyuApiKey,$time),
                );
                $json_data = $tmMo->zhiyuVoiceSend($ch, $data);

                break;


            default:
                # code...
                break;
        }
        curl_close($ch);

        // 解析json数据
        return json_decode($json_data,true);

//      return simplexml_load_string($json_data);
    }


    public function sendSmsTempBatch($to, $templateId)
    {
        $time = $this->microtime_float();
        $data = array(
            'apiAccount' => self::$apiAccount,
            'appId' => self::$appId,
            'timeStamp' => $time,
            'sign'      => $this->getMd5Sign(self::$apiAccount, self::$zhiyuApiKey, $time),
            'templateId' => $templateId,
            'singerId' => 'msnj097ei53C559d',
            'mobile'=>implode(',', $to),
        );
        $postData = json_encode($data);
        $postUrl = self::$baseurl.'/Account/'.self::$apiAccount.'/sms/batchSureTempalteSend';
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $result = curl_exec($ch);//运行curl
        curl_close($ch);

        return $result;
    }


    //获得账户
    protected function getUser($ch, $apikey)
    {
        curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v1/user/get.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('apikey' => $apikey)));
        return curl_exec($ch);
    }

    /**
     * 智语短信平台接入，url和数据组装
     * 短信
     * 2017-5-26 longbijia
     * @param $ch
     * @param $data
     * @return mixed
     */
    protected function zhiyusend($ch, $data)
    {
        curl_setopt($ch, CURLOPT_URL, self::$baseurl.'/Account/'.self::$apiAccount.'/sms/matchTemplateSend');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        return curl_exec($ch);
    }

    /*
     * 智语- url
     * 语音
     * time: 2017-6-2 longbijia
     */
    protected function zhiyuVoiceSend($ch, $data)
    {
        curl_setopt ($ch, CURLOPT_URL, self::$baseurl.'/Account/'.self::$apiAccount.'/voiceVerify/call/');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        return curl_exec($ch);
    }

    /**
     * md5签名
     * time: 2017-6-2 longbijia
     * @param $apiAccount
     * @param $apikey
     * @param $time
     * @return string
     */
    protected function getMd5Sign($apiAccount,$apikey,$time)
    {
        return md5($apiAccount.$apikey.$time);
    }

    /**
     * 取时间戳，精确到毫秒
     * time: 2017-6-2 longbijia
     * @return float
     */
    protected function microtime_float(){
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    protected function request_post($url = '', $post_data = array()) {
        if (empty($url) || empty($post_data)) {
            return false;
        }

        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }

    /**
     * @param $url
     * @param string $post
     * @param string $action
     * @param string $cookie
     * @param int $returnCookie
     * @return mixed
     */
    public static function Request_http($url,$post='',$action='json',$cookie='', $returnCookie=0){
        $ch = curl_init();

        //设置头文件的信息作为数据流输出
        if($action == 'json'){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:*/*;charset=utf-8', 'Content-Type:application/json','charset=utf-8'));
        }
        else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
        }

        # 设置返回结果为流 (设置获取的信息以文件流的形式返回，而不是直接输出).
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        # 设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        # 设置通信方式
        if($post)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            # 设置验证方式
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt ($ch, CURLOPT_URL, $url);

        $data = curl_exec($ch);
        curl_close($ch);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            if($action='xml')
            {
                return simplexml_load_string($data);
            }
            else{
                return json_decode($data,true);
            }

        }
    }




}
