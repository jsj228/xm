<?php
class News_CommentModel extends Orm_Base
{
    public $table = 'news_comment';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'mo' => array('type' => "char(11)", 'comment' => '手机号'),
        'email' => array('type' => "char(60)", 'comment' => '邮箱'),
        'uid' => array('type' => "int(11)", 'comment' => '用户'),
        'nid' => array('type' => "int(11)", 'comment' => 'news表id'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'content' => array('type' => "text", 'comment' => '评论'),
        'type' => array('type' => "tinyint(4) unsigned", 'comment' => '评论类型'),
        'shield'   => array('type'    => "tinyint(4)", 'comment' => '是否屏蔽 1屏蔽 0 不屏蔽'),
        'backlist' => array('type'    => "tinyint(4)", 'comment' => '是否拉黑 1拉黑 0 不拉黑'),
        'area' => array('type' => "varchar(11)", 'comment' => '区号'),
    );
    public $pk = 'id';
}
