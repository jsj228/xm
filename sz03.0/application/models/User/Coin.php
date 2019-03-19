<?php
class User_CoinModel extends Orm_Base{
	public $table = 'coin';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'name' => array('type' => "char(50) unsigned", 'comment' => '币名称'),
		'author' => array('type' => "char(50) unsigned", 'comment' => '作者'),
		'describe' => array('type' => "char(50) unsigned", 'comment' => '描述'),
		'display' => array('type' => "char(50) unsigned", 'comment' => '中文名称'),
		'status' => array('type' => "char(50) unsigned", 'comment' => '状态'),
		'url' => array('type' => "char(50) unsigned", 'comment' => '币详情地址'),
		'block_url' => array('type' => "char(50) unsigned", 'comment' => '区块地址'),
		'minout' => array('type' => "char(50) unsigned", 'comment' => '最小转出数'),
		'maxout' => array('type' => "char(50) unsigned", 'comment' => '最大转出数'),
		'out_limit' => array('type' => "char(50) unsigned", 'comment' => '转出审核值'),
		'rate_out' => array('type' => "char(50) unsigned", 'comment' => '转出手续费'),
		'order_by' => array('type' => "char(50) unsigned", 'comment' => '显示顺序'),
		'in_status' => array('type' => "char(50) unsigned", 'comment' => '转入状态'),
		'out_status' => array('type' => "char(50) unsigned", 'comment' => '转出状态'),
		'created' => array('type' => "char(50) unsigned", 'comment' => '上币时间'),
		'asset_name' => array('type' => "varchar", 'comment' => '全称')
	);
	public $pk = 'id';

	const STATUS_ON = 0;

	/**
	 * 个人中心可显示的币种
	 */
	public function getList(){
		$list = $this->where("status=".self::STATUS_ON)->order("order_by asc,id asc")->fList();
		 if(LANG != 'cn')
		{
			foreach ($list as &$v) 
			{
				$v['display'] = strtoupper($v['name']);
			}
		}
		return $list;
	}

	/**
	 * 币种信息
	 */
	public static function getByName($name){
		if(!$coin = User_CoinModel::getInstance()->ffName($name)){
			return array();
		}
		return $coin;
	}

	/**
	 * 转出审核值
	 */
	public static function outlimit($name){
		$out_limit = User_CoinModel::getInstance()->where("status = ".self::STATUS_ON." and name = '{$name}'")->fOne('out_limit');
		if( !$out_limit ){
			return 0;
		}

		return $out_limit;
	}


	/**
     * 查询用户是否冻结禁止数字货币提现
     */
    public static function getCoinOutStatus($uid){
    	$forMo = new UserForbiddenModel;
        $fdata = $forMo->lock()->where("uid = {$uid} and status = 0")->fRow();

        if( $fdata ){
        	return $fdata['cancoinout'];
        }else{
        	return 2;  //账户没有冻结
        }
    }

}
