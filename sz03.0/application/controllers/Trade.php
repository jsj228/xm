<?php
/**
 * 交易中心
 */
class TradeController extends Ctrl_Base
{
    protected $_auth = 1;

    public function indexAction($name="wcg_cnyx")
    {
        if(!$name)
        {
            $coinPair = Coin_PairModel::getInstance()->where('status='.Coin_PairModel::STATUS_ON)->fRow();
            $name = $coinPair['name'];
        }
        else
        {
            //币规则
            $coinPair = Coin_PairModel::getInstance()->ffName($name);
            if (!$coinPair || $coinPair['status'] == 2)
            {
                $this->page(404);
            }
        }
        
        
        if ($coinPair['open_start'] == $coinPair['open_end'])
        {
            $coinPair['tradetime'] = '24' . $GLOBALS['MSG']['HOURS'];
        }
        else
        {
             $coinPair['tradetime'] = trim(chunk_split($coinPair['open_start'], 2, ':'), ':') . ' - ' . trim(chunk_split($coinPair['open_end'], 2, ':'), ':');
        }
        
        //币信息
        $coinInfo = User_CoinModel::getInstance()->ffName(trim($coinPair['coin_from']));

        //所有币
        $coinLogo = User_CoinModel::getInstance()->field('logo,name')->fList();
        $coinLogo = array_column($coinLogo, 'logo', 'name');

        // $coinList = Coin_PairModel::getInstance()->field('name,coin_from,coin_to')->where(['status'=>Coin_PairModel::STATUS_ON])->fList();

        // $temp = array();
        // foreach ($coinList as $v) 
        // {
        //     $temp[$v['coin_to']][$v['coin_from']] = $v['name'];
        //     if($coinPair['name']==$v['name'])
        //     {
        //         unset($temp[$v['coin_to']][$v['coin_from']]);
        //     }
        // }
        // $coinList = $temp;
        // if(!isset($coinList['usdt']))
        // {
        //     $coinList['usdt'] = [];
        // }
        // //$coinList['创新区'] = ['obc'=>'obc_btc'];
        // //交易区排序
        // $areaSort = array('dob','btc','eth','new');
        // $temp = array();
        // foreach ($areaSort as $v) 
        // {
        //     $temp[$v] = $coinList[$v];
        // }
        // $coinList = $temp;

        $pwdFlag = 2;//需要输入交易密码
        if ($this->mCurUser && Tool_Md5::pwdTradeCheck($this->mCurUser['uid']))
        {
            $pwdFlag = 1;//不需要输入交易密码
        }

        //是否免手续费
        if($this->mCurUser)
        {
            $free = FreeTradeUserModel::getInstance()->where(array('uid'=>$this->mCurUser['uid']))->fRow();
            if($free && $free['end_time']>time())
            {
                $coinPair['rate'] = $coinPair['rate_buy'] = 0;
                $coinPair['free_expire'] = $free['end_time'] - time();
            }
        }
        
        //当前交易区
        $curArea = $coinPair['type']==1?$coinPair['coin_to']:'new';

        //最新價格
        $quote  = Cache_Redis::instance('quote')->get($coinPair['name'] . '_quote');;
        $quote  = json_decode($quote, true);
        $quote['price'] = Tool_Str::format($quote['price'], 20, 0, true);
        $coinName= strtoupper(str_replace('_', '/', $coinPair['name']));
        $coinName1 = strtoupper($coinPair['coin_from']);
        $str1=sprintf($GLOBALS['MSG']['COIN_TO_BTC_PRICE'], $quote['price'], $coinName);
        $str2 = sprintf($GLOBALS['MSG']['THE_NEW_PRICE'], $coinName1, $quote['price']);
        $this->seo($str1, $str2, $coinPair['coin_from'].'交易'.$coinPair['coin_to'].','.$str1);




        $this->assign('curArea', $curArea);
        $this->assign('coinLogo', $coinLogo);
        $this->assign('pwdFlag', $pwdFlag);
        $this->assign('coinPair', $coinPair);
        $this->assign('coinInfo', $coinInfo);
    }


