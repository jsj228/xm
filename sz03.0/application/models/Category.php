<?php
class CategoryModel extends Orm_Base{
	public $table = 'category';
	public $field = array(
		'cid' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'pid' => array('type' => "int(11) unsigned", 'comment' => '父id'),
		'mid' => array('type' => "tinyint(1) unsigned", 'comment' => '模块'),
		'name' => array('type' => "char(60)", 'comment' => '名称'),
		'py' => array('type' => "char(60)", 'comment' => '拼音'),
		'id1' => array('type' => "int(11) unsigned", 'comment' => '1级'),
		'id2' => array('type' => "int(11) unsigned", 'comment' => '2级'),
		'id3' => array('type' => "int(11) unsigned", 'comment' => '3级'),
		'id4' => array('type' => "int(11) unsigned", 'comment' => '4级'),
		'id5' => array('type' => "int(11) unsigned", 'comment' => '5级'),
		'ob' => array('type' => "tinyint(3) unsigned", 'comment' => '排序'),
	);
	public $pk = 'cid';
  const MID_PAGE = 1; # 单页面
  const MID_ARTICLE_LIST = 2; # 文章列表
  const MID_PRODUCT_LIST = 3; # 产品系列
  const MID_JUMP = 9; # 二跳

  /**
   * 得到分类树
   * @param int $pPid 上级id
   * @param int $pPos 树节点位置
   *
   * @return array
   */
  static function tree($pPid = 0, $pPos = 0){
    if(!$pPos && $pPid){
      if($tCat = Cache_Redis::hget('category', 'json&f='.$pPid)){
        for($pPos=1;$pPos<5;$pPos++) if($tCat['id'.$pPos] == $pPid) break;
      }
      if(!$pPos) return array();
    }
    # 排序
    $tOpt = array('order'=>'id1,OB DESC');
    # where 条件
    $tOpt['where'][] = "mid!=".self::MID_PAGE;
    $pPid && $tOpt['where'][] = "id$pPos=$pPid";
    $tOpt['where'] = join(' AND ', $tOpt['where']);
    # 查询记录
    $tMO = new CategoryModel;
    return $tMO->fList($tOpt);
  }
}
