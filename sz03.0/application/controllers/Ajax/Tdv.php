<?php
/*TradingView Charting 數據*/
class Ajax_TdvController extends Ajax_BaseController
{
    function init()
    {
        parent::init();
    }
    public function historyAction()
    {
        if (!isset($_GET['symbol']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }
        
        $type = $_GET['resolution'];
        $from = $_GET['from']?:0;
        $to = $_GET['to']?:time();

        $arr = array(
            'W'  => '1w',
            'D'   => '1d',
            'H'  => '1h',
            '1'   => '1m',
            '15'   => '15m',
            '30'   => '30m',
            '60'   => '1h',
        );

        $type = $arr[$type];
        $name = $_GET['symbol'] . 'tradeline';
        $j    = $name . '_' . $type;
        $data = json_decode(Cache_Redis::instance('quote')->get($j), true);
        $returnData = array('s'=>'ok');
        
        if($data && $data['datas']['data'])
        {
            foreach($data['datas']['data'] as $v)
            {
                $v[0] = $v[0]/1000;
                if($v[0]>=$from && $v[0]<$to)
                {
                    $returnData['c'][] = $v[4];
                    $returnData['h'][] = $v[2];
                    $returnData['l'][] = $v[3];
                    $returnData['o'][] = $v[1];
                    $returnData['t'][] = $v[0];
                    $returnData['v'][] = $v[5];
                    $returnData[1][] = $v[1];
                }
                
            }
        }

        if(!isset($returnData['c']))
        {
            $returnData['s'] = 'no_data';
        }
        else
        {
            $returnData['nextTime'] = time() + 60;
        }
        
        exit(json_encode($returnData));
    }


    public function symbolsAction()
    {
        $symbol = $_GET['symbol'];
        $pair = strtoupper(str_replace('_', '/', $symbol));
        $returnData = array(
            'name'=>$pair,
            'ticker'=>$symbol,
            'exchange-traded'=>'',
            'exchange-listed'=>'',
            'timezone'=>'Asia/Shanghai',
            'minmovement'=>1,
            'minmovement2'=>0,
            'pointvalue'=>1,
            'session'=>'24x7',
            'type'=>'bitcoin',
            'pricescale'=>100000000,
            'has_no_volume'=>false,
            'has_intraday'=>true,
            'intraday_multipliers'=>['1',"15", "30", "60", "D"],
            'supported_resolutions'=>["1", "5","15", "30", "60", "240", "720", "D", "W", "M"],
            'description'=>''
        );
  
        exit(json_encode($returnData));
    }

    public function configAction()
    {
        $returnData = array(
            'supports_search'=>true,
            'supports_group_request'=>false,
            'supports_marks'=>false,
            'supports_timescale_marks'=>false,
            'supports_time'=>true,
            "exchanges"=>array([
                'value'=>"",
                "name"=>"All Exchanges",
                "desc"=>""
            ],[
                "value"=>"NasdaqNM",
                "name"=>"NasdaqNM",
                "desc"=>"NasdaqNM"
            ],["value"=>"NYSE",
            "name"=>"NYSE",
            "desc"=>"NYSE"
            ],["value"=>"NCM","name"=>"NCM","desc"=>"NCM"],["value"=>"NGM","name"=>"NGM","desc"=>"NGM"]),
            'symbolsTypes'=>[["name"=>"All types","value"=>""],["name"=>"Stock","value"=>"stock"],["name"=>"Index","value"=>"index"]],
            'supported_resolutions'=>["1", "15", "30", "60", "D"],
        );
        
        exit(json_encode($returnData));
    }

    public function symbol_infoAction()
    {
        
    }
    
    public function timeAction()
    {
        exit(''.time());
    }

    public function marksAction()
    {
        exit();
    }
}