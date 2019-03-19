<?php
/*
 *戳单
 */
class Cli_TradeController extends Ctrl_Cli
{
    //免手续费用户uid列表文件
    private $noFeeUserConf = 'TradeNoFeeUid.list';

    //model实例
    private $model = array();

    //延迟处理的trust_id
    private $delayId = 0;

    //空数据查询间隔秒
    private $sleep = 1;

    //进程列表
    private $process = array();

    //错误
    private $error = array();

    //日志文件
    private $logDir = array(
        'process' => 'process',
        'trade'   => 'trade',
    );

    //启动标识文件
    private $processRunning = 'running.lock';


    function init()
    {
        $this->noFeeUserConf = CONF_PATH.$this->noFeeUserConf;
    }
    /**
     * 程序入口（多进程）
     */
    public function runAction()
    {
        //如果进程正在运行，需要删掉‘启动标识文件’
        $this->checkProcessRunningLock();

        $coinPairMo = new Coin_PairModel;
        $coinList   = $coinPairMo->where('status='.Coin_PairModel::STATUS_ON.' or start>'.time())->fList();
        if ($coinList)
        {
            //分发任务
            $this->distribute($coinList);
        }
        exit;
    }

    /**
     * 单币启动入口（用于调试）
     */
    public function oneAction($coin)
    {
        $coinPairMo = Coin_PairModel::getInstance();
        $coinObj    = $coinPairMo->where("status=1 and name='{$coin}'")->fRow();
        if ($coinObj)
        {
            $this->initModels();
            $this->quoteInit($coinObj['name'], 'init');
            $this->coin2coin($coinObj);
        }
        else
        {
            throw new Exception('cannot found coin : ' . $coin);
        }
        exit;
    }

    /**
     * 单进程启动方式
     */
    public function singleAction()
    {
        //如果进程正在运行，需要删掉‘启动标识文件’
        $this->checkProcessRunningLock();

        $coinPairMo = Coin_PairModel::getInstance();
        $coinList   = $coinPairMo->where('status=1')->fList();
        if ($coinList)
        {
            $this->initModels();
            while(true)
            {
                foreach($coinList as $v)
                { 
                    $this->coin2coin($v, $this->sleep);
                }
            }
        }
        exit;
    }

    /**
     * 避免重复启动
     */
    private function checkProcessRunningLock()
    {
        if(file_exists($this->processRunning))
        {
            throw new Exception('the process is already running, check process and the lock file'.PHP_EOL);
        }
        file_put_contents($this->processRunning, posix_getpid());
    }

    /**
     * 用到的model实例(注意：每个进程一定要使用不同的数据库连接)
     */
    private function initModels($connect='default')
    {
        $models = array('Order_Coin', 'Trust_Coin', 'User', 'Address', 'FreeTradeUser','FeeBonus');//,'AssetDetail'
        foreach ($models as $v)
        {
            $m               = $v . 'Model';
            $this->model[$v] = new $m('', 'default', $connect);
        }
    }

    /**
     * 分发任务
     */
    private function distribute($list)
    {
        //创建子进程
        foreach ($list as $v)
        {
            //检查是否有重复任务
            if(isset($this->process[$v['name']]))
            {
                echo 'duplicate process name ' . $v['name'].PHP_EOL;
                $this->killAll();
            }
            else
            {
                $this->process[$v['name']] = $v;
            }

            $pid = pcntl_fork();
            if ($pid == 0)
            {
                $this->setProcessTitle($v['name']);
                $this->process[$v['name']]['process_id'] = posix_getpid();
                break;
            }
            else
            {
                $this->process[$v['name']]['process_id'] = $pid;
            }
        }

        //工作
        if ($pid == 0)
        {
            $myPid = posix_getpid();

            //根据当前进程id，找到自己的任务
            $namePidMap = array_column($this->process, 'name', 'process_id');

            Tool_Log::wlog(sprintf("child process pid:%d, name:%s ", $myPid, $namePidMap[$myPid]), $this->logDir['process'], true, '[m-d H:i:s]');
            //开始工作
            $this->work($this->process[$namePidMap[$myPid]]);
        }
        else
        {
            //这里是父进程，监控所有子进程状态
            pcntl_signal(SIGTERM, array(&$this,"sigHandler"), false);
            pcntl_signal(SIGUSR2, array(&$this,"sigHandler"), false);
            Tool_Log::wlog(sprintf("father process pid:%d ", posix_getpid()), $this->logDir['process'], true, '[m-d H:i:s]');
            $this->oversee();

        }
    }

    /**
     * 监控子进程
     */
    private function oversee()
    {
        while(true)
        {
            //阻塞等待任意子进程退出
            $pid = pcntl_wait($status, WUNTRACED);
            //处理信号
            pcntl_signal_dispatch();
            //查找具体退出进程
            if ($pid > 0)
            {
                $namePidMap = array_column($this->process, 'name', 'process_id');
                Tool_Log::wlog(sprintf("%s die; pid: %d ", $namePidMap[$pid], $pid), $this->logDir['process'], true, '[m-d H:i:s]');
                unset($this->process[$namePidMap[$pid]]['process_id']);
                //重建
                $this->createProcess($namePidMap[$pid]);
            }
        }
    }

