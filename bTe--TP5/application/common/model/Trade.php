<?php

namespace app\common\model;

use think\Model;
use think\Db;
class Trade extends Model
{
    protected $key = 'home_trade';

    // 撤销委单
    public function chexiao($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', '参数错误');
        }
        Db::startTrans();
        try {
           $trade = Db::table('weike_trade')->lock(true)->where(['id' => $id])->find();
           if (!$trade) {
               Db::rollback();
               return array('0', '订单不存在');
           }

           if ($trade['status'] != 0) {
               Db::rollback();
               return array('0', '订单不能撤销');
           }

           $xnb = explode('_', $trade['market'])[0];
           $rmb = explode('_', $trade['market'])[1];

           if (!$xnb) {
               Db::rollback();
               return array('0', '卖出市场错误');
           }

           if (!$rmb) {
               Db::rollback();
               return array('0', '买入市场错误');
           }

           $fee_buy = isset(cache('home_market')[$trade['market']]['fee_buy']) ? cache('home_market')[$trade['market']]['fee_buy'] : 0;
           $fee_sell = isset(cache('home_market')[$trade['market']]['fee_sell']) ? cache('home_market')[$trade['market']]['fee_sell'] : 0;

           if ($fee_buy < 0) {
               Db::rollback();
               return array('0', '买入手续费错误');
           }

           if ($fee_sell < 0) {
               Db::rollback();
               return array('0', '卖出手续费错误');
           }

           $rs = [];
           if ($trade['type'] == 1) {
               $mun = round(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
               $user_buy = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $trade['userid']))->find();

               if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                   $save_buy_rmb = $mun;
               } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                   $save_buy_rmb = $user_buy[$rmb . 'd'];
               } else {
                   Db::rollback();
                   Db::table('weike_trade')->where(array('id' => $id))->update(['endtime' => time(), 'status' => 2]);
                   return array('0', '撤销失败1');
               }

               $finance = Db::table('weike_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
               $finance_num_user_coin = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->find();
               $rs[] = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $save_buy_rmb);
               $rs[] = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $save_buy_rmb);
               $finance_nameid = $trade['id'];
               $save_buy_rmb = $save_buy_rmb;
               $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->find();
               $finance_hash = md5($trade['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $save_buy_rmb . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
               $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

               if ($finance['mum'] < $finance_num) {
                   $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
               } else {
                   $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
               }

               if ($rmb == "hkd") {
                   $rs[] = Db::table('weike_finance')->insert(array('userid' => $trade['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => '交易中心-交易撤销' . $trade['market'], 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status), false, true);
               }
               $rs[] = Db::table('weike_trade')->where(array('id' => $trade['id']))->setField('status', 2);
               $you_buy = Db::table('weike_trade')->where(array(
                   'market' => array('like', '%' . $rmb . '%'),
                   'status' => 0,
                   'userid' => $trade['userid']
               ))->find();

               if (!$you_buy) {
                   $you_user_buy = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->find();

                   if (0 < $you_user_buy[$rmb . 'd']) {
                       $rs[] = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->setField($rmb . 'd', 0);
                   }
               }
           } else if ($trade['type'] == 2) {
               $mun = round($trade['num'] - $trade['deal'], 8);
               $user_sell = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $trade['userid']))->find();

               if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                   $save_sell_xnb = $mun;
               } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                   $save_sell_xnb = $user_sell[$xnb . 'd'];
               } else {
                   Db::rollback();
                   Db::table('weike_trade')->where(array('id' => $trade['id']))->setField(['endtime' => time(), 'status' => 2]);
                   return array('0', '撤销失败2');
               }

               if (0 < $save_sell_xnb) {
                   $rs[] = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $save_sell_xnb);
                   $rs[] = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $save_sell_xnb);
               }

               $rs[] = Db::table('weike_trade')->where(array('id' => $trade['id']))->setField('status', 2);
               $you_sell = Db::table('weike_trade')->where(array(
                   'market' => array('like', $xnb . '%'),
                   'status' => 0,
                   'userid' => $trade['userid']
               ))->find();

               if (!$you_sell) {
                   $you_user_sell = Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->find();

                   if (0 < $you_user_sell[$xnb . 'd']) {
                       Db::table('weike_user_coin')->where(array('userid' => $trade['userid']))->setField($xnb . 'd', 0);
                   }
               }
           } else {
               Db::rollback();
               return array('0', '撤销失败3');
           }

           if (check_arr($rs)) {
               Db::commit();
               cache('getDepth', null);

               return array('1', '撤销成功');
           } else {
               Db::rollback();
               return array('0', '撤销失败4|' . implode('|', $rs));
           }
       }catch (Exception $e) {
           Db::rollback();
           return array('0', '撤销失败5');
       }
    }
}
