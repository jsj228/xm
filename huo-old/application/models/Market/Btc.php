<?php
class Market_BtcModel extends Orm_Base{
    protected $_config='otc';
    public $table = 'market_btc';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
        'price' => array('type' => "decimal(40,20) unsigned", 'comment' => '单价'),
        'number' => array('type' => "decimal(40,20) unsigned", 'comment' => '数量'),
        'numberover' => array('type' => "decimal(40,20) unsigned", 'comment' => '剩余数量'),
        'numberdeal' => array('type' => "decimal(40,20) unsigned", 'comment' => '成交数量'),
        'max_price' => array('type' => "decimal(40,20) unsigned", 'comment' => '最大额度'),
        'min_price' => array('type' => "decimal(40,20) unsigned", 'comment' => '最小额度'),
        'last_max_price' => array('type' => "decimal(40,20) unsigned", 'comment' => '初始最大额度'),
        'last_min_price' => array('type' => "decimal(40,20) unsigned", 'comment' => '初始最小额度'),
        'fee' => array('type' => "decimal(20,8) unsigned", 'comment' => '手续费'),
        'feeover' => array('type' => "decimal(20,8) unsigned", 'comment' => '剩余手续费'),
        'feedeal' => array('type' => "decimal(20,8) unsigned", 'comment' => '已用手续费'),
        'flag' => array('type' => "enum('buy','sale')", 'comment' => '买卖标志'),
        'coin' => array('type' => "varchar(10)", 'comment' => '币种'),
        'status' => array('type' => "tinyint(1) unsigned", 'comment' => '状态:0未成交，1部分成交，2全部成交，3撤销'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
        'updateip' => array('type' => "char(15)", 'comment' => '更新ip'),
        'pay_time' => array('type' => "int(3)", 'comment' => '支付时间范围'),
        'pay_type' => array('type' => "varchar(20)", 'comment' => '支付方式：1.微信，2支付宝，3银行卡'),
        'userbak' => array('type' => "varchar(200) unsigned", 'comment' => '卖家留言'),
        'trust_type' => array('type' => "int(11) unsigned", 'comment' => 'trust_type'),
        'overflow' => array('type' => "int(4)", 'comment' => '浮动百分比'),
        'pricetype' => array('type' => "tinyint(1)", 'comment' => '价格类型：1固定价格，2溢价'),
        'overflowprice' => array('type' => "decimal(40,20)", 'comment' => '溢价最低价格'),
    );
    public $pk = 'id';

    const STATUS_UNSOLD = 0; # 未成交
    const STATUS_PART   = 1; # 部分成交
    const STATUS_ALL    = 2; # 全部成交
    const STATUS_CANCEL = 3; # 已经撤销

    static $status = array('未成交', '部分成交', '全部成交', '已经撤销');

    public function designTable($table){	//chen
        $tab='trust_'.$table.'coin';
        $this->table=$tab;
    }
    public function getList($sql){
        return $this->query($sql);
    }
    public function getOne($sql){
        return $this->fRow($sql);
    }
    /**
     * 撤销委托
     * @param $pId
     * @param $pUser
     * @param $ctype 1富途币，2融资
     */
    public function cancel($pId, &$pUser, $ctype = 1, $api =false){
        # 开始事务
        $this->begin();
        # 查询委托
        if(!$tTrust = $this->lock()->fRow("SELECT uid,number,numberover,price,flag,isnew,status,coin_from,coin_to FROM {$this->table} WHERE id=$pId")){
            $this->back();
            Tool_Fnc::ajaxMsg('委托记录不存在');
        }
        # 用户验证
        if($tTrust['uid'] != $pUser['uid']) {
            $this->back();
            Tool_Fnc::ajaxMsg('您无权进行此操作');
        }
        # 剩余查询
        if(($tTrust['numberover'] < 0.0001) || ($tTrust['status'] > 1)){
            $this->back();
            Tool_Fnc::ajaxMsg('撤消失败，您的委托已经被处理过');
        }
        //买卖
        if($tTrust['flag']=='buy'){
            $tMoney = $tTrust['numberover'] * $tTrust['price'];
            $tUserData = array($tTrust['coin_to'].'_lock' => -$tMoney, $tTrust['coin_to'].'_over' => $tMoney);
        } else {
            $tUserData = array($tTrust['coin_from'].'_lock' => -$tTrust['numberover'], $tTrust['coin_from'].'_over' => $tTrust['numberover']);
        }
        # 更新用户
        $tMO = new UserModel();
        if(TRUE !== $tMO->safeUpdate($pUser, $tUserData, $api)){
            $this->back();
            Tool_Fnc::ajaxMsg($tMO->error[2]);
        }
        # 更新委托
        if(!$this->update(array('id'=>$pId, 'numberover'=>0, 'isnew'=>'N', 'status'=>self::STATUS_CANCEL, 'updated'=>time(), 'updateip'=>Tool_Fnc::realip()))){
            $this->back();
            Tool_Fnc::ajaxMsg('系统错误，请通知管理员 [错误编号:T_C_001]');
        }
        $this->commit();
        Tool_Session::mark($pUser['uid']);
    }

