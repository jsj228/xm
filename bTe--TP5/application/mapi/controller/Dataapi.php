<?php


namespace app\mapi\controller;


class Dataapi extends Mapi
{
    public function get_data(){
        $day = time() - 86400;
        $time = time();
        $market = db('Market')->where(['status' => 1,'trade' => 1])->select();
        foreach ($market as $k => $vo){
            $data[] = $vo['name'];
            //市场标记
            $data[$vo['name']]['symbol'] = $vo['name'];
            //获取买一价
            $data[$vo['name']]['buy'] = round(db('Trade')->where(['market' => $vo['name'],'type' => 1,'status' => 0])->order('price desc')->value('price'),4);
            //获取卖一价
            $data[$vo['name']]['sell'] = round(db('Trade')->where(['market' => $vo['name'],'type' => 2,'status' => 0])->order('price asc')->value('price'),4);
            //获取24小时的最高价、最低价
            $data[$vo['name']]['high'] = round(db('TradeLog')->where(['market' => $vo['name'],'addtime' => array('between',"$day,$time")])->order('price desc')->value('price'),4);
            $data[$vo['name']]['low'] = round(db('TradeLog')->where(['market' => $vo['name'],'addtime' => array('between',"$day,$time")])->order('price asc')->value('price'),4);
            //最新成交价
            $data[$vo['name']]['last'] = round(db('TradeLog')->where(['market' => $vo['name']])->order('id desc')->value('price'),4);
            //获取24小时的成交量
            $data[$vo['name']]['vol'] = round(db('TradeLog')->where(['market' => $vo['name'],'addtime' => array('between',"$day,$time")])->sum('num'),2);
        }
        $data = json_encode($data);
        echo $data;exit();
    }
}