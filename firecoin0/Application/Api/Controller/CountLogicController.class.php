<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/19
 * Time: 17:12
 */
namespace Api\Controller;

use function GuzzleHttp\Promise\queue;

class CountLogicController{

    //统计总额
    public static function income($input){
        $currency = $input['currency'];
        $start_time = $input['startTime'];
        $endTime = $input['endTime'];
        $type = $input['type'];//type:1 自己与自己成交  type:2 自己与用户成交  type:3 用户与用户(包括自己)成交
        $uid = userid()?userid():32424;
        if($type==1){
            $typewherebuy = "userid = '$uid' and userid != peerid";
            $typewheresell = "peerid = '$uid' and userid != peerid";
        } else if($type==2){
            $typewherebuy = "userid != '$uid'";
            $typewheresell = "peerid != '$uid'";
        }else{
            $typewherebuy = "userid='$uid' and userid = peerid";
            $typewheresell = "peerid='$uid' and userid = peerid";
        }

        if($start_time && $endTime) $where = "and addtime >= $start_time and addtime < $endTime";
        if($start_time && !$endTime) $where = "and addtime > '$start_time'";
        if(!$start_time && $endTime) $where = "and addtime < $endTime";
        if(!$start_time && !$endTime) $where = '';


        $m = M();
        $buy = $m->query("select sum(num) num,sum(mum) mum from weike_trade_log where $typewherebuy and market='$currency' and status='1' $where");
        $sell = $m->query("select sum(num) num,sum(mum) mum from weike_trade_log where $typewheresell and market='$currency' and status='1' $where");

        $res = [
            'buy_num' => $buy[0]['num'],
            'buy_mum' => $buy[0]['mum'],
            'sell_num' => $sell[0]['num'],
            'sell_mum' => $sell[0]['mum']
        ];
        return $res;
    }

    //统计盘口数据
    public static function trades($input){
        $currency = $input['currency'];
        $uid = userid();

        $own = $input['own'];
        if($own){ $ownWhere = "userid = $uid";}else{ $ownWhere = "userid != $uid";}

        $m = M();
        $buy = $m->query("select count(*) total,sum(num-deal) num,sum((num-deal)*price) mum from weike_trade where $ownWhere and market='$currency' and `type`='1' and status='0'");
        $sell = $m->query("select count(*) total,sum(num-deal) num,sum((num-deal)*price) mum from weike_trade where $ownWhere and market='$currency' and `type`='2' and status='0'");

        $res = [
            'buy_total'=> $buy[0]['total'],
            'buy_num' => $buy[0]['num'],
            'buy_mum' => $buy[0]['mum'],
            'sell_total'=> $sell[0]['total'],
            'sell_num' => $sell[0]['num'],
            'sell_mum' => $sell[0]['mum']
        ];
        return $res;
    }

    //分档次统计盘口数据
    public static function trades_level($input)
    {
        $currency = $input['currency'];
        $uid = userid();

        $own = $input['own'];
        if ($own) {
            $ownWhere = "userid = $uid";
        } else {
            $ownWhere = "userid != $uid";
        }

        $size = $input['size'];
        $limit = $input['limit'];
        $kline = $input['kline'];

        $m = M();
        $buy = $m->query("select num,deal,price from weike_trade where $ownWhere and market='$currency' and `type`='1' and status='0'");
        $sell = $m->query("select num,deal,price from weike_trade where $ownWhere and market='$currency' and `type`='2' and status='0'");

        $res = [];

        for ($i=0;$i<=$limit;$i++){
            if($i){
                $res['buy_range'][$i] = $kline-$size*($i-1).'~~~'.($kline-$size*$i);
            }else{
                $res['buy_range'][$i] = $kline.'+++';
            }
            $res['buy_total'][$i] = 0;
            $res['buy_num'][$i] = 0;
            $res['buy_mum'][$i] = 0;
            foreach ($buy as $k => $v) {
                if(($v['price']<=$kline-$size*($i-1) && $v['price'] > $kline-$size*$i && $i) || ($v['price']>$kline && !$i)){
                    $res['buy_total'][$i] += 1;
                    $res['buy_num'][$i] += $v['num']-$v['deal'];
                    $res['buy_mum'][$i] += ($v['num']-$v['deal'])*$v['price'];
                }
            }
        }

        for ($i=0;$i<=$limit;$i++){
            if($i){
                $res['sell_range'][$i] = $kline+$size*($i-1).'~~~'.($kline+$size*$i);
            }else{
                $res['sell_range'][0] = $kline.'---';
            }

            $res['sell_total'][$i] = 0;
            $res['sell_num'][$i] = 0;
            $res['sell_mum'][$i] = 0;
            foreach ($sell as $k => $v) {
                if(($v['price']>=$kline+$size*($i-1) && $v['price'] < $kline+$size*$i && $i) || ($v['price']<$kline && !$i)){
                    $res['sell_total'][$i] += 1;
                    $res['sell_num'][$i] += $v['num']-$v['deal'];
                    $res['sell_mum'][$i] += ($v['num']-$v['deal'])*$v['price'];
                }
            }
        }

        return $res;
    }