    /**
     *  进程命名
     */
    private function setProcessTitle($processName)
    {
        //命名
        $title = sprintf('├─Cli_trade───%s', $processName);
        if (function_exists('cli_set_process_title')) 
        {
            @cli_set_process_title($title);
        }
    }

    /**
     * 创建新进程
     */
    private function createProcess($processName)
    {
        $pid = pcntl_fork();
        if ($pid > 0)
        {
            //给父进程更新监听列表
            $this->process[$processName]['process_id'] = $pid;
        }
        elseif ($pid == 0)
        {
            $this->setProcessTitle($processName);
            //子进程投入工作
            Tool_Log::wlog(sprintf("created %s; pid: %d ", $processName, posix_getpid()), $this->logDir['process'], true, '[m-d H:i:s]');
            //信号委托
            pcntl_signal(SIGTERM, function(){exit;});
            $this->work($this->process[$processName]);
        }
    }




    /**
     * 信号处理
     */
    private function sigHandler($signo)
    {
        if($signo==SIGTERM)
        {
            $this->killAll();
            exit;
        }
        elseif($signo==SIGUSR2)
        {
            $this->restart();
            exit;
        }
    }


    /**
     * 结束所有进程
     */
    protected function killAll($msg='')
    {   
        foreach($this->process as $v)
        { 
            posix_kill($v['process_id'], SIGKILL);   
        }

        //删除启动标识
        unlink($this->processRunning);

        $msg = $msg?sprintf('(%s)', $msg):'';
        Tool_Log::wlog('kill all process'. $msg, $this->logDir['process'], true, '[m-d H:i:s]');
        exit(0);
    }


    /**
     * 重启所有进程
     */
    protected function restart($msg='')
    {   
        foreach($this->process as $v)
        { 
            posix_kill($v['process_id'], SIGKILL);   
        }

        //删除启动标识
        unlink($this->processRunning);

        $msg = $msg?sprintf('(%s)', $msg):'';
        Tool_Log::wlog('kill all process'. $msg, $this->logDir['process'], true, '[m-d H:i:s]');
        exit(0);
    }
    

    /**
     * 交易处理
     */
    private function work($coin)
    {
        //日志分币存放
        $this->logDir['trade'] .= '/'.$coin['name'];
        $this->initModels($coin['name']);

        //记录当前的mysql thread_id，便于出现问题后定位
        $threadId = $this->model['User']->query('SELECT CONNECTION_ID() thread_id');
        Cache_Redis::instance('quote')->hSet('current_trust_mysql_tid', $coin['name'], $threadId[0]['thread_id']);

        //初始化行情数据
        $this->quoteInit($coin['name']);
        //重复劳动
        while (true)
        {
            $this->coin2coin($coin, $this->sleep, $this->delayId);
            //处理信号
            pcntl_signal_dispatch();
        }
    }

    /**
     * 初始化行情数据
     */
    private function quoteInit($coinName)
    {
        $orderMo  = $this->model['Order_Coin']->designTable(preg_replace('/_.+/i', '',$coinName));
        $orderMo->ajaxcoinTrustList($coinName, 'init');
        $orderMo->ajaxcoinOrder($coinName);
    }

