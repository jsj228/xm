<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;

class Trade extends AdminCommon
{
	public function index()
	{
        $name = input('name');
        $field = input('field');
        $market = input('market');
        $status = input('status');
        $type = input('type');
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($market) {
			$where['market'] = $market;
		}
		if ($status) {
			$where['status'] = $status;
		}

		if($status == 0 && $status != null){
			$where['status'] = 0;
		}
		if ($type==1 || $type==2) {
			$where['type'] = $type;
		}

		$count = DB::name('Trade')->where($where)->count();
		$weike_getSum = DB::name('Trade')->where($where)->sum('mum');

        //获取已成交总数量，已成交总额额
        $weike_num = DB::name('Trade')->where($where)->sum('deal');
        $weike_total = round(DB::name('Trade')->where($where)->sum('price * deal'), 8);

		$list = DB::name('Trade')->where($where)->order('id desc')->paginate(15);
		$page = $list->render();
		$list = $list->all();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
            $list[$k]['usertype'] = DB::name('User')->where(array('id' => $v['userid']))->value('usertype');
            if($list[$k]['status'] == 2){//用户撤单后显示的交易金额
                if($list[$k]['deal'] == 0){
                    $list[$k]['mum'] =$list[$k]['num']*$list[$k]['price'];
                }else{
                    $list[$k]['mum'] =$list[$k]['deal']*$list[$k]['price'];
                }
            }
		}
		$this->assign('list', $list);
		$this->assign('weike_count', $count);
		$this->assign('weike_getSum', $weike_getSum);
		$this->assign('page', $page);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_total', $weike_total);
		return $this->fetch();
	}

	//撤单
	public function chexiao()
	{
        $id = input('id');

		$rs = model('Trade')->chexiao($id);
		if ($rs[0]) {
			$this->success($rs[1]);
		} else {
			$this->error($rs[1]);
		}
	}

	//一键撤单
    public function autochexiao()
    {
        $ids = input('ids');
        $ids = explode(',', $ids);
        $count = count($ids);
        if($count > 15){
            $this->error('不能撤销数量超过 15 的委托！');
        }

        foreach ($ids as $k => $v){
            $rs = model('Trade')->chexiao($v);
        }

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }
    //统计交易排名
	public function ranking($addtime='',$endtime='',$market='',$field='',$name='')
	{
	    $addtime ? $addtime = urldecode($addtime):false;
	    $endtime ? $endtime = urldecode($endtime):false;
	    $jqren_id = DB::name('user')->field('id')->where('usertype=1')->select();
	    $jqren_id_arr = [];
	    if (count($jqren_id)>0){
            foreach ($jqren_id as $k=>$v){
                array_push($jqren_id_arr,$v['id']);
            }
        }
		//去除机器人0普通用户1机器人
        $where = 'u.usertype=0';
       //筛选时间
	    if ($addtime && $endtime){
			//转换时间戳
	        $addtime = strtotime($addtime);
	        $endtime = strtotime($endtime);
			//条件
	        $where ? $where .= ' and t.addtime between '.$addtime.' and '.$endtime:$where='t.addtime between '.$addtime.' and '.$endtime;

	        $this->assign('addtime',$addtime);
	        $this->assign('endtime',$endtime);
        }else{
			//默认获取当天
            $addtime = strtotime(date('Y-m-d').' 00:00:00');
            $endtime = strtotime(date('Y-m-d').' 23:59:59');

            $where ? $where .= ' and t.addtime between '.$addtime.' and '.$endtime:$where='t.addtime between '.$addtime.' and '.$endtime;

            $this->assign('addtime',$addtime);
            $this->assign('endtime',$endtime);
        }

        if ($market){
            $where ? $where .= ' and t.market="'.$market.'" ' :$where = "t.market='".$market."'";
            $this->assign('market',$market);
        }
        if ($name && $field){
            $uid = DB::name('user')->where('username="'.$name.'"')->value('id');
	        if ($field == 'peername'){
               $where ? $where .= ' and u.id='.$uid.' and t.peerid='.$uid:$where = 't.peerid='.$uid;

            }else{
               $where ? $where .= ' and u.id='.$uid.' and t.userid='.$uid:$where = 't.userid='.$uid;

            }

//            $this->assign('name',$name);
//            $this->assign('field',$field);
        }
       
        $c_field = 'sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id,u.username,u.truename,t.release';
	    $sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE '.$where.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u	LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE '.$where.' GROUP BY u.id) a GROUP BY a.id';
        $count = count(DB::query($sql));

        $sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE '.$where.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u	LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE '.$where.' GROUP BY u.id) a
	              GROUP BY a.id order by zongshu desc';

		$list = DB::query($sql);

        $weike_fee = round(DB::name('trade_log')->where('userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')')->sum('fee_buy+fee_sell'),4);
        $weike_mum = round(DB::name('trade_log')->where('userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')')->sum('mum'),4);

		$now_fee = round(DB::name('trade_log')
                ->where('(userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')) and addtime between '.$addtime.' and '.$endtime)
                ->sum('fee_buy+fee_sell'),4);

        foreach ($list as $k=>$v){
            if (date('Y-m-d',$addtime) != date('Y-m-d',$endtime)){
                $list[$k]['release'] = 0;
            }else{
                if (!DB::name('fenhong_log')->where('userid='.$v['id'].' and grant_start_time='.$addtime.' and grant_end_time='.$endtime)->find()){
                    $list[$k]['release'] = 0;
                }else{
                    $list[$k]['release'] = 1;
                }
            }

            $list[$k]['weike_fell'] = $now_fee;
        }
		$this->assign('weike_count', $count);
		$this->assign('weike_fee', $weike_fee);
		$this->assign('now_fee', $now_fee);
		$this->assign('weike_mum', $weike_mum);
		$this->assign('list', $list);
		$this->assign('page', $page);
		$this->assign('num',$Page->firstRow);
		return $this->fetch();
	}

	//数据排序
	/**
	 *二维数组排序
	 * SORT_ASC - 默认，按升序排列。(A-Z)
	 * SORT_DESC - 按降序排列。(Z-A)
	 *
	 * SORT_REGULAR - 默认。将每一项按常规顺序排列
	 * SORT_NUMERIC - 将每一项按数字顺序排列
	 * SORT_STRING - 将每一项按字母顺序排列
	 * $arrays - 需要排序的二维数组
	 * $sort_key - 需要排序的键名
	 */
	function my_sort($list,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC  ){
		if(is_array($list)){
			foreach ($list as $array){
				if(is_array($array)){
					$key_arrays[] = $array[$sort_key];
				}else{
					return false;
				}
			}
		}else{
			return false;
		}
		array_multisort($key_arrays,$sort_order,$sort_type,$list);
		return $list;
	}
	public function fenhong($id='',$feeall='',$feebuy='',$addtime='',$endtime='')
	{

	    $where = '';

	    if (date('Y-m-d',$addtime) != date('Y-m-d',$endtime)){
	        $this->error('开始时间和结束时间不在同一天');
        }
		if($addtime && $endtime){
			$where = 'u.usertype=0 and t.status=1 and t.addtime between '.$addtime.' and '.$endtime;

		}
		//买

        $sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id FROM(
                  SELECT sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE '.$where.' GROUP BY u.id
                  UNION
	              SELECT sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id FROM  weike_user AS u	LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE '.$where.' GROUP BY u.id) a  
	              GROUP BY a.id order by zongshu desc limit 20';
        $list = M()->query($sql);
		//前二十名用户总手续费
        $weike_fee = 0;
		foreach($list as $key=>$value){
			$weike_fee += $value['buy'];
		}

		if($weike_fee==0){
			$this->error('用户当前分红为零，不可发放！');
		}

		$fengong=($feeall*0.7)*($feebuy/$weike_fee);//前二十名
        

		$usercoin=DB::name('fenhong_log')->where('userid='.$id.' and grant_start_time='.$addtime.' and grant_end_time='.$endtime)->find();
		if($usercoin){
			$this->error('分红已发放,请不要重复发放！');
		}


		DB::name()->startTrans();
		try{
            $addcny=DB::name('fenhong_log')->where(array('userid'=>$id,'addtime'=>array('between',"$addtime,$endtime")))->update(array('release'=>1));
            $data=array(
                'name'=>$fengong.'人民币',
                'coinname'=>'cny',
                'num'=>$fengong,
                'mum'=>$fengong,
                'addtime'=>time(),
                'status'=>1,
                'userid'=>$id,
                'release'=>1,
                'grant_start_time'  =>  $addtime,
                'grant_end_time'    =>  $endtime
            );
            $fenlog_id = DB::name('fenhong_log')->insert($data);
            $pre_user = DB::name('user_coin')->where('id='.$id)->find();
            $finance = array(
                'userid'        =>  $id,
                'coinname'      =>  'cny',
                'num_a'         =>  $pre_user['cny'],
                'num_b'         =>  $pre_user['cnyd'],
                'num'           =>  $pre_user['cny']+$pre_user['cnyd'],
                'fee'           =>  $fengong,
                'type'          =>  1,
                'name'          =>  'fenhonglog',
                'nameid'        =>  $fenlog_id,
                'remark'        =>  '分红发放',
                'mum_a'        =>  $pre_user['cny']+$fengong,
                'mum_b'        =>  $pre_user['cnyd'],
                'mum'          =>  $pre_user['cny']+$fengong+$pre_user['cnyd'],
                'addtime'      =>  time(),
                'status'      =>  1
            );
            DB::name('finance')->insert($finance);
            DB::name('user_coin')->where(array('userid'=>$id))->setInc('cny',$fengong);
		    $flag = true;
		    DB::name()->commit();
        }Catch(\Exception $e){
		    $flag = false;
		    DB::name()->rollback();
        }

        if ($flag){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！');
		}

	}


    public function log()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
        $market = strval(input('market'));
        $type = input('type');
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name,))->value('id');
			} else if ($field == 'peername') {
				$where['peerid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;

			}
		}
		if ($type==1 || $type==2) {
			$where['type'] = $type;
		}

		if ($market) {
			$where['market'] = $market;
		}
		$count = DB::name('TradeLog')->where($where)->count();
        $weike_getSum = DB::name('TradeLog')->where($where)->sum('mum');
        $weike_num = DB::name('TradeLog')->where($where)->sum('num');

		$list = DB::name('TradeLog')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
		foreach ($list as $k => $v) {
			$list[$k]['username'] =  DB::name('User')->where(array('id' => $v['userid']))->value('username');
			$list[$k]['peername'] =  DB::name('User')->where(array('id' => $v['peerid']))->value('username');
            $list[$k]['usertype'] =  DB::name('User')->where(array('id' => $v['peerid']))->value('usertype');
        }

		$this->assign('weike_count', $count);
        $this->assign('weike_num', $weike_num);
		$this->assign('weike_getSum', $weike_getSum);
		$this->assign('list', $list);
		$this->assign('page', $$page);
		return $this->fetch();
	}

	public function chat()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		// $count = M('Chat')->where($where)->count();
		// $Page = new \Think\Page($count, 15);
		// $show = $Page->show();
		$list = DB::name('Chat')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function chatStatus()
	{
        $id = input('id');
        $type = input('type');
        $moble = input('moble');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (DB::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (DB::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function comment()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $coinname = strval(input('coinname'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}

		// $count = M('CoinComment')->where($where)->count();
		// $Page = new \Think\Page($count, 15);
		// $show = $Page->show();
		$list = DB::name('CoinComment')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function commentStatus()
	{
        $id = input('id');
        $type = input('type');
        $moble = input('moble');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (DB::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (DB::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function market()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}
		$list = DB::name('Market')->where($where)->order('id desc')->paginate(15);
		$page = $list->render();
		$list = $list->all();
		foreach($list as $k=>$v){
			if($v['begintrade']){
				$begintrade = substr($v['begintrade'],0,5);
			}else{
				$begintrade = "00:00";
			}
			if($v['endtrade']){
				$endtrade = substr($v['endtrade'],0,5);
			}else{
				$endtrade = "23:59";
			}
            
			$list[$k]['tradetimeweike'] = $begintrade."-".$endtrade;
		}
		
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}

	public function marketEdit()
	{
        $id = input('id');
        if (empty($_POST)) {
           $_POST = input('post.');
            if (empty($id)) {
                $this->data = array();

                $beginshi = "00";
                $beginfen = "00";
                $endshi = "23";
                $endfen = "59";

            } else {
                $market_weike = DB::name('Market')->where(array('id' => $id))->find();
                $auto_trade = DB::name('AutoTrade')->where(['market' => $market_weike['name']])->find();
                $market_weike['auto_volume'] = $auto_trade['volume'];
                $market_weike['auto_min'] = $auto_trade['min'];
                $market_weike['auto_max'] = $auto_trade['max'];
                $market_weike['auto_kaiguan'] = $auto_trade['status'];
                $this->assign('data',$market_weike);

                if($market_weike['begintrade']){
                    $beginshi = explode(":",$market_weike['begintrade'])[0];
                    $beginfen = explode(":",$market_weike['begintrade'])[1];
                }else{
                    $beginshi = "00";
                    $beginfen = "00";
                }

                if($market_weike['endtrade']){
                    $endshi = explode(":",$market_weike['endtrade'])[0];
                    $endfen = explode(":",$market_weike['endtrade'])[1];
                }else{
                    $endshi = "23";
                    $endfen = "59";
                }
            }

            $this->assign('weike_getCoreConfig', ["CNY交易区"]);
            $this->assign('beginshi', $beginshi);
            $this->assign('beginfen', $beginfen);
            $this->assign('endshi', $endshi);
            $this->assign('endfen', $endfen);
            return $this->fetch('marketedit');
        } else {
            $_POST = input('post.');
            $round = array(0, 1, 2, 3, 4, 5, 6, 7, 8);
            if (!in_array($_POST['round'], $round)) {
                $this->error('小数位数格式错误！');
            }

            if ($_POST['id']) {
                $name = DB::name('Market')->where(['id' => $_POST['id']])->value('name');
                $new_price = DB::name('TradeLog')->where(['market' => $name])->order('id desc')->value('price');
                $deal_toble = $new_price * $_POST['auto_volume'];
                $auto = DB::name('AutoTrade')->where(['market' => $name])->update(['deal_toble' => $deal_toble ,'min' => $_POST['auto_min'] , 'max' => $_POST['auto_max'] , 'volume' => $_POST['auto_volume'] ,'status' => $_POST['auto_kaiguan'] ]);
                $rs = DB::name('Market')->update(["jiaoyiqu" => $_POST['jiaoyiqu'] , "round" => $_POST["round"] , "fee_buy" => $_POST["fee_buy"] , "fee_sell"=> $_POST["fee_sell"],
                    "buy_min" => $_POST["buy_min"] , "buy_max" => $_POST["buy_max"] , "sell_min" => $_POST["sell_min"] , "sell_max" => $_POST["sell_max"] ,
                    "trade_min" => $_POST["trade_min"] , "trade_max" => $_POST["trade_max"] , "invit_1" => $_POST["invit_1"] ,"invit_2" => $_POST["invit_2"] ,
                    "invit_3" => $_POST["invit_3"] , "invit_buy"=>$_POST["invit_buy"] , "invit_sell"=>$_POST["invit_sell"] , "weike_faxingjia" => $_POST["weike_faxingjia"],
                    "zhang"=> $_POST["zhang"] , "die" => $_POST["die"] , "hou_price" => $_POST["hou_price"], "begintrade" =>$_POST["begintrade"] ,
                    "endtrade"=> $_POST["endtrade"] , "trade" =>$_POST["trade"], "status"=>$_POST["status"], "id" => $_POST["id"]]);
            } else {
                $_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($_POST['buyname']);
                unset($_POST['sellname']);

                if (DB::name('Market')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('市场存在！');
                }

                $rs = DB::name('Market')->insert(['name' => $_POST['name'],
                    "jiaoyiqu" => $_POST['jiaoyiqu'] , "round" => $_POST["round"] , "fee_buy" => $_POST["fee_buy"] , "fee_sell"=> $_POST["fee_sell"],
                    "buy_min" => $_POST["buy_min"] , "buy_max" => $_POST["buy_max"] , "sell_min" => $_POST["sell_min"] , "sell_max" => $_POST["sell_max"] ,
                    "trade_min" => $_POST["trade_min"] , "trade_max" => $_POST["trade_max"] , "invit_1" => $_POST["invit_1"] ,"invit_2" => $_POST["invit_2"] ,
                    "invit_3" => $_POST["invit_3"] , "invit_buy"=>$_POST["invit_buy"] , "invit_sell"=>$_POST["invit_sell"] , "weike_faxingjia" => $_POST["weike_faxingjia"],
                    "zhang"=> $_POST["zhang"] , "die" => $_POST["die"] , "hou_price" => $_POST["hou_price"] , "begintrade" =>$_POST["begintrade"] ,
                    "endtrade"=> $_POST["endtrade"] , "trade" =>$_POST["trade"], "status"=>$_POST["status"]]);
                $auto = DB::name('AutoTrade')->insert(['market' => $_POST['name'] ,'min' => $_POST['auto_min'] , 'max' => $_POST['auto_max'] , 'status' => $_POST['auto_kaiguan']]);
            }

            if ($rs || $auto || ($rs && $auto)) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
	}

	public function marketStatus()
	{
        $id = input('post.');
        foreach ($id as $key => $value) {
        	$ids=implode(',',$value);
        }
        $type = input('type');
        $moble =input('moble','Market');
		if (empty($ids)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		$where['id'] = array('in', $ids);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (DB::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (DB::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function invit()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		$list = DB::name('Invit')->where($where)->paginate(15);
		$page = $list->render();
		$list = $list->all();
		$userall=DB::name('Invit')->where($where)->sum('fee');

		$this->assign('userall',$userall);

		foreach ($list as $k => $v) {

			$list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
			$list[$k]['invit'] = DB::name('User')->where(array('id' => $v['invit']))->value('username');
		}
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}

    //marketControl page
    public function marketControl()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            $where[$field] = $name;
        }

        // $count = M('MarketControl')->where($where)->count();
        // $Page = new \Think\Page($count, 15);
        // $show = $Page->show();
        $list = DB::name('MarketControl')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        foreach ($list as $k => $v) {
            $rmb = explode('_', $v['name'])[1];
            if ($rmb === 'btc'){
                $list[$k]['unit'] = $rmb;
                $list[$k]['api_unit'] = $rmb;
            } else {
                $list[$k]['unit'] = $rmb;
                $list[$k]['api_unit'] = 'usdt';
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
       return $this->fetch('marketcontrol');
    }

    //marketControl modify
    public function marketControlEdit()
    {
        $id = input('id');
        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = [];
            } else {
                $market_weike = DB::name('MarketControl')->where(['id' => $id])->find();
                $rmb = explode('_', $market_weike['name'])[1];
                if ($rmb === 'btc'){
                    $market_weike['unit'] = $rmb;
                    $market_weike['api_unit'] = $rmb;
                } else {
                    $market_weike['unit'] = $rmb;
                    $market_weike['api_unit'] = 'usdt';
                }
                $this->assign('data',$market_weike);
            }
           return $this->fetch('marketcontroledit');
        } else {
            $_POST = input('post.');

            //验证接口地址
            if (!empty($_POST['api_url'])){
                $data = mCurl($_POST['api_url']);
                if($data['result'] !== 'true' && $data['btc_usdt']['result'] !== 'true'){
                    $this->error('请填写比特尔接口地址！');
                }
            }

            // 非 BOSS 不能操作市场
            if (session('admin_id') != 11) {
                $this->error('非 BOSS 不能操作市场！');
            }

            // 判断 BOSS 密码是否正确
            $password = DB::name('Admin')->where(array('id' => 11))->value('password');
            if (md5($_POST['pass']) != $password) {
                $this->error('BOSS 密码不正确！');
            }
            unset($_POST['pass']);

            if ($_POST['id']) {
                $rs = DB::name('MarketControl')->update($_POST);
            } else {
                if ($_POST['sellname'] === $_POST['buyname']) {
                    $this->error('市场错误！');
                }

                $_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($_POST['buyname']);
                unset($_POST['sellname']);

                if (DB::name('MarketControl')->where(['name' => $_POST['name']])->find()) {
                    $this->error('市场存在！');
                }

                $rs = DB::name('MarketControl')->insert($_POST);
            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

	//marketControl Status
    public function marketControlStatus()
    {
        $id = input('post.');
        foreach ($id as $key => $value) {
        	$ids=implode(',', $value);
        }
        $type = input('type');
        $moble =input('moble', 'MarketControl');
        if (empty($ids)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        // 非 BOSS 不能操作市场
        // if (session('admin_id') != 11) {
        //     $this->error('非 BOSS 不能操作市场！');
        // }

        $where['id'] = array('in', $ids);
        switch (strtolower($type)) {
            case 'forbid':
                $data = array('type' => 0);
                break;

            case 'resume':
                $data = array('type' => 1);
                break;

            case 'repeal':
                $data = array('type' => 2);
                break;

            case 'delete':
                $data = array('type' => -1);
                break;

            case 'del':
                if (DB::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('操作失败！');
        }

        if (DB::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }
	public function exportExcel($expTitle, $expCellName, $expTableData)
	{
		ini_set('memory_limit','1024M');
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Writer.Excel5", '', '.php');
		import("Org.Util.PHPExcel.IOFactory", '', '.php');


		$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
		$fileName = $_SESSION['loginAccount'] . date('_YmdHis');
		$cellNum = count($expCellName);
		$dataNum = count($expTableData);
		vendor("PHPExcel.PHPExcel");
        include ROOT_PATH."thinkphp/library/vendor/PHPExcel/PHPExcel.php";
		$objPHPExcel = new \PHPExcel();
		$cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
		$objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', date('Y-m-d H:i:s') . '导出记录');
		$i = 0;

		for (; $i < $cellNum; $i++) {
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][2]);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($cellName[$i])->setWidth(12);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('D')->setWidth(20);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('H')->setWidth(30);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('M')->setWidth(30);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('O')->setWidth(20);
			$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('L')->setWidth(30);
		}

		$i = 0;

		for (; $i < $dataNum; $i++) {
			$j = 0;

			for (; $j < $cellNum; $j++) {
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), (string)$expTableData[$i][$expCellName[$j][0]]);
			}
			unset($expTableData[$i]);
		}

		ob_end_clean();
		header('pragma:public');
		header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
		header('Content-Disposition:attachment;filename=' . $fileName . '.xls');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit();
	}
	//选择导出
	public function weike_financeExcel()
	{
		$id=input('id');
		if ($id) {
			$id = implode(',', $id);
		}
		$addtime=input('addtime');
		$endtime=input('endtime');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
	$c_field = 'sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id,u.username,u.truename,t.addtime,t.userid,t.peerid,t.release';
		$sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release`,a.`addtime`,a.`userid`,a.`peerid` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE t.userid in ('.$id.') and t.addtime between '.$addtime.' and '.$endtime.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE t.peerid in ('.$id.') and t.addtime between '.$addtime.' and '.$endtime.' GROUP BY u.id) a
	              GROUP BY a.id order by zongshu desc';
		$list = DB::name()->query($sql);

		foreach($list as $k=>$val){
			$list[$k]['benun']=$k+1;
			$list[$k]['addtime']=date('Y-m-d H:i:s',$addtime).'-'.date('Y-m-d H:i:s',$endtime);
			$list[$k]['sell']=DB::name('user')->where(array('id'=>$val['peerid']))->value('username');
		}
		$xlsName = 'trade_log';
		$xls = array();
		$xls[0][0] = 'benun';
		$xls[0][2] = "排名";
		$xls[1][0] = "truename";
		$xls[1][2] = '姓名';
		$xls[2][0] = "username";
		$xls[2][2] = '买家用户名';
		$xls[3][0] = "username";
		$xls[3][2] = '卖家用户名';
		$xls[4][0] = "buy";
		$xls[4][2] = "手续费";
		$xls[5][0] = "zongshu";
		$xls[5][2] = "总额";
		$xls[6][0] = "addtime";
		$xls[6][2] = '时间';
		$this->exportExcel($xlsName, $xls, $list);
	}
	//导出全部
	public function Excel()
	{
//        ini_set('max_execution_time','0');
        set_time_limit(0);

		$addtime=input('addtime');
		$endtime=input('endtime');
//        and t.addtime between '.$addtime.' and '.$endtime.'
//        and t.addtime between '.$addtime.' and '.$endtime.'

		$c_field = 'sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id,u.username,u.truename,t.addtime,t.userid,t.peerid,t.release,u.usertype';
		$sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.release,a.addtime,a.userid,a.peerid,a.usertype FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE  u.usertype=0  group by u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE u.usertype=0  group by u.id) a
	              group by a.id order by zongshu desc';
		$list = DB::query($sql);

//		p($list);die;
        if(!$list){
			$this->error('导出记录为空!');
		}
		foreach($list as $k=>$val){
			$list[$k]['benun']=$k+1;
			$list[$k]['addtime']=date('Y-m-d H:i:s',$addtime).'-'.date('Y-m-d H:i:s',$endtime);
			$list[$k]['sell']=DB::name('user')->where(array('id'=>$val['peerid']))->value('username');
		}
		$xlsName = 'trade_log';
		$xls = array();
		$xls[0][0] = 'benun';
		$xls[0][2] = "排名";
		$xls[1][0] = "truename";
		$xls[1][2] = '姓名';
		$xls[2][0] = "username";
		$xls[2][2] = '买家用户名';
		$xls[3][0] = "username";
		$xls[3][2] = '卖家用户名';
		$xls[4][0] = "buy";
		$xls[4][2] = "手续费";
		$xls[5][0] = "zongshu";
		$xls[5][2] = "总额";
		$xls[6][0] = "addtime";
		$xls[6][2] = '时间';
		$this->exportExcel($xlsName, $xls, $list);
	}
	//导出全部
  public function weike_financeAllExcel($xlsName, $xls = 'attack_ip_info', $list = "test.csv")
	{
		set_time_limit(0);
		$sqlCount = Db::table('weike_trade_log')->count();
		//输出Excel文件头，可把user.csv换成你要的文件名
		header('Content-Type: application/vnd.ms-excel;charset=utf-8');
		header('Content-Disposition: attachment;filename="' . $list . '"');
		header('Cache-Control: max-age=0');

		$sqlLimit = 100000;//每次只从数据库取100000条以防变量缓存太大
		//每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
		$limit = 100000;
		//buffer计数器
		$cnt = 0;
		$fileNameArr = array();
		//逐行取出数据，不浪费内存
		for ($i = 0; $i < ceil($sqlCount / $sqlLimit); $i++) {
			$fp = fopen($xls . '_' . $i . '.csv', 'w'); //生成临时文件
			//chmod('attack_ip_info_' . $i . '.csv',777);//修改可执行权限
			$fileNameArr[] = $xls . '_' .  $i . '.csv';
			//将数据通过fputcsv写到文件句柄
			fputcsv($fp, $xlsName);
			$dataArr = Db::table('weike_trade_log')->limit($i * $sqlLimit,$sqlLimit)->select();
			foreach ($dataArr as $a) {
				$cnt++;
				if ($limit == $cnt) {
					//刷新一下输出buffer，防止由于数据过多造成问题
					ob_flush();
					flush();
					$cnt = 0;
				}
				fputcsv($fp, $a);
			}
			fclose($fp);//每生成一个文件关闭
		}
		//进行多个文件压缩
		$zip = new ZipArchive();
		$filename = $xls . ".zip";
		$zip->open($filename, ZipArchive::CREATE);//打开压缩包
		foreach ($fileNameArr as $file) {
			$zip->addFile($file, basename($file));//向压缩包中添加文件
		}
		$zip->close();//关闭压缩包
		foreach ($fileNameArr as $file) {
			unlink($file);//删除csv临时文件
		}
		//输出压缩文件提供下载
		header("Cache-Control: max-age=0");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . basename($filename));
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($filename));
		@readfile($filename);//输出文件;
		unlink($filename); //删除压缩包临时文件
	}
	 public function ctrade()
    {
        $name = input('name', null);
        $field = input('field', null);
        $status = input('status', null);
        $type = input('type', 0);
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status;
        }

        if($status == 0 && $status != null){
            $where['status'] = 0;
        }

        if ($status == 4){
            $where['status'] = 0;
            $where['businessid'] = ['neq',0];
        }

        if ($type==1 || $type==2) {
            $where['type'] = $type;
        }

        $count = DB::name('user_c2c_trade')->where($where)->count();
        $weike_getSum = DB::name('user_c2c_trade')->where($where)->sum('mum');

        //获取已成交总数量，已成交总额额
        $weike_num = DB::name('user_c2c_trade')->where($where)->sum('deal');
        $weike_total = round(DB::name('user_c2c_trade')->where($where)->sum('mum'), 8);

        $list = DB::name('user_c2c_trade')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = DB::name('User')->where(array('id' => $v['userid']))->value('username');
            $list[$k]['usertype'] = DB::name('User')->where(array('id' => $v['userid']))->value('usertype');
            if($list[$k]['status'] == 2){//用户撤单后显示的交易金额
                if($list[$k]['deal'] != 0){
                    $list[$k]['mum'] =$list[$k]['deal'];
                }
            }

            $list[$k]['pipei'] = 0;
            if ($v['status'] == 3){
                $wtype = $v['type'] == 1 ? 'buytradeno="'.$v['tradeno'].'"':'selltradeno="'.$v['tradeno'].'"';
                $list[$k]['pipei'] = DB::name('user_c2c_log')->where('status=3 and '.$wtype)->value('price');
            }
        }
        $this->assign('list', $list);

        $this->assign('weike_count', $count);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('page', $page);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_total', $weike_total);
        return $this->fetch();
	}
	 public function cltrade()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = input('status',null);
        $market = strval(input('market'));
        $type = input('type');
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['buyid'] = DB::name('User')->where(array('username' => $name,))->value('id');
            } else if ($field == 'peername') {
                $where['sellid'] = DB::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;

            }
        }
        if ($type==1 || $type==2) {
            $where['type'] = $type;
        }

        if ($status) {
            $where['status'] = $status;
        }


        if($status == 0 && $status != null){
            $where['status'] = 0;
        }
        if ($type==1 || $type==2) {
            $where['type'] = $type;
        }



        $weike_getSum = DB::name('user_c2c_log')->where($where)->sum('price');
        $weike_num = DB::name('user_c2c_log')->where($where)->sum('price');

        $list = DB::name('user_c2c_log')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        foreach ($list as $k => $v) {
            if ($v['order'] == 1){
                if ($v['type'] == 1){
                    $list[$k]['username'] = DB::name('User')->where(array('id' => $v['buyid']))->value('username');
                    $list[$k]['peername'] = DB::name('user_c2c')->where(array('id' => $v['sellid']))->value('name');
                    $list[$k]['usertype'] = 1;
                }else{
                    $list[$k]['username'] = DB::name('user_c2c')->where(array('id' => $v['buyid']))->value('name');
                    $list[$k]['peername'] = DB::name('User')->where(array('id' => $v['sellid']))->value('username');
                    $list[$k]['usertype'] = 1;
                }
            }else{
                $list[$k]['username'] = DB::name('User')->where(array('id' => $v['buyid']))->value('username');
                $list[$k]['peername'] = DB::name('User')->where(array('id' => $v['sellid']))->value('username');
                $list[$k]['usertype'] = DB::name('User')->where(array('id' => $v['sellid']))->value('usertype');
            }

        }

        $this->assign('weike_count', $count);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
	}
	 public function c2cchexiao()
    {
        if ($this->request->isAjax()) {
            $id = I('id/d');
            $trade = M('UserC2cTrade')->lock(true)->where(['id' => $id])->find();

            if ($trade['order'] == 0){
                if ($trade['type'] == 1) {
                    //买单撤销
                    if ($trade['businessid'] == 0) {
                        $chage_status = Db::name('UserC2cTrade')->where(['id' => $id])->update(['status' => 2]);
                        if ($chage_status) {
                            $this->success('撤单成功');
                        } else {
                            $this->error('撤单失败，请联系客服人员');
                        }
                    } else {

                        $data = M('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                        $sell = M('UserC2cTrade')->where(['tradeno'=>$data['selltradeno']])->find();

                        $mo = M();
                        $mo->execute('set autocommit=0');
                        $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write ,weike_user_coin write,weike_finance write');
                        $rs = [];

                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2,'endtime'=>time()]);
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $data['selltradeno']])->update(['status' => 0, 'businessid'=>0]);
                        $rs[] = $mo->table('weike_user_c2c_log')->where(['id' => $data['id']])->update(['status' => 2]);


                        if (check_arr($rs)) {
                            $mo->execute('commit');
                            $mo->execute('unlock tables');
                            $title = C('web_name');
                            $sellmoble = $data['sellmoble'];
                            $message = '【火网】尊敬的火网用户，您的卖单'.$data['selltradeno'].'，成功匹配金额'.$data['price'].'，买家撤销了订单，请您重新下单。如有疑问，请联系官方客服。';
                            send_moble($sellmoble, $title, $message);
                            $this->success('撤单成功！');
                        } else {
                            $mo->execute('rollback');
                            $mo->execute('unlock tables');
                            $this->error('撤单失败！');
                        }
                    }
                } else {
                    //卖单撤销
                    if ($trade['businessid'] != 0) {
                        $this->error('订单已匹配成功，无法撤单');
                    }
                    $sell_num = $trade['price'] - $trade['deal'];
                    $fee = 0;
                    $sell_fee = 0;
                    $is_sell = 0;
                    if ($trade['deal'] == 0){
                        //在未有交易的情况
                        if ($trade['selltype'] == 1){
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.005;
                                $fee = $trade['price'] * $bili < 5 ? 5 : $trade['price'] *$bili;
                            }else{
                                $fee = $trade['price'] * 0.005 < 5 ? 5 : $trade['price'] *0.005;
                            }
                        }else{
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.01;
                                $fee = $trade['price'] * $bili < 5 ? 5 : $trade['price'] *$bili;
                            }else{
                                $fee = $trade['price'] * 0.01 < 5 ? 5 : $trade['price'] *0.01;
                            }
                        }

                    }else{
                        $is_sell = 1;
                        if ($trade['selltype'] == 1){
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.005;
                                if ($trade['price'] * $bili <= 5){
                                    $fee = 5 - ($trade['deal'] * $bili);
                                }else{
                                    $fee = ($trade['price'] * $bili) - ($trade['deal'] * $bili <= 5 ? 5 : $trade['deal'] * $bili);
                                }
                            }else{
                                if ($trade['price'] * 0.005 <= 5){
                                    $fee = 5 - ($trade['deal'] * 0.005);
                                }else{
                                    $fee = ($trade['price'] * 0.005) - ($trade['deal'] * 0.005 <= 5 ? 5 : $trade['deal'] * 0.005);
                                }
                            }
                        }elseif ($trade['selltype'] == 2){
                            if ($trade['tx_num'] > 2){
                                $bili = ($trade['tx_num']-1)*0.01;
                                if ($trade['price'] * $bili <= 5){
                                    $fee = 5 - ($trade['deal'] * $bili);
                                }else{
                                    $fee = ($trade['price'] * $bili) - ($trade['deal'] * $bili <= 5 ? 5 : $trade['deal'] * $bili);
                                    $sell_fee = $trade['deal'] * $bili <= 5 ? 5 : $trade['deal'] * $bili;
                                }
                            }else{
                                if ($trade['price'] * 0.01 <= 5){
                                    $fee = 5 - ($trade['deal'] * 0.01);
                                }else{
                                    $fee = ($trade['price'] * 0.01) - ($trade['deal'] * 0.01 <= 5 ? 5 : $trade['deal'] * 0.01);
                                }
                            }
                        }
                    }
                    $total = $sell_num + $fee;
                    $mo = M();
                    $mo->execute('set autocommit=0');
                    $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write ,weike_user_coin write,weike_finance write');
                    $rs = [];
                    $finance = $mo->table('weike_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
                    $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $trade['userid']))->find();
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2,'is_sell'=>$is_sell]);
                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $trade['userid']])->setInc('cny', $total);
                    $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $trade['userid']))->find();
                    $finance_hash = md5($trade['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $trade['price'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    $rs[] = $mo->table('weike_finance')->add(array('userid' => $trade['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $sell_num, 'type' => 2, 'name' => 'c2c', 'nameid' => $id, 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    if (check_arr($rs)) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        $this->success('撤单成功！');
                    } else {
                        $mo->execute('rollback');
                        $mo->execute('unlock tables');
                        $this->error('撤单失败！');
                    }
                }
            }else{
                if ($trade['type'] == 1){
                    $c2c_log = M('UserC2cLog')->where(['buytradeno' => $trade['tradeno']])->find();
                    $mo = M();
                    $mo->execute('set autocommit=0');
                    $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write,weike_user_c2c write');
                    $rs = [];
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2]);
                    $rs[] = $mo->table('weike_user_c2c')->where(['id' => $c2c_log['sellid']])->setInc('deal',$trade['price']);
                    $rs[] = $mo->table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->update(['status' => 2]);
                    if (check_arr($rs)) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        $this->success('撤单成功！');
                    } else {
                        $mo->execute('rollback');
                        $mo->execute('unlock tables');
                        $this->error('撤单失败！');
                    }
                }
            }
        }
    }
	//确认收款
    public function confirm()
    {
        if ($this->request->isAjax()) {
            $id = input('id');
            $trade = DB::name('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['businessid' == 0 && $trade['status'] ==0]){
                $this->error('订单正在匹配！');
            }
            require_once COMMON_PATH . 'Ext/SmsMeilian.class.php';
            $username='dctx';  //用户名
            $password_md5='0b11ac988314c2399752d3b4d875b217';  //32位MD5密码加密，不区分大小写
            $apikey='e525954fc72f54324d3c4a7bd2fc20c6';
            $smsMeilian = new \SmsMeilian();


            $c2c_log = DB::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->order('id desc')->find();
            //判断是用户之间的交易还是用户和系统之间的交易
            if ($trade['order'] == 1) {
                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write');
                $rs = [];
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 1,'endtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->update(['status' => 1,'endtime' => time()]);
                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->success('卖出成功！');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('卖出失败！');
                }
            } else {
//                $userid = M('UserBank')->where(['id' => $trade['businessid']])->getField('userid');
                $buy = DB::name('UserC2cLog')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->order('id desc')->find();
                $buyid = DB::name('UserC2cTrade')->where(['tradeno' => $buy['buytradeno']])->value('id');
                $trade = DB::name('UserC2cTrade')->where(['id' => $id])->find();
                if ($trade['businessid'] == 0 && $trade['status'] == 0){
                    $this->error('订单已确认收款，不可以重复操作！');
                }elseif ($trade['businessid'] != 0 && $trade['status'] == 1){
                    $this->error('订单已成交，不可以重复操作！');
                }
                //修改订单状态
                $mo = DB::name();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_coin write, weike_user_c2c_trade write ,weike_user_bank write ,weike_user_c2c_log write,weike_finance write');
                $rs = [];
                $finance = $mo->table('weike_finance')->where(array('userid' => $buy['buyid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->setInc('deal',$c2c_log['price']);
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->setInc('deal',$c2c_log['price']);
                $rs[] = $deal = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->value('deal');
                if($trade['price'] - $deal == 0){
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['status' => 1, 'endtime' => time()]);
                }else{
                    if ($trade['price'] - $deal > 100 && $trade['price'] - $deal > $trade['min_num']){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0,'status' => 0]);
                    }else if ($trade['price'] - $deal > 100 && $trade['num'] - $deal <= $trade['min_num']){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0,'status' => 0,'min_num'=>$trade['price'] - $deal]);
                    }else if ($trade['price'] - $deal <= 100 && $trade['price'] - $deal <= $trade['min_num'] ){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->update(['businessid' => 0,'status' => 0,'min_num'=>$trade['price'] - $deal]);
                    }
                }
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->update(['status' => 1, 'endtime' => time()]);
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['buyid']])->setInc('cny', $c2c_log['price']);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->update(['status' => 1 ,'endtime' => time()]);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                $finance_hash = md5($buy['buyid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $trade['price'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }
                $rs[] = $mo->table('weike_finance')->insert(array('userid' => $buy['buyid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $c2c_log['price'], 'type' => 1, 'name' => 'c2c', 'nameid' => $buyid, 'remark' => '点对点交易-买入交易', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->success('卖出成功！');
                } else {
                    $mo->execute('rollback');
                    $mo->execute('unlock tables');
                    $this->error('卖出失败！');
                }
            }
        }
    }
}

?>