<?php
namespace app\common\behavior;

use think\Config;
use think\Lang;
class setLang {
	/**
	 * 加载common模块语言包
	 */
	public function appBegin(){
		Lang::load(APP_PATH . 'common'. DS .'lang'. DS . request()->langset() . EXT);
	}

	/**
	 * 设置允许加载的语言包
	 */
	public function appInit(){
		Lang::setAllowLangList(Config::get('lang_list'));
	}
	
}
