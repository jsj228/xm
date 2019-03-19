<?php

class LanguageCodeModel extends Orm_Base
{
    public $table = 'language_code';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'language' => array('type' => "varchar(255)", 'comment' => '语言类型'),
        'code' => array('type' => "char(11)", 'comment' => '代号'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
    );
    public $pk = 'id';
}