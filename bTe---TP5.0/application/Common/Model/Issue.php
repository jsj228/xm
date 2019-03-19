<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;

class Issue extends Model
{
	public function check_install()
	{
		$this->check_server();
		$this->check_authorization();
		$this->check_database();
		$this->check_update();
		$this->check_file();
	}

	public function check_uninstall()
	{
	}

	public function check_server()
	{
	}

	public function check_authorization()
	{
	}

	public function check_database()
	{
	}

	public function check_update()
	{
	}

	public function check_file()
	{
	}
}

?>