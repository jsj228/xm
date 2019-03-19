<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/1/15
 * Time: 12:26
 */

class Tool_Oss{

    //阿里云 OSS　上传函数
    public function uploadOne($path, $tmp_name){

        if (empty($tmp_name)) {
            return false;
        }

        require_once  '../application/library/Oss/autoload.php';

        $accessKeyId = 'LTAIFjBKJtvMTGXa';
        $accessKeySecret = 'Ml2WvEnzzQBe4MI5ihS3F3jMvLO63i';
        $endpoint = 'oss-cn-shenzhen.aliyuncs.com';
        $bucket = 'lovepro';

        $object = $path;
        $content = file_get_contents($tmp_name);

        $ossClient = new Oss\src\OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $ossClient->putObject($bucket, $object, $content);
        //        $content = $ossClient->getObject($bucket, $object);
        //        $content = $ossClient->deleteObject($bucket, $object);

        return 'http://' . $bucket . '.' . $endpoint . '/' . $object;
    }
}