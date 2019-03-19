<?php
namespace Admin\Controller;
use Think\Db;
use Think\Page;

class TradeController extends AdminController
{
	public function index()
	{
        $name = I('name/s', null);
        $field = I('field/s', null);
        $market = I('market/s', null);
        $status = I('status/s', null);
        $type = I('type/d', 0);
		$where = [];
		$where['userid'] = ['neq',33757];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
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

		$count = M('Trade')->where($where)->count();
		$weike_getSum = M('Trade')->where($where)->sum('mum');

        //获取已成交总数量，已成交总额额
        $weike_num = M('Trade')->where($where)->sum('deal');
        $weike_total = round(M('Trade')->where($where)->sum('price * deal'), 8);
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Trade')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['usertype'] = M('User')->where(array('id' => $v['userid']))->getField('usertype');
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
		$this->assign('page', $show);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_total', $weike_total);
		$this->display();
	}

	//撤单
	public function chexiao()
	{
        $id = I('id/d');

		$rs = D('Trade')->chexiao($id);
		if ($rs[0]) {
			$this->success($rs[1]);
		} else {
			$this->error($rs[1]);
		}
	}

	//一键撤单
    public function autochexiao()
    {
        $ids = I('ids/s');
        $ids = explode(',', $ids);
        $count = count($ids);
        if($count > 15){
            $this->error('不能撤销数量超过 15 的委托！');
        }

        foreach ($ids as $k => $v){
            $rs = D('Trade')->chexiao($v);
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
	    $jqren_id = M('user')->field('id')->where('usertype=1')->select();
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
            $uid = M('user')->where('username="'.$name.'"')->getField('id');
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
        $count = count(M()->query($sql));

		$Page = new Page($count,20);
		$show = $Page->show();

        $sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE '.$where.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u	LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE '.$where.' GROUP BY u.id) a
	              GROUP BY a.id order by zongshu desc limit '.$Page->firstRow.','.$Page->listRows;
		$list = M()->query($sql);


        $weike_fee = round(M('trade_log')->where('userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')')->sum('fee_buy+fee_sell'),4);
        $weike_mum = round(M('trade_log')->where('userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')')->sum('mum'),4);

		$now_fee = round(M('trade_log')
                ->where('(userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')) and addtime between '.$addtime.' and '.$endtime)
                ->sum('fee_buy+fee_sell'),4);

        foreach ($list as $k=>$v){
            if (date('Y-m-d',$addtime) != date('Y-m-d',$endtime)){
                $list[$k]['release'] = 0;
            }else{
                if (!M('fenhong_log')->where('userid='.$v['id'].' and grant_start_time='.$addtime.' and grant_end_time='.$endtime)->find()){
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
		$this->assign('page', $show);
		$this->assign('num',$Page->firstRow);
		$this->display();
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
        

		$usercoin=M('fenhong_log')->where('userid='.$id.' and grant_start_time='.$addtime.' and grant_end_time='.$endtime)->find();
		if($usercoin){
			$this->error('分红已发放,请不要重复发放！');
		}


		M()->startTrans();
		try{
            $addcny=M('fenhong_log')->where(array('userid'=>$id,'addtime'=>array('between',"$addtime,$endtime")))->save(array('release'=>1));
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
            $fenlog_id = M('fenhong_log')->add($data);
            $pre_user = M('user_coin')->where('id='.$id)->find();
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
            M('finance')->add($finance);
            M('user_coin')->where(array('userid'=>$id))->setInc('cny',$fengong);
		    $flag = true;
		    M()->commit();
        }Catch(\Exception $e){
		    $flag = false;
		    M()->rollback();
        }

        if ($flag){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！');
		}

	}


    public function log()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
        $market = strval(I('market'));
        $type = I('type');
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name,))->getField('id');
			} else if ($field == 'peername') {
				$where['peerid'] = M('User')->where(array('username' => $name))->getField('id');
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
		$count = M('TradeLog')->where($where)->count();
        $weike_getSum = M('TradeLog')->where($where)->sum('mum');
        $weike_num = M('TradeLog')->where($where)->sum('num');
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('TradeLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['peername'] = M('User')->where(array('id' => $v['peerid']))->getField('username');
            $list[$k]['usertype'] = M('User')->where(array('id' => $v['peerid']))->getField('usertype');
        }

		$this->assign('weike_count', $count);
        $this->assign('weike_num', $weike_num);
		$this->assign('weike_getSum', $weike_getSum);
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function chat()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		$count = M('Chat')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Chat')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function chatStatus()
	{
        $id = I('id/d');
        $type = I('type');
        $moble = I('moble', 'Chat');
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
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function comment()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $coinname = strval(I('coinname'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}

		$count = M('CoinComment')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('CoinComment')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function commentStatus()
	{
        $id = I('id/d');
        $type = I('type');
        $moble = I('moble','CoinComment');
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
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function market()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		$count = M('Market')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Market')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
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
		$this->assign('page', $show);
		$this->display();
	}

	public function marketEdit()
	{
        $id = I('id/d');
        if (empty($_POST)) {
            $_POST = I('post./a');
            if (empty($id)) {
                $this->data = array();

                $beginshi = "00";
                $beginfen = "00";
                $endshi = "23";
                $endfen = "59";

            } else {
                $market_weike = M('Market')->where(array('id' => $id))->find();
                $auto_trade = M('AutoTrade')->where(['market' => $market_weike['name']])->find();
                $market_weike['auto_volume'] = $auto_trade['volume'];
                $market_weike['auto_min'] = $auto_trade['min'];
                $market_weike['auto_max'] = $auto_trade['max'];
                $market_weike['auto_kaiguan'] = $auto_trade['status'];
                $market_weike['auto_brush_interval'] = $auto_trade['brush_interval'];
                $this->data = $market_weike;

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
            $this->display();
        } else {
            $_POST = I('post./a');
            $round = array(0, 1, 2, 3, 4, 5, 6, 7, 8);
            if (!in_array($_POST['round'], $round)) {
                $this->error('小数位数格式错误！');
            }

            if ($_POST['id']) {
                $name = M('Market')->where(['id' => $_POST['id']])->getField('name');
                $new_price = M('TradeLog')->where(['market' => $name])->order('id desc')->getField('price');
                $deal_toble = $new_price * $_POST['auto_volume'];
                $auto = M('AutoTrade')->where(['market' => $name])->save(['deal_toble' => $deal_toble ,'min' => $_POST['auto_min'] , 'max' => $_POST['auto_max'] , 'volume' => $_POST['auto_volume'] ,'status' => $_POST['auto_kaiguan'],'brush_interval'=>$_POST['auto_brush_interval'] ]);
                $rs = M('Market')->save(["jiaoyiqu" => $_POST['jiaoyiqu'] , "round" => $_POST["round"] , "fee_buy" => $_POST["fee_buy"] , "fee_sell"=> $_POST["fee_sell"],
                    "buy_min" => $_POST["buy_min"] , "buy_max" => $_POST["buy_max"] , "sell_min" => $_POST["sell_min"] , "sell_max" => $_POST["sell_max"] ,
                    "trade_min" => $_POST["trade_min"] , "trade_max" => $_POST["trade_max"] , "invit_1" => $_POST["invit_1"] ,"invit_2" => $_POST["invit_2"] ,
                    "invit_3" => $_POST["invit_3"] , "invit_buy"=>$_POST["invit_buy"] , "invit_sell"=>$_POST["invit_sell"] , "weike_faxingjia" => $_POST["weike_faxingjia"],
                    "zhang"=> $_POST["zhang"] , "die" => $_POST["die"] , "hou_price" => $_POST["hou_price"], "begintrade" =>$_POST["begintrade"] ,
                    "endtrade"=> $_POST["endtrade"] , "trade" =>$_POST["trade"], "status"=>$_POST["status"], "id" => $_POST["id"]]);
            } else {
                $_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($_POST['buyname']);
                unset($_POST['sellname']);

                if (M('Market')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('市场存在！');
                }

                $rs = M('Market')->add(['name' => $_POST['name'],
                    "jiaoyiqu" => $_POST['jiaoyiqu'] , "round" => $_POST["round"] , "fee_buy" => $_POST["fee_buy"] , "fee_sell"=> $_POST["fee_sell"],
                    "buy_min" => $_POST["buy_min"] , "buy_max" => $_POST["buy_max"] , "sell_min" => $_POST["sell_min"] , "sell_max" => $_POST["sell_max"] ,
                    "trade_min" => $_POST["trade_min"] , "trade_max" => $_POST["trade_max"] , "invit_1" => $_POST["invit_1"] ,"invit_2" => $_POST["invit_2"] ,
                    "invit_3" => $_POST["invit_3"] , "invit_buy"=>$_POST["invit_buy"] , "invit_sell"=>$_POST["invit_sell"] , "weike_faxingjia" => $_POST["weike_faxingjia"],
                    "zhang"=> $_POST["zhang"] , "die" => $_POST["die"] , "hou_price" => $_POST["hou_price"] , "begintrade" =>$_POST["begintrade"] ,
                    "endtrade"=> $_POST["endtrade"] , "trade" =>$_POST["trade"], "status"=>$_POST["status"]]);
                $auto = M('AutoTrade')->add(['market' => $_POST['name'] ,'min' => $_POST['auto_min'] , 'max' => $_POST['auto_max'] , 'status' => $_POST['auto_kaiguan']]);
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
        $id = I('id/a');
        $type = I('get.type/s');
        $moble =I('moble/s', 'Market');
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
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function invit()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		$count = M('Invit')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Invit')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$userall=M('Invit')->where($where)->sum('fee');
		foreach($userall as $v){
			$userall['userall']=$v['allfee'];
		}
		$this->assign('userall', $userall);

		foreach ($list as $k => $v) {

			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['invit'] = M('User')->where(array('id' => $v['invit']))->getField('username');
		}
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

    //marketControl page
    public function marketControl()
    {
        $name = strval(I('name'));
        $field = strval(I('field'));
        $where = array();
        if ($field && $name) {
            $where[$field] = $name;
        }

        $count = M('MarketControl')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('MarketControl')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

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
        $this->assign('page', $show);
        $this->display();
    }

    //marketControl modify
    public function marketControlEdit()
    {
        $id = I('id/d');
        if (empty($_POST)) {
            $_POST = I('post./a');
            if (empty($id)) {
                $this->data = [];
            } else {
                $market_weike = M('MarketControl')->where(['id' => $id])->find();
                $rmb = explode('_', $market_weike['name'])[1];
                if ($rmb === 'btc'){
                    $market_weike['unit'] = $rmb;
                    $market_weike['api_unit'] = $rmb;
                } else {
                    $market_weike['unit'] = $rmb;
                    $market_weike['api_unit'] = 'usdt';
                }
                $this->data = $market_weike;
            }
            $this->display();
        } else {
            $_POST = I('post./a');

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
            $password = M('Admin')->where(array('id' => 11))->getField('password');
            if (md5($_POST['pass']) != $password) {
                $this->error('BOSS 密码不正确！');
            }
            unset($_POST['pass']);

            if ($_POST['id']) {
                $rs = M('MarketControl')->save($_POST);
            } else {
                if ($_POST['sellname'] === $_POST['buyname']) {
                    $this->error('市场错误！');
                }

                $_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($_POST['buyname']);
                unset($_POST['sellname']);

                if (M('MarketControl')->where(['name' => $_POST['name']])->find()) {
                    $this->error('市场存在！');
                }

                $rs = M('MarketControl')->add($_POST);
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
        $id = I('id/a');
        $type = I('get.type/s');
        $moble =I('moble/s', 'MarketControl');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        // 非 BOSS 不能操作市场
        if (session('admin_id') != 11) {
            $this->error('非 BOSS 不能操作市场！');
        }

        $where['id'] = array('in', $id);
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
                if (M($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('操作失败！');
        }

        if (M($moble)->where($where)->save($data)) {
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
		$id=I('id');
		if ($id) {
			$id = implode(',', $id);
		}
		$addtime=I('addtime');
		$endtime=I('endtime');

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
	$c_field = 'sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id,u.username,u.truename,t.addtime,t.userid,t.peerid,t.release';
		$sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release`,a.`addtime`,a.`userid`,a.`peerid` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE t.userid in ('.$id.') and t.addtime between '.$addtime.' and '.$endtime.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE t.peerid in ('.$id.') and t.addtime between '.$addtime.' and '.$endtime.' GROUP BY u.id) a
	              GROUP BY a.id order by zongshu desc';
		$list = M()->query($sql);

		foreach($list as $k=>$val){
			$list[$k]['benun']=$k+1;
			$list[$k]['addtime']=date('Y-m-d H:i:s',$addtime).'-'.date('Y-m-d H:i:s',$endtime);
			$list[$k]['sell']=M('user')->where(array('id'=>$val['peerid']))->getField('username');
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

		$addtime=I('addtime');
		$endtime=I('endtime');

		$c_field = 'sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id,u.username,u.truename,t.addtime,t.userid,t.peerid,t.release,u.usertype';
		$sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release`,a.`addtime`,a.`userid`,a.`peerid`,a.`usertype` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE  u.usertype=0 and t.addtime between '.$addtime.' and '.$endtime.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE u.usertype=0 and t.addtime between '.$addtime.' and '.$endtime.' GROUP BY u.id) a
	              GROUP BY a.id order by zongshu desc';
		$list = M()->query($sql);
        if(!$list){
			$this->error('导出记录为空!');
		}
		foreach($list as $k=>$val){
			$list[$k]['benun']=$k+1;
			$list[$k]['addtime']=date('Y-m-d H:i:s',$addtime).'-'.date('Y-m-d H:i:s',$endtime);
			$list[$k]['sell']=M('user')->where(array('id'=>$val['peerid']))->getField('username');
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
        $name = I('name/s', null);
        $field = I('field/s', null);
        $status = I('status/s', null);
        $type = I('type/d', 0);
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
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

        $count = M('user_c2c_trade')->where($where)->count();
        $weike_getSum = M('user_c2c_trade')->where($where)->sum('mum');


        //获取已成交总数量，已成交总额额
        $weike_num = M('user_c2c_trade')->where($where)->sum('deal');
        $weike_total = round(M('user_c2c_trade')->where($where)->sum('mum'), 8);
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('user_c2c_trade')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['usertype'] = M('User')->where(array('id' => $v['userid']))->getField('usertype');
            if($list[$k]['status'] == 2){//用户撤单后显示的交易金额
                if($list[$k]['deal'] != 0){
                    $list[$k]['mum'] =$list[$k]['deal'];
                }
            }

            $list[$k]['pipei'] = 0;
            if ($v['status'] == 3){
                $wtype = $v['type'] == 1 ? 'buytradeno="'.$v['tradeno'].'"':'selltradeno="'.$v['tradeno'].'"';
                $list[$k]['pipei'] = M('user_c2c_log')->where('status=3 and '.$wtype)->getField('price');
            }
        }
        $this->assign('list', $list);

        $this->assign('weike_count', $count);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('page', $show);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_total', $weike_total);
        $this->display();
	}

    public function cltrade($addtime='',$endtime='')
    {
        $name = trim(I('name'));
        $tradeCode = trim(I('tradeCode'));
        $field = trim(I('field'));
        $status = trim(I('status',null));
        $market = trim(I('market'));
        $type = trim(I('type'));
		$addtime ? $addtime = urldecode($addtime):false;
		$endtime ? $endtime = urldecode($endtime):false;
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['buyid'] = M('User')->where(array('username' => $name,))->getField('id');
            } else if ($field == 'peername') {
                $where['sellid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
        if ($type==1 || $type==2) $where['type'] = $type;

        if ($status) $where['status'] = $status;

        if ($tradeCode) $where['buytradeno|selltradeno'] = $tradeCode;

        if($status == 0 && $status != null){
            $where['status'] = 0;
        }
        if ($type==1 || $type==2) $where['type'] = $type;
		if ($addtime && $endtime){
			//转换时间戳
			$addtime = strtotime($addtime);
			$endtime = strtotime($endtime);
			//条件
			$where['addtime'] = array(array('gt',$addtime),array('lt',$endtime)) ;

		}
        $count = M('user_c2c_log')->where($where)->count();
        $weike_getSum = M('user_c2c_log')->where($where)->sum('price');
//        $weike_num = M('user_c2c_log')->where($where)->sum('price');
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('user_c2c_log')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
			$list[$k]['buystatus'] = M('UserC2cTrade')->where(array('tradeno' => $v['buytradeno']))->getField('bankstatus');
            if ($v['order'] == 1){
                if ($v['type'] == 1){
                    $list[$k]['username'] = M('User')->where(array('id' => $v['buyid']))->getField('username');
                    $list[$k]['peername'] = M('user_c2c')->where(array('id' => $v['sellid']))->getField('name');
                    $list[$k]['usertype'] = 1;
                }else{
                    $list[$k]['username'] = M('user_c2c')->where(array('id' => $v['buyid']))->getField('name');
                    $list[$k]['peername'] = M('User')->where(array('id' => $v['sellid']))->getField('username');
                    $list[$k]['usertype'] = 1;
                }
            }else{
                $list[$k]['username'] = M('User')->where(array('id' => $v['buyid']))->getField('username');
                $list[$k]['peername'] = M('User')->where(array('id' => $v['sellid']))->getField('username');
                $list[$k]['usertype'] = M('User')->where(array('id' => $v['sellid']))->getField('usertype');
            }

        }
        $where['status'] = 1;
        $weike_onnum = M('user_c2c_log')->where($where)->sum('price');

        $where['status'] = 2;
        $weike_unnum = M('user_c2c_log')->where($where)->sum('price');

        $this->assign('weike_count', $count);
//        $this->assign('weike_num', $weike_num);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('weike_onnum', $weike_onnum);
        $this->assign('weike_unnum', $weike_unnum);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
	}

    public function c2cchexiao()
    {
		$admin = M('Admin')->where(array('id' =>session('admin_id')))->getField('username');
        if (IS_AJAX) {
            $id = I('id/d');
            $trade = M('UserC2cTrade')->lock(true)->where(['id' => $id])->find();

            if ($trade['order'] == 0){
                if ($trade['type'] == 1) {
                    //买单撤销
                    if ($trade['businessid'] == 0) {
                        $chage_status = M('UserC2cTrade')->where(['id' => $id])->save(['status' => 2]);
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

                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->save(['status' => 2,'endtime'=>time()]);
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $data['selltradeno']])->save(['status' => 0, 'businessid'=>0,'useradmin'=>$admin]);
                        $rs[] = $mo->table('weike_user_c2c_log')->where(['id' => $data['id']])->save(['status' => 2]);


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
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->save(['status' => 2,'is_sell'=>$is_sell,'useradmin'=>$admin]);
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
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->save(['status' => 2,'useradmin'=>$admin]);
                    $rs[] = $mo->table('weike_user_c2c')->where(['id' => $c2c_log['sellid']])->setInc('deal',$trade['price']);
                    $rs[] = $mo->table('weike_user_c2c_log')->where(['buytradeno' => $trade['tradeno']])->save(['status' => 2]);
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
        if (IS_AJAX) {
            $id = I('id/d');
            $trade = M('UserC2cTrade')->where(['id' => $id])->find();
            if ($trade['businessid' == 0 && $trade['status'] ==0]){
                $this->error('订单正在匹配！');
            }
            require_once COMMON_PATH . 'Ext/SmsMeilian.class.php';
            $username='xzgr';  //用户名
            $password_md5='48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
            $apikey='b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
            $smsMeilian = new \SmsMeilian();


            $c2c_log = M('UserC2cLog')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->order('id desc')->find();
            //判断是用户之间的交易还是用户和系统之间的交易
            if ($trade['order'] == 1) {
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_c2c_trade write ,weike_user_c2c_log write');
                $rs = [];
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['id' => $id])->save(['status' => 1,'endtime' => time()]);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->save(['status' => 1,'endtime' => time()]);
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
                $buy = M('UserC2cLog')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->order('id desc')->find();
                $buyid = M('UserC2cTrade')->where(['tradeno' => $buy['buytradeno']])->getField('id');
                $trade = M('UserC2cTrade')->where(['id' => $id])->find();
                if ($trade['businessid'] == 0 && $trade['status'] == 0){
                    $this->error('订单已确认收款，不可以重复操作！');
                }elseif ($trade['businessid'] != 0 && $trade['status'] == 1){
                    $this->error('订单已成交，不可以重复操作！');
                }
                //修改订单状态
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_coin write, weike_user_c2c_trade write ,weike_user_bank write ,weike_user_c2c_log write,weike_finance write');
                $rs = [];
                $finance = $mo->table('weike_finance')->where(array('userid' => $buy['buyid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->setInc('deal',$c2c_log['price']);
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->setInc('deal',$c2c_log['price']);
                $rs[] = $deal = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->getField('deal');
                if($trade['price'] - $deal == 0){
                    $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->save(['status' => 1, 'endtime' => time()]);
                }else{
                    if ($trade['price'] - $deal > 100 && $trade['price'] - $deal > $trade['min_num']){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->save(['businessid' => 0,'status' => 0]);
                    }else if ($trade['price'] - $deal > 100 && $trade['num'] - $deal <= $trade['min_num']){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->save(['businessid' => 0,'status' => 0,'min_num'=>$trade['price'] - $deal]);
                    }else if ($trade['price'] - $deal <= 100 && $trade['price'] - $deal <= $trade['min_num'] ){
                        $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $trade['tradeno']])->save(['businessid' => 0,'status' => 0,'min_num'=>$trade['price'] - $deal]);
                    }
                }
                $rs[] = $mo->table('weike_user_c2c_trade')->where(['tradeno' => $buy['buytradeno']])->save(['status' => 1, 'endtime' => time()]);
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['buyid']])->setInc('cny', $c2c_log['price']);
                $rs[] = $mo->table('weike_user_c2c_log')->where(['selltradeno' => $trade['tradeno'],'status' => ['neq',2]])->save(['status' => 1 ,'endtime' => time()]);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['buyid']))->find();
                $finance_hash = md5($buy['buyid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $trade['price'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }
                $rs[] = $mo->table('weike_finance')->add(array('userid' => $buy['buyid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $c2c_log['price'], 'type' => 1, 'name' => 'c2c', 'nameid' => $buyid, 'remark' => '点对点交易-买入交易', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

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