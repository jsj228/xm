<?php
class Api_Pay_Cny
{

    private static $config;

    private $error = '';

    private static $authParam = array(); //接口身份识别参数

    private static $paySecret = '';

    private static $merchantNo = '';

    private static $url = array();

    public static $bankCode = array(
        '工商银行'     => 'ICBC',
        '招商银行'     => 'CMBCHINA',
        '中国农业银行'   => 'ABC',
        '建设银行'     => 'CCB',
        '北京银行'     => 'BCCB',
        '交通银行'     => 'BOCO',
        '中国民生银行'   => 'CMBC',
        '平安银行'     => 'PINGANBANK',
        '兴业银行'     => 'CIB',
        '南京银行'     => 'NJCB',
        '光大银行'     => 'CEB',
        '中国银行'     => 'BOC',
        '广发银行'     => 'CGB',
        '上海银行'     => 'SHB',
        '上海浦东发展银行' => 'SPDB',
        '中国邮政'     => 'POST',
        '渤海银行'     => 'CBHB',
        '东亚银行'     => 'HKBEA',
        '宁波银行'     => 'NBCB',
        '中信银行'     => 'ECITIC',
        '北京农村商业银行' => 'BJRCB',
        '华夏银行'     => 'HXB',
        '浙商银行'     => 'CZ',
        '杭州银行'     => 'HZBANK',
        '上海农村商业银行' => 'SRCB',
        '南洋商业银行'   => 'NCBBANK',
        '河北银行'     => 'SCCB',
        '泰隆银行'     => 'ZJTLCB',
        '成都银行'     => 'BOCDBANK',
    );

    public function __construct()
    {
        $this->loadConfig();
    }

    private function setError($error = '')
    {
        $this->error = $error;
        return false;
    }

    public function getError()
    {
        return $this->error;
    }

    private function loadConfig($name = '')
    {
        if (!self::$config)
        {
            $config          = Yaf_Registry::get("config")->api->pay->toArray();
            self::$authParam = array('payKey' => $config['payKey']);
            self::$paySecret = $config['paySecret'];
            self::$url       = $config['url'];
            self::$merchantNo = $config['merchantNo'];
            self::$config    = 1;
        }
    }

    public function getBankCode($bankName)
    {
        if($bankName)
        {
            if(isset(self::$bankCode[$bankName]))
            {
                return self::$bankCode[$bankName];
            }
            else
            {
                $bankName = str_replace(array('银行','中国'), '', $bankName);
                foreach (self::$bankCode as $k=>$v) 
                {
                    if(strpos($k, $bankName) != false)
                    {
                        return $v;
                    }
                }
            }
            
        }
    }

    //代付
    public function proxyPay($data = array())
    {
        if (!$data)
        {
            return $this->setError('参数错误');
        }

        //必要参数
        $mKeys = array(
            'orderPrice', //金额
            'outTradeNo', //商户订单号
            'receiverName', //收款人名字
            'phoneNo', //手机号
            'receiverAccountNo', //收款人卡号
            'bankBranchNo', //开户行支行行号
            'certNo', //证件号码
            'bankName', //开户行名称
            'bankCode', //银行编码
            'bankBranchName', //开户行支行名称
            'province', //开户省份
            'city', //开户城市
            'proxyType', //交易类型
            'productType', //产品类型
            'bankAccountType', //收款银行卡类型
            'certType', //证件类型
            'bankClearNo', //开户行清算行号
        );

        //默认参数
        $data['proxyType']       = 'T0';
        $data['productType']     = 'B2CPAY';
        $data['bankAccountType'] = 'PRIVATE_DEBIT_ACCOUNT';
        $data['certType']        = 'IDENTITY';
        $data['bankClearNo']     = '123456';
        $data['bankBranchNo']    = '123456';
        $data['bankCode'] = $this->getBankCode($data['bankName']);


        if (!$query = $this->setQueryData($mKeys, $data))
        {
            return false;
        }

        $url = self::$url['proxyPay'] . '?' . $query;

        return $this->request($url);
    }

    //查账
    public function proxyCheck($outTradeNo = '')
    {
        if (!$outTradeNo)
        {
            return $this->setError('参数错误');
        }

        //必要参数
        $mKeys = array('outTradeNo');
        $data  = array('outTradeNo' => $outTradeNo);

        if (!$query = $this->setQueryData($mKeys, $data))
        {
            return false;
        }

        $url = self::$url['proxyCheck'] . '?' . $query;

        return $this->request($url);

    }


