<?php
class ArticleModel extends Orm_Base{
	public $table = 'article';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'category_id' => array('type' => "int(11)", 'comment' => '分类id'),
		'title' => array('type' => "varchar(255)", 'comment' => '文章标题'),
		'content' => array('type' => "text", 'comment' => '文章内容'),
		'is_top' => array('type' => "tinyint(2)", 'comment' => '是否置顶：0否，1是'),
	    'is_delete' => array('type' => "tinyint(2)", 'comment' => '是否删除：0否，1是'),
	    'orderno' => array('type' => "int(11)", 'comment' => '排序'),
		'created' => array('type' => "int(11)", 'comment' => '添加时间'),
	    'updated' => array('type' => "int(11)", 'comment'=> '更新时间'),
	);

	public $pk = 'id';

}