    /**
     * 币币交易主要逻辑
     */
    public function coin2coin($coin, $sleep=1, $delayId=0)
    {
        $coinName = $coin['name'];

        static $mysqlMode = true;//为true时从mysql读取新委托
        static $alreadyDealId = array();//已处理的trust id
        
        $logDir   = $this->logDir['trade'].'/'.date('Ymd');
        $orderMo  = $this->model['Order_Coin']->designTable($coin['coin_from']);
        $trustMo  = $this->model['Trust_Coin']->designTable($coin['coin_from']);
        $userMo   = $this->model['User'];
        $freeTradeUserMo = $this->model['FreeTradeUser'];

        //程序启动时，会先进入mysqlMode模式，从数据库读取新委托处理
        if($mysqlMode)
        {
            $where = "isnew='Y' and coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}'";
            if($delayId)
            {
                $where .= ' and id > '.$delayId; 
            }
            $trust_id = $trustMo->where($where)->order("id asc")->fOne('id');
            if(!$trust_id)
            {
                $mysqlMode = false;
                return 'mysqlMode end';
            }
            $alreadyDealId[$trust_id] = 1;
        } 
        //读取缓存队列
        elseif(!$qData = Cache_Redis::instance('quote')->brpop('trust_queue_'.$coinName, 10))
        {
            $alreadyDealId = array();
            //随便搞个查询保持mysql连接
            $trustMo->query('SELECT CONNECTION_ID()');
            return 'empty queue';
        }  

        if($qData)
        {
            $qData = json_decode($qData[1], true);
            //委托状态变更
            if($qData['t']=='update')
            {
                $orderMo->ajaxcoinTrustList($coinName, array($qData['d']));       
                Tool_Push::one2nSend($coinName, array('t'=>'mytrust', 'a'=>'update', 'c'=>array(
                    'i'=>$qData['d']['id'], 
                    'o'=>$qData['d']['o'],
                    's'=>$qData['d']['s'],
                )), array($qData['d']['uid']));
                return;
            }
            //新委托
            elseif($qData['t']=='new')
            {
                if(isset($alreadyDealId[$qData['d']['id']]))
                {
                    return 'already deal id : '.$qData['d']['id'];
                }
                else
                {
                    $trust_id = $qData['d']['id'];
                }              
            }
        }

        //bc计算默认20位小数
        bcscale(20);


        //没有委托，sleep
        if (empty($trust_id))
        {
            //重置延迟trust_id
            $this->delayId = 0;
            //sleep($sleep);
            return false; 
        }


        //如果这笔委托出现过异常，则延迟重试
        $wrongTrust = $this->getWrongTrust($coinName, $trust_id);
        if($wrongTrust && time()<$wrongTrust['dueTime'])
        {
            $this->delayId = $trust_id;
            return false;
        }
        


        //分页处理
        $page = 1;
        $pageSize = 200;//每页数量
        do
        {
            //开始处理这一笔委托
            $userMo->begin();
            $trust = $trustMo->lock()->where("id={$trust_id}")->fRow();
            $baseTrust = $trust;

            if($page==1)
            {
                if (('N' == $trust['isnew'] || $trust['numberover']==0))
                {
                    //添加到异常记录
                    $errorMsg = sprintf("委托数据异常, isnew:%s, numberover:%s", $trust['isnew'], $trust['numberover']);
                    $this->setWrongTrust($coinName, $trust['id'], $errorMsg);
                    Tool_Log::wlog($errorMsg, $logDir, true);
                    $userMo->back();
                    return;
                }

                $list = array();
                if (!in_array($trust['flag'], array('buy', 'sale')))
                {
                    Tool_Log::wlog(sprintf("委托数据异常, flag:%s ", $trust['flag']), $logDir, true);
                    $userMo->back();
                    return;
                }

                //更新委托列表
                $orderMo->ajaxcoinTrustList($coinName, [['p'=>$trust['price'], 'n'=>$trust['number'], 'f'=>$trust['flag']]]);
            }

            //查询符合交易价格的委托列表
            $list = $trustMo->getListByPrice($trust['price'], $coin, $trust['flag'] == 'buy' ? 'sale' : 'buy', $pageSize);
            $listCount = count($list);
           

            //没有符合价格的列表，更新委托并返回
            if (empty($list))
            {
                $trustMo->update(array('id' => $trust['id'], 'isnew' => 'N'));
                $userMo->commit();

                //更新用户委托
                $pushData = array(
                    'i'=>$trust['id'],
                    'n'=>$trust['number'],
                    'd'=>$trust['numberdeal'],
                    'o'=>$trust['numberover'],
                    'p'=>$trust['price'],
                    's'=>$trust['status'],
                    'f'=>$trust['flag'],
                    'cf'=>$trust['coin_from'],
                    'ct'=>$trust['coin_to'],
                    't'=>date('Y-m-d H:i:s', $trust['created']),
                );
                //t:type, a:action, c:content
                Tool_Push::one2nSend($coinName, array('t'=>'mytrust', 'a'=>'add', 'c'=>$pushData), array($trust['uid']));
                return;
            }

            //免手续费用户
            $noFeeUser = $freeTradeUserMo->field('uid')->where('end_time > '.time())->fList();
            if($noFeeUser)
            {
                $noFeeUser = array_column($noFeeUser, 'uid');
            }
            

            //主动用户
            $user = array('uid' => $trust['uid']);
            //订单
            $orders = array();
            //主动用户数据变化
            $activeUser = array(
                $coin['coin_from'] . '_over' => 0,
                $coin['coin_from'] . '_lock' => 0,
                $coin['coin_to'] . '_over'   => 0,
                $coin['coin_to'] . '_lock'   => 0,
            );

            //委托变动数据(用于推送)
            $trustListChange = $trustChangeData = array();

            foreach ($list as $v)
            {
                //加锁/数据获取失败
                if (!($v = $trustMo->lock()->where("id={$v['id']}")->fRow()) || $v['numberover'] < 1E-9)
                {
                    Tool_Log::wlog(sprintf("trust数据获取失败, id:%s ", $v['id']), $logDir, true);
                    continue;
                }

                //可成交最大值
                $min = min($trust['numberover'], $v['numberover']);

                //安全校验
                if ($min <= 0)
                {
                    $userMo->back();
                    Tool_Log::wlog(sprintf("可成交最大值<=0, trust_num:%s, v_num:%s ", $trust['numberover'], $v['numberover']), $logDir, true);
                    return false;
                }

                //更新被动委托
                $vNumberOver = Tool_Math::sub($v['numberover'], $min);
                $vNumberDeal = Tool_Math::sub($v['number'], $vNumberOver);
                if (!$trustMo->updateNumber($v['id'], $vNumberOver, $vNumberDeal))
                {
                    $userMo->back();
                    Tool_Log::wlog(sprintf("更新被动委托失败, sql:%s ", $trustMo->getLastSql()), $logDir, true);
                    return false;
                }

                //主动用户数据
                $trust['numberover'] = bcsub($trust['numberover'], $min);

                //卖
                $saleTotalPrice      = bcmul($min, $v['price']); //成交总价
                $aRealSaleTotalPrice = $saleTotalPrice;//主动
                $pRealSaleTotalPrice = $saleTotalPrice;//被动
                //扣除手续费后，实际成交总价
                if($coin['rate']>0)
                {
                    $realSaleTotalPrice = bcmul($saleTotalPrice, bcsub(1, $coin['rate'], 10));
                    in_array($trust['uid'], $noFeeUser) or $aRealSaleTotalPrice = $realSaleTotalPrice;
                    in_array($v['uid'], $noFeeUser) or $pRealSaleTotalPrice = $realSaleTotalPrice;
                }

                $pSaleFee = bcsub($saleTotalPrice, $pRealSaleTotalPrice);//被动手续费
                $aSaleFee = bcsub($saleTotalPrice, $aRealSaleTotalPrice);//主动手续费

                //买
                $realBuyTotal = $min;
                $min_real_v   = $min;
                if ($coin['rate_buy'] > 0)
                {
                    in_array($trust['uid'], $noFeeUser) or $realBuyTotal = bcmul($min, bcsub(1, $coin['rate_buy']));
                    in_array($v['uid'], $noFeeUser) or $min_real_v   = bcmul($min, bcsub(1, $coin['rate_buy']));
                }

                $pBuyFee = bcsub($min, $min_real_v);//被动手续费
                $aBuyFee = bcsub($min, $realBuyTotal);//主动手续费

                //被动用户数据变化
                $user_passive = array();
                if ($v['uid'] == $trust['uid'])
                {
                    $passiveUser = false;
                }
                else
                {
                    $passiveUser = $userMo->where("uid={$v['uid']}")->fRow();
                }

                if ('sale' == $trust['flag'])
                {
                    $orders[] = array('buy' => $v, 'sale' => $trust, 'price' => $v['price'], 'sale_fee'=>$aSaleFee, 'buy_fee'=>$pBuyFee, 'min' => $min, 'opt' => Order_CoinModel::OPT_TRADE);
                    $activeUser[$coin['coin_to'] . '_over'] = bcadd($activeUser[$coin['coin_to'] . '_over'], $aRealSaleTotalPrice);
                    $activeUser[$coin['coin_from'] . '_lock'] = bcsub($activeUser[$coin['coin_from'] . '_lock'], $min);
                    if ($passiveUser)
                    {
                        $user_passive[$coin['coin_to'] . '_lock']   = bcmul(-1, $saleTotalPrice);
                        $user_passive[$coin['coin_from'] . '_over'] = $min_real_v;
                    }
                    else
                    {
                        $activeUser[$coin['coin_to'] . '_lock'] = bcsub($activeUser[$coin['coin_to'] . '_lock'], $saleTotalPrice);
                        $activeUser[$coin['coin_from'] . '_over'] = bcadd($activeUser[$coin['coin_from'] . '_over'], $min_real_v);
                    }

                    //买家手续费订单
                    if ($coin['rate_buy'] > 0 && !in_array($v['uid'], $noFeeUser))
                    {
                        $orders[] = array('buy' => $v, 'sale' => $trust, 'price' => 1, 'min' => bcmul($min, $coin['rate_buy']), 'opt' => Order_CoinModel::OPT_FEE_BUY);
                    }

                    //卖家手续费订单
                    if ($coin['rate'] > 0 && !in_array($trust['uid'], $noFeeUser))
                    {
                        $orders[] = array('buy' => $v, 'sale' => $trust, 'price' => bcsub($saleTotalPrice, $aRealSaleTotalPrice), 'min' => 1, 'opt' => Order_CoinModel::OPT_FEE);
                    }
                }
                elseif ('buy' == $trust['flag'])
                {
                    $orders[] = array('buy' => $trust, 'sale' => $v, 'price' => $v['price'], 'sale_fee'=>$pSaleFee, 'buy_fee'=>$aBuyFee, 'min' => $min, 'opt' => Order_CoinModel::OPT_TRADE);
                    //差价还给用户
                    $priceDiff = bcsub(bcmul($min, $trust['price']), $saleTotalPrice);
                    //$priceDiff = $priceDiff < 0 ? 0 : $priceDiff;
                    $activeUser[$coin['coin_to'] . '_lock'] = bcadd($activeUser[$coin['coin_to'] . '_lock'], '-'.bcadd($saleTotalPrice, $priceDiff));
                    $activeUser[$coin['coin_to'] . '_over'] = bcadd($activeUser[$coin['coin_to'] . '_over'], $priceDiff);
                    $activeUser[$coin['coin_from'] . '_over'] = bcadd($activeUser[$coin['coin_from'] . '_over'], $realBuyTotal);
                    if ($passiveUser)
                    {
                        $user_passive[$coin['coin_to'] . '_over']   = $pRealSaleTotalPrice;
                        $user_passive[$coin['coin_from'] . '_lock'] = bcmul(-1, $min);
                    }
                    else
                    {
                        $activeUser[$coin['coin_to'] . '_over'] = bcadd($activeUser[$coin['coin_to'] . '_over'], $pRealSaleTotalPrice);
                        $activeUser[$coin['coin_from'] . '_lock'] = bcsub($activeUser[$coin['coin_from'] . '_lock'], $min);
                    }

                    //卖家手续费订单
                    if ($coin['rate'] > 0 && !in_array($v['uid'], $noFeeUser))
                    {
                        $orders[] = array('buy' => $trust, 'sale' => $v, 'price' => bcsub($saleTotalPrice, $pRealSaleTotalPrice), 'min' => 1, 'opt' => Order_CoinModel::OPT_FEE);
                    }

                    //买家手续费订单
                    if ($coin['rate_buy'] > 0 && !in_array($trust['uid'], $noFeeUser))
                    {
                        $orders[] = array('buy' => $trust, 'sale' => $v, 'price' => 1, 'min' => bcmul($min, $coin['rate_buy']), 'opt' => Order_CoinModel::OPT_FEE_BUY);
                    }
                }
                else
                {
                    continue;
                }

                //需要刷新用户缓存信息的uid
                $refreshUid = array();

                //更新被动用户
                if ($passiveUser)
                {
                    if (!$userMo->safeUpdateCli($passiveUser, $user_passive, true, $coinName))
                    {
                        $userMo->back();
                        $aError = $userMo->getError(2);
                        if($aError != $GLOBALS['MSG']['SYS_BUSY'])
                        {
                            //更新失败，撤销委托
                            $trustMo->update(array('id' => $v['id'], 'isnew' => 'N', 'numberover' => 0, 'status' => 3, 'updated' => time(), 'updateip' => '6.4.6.4'));

                            //刷新委托列表，推送
                            $orderMo->ajaxcoinTrustList($coinName, array(
                                array(
                                    'n'=>Tool_Math::mul($v['numberover'], -1),
                                    'f'=>$v['flag'],
                                    'p'=>$v['price'],          
                                )
                            ));       

                            Tool_Push::one2nSend($coinName, array('t'=>'mytrust', 'a'=>'update', 'c'=>array(
                                'i'=>$v['id'],
                                'o'=>0,
                                's'=>Trust_CoinModel::STATUS_CANCEL,
                            )), array($v['uid']));
                        }
                        $errorMsg = sprintf("更新被动用户失败, %s, 已撤销id:%s, sql:%s ", $v['id'], $aError, $userMo->getLastSql());
                        Tool_Log::wlog($errorMsg, $logDir, true);
                        Tool_Fnc::warning($errorMsg);
                        return;
                    }
                    $refreshUid[] = $passiveUser['uid'];
                }

                $trustChangeData[] = array(
                    'i'=>$v['id'], 
                    'o'=>$vNumberOver,
                    'd'=>$vNumberDeal,
                    'uid'=>$v['uid'], 
                    's'=>$vNumberOver==0?Trust_CoinModel::STATUS_ALL:Trust_CoinModel::STATUS_PART,
                );
                $trustListChange[] = array(
                    'n'=>Tool_Math::mul(-1, $min),
                    'f'=>$v['flag'],
                    'p'=>$v['price'],
                );

                //已全部交易完
                if ($trust['numberover']==0)
                {
                    break;
                }

            }
            //更新主动委托
            if (!$trustMo->updateNumber($trust['id'], $trust['numberover'], Tool_Math::sub($trust['number'], $trust['numberover'])))
            {
                $userMo->back();
                //添加到异常记录
                $errorMsg = sprintf("更新主动委托失败, sql:%s ", $trustMo->getLastSql());
                $this->setWrongTrust($coinName, $baseTrust['id'], $errorMsg);
                Tool_Log::wlog($errorMsg, $logDir, true);
                return;
            }

            //更新主动用户
            if (!$userMo->safeUpdateCli($user, $activeUser, true, $coinName))
            {
                $userMo->back();
                //更新失败，撤销委托(数据库错误除外)
                $aError = $userMo->getError(2);
                $aErrorDetails = $userMo->getError(0);
                $aSql   = $userMo->getLastSql();
                if($aError != $GLOBALS['MSG']['SYS_BUSY'])
                {
                    $trustMo->begin();
                    if(!$trustMo->update(array('id' => $baseTrust['id'], 'isnew' => 'N', 'numberover' => 0, 'status' => 3, 'updated' => time(), 'updateip' => '6.7.6.7')))
                    {
                        Tool_Log::wlog(sprintf("撤销委托失败, sql:%s ", $trustMo->getLastSql()), $logDir, true);
                        $trustMo->back();
                    }

                    if($baseTrust['flag']=='buy')
                    {
                        $tMoney = bcmul($baseTrust['numberover'], $baseTrust['price']);
                        $tUserData = array($baseTrust['coin_to'].'_lock' => -$tMoney, $baseTrust['coin_to'].'_over' => $tMoney);
                    } 
                    else 
                    {
                        $tUserData = array($baseTrust['coin_from'].'_lock' => -$baseTrust['numberover'], $baseTrust['coin_from'].'_over' => $baseTrust['numberover']);
                    }
                    if(TRUE !== $userMo->safeUpdateCli($user, $tUserData, true, $coinName))
                    {
                        Tool_Log::wlog(sprintf("撤销委托失败, %s, sql:%s ", $userMo->getError(2), $userMo->getLastSql()), $logDir, true);
                        $trustMo->back();
                    }
                    $trustMo->commit();

                    //刷新委托列表，推送
                    $orderMo->ajaxcoinTrustList($coinName, array(
                        array(
                            'n'=>Tool_Math::mul($baseTrust['numberover'], -1),
                            'f'=>$baseTrust['flag'],
                            'p'=>$baseTrust['price'],          
                        )
                    ));       

                    Tool_Push::one2nSend($coinName, array('t'=>'mytrust', 'a'=>'add', 'c'=>array(
                        'i'=>$baseTrust['id'],
                        'n'=>$baseTrust['number'],
                        'd'=>$baseTrust['numberdeal'],
                        'o'=>0,
                        'p'=>$baseTrust['price'],
                        's'=>Trust_CoinModel::STATUS_CANCEL,
                        'f'=>$baseTrust['flag'],
                        'cf'=>$baseTrust['coin_from'],
                        'ct'=>$baseTrust['coin_to'],
                        't'=>date('Y-m-d H:i:s', $baseTrust['created']),
                    )), array($baseTrust['uid']));
                    
                }

                $errorMsg = sprintf("更新主动用户失败, %s, sql:%s, details:%s ", $aError, $aSql, $aErrorDetails);
                //添加到异常记录
                //$this->setWrongTrust($coinName, $baseTrust['id'], $errorMsg);
                Tool_Fnc::warning($errorMsg);
                Tool_Log::wlog($errorMsg, $logDir, true);
                return false;
            }

            $refreshUid[] = $user['uid'];
            $pushData = array(
                    'i'=>$trust['id'],
                    'n'=>$trust['number'],
                    'd'=>Tool_Math::sub($trust['number'], $trust['numberover']),
                    'o'=>Tool_Math::eftnum($trust['numberover']), 
                    'p'=>$trust['price'],
                    's'=>$trust['numberover']==0?Trust_CoinModel::STATUS_ALL:Trust_CoinModel::STATUS_PART,
                    'f'=>$trust['flag'],
                    'cf'=>$trust['coin_from'],
                    'ct'=>$trust['coin_to'],
                    't'=>date('Y-m-d H:i:s', $trust['created']),
                );
            //t:type, a:action, c:content
            Tool_Push::one2nSend($coinName, array('t'=>'mytrust', 'a'=>'add', 'c'=>$pushData), array($trust['uid']));

            $trustListChange[] = array(
                'n'=>Tool_Math::sub($trust['numberover'], $baseTrust['numberover']),
                'f'=>$trust['flag'],
                'p'=>$trust['price'],
            );

            //插入订单
            if (!$this->insertOrder($orders, $coin))
            {
                $userMo->back();
                Tool_Log::wlog(sprintf("插入订单失败, orders:%s ", json_encode($this->error)), $logDir, true);
                return;
            }

            $refreshUid = array_unique($refreshUid);

            //更新用户余额
            //$addressMap = $this->model['Address']->getAddrMap($refreshUid, [$trust['coin_from'], $trust['coin_to']]);

            // if ($addressMap && !$this->updateUserCoin($orders, $coin, $addressMap))
            // {
            //     Tool_Log::wlog(sprintf("转币系统更新用户余额失败, error:%s", json_encode($this->error)), $logDir, true);
            //     //$userMo->back();
            //     //return;
            // }

            $userMo->commit();

            //这笔交易相关的用户
            foreach ($refreshUid as $v)
            {
                //刷新用户redis数据
                Tool_Session::mark($v);
            }

            //推送
            $trustChangeTo = [];
            $trustChangeMsg = [];
            $trustChangeList = [];
            foreach($trustChangeData as $v)
            {
                $trustChangeTo[] = $v['uid'];
                unset($v['uid']);
                $trustChangeMsg[] = array('t'=>'mytrust', 'a'=>'update', 'c'=>$v);
            }
            Tool_Push::n2nSend($coinName, $trustChangeMsg, $trustChangeTo);

            $time = date('H:i:s');
            $pushOrders = array();
            $pushOrdersBase = array_slice($orders, -50);//成交记录只推送最新50条;
            foreach($pushOrdersBase as $v)
            {
                if($v['opt'] == Order_CoinModel::OPT_TRADE)
                {
                    $pushOrders[] = array('p'=>$v['price'], 't'=>$time, 'n'=>$v['min'], 's'=>$v['buy']['id']>$v['sale']['id']?'buy':'sell');
                }
            }
 
            if($pushOrders)
            {
                Tool_Push::send($coinName, array('t'=>'orders', 'c'=>$pushOrders), array('group'=>'all'));
            }

            $refreshUid = array();
            $orderMo->ajaxcoinTrustList($coinName, $trustListChange);
            $orderMo->ajaxcoinOrder($coinName);

            $page++;
            //当单次成交占比没超过20%, 增加单页数量
            if(1-($trust['numberover']/$baseTrust['numberover'])<=0.2)
            {
                $pageSize = $listCount = 500;
            }

        } while($trust['numberover']>0 && $pageSize==$listCount);


        return true;
    }