    //平台充值
    public function recharge($data = array())
    {
        if (!$data)
        {
            return $this->setError('参数错误');
        }
        
        //必要参数
        $mKeys = array(
            'orderPrice',
            'outTradeNo',
            'productType',
            'orderTime',
            'productName',
            'orderIp',
            'bankCode',
            'bankAccountType',
            'returnUrl',
            'notifyUrl',
        );
        
        $host = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
        //默认参数
        $data['productType'] = '50000103';
        $data['productName'] = '高汇通平台充值';
        $data['bankAccountType'] = 'PRIVATE_DEBIT_ACCOUNT';
        $data['returnUrl'] = $host.'/b2cpay/ghtpayreturn';//页面通知地址
        $data['notifyUrl'] = $host.'/b2cpay/ghtpaynotify';//异步通知地址


        if (!$query = $this->setQueryData($mKeys, $data))
        {
            return false;
        }

        $url = self::$url['b2cPay'] . '?' . $query;

        return $this->request($url);
    }


    //平台余额查询
    public function getBalance()
    {
        //必要参数
        $mKeys = array(
            'merchantNo',
        );
        $data = array('merchantNo' => self::$merchantNo);
        if (!$query = $this->setQueryData($mKeys, $data))
        {
            return false;
        }

        $url = self::$url['balance'] . '?' . $query;

        return $this->request($url);
    }


    //组装请求数据
    private function setQueryData($necessaryKeys = array(), $data = array())
    {

        foreach ($necessaryKeys as $v)
        {
            if (!$data[$v])
            {
                return $this->setError($v.'不能为空');
            }
        }

        $reqData = self::$authParam;

        foreach ($necessaryKeys as $v)
        {
            $reqData[$v] = $data[$v];
        }

        $signData = $this->buildSign($reqData);
        
        $query = $signData['query'];

        //签名
        $sign = array('sign' => $signData['sign']);

        $query .= '&' . $this->buildQuery($sign);

        return $query;
    }

    //生成签名
    public function buildSign($data=array())
    {
        //排序(签名需要)
        ksort($data);

        //支付密钥放最后
        $data['paySecret'] = self::$paySecret;
        $query = $this->buildQuery($data);

        //签名
        $sign = strtoupper(md5($query));

        return array('sign'=>$sign, 'query'=>$query);
    }

    //组装成query string
    public function buildQuery($data, $encoding = false)
    {
        $res   = '';
        $count = count($data);
        $i     = 0;
        foreach ($data as $k => $v)
        {
            if ($encoding === true)
            {
                $v = urlencode($v);
            }
            if ($i < $count - 1)
            {
                $res .= $k . '=' . $v . '&';
            }
            else
            {
                $res .= $k . '=' . $v;
            }
            $i++;
        }
        return $res;
    }

    public function request($url, $param = array(), $header = array())
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36'); //user agent
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //添加自定义的http header
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_POST, true); // post
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param); // post传输数据
        $responseText = curl_exec($curl);

        if (!$responseText && $error = curl_error($curl))
        {
            return $this->setError($error);
        }
        curl_close($curl);

        return json_decode($responseText, true)?:$responseText;
    }

    //密钥
    private static function getCodeSecretKey($type=1, $encode=true)
    {
        $kMap = array(
            array(
                '0'=>'8',
                '1'=>'3',
                '2'=>'7',
                '3'=>'9',
                '4'=>'6',
                '5'=>'4',
                '6'=>'1',
                '7'=>'2',
                '8'=>'5',
                '9'=>'0',
            ),
            array(
                '0'=>'8',
                '1'=>'yp',
                '2'=>'7',
                '3'=>'9',
                '4'=>'a',
                '5'=>'6',
                '6'=>'c',
                '7'=>'2',
                '8'=>'w',
                '9'=>'bx',
            ),
        );

        if($encode)
        {
            return $kMap[$type];
        }
        return array_flip($kMap[$type]);
    }

    //int型id加密
    public static function idEncode($id, $prefix='bjstest_', $type=1)
    {
        return self::dealCode($id, $prefix, $type, true);
    }

    //int型id解密
    public static function idDecode($id, $prefix='bjstest_', $type=1)
    {
        return self::dealCode($id, $prefix, $type, false);
    }

    private static function dealCode($id, $prefix='', $type, $encode, $minLen=8)
    {
        if($encode)
        {
            $id = sprintf('%0'.$minLen.'d', $id);
        }
        else
        {
            $id = str_replace($prefix, '', $id);
        }
        
        $sKey = self::getCodeSecretKey($type, $encode);
        $len = strlen($id);

        $newOne = '';
        for($i=0; $i<$len; $i++)
        {
            $newOne .= $sKey[$id[$i]]?:$sKey[$id[$i++].$id[$i]];
        }

        if($encode)
        {
            $newOne = $prefix.$newOne;
        }
        else
        {
            $newOne = intval($newOne);
        }
        return $newOne;
    }

}
