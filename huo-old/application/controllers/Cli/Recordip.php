<?php
/**
 * 登陆记录ip
 *
 */
class Cli_RecordipController extends Ctrl_Cli
{
	protected $logDir = 'recordip/';
	public function runAction()
	{
		$tRedis = Cache_Redis::instance('user');
		while(true)
		{
		    $pData = $tRedis->rpop('recordIP');
			if(!$pData)
			{
				sleep(3);
				continue;
			}
		    $pData = json_decode($pData, true);
			$ip = $pData['ip'];
			$url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
			$result = file_get_contents($url);
			$result = json_decode($result, true);
			if ($result['code'] !== 0 || !is_array($result['data'])) {
				$logDir = $this->logDir . date('Ymd');
				Tool_Log::wlog(sprintf("get failed : %s, %s, %s, 获取位置失败", $pData['time'], $pData['uid'], $pData['ip']), $logDir, true);
				sleep(5);
				continue;
			}
			$ipdata = $result['data']['ip'];
			if ($result['data']['country'] == '内网IP') {
				$iparea = $result['data']['country'];
			} else {
				$iparea = $result['data']['country'] . $result['data']['region'] . $result['data']['city'];
			}

			$userloginMo = new UserLoginModel();
			$data = array(
				'uid' => $pData['uid'],
				'created' => $pData['time'],
				'createdip' => $ipdata,
				'area' => $iparea
			);

			if(!$userloginMo->insert($data)){
				$logDir = $this->logDir . date('Ymd');
				Tool_Log::wlog(sprintf("insert failed : %s, %s, 插入失败", $pData['uid'], $pData['ip']), $logDir, true);
				continue;
		};
		}
	}
}