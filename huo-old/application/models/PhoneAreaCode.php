<?php
class PhoneAreaCodeModel extends Orm_Base{
    public $table = 'phone_area_code';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'area_code' => array('type' => "char(11)", 'comment' => '区号'),
        'country' => array('type' => "varchar(255)", 'comment' => '国家'),
        'langue' => array('type' => "char(5)", 'comment' => '语言'),
        'character' => array('type' => "char(5)", 'comment' => '首字母'),
    );
    public $pk = 'id';
}
