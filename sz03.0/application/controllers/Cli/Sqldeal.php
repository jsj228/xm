<?php
/**
 * 处理 sql 队列
 *
 */
class Cli_SqldealController extends Ctrl_Cli
{
	protected $logDir = 'sqldeal/';
    # Run
    public function runAction()
    {
    	$tRedis = Cache_Redis::instance();
    	while(true)
    	{
    		$data = $tRedis->brpop('sqlQueue', 10);
    		$data = json_decode($data[1], true);
    		$logDir = $this->logDir . date('Ymd');

			if(isset($data['model']))
			{
				$r = $data['model']::getInstance()->save($data['data']);
				$r or Tool_Log::wlog($data['model']::getInstance()->getLastSql(), $logDir, true);
			}
    	}
    }
}