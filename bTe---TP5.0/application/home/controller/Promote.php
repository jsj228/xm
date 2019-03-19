<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Promote extends HomeCommon
{
	public function index()
	{
		$where = "invit_1<>'' and invit_1>0";
		$list = Db::name('User')->column(array('count(*)'=>'pnum', 'invit_1'=>'uid'))->where($where)->group('invit_1')->order('pnum desc')->limit(10)->select();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where(array('id' => $v['uid']))->value('username');
		}
		
		//select invit,sum(fee) as jiner from weike_invit  where  group by invit ORDER BY jiner desc
		$where = "type like '%充值奖励%' and userid in (select id from weike_user)";
		$list_jiner = Db::name('Invit')->field(array('sum(fee)'=>'jiner', 'userid'))->where($where)->group('userid')->order('jiner desc')->limit(10)->select();
		foreach ($list_jiner as $k => $v) {
			$list_jiner[$k]['username'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
		}
		
		$this->assign('list_jiner', $list_jiner);
		$this->assign('list', $list);
		return $this->fetch();
	}


    
}

?>