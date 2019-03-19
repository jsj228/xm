<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/22
 * Time: 18:42
 */

//接收参数开发环境与正式环境
function coll($type,$param = false){
    if(!COLLECT){
        if($param){
            return $_REQUEST[$param]?:$_POST[$param];
        }else{
            return $_REQUEST?:$_POST;
        }
    }

    if($type=='post') return $param?$_POST[$param]:$_POST;
    if($type=='get') return $param?$_GET[$param]:$_GET;
    return false;
}


//快捷打印
function p($values, $vardump=false)
{
    echo '<pre>';
    if($vardump) var_dump($values);
    else print_r($values);
    die;
}