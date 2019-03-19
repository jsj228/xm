<?php
class Tool_Log
{
    private static $existDir = array();
    /**
     * 读日志
     */
    public static function rlog($tip){
        $file = Yaf_Registry::get("config")->log->dir."{$tip}.log";
        if(!file_exists($file)){
            return '';
        }
        return file_get_contents($file);
    }
    /**
     * 写日志
     */
    public static function wlog($msg, $tip, $repeat=false, $dateFormat='[H:i:s]', $sizeLimit=104857600){
        $file = @Yaf_Registry::get("config")->log->dir."{$tip}.log";
        if(strpos($tip, '/')!==false && !in_array($tip, self::$existDir))
        {
            $dir = preg_replace('#/[^/]+?\..+$#', '', $file);
            if(!is_dir($dir))
            {
                if(!mkdir($dir, 0777, true))
                    return false;
            }
            self::$existDir[] = $tip;    
        }

        if(!$repeat){
            $content = self::rlog($tip);
            if(!empty($content) && false !== strpos($content, $msg)){
                return false;
            }
        }

        //大小超过限制，清空重写
        if(@filesize($file)>$sizeLimit)
        {
            file_put_contents($file, '');
        }
        $msg = date($dateFormat)." {$msg} \n";
        file_put_contents($file, $msg, FILE_APPEND);
        return true;
    }
}
