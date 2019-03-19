<?php
/**
 * cli base
 */
abstract class Ctrl_Cli extends Yaf_Controller_Abstract{
	/**
	 * 构造函数
	 */
	public function init(){
        #only for cli
        if(!$this->getRequest()->isCli()){
            ErrorController::page403();
        }
    }
    
}
