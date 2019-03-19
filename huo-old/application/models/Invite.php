<?php
class InviteModel extends Orm_Base
{
    public $table = 'invite';
    public $field = array(
        'id' => array('type' => "int", 'comment' => 'id'),
        'fuid' => array('type' => "int", 'comment' => 'fuid'),
        'tuid' => array('type' => "int", 'comment' => 'tuid'),
        'email' => array('type' => "char", 'comment' => 'Email'),
        'created' => array('type' => "int", 'comment' => '创建时间'),
        'updated' => array('type' => "int", 'comment'=> '更新时间'),
    );

    public $pk = 'id';

}
