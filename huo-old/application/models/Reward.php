<?php
class RewardModel extends Orm_Base{
	public $table = 'reward';
	public $field = array(
		'id'  => array('type' => "int(11)"),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'oid' => array('type' => "int(11) unsigned", 'comment' => '接受推荐人id'),
		'bid' => array('type' => "int(11) unsigned", 'comment' => '引起奖励的行为id'),
		'reward' => array('type' => "decimal(10,2) unsigned", 'comment' => '奖励信息'),
		'rewstr' => array('type' => "varchar(255)"),
		'type' => array('type' => "tinyint(1) unsigned", 'comment' => '奖励类型'),
		'isrew' => array('type' => "tinyint(1) unsigned", 'comment' => '是否奖励'),
		'creatime' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
	);
	
	public function getData($uid, $type=0){
		$sql = "select sum(reward) as sum, count(*) as count from {$this->table} where uid={$uid} and type={$type}";
		$data = $this->query($sql);
		return $data[0];
	}
	public function getDataMap($uid, $type=0){
		$sql = "select * from {$this->table} where uid={$uid} and type={$type}";
		$data = $this->query($sql);
		return $data;
	}
}
