<?php
class Article_CategoryModel extends Orm_Base{
	public $table = 'article_category';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'name' => array('type' => "varchar(255)", 'comment' => '分类名称'),
	);

	public $pk = 'id';

	public static function getCategorys() {
		$where = 'is_delete=0';
		$field = 'id,name';
		$ac_mo = new self();

		return $ac_mo->field($field)->where($where)->fList();
	}
}
