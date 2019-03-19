<?php
class IpDataModel extends Orm_Base {
	public $table = 'ip_data';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'ip' => array('type' => "char(15) unsigned", 'comment' => 'ip'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'uid'),
		'auid' => array('type' => "int(11) unsigned", 'comment' => '添加人uid'),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => '0：黑 1：白'),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => '创建时间')
	);
	public $pk = 'id';
	
	static public function addIp($data){
		$ipd = new IpDataModel;
		$data['ctime'] = $_SERVER['REQUEST_TIME'];
		return $ipd->insert($data);
	}
	
	static public function getIp($ip, $s=0){
		$uom = new IpDataModel;
		$ipd = $uom->fRow("select * from {$uom->table} where ip='{$ip}' and status={$s}");
		return $ipd ? $ipd : false;
	}
	static public function getUid(){
		$uom = new IpDataModel;
		$ipd = $uom->query("select uid from {$uom->table} where status=1");
		$uid = array();
		if($ipd){
			foreach($ipd as $val){
				$uid[] = $val['uid'];
			}
		}
		return $uid;
	}
	static public function getdata($s){
		$uom = new IpDataModel;
		$ipd = $uom->query("select auid, ".($s?'uid':'ip')." from {$uom->table} where status={$s}");
		return $ipd;
	}
}
