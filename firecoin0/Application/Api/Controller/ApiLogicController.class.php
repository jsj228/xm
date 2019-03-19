<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/5
 * Time: 11:39
 */

namespace Api\Controller;


class ApiLogicController
{
    //判断用户是买还是卖
    public static function upTrade($input,$uid){
//        $paypassword = I('post.paypassword/s', NULL);
        $market = $input['currency'];
        $price = $input['price'];
        $num = $input['amount'];

        $types = [0=>2,1=>1];
        if(!isset($types[$input['tradeType']])) return ['code'=>'0','msg'=>'交易类型错误'];
        $type = $types[$input['tradeType']];

        $user = M('user')->where('id='.$uid)->find();
        if (!$user['status'])  return ['code'=>0,'msg'=>'用户被冻结，请联系客服人员!'];
        if (!C('market')[$market]['trade']) {
            if ($user['usertype'] == 0) return ['code'=>0,'msg'=>'非交易时间段，禁止交易!'];
        }

        if(strlen($price) > 10 || strlen($num) > 10) return ['code'=>0,'msg'=>'价格或者数量超过预定长度！'];

        if (C('market')[$market]['begintrade']) {
            $begintrade = C('market')[$market]['begintrade'];
        } else {
            $begintrade = "00:00:00";
        }

        if (C('market')[$market]['endtrade']) {
            $endtrade = C('market')[$market]['endtrade'];
        } else {
            $endtrade = "23:59:59";
        }


        $trade_begin_time = strtotime(date("Y-m-d") . " " . $begintrade);
        $trade_end_time = strtotime(date("Y-m-d") . " " . $endtrade);
        $cur_time = time();

        if ($cur_time < $trade_begin_time || $cur_time > $trade_end_time) return ['code'=>0,'msg'=>'当前市场禁止交易,交易时间为每日' . $begintrade . '-' . $endtrade];

        if (!check($price, 'double')) return ['code'=>0,'msg'=>'交易价格格式错误'];

        if (!check($num, 'double')) return ['code'=>0,'msg'=>'交易数量格式错误'];

        if (($type != 1) && ($type != 2))  return ['code'=>0,'msg'=>'交易类型格式错误'];

//        $user = M('User')->where(array('id' => userid()))->find();

        //每次交易必须要整交易密码
//        if ($user['tpwdsetting'] == 2) {
//            if (md5($paypassword) != $user['paypassword']) {
//                $this->error('交易密码错误！');
//            }
//        }

        //登陆只需验证一次（$user['tpwdsetting']设置交易密码方式）
//        if ($user['tpwdsetting'] == 1) {
//            if (!session(userid() . 'tpwdsetting')) {
//                if (md5($paypassword) != $user['paypassword']) {
//                    $this->error('交易密码错误！');
//                } else {
//                    session(userid() . 'tpwdsetting', 1);
//                }
//            }
//        }

        if (!C('market')[$market]) {
            return ['code'=>0,'msg'=>'交易市场错误'];
        } else {
            $xnb = explode('_', $market)[0];//要交易的币
            $rmb = explode('_', $market)[1];
        }

        // TODO: SEPARATE
        $price = round(floatval($price), 8);
        if (!$price) return ['code'=>0,'msg'=>'交易价格错误' . $price];
        $num = round($num, 8);

        if (!check($num, 'double')) return ['code'=>0,'msg'=>'交易数量错误'];

        //$type的值来判断用户 是买 还是卖
        if ($type == 1) {
            $min_price = (C('market')[$market]['buy_min'] ? C('market')[$market]['buy_min'] : 1.0E-8);//买入最低价
            $max_price = (C('market')[$market]['buy_max'] ? C('market')[$market]['buy_max'] : 10000000);//买入最高价
        }
        if ($type == 2) {
            $min_price = (C('market')[$market]['sell_min'] ? C('market')[$market]['sell_min'] : 1.0E-8);//卖出最低价
            $max_price = (C('market')[$market]['sell_max'] ? C('market')[$market]['sell_max'] : 10000000);//卖出最高价
        }

        if ($max_price < $price) return ['code'=>0,'msg'=>'交易价格超过最大限制'];
        if ($price < $min_price) return ['code'=>0,'msg'=>'交易价格低过最小限制'];

        $hou_price = C('market')[$market]['hou_price'];   //每天 最后的价格
        if (!$hou_price) $hou_price = C('market')[$market]['weike_faxingjia'];

        if ($hou_price) {
            if (C('market')[$market]['zhang']) {
                $zhang_price = round(($hou_price / 100) * (100 + C('market')[$market]['zhang']), 8);
                if ($zhang_price < $price) return ['code'=>0,'msg'=>'交易价格超过今日涨幅限制'];
            }

            if (C('market')[$market]['die']) {
                $die_price = round(($hou_price / 100) * (100 - C('market')[$market]['die']), C('market')[$market]['round']);
                if ($price < $die_price) return ['code'=>0,'msg'=>'交易价格超过今日跌幅限制！'];
            }
        }
        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();
        if ($type == 1) {
            $trade_fee = $user['username'] === '18985818487' ? 0 : C('market')[$market]['fee_buy'];//交易手续费

            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);//买入的手续费
                $mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);//用户买入时产生的费用
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