    /**
     * 后台撤销委托
     * @param $pId
     */
    public function adminCancel($pId){
        # 开始事务
        $this->begin();
        # 查询委托
        if(!$tTrust = $this->lock()->fRow("SELECT uid,number,numberover,price,flag,isnew,status,coin_from,coin_to FROM {$this->table} WHERE id=$pId")){
            $this->back();
            Tool_Fnc::ajaxMsg('委托记录不存在');
        }
        # 状态验证
        if(!in_array($tTrust['status'], array(0, 1))) {
            $this->back();
            Tool_Fnc::ajaxMsg('您已不能进行此操作');
        }
        # 剩余查询
        if(($tTrust['numberover'] < 0.0001) || ($tTrust['status'] > 1)){
            $this->back();
            Tool_Fnc::ajaxMsg('撤消失败，委托已经被处理过');
        }

        # 撤销用户信息
        $pUser = array('uid'=>$tTrust['uid']);
        # 买卖
        if($tTrust['flag']=='buy'){
            $tMoney = $tTrust['numberover'] * $tTrust['price'];
            $tUserData = array($tTrust['coin_to'].'_lock' => -$tMoney, $tTrust['coin_to'].'_over' => $tMoney);
        } else {
            $tUserData = array($tTrust['coin_from'].'_lock' => -$tTrust['numberover'], $tTrust['coin_from'].'_over' => $tTrust['numberover']);
        }
        # 更新用户
        $tMO = new UserModel();
        if(!$tMO->safeUpdate($pUser, $tUserData)){
            $this->back();
            Tool_Fnc::ajaxMsg($tMO->error[2]);
        }
        # 更新委托
        if(!$this->update(array('id'=>$pId, 'numberover'=>0, 'isnew'=>'N', 'status'=>self::STATUS_CANCEL, 'updated'=>time(), 'updateip'=>Tool_Fnc::realip()))){
            $this->back();
            Tool_Fnc::ajaxMsg('系统错误，请通知管理员 [错误编号:T_C_001]');
        }
        $this->commit();
        Cache_Redis::instance()->hSet('usersession', $tTrust['uid'], 1);
    }
    /**
     * 交易
     * @param $pKey 币地址
     * @param $pUser 用户
     * @return array 富途币数据
     */
    public function btc($pData, &$pUser, $api = false){
        # 保存DB
        $this->begin();
        # 买入YBC
        if($pData['type']=='in'){
            $tRMB = $pData['price']*$pData['number'];
            $tData = array('cny_lock' => $tRMB, 'cny_over' => -$tRMB);
            $pData['type'] = 'buy';
            if($tRMB < 1E-3){
                Tool_Fnc::ajaxMsg('系统错误，请通知管理员 [错误编号:S_TB_001]');
            }
        }
        # 卖出YBC
        else {
            $tBTC = $pData['number'];
            $tData = array($pData['coin_from'].'_lock' => $tBTC, $pData['coin_from'].'_over' => -$tBTC);
            $pData['type'] = 'sale';
            if($tBTC < 1E-3){
                Tool_Fnc::ajaxMsg('系统错误，请通知管理员 [错误编号:S_TB_002]');
            }
        }
        # 写入
        $tMO = new UserModel();
        if(!$tMO->safeUpdate($pUser, $tData, $api)){
            $this->back();
            Tool_Fnc::ajaxMsg($tMO->error[2]);
        }
        # 写入委托
        if(!$tId = $this->insert(array(
            'uid'=>$pUser['uid'],
            'price'=>$pData['price'],
            'number'=>$pData['number'],
            'numberover'=>$pData['number'],
            'flag'=>$pData['type'],
            'status'=>0,
            'coin_from'=>$pData['coin_from'],
            'coin_to'=>'cny',
            'created'=>time(),
            'createip'=>Tool_Fnc::realip()
        ))){
            $this->back();
            Tool_Fnc::ajaxMsg('系统错误，请通知管理员 [错误编号:S_TB_001]');
        }
        # 提交数据
        $this->commit();
        Tool_Session::mark($pUser['uid']);
        $_SESSION['user'] = $pUser;
        Cache_Redis::instance()->hSet('TTask', 'user', 1);
        return $tId;
    }

