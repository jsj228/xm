<?php
class Tool_YunMessage {

    // 云片网
    private static $apikey = "45fde38e6d6ad5f95d2cc12005724718"; //修改为您的apikey(https://www.yunpian.com)登陆官网后获取

    /**
     *云片网短信平台接入， 发送请求
     * time : 2017-5-26
     * @param $action
     * @param string $mobile
     * @param string $text
     * @param string $sign
     * @return mixed
     */
    static function yunsentmessage($type,$mo,$msg)
    {
        header("Content-Type:text/html;charset=utf-8");
        $mobile = $mo; //请用自己的手机号代替
        $text = $msg;// "【云片网】您的验证码是1234";
        $ch = curl_init();

        /* 设置验证方式 */
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
        /* 设置返回结果为流 */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        /* 设置超时时间*/
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        /* 设置通信方式 */
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $yunmo=new Tool_YunMessage();
        switch ($type) {
            case 'g_send':
                // 国际发送短信
                //$mobile= urlencode('+60124414888');
                $data = array('text' => $text, 'apikey' => self::$apikey, 'mobile' => $mobile);
                //show($data);
                $json_data = $yunmo->send($ch, $data);
                curl_close($ch);
                $array = json_decode($json_data, true);
                return $array;
                break;

            case 'voice':
                // 发送语音验证码
                $data = array('code' => $text, 'apikey' => self::$apikey, 'mobile' => $mobile);
                $json_data = $yunmo->voice_send($ch, $data);
                curl_close($ch);
                $array = json_decode($json_data, true);
                return $array;
                break;
            default:
                # code...
                break;
        }

    }

    protected function send($ch, $data)
    {
        curl_setopt($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $ss = "Curl error--$error";
        $json=json_encode(array('code' => 100, 'msg' => $ss));
      if($result !== false){
          return $result;
      }else{
          return $json;
      }

    }



protected function voice_send($ch, $data)
    {
        curl_setopt($ch, CURLOPT_URL, 'http://voice.yunpian.com/v2/voice/send.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $ss = "Curl error--$error";
        $json = json_encode(array('code' => 100, 'msg' => $ss));
        if ($result !== false) {
            return $result;
        } else {
            return $json;
        }
    }

    static function Template($code,$type=1)
    {
        if ($type == 1) {//注册或登录
            $message = "【DOBI】Apply for registration or login:$code, please confirm that it is your own operation!";
            return $message;
        } else if($type ==2) {//通用验证码
            $message = "【DOBI】Your verfication code is:$code.";
            return $message;
        } else if ($type == 3) {//找回密码
            $message = "【DOBI】Your verfication code is:$code.You are applying for password recovery.This verification code is valid for 5 minutes.";
            return $message;
        } else if ($type == 4) {//實名成功
            $message = "【DOBI】Dear user:Congratulations! you've successfully passed the real name authentication.";
            return $message;
        } else if ($type == 5) {//實名失敗
            $message = "【DOBI】Dear user: I'm sorry! Your real-name authentication failed, please re-upload the information.";
            return $message;
        }
    }
}