            if ($user_coin[$rmb] < $mum) return ['code'=>0,'msg'=>C('coin')[$rmb]['title'] . '余额不足！'];//用户可用资产 与  用户买入时产生的费用 比较

        }
        if ($type == 2) {
            $trade_fee = $user['username'] === '18985818487' ? 0 : C('market')[$market]['fee_sell'];
            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);//卖出的手续费
                $mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);//？
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }
            if ($user_coin[$xnb] < $num) return ['code'=>0,'msg'=>C('coin')[$xnb]['title'] . '余额不足！'];//判断用户该币可用数量
        }

        //挂单比例
        if (C('coin')[$xnb]['fee_bili']) {
            if ($type == 2) {
                // TODO: SEPARATE
                $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], C('market')[$market]['round']);
                if ($bili_user) {
                    // TODO: SEPARATE
                    $bili_keyi = round(($bili_user / 100) * C('coin')[$xnb]['fee_bili'], C('market')[$market]['round']);

                    if ($bili_keyi) {
                        $bili_zheng = M('Trade')->field('id,price,sum(num-deal)as nums')->where(['userid' => $uid, 'status' => 0, 'type' => 2, 'market' => ['like', "%$xnb%"]])->select();
                        if (!$bili_zheng[0]['nums']) {
                            $bili_zheng[0]['nums'] = 0;
                        }
                        $bili_kegua = $bili_keyi - $bili_zheng[0]['nums'];
                        if ($bili_kegua < 0) {
                            $bili_kegua = 0;
                        }

                        if ($bili_kegua < $num) {
                            return ['code'=>0,'msg'=>'您的挂单总数量超过系统限制，您当前持有' . C('coin')[$xnb]['title'] . $bili_user . '个，已经挂单' . $bili_zheng[0]['nums'] . '个，还可以挂单' . $bili_kegua . '个'];
                        }
                    } else {
                        return ['code'=>0,'msg'=>'可交易量错误'];
                    }
                }
            }
        }

        //每日交易限制
        if (C('coin')[$xnb]['fee_meitian']) {
            if ($type == 2) {
                $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], 8);
                if ($bili_user < 0) return ['code'=>0,'msg'=>'可交易量错误'];
                $kemai_bili = ($bili_user / 100) * C('coin')[$xnb]['fee_meitian'];
                if ($kemai_bili < 0) return ['code'=>0,'msg'=>'您今日只能再卖' . C('coin')[$xnb]['title'] . 0 . '个'];

                $kaishi_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $jintian_sell = M('Trade')->where(array(
                    'userid' => $uid,
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
                    if ($kemai < 0) $kemai = 0;
                    return ['code'=>0,'msg'=>'您的挂单总数量超过系统限制，您今日只能再卖' . C('coin')[$xnb]['title'] . $kemai . '个'];
                }
            }
        }

        //最小交易数量
        if (C('market')[$market]['trade_min']) {
            if ($mum < C('market')[$market]['trade_min']) return ['code'=>0,'msg'=>'交易总额不能小于' . C('market')[$market]['trade_min']];
        }

        //最大交易数量
        if (C('market')[$market]['trade_max']) {
            if (C('market')[$market]['trade_max'] < $mum) return ['code'=>0,'msg'=>'交易总额不能大于' . C('market')[$market]['trade_max']];
        }

        if (!$rmb || !$xnb || !$market || !$price || !$num || !$mum || !$type) return ['code'=>0,'msg'=>'数据错误'];

        $mo = M();
        $mo->startTrans();
        $flag = false;
        $user_coin = $mo->table('weike_user_coin')->where(array('userid' => $uid))->find();

        if ($type == 1) {
            if ($user_coin[$rmb] < $mum) return ['code'=>0,'msg'=>C('coin')[$rmb]['title'] . '余额不足！'];

            try{
                $finance = $mo->table('weike_finance')->where(array('userid' => $uid))->order('id desc')->find();//用户资产
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $uid))->find();
//                    $mo->table('weike_user_coin')->where(array('userid' => userid()))->save([$rmb=>['exp',"$rmb-$mum"],$rmb.'d'=>['exp',$rmb.'d'+$mum]]);
                $mo->table('weike_user_coin')->where(array('userid' => $uid))->setDec($rmb, $mum);//可用资产   剩余的
                $mo->table('weike_user_coin')->where(array('userid' => $uid))->setInc($rmb . 'd', $mum);
                $finance_nameid = $mo->table('weike_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));

                if ($rmb == "cny") {//cny 换成  cny
                    $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $uid))->find();
                    $finance_hash = md5($uid . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }
                    $mo->table('weike_finance')->add(array('userid' => $uid, 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'],
                        'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid,
                        'remark' => '交易中心-委托买入-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                        'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                }
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }
        }
        if ($type == 2) {

            if ($user_coin[$xnb] < $num) return ['code'=>0,'msg'=>C('coin')[$xnb]['title'] . '余额不足2！'];

            try{
//                    $mo->table('weike_user_coin')->where(array('userid' => userid()))->save([$xnb=>['exp',"$xnb-$num"],$xnb.'d'=>['exp',$xnb.'d'+$num]]);
                $mo->table('weike_user_coin')->where(array('userid' => $uid))->setDec($xnb, $num);
                $mo->table('weike_user_coin')->where(array('userid' => $uid))->setInc($xnb . 'd', $num);
                $finance_nameid =$mo->table('weike_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }
        }

        if ($flag) {
            S('getDepth', null);
            //C('is_init_trade') == 1 ? self::initMatchingTrade($market) : self::matchingTrade($market,$uid);
            self::matchingTrade($market,$uid);
            return ['code'=>1,'msg'=>'挂单成功！'];
        } else {
            return ['code'=>0,'msg'=>'交易失败！'];
        }

    }

    //异步提交
    private static function initMatchingTrade($market = null){
        if ($market){
            $url = 'http://127.0.0.1/Trade/matchingTrade/market/'.$market;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ( $ch, CURLOPT_TIMEOUT, 1 );
            curl_exec($ch);
            curl_close($ch);
        }
    }

    //撮合交易
    public function matchingTrade($market = NULL,$uid)
    {
        // $market = I('market/s', NULL);
        if (!$market) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }
        //特殊用户不需要买卖手续费
        $user_type = M('User')->where(['id' =>$uid])->find();
        if ($user_type['usertype'] == 3){
            $fee_buy = 0 ;
            $fee_sell = 0;
        }else{
            $fee_buy = C('market')[$market]['fee_buy'];
            $fee_sell = C('market')[$market]['fee_sell'];
        }

        $invit_buy = C('market')[$market]['invit_buy'];

        $invit_sell = C('market')[$market]['invit_sell'];
        $invit_1 = C('market')[$market]['invit_1'];
        $invit_2 = C('market')[$market]['invit_2'];
        $invit_3 = C('market')[$market]['invit_3'];
        $mo = M();
        $new_trade_weike = 0;

//        $sell_obj = new SellRedis(C('redis'),$market);
//        $buy_obj = new BuyRedis(C('redis'),$market);
//        $buy = json_decode($buy_obj->get_first_data(),true);
//        $sell = json_decode($sell_obj->get_first_data(),true);
//        $market_redis_key = 'call_getDepth'.$market;
//        while($buy['price'] >=  $sell['price']){
//       $market_redis_num =  intval(get_redis($market_redis_key));
//       set_redis($market_redis_key, $market_redis_num + 1);
        for (; true; ) {
            $buy = $mo->table('weike_trade')->where(array('market' => $market, 'type' => 1,'userid' => array('gt',0), 'status' => 0))->order('price desc,id asc')->find();
            $sell = $mo->table('weike_trade')->where(array('market' => $market, 'type' => 2,'userid' => array('gt',0),'status' => 0))->order('price asc,id asc')->find();
            if ($sell['id'] < $buy['id']) {
                $type = 1;
            } else {
                $type = 2;
            }

            //买的价格大于卖的价格
            if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
                $rs = [];
                $amount = min(round($buy['num'] - $buy['deal'], 8), round($sell['num'] - $sell['deal'], 8));
                $amount = round($amount, 8);
                if ($amount <= 0) {
                    $log = '错误1交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . "\n";
                    $log .= 'ERR: 成交数量出错，数量是' . $amount;
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    break;
                }

                if ($type == 1) {
                    $price = $sell['price'];
                } else if ($type == 2) {
                    $price = $buy['price'];
                } else {
                    break;
                }

                if (!$price) {
                    $log = '错误2交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                    $log .= 'ERR: 成交价格出错，价格是' . $price;
                    mlog($log);
                    break;
                } else {
                    // TODO: SEPARATE
                    $price = round($price, 8);
                }

                $mum = round($price * $amount, 8);
                if (!$mum) {
                    $log = '错误3交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                    $log .= 'ERR: 成交总额出错，总额是' . $mum;
                    mlog($log);
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
                    break;
                }

                $user_buy = M('UserCoin')->where(array('userid' => $buy['userid']))->find();
                if (!$user_buy[$rmb . 'd']) {
                    $log = '错误6交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家财产错误，冻结财产是' . $user_buy[$rmb . 'd'];
                    mlog($log);
                    break;
                }elseif ($user_buy[$rmb . 'd'] <= 0){
                    $log = '错误66交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家财产错误，冻结财产是' . $user_buy[$rmb . 'd'];
                    mlog($log);
                    break;
                }

                $user_sell = M('UserCoin')->where(array('userid' => $sell['userid']))->find();
                if (!$user_sell[$xnb . 'd']) {
                    $log = '错误7交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家财产错误，冻结财产是' . $user_sell[$xnb . 'd'];
                    mlog($log);
                    break;
                }elseif ($user_sell[$xnb . 'd'] <= 0){
                    $log = '错误77交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家财产错误，冻结财产是' . $user_sell[$xnb . 'd'];
                    mlog($log);
                    break;
                }

                if ($user_buy[$rmb . 'd'] < 1.0E-8) {
                    $log = '错误88交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
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
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }
                // TODO: SEPARATE

                if ($amount <= round($user_sell[$xnb . 'd'], 8)) {
                    $save_sell_xnb = $amount;
                } else {
                    // TODO: SEPARATE
                    if ($amount <= round($user_sell[$xnb . 'd'], 8) + 1) {
                        $save_sell_xnb = $user_sell[$xnb . 'd'];
                        $log = '错误10交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家更新冻结虚拟币出现误差,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '实际更新' . $save_sell_xnb;
                        mlog($log);
                    } else {
                        $log = '错误11交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家更新冻结虚拟币出现错误,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '进行错误处理';
                        mlog($log);
                        M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                        break;
                    }
                }

                if (!$save_buy_rmb) {
                    $log = '错误12交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新数量出错错误,更新数量是' . $save_buy_rmb;
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }

                if (!$save_sell_xnb) {
                    $log = '错误13交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家更新数量出错错误,更新数量是' . $save_sell_xnb;
                    mlog($log);
                    M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    break;
                }

                $mo->startTrans();
                $buy = $mo->table('weike_trade')->lock(true)->where(['id' => $buy['id']])->find();
                $sell = $mo->table('weike_trade')->lock(true)->where(['id' => $sell['id']])->find();
                if($buy['status'] != 0 || $sell['status'] != 0){
                    $mo->rollback();
                    break;
                }

                $rs[] = $mo->table('weike_trade')->where(array('id' => $buy['id']))->setInc('deal', $amount);
                $rs[] = $mo->table('weike_trade')->where(array('id' => $sell['id']))->setInc('deal', $amount);
                $rs[] = $finance_nameid = $mo->table('weike_trade_log')->add(array('userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->setInc($xnb, $amount);
                $finance = $mo->table('weike_finance')->where(array('userid' => $buy['userid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->setDec($rmb . 'd', $save_buy_rmb);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();
                $finance_hash = md5($buy['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                if($rmb == "cny"){
                    $rs[] = $mo->table('weike_finance')->add(array('userid' => $buy['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功买入-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                }

                $finance = $mo->table('weike_finance')->where(array('userid' => $sell['userid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->setInc($rmb, $sell_save);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();
                $finance_hash = md5($sell['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                if($rmb == "cny"){
                    $rs[] = $mo->table('weike_finance')->add(array('userid' => $sell['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功卖出-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                }

                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->setDec($xnb . 'd', $save_sell_xnb);
                $buy_list = $mo->table('weike_trade')->where(array('id' => $buy['id'], 'status' => 0))->find();
                if ($buy_list) {
                    if ($buy_list['num'] <= $buy_list['deal']) {
                        $rs[] = $mo->table('weike_trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    }
                }

                $sell_list = $mo->table('weike_trade')->where(array('id' => $sell['id'], 'status' => 0))->find();
                if ($sell_list) {
                    if ($sell_list['num'] <= $sell_list['deal']) {
                        $rs[] = $mo->table('weike_trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    }
                }

                if ($price < $buy['price']) {
                    $chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
                    $chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
                    $chajia = round($chajia_dong - $chajia_shiji, 8);

                    if ($chajia) {
                        $chajia_user_buy = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();

                        if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
                            $chajia_save_buy_rmb = $chajia;
                        } else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {
                            $chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];
                            mlog('错误91交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
                            mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现误差,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '实际更新' . $chajia_save_buy_rmb);
                        } else {
                            mlog('错误92交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
                            mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现错误,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '进行错误处理');
                            $mo->rollback();
                            M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                            break;
                        }

                        if ($chajia_save_buy_rmb) {
                            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->setDec($rmb . 'd', $chajia_save_buy_rmb);
                            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->setInc($rmb, $chajia_save_buy_rmb);
                        }
                    }
                }

                $you_buy = $mo->table('weike_trade')->where(array(
                    'market' => array('like', '%' . $rmb . '%'),
                    'status' => 0,
                    'userid' => $buy['userid']
                ))->find();
                $you_sell = $mo->table('weike_trade')->where(array(
                    'market' => array('like', '%' . $xnb . '%'),
                    'status' => 0,
                    'userid' => $sell['userid']
                ))->find();

                if (!$you_buy) {
                    $you_user_buy = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->find();

                    if (0 < $you_user_buy[$rmb . 'd']) {
                        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->setField($rmb . 'd', 0);
                        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $buy['userid']))->setInc($rmb, $you_user_buy[$rmb . 'd']);
                    }
                }

                if (!$you_sell) {
                    $you_user_sell = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->find();

                    if (0 < $you_user_sell[$xnb . 'd']) {
                        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->setField($xnb . 'd', 0);
                        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $sell['userid']))->setInc($xnb, $you_user_sell[$xnb . 'd']);
                    }
                }

                $invit_buy_user = $mo->table('weike_user')->where(array('id' => $buy['userid']))->find();
                $invit_sell_user = $mo->table('weike_user')->where(array('id' => $sell['userid']))->find();

                if ($invit_buy) {
                    if ($invit_1) {
                        if ($buy_fee) {
                            if ($invit_buy_user['invit_1']) {
                                $invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 6);

                                if ($invit_buy_save_1) {
                                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $invit_buy_user['invit_1']))->setInc($rmb, $invit_buy_save_1);
                                    $rs[] = $mo->table('weike_invit')->add(array('userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '一代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1));
                                }
                            }

                            if ($invit_buy_user['invit_2']) {
                                $invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 6);

                                if ($invit_buy_save_2) {
                                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $invit_buy_user['invit_2']))->setInc($rmb, $invit_buy_save_2);
                                    $rs[] = $mo->table('weike_invit')->add(array('userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => '二代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1));
                                }
                            }

                            if ($invit_buy_user['invit_3']) {
                                $invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 6);

                                if ($invit_buy_save_3) {
                                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $invit_buy_user['invit_3']))->setInc($rmb, $invit_buy_save_3);
                                    $rs[] = $mo->table('weike_invit')->add(array('userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => '三代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1));
                                }
                            }
                        }
                    }

                    if ($invit_sell) {
                        if ($sell_fee) {
                            if ($invit_sell_user['invit_1']) {
                                $invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 6);

                                if ($invit_sell_save_1) {
                                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $invit_sell_user['invit_1']))->setInc($rmb, $invit_sell_save_1);
                                    $rs[] = $mo->table('weike_invit')->add(array('userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => '一代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1));
                                }
                            }

                            if ($invit_sell_user['invit_2']) {
                                $invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 6);

                                if ($invit_sell_save_2) {
                                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $invit_sell_user['invit_2']))->setInc($rmb, $invit_sell_save_2);
                                    $rs[] = $mo->table('weike_invit')->add(array('userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => '二代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1));
                                }
                            }

                            if ($invit_sell_user['invit_3']) {
                                $invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 6);

                                if ($invit_sell_save_3) {
                                    $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $invit_sell_user['invit_3']))->setInc($rmb, $invit_sell_save_3);
                                    $rs[] = $mo->table('weike_invit')->add(array('userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => '三代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1));
                                }
                            }
                        }
                    }
                }

                if (check_arr($rs)) {
                    $mo->commit();
                    //$mo->execute('commit');
                    //$mo->execute('unlock tables');
//                    $buy_one = D('trade')->where(array('id'=>$buy['id']))->find();
//                    $sell_one = D('trade')->where(array('id'=>$sell['id']))->find();
//                    if($buy_one['status'] == 0){
//                        $buy_obj->left_add_data($buy_one);
//                    }
//                    if($sell_one['status']==0){
//                        $sell_obj->left_add_data($sell_one);
//                    }
//                    $buy = json_decode($buy_obj->get_first_data(),true);
//                    $sell = json_decode($sell_obj->get_first_data(),true);
                    $new_trade_weike = 1;
                    $jiaoyiqu = C('market')[$market]['jiaoyiqu'];

                    S('weike_allcoin'.$jiaoyiqu,null);
                    S('marketjiaoyie24'.$jiaoyiqu,null);
                    S('allsum', null);
                    S('getJsonTop' . $market, null);
                    S('getTradelog' . $market, null);
                    S('getDepth' . $market . '1', null);
                    S('getDepth' . $market . '3', null);
                    S('getDepth' . $market . '4', null);
                    S('ChartgetJsonData' . $market, null);
                    S('allcoin', null);
                    S('trends', null);
                } else {
                    $mo->rollback();
                    //$mo->execute('rollback');
                    //$mo->execute('unlock tables');
                    break;
                }

            } else {
                break;
            }

            unset($rs);
        }
