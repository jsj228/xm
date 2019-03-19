<?php

class LiskModel extends Orm_Base
{
	public $table = 'lisk';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'passphrase' => array('type' => 'char', 'comment' => ''),
		'address' => array('type' => "char", 'comment' => ''),
		'status' => array('type' => "int", 'comment' => '0 未使用 , 1 已使用'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'updated'=>array('type'=>"int",'comment'=>'')
	);
	public $pk = 'id';

}
