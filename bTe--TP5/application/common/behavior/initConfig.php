<?php
namespace app\common\behavior;

use think\Db;
use think\Env;

class initConfig {
	
	public function run() {

	}
	
	public function appBegin() {
	    halt(Env::get('cache.redis.type'));
//		$this->getConfig();
//		$this->getCoin();
//		$this->getMarket();
    }

    //加载配置
	protected function getConfig() {
		model('app\common\model\Config')->getConfig();
	}
	
	//加载币种配置
	protected function getCoin() {
		model('app\common\model\Coin')->getCoin();
	}
	
	//加载Market配置
	protected function getMarket() {
		model('app\common\model\Market')->getMarket();
	}
	
}
