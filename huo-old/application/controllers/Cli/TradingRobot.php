<?php
class Cli_TradingrobotController extends Ctrl_Cli{

    // 自动交易用户
    private $user = array(
        "dob"=>array(
            'uid'=>13264259
        ),
        "btc"=>array(
            'uid'=>13231202
        ),
        "eth"=>array(
            'uid'=>13232743
        ),
    );

    //要刷的交易对
    private $coinList=array(
        1=>array(
            "name"=>"mcc_btc",
        ),
        2=>array(
            "name"=>"cash_btc",
        ),
        3=>array(
            "name"=>"ethms_btc",
        ),
        4=>array(
            "name"=>"dcon_dob",
        ),
        5=>array(
            "name"=>"ethms_dob",
        ),
        6=>array(
            "name"=>"dcon_eth",
        ),
        7=>array(
            "name"=>"ethms_eth",
        ),
        8=>array(
            "name"=>"kkc_eth",
        ),
        9=>array(
            "name"=>"mcc_eth",
        ),
        10=>array(
            "name"=>"dst_eth",
        ),
        // 11=>array(
        //     "name"=>"ltc_dob",
        // ),
        // 11=>array(
        //     "name"=>"ait_eth",
        // )
     
    );

    // 小数量单笔下单范围
    private $trustNumArr = array(
        "mcc_btc"=>array(
            'min'=>50,
            'max'=>1000
        ),
        "cash_btc"=>array(
            'min'=>10000,
            'max'=>12000
        ),
        "ethms_btc"=>array(
            'min'=>100,
            'max'=>500
        ),
        "dcon_dob"=>array(
            'min'=>100,
            'max'=>500
        ),
        "ethms_dob"=>array(
            'min'=>100,
            'max'=>500
        ),
        "dcon_eth"=>array(
            'min'=>100,
            'max'=>500
        ),
        "ethms_eth"=>array(
            'min'=>100,
            'max'=>500
        ),
        "kkc_eth"=>array(
            'min'=>100,
            'max'=>500
        ),
        "mcc_eth"=>array(
            'min'=>100,
            'max'=>500
        ),
        "dst_eth"=>array(
            'min'=>100,
            'max'=>500
        ),
        "ltc_dob"=>array(
            'min'=>0.5,
            'max'=>5
        ),
        // "ait_eth"=>array(
        //     'min'=>10,
        //     'max'=>50
        // ),
    );

    // 大数量单笔下单范围
    private $trustNumArr_max = array(
        "mcc_btc"=>array(
            'min'=>1000,
            'max'=>10000
        ),
        "cash_btc"=>array(
            'min'=>12000,
            'max'=>15000
        ),
        "ethms_btc"=>array(
            'min'=>5000,
            'max'=>10000
        ),
        "dcon_dob"=>array(
            'min'=>500,
            'max'=>1500
        ),
        "ethms_dob"=>array(
            'min'=>1000,
            'max'=>10000
        ),
        "dcon_eth"=>array(
            'min'=>500,
            'max'=>1500
        ),
        "ethms_eth"=>array(
            'min'=>5000,
            'max'=>10000
        ),
        "kkc_eth"=>array(
            'min'=>5000,
            'max'=>10000
        ),
        "mcc_eth"=>array(
            'min'=>1000,
            'max'=>10000
        ),
        "dst_eth"=>array(
            'min'=>1000,
            'max'=>10000
        ),
        "ltc_dob"=>array(
            'min'=>5,
            'max'=>10
        ),
        // "ait_eth"=>array(
        //     'min'=>1000,
        //     'max'=>10000
        // ),
    );

    // 数量小数点位数
    private $numberPoint = 4;

    // 单价小数点位数
    private $pricePoint = 8;

    // 低买高卖比例
    private $rate = 0.0001;

    // 刷单时间
    private $time_limit = 30;

    // 保留买卖单委托条数
    private $trustCount = 5;


    // 日志文件
    private $logDir="./robot/";

