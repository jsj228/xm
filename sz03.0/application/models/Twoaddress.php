<?php

class TwoaddressModel extends Orm_Base
{
	public $table = 'twoaddress';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => '用户id'),
		'address' => array('type' => "char", 'comment' => '钱包地址'),
		'coin' => array('type' => "char", 'comment' => '币种'),
		'secret' => array('type' => "char", 'comment' => '用户密码'),
		'publicKey' => array('type' => "char", 'comment' => '用户公钥'),
		'status' => array('type' => "int", 'comment' => '0 显示 , 1 删除'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated'=>array('type'=>"int",'comment'=>'')
	);
	public $pk = 'id';

	public function getAddr($uid, $coin)
	{

		if (!$addr = $this->where("uid = {$uid} and coin = '{$coin}' and status = 0")->fOne('address')) {
			if ($dist = $this->where("uid = 0 and coin = '{$coin}'")->fRow()) {
				if ($this->update(array('id' => $dist['id'], 'uid' => $uid, 'updated' => time()))) {
					return $dist['address'];
				}
			}
		} else {
			return $addr;
		}
		return false;
	}

}
