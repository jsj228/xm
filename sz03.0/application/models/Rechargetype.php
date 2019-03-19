<?php
class RechargetypeModel extends Orm_Base{
	public $table = 'user_recharge_type';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'max' => array('type' => "varchar(200) unsigned", 'comment' => ''),
		'min' => array('type' => "varchar(200) unsigned", 'comment' => ''),
		'kaihu' => array('type' => "varchar(200) unsigned", 'comment' => ''),
		'truename' => array('type' => "varchar(200) unsigned", 'comment' => ''),
        'name' => array('type' => "varchar(50) unsigned", 'comment' => 'tradeno'),
		'title' => array('type' => "varchar(50) unsigned", 'comment' => ''),
		'url' => array('type' => "varchar(50) unsigned", 'comment' => ''),
		'username' => array('type' => "varchar(50) unsigned", 'comment' => ''),
		'password' => array('type' => "varchar(50) unsigned", 'comment' => ''),
		'img' => array('type' => "varchar(25) unsigned", 'comment' => ''),
		'extra' => array('type' => "varchar(25) unsigned", 'comment' => ''),
		'sxfei' => array('type' => "varchar(25) unsigned", 'comment' => ''),
		'remark' => array('type' => "varchar(50) unsigned", 'comment' => ''),
		'sort' => array('type' => "int(11) unsigned", 'comment' => ''),
		'addtime' => array('type' => "int(11) unsigned", 'comment' => 'addtime'),
		'endtime' => array('type' => "int(11) unsigned", 'comment' => ''),
		'status' => array('type' => "int(4) unsigned", 'comment' => ''),
	);
	public $pk = 'id';
	static public function getRechargetype($type,$status){
		$Rechargetype = new RechargetypeModel;
		$mycztype = $Rechargetype->fRow("select * from {$Rechargetype->table} where status='{$status}' and name='{$type}'");
		return $mycztype ? $mycztype : false;
	}
}
