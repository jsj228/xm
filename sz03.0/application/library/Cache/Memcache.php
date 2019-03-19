<?php
/**
 * MemCache 缓存
 */
class Cache_Memcache{
	private static $mem;

  /**
   * 构造函数
   */
  public function __destruct(){
		self::$mem['json']->quit();
	}

  /**
   * 单例接口
   * @param string $pConfig 服务器配置
   * @return Memcache
   */
  static function &instance($pConfig = 'json'){
		if (!isset(self::$mem[$pConfig]) || !self::$mem[$pConfig]){
			//配置
			$tMC = Yaf_Registry::get("config")->mc->$pConfig->toArray();
			//连接
			self::$mem[$pConfig] = new Memcached;
			if(self::$mem[$pConfig]->addServer($tMC['host'], $tMC['port'])) { 
      // if(self::$mem[$pConfig]->addServer('120.24.67.132', '11211')) { 
                self::$mem[$pConfig]->setOption(Memcached::OPT_COMPRESSION, false);
                self::$mem[$pConfig]->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
                self::$mem[$pConfig]->setOption(Memcached::OPT_RECV_TIMEOUT, 100);
                self::$mem[$pConfig]->setOption(Memcached::OPT_SEND_TIMEOUT, 100);
                self::$mem[$pConfig]->setOption(Memcached::OPT_POLL_TIMEOUT, 100);
                self::$mem[$pConfig]->setOption(Memcached::OPT_CONNECT_TIMEOUT, 100);
            } else {
                self::$mem[$pConfig] = null;
            }
		}
		return self::$mem[$pConfig];
	}

  /**
   * 取缓存
   * @param $pKey
   * @return array|string
   */
  static function get($pKey, $pField = ''){
    if(!$pField) return Cache_Memcache::instance()->get($pKey);
    if($tData = Cache_Memcache::instance()->get($pKey)){
      if(isset($tData[$pField])) return $tData[$pField];
    }
    return false;
  }

  /**
   * 取缓存
   * @param $pKey
   * @param $pVal
   * @param int $pExp
   * @return bool
   */
  static function set($pKey, $pVal, $pExp = 0){
    return Cache_Memcache::instance()->set($pKey, $pVal, $pExp);
  }
}
