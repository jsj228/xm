<?php

namespace app\home\controller;

use think\Db;
use think\Exception;

class Trade extends Home
{
	public function index()
	{
        $market = input('market/s', NULL);
		$showPW = 1;
		if (userid()) {
			$user = Db::name('User')->where(array('id' => userid()))->find();
			if ($user['tpwdsetting'] == 3) {
				$showPW = 0;
			}

			if ($user['tpwdsetting'] == 1) {
				if (session(userid() . 'tpwdsetting')) {
					$showPW = 2;
				}
			}
		}

		if (!$market) {
			$market = config('market_mr');
		}

		$market_time_weike = config('market')[$market]['begintrade']."-".config('market')[$market]['endtrade'];
		$buy_best_price = Db::name('Trade')->where(['market' => $market , 'status' => 0 , 'type' => 2])->order('price asc')->find();
		$sell_best_price = Db::name('Trade')->where(['market' => $market , 'status' => 0 , 'type' => 1])->order('price desc')->find();
        $sell_best_price['price'] =round($sell_best_price['price'] , 4) ;
        $buy_best_price['price'] =round($buy_best_price['price'] , 4) ;
		$this->assign('market_time', $market_time_weike);
		$this->assign('buy_best_price', $buy_best_price);
		$this->assign('sell_best_price', $sell_best_price);
		$this->assign('showPW', $showPW);
		$this->assign('market', $market);
		$this->assign('xnb', explode('_', $market)[0]);
		$this->assign('rmb', explode('_', $market)[1]);
		return $this->fetch();
	}

	public function chart()
	{
        $market = input('market/s', NULL);
		if (!$market) {
			$market = config('market_mr');
		}

		$this->assign('market', $market);
		$this->assign('xnb', explode('_', $market)[0]);
		$this->assign('rmb', explode('_', $market)[1]);
		return $this->fetch();
	}

	public function chartweike(){
        $weike = input('weike/s', NULL);
		if (!$weike) {
			$weike = config('market_mr');
		}
		
		$weike_getCoreConfig = weike_getCoreConfig();
		if(!$weike_getCoreConfig){
			$this->error('');
		}
		
		$jiaoyiqu = cache('jiaoyiqu');
		if(!$jiaoyiqu){
			foreach(config('market') as $k => $v){
				$jiaoyiqu[$v['jiaoyiqu']][] = $k;
			}
			cache('jiaoyiqu',$jiaoyiqu);
		}
		
		$this->assign('weike', $weike);
		$this->assign('weike_jiaoyiqu', $weike_getCoreConfig['weike_indexcat']);
		$this->assign('weike_marketjiaoyiqu', $jiaoyiqu);
		$this->assign('weike_xnb', explode('_', $weike)[0]);
		$this->assign('weike_rmb', explode('_', $weike)[1]);
		return $this->fetch();
		
	}

	public function info()
	{
        $market = input('market/s', NULL);
		if (!userid()) {
            $this->error('请先登录！');
		}

		if (!$market) {
			$market = config('market_mr');
		}

		$this->assign('market', $market);
		$this->assign('xnb', explode('_', $market)[0]);
		$this->assign('rmb', explode('_', $market)[1]);
		return $this->fetch();
	}

