<?php
class Tool_UploadOne
{
    const BASE_PATH = '/data/';
    //生成缩略图
    public $makeThumb = true;
    //缩略图参数
    public $thumbConf = array(
        'maxWidth'  => 200,
        'maxHeight' => 150,
        'suffix'    => '_thumb',
        'ext'       => '.jpg',
    );
    function uploadOne($base64_image_content,$url)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){

            $type = $result[2];
            $newfile = $this->randStr(mt_rand(1,3)) .base_convert(date("ymdHis"),10,36) . $this->randStr(mt_rand(2,4)) . "." ;
            if(!$url){
                $new_file = self::BASE_PATH . "uploadtime/";
                $new_file = $new_file.date("Ymd").'/';
            }else{

                $new_file = $url;
            }
            $new_file = '.'.$new_file;

            if (!$this->checkHex($base64_image_content)) {
                return false;
            }

            if(!file_exists($new_file))
            {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file,0777,true);
            }

            //检查后缀名
            $ext = strtolower($type);

            if (!in_array($ext, array('jpg', 'jpeg', 'png'))) {
                return $this->setError($GLOBALS['MSG']['FILEERROR'], 0);//上传文件类型错误
            }

            $new_file = $new_file.$newfile."{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                $filePath = $new_file;
                $this->thum($filePath, $url);
                @chmod($filePath, 0444);
                return $filePath; //成功
            }else{
                return $this->setError('上传失败');
            }
        }
        else
        {
            return $this->setError($GLOBALS['MSG']['FILEERROR'],0);//不是一个文件

        }
    }


    function mkdirs($dir){
        return is_dir($dir) or ($this->mkdirs(dirname($dir)) and ($old=umask(0) and mkdir($dir, 0777) and umask(@$old)));
//        return is_dir($dir) or ($this->mkdirs(dirname($dir)) and mkdir($dir, 0777,true));
    }
    //创建多级文件夹
    function create_folders($dir){
        return is_dir($dir) or ($this->create_folders((dirname($dir))) and (mkdir($dir, 0777)) and umask(umask(0)));
    }

    //生成缩略图
    public function thum($imgName, $url = '', $maxWidth = '', $maxHeight = '')
    {
        if (!$this->makeThumb)
        {
            return false;
        }

        if (!$maxWidth)
        {
            $maxWidth = $this->thumbConf['maxWidth'];
        }

        if (!$maxHeight)
        {
            $maxHeight = $this->thumbConf['maxHeight'];
        }

        $imgInfo      = getimagesize($imgName);
        $imgHeight    = $imgInfo[0]; //图片高
        $imgWidth     = $imgInfo[1]; //图片宽
        $imgExtension = ''; //图片后缀名
        switch ($imgInfo[2])
        {
            case 1:
                $imgExtension = 'gif';
                break;
            case 2:
                $imgExtension = 'jpeg';
                break;
            case 3:
                $imgExtension = 'png';
                break;
            default:
                $imgExtension = 'jpeg';
                break;
        }
        $newImgSize = $this->getThumSize($imgWidth, $imgHeight, $maxWidth, $maxHeight); //新的图片尺寸

        $imgFnc     = ''; //函数名称
        $imgHandle  = ''; //图片句柄
        $thumHandle = ''; //略图图片句柄
        switch ($imgExtension)
        {
            case 'jpg':
                $imgHandle = imagecreatefromjpeg($imgName);
                $imgFnc    = 'imagejpeg';
                break;
            case 'jpeg':
                $imgHandle = imagecreatefromjpeg($imgName);
                $imgFnc    = 'imagejpeg';
                break;
            case 'png':
                $imgHandle = imagecreatefrompng($imgName);
                $imgFnc    = 'imagepng';
                break;
            case 'gif':
                $imgHandle = imagecreatefromgif($imgName);
                $imgFnc    = 'imagegif';
                break;
            default:
                $imgHandle = imagecreatefromjpeg($imgName);
                $imgFnc    = 'imagejpeg';
                break;
        }

        if($url){
            $new_file= $url;
        }else{
            $new_file = "./uploadtime/";
            $new_file = $new_file . date("Ymd") . '/';
        }

        $quality = 100; //图片质量
        if ($imgFnc === 'imagepng' && (str_replace('.', '', PHP_VERSION) >= 512))
        {
            //针对php版本大于5.12参数变化后的处理情况
            $quality = 9;
        }

        $thumHandle = imagecreatetruecolor($newImgSize['height'], $newImgSize['width']);
        if (function_exists('imagecopyresampled'))
        {
            imagecopyresampled($thumHandle, $imgHandle, 0, 0, 0, 0, $newImgSize['height'], $newImgSize['width'], $imgHeight, $imgWidth);
        }
        else
        {
            imagecopyresized($thumHandle, $imgHandle, 0, 0, 0, 0, $newImgSize['height'], $newImgSize['width'], $imgHeight, $imgWidth);
        }

        $pathInfo = pathinfo($imgName);
        $thumName =  $new_file. $pathInfo['filename'] . $this->thumbConf['suffix'] . $this->thumbConf['ext'];
        call_user_func_array($imgFnc, array($thumHandle, $thumName, $quality));
        imagedestroy($thumHandle); //清除句柄
        imagedestroy($imgHandle); //清除句柄
    }

    //缩略图尺寸算法
    public function getThumSize($width, $height, $max_width, $max_height)
    {
        $now_width  = $width; //现在的宽度
        $now_height = $height; //现在的高度
        $size       = array();
        if ($now_width > $max_width)
        {
            //如果现在宽度大于最大宽度
            $now_height *= number_format($max_width / $width, 4);
            $now_width = $max_width;
        }
        if ($now_height > $max_height)
        {
            //如果现在高度大于最大高度
            $now_width *= number_format($max_height / $now_height, 4);
            $now_height = $max_height;
        }
        $size['width']  = floor($now_width);
        $size['height'] = floor($now_height);
        return $size;
    }

    //随机 字符串
    public function randStr($length = 6)
    {
        $randpwd = '';
        $str     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $length; $i++)
        {
            $randpwd .= $str[mt_rand(0, 52)];
        }
        return $randpwd;
    }

    //十六进制安全检查
    private function checkHex($data)
    {

        $hexCode = bin2hex(base64_decode(preg_replace('/^(data:\s*image\/(\w+);base64,)/', '', $data)));

        /* 匹配16进制中的 <% ( ) %> */
        /* 匹配16进制中的 <? ( ) ?> */ //<?php 3C3F706870
        /* 匹配16进制中的 <script | /script> 大小写亦可*/
        //(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)
        if (preg_match("/(3C3F706870)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)) {
            //危险文件
            return $this->setError($GLOBALS['MSG']['FILEERROR'],0);
        }
        return true;

    }

    private function setError($error = '')
    {
        $this->error = $error;
        return false;
    }

    public function getError()
    {
        return $this->error;
    }

}
