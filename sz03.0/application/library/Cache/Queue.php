<?php
/**
 * order_qkc_cny 订单队列
 * ticker_qkc_cny 
 *
 */
class Cache_Queue extends Cache_Redis
{
    public static function rpush($key, $value){
        return self::instance('queue')->rpush($key, $value);
    }
    public static function lpop($key){
        return self::instance('queue')->lpop($key);
    }
    public static function llen($key){
        return self::instance('queue')->llen($key);
    }
}
