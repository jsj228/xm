<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/19
 * Time: 17:12
 */

class Robot_CountLogicController extends Ctrl_Base{

    //统计总额
    public function income($input){
        $coin = strtolower(substr($input['currency'],0,strrpos($input['currency'],"_")));
        $start_time = $input['startTime'];
        $endTime = $input['endTime'];
        $type = $input['type'];//type:1 自己与自己成交  type:2 自己与用户成交  type:3 用户与用户(包括自己)成交
        $uid = $input['uid'];

        if($type==1){
            $typewherebuy = "buy_uid = '$uid' and buy_uid != sale_uid";
            $typewheresell = "sale_uid = '$uid' and buy_uid != sale_uid";
        } else if($type==2){
            $typewherebuy = "buy_uid != '$uid'";
            $typewheresell = "sale_uid != '$uid'";
        }else{
            $typewherebuy = "buy_uid='$uid' and buy_uid = sale_uid";
            $typewheresell = "sale_uid='$uid' and buy_uid = sale_uid";
        }

        if($start_time && $endTime) $where = "and created >= $start_time and created < $endTime";
        if($start_time && !$endTime) $where = "and created > '$start_time'";
        if(!$start_time && $endTime) $where = "and created < $endTime";
        if(!$start_time && !$endTime) $where = '';


        $mo = new Orm_Base();
        $buy = $mo->query("select sum(number) num,sum(number*price) mum from order_{$coin}coin where $typewherebuy $where");
        $sell = $mo->query("select sum(number) num,sum(number*price) mum from order_{$coin}coin where $typewheresell $where");

        $res = [
            'buy_num' => $buy[0]['num'],
            'buy_mum' => $buy[0]['mum'],
            'sell_num' => $sell[0]['num'],
            'sell_mum' => $sell[0]['mum']
        ];
        return $res;
    }

