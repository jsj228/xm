<?php
class App_TradeController extends App_BaseController
{
    //启用 SESSION
    protected $_auth = 1;

    protected $defaultMarket = 'mcc_dob';

    /**
     * 我的委托 
     */
    public function getMyTrustAction($return=false)
    {
        $this->_islogin();

        $page = $_GET['page']?:1;
        $market = $_GET['market']?:$this->defaultMarket;
        $type = $_GET['type']?:1; //1 全部状态， 2、当前委托， 3、历史委托
        $flag = $_GET['flag']?:1; //1 全部， 2、买， 3、卖

        $pageSize = 20;
        list($coinFrom, $coinTo) = explode('_', $market);

        if (!$coinFrom || !$coinTo)
        {
            $this->response($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $trustCoinMo = Trust_CoinModel::getInstance()->designTable($coinFrom);
        $where       = array(
            'uid'       => $this->mCurUser['uid'],
            'coin_from' => $coinFrom,
            'coin_to' => $coinTo,
        );

        switch ($type) {
            case 2:
                $where['status'] = array('<', 2);
                break;
            case 3:
                $where['status'] = 2;
                break;
            
        }

        switch ($flag) {
            case 2:
                $where['flag'] = 'buy';
                break;
            case 3:
                $where['flag'] = 'sale';
                break;
            
        }

        $list = $trustCoinMo->field('coin_from,coin_to,flag,id,number,numberdeal,numberover,price,created,status')->where($where)->page($page, $pageSize)->order('created desc')->fList();
        $count = $trustCoinMo->where($where)->count();
        
        $data = array('list'=>array(), 'totalPage'=>ceil($count/$pageSize));
        foreach ($list as $v)
        {
            $data['list'][] = array(
                'i'=>$v['id'],
                'n'=>$v['number'],
                'd'=>$v['numberdeal'],
                'o'=>$v['numberover'],
                'p'=>$v['price'],
                's'=>$v['status'],
                'f'=>$v['flag'],
                'cf'=>$v['coin_from'],
                'ct'=>$v['coin_to'],
                't'=>$v['created'],
            );
        }

        if($return)
        {
            return $data;
        }
        $this->response('', 1, $data);

    }


    /**
     * 市场挂单
     */
    public function getMarketTrustAction($return=false)
    {
        if (!$_GET['market'])
        {
            $_GET['market'] = $this->defaultMarket;
        }

        $market  = $_GET['market'];
        $redis = Cache_Redis::instance('quote');
        $data  = $redis->get($market . '_sum');

        if ($data)
        {
            $data = json_decode($data, true);
            if($data)
            {
                $data['buy'] = array_values($data['buy']);
                $data['sale'] = array_values($data['sale']);
            }
        }

        $data or $data = array('buy'=>[], 'sale'=>[]);


        if($return)
        {
            return $data;
        }
        $this->response('', 1, $data);
    }






    /**
     * 市场成交
     */
    public function getMarketOrdersAction($return=false)
    {
        $market  = $_GET['market']?:$this->defaultMarket;
        $redis = Cache_Redis::instance('quote');
        $data  = $redis->get($market . '_order');
        if ($data)
        {
            $data = json_decode($data, true);
        }
        if(!isset($data['price']))
        {
            $data['price'] = '';
        }
        if($return)
        {
            return $data;
        }
        $this->response('', 1, $data);
    }

    /**
     * 委托下单
     */
    public function setTrustAction()
    {
        //会话验证
        $this->auth();

        list($_POST['coin_from'], $_POST['coin_to']) = explode('_', $_POST['market']);
        //下单
        $tradeLogic = new TradeLogic;
        $tradeLogic->order($_POST, $this->mCurUser);

        $this->response($GLOBALS['MSG']['ORDER_SUCCESS'], 1, $this->getUserCoinInfo($_POST['coin_from'], $_POST['coin_to']));

    }



    private function getUserCoinInfo($coinFrom, $coinTo)
	{
		return array(
        	$coinTo . '_over'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinTo . '_over']), '.'),
        	$coinTo . '_lock'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinTo . '_lock']), '.'),
        	$coinFrom.'_over'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinFrom.'_over']), '.'),
        	$coinFrom.'_lock'=>trim(preg_replace('/(\.\d*?)0+$/', '$1', $this->mCurUser[$coinFrom.'_lock']), '.')
        );
	}

	/**
     * 委托撤销
     */
	public function trustcancelAction()
	{
		$this->auth();

        list($_POST['coin_from'], $_POST['coin_to']) = explode('_', $_POST['market']);
		if(!$_POST['id'] || !$_POST['coin_from'] || !$_POST['coin_to'])
		{
			$this->response($GLOBALS['MSG']['PARAM_ERROR'], 2);
		}

		$id = intval($_POST['id']);
		$coinFrom = $_POST['coin_from'];
        $tradeLogic = new TradeLogic;
        $tradeLogic->cancel($id, $coinFrom, $this->mCurUser);
        $this->response($GLOBALS['MSG']['SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));

	}


    /**
     * 交易密码校验
     */
    public function pwdtradeAuthAction()
    {
        $this->auth();
        $pwdtrade = urldecode($_POST['pwdtrade']);
        $tradeLogic = new TradeLogic;
        $tradeLogic->verifiyTradePwd($pwdtrade, $this->mCurUser);
        $this->response('', 1);
    }



    /**
     * 交易中心合并接口
     */
    public function tradeDataAction()
    {
        //我的委托
        if($this->mCurUser)
        {
            $data['mytrust'] = $this->getMyTrustAction(true);
        }
        //市场挂单
        $data['trust'] = $this->getTrustAction(true);
        //市场成交
        $data['orders'] = $this->getOrdersAction(true);

        $this->response('', 1, $data);
    }


    //获取服务器时间戳
    public function gettsAction()
    {
        $this->ajax('', 1, time());
    }


    /**
     * 交易規則
     */
    public function rulesAction()
    {
        $market = $_GET['market']?:$this->defaultMarket; 
        if(!$market || !preg_match('/^[a-z]+_[a-z]+$/i', $market))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }
        $where = array('status'=>1, 'name'=>$market);
        $data = Coin_PairModel::getInstance()->field('name,price_float,number_float,min_trade,max_trade,rate,rate_buy')->where($where)->fRow();
        if(!$data)
        {
            $this->ajax('Market ' . $GLOBALS['MSG']['NOT_EXISTED']);
        }
        $returnData = array(
            'market'=>$data['name'],
            'price_decimal_limit'=>$data['price_float'],
            'number_decimal_limit'=>$data['number_float'],
            'min'=>$data['min_trade'],
            'max'=>$data['max_trade'],
            'buy_rate'=>$data['rate_buy'],
            'sell_rate'=>$data['rate'],
        );
        $this->ajax('', 1, $returnData);
    }


    /**
     * 交易行情首页接口
     */
    public function indexAction()
    {
        $market = $_GET['market']?:$this->defaultMarket; 
        if(!$market || !preg_match('/^[a-z]+_[a-z]+$/i', $market))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }
        $where = array('status'=>1, 'name'=>$market);
        $data = Coin_PairModel::getInstance()->field('name,price_float,number_float,min_trade,max_trade,rate,rate_buy')->where($where)->fRow();
        if(!$data)
        {
            $this->ajax('Market ' . $GLOBALS['MSG']['NOT_EXISTED']);
        }

        //交易规则
        $where = array('status'=>1, 'name'=>$market);
        $data = Coin_PairModel::getInstance()->field('name,price_float,number_float,min_trade,max_trade,rate,rate_buy')->where($where)->fRow();
        if(!$data)
        {
            $this->ajax('Market ' . $GLOBALS['MSG']['NOT_EXISTED']);
        }

        $returnData['rules'] = array(
            'market'=>$data['name'],
            'price_decimal_limit'=>$data['price_float'],
            'number_decimal_limit'=>$data['number_float'],
            'min'=>$data['min_trade'],
            'max'=>$data['max_trade'],
            'buy_rate'=>$data['rate_buy'],
            'sell_rate'=>$data['rate'],
        );

        //兑换法币
        $currency = 'rmb';

        //最新行情
        $redis = Cache_Redis::instance('quote');
        $quote = json_decode($redis->get($market . '_quote'), true);
        $returnData['quotes'] = $quote;
        $returnData['quotes']['currencySymbol'] = '￥';
        $returnData['quotes']['currencyCode'] = 'cny';
        if(stripos(LANG, 'zh-')===false&&stripos(LANG, 'cn')===false)
        {
            $returnData['quotes']['currency'] = $returnData['quotes']['usd'];
            $returnData['quotes']['currencySymbol'] = '$';
            $returnData['quotes']['currencyCode'] = 'usd';
            $currency = 'usd'; 
        }
        
        unset($returnData['quotes']['usd']);

        //$coinTo兑换法币价格
        list($coinFrom, $coinTo) = explode('_', $market);
        $cKey = $coinTo.'_'.$currency.'_price';
        $cache = Cache_Redis::instance()->get($cKey);
        $cache and $cPrice = json_decode($cache);  

        $returnData['quotes']['exPrice'] = $cPrice?:'0';

        $this->ajax('', 1, $returnData);
    }



    /**
     * 所有币的最新行情排序
     */
    public function getAllQuoteAction()
    {
        $domain = Yaf_Registry::get("config")->dobidomain;
        $all  = Coin_PairModel::getInstance()->getCoinPrice();

        $redis = Cache_Redis::instance();
        $cKey  = 'ALL_COINS_INFO';
        $coinsMap = json_decode($redis->get($cKey), true);

        //币信息缓存
        if(!$coinsMap)
        {
            $coins = User_CoinModel::getInstance()->field('`name`,`asset_name`,`logo`')->fList();
            $coinsMap = array();
            foreach($coins as $v)
            {
                $coinsMap[$v['name']] = $v;
            }
            $redis->set($cKey, json_encode($coinsMap), 86400 * rand(5, 10));
        }
        

        $data = array();
        foreach($all as $k=>$area)
        {
            foreach($area as $coinPair=>$quote)
            {
                $coinName = str_replace('_'.$k, '', $coinPair);
                $data[] = array(
                    'lastPrice'=>$quote['price'],
                    'volume'=>$quote['amount'],
                    'high'=>$quote['max'],
                    'low'=>$quote['min'],
                    'amount'=>$quote['money'],
                    'ratio'=>$quote['ratio'] .'%',
                    'tradeUrl'=>$domain.'trade/'.$coinPair,
                    'baseAssetLogo'=>$coinsMap[$coinName]['logo']?$domain.$coinsMap[$coinName]['logo']:'',
                    'baseAsset'=>strtoupper($coinName),
                    'baseAssetName'=>$coinsMap[$coinName]['asset_name'],
                    'quoteAsset'=>strtoupper($k),
                    'quoteAssetName'=>$coinsMap[$k]['asset_name'],  
                );
            }
            
        }
        
        $this->response('', 1, $data);
    }

}
