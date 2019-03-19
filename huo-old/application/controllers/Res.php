<?php
/**
 *  静态资源
 */
class ResController extends Ctrl_Base
{
    protected $_auth = 0;

    /**
     * 用户图片鉴权
     */
    public function userImgAction($filePath='')
    {
        //缓存
        $intval = 86400;
        if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ){  
            $browserCachedCopyTimestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);  
            if ( ( $browserCachedCopyTimestamp + $intval ) > time() ) {  
                header("HTTP/1.1 304 Not Modified");  
                exit();  
            }  
        }  

        session_cache_limiter('public');//设置缓存限制器
        $this->_session();
        if($this->mCurUser)
        {
            $filePath = '.' . $filePath;
            if(file_exists($filePath))
            {   
                $owner = false;
                $imgName = preg_replace('/_thumb\..+/', '', $filePath);
                $realInfo = AutonymModel::getInstance()->field('frontFace,backFace,handkeep,updated')->where(array('uid'=>$this->mCurUser['uid']))->order('id desc')->fRow();
                foreach ($realInfo as $v) 
                {
                    if(strpos($v, $imgName) !== false)
                    {
                        $owner = true;
                        break;
                    }
                }
                if($owner)
                {
                    ob_clean();
                    header('Cache-Control:max-age='.$intval);
                    header("Expires: " . gmdate("D, d M Y H:i:s",time()+$intval)." GMT");
                    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $realInfo['updated']) . " GMT");
                    header("Content-type:image/" . ltrim(strrchr($filePath, '.'),'.'));
                    echo file_get_contents($filePath, true);
                    exit;
                }    
            }
        }
        
        header("HTTP/1.1 403 Forbidden");
        exit;
    }


    /**
     * 
     */
    public function imgAction($name='')
    {
        header("status: 404 Not Found");
        exit;
    }
}