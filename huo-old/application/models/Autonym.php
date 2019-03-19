<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/8/16
 * Time: 15:49
 */
class  AutonymModel extends Orm_Base{
    public $table = 'autonym';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'name' => array('type' => "varchar(30) unsigned", 'comment' => '真实姓名'),
        'cardtype' => array('type' => "int(11) unsigned", 'comment' => '1、身份证 2、护照'),
        'idcard' => array('type' => "varchar(20) unsigned", 'comment' => '身份证号码'),
        'frontFace' => array('type' => "varchar(60) unsigned", 'comment' => '正面'),
        'backFace' => array('type' => "varchar(60) unsigned", 'comment' => '反正'),
        'handkeep' => array('type' => "varchar(60) unsigned", 'comment' => '手持证件照'),
        'status' => array('type' => "tinyint(4) unsigned", 'comment' => '1、审核中 2、审核通过 3、审核失败、'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),
        'admin' => array('type' => "int(11) unsigned", 'comment' => '审核人'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => '用户uid'),

    );
    public $pk = 'id';

}