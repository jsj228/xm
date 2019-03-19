<?php
# api auth
class Api_AuthModel extends Orm_Base
{
    public $table="api_auth";
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'api_key' => array('type' => "char", 'comment' => ''),
		'secret_key' => array('type' => "char", 'comment' => ''),
		'owner_id' => array('type' => "int", 'comment' => ''),
		'owner_name' => array('type' => "char", 'comment' => ''),
		'status' => array('type' => "int", 'comment' => ''),
        'ips' => array('type' => "char", 'comment' => ''),
		'created' => array('type' => "int", 'comment' => ''),
		'updated' => array('type' => "int", 'comment' => ''),
        'bak' => array('type' => "char", 'comment' => '')
    );
    public $pk = 'id';

    const STATUS_OK = 0;
    const STATUS_NO = 1;

	# get auth info    
	public function getAuthInfo($api_key)
    {
    	return $this->where("api_key = '{$api_key}'")->fRow();
	}


}

