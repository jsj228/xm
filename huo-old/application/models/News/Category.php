<?php
class News_CategoryModel extends Orm_Base
{
    public $table = 'news_category';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'cate_name' => array('type' => "varchar(50)", 'comment' => '分类名称'),
        'status' => array('type' => "tinyint(1)", 'comment' => '状态'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),
        'updateip' => array('type' => "char(15)", 'comment' => '修改ip'),
        'bak' => array('type' => "char", 'comment' => ''),
        'admin' => array('type' => "int(11) unsigned", 'comment' => '管理员')
    );
    public $pk = 'id';
}