    /**
     * 获取符合对应价格的list
     * @param pair array(0=>coin_from,1=>coin_to,2=>pair_id)
     * @param flag buy获取买单list,sale获取卖单list
     *
     * return array()|bool
     */
    public function getListByPrice($price, $pair=array(), $flag='buy') {
        if(!is_numeric($price) || !is_array($pair)){
            return false;
        }
        $where = array(
            'buy' => "coin_from='{$pair[0]}' and coin_to='{$pair[1]}' and flag='{$flag}' and isnew='N' and price>={$price} and status<2",
            'sale' => "coin_from='{$pair[0]}' and coin_to='{$pair[1]}' and flag='{$flag}' and isnew='N' and price<={$price} and status<2"
        );
        $order = array(
            'buy' => 'price desc,id asc',
            'sale' => 'price asc,id asc'
        );
        $list = $this->field('id')->where($where[$flag])->order($order[$flag])->limit(50)->fList();
        if(empty($list)){
            return false;
        }
        return $list;
    }

    /**
     * 成交更改numberover
     *
     */
    public function updateNumber($id, $number, $numberdeal){
        if($number < 1E-9){
            $status = self::STATUS_ALL;
        } else {
            $status = self::STATUS_PART;
        }
        $oldnumberdeal = $this->where("id={$id}")->fOne('numberdeal');
        $data = array('id'=>$id, 'numberover'=>$number, 'numberdeal'=>$oldnumberdeal+$numberdeal, 'isnew'=>'N', 'status'=>$status, 'upadted'=>time());
        return $this->update($data);
    }

    /**
     * 根据币名撤销挂单
     * @param  [string]  $coin_from [原来]
     * @param  [string]  $coin_to   [目标]
     * @param  integer $uid       [uid]
     * @return [boolean]             [true/false]
     */
    public function cancleAllByCoin($coin_from, $coin_to, $uid=0) {
        $where = '';
        if($uid) {
            $where .= 'uid='.$uid.' and ';
        }

        $where .= "coin_from='".$coin_from."' and coin_to='".$coin_to."' and status in(0, 1) and numberover>0.0001";
        # 查询委托
        // $sql = "SELECT uid,number,numberover,price,flag,isnew,status,coin_from,coin_to FROM {$this->table} WHERE ".$where;
        if(!$tTrustAll = $this->lock()->where($where)->fList()){
            return true;
        }

        $tMO 	= new UserModel();
        $ip 	= Tool_Fnc::realip();
        $redis 	= Cache_Redis::instance();
        $time 	= time();

        # 开始事务
        $this->begin();
        foreach ($tTrustAll as $tTrust) {
            # 撤销用户信息
            $pUser = array('uid'=>$tTrust['uid']);
            # 买卖
            if($tTrust['flag']=='buy'){
                $tMoney 	= $tTrust['numberover'] * $tTrust['price'];
                $tUserData 	= array($tTrust['coin_to'].'_lock' => -$tMoney, $tTrust['coin_to'].'_over' => $tMoney);
            } else {
                $tUserData 	= array($tTrust['coin_from'].'_lock' => -$tTrust['numberover'], $tTrust['coin_from'].'_over' => $tTrust['numberover']);
            }
            # 更新用户
            if(!$tMO->safeUpdate($pUser, $tUserData, true)){
                $this->back();
                return false;
            }
            # 更新委托
            if(!$this->update(array('id'=>$tTrust['id'], 'numberover'=>0, 'isnew'=>'N', 'status'=>self::STATUS_CANCEL, 'updated'=>$time, 'updateip'=>$ip))){
                $this->back();
                return false;
            }
            $redis->hSet('usersession', $tTrust['uid'], 1);
        }

        $this->commit();

        return true;

    }


    /**
     * 查询用户是否冻结禁止交易
     */
    public static function getTradeStatus($uid){
        $forMo = new UserForbiddenModel;
        $fdata = $forMo->lock()->where("uid = {$uid} and status = 0")->fRow();

        if( $fdata ){
            return $fdata;
        }else{
            return false;
        }
    }

