<?php
/*
* 机器人 base
*/
class App_BaseRobotController extends Ctrl_Base
{

	protected $token;

	public function init()
	{
		parent::init();
		if(isset($_POST['token']))
		{
			$this->token = $_POST['token'];
		}
	}

	/*
	* 会话认证
	*/
	protected function auth($token='')
	{
		$token = $token?:$this->token;
		if($us = Cache_Redis::instance('token')->get($token))
		{
			list($uid, $skey) = explode(',', $us);
		
			if($uid && $info = Cache_Redis::instance('token')->get($uid))
			{
				$this->mCurUser = json_decode($info, true);
				if(Cache_Redis::instance('token')->hGet('UPDATE_UID', $uid))
				{
					$user = UserModel::getInstance()->fRow($uid);
					$this->mCurUser = array_merge($this->mCurUser, $user);
					Cache_Redis::instance('token')->set($user['uid'], json_encode($this->mCurUser));
					Cache_Redis::instance('token')->hDel('UPDATE_UID', $uid);
				}
				
				$this->verifySign($skey?:$this->mCurUser['skey']);
				//顺延token期限
				Cache_Redis::instance('token')->expire($token, 86400);
				return $this->mCurUser;
			}
		}
		$this->ajax('认证失败');
	}


	/*
	* 校验签名
	*/
	protected function verifySign($sKey)
	{
		if(isset($_POST['sign'], $_POST['token'], $_POST['timestamp']))
		{

			if(abs(time()-$_POST['timestamp'])>60)
			{
				$this->ajax('签名已过期');
			}

			$token = $_POST['token'];
			$postSign = $_POST['sign'];
			$data = $_POST;

			//常规参数，剔除token 和 sign
			unset($data['token'], $data['sign'], $data['skey']);

			//按key升序排序
			ksort($data);
			//将所有参数的键值连接成一个字符串
			$dataStr = '';
			foreach ($data as $k=>$v) 
			{
			    $dataStr .= $k.$v;
			}

			//将三者按长度升序,长度一样按ASCII升序
			$strMap = array(
			    $token => strlen($token),
			    $dataStr => strlen($dataStr),
			    $sKey => strlen($sKey),
			);

			uasort($strMap, function($a, $b) {
			    if($a == $b)
			    {
			        return 0;
			    }
			    $aLen=strLen($a);
			    $bLen=strLen($b);
			    if($aLen == $bLen)
			    {
			        $tArr = [$a, $b];
			        sort($tArr);
			        return $tArr[0] == $a?-1:1;
			    }
			    return $aLen > $bLen;
			} );

			//前两个组合成一个字符串
			$i = 0;
			$a = '';
			$b = '';
			foreach ($strMap as $k => $v) 
			{
			    if($i++<2)
			    {
			        $a .= $k;
			    }
			    else
			    {
			        $b = $k;
			    }
			}


			$aLen = strlen($a);
			$bLen = strlen($b);
			if($aLen < $bLen)
			{
			    $minLen = $aLen;
			    $long = $b;
			    $short = $a;
			}
			else
			{
			    $minLen = $bLen;
			    $long = $a;
			    $short = $b;
			}

			//两个字符串左对齐交叉合并（短的先）
			$sign = '';
			for($i=0; $i<$minLen; $i++)
			{
			    $sign .= $short{$i}.$long{$i};

			}

			$sign = md5($sign . substr($long, $i));

			if($postSign == $sign)
			{
				return true;
			}
		}
		
		$this->ajax('无效签名');
	}



	protected function log($type, $data, $field1='')
	{
		if(is_array($data))
		{
			$data = json_encode($data);
		}
		$appRobotLogMo = AppRobotLogModel::getInstance();
		$appRobotLogMo->save(array(
			'uid'=>(int)$this->mCurUser['uid'],
			'type'=>$type,
			'data'=>$data,
			'created'=>time(),
			'createip'=>Tool_Fnc::realip(),
			'field1'=>$field1,
		));

	}

	/**
	 * AJAX返回
	 */
	protected function ajax($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		//不成功结果记录到log
		if($pStatus!=1)
		{
			$data = array(
				'uid' =>isset($this->mCurUser['uid'])?$this->mCurUser['uid']:0,
		        'req_url' =>REDIRECT_URL,
		        'param' =>isset($_POST)?json_encode($_POST):$_GET,
		        'response' =>sprintf('status:%d, msg:%s, data:%s', $pStatus, $pMsg, json_encode($pData)),
		        'sql' => Orm_Base::getInstance()->getLastSql(),
		        'session' =>isset($_SESSION)?json_encode($_SESSION):'',
		        'req_time' =>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
		        'req_ip' =>Tool_Fnc::realip(),
		        'created' =>date('Y-m-d H:i:s'),
			);
			Cache_Redis::instance()->lpush('sqlQueue', json_encode(array('model'=>'ReqFailedLogModel', 'data'=>$data)));
		}
		Tool_Fnc::ajaxMsg($pMsg, $pStatus, $pData, $pType);
	}

}