	public function comment()
	{
        $market = input('market/s', NULL);
		if (!userid()) {
            $this->error('请先登录！');
		}

		if (!$market) {
			$market = config('market_mr');
		}

		if (!$market) {
			$market = config('market_mr');
		}

		$this->assign('market', $market);
		$this->assign('xnb', explode('_', $market)[0]);
		$this->assign('rmb', explode('_', $market)[1]);
		$where['coinname'] = explode('_', $market)[0];

		$list = Db::name('CoinComment')->where($where)->order('id desc')->paginate(10);
		$show = $list->render();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function ordinary()
	{
        $market = input('market/s', NULL);
		if (!$market) {
			$market = config('market_mr');
		}

		$this->assign('market', $market);
		return $this->fetch();
	}

	public function specialty()
	{
        $market = input('market/s', NULL);
		if (!$market) {
			$market = config('market_mr');
		}

		$this->assign('market', $market);
		return $this->fetch();
	}

	//判断用户是买还是卖
	public function upTrade()
	{
        if(IS_POST) {
            //token验证
            $token_str = input('post.token_str');
            if(!token_check($token_str)){
                $this->error('令牌错误！');
            }
            $paypassword = input('paypassword/s', NULL);
            $market = input('market/s', NULL);
            $price = input('price/f');
            $num = input('num/f');
            $type = input('type/d');

            if (!userid()) {
                $this->error('请先登录！');
            } else {
                // 获取用户信息
                $user = Db::name('User')->where(array('id' => userid()))->find();

                if (!config('market')[$market]['trade']) {
                    if ($user['usertype'] == 0 || $user['usertype'] == 9) {
                        $this->error('非交易时间段，禁止交易');
                    }
                }

                if (strlen($price) > 8 || strlen($num) > 8) {
                    $this->error('价格或者数量超过预定长度！');
                }

                if (config('market')[$market]['begintrade']) {
                    $begintrade = config('market')[$market]['begintrade'];
                } else {
                    $begintrade = "00:00:00";
                }

                if (config('market')[$market]['endtrade']) {
                    $endtrade = config('market')[$market]['endtrade'];
                } else {
                    $endtrade = "23:59:59";
                }

                $trade_begin_time = strtotime(date("Y-m-d") . " " . $begintrade);
                $trade_end_time = strtotime(date("Y-m-d") . " " . $endtrade);
                $cur_time = time();

                if ($cur_time < $trade_begin_time || $cur_time > $trade_end_time) {
                    $this->error('当前市场禁止交易,交易时间为每日' . $begintrade . '-' . $endtrade);
                }

                if (!check($price, 'double')) {
                    $this->error('交易价格格式错误');
                }

                if (!check($num, 'double')) {
                    $this->error('交易数量格式错误');
                }

                if (($type != 1) && ($type != 2)) {
                    $this->error('交易类型格式错误');
                }

                //每次交易必须要整交易密码
                if ($user['tpwdsetting'] == 2) {
                    if (md5($paypassword) != $user['paypassword']) {
                        $this->error('交易密码错误！');
                    }
                }

                //登陆只需验证一次（$user['tpwdsetting']设置交易密码方式）
                if ($user['tpwdsetting'] == 1) {
                    if (!session(userid() . 'tpwdsetting')) {
                        if (md5($paypassword) != $user['paypassword']) {
                            $this->error('交易密码错误！');
                        } else {
                            session(userid() . 'tpwdsetting', 1);
                        }
                    }
                }

                if (!config('market')[$market]) {
                    $this->error('交易市场错误');
                } else {
                    $xnb = explode('_', $market)[0];//要交易的币
                    $rmb = explode('_', $market)[1];
                }

                // TODO: SEPARATE
                $price = round(floatval($price), config('market')[$market]['round']);
                if (!$price) {
                    $this->error('交易价格错误' . $price);
                }

                $num = round($num, 8 - config('market')[$market]['round']);
                if (!check($num, 'double')) {
                    $this->error('交易数量错误');
                }

                //$type的值来判断用户 是买 还是卖
                if ($type == 1) {
                    $min_price = (config('market')[$market]['buy_min'] ? config('market')[$market]['buy_min'] : 1.0E-8);//买入最低价
                    $max_price = (config('market')[$market]['buy_max'] ? config('market')[$market]['buy_max'] : 10000000);//买入最高价
                } else if ($type == 2) {
                    $min_price = (config('market')[$market]['sell_min'] ? config('market')[$market]['sell_min'] : 1.0E-8);//卖出最低价
                    $max_price = (config('market')[$market]['sell_max'] ? config('market')[$market]['sell_max'] : 10000000);//卖出最高价
                } else {
                    $this->error('交易类型错误');
                }

                if ($max_price < $price) {
                    $this->error('交易价格超过最大限制！');
                }

                if ($price < $min_price) {
                    $this->error('交易价格低过最小限制！');
                }

                $hou_price = config('market')[$market]['hou_price'];   //每天 最后的价格
                if (!$hou_price) {
                    $hou_price = config('market')[$market]['weike_faxingjia'];
                }
                /**
                 * 判断价格是否在涨跌幅中
                 * 前提要有收盘价和设定了涨跌幅
                 */
                if ($hou_price) {
                    if (config('market')[$market]['zhang']) {
                        $zhang_price = round(($hou_price / 100) * (100 + config('market')[$market]['zhang']), config('market')[$market]['round']);

                        if ($zhang_price < $price) {
                            $this->error('交易价格超过今日涨幅限制！');
                        }
                    }

                    if (config('market')[$market]['die']) {
                        $die_price = round(($hou_price / 100) * (100 - config('market')[$market]['die']), config('market')[$market]['round']);

                        if ($price < $die_price) {
                            $this->error('交易价格超过今日跌幅限制！');
                        }
                    }
                }
                

                $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
                if ($type == 1) {
                    //操盘手 买入不需要手续费
                    if ($user['usertype'] == 3) {
                        $trade_fee = 0;
                    } else {
                        $trade_fee = config('market')[$market]['fee_buy'];//交易手续费
                    }

                    if ($trade_fee) {
                        $fee = round((($num * $price) / 100) * $trade_fee, 8);//买入的手续费
                        $mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);//用户买入时产生的费用
                    } else {
                        $fee = 0;
                        $mum = round($num * $price, 8);
                    }

                    if ($user_coin[$rmb] < $mum) {//用户可用资产 与  用户买入时产生的费用 比较
                        $this->error(config('coin')[$rmb]['title'] . '余额不足！');
                    }
                } else if ($type == 2) {
                    //操盘手 卖出不需要手续费
                    if ($user['usertype'] == 3) {
                        $trade_fee = 0;
                    } else {
                        $trade_fee = config('market')[$market]['fee_sell'];//交易手续费
                    }

                    if ($trade_fee) {
                        $fee = round((($num * $price) / 100) * $trade_fee, 8);//卖出的手续费
                        $mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);//？
                    } else {
                        $fee = 0;
                        $mum = round($num * $price, 8);
                    }

                    if ($user_coin[$xnb] < $num) {//判断用户该币可用数量
                        $this->error(config('coin')[$xnb]['title'] . '余额不足！');
                    }
                } else {
                    $this->error('交易类型错误');
                }

                if (config('coin')[$xnb]['fee_bili']) {
                    if ($type == 2) {
                        // TODO: SEPARATE
                        $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], config('market')[$market]['round']);

                        if ($bili_user) {
                            // TODO: SEPARATE
                            $bili_keyi = round(($bili_user / 100) * config('coin')[$xnb]['fee_bili'], config('market')[$market]['round']);

                            if ($bili_keyi) {
                                $bili_zheng = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['userid' => userid(), 'status' => 0, 'type' => 2, 'market' => ['like', "%$xnb%"]])->find();
                                if (!$bili_zheng['nums']) {
                                    $bili_zheng['nums'] = 0;
                                }

                                $bili_kegua = $bili_keyi - $bili_zheng['nums'];
                                if ($bili_kegua < 0) {
                                    $bili_kegua = 0;
                                }

                                if ($bili_kegua < $num) {
                                    $this->error('您的挂单总数量超过系统限制，您当前持有' . config('coin')[$xnb]['title'] . $bili_user . '个，已经挂单' . $bili_zheng['nums'] . '个，还可以挂单' . $bili_kegua . '个', '', 5);
                                }
                            } else {
                                $this->error('可交易量错误');
                            }
                        }
                    }
                }

                if (config('coin')[$xnb]['fee_meitian']) {
                    if ($type == 2) {
                        $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], 8);

                        if ($bili_user < 0) {
                            $this->error('可交易量错误');
                        }

                        $kemai_bili = ($bili_user / 100) * config('coin')[$xnb]['fee_meitian'];
                        if ($kemai_bili < 0) {
                            $this->error('您今日只能再卖' . config('coin')[$xnb]['title'] . 0 . '个', '', 5);
                        }

                        $kaishi_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                        $jintian_sell = Db::name('Trade')->where(array(
                            'userid' => userid(),
                            'addtime' => array('egt', $kaishi_time),
                            'type' => 2,
                            'status' => array('neq', 2),
                            'market' => array('like', '%' . $xnb . '%')
                        ))->sum('num');

                        if ($jintian_sell) {
                            $kemai = $kemai_bili - $jintian_sell;
                        } else {
                            $kemai = $kemai_bili;
                        }

                        if ($kemai < $num) {
                            if ($kemai < 0) {
                                $kemai = 0;
                            }

                            $this->error('您的挂单总数量超过系统限制，您今日只能再卖' . config('coin')[$xnb]['title'] . $kemai . '个', '', 5);
                        }
                    }
                }

                if (config('market')[$market]['trade_min']) {
                    if ($mum < config('market')[$market]['trade_min']) {
                        $this->error('交易总额不能小于' . config('market')[$market]['trade_min']);
                    }
                }

                if (config('market')[$market]['trade_max']) {
                    if (config('market')[$market]['trade_max'] < $mum) {
                        $this->error('交易总额不能大于' . config('market')[$market]['trade_max']);
                    }
                }

                if (!$rmb) {
                    $this->error('数据错误1');
                }

                if (!$xnb) {
                    $this->error('数据错误2');
                }

                if (!$market) {
                    $this->error('数据错误3');
                }

                if (!$price) {
                    $this->error('数据错误4');
                }

                if (!$num) {
                    $this->error('数据错误5');
                }

                if (!$mum) {
                    $this->error('数据错误6');
                }

                if (!$type) {
                    $this->error('数据错误7');
                }


                Db::startTrans();
                try {
                    $rs = [];
                    $user_coin = $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => userid()))->find();
                    if ($type == 1) {
                        if ($user_coin[$rmb] < $mum) {
                            Db::rollback();
                            $this->error(config('coin')[$rmb]['title'] . '余额不足！');
                        }

                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => userid()))->order('id desc')->find();//用户资产
                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setDec($rmb, $mum);//可用资产   剩余的
                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setInc($rmb . 'd', $mum);
                        $rs[] = $finance_nameid = Db::table('weike_trade')->insertGetId(array('userid' => userid(), 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));

                        if ($rmb == "hkd") {//hkd 换成  hkd
                            $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => userid()))->find();
                            $finance_hash = md5(userid() . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mum . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                            $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                            if ($finance['mum'] < $finance_num) {
                                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                            } else {
                                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                            }

                            $rs[] = Db::table('weike_finance')->insert(array('userid' => userid(), 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => '交易中心-委托买入-市场' . $market, 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        }

                    } else if ($type == 2) {
                        if ($user_coin[$xnb] < $num) {
                            Db::rollback();
                            $this->error(config('coin')[$xnb]['title'] . '余额不足2！');
                        }

                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setDec($xnb, $num);
                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setInc($xnb . 'd', $num);
                        $rs[] = $finance_nameid = Db::table('weike_trade')->insertGetId(array('userid' => userid(), 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
                    } else {
                        Db::rollback();
                        $this->error('交易类型错误');
                    }

                    if (check_arr($rs)) {
                        Db::commit();
                        cache('getDepth', null);
                        $this->matchingTrade($market);
                        $this->success('挂单成功！您获得了一次免费领取POS机的机会，详情联系客服。',null,['market'=>$market]);
                    } else {
                        Db::rollback();
                        $this->error('交易失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('交易失败！');
                }
            }
        }
	}

    //撮合买卖
    public function matchingTrade($market='')
    {

        if (!$market) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }
        $user_type = Db::name('User')->where('id',userid())->value('usertype');
        if ($user_type == 3){
            $fee_buy = 0;
            $fee_sell = 0;
        }else{
            $fee_buy = config('market')[$market]['fee_buy'];
            $fee_sell = config('market')[$market]['fee_sell'];
        }
        $invit_buy = config('market')[$market]['invit_buy'];
        $invit_sell = config('market')[$market]['invit_sell'];
        $invit_1 = config('market')[$market]['invit_1'] ? config('market')[$market]['invit_1']:0;
        $invit_2 = config('market')[$market]['invit_2'] ? config('market')[$market]['invit_2']:0;
        $invit_3 = config('market')[$market]['invit_3'] ? config('market')[$market]['invit_3']:0;
        $new_trade_weike = 0;

        for (; true; ) {
            Db::startTrans();
            try {
                $buy = Db::table('weike_trade')->lock(true)->where(array('market' => $market, 'type' => 1, 'userid' => array('gt', 0), 'status' => 0))->order('price desc,id asc')->find();
                $sell = Db::table('weike_trade')->lock(true)->where(array('market' => $market, 'type' => 2, 'userid' => array('gt', 0), 'status' => 0))->order('price asc,id asc')->find();
                if(!$buy || ! $sell){
                    Db::rollback();
                    break;
                }

                if ($buy['status'] != 0 || $sell['status'] != 0) {
                    Db::rollback();
                    break;
                }

                if (0 <= floatval($buy['price']) - floatval($sell['price'])) {
                    //控制插队现象，要让其中一个委托单匹配完它之前对方的单，才可以匹配它之后对方的单

                    $buy_second = Db::table('weike_trade')->where(array('id'=>array('lt',$sell['id']),'market' => $market, 'type' => 1, 'userid' => array('gt', 0), 'status' => 0))->order('price desc,id asc')->find();
                    //控制买家插队
                    if ($buy_second && ($buy['id']!=$buy_second['id']) && (0 <= floatval($buy_second['price']) - floatval($sell['price'])) ){
                        $buy = Db::table('weike_trade')->lock(true)->where(array('id'=>$buy_second['id']))->find();
                    //控制卖家插队
                    }else{
                        $sell_second = Db::table('weike_trade')->where(array('id'=>array('lt',$buy['id']),'market' => $market, 'type' => 2, 'userid' => array('gt', 0), 'status' => 0))->order('price asc,id asc')->find();
                        if($sell_second && ($sell['id'] != $sell_second['id']) && (0 <= floatval($buy['price']) - floatval($sell_second['price']))) {
                            $sell = Db::table('weike_trade')->lock(true)->where(array('id'=>$sell_second['id']))->find();
                        }
                    }

                    if(!$buy || !$sell){
                        Db::rollback();
                        break;
                    }

                    if ($sell['id'] < $buy['id']) {
                        $type = 1;
                    } else {
                        $type = 2;
                    }

                    $amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']));
                    $amount = round($amount, 8 - config('market')[$market]['round']);
                    if ($amount <= 0) {
                        $log = '错误1交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . "\n";
                        $log .= 'ERR: 成交数量出错，数量是' . $amount;
                        mlog($log);
                        Db::table('weike_trade')->where(array('id' => $buy['id']))->setField(['endtime' => time(), 'status' => 1]);
                        Db::table('weike_trade')->where(array('id' => $sell['id']))->setField(['endtime' => time(), 'status' => 1]);
                        Db::commit();
                        break;
                    }

                    if ($type == 1) {
                        $price = $sell['price'];
                    } else if ($type == 2) {
                        $price = $buy['price'];
                    } else {
                        Db::rollback();
                        break;
                    }

                    if (!$price) {
                        $log = '错误2交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                        $log .= 'ERR: 成交价格出错，价格是' . $price;
                        mlog($log);
                        Db::rollback();
                        break;
                    } else {
                        $price = round($price, config('market')[$market]['round']);
                    }

                    $mum = round($price * $amount, 8);
                    if (!$mum) {
                        $log = '错误3交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                        $log .= 'ERR: 成交总额出错，总额是' . $mum;
                        mlog($log);
                        Db::rollback();
                        break;
                    } else {
                        $mum = round($mum, 8);
                    }

                    if ($fee_buy) {
                        $buy_fee = round(($mum / 100) * $fee_buy, 8);
                        $buy_save = round(($mum / 100) * (100 + $fee_buy), 8);
                    } else {
                        $buy_fee = 0;
                        $buy_save = $mum;
                    }

                    if (!$buy_save) {
                        $log = '错误4交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 买家更新数量出错，更新数量是' . $buy_save;
                        mlog($log);
                        Db::rollback();
                        break;
                    }

                    if ($fee_sell) {
                        $sell_fee = round(($mum / 100) * $fee_sell, 8);
                        $sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
                    } else {
                        $sell_fee = 0;
                        $sell_save = $mum;
                    }

                    if (!$sell_save) {
                        $log = '错误5交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家更新数量出错，更新数量是' . $sell_save;
                        mlog($log);
                        Db::rollback();
                        break;
                    }

                    $user_buy = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();
                    if (!$user_buy[$rmb . 'd']) {
                        $log = '错误6交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 买家财产错误，冻结财产是' . $user_buy[$rmb . 'd'];
                        mlog($log);
                        Db::rollback();
                        break;
                    }

                    $user_sell = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();
                    if (!$user_sell[$xnb . 'd']) {
                        $log = '错误7交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家财产错误，冻结财产是' . $user_sell[$xnb . 'd'];
                        mlog($log);
                        Db::rollback();
                        break;
                    }

                    if ($user_buy[$rmb . 'd'] < 1.0E-8) {
                        $log = '错误88交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
                        mlog($log);
                        Db::rollback();
                        Db::table('weike_trade')->where(array('id' => $buy['id']))->setField(['endtime' => time(), 'status' => 1]);
                        break;
                    }

                    if ($buy_save <= round($user_buy[$rmb . 'd'], 8)) {
                        $save_buy_rmb = $buy_save;
                    } else if ($buy_save <= round($user_buy[$rmb . 'd'], 8) + 1) {
                        $save_buy_rmb = $user_buy[$rmb . 'd'];
                        $log = '错误8交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 买家更新冻结人民币出现误差,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '实际更新' . $save_buy_rmb;
                        mlog($log);
                    } else {
                        $log = '错误9交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
                        mlog($log);
                        Db::rollback();
                        Db::table('weike_trade')->where(array('id' => $buy['id']))->setField(['endtime' => time(), 'status' => 1]);
                        break;
                    }

                    // TODO: SEPARATE
                    if ($amount <= round($user_sell[$xnb . 'd'], 8 - config('market')[$market]['round'])) {
                        $save_sell_xnb = $amount;
                    } else {
                        // TODO: SEPARATE
                        if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round']) + 1) {
                            $save_sell_xnb = $user_sell[$xnb . 'd'];
                            $log = '错误10交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                            $log .= 'ERR: 卖家更新冻结虚拟币出现误差,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '实际更新' . $save_sell_xnb;
                            mlog($log);
                        } else {
                            $log = '错误11交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                            $log .= 'ERR: 卖家更新冻结虚拟币出现错误,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '进行错误处理';
                            mlog($log);
                            Db::rollback();
                            Db::table('weike_trade')->where(array('id' => $sell['id']))->setField(['endtime' => time(), 'status' => 1]);
                            break;
                        }
                    }

                    if (!$save_buy_rmb) {
                        $log = '错误12交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 买家更新数量出错错误,更新数量是' . $save_buy_rmb;
                        mlog($log);
                        Db::rollback();
                        Db::table('weike_trade')->where(array('id' => $buy['id']))->setField(['endtime' => time(), 'status' => 1]);
                        break;
                    }

                    if (!$save_sell_xnb) {
                        $log = '错误13交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家更新数量出错错误,更新数量是' . $save_sell_xnb;
                        mlog($log);
                        Db::rollback();
                        Db::table('weike_trade')->where(array('id' => $sell['id']))->setField(['endtime' => time(), 'status' => 1]);
                        break;
                    }
                    $rs = [];
                    $rs[] = Db::table('weike_trade')->where(array('id' => $buy['id']))->setInc('deal', $amount);
                    $rs[] = Db::table('weike_trade')->where(array('id' => $sell['id']))->setInc('deal', $amount);
                    $rs[] = $finance_nameid = Db::table('weike_trade_log')->insertGetId(array('userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->setInc($xnb, $amount);
                    $finance = Db::table('weike_finance')->where(array('userid' => $buy['userid']))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->setDec($rmb . 'd', $save_buy_rmb);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();
                    $finance_hash = md5($buy['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mum . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    if ($rmb == "hkd") {
                        $rs[] = Db::table('weike_finance')->insert(array('userid' => $buy['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功买入-市场' . $market, 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    }

                    $finance = Db::table('weike_finance')->where(array('userid' => $sell['userid']))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();
                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->setInc($rmb, $sell_save);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();
                    $finance_hash = md5($sell['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $mum . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    if ($rmb == "hkd") {
                        $rs[] = Db::table('weike_finance')->insert(array('userid' => $sell['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功卖出-市场' . $market, 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    }

                    $rs[] = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->setDec($xnb . 'd', $save_sell_xnb);
                    $buy_list = Db::table('weike_trade')->where(array('id' => $buy['id'], 'status' => 0))->find();
                    if ($buy_list) {
                        if ($buy_list['num'] <= $buy_list['deal']) {
                            $rs[] = Db::table('weike_trade')->where(array('id' => $buy['id']))->setField('status', 1);
                        }
                    }

                    $sell_list = Db::table('weike_trade')->where(array('id' => $sell['id'], 'status' => 0))->find();
                    if ($sell_list) {
                        if ($sell_list['num'] <= $sell_list['deal']) {
                            $rs[] = Db::table('weike_trade')->where(array('id' => $sell['id']))->setField('status', 1);
                        }
                    }

                    if ($price < $buy['price']) {
                        $chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
                        $chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
                        $chajia = round($chajia_dong - $chajia_shiji, 8);

                        if ($chajia) {
                            $chajia_user_buy = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();

                            if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
                                $chajia_save_buy_rmb = $chajia;
                            } else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {
                                $chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];
                                mlog('错误91交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
                                mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现误差,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '实际更新' . $chajia_save_buy_rmb);
                            } else {
                                mlog('错误92交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
                                mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现错误,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '进行错误处理');
                                Db::rollback();
                                Db::table('weike_trade')->where(array('id' => $buy['id']))->setField(['endtime' => time(), 'status' => 1]);
                                break;
                            }

                            if ($chajia_save_buy_rmb) {
                                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->setDec($rmb . 'd', $chajia_save_buy_rmb);
                                $rs[] = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->setInc($rmb, $chajia_save_buy_rmb);
                            }
                        }
                    }

                    $you_buy = Db::table('weike_trade')->where(array(
                        'market' => array('like', '%' . $rmb . '%'),
                        'status' => 0,
                        'userid' => $buy['userid']
                    ))->find();
                    $you_sell = Db::table('weike_trade')->where(array(
                        'market' => array('like', '%' . $xnb . '%'),
                        'status' => 0,
                        'userid' => $sell['userid']
                    ))->find();

                    if (!$you_buy) {
                        $you_user_buy = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();

                        if (0 < $you_user_buy[$rmb . 'd']) {
                            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->setField($rmb . 'd', 0);
                            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $buy['userid']))->setInc($rmb, $you_user_buy[$rmb . 'd']);
                        }
                    }

                    if (!$you_sell) {
                        $you_user_sell = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();

                        if (0 < $you_user_sell[$xnb . 'd']) {
                            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->setField($xnb . 'd', 0);
                            $rs[] = Db::table('weike_user_coin')->where(array('userid' => $sell['userid']))->setInc($xnb, $you_user_sell[$xnb . 'd']);
                        }
                    }

                    $invit_buy_user = Db::table('weike_user')->where(array('id' => $buy['userid']))->find();
                    $invit_sell_user = Db::table('weike_user')->where(array('id' => $sell['userid']))->find();
                    if ($invit_buy) {
                        if ($invit_1) {
                            if ($buy_fee) {
                                if ($invit_buy_user['invit_1']) {
                                    $invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 6);

                                    if ($invit_buy_save_1) {
                                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $invit_buy_user['invit_1']))->setInc($rmb, $invit_buy_save_1);
                                        $rs[] = Db::table('weike_invit')->insert(array('userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '一代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1));
                                    }
                                }

                                if ($invit_buy_user['invit_2']) {
                                    $invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 6);

                                    if ($invit_buy_save_2) {
                                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $invit_buy_user['invit_2']))->setInc($rmb, $invit_buy_save_2);
                                        $rs[] = Db::table('weike_invit')->insert(array('userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => '二代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1));
                                    }
                                }

                                if ($invit_buy_user['invit_3']) {
                                    $invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 6);

                                    if ($invit_buy_save_3) {
                                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $invit_buy_user['invit_3']))->setInc($rmb, $invit_buy_save_3);
                                        $rs[] = Db::table('weike_invit')->insert(array('userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => '三代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1));
                                    }
                                }
                            }
                        }

                        if ($invit_sell) {
                            if ($sell_fee) {
                                if ($invit_sell_user['invit_1']) {
                                    $invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 6);

                                    if ($invit_sell_save_1) {
                                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $invit_sell_user['invit_1']))->setInc($rmb, $invit_sell_save_1);
                                        $rs[] = Db::table('weike_invit')->insert(array('userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => '一代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1));
                                    }
                                }

                                if ($invit_sell_user['invit_2']) {
                                    $invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 6);

                                    if ($invit_sell_save_2) {
                                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $invit_sell_user['invit_2']))->setInc($rmb, $invit_sell_save_2);
                                        $rs[] = Db::table('weike_invit')->insert(array('userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => '二代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1));
                                    }
                                }

                                if ($invit_sell_user['invit_3']) {
                                    $invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 6);

                                    if ($invit_sell_save_3) {
                                        $rs[] = Db::table('weike_user_coin')->where(array('userid' => $invit_sell_user['invit_3']))->setInc($rmb, $invit_sell_save_3);
                                        $rs[] = Db::table('weike_invit')->insert(array('userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => '三代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1));
                                    }
                                }
                            }
                        }
                    }

                    if (check_arr($rs)) {

                        $new_trade_weike = 1;
                        $jiaoyiqu = config('market')[$market]['jiaoyiqu'];

                        cache('weike_allcoin' . $jiaoyiqu, null);
                        cache('marketjiaoyie24' . $jiaoyiqu, null);
                        cache('allsum', null);
                        cache('getJsonTop' . $market, null);
                        cache('getTradelog' . $market, null);
                        cache('getDepth' . $market . '1', null);
                        cache('getDepth' . $market . '3', null);
                        cache('getDepth' . $market . '4', null);
                        cache('ChartgetJsonData' . $market, null);
                        cache('allcoin', null);
                        cache('trends', null);
                        Db::commit();
                    } else {
                        Db::rollback();
                        break;
                    }
                } else {
                    Db::rollback();
                    break;
                }
            }catch (Exception $e){
                exception_log($e,__FUNCTION__);
                Db::rollback();
                break;
            }
            unset($rs,$buy_second,$sell_second,$buy,$sell);
        }

        if ($new_trade_weike) {
            $new_price = round(Db::name('TradeLog')->where(array('market' => $market, 'status' => 1))->order('id desc')->value('price'), 6);
            $buy_price = round(Db::name('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'), 6);
            $sell_price = round(Db::name('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'), 6);
            $min_price = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->min('price'), 6);
            $max_price = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->max('price'), 6);

            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            //time() - (60 * 60 * 24)
            $volume = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt',$beginToday)
            ))->sum('num'), 6);

            $sta_price = round(Db::name('TradeLog')->where(array(
                'market'  => $market,
                'status'  => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->order('id asc')->value('price'), 6);
            $Cmarket = Db::name('Market')->where(array('name' => $market))->find();

            if ($Cmarket['new_price'] != $new_price) {
                $upCoinData['new_price'] = $new_price;
                cache('get_new_price_'.$market,null);
            }

            if ($Cmarket['buy_price'] != $buy_price) {
                $upCoinData['buy_price'] = $buy_price;
            }

            if ($Cmarket['sell_price'] != $sell_price) {
                $upCoinData['sell_price'] = $sell_price;
            }

            if ($Cmarket['min_price'] != $min_price) {
                $upCoinData['min_price'] = $min_price;
            }

            if ($Cmarket['max_price'] != $max_price) {
                $upCoinData['max_price'] = $max_price;
            }

            if ($Cmarket['volume'] != $volume) {
                $upCoinData['volume'] = $volume;
            }

            $change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
            $upCoinData['change'] = $change;

            if ($upCoinData) {
                Db::name('Market')->where(array('name' => $market))->update($upCoinData);
                cache('home_market', null);
            }
        }
    }

	//挂单撤销
	public function chexiao()
	{
        $id = input('id/d');

		if (!userid()) {
			$this->error('请先登录！');
		}

		if (!check($id, 'd')) {
			$this->error('请选择要撤销的委托！');
		}

		$trade = Db::name('Trade')->where(array('id' => $id))->find();

		if (!$trade) {
			$this->error('撤销委托参数错误！');
		}

		if ($trade['userid'] != userid()) {
			$this->error('参数非法！');
		}

		$rs = model('Trade')->chexiao($id);
        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
	}
}