    /**
     * 转币系统更新用户余额
     */
    public function updateUserCoin($datas, $coin, $addressMap)
    {
        if(!$datas || !$coin)
        {
            return false;
        }


        $reqData = array(
            'command'=>'trans_trade',
            'args'=>array()
        );

        foreach ($datas as $v)
        {
            if ($v['opt'] != Order_CoinModel::OPT_FEE && $v['opt'] != Order_CoinModel::OPT_FEE_BUY)
            {
                $reqData['args'][] = array(
                    'coin'=>$coin['coin_from'],
                    'num'=>Tool_Math::sub($v['min'], $v['buy_fee']),
                    'charge'=>$v['buy_fee'],
                    'from'=>$addressMap[$v['sale']['uid']][$coin['coin_from']],
                    'to'=>$addressMap[$v['buy']['uid']][$coin['coin_from']],
                );
                $reqData['args'][] = array(
                    'coin'=>$coin['coin_to'],
                    'num'=>Tool_Math::sub(Tool_Math::mul($v['price'], $v['min']), $v['sale_fee']),
                    'charge'=>$v['sale_fee'],
                    'from'=>$addressMap[$v['buy']['uid']][$coin['coin_to']],
                    'to'=>$addressMap[$v['sale']['uid']][$coin['coin_to']],
                );
            }  
        }
        
        $result = Api_Trans_Client::request($reqData);
        if($result && $result['code']==0)
        {
            return true;
        }
        $this->error = array('result'=>$result, 'data'=>$reqData);
        return false;
    }

