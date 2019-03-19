<?php
# Transactions
class TransactionsModel extends Orm_Base
{
	public $table = 'transactions';
	public $field = array(
		'id' => array('type' => "int", 'comment' => ''),
		'coin' => array('type' => "char", 'comment' => ''),
		'address' => array('type' => "char", 'comment' => ''),
		'category' => array('type' => "char", 'comment' => ''),
		'amount' => array('type' => "decimal", 'comment' => ''),
		'confirmations' => array('type' => "int", 'comment' => ''),
		'txid' => array('type' => "char", 'comment' => ''),
		'fee' => array('type' => "decimal", 'comment' => ''),
		'time' => array('type'=>"int",'comment'=>''),
		'status' => array('type' => "tinyint", 'comment' => '0. 未检测 , 1. 已检测'),
		'created' => array('type' => "int", 'comment' => ''),
		'updated'=>array('type'=>"int",'comment'=>'')
	);
	public $pk = 'id';

}
