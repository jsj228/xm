<?php
class UserConfigModel extends Orm_Base{
    public $table = 'user_config';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'uid' => array('type'  => "int(11) unsigned", 'comment' => 'uid'),
        'config' => array('type'  => "var", 'comment' => 'config'),
    );
    public $pk = 'id';
}
