<?php
/**
 * 用户相关
 */
class Api_UserController extends Api_BaseController
{
	public function refresh($uid)
	{
		if(is_numeric($uid))
		{
			if(Tool_Session::mark($uid))
			{
				$this->ajax('', 1);
			}
			$this->ajax('redis更新失败', 0);
			
		}
		$this->ajax('不是有效uid', 0);
		
	}


}