    /*
     * 需要使用的model
     */
    private $user_mo;
    private $coin_pair_mo;
    private $coin_float_mo;
    private $trust_coin_mo;
    private $order_coin_mo;
    private $mo = array('User', 'Coin_Pair', 'Coin_Float', 'Order_Coin', 'Trust_Coin');



    //启动标识文件
    private $processRunning = 'robotRunning.lock';


    /**
     * 程序入口（多进程）
     */
    public function runAction()
    {
        //如果进程正在运行，需要删掉‘启动标识文件’
        $this->checkProcessRunningLock();
        $redis = Cache_Redis::instance('token');
        foreach($this->user as $coin)
        {
            foreach ($coin as $uid) 
            {
                $redis->set($uid, '1');
            }
        }
        $coinList   = $this->coinList;
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
        $models = array('User', 'Coin_Pair', 'Coin_Float', 'Order_Coin', 'Trust_Coin');
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

            //Tool_Log::wlog(sprintf("child process pid:%d, name:%s ", $myPid, $namePidMap[$myPid]), $this->logDir, true, '[m-d H:i:s]');
            //开始工作
            $this->work($this->process[$namePidMap[$myPid]]);
        }
        else
        {
            //这里是父进程，监控所有子进程状态
            pcntl_signal(SIGTERM, array(&$this,"sigHandler"), false);
            //Tool_Log::wlog(sprintf("father process pid:%d ", posix_getpid()), $this->logDir, true, '[m-d H:i:s]');
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
                //Tool_Log::wlog(sprintf("%s die; pid: %d ", $namePidMap[$pid], $pid), $this->logDir, true, '[m-d H:i:s]');
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
        $title = sprintf('├─Cli_tradingrobot───%s', $processName);
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
            //Tool_Log::wlog(sprintf("created %s; pid: %d ", $processName, posix_getpid()), $this->logDir, true, '[m-d H:i:s]');
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
        Tool_Log::wlog('kill all process'. $msg, $this->logDir, true, '[m-d H:i:s]');
        exit(0);
    }


    /**
     * 交易处理
     */
    private function work($coin)
    {
        //日志分币存放
        //$this->logDir .= '/'.$coin['name'];
        $this->initModels($coin['name']);

        //记录当前的mysql thread_id，便于出现问题后定位
        $threadId = $this->model['User']->query('SELECT CONNECTION_ID() thread_id');
        Cache_Redis::instance('quote')->hSet('current_trust_mysql_tid', $coin['name'], $threadId[0]['thread_id']);

        $arr=explode("_",$coin['name']);
        $this->cancelAllAction($arr);
        //重复劳动
        while (true)
        {
            $time = time();
            $this->dotrust(strtolower($coin['name']));
            //处理信号
            pcntl_signal_dispatch();

            sleep(rand($this->time_limit,60));
        }
    }




    public function cancelAllAction($arr)
    {
        $this->initMo();
        $tMO= $this->trust_coin_mo;
        $tMO->designtable($arr[0]); //chen
        //启动前要先撤销掉该机器人的委托单
        $sale_cancel = $tMO->field('id')->where("coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and uid = {$this->user[$arr[1]]['uid']}  and numberover > 0 and status < 2 and 1=1  ")->order('price desc')->fList();
        if($sale_cancel) {
            foreach ($sale_cancel as $val) {
                $this->adminCancel($val['id'],$arr);
                sleep(5);
            }
        }
        return true;

    }

    /**
     * model初始化
     */
    private function initMo($refresh=false){
        foreach($this->mo as $mo){
            if($refresh === false && $this->{strtolower($mo).'_mo'}){
                continue;
            }
            $model = $mo.'Model';
            $this->{strtolower($mo).'_mo'} = new $model;
            
        }
    }

