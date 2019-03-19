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

    public function setLock($coin,$type=''){
        //产生一个锁文件
        $lockfile = APPLICATION_PATH.'/shell/'.$coin.$type.'.lock';
        if(file_exists($lockfile)) die('锁文件已存在');
        file_put_contents($lockfile,date('Y-m-d H:i:s'));
        chmod($lockfile,0666);
        return $lockfile;
    }
    
}
