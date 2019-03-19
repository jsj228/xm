<?php
class Otcorder_FactoryModel{

	static public function Model($coin){
        $coinName = strtolower((string)$coin);
	    $model = new Otcorder_CoinModel();
	    $model->table = order_.$coinName;
	    return $model;
    }
}
