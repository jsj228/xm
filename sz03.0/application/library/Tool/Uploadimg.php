<?php
class Tool_Uploadimg
{
    //小程序图片上传
    //文件大小限制
    const MAX_FILE_SIZE = 5242880;

    //文件上传根目录
    const UPLOAD_PATH = "/data/uploadtime/xcximg/";

    //错误信息
    private $error = '';

    //生成缩略图
    public $makeThumb = true;

    //缩略图参数
    public $thumbConf = array(
        'maxWidth'  => 200,
        'maxHeight' => 150,
        'suffix'    => '_thumb',
        'ext'       => '.jpg',
    );

    public function __construct()
    {
         $dir = self::UPLOAD_PATH;
         $dir = $dir.date("Ymd").'/';
        if ($this->setPath($dir) === false)
        {
            return $this->setError('上传目录不存在');
        }
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

    //上传图片
    public function autony($upfile,$extension)
    {
        $typelist = array("image/jpeg", "image/jpg", "image/png", "image/gif");
        $dir = self::UPLOAD_PATH;
        $path = $dir.date("Ymd").'/';

        if (empty($upfile) || $upfile['size'] == 0)
        {  
           // return $this->assign('errorTips', '图片不能为空');
           return $this->setError('证件照片需上传完整');
        }
        if ($upfile["error"] > 0)
        {
            switch ($upfile['error'])
            {
                case 1:
                    $info = "上传文件大小超出限制"; //超过了 php.ini中upload_max_filesize 选项中的最大值
                    break;
                case 2:
                    $info = "上传文件大小超出限制(2)"; //超过了html中MAX_FILE_SIZE 选项中的最大值
                    break;
                case 3:
                    $info = "文件只有部分被上传";
                    break;
                case 4:
                    $info = "没有文件被上传.";
                    break;
                case 6:
                    $info = "找不到临时文件夹.";
                    break;
                case 7:
                    $info = "文件写入失败！";
                    break;
            }

            return $this->setError('上传文件错误,原因:' . $info);
        }

        //检查文件大小
        if ($upfile['size'] > self::MAX_FILE_SIZE)
        {
            return $this->setError(sprintf('上传文件大小超出限制%dM', floor(self::MAX_FILE_SIZE / 1024 / 1024)));
        }

        //检查mine类型
        if (!in_array($upfile["type"], $typelist))
        {
            return $this->setError('上传文件类型错误!');
        }

        $fileinfo = pathinfo($upfile["name"]);

        //检查后缀名
       $fileinfo["extension"] = $extension;

        $ext = strtolower($fileinfo["extension"]);
        if (!in_array($ext, array('jpg', 'jpeg', 'bmp', 'png')))
        {
            return $this->setError('上传文件类型错误!');
        }
        do
        {    //随机数
            $newfile = $this->randStr(mt_rand(1,3)) .base_convert(date("ymdHis"),10,36) . $this->randStr(mt_rand(2,4)) . "." . $ext;

        } while (file_exists($path . $newfile));

        if (is_uploaded_file($upfile["tmp_name"]))
        {
            if (!$this->checkHex($upfile["tmp_name"]))
            {
                return false;
            }

            if (move_uploaded_file($upfile["tmp_name"], $path . $newfile))
            {
                $filePath = $path . $newfile;
                $this->thum($filePath);
                @chmod($filePath, 0444);
                return $filePath; //成功
            }
            else
            {
                return $this->setError('上传失败');
            }
        }
        else
        {
            return $this->setError('不是一个文件');
        }

    }

    //十六进制安全检查
    private function checkHex($filePath)
    {
        if (file_exists($filePath))
        {
            $resource = fopen($filePath, 'rb');
            $fileSize = filesize($filePath);
            fseek($resource, 0);
            $hexCode = bin2hex(fread($resource, $fileSize));
            /*   if ($fileSize > 512)
               {

                   // 取头和尾
                   $hexCode = bin2hex(fread($resource, 512));
                   fseek($resource, $fileSize - 512);
                   $hexCode .= bin2hex(fread($resource, 512));
               }
               else
               {
                   // 取全部
                   $hexCode = bin2hex(fread($resource, $fileSize));
               }*/

            fclose($resource);

            /* 匹配16进制中的 <% ( ) %> */
            /* 匹配16进制中的 <? ( ) ?> */ //<?php 3C3F706870
            /* 匹配16进制中的 <script | /script> 大小写亦可*/
            //(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)
            if (preg_match("/(3C3F706870)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)) {
                //危险文件
                return $this->setError('含有危险文件');
            }

            return true;
        }
        else
        {
            return $this->setError('文件不存在');
        }
    }

    //生成缩略图
    public function thum($imgName, $maxWidth = '', $maxHeight = '')
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
        //按日期生成图片路径
        $dir = self::UPLOAD_PATH;
        $path = $dir.date("Ymd").'/';
        $thumName = $path. $pathInfo['filename'] . $this->thumbConf['suffix'] . $this->thumbConf['ext'];
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

    //创建目录
    private function setPath($dir)
    {
        //$dir =$dir.date("Ymd");
        if (!is_dir($dir))
        {
            if (!mkdir($dir, 0777, true))
            {
                return false;
            }

        }
        return $dir;
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

}
