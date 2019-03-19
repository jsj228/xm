<?php
define('CONFIG', 
	array(
		'mysql'=>array(
	    'host' => '172.66.88.78',//35.167.45.99
	    'port' => 3306,
	    'user' => 'root',//root
	    'pass' => '2008china',//2008china
	    'db' => 'weike',
	    'charset' => 'utf8', //指定字符集
	),
	'redis'=>array(
		'host' => '172.66.88.78',//18.188.16.3
		'port' => 6379,
		'auth' => '2008china',//
	),
	'weike_getCoreConfig'=>array(
		"auth" => "weike",
		"weike_indexcat" => ["CNY交易区"], /*用于交易市场的分类*/
		"weike_userTradeNum" => 20,/*普通用户组交易市场可以浏览显示买入卖出数据信息条数*/
		"weike_specialUserTradeNum" => 20,/*特殊用户组交易市场可以浏览显示买入卖出数据信息条数  只支持20 */
		"weike_userTradeDetailNum" => 50,/*普通用户组市场行情页面可以浏览显示买入卖出数据信息条数*/
		"weike_specialUserTradeDetailNum"=> 500,/*普通用户组行情页面可以浏览显示买入卖出数据信息条数*/
		"weike_opencoin"=> 1, /*是否开启钱包对接服务器模式*/
	),
));
