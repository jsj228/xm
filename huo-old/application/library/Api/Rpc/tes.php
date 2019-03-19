<?

require_once 'Client.php';
 
  $bitcoin = new Api_Rpc_Client('http://ybcoinrpc:YuanBaoHuiYbcoin@127.0.0.1:8344/');

  echo "<pre>\n";
  print_r($bitcoin->getinfo());
  echo "</pre>";
?>
