<?php
/**
 * 输出
 */
class Tool_Out
{

    //格式化输出 true为var_dump
	public static function p($arr,$type=false){
	    if($type){
            echo '<pre>';
	        var_dump($arr);
        }else{
	        echo '<pre>';
	        print_r($arr);
        }
    }


    //obj转array
    public static function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = self::object_array($value);
            }
        }
        return $array;
    }





}