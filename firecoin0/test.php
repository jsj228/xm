<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/10/20
 * Time: 17:35
 */


$usercoin = "5000%";

if($str_len = strpos($usercoin,'%')){
    echo $str_len;
}else{
    echo 111;
}
