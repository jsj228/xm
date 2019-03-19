<?php
class Tool_Fnc{

    //3333
    public static function httpRequest($url, $param=array(), $input_charset = '') {
        if (trim($input_charset) != '') {
            $url = $url."_input_charset=".$input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$param);// post传输数据
        $responseText = curl_exec($curl);
        curl_close($curl);
        return $responseText;
    }
	//curl
	public static function callInterfaceCommon($URL, $type, $params, $headers)
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $URL); //地址
		if ($headers != "") {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		} else {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		switch ($type) {
			case "GET" :
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				break;
			case "POST":
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				break;
			case "PUT" :
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				break;
			case "DELETE":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				break;
		}
		$file_contents = curl_exec($ch);//获得返回值
		curl_close($ch);

		return $file_contents;
	}
	/**
	 * 获取以父ID为KEY的分类
	 *
	 * @param int $pPid 父ID
	 * @param int $pMeId 输出一个类别
	 */
	public static function catdata($pPid = false, $pMeId = 0){
		static $datas = array();
		if(!$datas) foreach(Cache_Redis::hget('category') as $v1){
			$v1 = json_decode($v1, true);
			$datas[$v1['pid']][$v1['cid']] = $v1;
		}
		if(false === $pPid) return $datas;
		return $pMeId? $datas[$pPid][$pMeId]: $datas[$pPid];
	}

	/**
	 * 显示树状分类
	 * @param string $pBoxId 容器ID
	 * @param int $pPid 父ID (0:全部)
	 */
	public static function cattree($pBoxId, $pPid = 0){
		$tDatas = self::catdata(false, 0);
		echo '<select id="yaf_', $pBoxId, '" name="', $pBoxId, '">';
		if(false !== strpos(strtolower($_SERVER['REQUEST_URI']), 'manage')){
			echo '<option value="0">顶级</option>';
		}
		self::cattreeIterate($tDatas, $pPid, 0);
		echo '</select>';
	}

	/**
	 * cattree 迭代函数
	 *
	 * @param array $datas 分类数组
	 * @param int $i 层级
	 * @param int $count 占位符个数
	 */
	static function cattreeIterate(&$datas, $i, $count){
		if(isset($datas[$i])) foreach($datas[$i] as $v1){
			echo "<option value='{$v1['cid']}'", $i == 0? " class='option'": "", ">", str_repeat('　　', $count), $v1['name'], "</option>";
			self::cattreeIterate($datas, $v1['cid'], $count + 1);
		}
	}

	/**
	 * 真实IP
	 * @return string 用户IP
	 */
	static function realip(){
		foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $v1){
			if(isset($_SERVER[$v1])){
				$tIP = ($tPos = strpos($_SERVER[$v1], ','))? substr($_SERVER[$v1], 0, $tPos): $_SERVER[$v1];
				break;
			}
			if($tIP = getenv($v1)){
				$tIP = ($tPos = strpos($tIP, ','))? substr($tIP, 0, $tPos): $tIP;
				break;
			}
		}
		return $tIP;
	}


	/**
	 * 直连ip
	 * @return string 用户IP
	 */
	static function xRealIp(){
		foreach(array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $v1){
			if(isset($_SERVER[$v1])){
				$tIP = ($tPos = strpos($_SERVER[$v1], ','))? substr($_SERVER[$v1], 0, $tPos): $_SERVER[$v1];
				break;
			}
			if($tIP = getenv($v1)){
				$tIP = ($tPos = strpos($tIP, ','))? substr($tIP, 0, $tPos): $tIP;
				break;
			}
		}
		return $tIP;
	}

	/**
	 * 发送邮件
	 * @param $pAddress 地址
	 * @param $pSubject 标题
	 * @param $pBody 内容
	 */
	static function mailto($pAddress, $pSubject, $pBody, $pCcAddress = NULL){
		static $mail;

		if(!$mail){
			require preg_replace( '/Tool/' ,'' , dirname(__FILE__)) . 'Source/PHPMailer/PHPmailer.php';
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->CharSet = 'utf-8';
			$mail->SMTPAuth = true;
			$mail->Port = 25;

			$mail->Host = "smtp.huocoin.wang";
			$mail->From = "email@huocoin.wang";
			$mail->Username = "email@huocoin.wang";
			$mail->Password = "huocoin%123";
			$mail->FromName = "huocoin";
			$mail->IsHTML(true);
		}
		$mail->ClearAddresses();
		$mail->ClearCCs();
		$mail->ClearBCCs();
        if(is_array($pAddress)){
            foreach($pAddress as $v){
		        $mail->AddAddress($v);
            }
            unset($v);
        } else {
		    $mail->AddAddress($pAddress);
        }

		$pCcAddress && $mail->AddBCC($pCcAddress);
		$mail->Subject = $pSubject;
		$mail->MsgHTML(preg_replace('/\\\\/', '', $pBody));
		if($mail->Send()){
            return 1;
		}else{
			return $mail->ErrorInfo;
		}
	}

	/**
	 * 提示信息
	 * @param string $pMsg 信息
	 * @param bool $pUrl 跳转到
	 */
	static function showMsg($pMsg, $pUrl = false){
		is_array($pMsg) && $pMsg = join('\n', $pMsg);
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        if('.' == $pUrl){
            $pUrl = $_SERVER['REQUEST_URI'];
        }
		echo '<script type="text/javascript">';
		if($pMsg) echo "alert('$pMsg');";
        if($pUrl){
            echo "self.location='{$pUrl}'";
        }elseif(empty($_SERVER['HTTP_REFERER'])){
            echo 'window.history.back(-1);';
        }else{
            echo "self.location='{$_SERVER['HTTP_REFERER']}';";
        }
		exit('</script>');
	}

	/**
	 * AJAX返回
	 *
	 * @param string $pMsg 提示信息
	 * @param int $pStatus 返回状态
	 * @param mixed $pData 要返回的数据
	 * @param string $pStatus ajax返回类型
	 */
	static function ajaxMsg($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		# 信息
		$tResult = array('status' => $pStatus, 'msg' => $pMsg, 'data' => $pData);
		# 格式
		if(!DEBUG) ob_clean();
		switch ($pType) {
			case 'json':
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode($tResult));
			case 'xml':
				exit(xml_encode($tResult));
			case 'eval':
				exit($pData);
		}
	}

    /**
     * 邮件模版赋值
     *
     */
    static function emailTemplate($pData , $pTemplatename){

        $pDir = preg_replace('/library\/Tool/' , '' , dirname(__FILE__));

       // $pTemplatedir = $pDir . 'views/'.LANG.'/email_template/' . $pTemplatename . '.phtml';
        $pTemplatedir = $pDir . 'views/' . $pTemplatename . '.phtml';

        if(!is_file($pTemplatedir)){
            return false;
        }
        $pHtml = file_get_contents($pTemplatedir);

        $pKeys = array_keys($pData);
        if(!count($pKeys)){ return false;}

        foreach($pKeys as $pKey){
           $pHtml = preg_replace('/{'.$pKey.'}/' , $pData[$pKey] , $pHtml);
        }
        return $pHtml;
    }
	public static function isMobile(){
		static $isMobile;
		if(isset($isMobile))
		{
			return $isMobile;
		}

		$useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
		function CheckSubstrs($substrs,$text){
			foreach($substrs as $substr)
				if(false!==strpos($text,$substr)){
					return true;
				}
				return false;
		}
		$mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
		$mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod', 'iPad');

		$isMobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
				  CheckSubstrs($mobile_token_list,$useragent);

		return $isMobile;
    }

    /**
    * 浏览器友好的变量输出
    * @param mixed $var 变量
    * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
    * @param string $label 标签 默认为空
    * @param boolean $strict 是否严谨 默认为true
    * @return void|string
    */
    static function dump($var, $echo=true, $label=null, $strict=true) {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        }else
            return $output;
    }


    /**
    * 导出csv
    * @param array $header 标题
    * @param array $body 内容
    * @param array $option 其它选项
    * @param boolean complete 数据包是否完整
    */
    static function csv($header=array(), $body=array(), $option=array(), $complete=true)
    {
    	static $init = true;
    	static $fp;

    	if($init)
    	{
    		$init = false;
    		@ini_set('memory_limit', '20M');
    		header("Content-type:text/csv;");
	        header("Content-Disposition: attachment;filename=" .($option['filename']?:'导出数据'). date('YmdHi') . ".csv");
	        header('Cache-Control: max-age=0');

	  		ob_clean();
    		ob_start();
	  		$fp = fopen('php://output', 'a');
	  		foreach ($header as &$v)
	  		{
	  			$v = iconv('utf-8', 'gbk', $v);
	  		}
	  		unset($v);
    		fputcsv($fp, $header);

    	}


		foreach ($body as $row)
		{
			$row = explode(',', iconv('utf-8', 'gbk', implode(',', $row)));
	        fputcsv($fp, $row);
	    }
		ob_flush();
        flush();

        if($complete)
    	{
    		fclose($fp);
    		exit;
    	}

    }



    /**
    * 导出excel
    */
    static function excel($header=array(), $body=array(), $option=array())
    {
    	ob_clean();    //清除缓存
        @ini_set('memory_limit', '20M');//设置一下使用内存，由于数据量很大，不设置不行，默认的不够
        ob_start();//缓冲区开始

        require(dirname(__FILE__) . '/../PHPExcel/PHPExcel.php');

        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getActiveSheet()->freezePane('A1', 'ID');

  		$column = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  		foreach($header as $k=>$v)
  		{
  			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($column[$k].'1', $v);
  		}

  		$row = 2;
  		foreach ($body as $rowData)
  		{
  			$i = 0;
  			foreach($rowData as $v)
  			{
  				$objPHPExcel->setActiveSheetIndex(0)->setCellValue($column[$i++] . $row, $v);
  			}
  			$row++ ;
  		}

  		if($option['title'])
  		{
  			$objPHPExcel->getActiveSheet()->setTitle($option['title']);
  		}

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=" .($option['filename']?:'导出数据'). date('YmdHi') . ".xls");
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        exit;
    }


    /**
    * 警告通知
    */
    static function warning($msg, $interval=300)
	{
        if($msg)
        {
            $wiFile   = 'warningInterval.recode';
            $lock      = $wiFile . '.lock';//锁文件，解决并发问题
			$fp = fopen($lock, 'w+');
			flock($fp, LOCK_EX);

            $prevRecode = is_file($wiFile)?json_decode(file_get_contents($wiFile), true):'';

            $msgMd5   = md5($msg);
            $now      = time();
            if(!$prevRecode || !isset($prevRecode[$msgMd5]) || $now-$prevRecode[$msgMd5]>intval($interval))
            {
                //通知人配置文件
	        	$warning = file_get_contents(CONF_PATH.'Warning.json');
	        	$warning = json_decode(preg_replace('#//.+?\n#', '', $warning), true);
	        	if(isset($warning['email']) && is_array($warning['email']))
	        	{
	        		foreach($warning['email'] as $v)
	        		{
	        			$msg = sprintf('%s|%s', Yaf_Registry::get("config")->server->name?:$_SERVER['SSH_CONNECTION'], $msg);
	        			$r = Tool_Fnc::mailto($v , '【WARNING】'.mb_substr($msg, 0, 20, 'utf8') , $msg);
	                    if($r!=1)
	                    {
	                        Tool_Log::wlog(sprintf("邮件发送失败, %s", $msg), 'warningEmailFailed', true);
	                    }
	        		}
	        		$prevRecode[$msgMd5] = $now;
	        		file_put_contents($wiFile, json_encode($prevRecode));
	        		fwrite($fp, 1);
	        	}

            }

        	flock($fp, LOCK_UN);
			fclose($fp);
        }
        return true;
    }
}