    //统计盘口数据
    public static function trades($input,$uid){
        $coin = strtolower(substr($input['currency'],0,strrpos($input['currency'],"_")));

        $own = $input['own'];
        if($own){ $ownWhere = "uid = $uid";}else{ $ownWhere = "uid != $uid";}

        $mo = new Orm_Base();
        $buy = $mo->query("select count(id) total,sum(numberover) num,sum((numberover)*price) mum from trust_{$coin}coin where $ownWhere and (status='0' or status='1')");
        $sell = $mo->query("select count(id) total,sum(numberover) num,sum((numberover)*price) mum from trust_{$coin}coin where $ownWhere and (status='0' or status='1')");

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
    public static function trades_level($input,$uid)
    {
        $coin = strtolower(substr($input['currency'],0,strrpos($input['currency'],"_")));

        $own = $input['own'];
        if ($own) {
            $ownWhere = "uid = $uid";
        } else {
            $ownWhere = "uid != $uid";
        }

        $size = $input['size'];
        $limit = $input['limit'];
        $kline = $input['kline'];

        $mo = new Orm_Base();
        $buy = $mo->query("select numberover,price from trust_{$coin}coin where $ownWhere and (status='0' or status='1')");
        $sell = $mo->query("select numberover,price from trust_{$coin}coin where $ownWhere and (status='0' or status='1')");

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
                    $res['buy_num'][$i] += $v['numberover'];
                    $res['buy_mum'][$i] += $v['numberover']*$v['price'];
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
                    $res['sell_num'][$i] += $v['numberover'];
                    $res['sell_mum'][$i] += $v['numberover']*$v['price'];
                }
            }
        }

        return $res;
    }

    //统计盘口详情 默认type：0(只显示用户数据) type:1(只显示自己数据) type：2(都显示)
    public static function trades_detail($input,$uid){
        $coin = strtolower(substr($input['currency'],0,strrpos($input['currency'],"_")));
        $limit = $input['limit'];
        if($input['type']==0){
            $where = "t.uid != $uid and";
        }else if($input['type']==1){
            $where = "t.uid = $uid and";
        }else{
            $where = '';
        }
        $mo = new Orm_Base();
        $buy_trade = $mo->query("select t.price,t.numberover unnum,t.numberover*t.price mum,t.created addtime,u.name username,au.name truename from trust_{$coin}coin t left join user u on t.uid=u.uid left join autonym au on au.uid=t.uid
                                where $where t.flag='buy' and (t.status=0 or t.status=1) order by t.price desc,t.id asc limit $limit");
        $sell_trade = $mo->query("select t.price,t.numberover unnum,t.numberover*t.price mum,t.created addtime,u.name username,au.name truename from trust_{$coin}coin t left join user u on t.uid=u.uid left join autonym au on au.uid=t.uid
                                where $where t.flag='sale' and (t.status=0 or t.status=1) order by t.price asc,t.id asc limit $limit");
        $sell_trade = array_reverse($sell_trade);
        foreach ($buy_trade as $k=>$v){
            $buy_trade[$k]['created'] = date('m-d H:i:s',$v['created']);
        }

        foreach ($sell_trade as $k=>$v){
            $sell_trade[$k]['created'] = date('m-d H:i:s',$v['created']);
        }

        $res = ['buy_trade'=>$buy_trade,'sell_trade'=>$sell_trade];
        return $res;
    }


    //统计交易记录 type true 自己与用户,type false 自己与用户和用户与用户 userid!=peerid
    public static function trade_log($input,$uid){
        $coin = strtolower(substr($input['currency'],0,strrpos($input['currency'],"_")));
        $limit = $input['limit'];
        if($input['type']){
            $where = "buy_uid!=sale_uid and (buy_uid=$uid or sale_uid=$uid)";
        }else{
            $where = "buy_uid!=sale_uid ";
        }
        $mo = new Orm_Base();
        $trade_log = $mo->query("select buy_uid,sale_uid,price,number num,number*price mum,opt `type`,created from order_{$coin}coin where  $where order by created desc,id desc limit $limit ");
        $userids = array_column($trade_log,'buy_uid');
        $peerids = array_column($trade_log,'sale_uid');
        $uids = array_merge($userids,$peerids);
        $uids = array_unique($uids);
        $uids = implode(',',$uids);
        $users = $mo->query("select u.uid,u.name username,au.name truename from user u left join autonym au on u.uid=au.uid where u.uid in($uids)");
        $usernames = array_column($users,'username','uid');
        $truenames = array_column($users,'truename','uid');

        $buy_price = 0;
        $buy_count = 0;
        $buy_number = 0;
        $buy_total = 0;
        $sell_price = 0;
        $sell_count = 0;
        $sell_number = 0;
        $sell_total = 0;

        foreach ($trade_log as $k=>$v){
            $trade_log[$k]['buy_username'] = $usernames[$v['buy_uid']];
            $trade_log[$k]['buy_truename'] = $truenames[$v['buy_uid']];
            $trade_log[$k]['sell_username'] = $usernames[$v['sale_uid']];
            $trade_log[$k]['sell_truename'] = $truenames[$v['sale_uid']];
            $trade_log[$k]['trade_time'] = date('m-d H:i:s',$v['created']);
            $trade_log[$k]['buy_own'] = $v['buy_uid']==$uid?1:0;
            $trade_log[$k]['sell_own'] = $v['sale_uid']==$uid?1:0;
            if($v['buy_uid']==$uid){
                $buy_price += $v['price'];
                $buy_count += 1;
                $buy_number += $v['num'];
                $buy_total += $v['mum'];
            }
            if($v['sale_uid']==$uid){
                $sell_price += $v['price'];
                $sell_count += 1;
                $sell_number += $v['num'];
                $sell_total += $v['mum'];
            }
        }
        $trade_log_count = [
            'buy_ave_price'=>Tool_Math::div($buy_price,$buy_count),'buy_count'=>$buy_count,'buy_number'=>$buy_number,'buy_total'=>$buy_total,
            'sell_ave_price'=>Tool_Math::div($sell_price,$sell_count),'sell_count'=>$sell_count,'sell_number'=>$sell_number,'sell_total'=>$sell_total
        ];

        $res = ['trade_log'=>$trade_log,'trade_log_count'=>$trade_log_count];
        return $res;
    }



}

