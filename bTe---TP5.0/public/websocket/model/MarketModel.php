<?php
require_once __MODEL__.'Db.php';
class MarketModel{
    static $db;
    public function __construct($mysql_conf){
        self::$db=Db::getIntance($mysql_conf);
    }

    public function compare_market($market){
        $sql = "select name from weike_market where name='{$market}' and status = 1";
        if(self::$db->getAll($sql)) return true;
        else return false;
    }
}