    // 校验数据
    public function checkAction()
    {
        $coin = $_GET['coin'];
        if(!$coin){die('param error');}
        $userInfo = UserModel::getInstance()->field(sprintf('updated, btc_over, btc_lock, %s_over, %s_lock', $coin, $coin))->fRow($this->mCurUser['uid']);
        $fee = UserModel::getInstance()->field(sprintf('updated, btc_over, btc_lock, %s_over, %s_lock', $coin, $coin))->fRow(1);
        $cKey     = $coin.'_old';
        $oldData  = json_decode($_COOKIE[$cKey], true);
        if(!$oldData){
            $saveData = array('user'=>$userInfo, 'fee'=>$fee);
            setcookie($cKey, json_encode($saveData), time()+864000, '/');
        }
        if($oldData && $oldData['user']['updated'] != $userInfo['updated'])
        {
            $diff['user'][$coin.'_over'] = $this->fmtNum(bcsub($userInfo[$coin.'_over'], $oldData['user'][$coin.'_over'], 20));
            $diff['user'][$coin.'_lock'] = $this->fmtNum(bcsub($userInfo[$coin.'_lock'], $oldData['user'][$coin.'_lock'], 20));
            $diff['user']['btc_over'] = $this->fmtNum(bcsub($userInfo['btc_over'], $oldData['user']['btc_over'], 20));
            $diff['user']['btc_lock'] = $this->fmtNum(bcsub($userInfo['btc_lock'], $oldData['user']['btc_lock'], 20));

            $diff['user']['updated'] = $userInfo['updated'];

            $diff['fee'][$coin.'_over'] = $this->fmtNum(bcsub($fee[$coin.'_over'], $oldData['fee'][$coin.'_over'], 20));
            $diff['fee'][$coin.'_lock'] = $this->fmtNum(bcsub($fee[$coin.'_lock'], $oldData['fee'][$coin.'_lock'], 20));
            $diff['fee']['btc_over'] = $this->fmtNum(bcsub($fee['btc_over'], $oldData['fee']['btc_over'], 20));
            $diff['fee']['btc_lock'] = $this->fmtNum(bcsub($fee['btc_lock'], $oldData['fee']['btc_lock'], 20));

            $diff['fee']['updated'] = $fee['updated'];

            $saveData = array('user'=>$userInfo, 'fee'=>$fee);
            setcookie($cKey, json_encode($saveData), time()+864000, '/');
        }

        $this->assign('userInfo', $userInfo);
        $this->assign('fee', $fee);
        $this->assign('diff', $diff);
    }


    private function fmtNum($number)
    {
        return trim(preg_replace('/(\.\d*?)0+$/', '$1', $number),'.');
    }

    public function batchCancelAction()
    {
        $pair = $_GET['pair'];
        if($pair)
        {
            list($coinFrom, $coinTo) = explode('_', $pair);
            $aList = Trust_CoinModel::getInstance()->designTable($coinFrom)->where(array('coin_from'=>$coinFrom, 'coin_to'=>$coinTo, 'status'=>['<', 2]))->fList();
            foreach($aList as $v)
            {
                Trust_CoinModel::getInstance()->begin();
                $r = Trust_CoinModel::getInstance()->where(array('id'=>$v['id'],))->update(array('numberover'=>0, 'status'=>3, 'updated'=>time()));
                if(!$r)
                {
                    Trust_CoinModel::getInstance()->back();
                    break;
                }
                if($v['flag']=='buy')
                {
                    $num = Tool_Math::mul($v['price'], $v['numberover']);
                    $r = UserModel::getInstance()->exec("UPDATE user SET {$coinTo}_over={$coinTo}_over+{$num}, {$coinTo}_lock={$coinTo}_lock-{$num} WHERE uid={$v['uid']}");
                    
                }   
                elseif($v['flag']=='sale')
                {
                    $num = $v['numberover'];
                    $r = UserModel::getInstance()->exec("UPDATE user SET {$coinFrom}_over={$coinFrom}_over+{$num}, {$coinFrom}_lock={$coinFrom}_lock-{$num} WHERE uid={$v['uid']}");
                }
                else
                {
                    die('flag error '.$v['id']);
                }

                if(!$r)
                {
                    Trust_CoinModel::getInstance()->back();
                    break;
                }
                Trust_CoinModel::getInstance()->commit();
                Tool_Session::mark($v['uid']);
            }
        }

        die('result: '.($r?'success':'failed'));

    }
}
