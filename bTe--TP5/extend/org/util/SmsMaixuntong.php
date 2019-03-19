<?php

namespace org\util;

class SmsMaixuntong {

    var $userId	= '800004'; //国内线路独有
    var $password	= 'rHbze4';
    var $account	= 'admin';
    var $product = 0; //国际线路独有


    public function __construct($userId=null, $account=null, $password=null,$product=0) {
        if ($userId) $this->userId = $userId;
        if ($password) $this->password = $password;
        if ($account) $this->account = $account;
        if ($product) $this->product = $product;
    }

    public function xml2array($contents, $get_attributes=1) {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            return array();
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
        xml_parse_into_struct( $parser, $contents, $xml_values );
        xml_parser_free( $parser );

        if(!$xml_values) return;//Hmm...

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array;

        //Go through the tags.
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = '';
            if($get_attributes) {//The second argument of the function decides this.
                $result = array();
                if(isset($value)) $result['value'] = $value;

                //Set the attributes too.
                if(isset($attributes)) {
                    foreach($attributes as $attr => $val) {
                        if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                        /**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
                    }
                }
            } elseif(isset($value)) {
                $result = $value;
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;

                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];

                } else { //There was another element with the same tag name
                    if(isset($current[$tag][0])) {
                        array_push($current[$tag], $result);
                    } else {
                        $current[$tag] = array($current[$tag],$result);
                    }
                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;

                } else { //If taken, put all things inside a list(array)
                    if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
                        or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                        array_push($current[$tag],$result); // ...push the new element into that array.
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    }

    function data_encode($data, $keyprefix = "", $keypostfix = "") {
        assert( is_array($data) );
        $vars=null;
        foreach($data as $key=>$value) {
            if(is_array($value)) $vars .= SmsMaixuntong::data_encode($value, $keyprefix.$key.$keypostfix.urlencode("["), urlencode("]"));
            else $vars .= $keyprefix.$key.$keypostfix."=".urlencode($value)."&";
        }
        return $vars;
    }


    function _curl_post($url, $vars) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不向网页输出 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);//POST请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, substr(SmsMaixuntong::data_encode($vars), 0, -1));//POST字段     
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );//启用时会汇报所有的信息     
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data)
            return $data;
        else
            return false;
    }


    //发送国内
    public function sendSMS($phones, $content, $postFixNumber=1, $sendTime='', $sendType=1) {
 
        $url = "http://113.106.16.55:8080/GateWay/Services.asmx/DirectSend";

        if(false !== stripos($phones,'+86')){
            if(false !== stripos($phones,'+8686')){
                $phones = str_replace('+8686','',$phones);
            }else{
                $phones = str_replace('+86','',$phones);
            }
        }else if(false !== stripos($phones,'+')){
            $phones = str_replace('+','',$phones);
        }
        $param = array('UserID'=>$this->userId, 'Account'=>$this->account, 'Password'=>$this->password,
            'Phones'=>$phones, 'Content'=>$content, 'SendTime'=>$sendTime, 'SendType'=>$sendType, 'PostFixNumber'=>$postFixNumber);
        $output = SmsMaixuntong::_curl_post($url, $param);

        $data = $this->xml2array($output);
        if ($data) {
            $ret = $data['ROOT'];
            $info = array('jobid'=>$ret['JobID']['value']
            );

            if ($ret['RetCode']['value'] == 'Sucess') {
                return array('errorno'=>0, 'info'=>$info);
            } else {
                if($ret['ErrPhones']['value']){
                    return array('errorno'=>-1, 'info'=>'非法手机号码');
                }else{
                    return array('errorno'=>-1, 'info'=>$ret['Message']['value']);
                }

            }
        }
        return array('errorno'=>-1, 'info'=>'发送短信请求失败');
    }

    //发送国际
    public function sendSMSINT($phones, $content,$needstatus=true,$resptype='json'){
        $url = 'http://www.weiwebs.cn/msg/HttpBatchSendSM';
        if(false !== stripos($phones,'+')){
            $phones = str_replace('+','',$phones);
        }
        $param = array('account'=>$this->account, 'pswd'=>$this->password,
            'mobile'=>$phones, 'msg'=>$content, 'needstatus'=>$needstatus, 'product'=>$this->product,'resptype'=>$resptype);
        $output = SmsMaixuntong::_curl_post($url, $param);
        $data = json_decode($output);
        if($data){

            if($data->result==0){
                return array('errorno'=>0);
            }else{
                return array('errorno'=>-1, 'info'=>$data->result);
            }
        }
        return array('errorno'=>-1, 'info'=>'发送短信请求失败');

    }

    
}
