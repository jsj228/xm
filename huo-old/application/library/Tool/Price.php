<?php
/**
 * 获取coin对应价格
 *
 */
class Tool_Price
{
    /**
     * 获取对应货币的价格
     */
    public static function getByPair($pair='qkc_cny')
    {
        if(!$price = Cache_Redis::instance('json')->hGet('price',$pair)){
            $price = null;
        }
        return $price;
    }

}
