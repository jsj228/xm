<?php
/**
 * 图片命名格式 hashkey_b 原图,
 *          hashkey_m 中图,
 *          hashkey_s 小图
 * 
 */
class Tool_Image{
    //初始化图片
    public static function getHandler ($img, $type) {
        switch($type){
          case 'image/jpeg': $im = imagecreatefromjpeg($img);break;
          case 'image/gif' : $im = imagecreatefromgif($img);break;
          case 'image/png' : $im = imagecreatefrompng($img);break;
          default: $im=false;break;
        }
        return $im;
    }
    /**
     * 
     */
    public static function getSlice($im, $file, $x, $y, $w, $h){
     //剪切后小图片的名字
      $sliceBanner = '/public/upload/'. $file;
      $slicePath = APPLICATION_PATH.$sliceBanner;//剪切后的图片存放的位置
     
     //创建图片
      $dst_pic = imagecreatetruecolor($w, $h);
      imagecopyresampled($dst_pic, $im, 0, 0, $x, $y, $w, $h, $w, $h);
      imagejpeg($dst_pic, $slicePath);
      imagedestroy($im);
      imagedestroy($dst_pic);
     
      //返回新图片的位置
      return  $sliceBanner;
    }

    public static function resize($filename, $w=600, $h=450, $savePath='/upload/'){
        $imgsize = getimagesize($filename);
        list($width, $height) = $imgsize;
        if($width < $w || $height < $h){
            return false;
        }
        $saveKey = md5_file($filename);
        $showPath = $savePath.$saveKey.'.png';
        $savePath = APPLICATION_PATH.'/public'.$showPath;
        if(file_exists($savePath)){
            return $showPath;
        }
        $nw = 0;
        $nh = 0;
        $scale = floatval($width/$height);
        $scale_need = floatval($w/$h);
        if($scale - $scale_need <= 0){
            $nw = $w;
            $nh = intval($height*$w/$width);
        } else{
            $nh = $h;
            $nw = intval($width*$h/$height);
        }

        $image = self::getHandler($filename, $imgsize['mime']);
        $image_p = imagecreatetruecolor($nw, $nh);
        $image_l = imagecreatetruecolor($w, $h);
        if($imgsize['mime'] != 'image/jpeg'){
            imagesavealpha($image,true);
            imagealphablending($image_p,false);
            imagealphablending($image_l,false);
            imagesavealpha($image_p,true);
            imagesavealpha($image_l,true);
        }
        #$white = imagecolorallocate($image_l, 255, 255, 255);
        #imagefill($image_l, 0, 0, $white);

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $nw, $nh, $width, $height);
        imagecopy($image_l, $image_p, 0, 0, 0, 0, $w, $h);
        if($imgsize['mime'] == 'image/jpeg'){
            imagejpeg($image_l, $savePath);
        }elseif($imgsize['mime'] == 'image/png'){
            imagepng($image_l, $savePath);
        }elseif($imgsize['mime'] == 'image/gif'){
            imagegif($image_l, $savePath);
        }

        return $showPath;
    }
}

