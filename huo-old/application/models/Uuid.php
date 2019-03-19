<?php
class UuidModel extends Orm_Base {
	public $table = 'uuid';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uuid' => array('type' => "char(36) unsigned", 'comment' => 'uuid'),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => '创建时间')
	);
	public $pk = 'id';
	
	static public function addUuid($data){
		$uuid = new UuidModel;
		$data['ctime'] = $_SERVER['REQUEST_TIME'];
		$uuid->insert($data);
	}
	
	static public function getUuid($uuid){
		$uom = new UuidModel;
		$udid = $uom->fRow("select * from {$uom->table} where uuid='{$uuid}'");
		return $udid ? $udid : false;
	}
}