    /**
     * 挂单
     */
    private function dotrust($coin){

        $logDir=$this->logDir.$coin."/".date("Ymd");
        //$this->initMo();
        $arr=explode('_',$coin);
        //获取该币种的最大最小交易数量
        //$numberLimit=$this->coin_pair_mo->field("min_trade,max_trade")->where("name='{$coin}' and status=1")->fRow();
        //if(!$numberLimit)
        //{
        //    exit("该交易对暂未开放");
        //}
        //$this->trustNumArr['min']=$numberLimit['min_trade'];
        //$this->trustNumArr['max']=$numberLimit['min_trade']+5;
        //$this->trustNumArr_max['min']=$numberLimit['max_trade']-10;
        //$this->trustNumArr_max['max']=$numberLimit['max_trade'];
        //show($numberLimit);
        // 判断账户内是否有足够的人民币，没有则撤销最高价或者最低价的委托单
        $user_cny_num = $this->user_mo->where("uid = {$this->user[$arr[1]]['uid']}")->fOne("{$arr[0]}_over");
        $tMO= $this->trust_coin_mo;
        $tMO->designtable($arr[0]); //chen
        $tMO1= $this->order_coin_mo;
        $tMO1->designtable($arr[0]);    //chen
         if( $user_cny_num < 5 ){
             $sumArr = json_decode(Cache_Redis::instance("quote")->get("{$coin}_sum"), true);
            
             foreach ($sumArr as $k=>$v) {
                 $order = ( $k == 'buy' ) ? ' asc' : ' desc' ;
                 if( count($v) > 5 ){
                     // 撤单
                     $trust_arr = $tMO->where("coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and uid = {$this->user[$arr[1]]['uid']} and flag = '{$k}' and numberover > 0 and status < 2")->order("price {$order}")->limit(5)->fList();
                     foreach ($trust_arr as $val) {
                         $this->adminCancel($val['id'],$arr);
                     }
                 }
             }
         }
         // 判断账户内是否有足够的货币数量，没有则撤销最高价的委托单
         $user_btc_num = $this->user_mo->where("uid = {$this->user[$arr[1]]['uid']}")->fOne("{$arr[1]}_over");
         if( $user_btc_num < 3 ){
             $sale_cancel = $tMO->field('id')->where("coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and uid = {$this->user[$arr[1]]['uid']} and flag = 'sale' and numberover > 0 and status < 2")->order('price desc')->limit(1)->fRow();
             $this->adminCancel( $sale_cancel['id'],$arr);
         }
        $this->order_coin_mo;
        // 判断平台规定时间内是否有成交单
        $time = time();
        $last_minute_order = $tMO1->where("coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and created > ({$time}-{$this->time_limit})")->fRow();

        // 如果有成交单，
        if( $last_minute_order ){
            // 判断规定时间内是否有机器人部分成交的成交委托单，有则撤销
             $robot_order_trust = $tMO->field('id')->where("trust_type = 1 and coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and uid = {$this->user[$arr[1]]['uid']} and numberover > 0 and status < 2 and created > {$time}-{$this->time_limit}")->fList();

             if( $robot_order_trust ){
                 foreach ($robot_order_trust as $key => $val) {
                     $this->adminCancel( $val['id'],$arr);
                 }
             }

        }
        // 否则如果没有成交单，则委托促成交易
        else{
            // 自动交易用户信息
            $robotUserInfo = UserModel::getById($this->user[$arr[1]]['uid']);

            // 获取市场深度行情
            $market = $this->getMarket($arr);
            if( !$market ){
                // exit("price error");
                Tool_Log::wlog("获取市场价格失败", $logDir, true);
                return;
            }
            // 市场买一价和卖一价
            $marketPrice = array('buy_one'=>$market['bid'], 'sale_one'=>$market['ask']);
            // 在市场买一价和卖一价之间随机
            $price = $this->getRangePrice($marketPrice['buy_one'], $marketPrice['sale_one']);

            if( !$price ){
                Tool_Log::wlog("随机价格失败", $logDir, true);
                return;
            }
            // 组装委托数据
            $trust_number1 = $this->getRandomNum($arr);
            $trust_number2 = $this->getRandomNum($arr);

            $trust_datas = array(
                0 => array('type'=>'in', 'price'=>$price, 'number'=>$trust_number1, 'coin_from' => $arr[0],'coin_to'=>$arr[1] ,'trust_type'=>1),
                1 => array('type'=>'out', 'price'=>$price, 'number'=>$trust_number2, 'coin_from'=>$arr[0],'coin_to'=>$arr[1], 'trust_type'=>1),
                2 => array('type'=>'out', 'price'=>round($price*(1+$this->rate), $this->pricePoint+1), 'number'=>$trust_number1, 'coin_from' => $arr[0],'coin_to'=>$arr[1], 'trust_type'=>0),
                3 => array('type'=>'in', 'price'=>round($price*(1-$this->rate), $this->pricePoint+1), 'number'=>$trust_number2, 'coin_from' => $arr[0],'coin_to'=>$arr[1], 'trust_type'=>0)
            );
            //print_r($trust_datas);//die;
            //下委托单前还需判断该机器人委托的价格是否在当前用户买一价和卖一价之间
            $robot = Cache_Redis::instance('token')->keys('*');
            if(!$robot)
            {
                $res=0;
            }
            else
            {
                foreach($robot as $k => $v) {
                    if(!is_numeric($v))
                        unset($robot[$k]);
                    }
                $res=implode(",",$robot);
            }

            //再查出用户的买一价和卖一价
            //用户的买一价
            $dobBuyOne=$tMO->field("price")->where("uid not in ({$res}) and flag='buy' and coin_to='{$arr[1]}' and status<2")->order("price desc")->fRow();
            //用户的卖一价
            $dobSaleOne=$tMO->field("price")->where("uid not in ({$res}) and flag='sale' and coin_to='{$arr[1]}' and status<2")->order("price asc")->fRow();
            $dobBuyOne=$dobBuyOne['price'];
            $dobSaleOne=$dobSaleOne['price'];
            if($price<=$dobBuyOne || $price>=$dobSaleOne) {
                //exit("Commissioned by the current price is not buy a price and sold for a price");
                Tool_Log::wlog("随机价格不在当前用户的买一卖一价之间, price:$price", $logDir, true);
                return ;
            }
            // 下委托单
            foreach ($trust_datas as $v) {
                $this->trustbtc($v, $robotUserInfo,$arr);
            }
            sleep(rand(2,3));
            $saleCount=$tMO->field("id")->where("uid={$this->user[$arr[1]]['uid']} and flag='sale' and coin_to='{$arr[1]}' and status<2")->count();
            $buyCount=$tMO->field("id")->where("uid={$this->user[$arr[1]]['uid']} and flag='buy' and coin_to='{$arr[1]}' and status<2")->count();

            // 机器人的委托只保留买卖3条
            if( $buyCount > $this->trustCount ){
                $buyLimit=$tMO->query("SELECT id FROM  `trust_{$arr[0]}coin` WHERE coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and uid = {$this->user[$arr[1]]['uid']} and flag = 'buy' and status < 2 ORDER BY price desc limit 0,".($buyCount-$this->trustCount));
                foreach ($buyLimit as $key=>$val){
                    $this->adminCancel( $val['id'],$arr );
                }
            }
            if( $saleCount > $this->trustCount ){
                $saleLimit=$tMO->query("SELECT id FROM  `trust_{$arr[0]}coin` WHERE coin_from = '{$arr[0]}' and coin_to='{$arr[1]}' and uid = {$this->user[$arr[1]]['uid']} and flag = 'sale'  and status < 
2 ORDER BY price asc limit 0,".($saleCount-$this->trustCount));
                foreach ($saleLimit as $key=>$val){
                    $this->adminCancel( $val['id'],$arr );
                }
            }

        }

    }

    /**
     * 获取市场深度行情
     */
    private function getMarket($arr){

        //获取外部平台买一价和卖一价
        //如果是dob交易区的币种,则查看该币种兑换usd的价格
        if(strtolower($arr[1])=='dob')
        {
            $url="http://47.52.210.21/?coin=".$arr[0]."usd";
        }
        else
        {
            $url="http://47.52.210.21/?coin=".$arr[0].$arr[1];
        }
        $marketList=Tool_Fnc::callInterfaceCommon($url,"GET",'','');
        $marketList = substr($marketList, strpos($marketList, "{"));//去掉json前面的东东
        $marketArr = json_decode($marketList, true);
        if(strtolower($arr[1])=='dob')
        {
            //获取人民币兑美元汇率
            $usdRate = file_get_contents('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b');
            $usdRate = iconv("GBK", "UTF-8//IGNORE", $usdRate);
            $usdRate = preg_replace('/.+?美元.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate)/100;
            $marketArr['bid']=round(bcmul($marketArr['bid'],$usdRate,8),$this->pricePoint);
            $marketArr['ask']=round(bcmul($marketArr['ask'],$usdRate,8),$this->pricePoint);
        }
        else
        {
            $marketArr['bid']=round($marketArr['bid'],$this->pricePoint);
            $marketArr['ask']=round($marketArr['ask'],$this->pricePoint);
        }

        //获取多比平台用户的买一价和卖一价  不包含机器人 需查询数据库
        //首先获取机器人的uid
        $robot = Cache_Redis::instance('token')->keys('*');
        if(!$robot)
        {
            $res=0;
        }
        else
        {
            foreach($robot as $k => $v) {
            if(!is_numeric($v))
                unset($robot[$k]);
            }
            $res=implode(",",$robot);
        }

       
        //再查出用户的买一价和卖一价
        $tMO= $this->trust_coin_mo;
        $tMO->designtable($arr[0]); //chen
        //用户的买一价
        $dobBuyOne=$tMO->field("price")->where("uid not in ({$res}) and flag='buy' and coin_to='{$arr[1]}' and status<2")->order("price desc")->fRow();
        //用户的卖一价
        $dobSaleOne=$tMO->field("price")->where("uid not in ({$res}) and flag='sale' and coin_to='{$arr[1]}' and status<2")->order("price asc")->fRow();
        $dobBuyOne=$dobBuyOne['price'];
        $dobSaleOne=$dobSaleOne['price'];
        if($marketArr)
        {
            if($dobBuyOne)
            {
                //如果多币买一价大于或等于外部平台的卖一价 并且 多币平台的卖一价不存在时
                if(bccomp($dobBuyOne,$marketArr['ask'],$this->pricePoint)>=0 && !$dobSaleOne)
                {
                    return false;
                }
            }
            else
            {
                $dobBuyOne=$marketArr['bid'];
            }

            if($dobSaleOne)
            {
                //如果多币卖一价小于或等于外部平台的买一价 并且 多币平台的买一价不存在时
                if(bccomp($dobSaleOne,$marketArr['bid'],$this->pricePoint)<=0 && !$dobBuyOne)
                {
                    return false;
                }
            }
            else
            {
                $dobSaleOne=$marketArr['ask'];
            }

        }
        else
        {
            if(!$dobBuyOne || !$dobSaleOne)
            {
                return false;
            }
        }
        //判断获取的外部获取的买一价是否在多比的买一价卖一价之间
        if(bccomp($marketArr['bid'],$dobBuyOne,$this->pricePoint)<0 || bccomp($marketArr['bid'],$dobSaleOne,$this->pricePoint)>=0)
        {
            //不在则取多比的买一价
            $marketArr['bid']=$dobBuyOne;
        }
        //判断获取的外部获取的卖一价是否在多比的买一价卖一价之间
        if(bccomp($marketArr['ask'],$dobBuyOne,$this->pricePoint)<=0 || bccomp($marketArr['ask'],$dobSaleOne,$this->pricePoint)>0)
        {
            //不在则取多比的卖一价
            $marketArr['ask']=$dobSaleOne;
        }
        //如果获取的买一价和卖一价相等时，卖一价则取多比的卖一价
        if(bccomp($marketArr['ask'],$marketArr['bid'],$this->pricePoint)==0)
        {
            $marketArr['ask']=$dobSaleOne;
        }

        if($marketArr['ask']<=0 || $marketArr['bid']<=0)
        {
            return false;
        }

        return $marketArr;

    }

    /**
     * 买一价和卖一价直接随机价格
     */
    private function getRangePrice($min, $max){
        //$price = round($min + mt_rand() / mt_getrandmax() * ($max - $min), $this->pricePoint);
        $price = Tool_Math::format(rand($min*100000000, $max*100000000)/100000000, $this->pricePoint);
        return $price;
    }

    /**
     * 随机生成委托数量
     */
    private function getRandomNum($arr){
        $num = rand(0,100);
        if( $num >= 90 ){
            return round($this->trustNumArr_max[$arr[0]."_".$arr[1]]['min'] + mt_rand() / mt_getrandmax() * ($this->trustNumArr_max[$arr[0]."_".$arr[1]]['max'] - $this->trustNumArr_max[$arr[0]."_".$arr[1]]['min']), $this->numberPoint);
        }else{
            return round($this->trustNumArr[$arr[0]."_".$arr[1]]['min'] + mt_rand() / mt_getrandmax() * ($this->trustNumArr[$arr[0]."_".$arr[1]]['max'] - $this->trustNumArr[$arr[0]."_".$arr[1]]['min']), $this->numberPoint);
        }

    }

    /**
     * 写入委托
     */
    private function trustbtc($pData, &$pUser,$arr){
        // 保存DB
        $tMO= $this->trust_coin_mo;
        $tMO->designtable($arr[0]); //chen
       // $tMO->btc($pData, $pUser);

        $tMO->begin();
            # 买入
            if($pData['type']=='in'){
                $totalPrice = Tool_Math::mul($pData['price'], $pData['number']);
                $coinData = array($pData['coin_to'].'_lock' => $totalPrice, $pData['coin_to'].'_over' => Tool_Math::mul('-1', $totalPrice));
                $pData['type'] = 'buy';
            }
            # 卖出
            else {
                $number = $pData['number'];
                $coinData = array($pData['coin_from'].'_lock' => $number, $pData['coin_from'].'_over' => Tool_Math::mul('-1', $number));
                $pData['type'] = 'sale';
            }
            # 写入
            $userMo = UserModel::getInstance();
            if(!$userMo->safeUpdate($pUser, $coinData, false, $pData['coin_from'].'_'.$pData['coin_to'])){
                $tMO->back();
                Tool_Fnc::warning($pData['coin_from'].'_'.$pData['coin_to'].'機器人賬戶餘額不足'."; price:{$pData['price']}, number:{$number}");
                return $tMO->setError($userMo->error[2]);
            }
            # 写入委托
            if($pData['number']>0 && !$tId = $tMO->insert(array(
                'uid'=>$pUser['uid'],
                'price'=>$pData['price'],
                'number'=>$pData['number'],
                'numberover'=>$pData['number'],
                'flag'=>$pData['type'],
                'status'=>0,
                'coin_from'=>$pData['coin_from'],
                'coin_to'=>$pData['coin_to'],
                'created'=>time(),
                'createip'=>Tool_Fnc::realip()
            ))){
                $tMO->back();
                return $tMO->setError($GLOBALS['MSG']['SYS_ERROR']);
            }


            //写入队列
            $r = $tMO->pushInQueue($pData['coin_from'].'_'.$pData['coin_to'], array(
                'id'=>$tId,
            ), 'new');

            if(!$r)
            {
                $tMO->back();
                return $tMO->setError($GLOBALS['MSG']['SYS_ERROR'].'[2]');
            }
            # 提交数据
        $tMO->commit();

        return;
    }

    /**
     * 撤销
     */
    private function adminCancel($pId,$arr){
        # 开始事务

        $tMO= $this->trust_coin_mo;
        $tMO->designtable($arr[0]); //chen
        // 自动交易用户信息
        $robotUserInfo = UserModel::getById($this->user[$arr[1]]['uid']);
        $result = $tMO->cancel($pId, $robotUserInfo, 1);
        return $result;
    }
}
