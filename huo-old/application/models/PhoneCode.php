<?php
class PhoneCodeModel extends Orm_Base{
	public $table = 'phone_code';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'code' => array('type' => "int(6) unsigned", 'comment' => ''),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'message' => array('type' => "varchar(255) unsigned", 'comment' => 'UID'),
		'email' => array('type' => "char(35) unsigned", 'comment' => ''),
		'mo' => array('type' => "char(11) unsigned", 'comment' => 'UID'),
		'email' => array('type' => "char(35) unsigned", 'comment' => 'UID'),
		'action' => array('type' => "tinyint(1) unsigned", 'comment' => '1:rmb转出,2ybc转出,3绿色通道'),
		'aid' => array('type' => "int(11) unsigned", 'comment' => ''),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => ''),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => ''),
		'utime' => array('type' => "int(11) unsigned", 'comment' => ''),
		'area' => array('type' => "char(11) unsigned", 'comment' => '')
	);
	public $pk = 'id';

	public static function sendCode($user, $aid, $area='+86', $num = 0){
		$area = trim($area);
		$code = rand(100000,999999);
		$data = array('code'=>$code, 'action'=>$aid, 'status'=>0,'ctime'=>$_SERVER['REQUEST_TIME']);
		$pc = new PhoneCodeModel();
		if($pc->fRow("select * from {$pc->table} where mo={$user['mo']} and action={$aid} and area='$area' and status=0")){
			$pc->exec("update {$pc->table} set status=2,utime={$_SERVER['REQUEST_TIME']} where mo={$user['mo']} and area='$area' and action={$aid} and status=0");
		}
		if(in_array($area, array('+86', '+852', '+853', '+886'))) {//国内短信
			$message = '';
			if ($area == '+86') {
				$sign = '';//智语
			} else {
				$sign = '【DOBI多比】';//云片
			}
		/*	if (LANG == 'cn') {//中文签名
				if ($area == '+86') {
					$sign = '【多比】';//智语
				} else {
					$sign = '【DOBI多比】';//云片
				}
			} else {//英文签名
				$sign = '【DOBI】';
			}*/
			switch ($aid) {
				case 7:
					$data['message'] = self::selectmessage('cn', $aid, $code, $sign,'');
					$data['action'] = 7;//通用短信
					$data['area'] = $area;//区号
					break;
				case 8:
					$data['message'] = self::selectmessage('cn', $aid, $code, $sign,'');
					$data['action'] = 8;//语音
					$data['area'] = $area;//区号
					break;
			}

		}else{//国际短信
			switch ($aid) {
				case 7:
					$data['message'] = Tool_YunMessage::Template($code, 2);//1注册或登录 2通用模板
					$data['action'] = 7;//通用短信
					$data['area'] = $area;//区号
					break;
				case 8:
					$data['message'] = Tool_YunMessage::Template($code, 2);//1注册或登录 2通用模板
					$data['action'] = 8;//语音
					$data['area'] = $area;//区号
					break;
			}

		}
		$data['mo'] = $user['mo'];
		$mo = $user['mo'];
		$result = [];
		// 智语
		if($user['mo']){
            if($id = $pc->insert($data)){
                if ($area == '+86') {//国内短信
                    if ($aid == 8) {
                        $returnMsg = Tool_Message::zhiyurun('voice_send', $data['mo'], $code);
                        $code = array();
                        if ($returnMsg['desc'] == 'OK' || $returnMsg['status'] == '0') {
                            $result = '200';
                        }
                    } else {
                        $data['mo'] = '86'.$data['mo'];
                        if(MOBILE_CODE){
                            $returnMsg = Tool_SmsMeilian::sendSMS($data['mo'], $data['message']);
                            if(strpos($returnMsg,"success")>-1) {
                                $result = ['code'=>200];
                            }
                        }else{
                            $result = ['code'=>'200','msg'=>'发送成功，您的验证码为'.$code];
                        }
                    }
                }else{
                    $phone=$area. $data['mo'];
                    $returnMsg = Tool_YunMessage::yunsentmessage('g_send', $phone, $data['message']);//云片网
                    if ($returnMsg['code'] == 2) {//云片网返回手机格式错误
                        $tResult = array('status' => 0, 'msg' => $GLOBALS['MSG']['YUN_SEND_FAIL'], 'data' => 'mo');//輸入號碼與歸屬地不匹配
                        header('Content-Type:application/json; charset=utf-8');
                        exit(json_encode($tResult));
                    }
                    if ($returnMsg['code'] == '0') {
                        $result = '200';
                    }
                }
            }

			if($result['code']=='200')
			{
				$pc->insert($data);
			}

		}

		return $result;
	}
	//发送邮件
	public static function sendemail($email, $aid)
	{
		$code = rand(100000, 999999);
		$data = array('code' => $code, 'action' => $aid, 'status' => 0, 'ctime' => $_SERVER['REQUEST_TIME']);
		$pc = new PhoneCodeModel();
		if ($pc->fRow("select * from {$pc->table} where email='$email' and action={$aid} and status=0")) {
			$pc->exec("update {$pc->table} set status=2,utime={$_SERVER['REQUEST_TIME']} where email='$email' and action={$aid} and status=0");
		}

		if (LANG == 'cn') {//中文签名
			$sign = '【多比】';//智语
		} else {//英文签名
			$sign = '【DOBI】';
		}
		switch ($aid) {
			case 1:
				$data['message'] = self::selectmessage(LANG, $aid, $code, $sign, '');
				$data['action'] = 1;//注册
				break;
			case 7:
				$data['message'] = self::selectmessage(LANG, $aid, $code, $sign, '');
				$data['action'] = 7;//通用短信
				break;
			case 11:
				$data['message'] = self::selectmessage(LANG, $aid, $code, $sign, '');
				$data['action'] = 11;//邮件找回密码
				break;
		}
		$data['email'] = $email;
		if($pc->insert($data)){//插入数据
			$sent = Tool_Fnc::mailto($email, $GLOBALS['MSG']['EMAIL_TITLE'], $data['message']);//多比驗證碼
			if ($sent) {
			return '200';
			} else {//发送失败
				return 0;
			}
		}else{
			return 0;
		}

	}
	public static function getCode($user){
		$pc = new PhoneCodeModel();
		if($code = $pc->fRow("select * from {$pc->table} where uid={$user['uid']} and status=0 order by id desc")){
			if($code['ctime'] + PHONE_TIME > $_SERVER['REQUEST_TIME']){
				$code['time'] = PHONE_TIME-($_SERVER['REQUEST_TIME']-$code['ctime']);
				return $code;
			}
		}
		return false;
	}
	public static function verifiCode($user, $aid, $c,$area){
		$area = trim($area);
		$pc = new PhoneCodeModel();
		if($code = $pc->fRow("select * from {$pc->table} where mo={$user['mo']} and code={$c} and area='$area' and action={$aid} and status=0 order by id desc")){
//		    Tool_Fnc::ajaxMsg($code,1,1);
			if($code['ctime'] + 300 < time()){
				return false;
			}
			if($pc->exec("update {$pc->table} set status=1,utime={$_SERVER['REQUEST_TIME']} where id={$code['id']}")){
				return true;
			}
		}
        Tool_Fnc::ajaxMsg($pc->getLastSql(),0,1);
		return false;
	}

	public static function updateCode($user, $aid, $id){
		$pc = new PhoneCodeModel();
		if($code = $pc->fRow("select * from {$pc->table} where action={$aid} and status=1 order by id desc")){
			if($pc->exec("update {$pc->table} set aid={$id},utime={$_SERVER['REQUEST_TIME']} where id={$code['id']}")){
				return true;
			}
		}
		return false;
	}

	public static function verifiTime($user, $aid){
		$pc = new PhoneCodeModel();
		if($code = $pc->fRow("select * from {$pc->table} where uid={$user['uid']} and action={$aid} and status=0 order by id desc")){
			if($code['ctime'] + 40 < time()){
				return true;
			}
		}else{
			return true;
		}
		return false;
	}

	public function voiceVerify($verifyCode,$to,$playTimes = 3,$displayNum = '',$respUrl = ''){
		$err = array('Code'=>0, 'Msg'=>'');
		$accountSid= '8a48b55147aab17f0147aac4c2ed0008';    //主账号
		$accountToken= 'b9a2c5e5aad149b98b61ee9559501363';  //主账号Token
		$appId='aaf98f8947ae19300147af8e50150446';          //应用ID
		$serverIP='app.cloopen.com';                        //请求地址，格式如下，不需要写https://
		$serverPort='8883';                                 //请求端口
		$softVersion='2013-12-26';                          //REST版本号
		$rest = new CcprestsdkModel($serverIP,$serverPort,$softVersion);    //初始化 REST SDK
		$rest->setAccount($accountSid,$accountToken);
		$rest->setAppId($appId);

		//调用语音/文字验证码接口
		if(!empty($displayNum)){
			$result = $rest->voiceVerify($verifyCode,$playTimes,$to,$displayNum,$respUrl);
		}else{
			$result = $rest->sendTemplateSMS($to,$verifyCode,1);
		}
		if($result == NULL ) {
			$err['Code'] = 'yuntong'; $err['Msg'] = 'yuntong result error'; exit(json_encode($err));
		}
		if($result->statusCode!=0) {
			$err['Code'] = $result->statusCode; $err['Msg'] = $result->statusMsg; exit(json_encode($err));
		} else{
			return 1;
		}
	}


	public static function sendregCode($phone, $aid, $area='+86', $num = 0){
		$area = trim($area);
		$code = rand(100000,999999);
		$data = array('code'=>$code, 'mo'=>$phone, 'action'=>$aid, 'status'=>0,'ctime'=>$_SERVER['REQUEST_TIME']);
		$pc = new PhoneCodeModel();
		if($pc->fRow("select * from {$pc->table} where mo={$phone} and area='$area' and action={$aid} and status=0")){
			$pc->exec("update {$pc->table} set status=2,utime={$_SERVER['REQUEST_TIME']} where mo={$phone} and action={$aid} and area='$area' and status=0");
		}
		if(in_array($area, array('+86','+852','+853','+886'))) {//国内
			$message = '';
			if ($area == '+86') {
				$sign = '';//智语
			} else {
				$sign = '【DOBI多比】';//云片
			}
		/*	if (LANG == 'cn') {//中文签名
				if ($area == '+86') {
					$sign = '【多比】';//智语
				}else{
					$sign = '【DOBI多比】';//云片
				}
			} else {//英文签名
				$sign = '【DOBI】';
			}*/
			switch ($aid) {
				case 1:
					$data['message']=self::selectmessage('cn', $aid, $code, $sign, $phone);
					$data['action'] = 1;
					$data['area'] = $area;
					break;
				case 7:
					$data['message'] = self::selectmessage('cn', $aid, $code, $sign, $phone);
					$data['action'] = 7;
					$data['area'] = $area;
					break;
				case 11:
					$data['message'] = self::selectmessage('cn', $aid, $code, $sign, $phone);
					$data['action'] = 11;
					$data['area'] = $area;
					break;
			}

		}else{//国外
			$message = '';
			switch ($aid) {
				case 1:
					$data['message'] = Tool_YunMessage::Template($code, 1);//1注册或登录 2通用模板
					//$message = '（您好:'.$phone.'申请注册或登录，请确认是本人操作），本次验证码5分钟内有效。';
					$data['action'] = 1;
					$data['area'] = $area;
					break;
				case 7:
					$data['message'] = Tool_YunMessage::Template($code, 2);//1注册或登录 2通用模板
					$data['action'] = 7;
					$data['area'] = $area;
					break;
				case 11:
					$data['message'] = Tool_YunMessage::Template($code, 3);//1注册或登录 2通用模板 3找回密码
					//	$message = '（您好:'.$phone.'申请找回密码，请确认是本人操作），本次验证码5分钟内有效。';
					$data['action'] = 11;
					$data['area'] = $area;
					break;
			}
		}
		$data['mo'] = $phone;
		// 智语 发生执行动作
		if($phone){
			if($id = $pc->insert($data)){
				if($area=='+86') {//国内
                    $data['mo'] = '86'.$phone;
                    if(MOBILE_CODE){
                        $returnMsg = Tool_SmsMeilian::sendSMS($data['mo'],$data['message']);
                        if(strpos($returnMsg,"success")>-1) {
                            return ['code'=>200];
                        }
                    }else{
                        return ['code'=>'200','msg'=>'发送成功，您的验证码为'.$code];
                    }
                    return 0;
				}
				else{//国际
					$phone = $area . $data['mo'];
					$returnMsg = Tool_YunMessage::yunsentmessage('g_send', $phone, $data['message']);//云片网
					if($returnMsg['code']==2){//云片网返回手机格式错误
						$tResult = array('status' => 0, 'msg' => $GLOBALS['MSG']['YUN_SEND_FAIL'], 'data' => 'mo');//輸入號碼與歸屬地不匹配
						header('Content-Type:application/json; charset=utf-8');
						exit(json_encode($tResult));
					}
					if ($returnMsg['code'] == '0') {
						return  '200';
					}
					return 0;
				}
			}
		}
		return $data['message'];
	}


	public static function sendregCodeyujing($phone, $aid, $name='人民币', $num = 0,$datas=array()){
		$code = rand(100000,999999);
		$data = array(
			'code'=>$code,
			'mo'=>$phone,
			'action'=>$aid,
			'status'=>0,
			'ctime'=>$_SERVER['REQUEST_TIME'],
			'wallet_over' => $datas['wallet_over'],
			'user_over' => $datas['user_over'],
			'coin' => $datas['coin'],
			'rate' => $datas['rate']
		);
		$pc = new PhoneCodeModel();
		if($pc->fRow("select * from `phone_yujing` where mo={$phone} and action={$aid} and status=0")){
			$pc->exec("update `phone_yujing` set status=2,utime={$_SERVER['REQUEST_TIME']} where mo={$phone} and action={$aid} and status=0");
		}

		$message = '';
		switch($aid){
			case 1:
				$message = '（您好:'.$phone.'申请注册，请确认是本人操作），本次验证码5分钟内有效。';
				$data['action'] = 1;
				break;
			case 10:
				$rates = sprintf("%.2f",  $datas['rate']*100 ).'%';
				$message = '报警提醒！'.$datas['coin'].'差值比例:'.$rates.',钱包余额:'.$datas['wallet_over'].',用户余额:'.$datas['user_over'].'。';
				$data['action'] = 10;
				break;
			case 12:
//				$rates = sprintf("%.2f",  $datas['rate']*100 ).'%';
				$message = '报警提醒！'.$datas['coin'].'差值比例:'.$datas['rate'].',钱包余额:'.$datas['wallet_over'].',用户余额:'.$datas['user_over'].'。';
				$data['action'] = 12;
				break;
		}
		if($aid ==10||$aid ==12 ){
			$data['message'] = "【多比】".$message;
		}else{
			$data['message'] = $message;
		}
//		echo $data['message']."\n";
		$data['mo'] = $phone;

		$values = "(".$data['mo'].", ".$data['action'].",".$data['status'].",".$data['ctime'].",".$data['wallet_over'].",".$data['user_over'].",'".$data['coin']."',".$data['rate'].",'".$data['message']."')";

		$sql = "insert into `phone_yujing` (`mo`,`action`,`status`,`ctime`,`wallet_over`,`user_over`,`coin`,`rate`,`message`) VALUES ".$values;
		// 智语 发生执行动作

		if($phone){
			if($id = $pc->exec($sql)){
				$returnMsg = Tool_Message::zhiyurun('send', $data['mo'], $data['message']);
				if($returnMsg['desc'] == '成功'|| $returnMsg['status']=='0')
				{
					return '200';
				}
				return $returnMsg;
			}
		}
		return $data['message'];
	}

