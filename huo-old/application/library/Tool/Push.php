<?php
class Tool_Push
{
	static protected $conn;

	static function getInstance()
	{
		//建立socket连接到推送端口
		if(!Yaf_Registry::get("config")->push['enabled'])
			return false;

		static $tryTimes = 0;

		if(!self::$conn || time()-self::$conn['time']>10)
		{
			self::$conn['handle'] and fclose(self::$conn['handle']);
			self::$conn = array(
				'handle'=>@stream_socket_client(Yaf_Registry::get("config")->push['subscribe']['host'], $errno, $errmsg, 5), 
				'time'=>time()
			);
		}
		

		if($errno || !self::$conn['handle'])
		{
			if($tryTimes<2)
			{
				$tryTimes ++;
				return self::getInstance();
			}
			else
			{
				Tool_Log::wlog("socket conn lost\n", APPLICATION_PATH.'/log/socketError', true);
			}
			
		}
		$tryTimes = 0;
		return self::$conn;
	}

	static function send($channel, $msg, $param=array('group'=>'all'))
	{
		
		$client = self::getInstance();
		$r = false;
		// 发送数据，注意5678端口是Text协议的端口，Text协议需要在数据末尾加上换行符
		if($client)
		{
			$r = fwrite($client['handle'], json_encode(array('msg'=>$msg, 'channel'=>$channel,'param'=>$param))."\n");
		}
		return $r;
	}

	/*
	* 1 msg to N user
	*/
	static function one2nSend($channel, $msg, $to=[])
	{
		return self::send($channel, $msg, array('group'=>'user', 'mapMode'=>'1->n', 'to'=>$to));
	}

	/*
	* N msg to 1 user
	*/
	static function n2oneSend($channel, $msg, $to='')
	{
		return self::send($channel, $msg, array('group'=>'user', 'mapMode'=>'n->1', 'to'=>$to));
	}


	/*
	* N msg to N user(相对于批量1 to 1)
	*/
	static function n2nSend($channel, $msg, $to=[])
	{
		return self::send($channel, $msg, array('group'=>'user', 'mapMode'=>'n->n', 'to'=>$to));
	}


	/*
	* N msg to M user(相当于批量n to 1)
	*/
	static function n2mSend($channel, $msg, $to=[])
	{
		return self::send($channel, $msg, array('group'=>'user', 'mapMode'=>'n->m', 'to'=>$to));
	}

}
 