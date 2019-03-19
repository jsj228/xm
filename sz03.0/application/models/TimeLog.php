<?php
class TimeLogModel extends Orm_Base{
	public $table = 'time_log';
	public $field = array(
		'id'  => array('type' => "int(11)"),
		'type' => array('type' => "tinyint(1)"),
		'uid' => array('type' => "int(11) unsigned"),
		'name' => array('type' => "varchar(100) unsigned"),
		'static' => array('type' => "tinyint(1) unsigned"),
		'utime' => array('type' => "int(10) unsigned", 'comment' => '更新时间'),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
	);
	
	public function getTimeLog($uid, $type, $ide=false){
		$sql = "select * from {$this->table} where ".($ide ? "email" : "uid")."={$uid} and type={$type}";
		$data = $this->fRow($sql);
		if(!empty($data)){
			return $data;
		}else{
			return false;
		}
	}
	
	public function add($data){
		return $this->insert($data);
	}
	
	public function updateTime($uid, $type, $ide=false, $status=true){
		$time = time();
		$sql = "update {$this->table} set static=".($status ? "static+1" : "1").", utime={$time} where ".($ide ? "email" : "uid")."={$uid} and type={$type}";
		return $this->exec($sql);
	}
}