    //统计交易记录 type true 自己与用户,type false 自己与用户和用户与用户 userid!=peerid
    public static function trade_log($input){
        $currency = $input['currency'];
        $uid = userid()?userid():32424;
        $limit = $input['limit'];
        if($input['type']){
            $where = "market='$currency' and userid!=peerid and status=1 and (userid=$uid or peerid=$uid)";
        }else{
            $where = "market='$currency' and userid!=peerid and status=1";
        }
        $m = M();
        $trade_log = $m->query("select userid,peerid,price,num,mum,`type`,addtime from weike_trade_log where  $where order by addtime desc,id desc limit $limit ");
        $userids = array_column($trade_log,'userid');
        $peerids = array_column($trade_log,'peerid');
        $uids = array_merge($userids,$peerids);
        $uids = array_unique($uids);
        $users = M('user')->where(['id'=>['in',$uids]])->field('id,username,truename')->select();
        $usernames = array_column($users,'username','id');
        $truenames = array_column($users,'truename','id');

        $buy_price = 0;
        $buy_count = 0;
        $buy_number = 0;
        $buy_total = 0;
        $sell_price = 0;
        $sell_count = 0;
        $sell_number = 0;
        $sell_total = 0;

        foreach ($trade_log as $k=>$v){
            $trade_log[$k]['buy_username'] = $usernames[$v['userid']];
            $trade_log[$k]['buy_truename'] = $truenames[$v['userid']];
            $trade_log[$k]['sell_username'] = $usernames[$v['peerid']];
            $trade_log[$k]['sell_truename'] = $truenames[$v['peerid']];
            $trade_log[$k]['trade_time'] = date('m-d H:i:s',$v['addtime']);
            $trade_log[$k]['buy_own'] = $v['userid']==$uid?1:0;
            $trade_log[$k]['sell_own'] = $v['peerid']==$uid?1:0;
            if($v['userid']==$uid){
                $buy_price += $v['price'];
                $buy_count += 1;
                $buy_number += $v['num'];
                $buy_total += $v['mum'];
            }
            if($v['peerid']==$uid){
                $sell_price += $v['price'];
                $sell_count += 1;
                $sell_number += $v['num'];
                $sell_total += $v['mum'];
            }
        }
        $trade_log_count = [
            'buy_ave_price'=>$buy_price/$buy_count,'buy_count'=>$buy_count,'buy_number'=>$buy_number,'buy_total'=>$buy_total,
            'sell_ave_price'=>$sell_price/$sell_count,'sell_count'=>$sell_count,'sell_number'=>$sell_number,'sell_total'=>$sell_total
        ];

        $res = ['trade_log'=>$trade_log,'trade_log_count'=>$trade_log_count];
        return $res;
    }

    //统计盘口详情 默认type：0(只显示用户数据) type:1(只显示自己数据) type：2(都显示)
    public static function trades_detail($input){
        $currency = $input['currency'];
        $uid = userid()?userid():32424;
        $limit = $input['limit'];
        if($input['type']==0){
            $where = "t.userid!=$uid and";
        }else if($input['type']==1){
            $where = "t.userid=$uid and";
        }else{
            $where = '';
        }
        $m = M();
        $buy_trade = $m->query("select t.price,t.num-t.deal unnum,t.mum,t.addtime,u.username,u.truename from weike_trade t left join weike_user u on t.userid=u.id
                                where $where t.type=1 and t.status=0 and market='$currency' order by t.price desc,t.id asc limit $limit");
        $sell_trade = $m->query("select t.price,t.num-t.deal unnum,t.mum,t.addtime,u.username,u.truename from weike_trade t left join weike_user u on t.userid=u.id
                                where $where t.type=2 and t.status=0 and market='$currency'  order by t.price asc,t.id asc limit $limit");

        $sell_trade = array_reverse($sell_trade);
        foreach ($buy_trade as $k=>$v){
            $buy_trade[$k]['addtime'] = date('m-d H:i:s',$v['addtime']);
        }

        foreach ($sell_trade as $k=>$v){
            $sell_trade[$k]['addtime'] = date('m-d H:i:s',$v['addtime']);
        }

        $res = ['buy_trade'=>$buy_trade,'sell_trade'=>$sell_trade];
        return $res;
    }





}

