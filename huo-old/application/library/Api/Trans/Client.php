<?php
class Api_Trans_Client
{
	public static function handle()
	{
		static $handle;

		if(!Yaf_Registry::get("config")->trans['enabled'])
			return false;

		if(!$handle)
		{
			$handle = stream_socket_client(Yaf_Registry::get("config")->trans['host'], $errno, $errmsg, 5);
		}
		
		static $tryTimes = 0;
		if($errno)
		{
			if($tryTimes<2)
			{
				$tryTimes ++;
				return self::handle();
			}
			else
			{
				Tool_Log::wlog("Api_Trans_Client conn lost\n", APPLICATION_PATH.'/log/socketError', true);
			}
		}
		$tryTimes = 0;
		return $handle;
	}

	//发送请求
	public static function request($data)
	{
		static $firstPackSent=false;
		$firstPack = array(
            'command'=>'start',
            "core_as"=>"server",
        );

        if(!$firstPackSent)
        {
        	$r = self::converse($firstPack);
        	if($r && $r['code']==0)
        	{
        		$firstPackSent = true;
        	}
        }
        
		if($firstPackSent)
		{
			return self::converse($data);
		}
		return $r;
	}

	//会话
	public static function converse($data, $timeout=10)
	{
		$handle = self::handle();
		
		if($handle && $data)
		{
			//超时时间
			stream_set_timeout($handle, $timeout);
			//request
			$r = fwrite($handle, self::encode($data));
			
			if(!$r)
			{
				throw new Exception('request failed');	
			}

			$s = time();
			$response = '';
			while (!feof($handle)) 
			{  
		        $response .= fread($handle, 8192);
		        if(self::packLen($response)>0 && self::packLen($response)<=strlen($response) || time()-$s>=$timeout)
	        	{
	        		break;
	        	}
		    }
		    
		    if(!$response)
			{
				throw new Exception('response empty');	
			}
			$repArr = self::decode($response);

			return $repArr?$repArr['data']:'';
		}

		return false;	
	}


	/**
     * 一直hold住等消息
     */
	public static function holdRequest($callback, $pingFnc=null)
	{
		$firstPack = array(
            'command'=>'start',
            "core_as"=>"client",
        );
        $r = self::converse($firstPack);

        if($r && $r['code']==0)
		{
			$handle = self::handle();

   //       STREAM_CLIENT_ASYNC_CONNECT
			$response = '';
			while (!feof($handle)) 
			{  
		        $response .= fread($handle, 8192);
		        if(self::packLen($response)>0 && self::packLen($response)<=strlen($response))
	        	{
	        		if(!$response)
					{
						throw new Exception('response empty');	
					}
					$repArr = self::decode($response);
					call_user_func($callback, $repArr['data']);
					self::response(0);
					$response = '';
	        	}

	        	if($ping)
	        	{
	        		call_user_func($pingFnc, []);
	        	}
		    }
			return $repArr;
		}

		return false;	
	}

	/**
     * core as client response
     */
	public static function response($code=0, $msg='')
	{
		$handle = self::handle();
		$data = array('code'=>intval($code), 'msg'=>$msg);
		$r = fwrite($handle, self::encode($data));
		return $r;
	}


	 /**
     * 检查包的完整性
     */
    public static function packLen($buffer)
    {
        if(strlen($buffer)<8)
        {
            return 0;
        }
        $unpackData = unpack('Vlen/Vsign', $buffer);
        return $unpackData['len'];
    }

    /**
     * 打包
     */
    public static function encode($data)
    {
    	$data = array(
			'version'=>'0.0.1',
			'data'=>$data,
		);
        // 先json后base64
        $dataStr = base64_encode(json_encode($data));
        // 计算整个包的长度，包体字节数
        $totalLength = strlen($dataStr);
        // 长度标志 8 字节，按 x86 字节序，前 32 个比特位全为 1，后 32 位是后面 Base64 数据的真实长度。Base64 数据是协议内容使用 Base64 编码得到的。
        return pack("VV",  $totalLength, 0xFFFFFFFF) . $dataStr;
    }

    /**
     * 解包
     */
    public static function decode($data)
    {
        // 去掉首部8字节，才是数据
        $dataStr = substr($data, 8);
        // 解码
        return json_decode(base64_decode($dataStr), true);
    }
}
?>
