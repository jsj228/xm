<?php
require_once __CONTROLLER__.'CommonController.php';
require_once __MODEL__.'TradeModel.php';
require_once __MODEL__.'MarketModel.php';
class TradeController extends CommonController {
    public $config;
    public $trademodel; 
	public $marketmodel; 
    public $redisdb;
	public function __construct($param,$is_open,$conf){
        $this->config = $conf;
        $this->trademodel = new TradeModel($conf['mysql']);
		$this->marketmodel = new MarketModel($conf['mysql']);
		$this->redisdb = new Redis();
        $this->redisdb->connect($conf['redis']['host'],$conf['redis']['port']);
		$this->redisdb->auth($conf['redis']['auth']);
		parent::__construct($param,$is_open,$conf);
	}

    public function getTradelog($data){
        $market = $data['market'];
        $queue_name = 'getTradelog' . $market;
        $key = 'getTradelog' . $market.'len';
        $redis_len = $this->trademodel->get_trade_log($market,1);
        $obj = (object)array();
        if($this->redisdb->exists($key)){
            $old_count = $this->redisdb->get($key);
            if($old_count === $redis_len[0]['num']){
                $obj->getTradelog=$this->redisdb->lrange($queue_name,0,-1);
            }else{
                $obj->getTradelog=self::init_trade_log($market);
            }
        }else{
            $obj->getTradelog=self::init_trade_log($market);
        }
        return $obj;
    }


    function init_trade_log($market){
        $queue_name = 'getTradelog' . $market;
        $key = 'getTradelog' . $market.'len';
	    $redis_len = $this->trademodel->get_trade_log($market,1);
        $this->redisdb->set($key,$redis_len[0]['num']);
        if($redis_len[0]['num'] == 0){
            return false;
        }
        $tradeLog = $this->trademodel->get_trade_log($market);
        if ($tradeLog) {
            foreach ($tradeLog as $k => $v) {
                $data['tradelog'][$k]['addtime'] = date('H:i:s', $v['addtime']);
                $data['tradelog'][$k]['type'] = $v['type'];
                $data['tradelog'][$k]['price'] = $v['price'] * 1;
                $data['tradelog'][$k]['num'] = round($v['num'], 6);
                $data['tradelog'][$k]['mum'] = round($v['mum'], 6);
            }
            $this->redisdb->del($queue_name);
            for($i=0;$i<count($data['tradelog']);$i++){
                $this->redisdb->rpush($queue_name,json_encode($data['tradelog'][$i]));  
            }
            return $this->redisdb->lrange($queue_name,0,-1);
        }else{
            return false;
        }
    }


    public function getDepth($data){
        $market = $data['market'];
        $buy_key = 'buy_getDepth'.$market;
        $sell_key = 'sell_getDepth'.$market;
        $call_key = 'call_getDepth'.$market;
        if(!$this->marketmodel->compare_market($market)){
            return array('msg'=>'市场已关闭');
        }
        if(!$this->config['weike_getCoreConfig']){
            return array('msg'=>'配置文件错误');
        }
        if($this->redisdb->exists($call_key)){
            $new = intval($this->redisdb->get($call_key));
            if($new > 0){
               self::init_getDepth($market); 
            }
        }else{
            self::init_getDepth($market);
        }
        $obj = (object)array();
        $obj->buy = $this->redisdb->lrange($buy_key,0,-1);
        $obj->sell = $this->redisdb->lrange($sell_key,0,-1);
        return $obj;
    }

    public function init_getDepth($market){
        $buy_key = 'buy_getDepth'.$market;
        $sell_key = 'sell_getDepth'.$market;
        $call_key = 'call_getDepth'.$market;
        $param = $this->trademodel->get_trade_order($market);
        $this->redisdb->del($buy_key);
        $this->redisdb->del($sell_key);
        if($param->buy){
            foreach($param->buy as $kb => $vb){
                $this->redisdb->rpush($buy_key,json_encode($vb));
            }
        }
        if($param->sell){
            foreach($param->sell as $ks => $vs){
                $this->redisdb->lpush($sell_key,json_encode($vs));
            }
        }
        $this->redisdb->set($call_key,0);
    }

    public function both($data){
        $obj = (object)array();
        $obj->market = $this-> getDepth($data);
        $obj->log = $this-> getTradelog($data);
        return $obj;
    }
}
