<?php
/**
 * 输出
 */
class Tool_Out
{

    //格式化输出
	public static function p($arr,$type=false){
        echo '<pre>';
	    if($type){
	        var_dump($arr);
        }else{
	        print_r($arr);
        }
    }


    //格式化手续费
    public static function fee_format($rate,$total){
        //trans
        if($str_len = strpos($rate,'%')){
            $num = substr($rate,0,$str_len);
            $fee = Tool_Math::mul(($num/100), $total);
        }else{
            $fee = (float)$rate;
        }
        return $fee;
    }

}