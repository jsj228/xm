<?php
class Tool_Str{
	static function safestr($pStr, $pDefault=false){
		if(!$pStr = htmlspecialchars($pStr)){
			return $pDefault;
		}
		return $pStr;
	}

	/**
	 * 替换危险字符串
	 *
	 * @param str $pStr 危险字符
	 * @param array $pTrans 自定义替换规则
	 * @return str 安全字符
	 */
	static function filter($pStr, $pTrans=array()){
		$tTrans = array("'"=>'', '"'=>'', '`'=>'', '\\'=>'', '<'=>'＜', '>'=>'＞');
		return strtr(trim($pStr), array_merge($tTrans, $pTrans));
	}

	/**
	 * 获得 KEY 对应的 数组值
	 *
	 * @param array $pArr
	 * @param str $pKey
	 * @param str $pDefault
	 */
	static function arr2str($pArr, $pKey, $pDefault=''){
		return isset($pArr[$pKey])? $pArr[$pKey]: $pDefault;
	}

	/**
	 * ID 转为 图片文件路径
	 * @param int $pId
	 * @return str
	 */
	static function id2path($pId){
		$tPid = str_pad($pId, 9, 0, 0);
		return array(substr($tPid, 0, 3).'/'.substr($tPid, 3, 3).'/', substr($tPid, 6));
	}

	/**
	 * 格式化数字
	 * @param $pNum
	 * @param int $pLen
	 * @param int $pRule 规则 0:四舍五入, 1:全入, 2:全舍
	 * @param bool $effective 规则 flase:保留指定位数小数, true:保留有效小数（最终小数位会小于$pLen）
	 * @return int | float
	 */
	static function format($pNum, $pLen = 2, $pRule = 0, $effective=false){
		//处理科学计数法
		if(false !== stripos($pNum, "e")){  
	        $a = explode("e",strtolower($pNum));
	        $pNum = bcmul($a[0], bcpow(10, $a[1], $pLen+1), $pLen+1);
	    } 

		list($int, $dec) = explode('.', strval($pNum));
		if(isset($dec))
		{
			$returnNum = trim(intval($int).'.'.substr($dec, 0, $pLen), '-');
			if( ($pRule==0 && isset($dec{$pLen}) && $dec{$pLen}>4) || ($pRule==1 && substr($dec, $pLen)>0) )
			{
				$returnNum = bcadd($returnNum, '0.'.str_pad('', $pLen-1, 0).'1', $pLen);
			}
			if($pNum<0)
			{
				$returnNum = '-'.$returnNum;
			}
		}
		else
		{
			$returnNum = $pNum;
		}
	    
		
		#保留有效小数
		if($effective)
		{
			$returnNum = self::eftnum($returnNum);
		}

		return $returnNum;
	}


	/**
	 * 保留有效小数
	 * @param $number
	 * @return str
	 */
	static function eftnum($number)
	{
		return trim(preg_replace('/(\.\d*?)0+$/', '$1', $number), '.');
	}



	/**
	 * 生成key
     * 1 ybex转币
	 */
	static function generate_key($type, $uid, $randstr='Ybc!1900#$@'){
        if(!$type = intval($type)){
            return FALSE;
        }
        $result = '';
        switch($type){
            case 1:
                $result = md5($randstr.$uid.'uid2');
                break;
            default:
                break;
        }
        return $result;
	}

	 /**
     * 字符串截取，支持中文和其他编码
     * @param  [string]  $str     [字符串]
     * @param  integer $start   [起始位置]
     * @param  integer $length  [截取长度]
     * @param  string  $charset [字符串编码]
     * @param  boolean $suffix  [是否有省略号]
     * @return [type]           [description]
     */
    static function msubstr($str, $start=0, $length=15, $charset="utf-8", $suffix=true) 
    {
        if (function_exists("mb_substr")) {
            return mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            return iconv_substr($str,$start,$length,$charset);
        }
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
        if ($suffix) {
            return $slice."…";
        }
        return $slice;
    }

    /**
     * unicode 转 UTF8
     */
    static function unicode_to_utf8($unicode_str) {

	    return preg_replace_callback('/\\\\u[\w]{4}/i', function($m){
	        $utf8_str = '';
	        $code = intval(hexdec($m[0]));
	        //这里注意转换出来的code一定得是整形，这样才会正确的按位操作
	        $ord_1 = decbin(0xe0 | ($code >> 12));
	        $ord_2 = decbin(0x80 | (($code >> 6) & 0x3f));
	        $ord_3 = decbin(0x80 | ($code & 0x3f));
	        $utf8_str = chr(bindec($ord_1)) . chr(bindec($ord_2)) . chr(bindec($ord_3));
	        return $utf8_str;
	    }, $unicode_str);

	}

}
