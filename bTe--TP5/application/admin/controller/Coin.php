<?php
namespace app\admin\controller;

use think\Db;

class Coin extends Admin
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = Db::name('Coin');
		$this->Title = '币种配置';
	}

	public function save()
	{
	}
}

?>