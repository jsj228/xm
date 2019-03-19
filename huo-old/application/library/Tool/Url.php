<?php
class Tool_Url{
    /**
     * @desc 当前Host
     */
    public static function getHost(){
        if(!$host = $_SERVER['SERVER_NAME']){
            $host = Yaf_Registry::get("config")->url->host;
        }
        return $host;
    }
    /**
     * @desc 获取共享域
     */
    public static function  getDomain(){
        $domain = '';
        if(false !== strpos($_SERVER['SERVER_NAME'], 'dobitrade.com') || $_SERVER['SERVER_NAME']=='localhost'){
            $domain = 'dobitrade.com';
        } elseif (false !== strpos($_SERVER['SERVER_NAME'], 'bijiaosuo.com')){
            $domain = 'bijiaosuo.com';
        } elseif (false !== strpos($_SERVER['SERVER_NAME'], '5ituya.com')){
            $domain = '5ituya.com';
        }elseif (false !== strpos($_SERVER['SERVER_NAME'], 'btc51.cn')){
            $domain = 'btc51.cn';
        }
        else {
            $domain = $_SERVER['HTTP_HOST'];
        }
        return $domain;
    }

	public static function getLoanHost(){
        if(!$host = $_SERVER['SERVER_NAME']){
		    $host = Yaf_Registry::get("config")->url->loanhost;
        }
		$base_url = 'http://';
        if(isset($_SERVER['HTTPS'])){
            $base_url = 'https://';
        }
		return $base_url.$host;
	}
    /**
     * @desc 首页
     */
    public static function getBaseUrl(){
        $host = self::getHost();
        $base_url = 'http://';
        if(isset($_SERVER['HTTPS'])){
            $base_url = 'https://';
        }
        return $base_url.$host;
    }
    public static function getYbexUrl()
    {
        $host = self::getHost();
        $base_url = 'http://';
        if(isset($_SERVER['HTTPS'])){
            $base_url = 'https://';
        }
        return $base_url.$host;
    }
    /**
     * @desc 获取当前https or http
     */
    public static function getProtocol(){
        $protocol = 'http://';
        if(isset($_SERVER['HTTPS'])){
            $protocol = 'https://';
        }
        return $protocol;
    }
    /**
     * @desc 强制https首页
     */
    public static function getBaseHttps(){
        $host = self::getHost();
        $http = "https://";
        if($_SERVER['HTTP_HOST'] != 'bijiaosuo.com'){
            $http = "http://";
        }
        return $http.$host;
    }
    /**
     * @desc 获取当前访问页面
     */
    public static function getCurrentUrl()
    {
        $url = 'http://';
        if(isset($_SERVER['HTTPS'])){
            $url = 'https://';
        }
        $url .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        return $url;
    }
    /**
     * @desc 注册页面
     */
    public static function getRegUrl($redirect='')
    {
        $url = self::getBaseUrl().'/user/reg';
        if(!empty($redirect)){
            $url .= '?url='.$redirect;
        }
        return $url;
    }
    /**
     * @desc 登陆页面
     */
    public static function getLoginUrl($redirect='')
    {
        $url = self::getBaseUrl().'/user/login';
        if(!empty($redirect)){
            $url .= '?url='.$redirect;
        }
        return $url;
    }
    /**
     * @desc 项目主页url
     */
    public static function getProjectUrl($id, $domain=false){
        $url = "/project_index/?id=".$id;
        if($domain){
            $url = self::getBaseUrl().$url;
        }
        return $url;
    }
    /**
     * @desc 用户主页url
     */
    public static function getUserUrl($uid, $domain=false){
        $url = "/user_home/?uid=".$uid;
        if($domain){
            $url = self::getBaseUrl().$url;
        }
        return $url;
    }
    /**
     * @desc 公告详情
     */
    public static function getNewsDetailUrl($id, $domain=false){
        $url = "/news/detail/?id=".$id;
        if($domain){
            $url = self::getBaseUrl().$url;
        }
        return $url;
    }

