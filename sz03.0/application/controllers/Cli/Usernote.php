<?php
/**
 *  用户
 */
class Cli_UsernoteController extends Ctrl_Cli
{
	protected $logDir = 'UserNote/';
    # Run
    public function runAction()
    {
    	while(true)
    	{
    		$this->firstTransCoin();
            $this->firstTrade();
            sleep(30);
    	}
    }


    /**
     *  首次转币
     */
    private function firstTransCoin()
    {
        $coinList = User_CoinModel::getInstance()->field('name')->where(array('status'=>User_CoinModel::STATUS_ON))->fList();
        $exKey  = 'USER_NOTE_EX_LAST_ID';
        $inKey  = 'USER_TRANS_IN_FIRST';
        $outKey = 'USER_TRANS_OUT_FIRST';
        $tRedis = Cache_Redis::instance();
        $lastIdMap = $tRedis->hGetAll($noteKey)?:[];

        foreach ($coinList as $v)
        {
            $mo = 'Exchange_' . ucfirst($v['name']).'Model';
            $where = 'bak="" ';
            if(isset($lastIdMap[$v['name']]))
            {
                $where = ' and id > '. $lastIdMap[$v['name']];
            }

            $newTrans = $mo::getInstance()->field('uid, opt_type, max(id) maxid')->where($where)->group('uid, opt_type')->limit(500)->fList();
            if(!$newTrans)
            {
                continue;
            }

            $ex = array();
            foreach($newTrans as $transOne)
            {
                $ex[$transOne['opt_type']][$transOne['uid']] = 1;
            }

            $lastid = max(array_column($newTrans, 'maxid'));

            if($lastid)
            {
                $tRedis->hset($exKey, $v['name'], $lastid);
            }

            if($ex['in'])
            {
                $tRedis->hmset($inKey, $ex['in']);
            }

            if($ex['out'])
            {
                $tRedis->hmset($outKey, $ex['out']);
            }
        }
    }


    /**
     *  首次交易
     */
    private function firstTrade()
    {
        $coinList = Coin_PairModel::getInstance()->field('coin_from name')->where(array('status'=>Coin_PairModel::STATUS_ON))->group('coin_from')->fList();
        $lKey  = 'USER_NOTE_ORDER_LAST_ID';
        $orderKey  = 'USER_ORDER_FIRST';
        $tRedis = Cache_Redis::instance();
        $lastIdMap = $tRedis->hGetAll($lKey)?:[];

        foreach ($coinList as $v)
        {
            $where = '';
            if(isset($lastIdMap[$v['name']]))
            {
                $where = 'id > '. $lastIdMap[$v['name']];
            }

            $newOrders = Order_CoinModel::getInstance()->designTable(strtolower($v['name']))->field('buy_uid, sale_uid, max(id) maxid')->where($where)->group('buy_uid, sale_uid')->fList();

            if(!$newOrders)
            {
                continue;
            }
            $ods = array();
            foreach($newOrders as $orderOne)
            {
                $ods[$orderOne['buy_uid']] = 1;
                $ods[$orderOne['sale_uid']] = 1;
            }

            $lastid = max(array_column($newOrders, 'maxid'));

            $tRedis->hset($lKey, $v['name'], $lastid);
            $tRedis->hmset($orderKey, $ods);
        }
    }
}