<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/19
 * Time: 19:00
 */



    /* CZW Framework  (C) jhdxr
     * This is NOT a freeware, use is subject to license terms
     * $Id: class_translate.php 514 2010-10-20 12:00:41Z chuzhaowei $
    */
    /**
     * PHP语言翻译类，基于google translate实现。
     *
     * @author 江湖大虾仁
     */
class Tool_Google{
    private static $var = array('from' => '', 'to' => '', 'text' =>'');
    private static $google_API_URL = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&langpair={{from}}|{{to}}&q={{text}}';
    private static $maxlen = 480;

    /**
     * 参数设置。例如setVar('from','zh-CN');代表设定从简体中文翻译成别的语言。
     * @param <string> $option: 待设置的项目，可以是源语言(from)或目标语言(to)
     * @param <string> $value: 设置的值
     */
    public function setVar($option, $value){
        if(in_array($option = strtolower($option), array('from','to','text'))){
            $this->var[$option] = $value;
        }
    }

    /**
     * 语言设置。例如setLang('en','zh-CN');代表从英文翻译成简体中文。
     * @param <string> $from: 源语言
     * @param <string> $to: 目标语言
     */
    public function setLang($from ,$to){
        $this->var['from'] = $from;
        $this->var['to'] = $to;
    }

    /**
     * （静态方法）翻译指定的文字。
     * @param <string> $text: 待翻译的文本
     * @param <string> $from: 源语言，如果为空代表使用设置的源语言（实例化后可用）。
     * @param <string> $to: 目标语言，如果为空代表使用设置的目标语言（实例化后可用）。
     */
    public static function translate($text, $from = '', $to = ''){
        $from = $from ? $from : self::$var['from'];
        $to = $to ? $to : self::$var['to'];
        $return = '';
        $isErr = false;


//        echo $from;echo $to;die;
        do{
            $text2 = self::_substr($text, 0, self::$maxlen);
            $url = str_replace(array('{{text}}','{{from}}','{{to}}'),array(urlencode($text2),$from,$to),self::$google_API_URL);
            //echo $url."<br>";
            $_return = json_decode(self::_fsockopen($url));
            if($_return->responseStatus == 200 || $isErr){
                !$isErr && $return .= $_return->responseData->translatedText;
                $text = substr($text, strlen($text2));
                $isErr = false;
            }else{
                $isErr = true;
            }
        }while($text);
        return $return;
    }

    /**
     * （静态方法）检测PHP环境是否能运行此类
     * @return <bool>如果能运行则返回true，否则返回缺少支持的函数名
     */
    public static function canRun(){
        $needFunctionArr = array('mb_substr','json_decode');
        foreach($needFunctionArr as $functionName){
            if(!function_exists($functionName)) return $functionName;
        }
        return true;
    }

    private static function _substr($text, $start, $len){
        $text = mb_substr($text, 0, $len);
        $splitiArr = array('。','.','；',';');//设定可以作为分段的标志
        $pos = $len + 1;
        foreach($splitiArr as $spliti){
            $_pos = strrpos($text, $spliti);
            $pos = $pos > $_pos ? $_pos : $pos;
        }
        $pos = $pos > $len ? $len : $len - $pos;
        return $pos >= $len ? $text : mb_substr($text, 0, $pos);
    }

    private static function _fsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
        //_fscokopen函数来自于Discuz!X 1.5
        $return = '';
        $matches = parse_url($url);
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        if($post) {
            $out = "POST $path HTTP/1.0rn";
            $out .= "Accept: */*rn";
            //$out .= "Accept-Language: zh-cnrn";
            $out .= "Content-Type: application/x-www-form-urlencodedrn";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]rn";
            $out .= "Host: $hostrn";
            $out .= 'Content-Length: '.strlen($post)."rn";
            $out .= "Connection: Closern";
            $out .= "Cache-Control: no-cachern";
            $out .= "Cookie: $cookiernrn";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0rn";
            $out .= "Accept: */*rn";
            //$out .= "Accept-Language: zh-cnrn";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]rn";
            $out .= "Host: $hostrn";
            $out .= "Connection: Closern";
            $out .= "Cookie: $cookiernrn";
        }

        if(function_exists('fsockopen')) {
            $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        } elseif(function_exists('pfsockopen')) {
            $fp = @pfsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        } else {
            $fp = '';
        }

        if(!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if(!$status['timed_out']) {
                while (!feof($fp)) {
                    if(($header = @fgets($fp)) && ($header == "rn" ||  $header == "n")) {
                        break;
                    }
                }

                $stop = false;
                while(!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);
            return $return;
        }
    }

}