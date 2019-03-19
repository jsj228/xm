<?php
/**
 *
 */
class Tool_Del
{

    //删除锁文件并停止运行
	public static function lock($lock,$msg){
	    unlink($lock);
	    die($msg);
    }

}