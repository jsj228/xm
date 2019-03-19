<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;

class Finance extends Model
{
	protected $keyS = 'Finance';

	public function check_install()
	{

	}

	public function updata($userid = NULL, $coinname = NULL, $type = NULL, $remark = NULL)
	{
	}
}

?>