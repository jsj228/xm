<?php
class News_LanguageModel extends Orm_Base
{
    public $table = 'news_language';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'category_id' => array('type' => "varchar(11)", 'comment' => 'category_id'),
        'language' => array('type' => "varchar(11)", 'comment' => '语言类型'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
        'title' => array('type' => "varchar(255)", 'comment' => '标题'),
    );
    public $pk = 'id';
}