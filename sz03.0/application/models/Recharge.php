<?php
class RechargeModel extends Orm_Base{
	public $table = 'user_recharge';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'userid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'num' => array('type' => "float(11, 2) unsigned", 'comment' => 'num'),
		'mum' => array('type' => "float(11, 2) unsigned", 'comment' => 'mum'),
		'type' => array('type' => "varchar(50) unsigned", 'comment' => 'type'),
        'tradeno' => array('type' => "varchar(50) unsigned", 'comment' => 'tradeno'),
		'remark' => array('type' => "varchar(50) unsigned", 'comment' => 'remark'),
		'sort' => array('type' => "int(11) unsigned", 'comment' => 'sort'),
		'addtime' => array('type' => "int(11) unsigned", 'comment' => 'addtime'),
		'endtime' => array('type' => "int(11) unsigned", 'comment' => 'endtime'),
		'status' => array('type' => "int(4) unsigned", 'comment' => 'status'),
		'czr' => array('type' => "varchar(50) unsigned", 'comment' => 'czr'),
		'beizhu' => array('type' => "varchar(50) unsigned", 'comment' => 'beizhu'),
		'bank_id' => array('type' => "int(11) unsigned", 'comment' => 'bank_id'),
	);
	public $pk = 'id';
	static public function getRecharge($userid='',$status=''){
		$uom = new RechargeModel;
		$udid = $uom->fRow("select * from {$uom->table} where uid='{$userid}' and status='{$status}'");
		return $udid ? $udid : false;
	}
	static public function tradeno($type='')
	{
		if ($type == 'c2c'){
			$length = 5;
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$string = '';
			for ( $i = 0; $i < $length; $i++ )
			{
				// 取字符数组 $chars 的任意元素
				$string .= $chars[ mt_rand(0, strlen($chars) - 1) ];
			}
			return	$string;
		}else{
			return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 2) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 3);
		}
	}
}