//        $sell_obj->init();
//        $buy_obj ->init();

        if ($new_trade_weike) {
            $new_price = round(M('TradeLog')->where(array('market' => $market, 'status' => 1))->order('id desc')->getField('price'), 6);
            $buy_price = round(M('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'), 6);
            $sell_price = round(M('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'), 6);
            $min_price = round(M('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->min('price'), 6);
            $max_price = round(M('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->max('price'), 6);

            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            //time() - (60 * 60 * 24)
            $volume = round(M('TradeLog')->where(array(
                'market'  => $market,
                'addtime' => array('gt',$beginToday)
            ))->sum('num'), 6);

            $sta_price = round(M('TradeLog')->where(array(
                'market'  => $market,
                'status'  => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->order('id asc')->getField('price'), 6);
            $Cmarket = M('Market')->where(array('name' => $market))->find();

            if ($Cmarket['new_price'] != $new_price) {
                $upCoinData['new_price'] = $new_price;
                S('get_new_price_'.$market,null);
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
                M('Market')->where(array('name' => $market))->save($upCoinData);
                M('Market')->execute('commit');
                S('home_market', null);
            }
        }
    }

    //订单撤销
    public static function chexiao($id)
    {
        if (!check($id, 'd')) return ['code'=>0,'msg'=>'请选择要撤销的委托！'];

        $trade = M('Trade')->where(array('id' => $id))->find();

        if (!$trade) return ['code'=>0,'msg'=>'撤销委托参数错误！'];

        if ($trade['userid'] != userid()) return ['code'=>0,'msg'=>'未查询到该订单！'];

        $rs = D('Trade')->chexiao($id);
        if ($rs[0]) {
            return ['code'=>1,'msg'=>$rs[1]];
        } else {
            return ['code'=>0,'msg'=>$rs[1]];
        }
    }

    //查询订单
    public static function getOrder($id){
        $order = M('Trade')->where(['id'=>$id,'userid'=>userid()])->field('id,market,price,status,num,deal,addtime,type')->find();

        if($order){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $type = [1=>1,2=>0];
            $res = [
                'currency'=>$order['market'],
                'id'=>$id,
                'price'=>$order['price'],
                'status'=>isset($status[$order['status']])?$status[$order['status']]:$order['status'],
                'total_amount'=>$order['num'],
                'trade_amount'=>$order['deal'],
                'trade_date'=>$order['addtime'],
                'trade_money'=>$order['deal']*$order['price'],
                'type'=>isset($type[$order['type']])?$type[$order['type']]:1
            ];
            return ['code'=>1,'data'=>$res];
        }else{
            return ['code'=>0,'msg'=>'未查询到该订单'];
        }
    }

    //获取多个委托买单或卖单，每次请求返回10条记录
    public static function getOrders($input){

        $types = [1=>1,0=>2];//类型切换
        if(!isset($types[$input['tradeType']])) return ['code'=>0,'msg'=>'交易类型错误'];

        $orders = M('Trade')->where(['userid'=>userid(),'market'=>$input['currency'],'type'=>$types[$input['tradeType']]])
            ->field('id,market,price,status,num,deal,addtime,type')
            ->order('id desc')->limit(($input['pageIndex']-1)*10,10)->select();

        if(!$orders) return ['code'=>0,'msg'=>'未查询到该订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $types = [1=>1,2=>0];//类型切换
            $res[] = [
                'currency'=>$v['market'],
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['num'],
                'trade_amount'=>$v['deal'],
                'trade_date'=>$v['addtime'],
                'trade_money'=>$v['deal']*$v['price'],
                'type'=>$types[$v['type']]
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //(新)获取多个委托买单或卖单，每次请求返回pageSize<100条记录
    public static function getOrdersNew($input){
        if($input['pageSize']>=100) return ['code'=>0,'msg'=>'每页数量超出限制'];
        $types = [1=>1,0=>2];//类型切换
        if(!isset($types[$input['tradeType']])) return ['code'=>0,'msg'=>'交易类型错误'];

        $orders = M('Trade')->where(['userid'=>userid(),'market'=>$input['currency'],'type'=>$types[$input['tradeType']]])
            ->field('id,market,price,status,num,deal,addtime,type')
            ->order('id desc')->limit(($input['pageIndex']-1)*$input['pageSize'],$input['pageSize'])->select();

        if(!$orders) return ['code'=>0,'msg'=>'未查询到该订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $types = [1=>1,2=>0];//类型切换
            $res[] = [
                'currency'=>$v['market'],
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['num'],
                'trade_amount'=>$v['deal'],
                'trade_date'=>$v['addtime'],
                'trade_money'=>$v['deal']*$v['price'],
                'type'=>$types[$v['type']]
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //与getOrdersNew的区别是取消tradeType字段过滤，可同时获取买单和卖单，每次请求返回pageSize10条记录
    public static function getOrdersIgnoreTradeType($input){
        if($input['pageSize']>=100) return ['code'=>0,'msg'=>'每页数量超出限制'];

        $orders = M('Trade')->where(['userid'=>userid(),'market'=>$input['currency']])
            ->field('id,market,price,status,num,deal,addtime,type')
            ->order('id desc')->limit(($input['pageIndex']-1)*$input['pageSize'],$input['pageSize'])->select();

        if(!$orders) return ['code'=>0,'msg'=>'未查询到该订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $types = [1=>1,2=>0];//类型切换
            $res[] = [
                'currency'=>$v['market'],
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['num'],
                'trade_amount'=>$v['deal'],
                'trade_date'=>$v['addtime'],
                'trade_money'=>$v['deal']*$v['price'],
                'type'=>isset($types[$v['type']])?$types[$v['type']]:''
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //获取未成交或部份成交的买单和卖单，每次请求返回pageSize<=10条记录
    public static function getUnfinishedOrdersIgnoreTradeType($input){
        $orders = M('Trade')->where(['userid'=>userid(),'market'=>$input['currency'],'status'=>0])
            ->field('id,market,price,status,num,deal,addtime,type')
            ->order('id desc')->limit(($input['pageIndex']-1)*$input['pageSize'],$input['pageSize'])->select();


        if(!$orders) return ['code'=>1013,'msg'=>'未查询到订单'];
        foreach ($orders as $v){
            $status = [0=>0,1=>2,2=>1];//成交状态切换
            $types = [1=>1,2=>0];//类型切换
            $res[] = [
                'currency'=>substr($v['market'],0,strrpos($v['market'],'_')),
                'id'=>$v['id'],
                'price'=>$v['price'],
                'status'=>isset($status[$v['status']])?$status[$v['status']]:$v['status'],
                'total_amount'=>$v['num'],
                'trade_amount'=>$v['deal'],
                'trade_date'=>$v['addtime'],
                'trade_money'=>$v['deal']*$v['price'],
                'type'=>isset($types[$v['type']])?$types[$v['type']]:0
            ];
        }
        return ['code'=>1,'data'=>$res];
    }

    //获取用户信息
    public static function getAccountInfo(){
        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();

        foreach (C('market') as $v){
            $market[] = [
                'key'=>$v['xnb'],
                'enName'=>strtoupper($v['xnb']),
                'cnName'=>$v['navtitle'],
                'showName'=>strtoupper($v['xnb']),
                'unitTag'=>$v['xnb'],
                'available'=>isset($userCoin[$v['xnb']])?$userCoin[$v['xnb']]:0,
                'freez'=>isset($userCoin[$v['xnb'].'d'])?$userCoin[$v['xnb'].'d']:0,
                'unitDecimal'=>8,
                'isCanRecharge'=>true,
                'isCanWithdraw'=>true,
                'canLoan'=>true
            ];
        }
        $base = [
            'username'=>username(),
            'trade_password_enabled'=>true,
            'auth_google_enabled'=>true,
            'auth_mobile_enabled'=>true,
        ];
        $res = [
            'result'=>[
                'coins' => $market,
                'base' => $base,
            ],
            'assetPerm'=>$userCoin['cnyd'],
            'leverPerm'=>true,
            'entrustPerm'=>true,
            'moneyPerm'=>$userCoin['cny']
        ];
        return ['code'=>1,'data'=>$res];
    }

    //获取用户充值地址
    public static function getUserAddress($input){
        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'key'=>$userCoin[$input['currency'].'b']
                ]
            ]

        ];
        return $res;
    }

    //获取用户认证的提现地址
    public static function getWithdrawAddress($input){
        $addr = M('User_qianbao')->where(['userid' => userid(),'coinname'=>$input['currency']])->getField('addr');
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'key'=>$addr
                ]
            ]

        ];
        return $res;
    }

    //获取数字资产提现记录
    public static function getWithdrawRecord($input){
        $datas = M('myzc')->where(['userid'=>userid(),'coinname'=>$input['currency']])->limit(($input['pageIndex']-1)*$input['pageSize'])->order('id desc')->select();
        $count = M('myzc')->where(['userid'=>userid(),'coinname'=>$input['currency']])->count();
        $totalPage = ceil($count/$input['pageSize']);

        $status = [0=>0,1=>2,2=>1];
        foreach ($datas as $v){
            $list[] = [
                'amount'=>$v['num'],
                'fees'=>$v['fee'],
                'id'=>$v['id'],
                'manageTime'=>$v['endtime'],
                'status'=>$status[$v['status']],
                'submitTime'=>$v['addtime'],
                'toAddress'=>$v['username'],
            ];
        }
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'list'=>$list,
                    'pageIndex'=>$input['pageIndex'],
                    'pageSize'=>$input['pageSize'],
                    'totalCount'=>$count,
                    'totalPage'=>$totalPage
                ]
            ]
        ];
        return $res;
    }

    //获取数字资产充值记录
    public static function getChargeRecord($input){
        $datas = M('myzr')->where(['userid'=>userid(),'coinname'=>$input['currency']])->limit(($input['pageIndex']-1)*$input['pageSize'])->order('id desc')->select();
        $count = M('myzr')->where(['userid'=>userid(),'coinname'=>$input['currency']])->count();

        $status = [0=>0,1=>2,2=>1,4=>2];
        foreach ($datas as $v){
            $list[] = [
                'address'=>$v['txid'],
                'amount'=>$v['num'],
                'confirmTimes'=>1,
                'currency'=>$input['currency'],
                'description'=>'确认成功',
                'hash'=>$v['txid'],
                'id'=>$v['id'],
                'itransfer'=>true,
                'status'=>isset($status[$v['status']])?$status[$v['status']]:'',
                'submit_time'=>date('Y-m-d H:i:s',$v['addtime'])
            ];
        }
        $res = [
            'code'=>1000,
            'message'=>[
                'des'=>'success',
                'isSuc'=>true,
                'datas'=>[
                    'list'=>$list,
                    'pageIndex'=>$input['pageIndex'],
                    'pageSize'=>$input['pageSize'],
                    'total'=>$count,
                ]
            ]
        ];
        return $res;
    }

    //提现
    public static function withdraw($input)
    {
        $coin = $input['currency'];
        $num = $input['amount'];
        $addr = $input['receiveAddr'];
        $paypassword = $input['safePwd'];

        $wcgkey = $input['wcgkey'];

        if (!userid()) return ['code'=>0,'msg'=>'数据错误'];
        $num = abs($num);
        if (!check($num, 'currency')) return ['code'=>0,'msg'=>'数量格式错误！'];
        if (!check($addr, 'dw')) return ['code'=>0,'msg'=>'钱包地址格式错误！'];
        if (!check($paypassword, 'password')) return ['code'=>0,'msg'=>'交易密码格式错误！'];
        if (!check($coin, 'n'))  return ['code'=>0,'msg'=>'币种格式错误！'];
        if (!C('coin')[$coin]) return ['code'=>0,'msg'=>'币种错误！'];

        $Coin = M('Coin')->where(array('name' => $coin))->find();
        if (!$Coin) return ['code'=>0,'msg'=>'币种错误！'];

        $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.01);
        $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);
        if ($num < $myzc_min) return ['code'=>0,'msg'=>'转出数量超过系统最小限制！'];
        if ($myzc_max < $num) return ['code'=>0,'msg'=>'转出数量超过系统最大限制！'];

        $user = M('User')->where(array('id' => userid()))->find();
        if (md5($paypassword) != $user['paypassword']) return ['code'=>0,'msg'=>'交易密码错误！'];

        if ($user['idcardauth'] == 0) return ['code'=>0,'msg'=>'请先进行身份认证！'];

        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        if ($coin == 'btmz'){
            if ($user_coin['btm'] < $num) return ['code'=>0,'msg'=>'可用余额不足！'];
        }else{
            if ($user_coin[$coin] < $num) return ['code'=>0,'msg'=>'可用余额不足！'];
        }

        //收手续费的地址，找到后进行手续费添加
        $qbdz = $coin . 'b';
        $fee_user = M('UserCoin')->where(array($qbdz => $Coin['zc_user']))->find();
        if ($fee_user) {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 存在,有手续费');
            $usercoin=M('coin')->where(array('name'=>$coin))->getField('zc_fee');

            if($str_len = strpos($usercoin,'%')){
                $usercoin = substr($usercoin,0,$str_len);
                $fee = round(($num / 100) * ($usercoin), 8);
            }else{
                $fee =  $usercoin;
            }


//            if($coin=='wc' || $coin=='wcg' || $coin=='oioc' || $coin=='eac' || $coin == 'sie' || $coin == 'drt' || $coin == 'mat' || $coin == 'ifc' || $coin == 'mtr' || $coin == 'xrp'){
//                $fee = round(($num / 100) * ($usercoin), 8);
//            }else{
//                $fee =  $usercoin;
//            }
            //无限币提币费率：就是500W以下 0.2%+200个.   500W以上 10000个+200 个
            /*if($coin=='ifc'){
                if($num<=5000000) {
                    $fee = round(($num * 0.002)+200,8);
                } elseif($num>5000000){
                    $fee = 10200;
                }
            }*/
            $mum = round($num - $fee, 8);
            if ($mum < 0) return ['code'=>0,'msg'=>'转出手续费错误！'];
            if ($fee < 0) return ['code'=>0,'msg'=>'转出手续费设置错误！'];
        } else {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 不存在,无手续费');
            $fee = 0;
            $mum = $num;
        }

        if ($Coin['type'] == 'rgb') {
            debug($Coin, '开始认购币转出');

            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzc_fee write');
            $rs = array();
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);

            if ($fee) {
                if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                    debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                } else {
                    $rs[] = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                }
            }

            $arr = array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0);
            if (($coin === 'wcg' && !empty($wcgkey)) || ($coin === 'drt' && !empty($wcgkey)) || ($coin === 'mat' && !empty($wcgkey))) {
                $arr['wcgkey'] = $wcgkey;
            }
            $rs[] = $mo->table('weike_myzc')->add($arr);
            if ($fee_user) {
                $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
            }

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                session('myzc_verify', null);
                return ['code'=>1,'msg'=>'转账成功！'];
            } else {
                $mo->execute('rollback');
                return ['code'=>0,'msg'=>'转账失败！'];
            }
        }

        if ($Coin['type'] == 'bit' || $Coin['type'] == 'eth' || $Coin['type'] == 'token' || $Coin['type'] == 'eos') {
            $mo = M();
            if ($Coin['type'] == 'eos') {
                $user_wallet =  M('UserQianbao')->where(array('memo' => $addr,'userid'=>userid(),'coinname'=>'eos'))->find();
                $addr = $user_wallet['addr'];
                $memo = $user_wallet['memo'];
            }
            if ($mo->table('weike_user_coin')->where(array($qbdz => $addr))->find() && $Coin['type'] != 'eos') {
                //禁止站内互转！
                return ['code'=>0,'msg'=>'禁止站内互转！'];
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();
                if (!$peer) {
                    return ['code'=>0,'msg'=>'转出地址不存在！'];
                }

                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzr write , weike_myzc_fee write');
                $rs = array();
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee) {
                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                    } else {
                        $rs[] = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    }
                }

                $rs[] = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('weike_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    session('myzc_verify', null);
                    return ['code'=>1,'msg'=>'转账成功！'];
                } else {
                    $mo->execute('rollback');
                    return ['code'=>0,'msg'=>'转账失败！'];
                }
            } else {
                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write ,weike_myzr write, weike_myzc_fee write');
                $rs = array();
                $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //$rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                if ( $Coin['type'] == 'eos') {
                    $addr_memo = $addr.' '.$memo;
                    $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr_memo, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                } else {
                    $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                }

                if ($fee && $auto_status) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $r = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                        debug(array('res' => $r, 'lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                    } else {
                        $rs[] = $r = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    }
                }
                if (check_arr($rs)) {
                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        if ($Coin['type'] == 'bit') {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);
                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                return ['code'=>0,'msg'=>$data['message']];
                            }
                        } elseif ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);

                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                return ['code'=>0,'msg'=>$data['message']];
                            }
                        } elseif ($Coin['type'] == 'eos') {
                            $data = myCurl('http://172.31.39.219/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num , 'memo' => $memo]);

                            if ($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                return ['code'=>0,'msg'=>$data['message']];
                            }
                        }

                        if ($sendrs) {
                            $flag = 1;
                            $arr = json_decode($sendrs, true);

                            if (isset($arr['status']) && ($arr['status'] == 0)) {
                                $flag = 0;
                            }
                        } else {
                            $flag = 0;
                        }

                        if (!$flag) {
                            return ['code'=>0,'msg'=>'钱包服务器转出币失败,请手动转出'];
                        } else {
                            return ['code'=>1,'msg'=>'转出成功'];
                        }
                    }

                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        return ['code'=>1,'msg'=>'转出成功'];
                    } else {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        return ['code'=>1,'msg'=>'转出申请成功,请等待审核！'];
                    }
                } else {
                    $mo->execute('rollback');
                    return ['code'=>0,'msg'=>'转出失败!'];
                }
            }
        }

        if ($Coin['type'] == 'btm'){
            $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
            if ($btmzData){
                $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                $chkAddr = $btmClient->validateAddress($addr);
                if ($chkAddr){
                    if ($chkAddr['valid'] && $chkAddr['is_local']){
                        return ['code'=>0,'msg'=>'禁止站内互转!'];
                    }
                }else{
                    return ['code'=>0,'msg'=>'地址错误!'];
                }

                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币

                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write ,weike_myzr write, weike_myzc_fee write');
                $rs = array();
                $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('btm', $num);
                $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $r = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc('btm', $fee);
                        debug(array('res' => $r, 'lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                    } else {
                        $rs[] = $r = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], 'btm' => $fee));
                    }
                }
                if (check_arr($rs)) {
                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');

                        $res = $btmClient->outcome($addr,$mum);
                        if ($res){
                            $sigRes = $btmClient->signTransaction($res);
                            if ($sigRes){
                                $resSub = $btmClient->submitTransaction($sigRes);
                                if ($resSub){
                                    M('myzc')->where(array('id' => trim($aid)))->save(['status'=>1,'txid'=>$resSub['tx_id']]);
                                    $flag = true;
                                }else{
                                    M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                                    $flag = false;
                                }
                            }else{
                                M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                                $flag = false;
                            }
                        }else{
                            M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                            $flag = false;
                        }
                    }

                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        if ($flag){
                            return ['code'=>1,'msg'=>'转出成功!'];
                        }else{
                            return ['code'=>0,'msg'=>'转出成功，请等待确认!'];
                        }
                    } else {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        return ['code'=>1,'msg'=>'转出申请成功,请等待审核！'];
                    }
                } else {
                    $mo->execute('rollback');
                    return ['code'=>0,'msg'=>'转出失败！'];
                }

            }else{
                return ['code'=>0,'msg'=>'转出失败！'];
            }
        }

        if ($Coin['type'] == 'xrp'){
            $user_wallet =  M('UserQianbao')->where(array('addr' => $addr,'userid'=>userid(),'coinname'=>'xrp'))->find();
            $addr = $user_wallet['memo'] ? $user_wallet['addr'] . ' ' .$user_wallet['memo'] :  $user_wallet['addr'];
            $mo = M();
            $mo->startTrans();
            try{
                $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('xrp', $num);
                $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));

                if ($fee) {
                    $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc('xrp', $fee);
                    } else {
                        $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], 'xrp' => $fee));
                    }
                }
                $mo->commit();
                $flag = true;
            }catch (\Exception $e){
                $mo->rollback();
                $flag = false;
            }

            if ($flag){
                return ['code'=>1,'msg'=>'添加成功！'];
            }else{
                return ['code'=>0,'msg'=>'添加失败！'];

            }

        }
    }
}