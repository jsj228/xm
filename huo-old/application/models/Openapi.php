<?php
class OpenapiModel extends Orm_Base{
    public $table = 'openapi';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'uid'=>array('type'  => "int(10) unsigned ", 'comment' => 'uid'),
        'access_key'=>array('type'  => "char(32) ", 'comment' => ''),
        'secret_key'=>array('type'  => "char(32) ", 'comment' => ''),
        'ip'=>array('type'  => "varchar(255) ", 'comment' => ''),
        'updated'=>array('type'  => "int(10) ", 'comment' => ''),
        'status'=>array('type'  => "tinyint(3) unsigned ", 'comment' => '1 有效 0 无效'),
    );
    public $pk = 'id';
}