    // 获取BTC实时价格
    public function nowPrice($coin='btc',$coinTo='cny')
    {
        $rate=0;
        switch ($coinTo)
        {
            case 'cny':
                $rate=1;
                break;
            case 'myr':
                $usdRate = file_get_contents('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b');
                $usdRate = iconv("GBK", "UTF-8//IGNORE", $usdRate);
                $usdRate = 100/preg_replace('/.+?林吉特.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate);
                $rate=$usdRate;
                break;
            case 'thb':
                $usdRate = file_get_contents('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b');
                $usdRate = iconv("GBK", "UTF-8//IGNORE", $usdRate);
                $usdRate = 100/preg_replace('/.+?泰国铢.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate);
                $rate=$usdRate;
                break;
            case 'idr':
                $usdRate = file_get_contents('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b');
                $usdRate = iconv("GBK", "UTF-8//IGNORE", $usdRate);
                $usdRate = 100/preg_replace('/.+?印尼盾.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate);
                $rate=$usdRate;
                break;
        }

        if($coin)
        {
            $coin = strtolower($coin);
        }
        if($coin=='btc')
        {
            $cKey = 'btc_rmb_price';
            $cache = Cache_Redis::instance("dobi")->get($cKey);
            if(!$cache)
            {
                $json = json_decode(file_get_contents("http://bit-z.com/index/coinsPrice"), true);
                $json = $json['data']['btc']['btc_cny'];
                Cache_Redis::instance()->set($cKey, json_encode($json), 30);
                $json = bcmul($json,$rate,2);
            }
            else
            {
                $json = json_decode(bcmul($cache,$rate,2));
            }
        }
        else if($coin=='mcc')
        {
            $btcKey = 'btc_rmb_price';
            $btcCache = Cache_Redis::instance("dobi")->get($btcKey);
            if(!$btcCache)
            {
                $btcJson = json_decode(file_get_contents("http://bit-z.com/index/coinsPrice"), true);
                $btcJson = $btcJson['data']['btc']['btc_cny'];
                Cache_Redis::instance()->set($btcKey, json_encode($btcJson), 30);
            }
            else
            {
                $btcJson=json_decode($btcCache);
            }

            $cKey = 'eb_mcc_price';
            $cache = Cache_Redis::instance()->get($cKey);
            if(!$cache)
            {
                $json = json_decode(file_get_contents("http://dobitrade.com/ajax_market/getAllQuote"), true);
                $json =bcmul($btcJson,$json['data']['mcc_btc']['price'],2);
                Cache_Redis::instance()->set($cKey, $json);
                $json = bcmul($json,$rate,2);
            }
            else
            {
                $json = Tool_Str::format(bcmul($cache,$rate,2),2,2);
            }
        }
        else if($coin=='eth')
        {
            $cKey = 'eth_rmb_price';
            $cache = Cache_Redis::instance("dobi")->get($cKey);
            if(!$cache)
            {
                $json = json_decode(file_get_contents("https://www.bitstamp.net/api/v2/ticker/ethusd/"), true);
                $json = $json['vwap'];
                $usdRate=file_get_contents("http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=b");
                $usdRate=iconv("GBK","UTF-8//IGNORE",$usdRate);
                $usdRate=preg_replace('/.+?美元.+?refePrice:[^\d]*?([\d\.]+?)[^\d\.].+/i', '$1', $usdRate)/100;
                if(is_numeric($usdRate) && $usdRate>0)
                {
                    $json=bcmul($json,$usdRate,2);
                    Cache_Redis::instance("default")->set($cKey, $json, 30);
                    $json=bcmul($json,$rate,2);
                }
            }
            else
            {
                $json = json_decode(bcmul($cache,$rate,2));
            }
        }
        else
        {
            $json = 1;
        }

        return $json;
    }

    /**
     * 获取ext实时价格
     */
    public function extPrice()
    {
        $json = 1;
        return $json;
    }

    // 出售信息存redis
    public function saveMarkeSaleAction($coinName='btc_cny')
    {
        $mccMo = new Market_BtcModel();

        $coinName = $coinName?strtolower($coinName):'btc_cny';
        $time=time();
        //判断用户是否在线
        $sessinId=Cache_Redis::instance("session")->keys("*");
        foreach ($sessinId as $k=>$v)
        {
            $arr=Cache_Redis::instance("session")->get($v);
            $arr=json_decode($arr,true);
            if(strrpos($arr,"otc"))
            {
                $arr=substr($arr,strrpos($arr,'otc'));
                preg_match_all('/\"(.*?)\"/', $arr, $matches);
                //用户最后法币时间
                $lastOtc=str_replace('"','',end($matches[0]));
                //不在法币页面超30分钟才算离线
                if($lastOtc>$time-1800)
                {
                    //当前在线用户的uid
                    $onlineUid[]= str_replace('"','',$matches[0][2]);
                }
            }
        }
        //如果没有用户在线
        if(!$onlineUid)
        {
            $onlineUid=0;
        }
        else
        {
            //当前在线所有用户的uid
            $onlineUid= array_unique($onlineUid) ;
            $onlineUid=implode(',',$onlineUid);
        }
        $sql="select a.*,b.nickname,b.logo,b.order_total,b.order_rate,b.mo mo,if(a.uid in({$onlineUid}),1,0) online
from `market_btc` a left join `user` b on a.uid=b.uid where a.status in (0,1) AND a.numberover>0 AND a.flag='sale' 
AND coin='".addslashes($coinName)."' order by online desc,price asc,order_rate desc,order_total desc, a.id asc";

        $data = $mccMo->query($sql);
        foreach ($data as $k=>&$v)
        {
            //查出该笔广告下是否24小时内有成交的订单
            $order = $mccMo->query("select id from order_{$v['coin']} where status=2 and m_id={$v['id']} and created<{$time}+86400");
            if(!$mccMo->dobiAction($v['mo']) && !$order)
            {
                $v['validity']=0;//无效广告
            }
            else
            {
                $v['validity']=1;//有效广告
            }
        }
        unset($v);
        $ages = array();
        $price = array();
        $rate = array();
        $order = array();
        $time = array();
        foreach ($data as $user) {
            $ages[] = $user['validity'];//按validity 有效无效广告排序
            $online[] = $user['online'];//按online 是否在线排序
            $price[] = $user['price'];//按price 价格排序
            $rate[] = $user['order_rate'];//按rate 信用度排序
            $order[] = $user['order_total'];//按order 成交单数排序
            $time[] = $user['created'];//按time 发布时间排序
        }
        array_multisort($ages, SORT_DESC,$online,SORT_DESC,$price,SORT_ASC,$rate,SORT_DESC,$order,SORT_DESC,$time,SORT_ASC,$data);

//		$mccMo = new Market_BtcModel();
//
//		$coinName = $coinName?strtolower($coinName):'mcc';
//
//		$sql = "select a.*,b.nickname,b.logo,b.order_total,b.order_rate from `market_btc` a left join `user` b on a.uid=b.uid
//                        where a.status in (0,1) AND a.numberover>0 AND a.flag='sale' and coin='".addslashes($coinName)."' order by price asc,order_total desc,order_rate desc, a.id desc";
//
//		$data = $mccMo->query($sql);
//		if($data)
//		{
//			Cache_Redis::instance('common')->del("sale");
//			Cache_Redis::instance('common')->del("saledata");
//		}
        $i = 0;
        foreach ($data as $k => $v)
        {
            if(bcmul($v['price'],$v['numberover'],2)<100)
            {
                Cache_Redis::instance('common')->zDelete('sale'.$coinName, $v['id']);
            }
            else
            {
                $payType = 0;
                if($v['flag']=='sale'&&isset($v['pay_type']))
                {
                    if($v['logo'])
                    {
                        if(substr($v['logo'],0,1)=='.')
                            $v['logo'] = substr($v['logo'],1,strlen($v['logo']));
                    }
                    if(strpos($v['pay_type'],','))
                    {
                        $dd = explode(',',$v['pay_type']);
                        if(is_array($dd))
                        {
                            foreach ($dd as $k1 => &$v1)
                            {
                                if($v1=='2')
                                {
                                    $v1 = 4;
                                }
                                else if($v1=='3')
                                {
                                    $v1 = 7;
                                }
                                else if($v1=='1')
                                {
                                    $v1 = 1;
                                }
                                $payType += $v1;
                            }
                        }
                    }
                    else
                    {
                        if($v['pay_type']=='1')
                        {
                            $payType = 1;
                        }
                        elseif($v['pay_type']=='2')
                        {
                            $payType = 4;
                        }
                        else
                        {
                            $payType = 7;
                        }
                    }
                    $v['price'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['price'],2)), '.');
                    $v['overflowprice'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['overflowprice'],8)), '.');
                    $v['min_price'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['min_price'],2)), '.');
                    $v['max_price'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['max_price'],2)), '.');
                    $v['numberover'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['numberover'],8)), '.');
                    $v['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['number'],8)), '.');
                    // 头像去掉字符.
                    if(isset($v['logo'])&&substr($v['logo'],0,1)=='.') $v['logo'] = substr($v['logo'],1,strlen($v['logo']));

                    $values = $v['id'].",".$v['number'].",".$v['price'].",".$v['overflowprice'].",".$v['min_price'].",".$v['max_price'].",".$v['numberover'].",".$v['pay_time'].",".$payType.",".$v['uid'].",".$v['nickname'].",".$v['logo'].",".$v['order_total'].",".$v['order_rate'].",".$v['coin'].",".$v['flag'].",".$v['online'];
                    //ext官方排第一
                    $pwdKey = Yaf_Registry::get("config")->market->$coinName;
                    $pwdKey=explode(',',$pwdKey);
                    if(!empty($pwdKey))
                    {
                        foreach ($pwdKey as $val)
                        {
                            if($v['uid']==$val)
                            {
                                Cache_Redis::instance('common')->zAdd("sale".$coinName,$i,$v['id']);
                                ++$i;
                            }
                            else
                            {
                                Cache_Redis::instance('common')->zAdd("sale".$coinName,$k+20,$v['id']);
                            }
                        }
                    }
                    else
                    {
                        Cache_Redis::instance('common')->zAdd("sale".$coinName,$i,$v['id']);
                    }
                    Cache_Redis::instance('common')->hSet('saledata'.$coinName,$v['id'],$values);
                }
            }

        }
    }

    //查看用户是否超过24小时未登录
    public function dobiAction($mo)
    {
        $dobi=new CoinModel();
        $time=time();
        $uid=$dobi->query("select uid from user where mo={$mo}");
        $arr=$dobi->query("select uid from user_login where uid={$uid[0]['uid']} and created>{$time}-86400 limit 1");
        if($arr)
        {
            return true;
        }
        return false;
    }
    // 求购信息存redis
    public function saveMarkeBuyAction($coinName='btc_cny')
    {
        $mccMo = new Market_BtcModel();

        $coinName = $coinName?strtolower($coinName):'btc_cny';
        $time=time();
        //判断用户是否在线
        $sessinId=Cache_Redis::instance("session")->keys("*");
        foreach ($sessinId as $k=>$v)
        {
            $arr=Cache_Redis::instance("session")->get($v);
            $arr=json_decode($arr,true);
            if(strrpos($arr,"otc"))
            {
                $arr=substr($arr,strrpos($arr,'otc'));
                preg_match_all('/\"(.*?)\"/', $arr, $matches);
                //用户最后法币时间
                $lastOtc=str_replace('"','',end($matches[0]));
                //不在法币页面超30分钟才算离线
                if($lastOtc>$time-1800)
                {
                    //当前在线用户的uid
                    $onlineUid[]= str_replace('"','',$matches[0][2]);
                }
            }
        }
        //如果没有用户在线
        if(!$onlineUid)
        {
            $onlineUid=0;
        }
        else
        {
            //当前在线所有用户的uid
            $onlineUid= array_unique($onlineUid) ;
            $onlineUid=implode(',',$onlineUid);
        }
        $sql="select a.*,b.nickname,b.logo,b.order_total,b.order_rate,b.mo mo,if(a.uid in({$onlineUid}),1,0) online
from `market_btc` a left join `user` b on a.uid=b.uid where a.status in (0,1) AND a.numberover>0 AND a.flag='buy' 
AND coin='".addslashes($coinName)."' order by online desc,price desc,order_rate desc,order_total desc, a.id asc";

        $data = $mccMo->query($sql);
        foreach ($data as $k=>&$v)
        {
            //查出该笔广告下是否24小时内有成交的订单
            $order = $mccMo->query("select id from order_{$v['coin']} where status=2 and m_id={$v['id']} and created<{$time}+86400");
            if(!$mccMo->dobiAction($v['mo']) && !$order)
            {
                $v['validity']=0;//无效广告
            }
            else
            {
                $v['validity']=1;//有效广告
            }
        }
        unset($v);
        $ages = array();
        $price = array();
        $rate = array();
        $order = array();
        $time = array();
        foreach ($data as $user) {
            $ages[] = $user['validity'];//按validity 有效无效广告排序
            $online[] = $user['online'];//按online 是否在线排序
            $price[] = $user['price'];//按price 价格排序
            $rate[] = $user['order_rate'];//按rate 信用度排序
            $order[] = $user['order_total'];//按order 成交单数排序
            $time[] = $user['created'];//按time 发布时间排序
        }
        array_multisort($ages, SORT_DESC,$online,SORT_DESC,$price,SORT_DESC,$rate,SORT_DESC,$order,SORT_DESC,$time,SORT_ASC,$data);

//		$sql = "select a.*,b.nickname,b.logo,b.order_total,b.order_rate,if(a.uid in({$onlineUid}),1,0) online from `market_btc` a left join `user` b on a.uid=b.uid
//                        where a.status in (0,1) AND a.numberover>0 AND a.flag='buy' AND coin='".addslashes($coinName)."' order by online asc,price desc,order_rate desc,order_total desc, a.id asc";
//		$data = $mccMo->query($sql);

//		if($data)
//		{
//			Cache_Redis::instance('common')->del("buy");
//			Cache_Redis::instance('common')->del("buydata");
//		}
        $i = 0;
        $j = 0;
        $redis = Cache_Redis::instance('common');
        foreach ($data as $k=>$v)
        {
            if(bcmul($v['price'],$v['numberover'],2)<100)
            {
                $redis->zDelete('buy'.$coinName, $v['id']);
            }
            else
            {
                $payType = 0;
                if($v['flag']=='buy'&&isset($v['pay_type']))
                {
                    if($v['logo'])
                    {
                        if(substr($v['logo'],0,1)=='.')
                            $v['logo'] = substr($v['logo'],1,strlen($v['logo']));
                    }
                    if(strpos($v['pay_type'],','))
                    {
                        $dd = explode(',',$v['pay_type']);
                        if(is_array($dd))
                        {
                            foreach ($dd as $k1 => &$v1)
                            {
                                if($v1=='2')
                                {
                                    $v1 = 4;
                                }
                                elseif($v1=='3')
                                {
                                    $v1 = 7;
                                }
                                elseif($v1=='1')
                                {
                                    $v1 = 1;
                                }
                                $payType += $v1;
                            }
                        }
                    }
                    else
                    {
                        if($v['pay_type']=='1')
                        {
                            $payType = 1;
                        }
                        elseif($v['pay_type']=='2')
                        {
                            $payType = 4;
                        }
                        elseif($v['pay_type']=='3')
                        {
                            $payType = 7;
                        }
                    }
                    $v['price'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['price'],2)), '.');
                    $v['overflowprice'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['overflowprice'],8)), '.');
                    $v['min_price'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['min_price'],2)), '.');
                    $v['max_price'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['max_price'],2)), '.');
                    $v['numberover'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['numberover'],8)), '.');
                    $v['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($v['number'],8)), '.');

                    $values = $v['id'].",".$v['number'].",".$v['price'].",".$v['overflowprice'].",".$v['min_price'].",".$v['max_price'].",".$v['numberover'].",".$v['pay_time'].",".$payType.",".$v['uid'].",".$v['nickname'].",".$v['logo'].",".$v['order_total'].",".$v['order_rate'].",".$v['coin'].",".$v['flag'].",".$v['online'];
                    //ext官方排第一
                    $pwdKey = Yaf_Registry::get("config")->market->$coinName;
                    $pwdKey=explode(',',$pwdKey);
                    if(!empty($pwdKey))
                    {
                        foreach ($pwdKey as $val)
                        {
                            if($v['uid']==$val)
                            {
                                $redis->zAdd("buy".$coinName,$k,$v['id']);
                                ++$i;
                            }
                            else
                            {
                                $redis->zAdd("buy".$coinName,$k+20,$v['id']);
                            }
                        }
                    }
                    else
                    {
                        $redis->zAdd("buy".$coinName,$k,$v['id']);
                    }
                    $redis->hSet('buydata'.$coinName,$v['id'],$values);
                }
            }
        }
    }

    // 移除某个信息
    public function remMarketAction($makertId,$flag,$coinName='btc_cny')
    {
        if($flag&&$makertId)
        {
            $redis = Cache_Redis::instance("common");
            $coinName = $coinName?strtolower($coinName):'btc_cny';
            $redis->zDelete($flag.$coinName, $makertId);
            $redis->hDel($flag."data".$coinName, $makertId);
        }
    }

    /**
     * 修改一个广告信息
     * @param $marketId 广告信息表id
     */
    public function saveOneMarketAction($marketId,$coinName='btc_cny')
    {
        if($marketId&&is_numeric($marketId))
        {
            $sql = "select a.*,b.nickname,b.logo,b.order_total,b.order_rate from `market_btc` a left join `user` b on a.uid=b.uid 
                        where a.status in (0,1) AND a.numberover>0 AND a.id=".addslashes($marketId)." order by price asc,order_total desc,order_rate desc, a.id desc";

            $datas = $this->query($sql);

            $data = $datas[0];

            if(!empty($data)&&is_array($data))
            {
                $mathPrice = bcmul($data['price'],$data['numberover'],2);
                if($mathPrice<100)
                {
                    if($data['flag']=='buy')
                    {
                        $this->saveMarkeBuyAction($coinName);
                    }
                    else
                    {
                        $this->saveMarkeSaleAction($coinName);
                    }
                }
                $payType = 0;
                if(strpos($data['pay_type'],','))
                {
                    $dd = explode(',',$data['pay_type']);
                    if(is_array($dd))
                    {
                        foreach ($dd as $k1 => $v1)
                        {
                            if($v1=='2')
                            {
                                $v1 = 4;
                            }
                            if($v1=='3')
                            {
                                $v1 = 7;
                            }
                            elseif($v1=='1')
                            {
                                $v1 = 1;
                            }
                            $payType += $v1;
                        }
                    }
                }
                else
                {
                    if($data['pay_type']=='1')
                    {
                        $payType = 1;
                    }
                    elseif($data['pay_type']=='2')
                    {
                        $payType = 4;
                    }
                    else
                    {
                        $payType = 7;
                    }
                }
                $data['price'] 			= trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($data['price'],2)), '.');
                $data['overflowprice'] 	= trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($data['overflowprice'],8)), '.');
                $data['min_price']  	= trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($data['min_price'],2)), '.');
                $data['max_price']  	= trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($data['max_price'],2)), '.');
                $data['numberover']	 	= trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($data['numberover'],8)), '.');
                $data['number']     	= trim(preg_replace('/(\.\d*?)0+$/', '$1', Tool_Str::format($data['number'],8)), '.');

                $values = $data['id'].",".$data['number'].",".$data['price'].",".$data['overflowprice'].",".$data['min_price'].",".$data['max_price'].",".$data['numberover'].",".$data['pay_time'].",".$payType.",".$data['uid'].",".$data['nickname'].",".$data['logo'].",".$data['order_total'].",".$data['order_rate'].",".$data['coin'];

                Cache_Redis::instance('common')->hSet($data['flag'].'data'.$coinName,$data['id'],$values);
            }
        }
    }

    /**
     * 获取交易市场列表
     * @param string $flag 买卖标志
     * @param int $page 当前页
     * @param int $pageSize 页大小
     * @param boolean $all true：所有，false:部分
     * @return array 返回一个数组
     */
    public function marketListAction($flag='buy',$coinName='btc_cny',$page=1,$pageSize=20,$all=false)
    {
        $coinName = $coinName?strtolower($coinName):'btc_cny';
        // 总记录数
        $count = Cache_Redis::instance('common')->zSize($flag.$coinName);

        if($all)
        {
            $pageSize = $count;
        }

        //页总数
        $page_count = ceil($count/$pageSize);

        $sdata= Cache_Redis::instance('common')->zRange($flag.$coinName,($page-1)*$pageSize,(($page-1)*$pageSize+$pageSize-1));
        // 循环得到每一条数据
        $data = array();
        foreach ($sdata as $k => $v)
        {
            $data[] = Cache_Redis::instance('common')->hGet($flag."data".$coinName,$v);
        }

        $dd = array();
        foreach ($data as $k => &$v)
        {
            $v = explode(",",$v);
            if(is_array($v))
            {
                foreach ($v as $k1 => $v1)
                {
                    $pay = [];
                    $pay1 = '';
//$values = $v['id'].",".$v['number'].",".$v['price'].",".$v['overflowprice'].",".$v['min_price'].",".$v['max_price'].",".
//          $v['numberover'].",".$v['pay_time'].",".$payType.",".$v['uid'].",".$v['nickname'].",".$v['logo'].",".$v['order_total'].",".$v['order_rate'];
                    if($k1==0)
                        $dd[$k]['id'] = $v1;
                    elseif($k1==1)
                        $dd[$k]['number'] = $v1;
                    elseif($k1==2)
                        $dd[$k]['price'] = $v1;
                    elseif($k1==3)
                        $dd[$k]['overflowprice'] = $v1;
                    elseif($k1==4)
                        $dd[$k]['min_price'] = $v1;
                    elseif($k1==5)
                        $dd[$k]['max_price'] = $v1;
                    elseif($k1==6)
                        $dd[$k]['numberover'] = $v1;
                    elseif($k1==7)
                        $dd[$k]['pay_time'] = $v1;
                    elseif($k1==8)
                    {
                        if($v1=='5')
                        {
                            $pay = [1,2];
                        }
                        elseif ($v1 == '11')
                        {
                            $pay = [2,3];
                        }
                        elseif ($v1 == '8')
                        {
                            $pay = [1,3];
                        }
                        elseif ($v1 == '12')
                        {
                            $pay = [1,2,3];
                        }
                        else
                        {
                            if($v1=='1')
                            {
                                $v1 = 1;
                            }
                            elseif($v1=='4')
                            {
                                $v1 = 2;
                            }
                            elseif($v1=='7')
                            {
                                $v1 = 3;
                            }
                            $pay = [$v1];
                        }
                        $dd[$k]['pay_type'] = $pay;
                    }
                    elseif($k1==9)
                        $dd[$k]['uid'] = $v1;
                    elseif($k1==10)
                        $dd[$k]['nickname'] = $v1;
                    elseif($k1==11)
                        $dd[$k]['logo'] = $v1;
                    elseif($k1==12)
                        $dd[$k]['order_total'] = $v1;
                    elseif($k1==13)
                        $dd[$k]['order_rate'] = $v1;
                    elseif($k1==14)
                        $dd[$k]['coin'] = $v1;
                    elseif($k1==15)
                        $dd[$k]['flag'] = $v1;
                    elseif($k1==16)
                        $dd[$k]['online'] = $v1;
                }
            }
        }
        return $dd;
    }

    /**
     * 检查市场列表是否有该用户的信息，有就刷新该用户的信息的缓存
     */
    public function flushUserMarket($uid,$coinName='mcc'){
        if($uid&&is_numeric($uid))
        {
            // 求购所有的信息
            $buydata = $this->marketListAction('buy',$coinName,1,20,true);

            // 出售所有的信息
            $saledata = $this->marketListAction('sale',$coinName,1,20,true);

            if(is_array($buydata))
            {
                foreach ($buydata as $k => $v)
                {
                    if($v['uid']==$uid)
                    {
                        $this->saveOneMarketAction($buydata[$k]['id'],$coinName);
                    }
                }
            }

            if(is_array($saledata))
            {
                foreach ($saledata as $k => $v)
                {
                    if($v['uid']==$uid)
                    {
                        $this->saveOneMarketAction($saledata[$k]['id'],$coinName);
                    }
                }
            }
        }

    }
}