    /**
     * 成交脚本order插入 
     */
    public function insertOrder($datas, $coin)
    {
        $orderMo = $this->model['Order_Coin']->designTable($coin['coin_from']);

        $time   = time();
        $values = array();
        $table  = 'order_' . $coin['coin_from'] . 'coin';
//        $sql    = "INSERT INTO $table (`price`, `number`, `buy_tid`, `buy_uid`, `sale_tid`, `sale_uid`, `opt`, `created`, coin_from, coin_to, buy_fee, sale_fee) VALUES ";

        $FeeData = array(
            "{$coin['coin_from']}_over" => 0,
            "{$coin['coin_to']}_over"   => 0,
        );

        $fees = "insert into log_finance(from_uid,to_uid,coin,number,type,bak_id,created,coin_from,coin_to,flag) values";
        foreach ($datas as $v)
        {

            if ($v['opt'] == Order_CoinModel::OPT_FEE)
            {
                $FeeData["{$coin['coin_to']}_over"] = bcadd($FeeData["{$coin['coin_to']}_over"], $v['price']);
                $fees .= "({$v['sale']['uid']}," . User_AdminModel::COIN_FEE . ",'{$coin['coin_to']}',{$v['price']},5,{$v['sale']['id']}," . time() . ",'{$v['sale']['coin_from']}','{$v['sale']['coin_to']}',0),";
                continue;
            }
            elseif ($v['opt'] == Order_CoinModel::OPT_FEE_BUY)
            {
                $FeeData["{$coin['coin_from']}_over"] = bcadd($FeeData["{$coin['coin_from']}_over"], $v['min']);
                $fees .= "({$v['buy']['uid']}," . User_AdminModel::COIN_FEE . ",'{$coin['coin_from']}',{$v['min']},5,{$v['buy']['id']}," . time() . ",'{$v['buy']['coin_from']}','{$v['buy']['coin_to']}',1),";
                continue;
            }
            empty($v['buy']) && $v['buy']   = array('id' => 0, 'uid' => 0);
            empty($v['sale']) && $v['sale'] = array('id' => 0, 'uid' => 0);
//            $values[] = "('{$v['price']}', '{$v['min']}', '{$v['buy']['id']}', '{$v['buy']['uid']}', '{$v['sale']['id']}', '{$v['sale']['uid']}', '{$v['opt']}', '$time', '{$coin['coin_from']}', '{$coin['coin_to']}', '{$v['buy_fee']}', '{$v['sale_fee']}')";
            $values = ['price'=>$v['price'],'number'=>$v['min'],'buy_tid'=>$v['buy']['id'],'buy_uid'=>$v['buy']['uid'],'sale_tid'=>$v['sale']['id'],'sale_uid'=>$v['sale']['uid'],'opt'=>$v['opt'],'created'=>$time,'coin_from'=>$coin['coin_from'],'coin_to'=>$coin['coin_to'],'buy_fee'=>$v['buy_fee'],'sale_fee'=>$v['sale_fee']];
        }

//        $sql = ['price'=>$v['price'],'number'=>$v['min']];
//        $sql    = "INSERT INTO $table (`price`, `number`, `buy_tid`, `buy_uid`, `sale_tid`, `sale_uid`, `opt`, `created`, coin_from, coin_to, buy_fee, sale_fee) VALUES ";

//        $sql .= implode(',', $values);
        if ($oid = $orderMo->insert($values))
        {

            //写入资产明细
//            $this->assetDetail($values,$oid);

            //插入分红手续费
            $this->feeBonus($values,$oid);

            if ($FeeData["{$coin['coin_from']}_over"] > 0 || $FeeData["{$coin['coin_to']}_over"] > 0)
            {
                $feeUser = array('uid' => User_AdminModel::COIN_FEE);
                if ($ooo = $this->model['User']->safeUpdateCli($feeUser, $FeeData, true, true))
                {
                    $fees = rtrim($fees, ',');
                    $this->model['User']->exec($fees);
                }
                else
                {
                    $this->error = $this->model['User']->getError();
                    return false;
                }
            }
            return true;
        }
        $this->error = $orderMo->getError();
        return false;
    }

