<?php
/**
 * 发送邮件
 *
 */
class Cli_EmailController extends Ctrl_Cli
{
	protected $logDir = 'email/';
	public function runAction()
	{
		$tRedis = Cache_Redis::instance('email');
		while(true)
		{
		    $pData = $tRedis->rpop('sentemail');
			if(!$pData)
			{
				sleep(3);
				continue;
			}
		    $pData = json_decode($pData, true);
			$code = Tool_Fnc::mailto($pData['email'] , $pData['title'] , $pData['body']);
		    if($code != 1)
		    {
		    	$logDir = $this->logDir.date('Ymd');
		        Tool_Log::wlog(sprintf("sent failed : %s, [%s]", $pData['email'], $code), $logDir, true);
		    }
		}
	}
}