//60秒内发送频繁
	public static function regverifiTime($phone, $aid,$area){
		$pc = new PhoneCodeModel();
		if($code = $pc->fRow("select * from {$pc->table} where mo={$phone} and action={$aid} and area='$area' and status=0 order by id desc")){
			if($code['ctime'] + 40 < time()){
				return true;
			}
		}else{
			return true;
		}
		return false;
	}
	//邮箱验证code
	public static function checkemailCode($email, $aid, $c)
	{
		$pc = new PhoneCodeModel();
		if ($code = $pc->fRow("select * from {$pc->table} where email='$email' and code={$c} and action={$aid} and status=0 order by id desc")) {
			if ($code['ctime'] + 300 < time()) {
				return false;
			}
			if ($pc->exec("update {$pc->table} set status=1,utime={$_SERVER['REQUEST_TIME']} where id={$code['id']}")) {
				return true;
			}
		}
		return false;
	}
	public static function verifiregCode($phone, $aid, $c,$area){
		$pc = new PhoneCodeModel();
		if($code = $pc->fRow("select * from {$pc->table} where mo={$phone} and code={$c} and action={$aid} and area='$area' and status=0 order by id desc")){
			if($code['ctime'] + 300 < time()){
				return false;
			}
			if($pc->exec("update {$pc->table} set status=1,utime={$_SERVER['REQUEST_TIME']} where id={$code['id']}")){
				return true;
			}
		}
		return false;
	}

	public static function selectmessage($type,$aid,$code,$sign='',$phone)
	{
		switch ($type) {
			case 'cn':
				switch ($aid) {
					case 1:
						$mess = "{$sign}驗證碼{$code}（您好:{$phone}申請註冊或登錄，請確認是本人操作），本次驗證碼5分鐘內有效。";
						break;
					case 11:
						$mess = "{$sign}驗證碼{$code}（您好:{$phone}申請找回密碼，請確認是本人操作），本次驗證碼5分鐘內有效。";
						break;
					case 7:
						$mess = "{$sign}您的驗證碼是{$code}，如非本人操作，請忽略本短信。";
						break;
					case 8:
						$mess = "{$sign}您的驗證碼是{$code}，如非本人操作，請忽略本短信。";
						break;
				}
				break;
			case 'en':
				switch ($aid) {
					case 1:
						$mess = "{$sign}Apply for registration or login:{$code}, please confirm that it is your own operation!";
						break;
					case 11:
						$mess = "{$sign}Your verfication code is:{$code}.";
						break;
					case 7:
						$mess = "{$sign}Your verfication code is:{$code}.";
						break;
					case 8:
						$mess = "{$sign}Your verfication code is:{$code}.";
						break;
				}
				break;
		}
	return $mess;
	}
}
