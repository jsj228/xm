<?php
require_once __MODEL__.'Db.php';
class TradeModel{
    static $db;
	public function __construct($mysql_conf){
		self::$db=Db::getIntance($mysql_conf);
	}

	public function all_data($id=1){
		$sql = "select * from weike_admin where id =$id";
		return self::$db->getAll($sql);
	}

    public function get_trade_log($market,$count=0,$length = 50){
        if($count==0){
            $sql = "select * from weike_trade_log where status = 1 and market = '{$market}' order by id desc limit {$length}";            
        }else{
            $sql = "select count(1) as num from weike_trade_log where status = 1 and market = '{$market}'";  
        }
        return self::$db->getAll($sql);
    }


    public function  get_trade_order($market,$limit = 15){
        $buy_sql = "select price,sum(num-deal) as nums,count(id) as cid from weike_trade where status = 0 and type = 1 and market = '{$market}' group by price order by price desc limit {$limit}"; 
        $sell_sql = "select price,sum(num-deal) as nums,count(id) as cid  from weike_trade where status = 0 and type = 2 and market = '{$market}' group by price order by price asc limit {$limit}"; 
        $buy = self::$db->getAll($buy_sql);
        $sell = self::$db->getAll($sell_sql);
        $obj = (object)array();
        $obj->buy = $buy;
        $obj->sell = $sell;
        return $obj;
    }
}