	/**
     * @desc 项目二级域名
     * 因为项目内容不可控(会引入http等资源)，不支持https
     */
    public static function getProjectOnlyUrl($id){
        if($_SERVER['SERVER_ADDR'] != '115.29.242.235' || false === strpos($_SERVER['SERVER_NAME'],'bijiaosuo.com')){
            return self::getProjectUrl($id);
        }
		$host = "8001".str_pad($id,4,'0',STR_PAD_LEFT);
        //$host .= '.'.Yaf_Registry::get("config")->url->host;
        $host .= '.'.self::getDomain();
		$base_url = 'http://';
        return $url = $base_url.$host;
    }
    /**
     * @desc ybc,btc成交汇总url
     * trust_coin,order_coin
     */
    public static function getAjaxCoinUrl($pair='')
    {
        $url = Yaf_Registry::get("config")->url->base;
        $url .= '/index/ajaxrefresh';
        if(!empty($pair)){
            $url .= "/coinpair/{$pair}";
        }
        return $url;
    }
    /**
     * @desc 获取出借详情页
     */
    public static function getLoanOutDetailUrl($id)
    {
        return self::getBaseUrl().'/user_loan/outDetail/id/'.$id;
    }
    /**
     * @desc 获取借款详情页
     */
    public static function getLoanInDetailUrl($id)
    {
        return self::getBaseUrl().'/user_loan/inDetail/id/'.$id;
    }
    /**
     * @desc 获取补币
     */
    public static function getLoanAddUrl($id)
    {
        return self::getBaseUrl().'/user_loan/add/in_id/'.$id;
    }
    /**
     * @desc 出借人列表
     */
    public static function getOutListUrl($id)
    {
        return self::getBaseUrl().'/user_loan/outlist/in_id/'.$id;
    }
    /**
     * @desc 投标详情页面
     */
    public static function getLoanIpoUrl($id)
    {
        return self::getBaseUrl().'/loan_index/detail/?id='.$id;
    }
    /**
     * @desc 获取邮箱激活链接
     */
    public static function getEmailActivateUrl($url_param)
    {
        $http = 'http://';
        if(isset($_SERVER['HTTPS'])){
            $http = 'https://';
        }
        $url = $http.$_SERVER['HTTP_HOST'].'/user/emailactivate?id='.base64_encode(serialize($url_param));
        return $url;
    }
    public static function getRepaymentUrl($id)
    {
        return self::getBaseUrl().'/user_loan/repayment/id/'.$id;
    }
    /**
     * 获取质押地址explorer url
     */
    public static function getBlockChainUrl($address='', $coin='btc')
    {
        $blockchain = array(
            'btc' => 'http://qukuai.com/address/',
            'ltc' => 'http://qukuai.com/ltc/address/',
            'ybc' => 'http://explorer.ybcoin.com/address/'
        );
        if(empty($address)){
            return 'false';
        }
        if(!isset($blockchain[$coin])){
            return '#';
        }
        return $blockchain[$coin].$address;
    }
    /**
     * 地址证明
     */
    public static function getProofUrl()
    {
        return self::getBaseUrl().'/index/proof';
    }
    /**
     * 币币交易地址
     */
    public static function getTradeUrl($name, $ggan=false)
    {
        $url = self::getBaseUrl().'/trade/'.$name;
        if($ggan){
            $url .= '?ggan=1';
        }
        return $url;
    }
    /**
     * 币转入地址
     */
    public static function getExchangeInUrl($name, $cfos=0)
    {
        $uri = '/user_exchange/coinin/coinname/'.$name;
        if($cfos){
            $uri = '/user_exchange/cfosin/coinname/'.$name;
        }
        return self::getBaseUrl().$uri;
    }
    /**
     * 币转出地址
     */
    public static function getExchangeOutUrl($name, $cfos=0)
    {
        $uri = '/user_exchange/coinout/coinname/'.$name;
        if($cfos){
            $uri = '/user_exchange/cfosout/coinname/'.$name;
        }
        return self::getBaseUrl().$uri;
    }
}