    //插入资产明细
    public function assetDetail($values,$oid){

        $from = $this->model['User']->where("uid={$values['buy_uid']}")->fRow();
        $to = $this->model['User']->where("uid={$values['sale_uid']}")->fRow();

        $this->model['AssetDetail']->tradeBuy($values,$oid,$from,$to);
    }

    /*
     * 插入推荐交易手续费
     */
    public function feeBonus($values,$oid){

        //买家手续费
        if($values['buy_fee']>0) $this->feeBonusData($values,$oid,'buy');

        //卖家手续费
        if($values['sale_fee']>0) $this->feeBonusData($values,$oid,'sale');
    }

    public function feeBonusData($values,$oid,$type){

        $feeBonusMo = $this->model['FeeBonus'];
        $userMo = $this->model['User'];

        $uid = $type=='buy'?$values['buy_uid']:$values['sale_uid'];
        $opt = $type=='buy'?1:2;
        if($uid==33757) return false;//机器人账号排除
        $from_uid = $userMo->where("uid=$uid")->fOne('from_uid');
        if(!$from_uid || $from_uid=='') return false;

        $ofee = $type=='buy'?$values['buy_fee']:$values['sale_fee'];
        if($type=='buy' && $values['opt']==1){
            $fee = Tool_Math::mul($ofee*0.3,$values['price'],8);
        }else{
            $fee = Tool_Math::mul($ofee,0.3,8);
        }


        $buy_data = [
            'uid' => $from_uid,
            'origin_uid' => $uid,
            'price' => $values['price'],
            'number' => $values['number'],
            'opt' => $opt,
            'coin_from' => $values['coin_from'],
            'coin_to' => $values['coin_to'],
            'oid' => $oid,
            'fee' => $fee,
            'created' => $values['created']
        ];
        if($fid = $feeBonusMo->insert($buy_data)){
            $userMo->exec("update user set cnyx_over=cnyx_over+{$fee} where uid={$from_uid}");

            //写入交易分红明细
//            $from_user = $userMo->lock()->fRow($from_uid);
//            $this->model['AssetDetail']->feeBonus($type,$from_user,$fid,$fee);
        }
    }



    /**
     * 添加异常标记
     */
    private function setWrongTrust($coin, $id, $error)
    {
        //设为延迟id
        $this->delayId = $id;

        $times = 1;
        $oldRecord = $this->getWrongTrust($coin, $id);
        if($oldRecord)
        {
            $times = intval($oldRecord['times']) + 1; 
        }

        $dueTime = time();
        //重试间隔
        $dueTime += $times<2?3:($times<5?10:30);
        Cache_Redis::instance('quote')->hSet('wrong_trust_'.$coin, $id, json_encode(array('error'=>$error, 'times'=>$times, 'dueTime'=>$dueTime, 'date'=>date('Ymd H:i:s', $dueTime))));
    }

    /**
     * 获取异常标记
     */
    private function getWrongTrust($coin, $id)
    {
        $data = Cache_Redis::instance('quote')->hGet('wrong_trust_'.$coin, $id);
        return $data?json_decode($data, true):'';
    }

}
