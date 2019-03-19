<?php
header("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set('PRC'); //设置中国时区
$domain = "http://127.0.0.1";//填写网站域名
$queues = array(
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/queue_3a32849e0c77173c325c72a3c2d7aa49', //后台计算执行时间
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/chartb8c3b3d94512472db8',           //计算行情
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/tendencyb8c3b3d94512472db8',        //计算趋势
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/houpriceb8c3b3d94512472db8',        //最后的价格
//    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7',       //比特币系列轮询
//    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8',       //以太币系列轮询
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db9',        //btm主链轮行
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaosync/coin/eth',              //ETH 币种归集
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaosync/coin/etc',              //ETC 币种归集
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaosync/coin/btm',              //BTM 币种归集
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaosync/coin/sie',              //sie 币种归集
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/marketandcoinb8c3b3d94512472db8',   //市场和币种
    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/set_api_hign_or_low_price',         //市场和币种
//    '158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/checkYichang',                      //调整不正常的委单
);
for ($i = 0; $i < count($queues); $i++) {
    http_get($domain . "/" . $queues[$i]);
}
echo "success4792";

file_put_contents(dirname(__FILE__)."/market.html", date("Y-m-d H:i:s",time()));

function http_get($url)
{
    $oCurl = curl_init();
    curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($oCurl, CURLOPT_TIMEOUT, 10);
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if (intval($aStatus["http_code"]) == 200) {
        return true;
    } else {
        return false;
    }
}
