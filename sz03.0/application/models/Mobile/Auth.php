<?php
# mobile auth
class Mobile_AuthModel extends Orm_Base
{
    public $table="mobile_auth";
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'access_key' => array('type' => "char", 'comment' => ''),
        'secret_key' => array('type' => "char", 'comment' => ''),
		'owner_id' => array('type' => "int", 'comment' => ''),
		'owner_name' => array('type' => "char", 'comment' => ''),
		'status' => array('type' => "int", 'comment' => ''),
		'created' => array('type' => "int", 'comment' => '')
    );
    public $pk = 'id';

    const STATUS_OK = 0;
    const STATUS_NO = 1;

    /**
     * 获取用户授权信息
     *
     * @param  $access_key	公钥
     */
    public function getAuthInfo($access_key)
    {
    	return $this->where("access_key = '{$access_key}'")->fRow();
	}
}
