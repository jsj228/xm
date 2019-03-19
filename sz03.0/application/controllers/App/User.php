<?php

class App_UserController extends App_BaseController
{
    protected $_auth = 1;

    /**
     * APP账单
     */

    public function coinOutRecordAction()
    {
        $this->_islogin();

        $coinList = $this->setCoinList('out');

        $_POST = $this->rsaDecode()?:$_POST;  //是否加密

        $coin     =trim($_POST['coin'])?:'btc';            //币种
        $type     =trim($_POST['type'])?:'all';     //类型
        $_POST['id'] = (int)$_POST['id'];

        if (!$coin || !preg_match('/^[a-z]+$/i', $coin))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);
        }

       if($type=='all' &&  !$_POST['id'])
       {
           $where = array('uid='.$this->mCurUser['uid']);  //全部

       }
       else if ($_POST['id'] && !$_POST['coin'])
       {
           $where = array('uid='.$this->mCurUser['uid'],'id='."'{$_POST['id']}' "); //详情
       }
       else
       {
         $where = array('uid='.$this->mCurUser['uid'], 'opt_type='."'{$type}' ");
       }

        $where = implode(' and ', $where);
        $exchange = 'Exchange_' . ucfirst($coin) . 'Model';
        $exchangeMo   =  new $exchange;


        $curCoinIdx = array_search($coin, array_column($coinList, 'coin_from'));
        $coinInfo[$curCoinIdx] = User_CoinModel::getInstance()->where(array('name'=>$coin))->fRow();

        //页码
        $total['total'] = $exchange::getInstance()->where($where)->count();
      //  $tPage = new Tool_Page($count, $pageSize);
      //  $pageinfo = $tPage->show();

        if ($total['total'] == 0) {
            $data['list'] = '';
            $data['pagetotal'] = 0;
            $data['prev'] = '';
            $data['next'] = '';
            $data['currentpage'] = '';
        } else {

            $page = $_POST['page'] ? (int)addslashes($_POST['page']) : 1;//页码
            $pagenumber = $_POST['size'] ? (int)addslashes($_POST['size']) :20;//每页多少条
            $data['pagetotal'] = ceil($total['total'] / $pagenumber);//总页数

            if ($page > $data['pagetotal']) {
                $page = $data['pagetotal'];
            }

            if ($page < 1) {
                $page = 1;
            }
            $p = ($page - 1) * $pagenumber;

            $tSql = sprintf('SELECT * FROM exchange_'.$coin.' WHERE %s ORDER BY id desc '. ' limit  '.$p.','.$pagenumber, $where);

            $data['list']  = $exchangeMo->query($tSql);

            foreach ($data['list'] as &$v) {//去0
                $v['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v['number']), '.');
            }

            $data['prev'] = $page - 1;//上一页
            $data['next'] = $page + 1;//下一页
            if ($data['prev'] < 1) {
                $data['prev'] = 1;
            }
            if ($data['next'] > $data['pagetotal']) {
                $data['next'] = $data['pagetotal'];
            }
            $data['currentpage'] = $page;//当前页

        }

        $statusMap = array(
            '待审批'=>0,
            '等待'=>1,
            '成功'=>2,
            '已取消'=>3,
            '确认中' => 4,
        );

        foreach ($data['list'] as &$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
            $v['is_out'] = $v['is_out']? $GLOBALS['MSG']['IN_PLATFORM']: $GLOBALS['MSG']['OUT_PLATFORM'];//'平台内':'平台外'

            if($v['confirm']==0 && $v['status']=='等待' && Tool_Math::comp($v['number'], $coinInfo[$curCoinIdx]['out_limit'])==1)
            {
                $v['statusCode'] = $statusMap['待审批'];
                $v['status'] = $statusMap['待审批'];
            }
            elseif(isset($statusMap[$v['status']]))
            {
                $v['statusCode'] = $statusMap[$v['status']];
                $v['status'] = $statusMap[$v['status']];
            }


        }



        unset($v);

        $this->response('', 1, $data);

    }


    private function setCoinList($type)
    {
        $coinPairMo = User_CoinModel::getInstance();
        $coinList   = $coinPairMo->field('name coin_from, display,in_status,out_status')->getList();
        $this->assign('coinList', $coinList);
        return $coinList;
    }


   /**
    *  提币用户余额
    */

    public function outoverAction()
    {
        $this->_islogin();
        $coin = trim($_POST['coin']);
        $over = $coin."_over";
        $mo  = 'Exchange_' . ucfirst($coin) . 'Model';
        $tMO = new $mo();

        if($coin)
        {
            //用户当前币的余额
            $user = UserModel::getInstance()->field("google_key,uid,$over")->where(['uid'=>$this->mCurUser['uid']])->fRow();
            $user[$over]     =  sprintf('%.8f', $user[$over]);
            $user['over']    =  sprintf('%.8f', $user[$over]);
            //币的状态
            $user['coin']    =  CoinModel::getInstance()->field('minout,maxout,out_status,in_status,rate_out,number_float,coin_transfer')->where(['name'=>$coin])->fRow();
            //历史地址
            $user['addres'] =  $tMO->query("select wallet from {$tMO->table} where uid = {$this->mCurUser['uid']} and wallet!='' or wallet!=null GROUP by wallet");

            $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'],1,$user);
        }
        else
        {
             $this->ajax($GLOBALS['MSG']['TEL_ZJ_CS']);
        }
    }


    /**
     * 提币
     */
    public function coinOutAction()
    {
        $this->_islogin();

        $user = UserModel::getInstance()->fRow($this->mCurUser['uid']);
        //没有交易密码
        if (!$user['pwdtrade'])
        {
            $this->response($GLOBALS['MSG']['NEED_REAL_AUTH'], 0, 'pwdtrade');
        }

        $realInfo = AutonymModel::getInstance()->where(array('uid' => $this->mCurUser['uid'], 'status' => 2))->fRow();
        //没有实名认证
        if (!$realInfo)
        {
            $this->response($GLOBALS['MSG']['NEED_REAL_AUTH']);
        }

        $wallet   = trim(strip_tags($_POST['wallet']));
        $number   = trim($_POST['number']);
        $pwdtrade = trim($_POST['pwdtrade']);
        $code     = trim($_POST['code']);
        $coinName = trim($_POST['coin']);

        //是否被冻结禁止数字货币提现
        $cancoinout = User_CoinModel::getCoinOutStatus($this->mCurUser['uid']);
        if ($cancoinout == 0)
        {
           $this->response($GLOBALS['MSG']['COIN_OUT_FROZEN']);
        }

        //钱包地址错误
        if (!$wallet)
        {
            $this->response($GLOBALS['MSG']['WALLET_ADDR_ERROR'], 0, 'wallet');
        }

        //币信息
        $coinInfo = User_CoinModel::getInstance()->where(array('name' => $coinName))->fRow();
        if (!$coinName || !$coinInfo)
        {
            $this->response($GLOBALS['MSG']['PARAM_ERROR']);
        }

        //提币暂停
        if($coinInfo['out_status']==1)
        {
            $this->response($GLOBALS['MSG']['COIN_OUT_STOP']);
        }

        //转出限额
        if (Tool_Math::comp($number, $coinInfo['minout'])==-1 || (Tool_Math::comp($number, $coinInfo['maxout'])==1  && $coinInfo['maxout']>0))
        {
            $this->response(sprintf($GLOBALS['MSG']['COIN_OUT_RANGE'], $coinInfo['minout'], $coinInfo['maxout']), 0, 'number');
        }

        $tNum = Tool_Math::format($number);

        //小数位限制
        list($int, $dec) = explode('.', $tNum);
        if ($coinInfo['number_float'] && strlen($dec)>$coinInfo['number_float'])
        {
            $this->response($GLOBALS['MSG']['NUMBER_ERROR'], 0, 'number');
        }

        //用户余额不足
        if (Tool_Math::comp($tNum, $this->mCurUser[$coinName . '_over'])==1)
        {
            $this->response($GLOBALS['MSG']['COIN_NOT_ENOUGH'], 0, 'number');
        }

        //验证交易密码
        if (!$pwdtrade || (Tool_Md5::encodePwdTrade($pwdtrade, $user['prand']) != $user['pwdtrade']))
        {
            $this->response($GLOBALS['MSG']['TRADE_PWD_ERROR'], 0, 'pwdtrade');
        }


         // google_key 验证码
         if($this->mCurUser['google_key'])
         {
             if (!Api_Google_Authenticator::verify_key($this->mCurUser['google_key'], $code))
             {
                 $this->ajax($GLOBALS['MSG']['GOOGLE_ERROR']);
             }
         }


        //入库
        $mo  = 'Exchange_' . ucfirst($coinName) . 'Model';
        $tMO = new $mo();
        if (!$tMO->post($wallet, $tNum, $coinName, $this->mCurUser))
        {
            $this->response($tMO->getError(2)?:$GLOBALS['MSG']['SYS_ERROR']);
        }

        $this->response('提币成功', 1,$wallet);

    }

    /**
     * 用户信息
     */
    public function getUserInfoAction()
    {
       $this->_islogin();
       /* $this->mCurUser['uid'] = '13231229';
        $this->mCurUser['area'] = '+86';
        $this->mCurUser['mo'] = '18370628189';
        $this->mCurUser['email'] = '';*/
        $coin = trim($_POST['coin']);
        if($coin)
        {
            if($this->mCurUser)
            {
                /*
                foreach ($this->mCurUser as $k => $v)
                {

                    if(stripos($coin, '_over') || stripos($coin, '_lock'))
                    {
                        $data[$coin] = sprintf('%.8f', $v);
                    }
                    else
                    {
                        unset($k);
                    }
                }*/

                //是否被冻结禁止数字货币提现 1该用户被冻结
                $data['cancoinout'] =  User_CoinModel::getCoinOutStatus($this->mCurUser['uid']);
                $realInfo = AutonymModel::getInstance()->where(array('uid' => $this->mCurUser['uid'], 'status' => 2))->fRow();

                $data[$coin.'_over']= sprintf('%.8f', $this->mCurUser[$coin.'_over']);
                $data[$coin.'_lock']=sprintf('%.8f', $this->mCurUser[$coin.'_lock']);
                $data['pwdtrade'] = $this->mCurUser['pwdtrade'];
                if($realInfo)
                {
                    $data['realInfo'] = 1;
                }else
                {
                    $data['realInfo'] = 0;
                }

              //  $data['total'] = UserModel::getInstance()->convertCoin($this->mCurUser, 'btc');
                $moLen = strlen($this->mCurUser['mo']);
                $data['phone'] = substr_replace($this->mCurUser['mo'], str_pad('',$moLen>7?4:$moLen-4,"*"), max(-8, -$moLen), -4);
            }
        }
        else
        {
            $this->response($GLOBALS['MSG']['PARAM_ERROR'], 0);
        }

        $this->response('', 1, $data);
    }

    //充币
    private function callInterfaceCommon($URL, $type, $params, $headers)
    {
        $ch      = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $URL); //发贴地址
        if ($headers != "")
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        else
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/json'));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        switch ($type)
        {
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "PUT" :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }
        $file_contents = curl_exec($ch);//获得返回值
        curl_close($ch);

        return $file_contents;
    }

    //获取钱包地址wallet=false表示获取失败
    public function getCoinAddressAction()
    {
        if (empty($this->mCurUser))
        {
            $data['reUrl'] = '/?login';
            $this->response($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        $name = $_POST['coin'] ? trim(addslashes($_POST['coin'])) : 'etw';
        if ($name == 'rss' || $name == 'npc' || $name == 'mcc')
        {

            $rpcurl = Yaf_Registry::get("config")->api->rpcurl->$name;
            if (!$rpcurl)
            {
                $data['wallet'] = false;
                $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
            }
            //$cointype 用的的js库
            //$addrType 地址的首位字符
            if ($name == 'rss')
            {
                $cointype = 'redss-js';
                $addrType = 'A';
            }
            else if($name == 'npc')
            {
                $cointype = 'asch-js';
                $addrType = 'A';
            }
            else
            {
                $cointype = 'mcc-js';
                $addrType = 'M';
            }
            # 二代钱包地址
            $twoaddress = new AddressModel;

            $wallet = $twoaddress->getAddr($this->mCurUser['uid'], $name);
            if (!$wallet)
            {
                $params     = array('coinType' => $cointype,'addrType'=>$addrType);
                $params     = json_encode($params);
                $headers    = array('Content-type: application/json');
                $url        = Yaf_Registry::get("config")->api->rpcurl->node;//node.js地址
                $strResult  = $this->callInterfaceCommon($url, "POST", $params, $headers);
                $strResult  = json_decode($strResult, true);
                $strResult  = $strResult['data'];
                $time       = time();
                if($name == 'mcc')
                {
                    $address = $strResult['address']."M";
                }
                else
                {
                    $address = $strResult['address'];
                }
                $insertData = array(
                    'uid'       => $this->mCurUser['uid'],
                    'address'   => $address,
                    'coin'      => $name,
                    'secret'    => $strResult['newsecret'],
                    'publicKey' => $strResult['publicKey'],
                    'status'    => 0,
                    'created'   => $time
                );

                //web端钱包登陆接口
                $params    = array("publicKey" => "{$strResult['publicKey']}");
                $params    = json_encode($params);
                $headers   = array('Content-type: application/json');
                $url       = $rpcurl . "api/accounts/open2/";
                $strResult = $this->callInterfaceCommon($url, "POST", $params, $headers);//获取全部交易
                $strResult = json_decode($strResult, true);
                if ($strResult['success'] == true)
                {
                    $twoaddress->insert($insertData);
                    $wallet = $twoaddress->getAddr($this->mCurUser['uid'], $name);
                }
                else
                {
                    $data['wallet'] = false;
                    $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
                }
            }
        }
         // else if ($name == 'etw' || $name == 'eth' || $name == 'eos' || $name=='etc'|| $name=='htc'|| $name=='mac'|| $name == 'bvt'|| $name == 'ptoc'|| $name == 'obc'|| $name == 'afc'|| $name == 'kkc')
        else if(in_array($name,array('etw','eth', 'eos','etc','htc','mac', 'bvt','ptoc','obc','afc','kkc','lcc','xtc','ethms','sw','qaq','en','read','ait','bqt')))
        {
            # etc地址
            $addressMo = new AddressModel;
            $wallet    = $addressMo->getAddr($this->mCurUser['uid'], $name);
            if (!$wallet)
            {
                $etwurl = Yaf_Registry::get("config")->api->rpcurl->$name;
                if (!$etwurl)
                {
                    $data['wallet'] = false;
                    $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
                }
                if($name=='lcc'){//lcc参数不一样
                    $params = '{"password":"dob88888","save":true}';
                }else{
                    $params = '{"jsonrpc":"2.0","method":"personal_newAccount", "params":["bjs88888"],"id":1}';
                }
                $headers   = array('Content-type: application/json');
                $strResult = $this->callInterfaceCommon($etwurl, "POST", $params, $headers);
                $strResult = json_decode($strResult, true);
                if (isset($strResult['error']))
                {
                    $data['wallet'] = false;
                    $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
                    //echo json_encode($data);
                    //exit;
                }
                $time       = time();
                if ($name == 'lcc') {//lcc参数不一样
                    $addrs = $strResult['address'];
                } else {
                    $addrs = $strResult['result'];
                }
                $insertData = array(
                    'address' => $addrs,
                    'coin'    => $name,
                    'created' => $time
                );
                //$sql  = "insert into address(address,coin,created) values ('{$strResult['result']}', '{$name}', {$time})";
                if (!$addressMo->insert($insertData))
                {
                    $data['wallet'] = false;
                    $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
                    //echo json_encode($data);
                    //exit;
                }
                $wallet = $addressMo->getAddr($this->mCurUser['uid'], $name);
            }
        }
        else
        {
            # 一代钱包地址
            $addressMo = new AddressModel;

            $wallet = $addressMo->getAddr1($this->mCurUser['uid'], $name);

            if (!$wallet)
            {
                $data['wallet'] = false;
                $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_FAIL'], 0, $data);//获取钱包地址失败
                //echo json_encode($data);
                //exit;
            }
        }

        $data['wallet'] = $wallet;
        $this->response($GLOBALS['MSG']['GET_WALLETADDRESS_SUCCESS'], 1, $data);//获取钱包地址成功
        //echo json_encode($data);
        //exit;
    }


    /*
     *   账户信息
     *
     * */


    public function accountuserAction()
    {
        $this->_ajax_islogin();
       /* $this->mCurUser['uid'] = '13231229';
        $this->mCurUser['area'] = '+86';
        $this->mCurUser['mo'] = '18370628189';
        $this->mCurUser['email'] = '';*/
        //币列表
        $coinList = User_CoinModel::getInstance()->getList();
        if($_POST['data']!='fabi')
        {
            //用户信息
            $userInfo = UserModel::getInstance()->fRow($this->mCurUser['uid']);
            unset($userInfo['uid']);
        }

         $postcoin = strtolower($_POST['coin']);

        if (trim($_POST['trust']) && trim($postcoin))   //该币种所在的交易区
        {
            $coin = new Coin_PairModel();
            $trustcoin = $coin->field('name')->where(array('coin_from' => trim($postcoin)))->fList();

            foreach ($trustcoin as $k => $v)
            {
                foreach ($v as $kk => $v1)
                {
                    $trust[] = str_replace('_', '/', $v1);
                }
            }
                if($trustcoin)
                {
                    $this->ajax('', 1, $trust);

                }
                else
                {
                    $this->ajax($GLOBALS['MSG']['JYQ_NO'], 0,array());//该币种没有在交易区
                }
        }
        else
        {
            if (!$_POST['trust'] && !trim($postcoin))  //所有币种
            {
                //幣交易规则
                $coinPair = Coin_PairModel::getInstance()->field('coin_from')->where('status=1')->fList();
                $coinStatus = array_column($coinPair, 'coin_from');
            }
            else if (!$_POST['trust'] && trim($postcoin))  //查询单个币种
            {
                $coinPair = CoinModel::getInstance()->field('name')->where("status=0 and name='{$postcoin}'")->fList();
                if ($coinPair)
                {
                    $coinStatus = array(
                        0 => trim($postcoin)
                    );

                }
                else
                {
                    $this->ajax($GLOBALS['MSG']['MY_NO'], 0, array()); //没有搜索到该币种
                }

            }

        }

        //btc = cny的价格
        $cKey = 'btc' . '_rmb_price';
        $cache = Cache_Redis::instance()->get($cKey);
        $cache = json_decode($cache);

        //易币数据
        $yibidata = $this->getyibiuser($this->mCurUser['area'], $this->mCurUser['mo'], $this->mCurUser['email'], '');//获取易币账户余额

        $yidd = $yibidata['data'];

        if ($yibidata['status'] == 1) {
            unset($yidd['mo']);
            unset($yidd['ext_over']);
            unset($yidd['ext_lock']);
        }
        if ($yibidata['status'] == 0)//没数据
        {
            $this->assign('if_data', 0);
        } else {
            $this->assign('if_data', 1);
        }

        $yibiuserInfo = $yidd;
        $yibicoinGroup = array();
        $yibicoin = array();
        $yi_coinList = User_CoinModel::getInstance()->where('status=0 and otc=0')->fList();

        // 法币
        if ($_POST['data'] != 'bibi') {
            foreach ($yi_coinList as $key => &$v) {
                if ($yibidata['status'] == 0) //没数据
                {
                    break;
                }
                //上线的币
                if (trim($postcoin))   //搜索单个币种
                {
                    if (in_array($v['name'], $coinStatus)) {
                        $yibicoin[] = $v['name'];

                        if ($yibiuserInfo[$v['name'] . '_over'] > 0 || $yibiuserInfo[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc') {
                            $yibicoinGroup['on']['owned'][] = $v;
                        }

                        $yibicoinGroup['on']['others'][] = $v;

                    }
                } else    //所有
                {
                    if (in_array($v['name'], $coinStatus) || in_array($v['name'], array('btc', 'dob'))) {
                        $yibicoin[] = $v['name'];

                        if ($yibiuserInfo[$v['name'] . '_over'] > 0 || $yibiuserInfo[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc') {
                            $yibicoinGroup['on']['owned'][] = $v;
                        }

                        $yibicoinGroup['on']['others'][] = $v;

                    }
                }

            }
        }
        //火网数据
        $coinGroup = array();
        if ($_POST['data']!='fabi')
        {
           foreach ($coinList as $key => &$v)  //上线的币
           {

            if (trim($postcoin)) {
                if (in_array($v['name'], $coinStatus)) {
                    if ($userInfo[$v['name'] . '_over'] > 0 || $userInfo[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc') {
                        $coinGroup['bibi']['on']['owned'][] = $v;
                    }

                    $coinGroup['bibi']['on']['others'][] = $v;

                }
            } else {
                if (in_array($v['name'], $coinStatus) || in_array($v['name'], array('btc', 'dob'))) {
                    if ($userInfo[$v['name'] . '_over'] > 0 || $userInfo[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc') {
                        $coinGroup['bibi']['on']['owned'][] = $v;
                    }

                    $coinGroup['bibi']['on']['others'][] = $v;


                }
            }

          }
        }
        unset($v);
        //折算总资产
        //$allToBtc = UserModel::getInstance()->convertCoin($userInfo, 'btc');
        $newPrice = Coin_PairModel::getInstance()->getCoinPrice();
        $tradearea = Coin_PairModel::getInstance()->field('DISTINCT coin_to')->fList();
        foreach ($tradearea as &$v1)
        {
            $v1['coin_to'] = '_' . $v1['coin_to'];
        }

        $bibiReturn = [];
        $fabiReturn = [];
        foreach($userInfo as $k=>$v)
        {
            if(strpos($k, '_lock')!==false||strpos($k, '_over')!==false)
            {
                $bibiReturn[$k] = sprintf('%.8f',$v);
            }
        }

        if($yidd)
        {
            foreach($yidd as $k1=>$v1)
            {
                if(strpos($k1, '_lock')!==false||strpos($k1, '_over')!==false)
                {
                    $fabiReturn[$k1] = sprintf('%.8f',$v1);
                }
            }
        }

        //全部币都换算成btc
        foreach ($newPrice as $coin => $area)
        {
            if ($coin == 'btc')
            {
                foreach ($area as $k => $v)
                {
                    $coinPrice[str_replace(array_column($tradearea, 'coin_to'), '', $k)] = array(
                        preg_replace('/.+?_/', '', $k), Tool_Math::format($v['price']),
                    );
                }
            }
            else
            {
                if($newPrice['btc'][$coin . '_btc']['price'])
                {
                    $method = 'mul';
                    $transPirce = $newPrice['btc'][$coin . '_btc']['price'];
                }
                else
                {
                    $method = 'div';
                    $transPirce = $newPrice[$coin]['btc_'.$coin]['price'];
                }

                foreach ($area as $k => $v)
                {
                    $cc = str_replace(array_column($tradearea, 'coin_to'), '', $k);
                    $btcarr = array_keys($coinPrice);
                    if (in_array($cc, $btcarr))//如果已经有跳过
                    {
                        continue;
                    }
                    $coinPrice[$cc] = array(
                        'btc', $transPirce?Tool_Math::$method($v['price'], $transPirce):'',
                    );
                }
            }
        }

        foreach ($coinPrice as $key=>$nn )   //每个币等于多少人民币
        {
            $over = $key.'_over';
            $lock = $key.'_lock';

            if($bibiReturn[$over])
            {
                $sum = Tool_Math::add($bibiReturn[$over], $bibiReturn[$lock]);
                $btc = Tool_Math::mul($sum, $nn[1]);
                $coinGroup['bibi']['cny'][$key] = Tool_Math::mul($btc, $cache);

            }


            if($fabiReturn[$over])
            {
                $sum = Tool_Math::add($fabiReturn[$over], $fabiReturn[$lock]);
                $btc = Tool_Math::mul($sum, $nn[1]);
                $coinGroup['otc']['cny'][$key] = Tool_Math::mul($btc, $cache);

            }
        }



        //折算总资产
        $allToBtc = Tool_Math::add($userInfo['btc_over'], $userInfo['btc_lock']);//全部折算btc 火网
        if ($yibidata['status'] == 1) //有数据
        {
            $yibiallToBtc = Tool_Math::add($yibiuserInfo['btc_over'], $yibiuserInfo['btc_lock']);//全部折算btc 易币
            foreach ($coinPrice as $k => $v)
            {
                $over_add_lock = Tool_Math::add($userInfo[$k . '_over'], $userInfo[$k . '_lock']);
                $coin_to_btc = Tool_Math::mul($over_add_lock, $v[1]);
                $allToBtc = Tool_Math::add($allToBtc, $coin_to_btc);
                if (isset($yibiuserInfo[$k . '_over']))
                {
                    $yibi_over_add_lock = Tool_Math::add($yibiuserInfo[$k . '_over'], $yibiuserInfo[$k . '_lock']);
                    $yibi_coin_to_btc = Tool_Math::mul($yibi_over_add_lock, $v[1]);
                    $yibiallToBtc = Tool_Math::add($yibiallToBtc, $yibi_coin_to_btc);
                }
            }
        }
        //火网
        $userbtc=Tool_Math::add($bibiReturn['btc_over'], $bibiReturn['btc_lock']);
        $userdob=Tool_Math::add($bibiReturn['dob_over'], $bibiReturn['dob_lock']);
        $coinGroup['bibi']['cny']['btc'] = Tool_Math::mul($userbtc, $cache);
        $coinGroup['bibi']['cny']['dob'] = $userdob;
        //法币
        $otcbtc=Tool_Math::add($fabiReturn['btc_over'], $fabiReturn['btc_lock']);
        $otcdob=Tool_Math::add($fabiReturn['dob_over'], $fabiReturn['dob_lock']);
        $coinGroup['otc']['cny']['btc'] = Tool_Math::mul($otcbtc, $cache);
        $coinGroup['otc']['cny']['dob'] = $otcdob;

        $coinGroup['dobiBtc']      = sprintf('%.8f',$allToBtc); //BTC火网總資產
        $coinGroup['dobicny']      = sprintf('%.8f',Tool_Math::mul($allToBtc, $cache));//火网人民币
        $coinGroup['fabicny']      = sprintf('%.8f',Tool_Math::mul($yibiallToBtc, $cache)) ; //法币人民币
        $coinGroup['fabiBtc']      = sprintf('%.8f',$yibiallToBtc);  //法币
        if($_POST['data']!='fabi')
        {
            $coinGroup['user']         = $bibiReturn;   //火网用户余额

        }

        if($_POST['data']!='bibi')
        {
            $coinGroup['fabi']         = $yibicoinGroup;//易币数据
            $coinGroup['fabiuser']     = $fabiReturn;//易币数据

        }


        $this->ajax('',1,$coinGroup);


    }


    //获取易币用户余额
    public function getyibiuser($area,$mo,$email, $coin )
    {
        $data = array(
            'area' => $area,
            'mo' => $mo,
            'email' => $email,
            'coin' => $coin,
            'timestamp' => time()
        );
        ksort($data);
        $str = '';
        foreach ($data as $key => $v) {
            $str .= $key . $v;
        }
        //$token = md5($str . 'asdcsd');
        $dobi_used_key = "EIML%CcrtqVwXzrT4s8%F5YaDdZ1F^6A";//火网传给法币
        $token = md5($str . $dobi_used_key);
        $data['token'] = $token;
        $headers = array('Content-Type:application/x-www-form-urlencoded', 'charset=utf-8');
        $yibiurl = Yaf_Registry::get("config")->yibi->ip;
        $url = $yibiurl . 'api_user/userover';
        $json = http_build_query($data);

        //$strResult = $this->callInterfaceCommon($url, "POST", $json, $headers);
        $strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);
        $ddd = substr($strResult, strpos($strResult, "{"));//去掉json前面的东东
        $ars = json_decode($ddd, true);
        return $ars;
    }

    //版本升级
    public function upgradeAction()
    {
      $where = ' 1=1 ';
      if(trim($_POST['system'])==1)             //1安卓  2ios
      {
          $name = trim($_POST['name']);        //当前版本号
          $mark = trim($_POST['mark']);
          $where .=" and system = 1 or system = 2" ;
          $url ="/Android/app-release_legu_{$mark}_signed_zipalign.apk" ;
      }
      else if( trim($_POST['system'])==2)
      {
          $name = trim($_POST['name']);         //当前版本号
          $where .=" and system = 1 or system = 3" ;
          $url ='app_user/ios';
      }
      else
      {
          $this->ajax($GLOBALS['MSG']['PARAM_ERROR'],0,'error');
      }

        $tMO  = new AppversionModel();
        $data = $tMO->query("select * from {$tMO->table} where $where ORDER BY id DESC  limit 1");
        if($data[0]['mandatory']=='')
        {
            $data[0]['mandatory']=0;
        }
        if($data)
        {
            $data[0]['url'] = $url;
            if($data['mark']!=$name)
            {
                $this->ajax('',1,$data[0]);
            }
        }
        else
        {
           $this->ajax($GLOBALS['MSG']['FX_NO'],1);
        }
    }


    /**
     * 撤销转出
     */
    public function cancelOutAction()
    {
        $this->_ajax_islogin();

        if(!isset($_POST['id'], $_POST['coin']))
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        $uid = $this->mCurUser['uid'];

        $coin = $_POST['coin'];
        $id       = intval($_POST['id']);
        $time     = time();

        $moName = 'Exchange_'.ucfirst($coin).'Model';
        $exchangeMo = new $moName;
        $exchangeMo->begin();
        $data = $exchangeMo->lock()->fRow($id);

        //記錄不存在或者正在轉出中
        if(!$data || $data['status'] != '等待' || $data['confirm']>0)
        {
            $this->ajax($GLOBALS['MSG']['RECORD_NOT_EXISIT']);
        }
        //用戶id不一致，非法操作
        if($data['uid'] != $uid)
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }

        $userMo = new UserModel();
        $userInfo = $userMo->lock()->field("{$coin}_lock,{$coin}_over")->fRow($uid);

        //判断用户冻结餘額
        if(Tool_Math::comp($userInfo[$coin.'_lock'], $data['number']) == -1)
        {
            $this->ajax($GLOBALS['MSG']['BALANCE_NOT_ENOUGH']);
        }

        //修改轉出單狀態
        $result = $exchangeMo->update(array('status'=>'已取消', 'updated'=>$time, 'id'=>$id));

        if(!$result)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY']);
        }

        //修改用餘額
        $userSaveData = array(
            "{$coin}_lock"=>Tool_Math::sub($userInfo[$coin.'_lock'], $data['number']),
            "{$coin}_over"=>Tool_Math::add($userInfo[$coin.'_over'], $data['number']),
            'updated'=>$time,
            'updateip'=>Tool_Fnc::realip(),
            'uid'=>$uid,
        );
        $result = $userMo->save($userSaveData);
        if (!$result)
        {
            $exchangeMo->back();
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'].'[2]');
        }
        $exchangeMo->commit();

        //刷新用戶信息緩存
        Tool_Session::mark($uid);

        $this->ajax($GLOBALS['MSG']['SUCCESS'], 1);

    }


}
