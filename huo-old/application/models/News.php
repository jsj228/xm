<?php
class NewsModel extends Orm_Base{
	public $table = 'news';
	public $field = array(
		'id' => array('type' => "int(3) unsigned", 'comment' => 'id'),
		'title' => array('type' => "varchar(100)", 'comment' => '主题'),
		'content' => array('type' => "text", 'comment' => '邮箱'),
	    'receive' => array('type' => "varchar(50)", 'comment' => '终端类型'),
		'created' => array('type' => "int(10)", 'comment' => '发布时间'),
	    'expired' => array('type' => "int(10)", 'comment'=> '过期时间'),
	    'is_new' => array('type' => "smallint(2)", 'comment' => '是否新发布'),
	    'sort' => array('type' => "smallint(2)", 'comment' => '排序'),
		'category' => array('type' => "tinyint(2)", 'comment' => '新闻类型'),
		'updated' => array('type' => "int(10)", 'comment' => '更新时间'),
		'admin' => array('type' => "int(5)", 'comment' => '管理员'),
		'click' => array('type' => "int(5)", 'comment' => '阅读量'),
		'source' => array('type' => "varchar(50)", 'comment' => '来源'),
		'language_code' => array('type' => "char(11)", 'comment' => '语言'),
	);
	public $pk = 'id';

}
