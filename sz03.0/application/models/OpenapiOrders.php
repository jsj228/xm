<?php
class OpenapiOrdersModel extends Orm_Base{
    public $table = 'openapi_orders';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'uid'=>array('type'  => "int(10) unsigned ", 'comment' => 'uid'),
        'out_trade_no'=>array('type'  => "varchar(64) ", 'comment' => '商户订单号'),
        'trust_id'=>array('type'  => "int(10)", 'comment' => '委托表id'),
        'coin'=>array('type'  => "varchar(255) ", 'comment' => 'coin from'),
    );
    public $pk = 'id';
}
