<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/15
 * Time: 9:53
 */


//刷单机器人 (单进程)
class Cli_RobotController extends Ctrl_Cli
{

    public function runAction(){

        $coinList = Coin_PairModel::getInstance()->where('status=' . Coin_PairModel::STATUS_ON . " and robot='on'")->fList();
        $this->work($coinList);
    }


    public function work($list){

        $userMo = UserModel::getInstance();

        $Robot_ApiLogic = new Robot_ApiLogicController();


        //开始工作
        while(true){
            foreach ($list as $coin){

                try{
                    $trustMo = Trust_CoinModel::getInstance()->designTable($coin['coin_from']);
                    $buy_where = "coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}' and flag='buy' and status=0 and numberover>0";
                    $buy_price = $trustMo->where($buy_where)->order("price desc")->fOne('price');

                    $sale_where = "coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}' and flag='sale' and status=0 and numberover>0";
                    $sale_price = $trustMo->where($sale_where)->order("price asc")->fOne('price');

                    if (in_array($coin['coin_from'], ['doge', 'eac', 'oioc', 'ifc', 'bcx'])) {
                        $holdF = mt_rand(5, 7);
                    } elseif (in_array($coin['coin_from'], ['sie', 'btm'])) {
                        $holdF = mt_rand(2, 4);
                    } else {
                        $holdF = mt_rand(1, 4);
                    }

                    $buyPrice = round($buy_price + ($sale_price - $buy_price) * mt_rand(5,6) / 10, $holdF);
                    $buy_num = round(mt_rand($coin['robot_min_num'], $coin['robot_max_num']) - mt_rand() / mt_getrandmax(), mt_rand(0, 4));

                    $sale_num = round(mt_rand($coin['robot_min_num'], $coin['robot_max_num']) - mt_rand() / mt_getrandmax(), mt_rand(0, 4));
                    $salePrice = round($buy_price + ($sale_price - $buy_price) * mt_rand(4, 5) / 10, $holdF);

                    if ($buyPrice >= $sale_price || $buyPrice <= $buy_price) {
                        $buyPrice = $buy_price + ($sale_price - $buy_price) * mt_rand(6, 8) / 10;
                    }
                    if ($salePrice <= $buy_price || $salePrice >= $sale_price) {
                        $salePrice = $buy_price + ($sale_price - $buy_price) * mt_rand(3, 5) / 10;
                    }

                    $buy_input = ['type' => 'in', 'price' => $buyPrice, 'number' => $buy_num, 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];
                    $sale_input = ['type' => 'out', 'price' => $salePrice, 'number' => $sale_num, 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];

                    //机器人账号
                    $user = $userMo->where(['mo' => '18800000000'])->fRow();

                    $random = mt_rand(1, 10);

                    if ($random % 2) {
                        //下成交买卖单
                        $buy = $Robot_ApiLogic->setTrust($buy_input, $user);
                        $sale = $Robot_ApiLogic->setTrust($sale_input, $user);
                    } else {
                        $sale = $Robot_ApiLogic->setTrust($sale_input, $user);
                        $buy = $Robot_ApiLogic->setTrust($buy_input, $user);
                    }

                    Tool_Out::p($sale);
                    Tool_Out::p($buy);

                    //暂停10毫秒 使订单成交
//                    usleep(10000);

                    //撤销订单
                    if ($buy['code'] == 1000) {
                        echo '下买单成功' . $sale['id'];
                        $buy_cancel_input = ['id' => $buy['id'], 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];
                        $buy_cancel = $Robot_ApiLogic->trustcancel($buy_cancel_input, $user);
                        echo "撤销买单成功" . $buy_cancel['message'];

                    }
                    if ($sale['code'] == 1000) {
                        echo '下卖单成功' . $sale['id'];
                        $sale_cancel_input = ['id' => $sale['id'], 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];
                        $sale_cancel = $Robot_ApiLogic->trustcancel($sale_cancel_input, $user);
                        echo "撤销卖单成功" . $sale_cancel['message'];
//                        Tool_Out::p($sale_cancel_id);
                    }

                    echo "\n" . $coin['coin_from'] . "\n";
                    echo '买一价' . $buy_price . "\n";
                    echo '卖一价' . $sale_price . "\n";
                    echo '成交买一价:' . $buyPrice . "数量:" . $buy_num . "\n";
                    echo '成交卖一价:' . $salePrice . "数量:" . $sale_num . "\n";
                    echo "结束\n\n\n\n";

                    sleep(mt_rand(1,10));
                }catch (Exception $e){
                    echo "异常";
                }
            }
        }

    }
}