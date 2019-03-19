<?php

namespace app\common\model;

use think\Model;
class Origin extends Model
{
    protected static $db;

    public function __construct()
    {
        self::$db = new \PDO("mysql:dbname=weike;host=192.168.0.88", "root", "123456");
    }

    protected function queryList($sql, $args){
        $stmt = self::$db->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }

    // 获取保存到 Redis 中的买卖数据
    public function getTrade($market, $type)
    {
        switch ($type) {
            case 1://buy
                $sql = "SELECT * FROM `weike_trade` WHERE `market` = ? AND `type` = ? AND `userid` > 0 AND `status` = 0 ORDER BY `price` desc,`id` asc limit 100";
                break;
            case 2://sell
                $sql = "SELECT * FROM `weike_trade` WHERE `market` = ? AND `type` = ? AND `userid` > 0 AND `status` = 0 ORDER BY `price` asc,`id` asc limit 100";
                break;
        }

        $args = [$market, $type];
        $stmt = $this->queryList($sql, $args);
        return $stmt->fetchALL();
    }

    // 根据 ID 获取单条数据
    public function getTradeById($id)
    {
        $sql = "SELECT * FROM `weike_trade` WHERE `id` = ? LIMIT 1 ";
        $args = [$id];
        $stmt = $this->queryList($sql, $args);
        return $stmt->fetch();
    }

}
