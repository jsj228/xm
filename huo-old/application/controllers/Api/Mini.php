<?php
/**
 * 小程序
 */
class Api_MiniController extends Api_BaseController
{

	/**
	 * 查詢用戶任務狀態
	 */
	private function checkTask()
	{
		
		$mo = $_GET['mo'];
		$area = $_GET['area'];
		$task = $_GET['task'];
		$userInfo = UserModel::getInstance()->field('uid')->where(array('mo'=>$mo, 'area'=>$area))->fRow();
		if(!$userInfo)
		{
			$this->ajax('用户不存在', 0);
		}

		$rep = array();
		$today = strtotime('today');
		foreach($task as $v)
		{
			$conf = json_decode($v['conf'], true);

			if(stripos($conf['table'], 'redis.')===0)
			{
				$db = explode('.', $conf['table']);
				if(isset($db[3]))
				{
					$status = Cache_Redis::instance()->hget($db[2], $db[3]);
				}
				else
				{
					$status = Cache_Redis::instance()->get($db[2]);
				}
				
			}
			else
			{
				$where = str_replace(['$uid', '$today'], [$userInfo['uid'], $today], $conf['where']);
				$status = Orm_Base::getInstance()->table($conf['table'])->where($where)->fRow();
			}
			$rep[] = array('id'=>$v['id'], 'status'=>intval($status));
		}
		$this->ajax('', 1, $rep);
		
	}

	
	public function callAction($action, $param1='')
	{
		$param = array($param1);
		return call_user_func_array(array($this, $action), $param);
	}
}