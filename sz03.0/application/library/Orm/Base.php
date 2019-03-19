<?php
class Orm_Base{
	/**
	 * 数据库链接
	 * @var obj
	 */
	protected $db;

	protected static $staticDb = array();

	/**
	 * 查询参数
	 * @var array
	 */
	public $options = array();

	/**
	 * PDO 实例化对象
	 * @var object
	 */
	static $instance = array();
	static $instance_model = array();

	/**
	 * 配置
	 * @var string
	 */
	protected $_config;

	/**
	 * 错误信息
	 */
	public $error = array();

	/**
	 * 锁表语句
	 * @var string
	 */
	protected $_lock = '';

	/**
	 * 最后执行的sql语句
	 */
	protected $_lastSql = '';

	/**
	 * 最后执行的sql语句
	 */
	public static $lastSql = '';

	/**
	 * 错误日志
	 */
	protected $errorLog = '';
	/**
	 * 构造函数
	 */
	function __construct($pPK = 0, $pConfig = 'default', $connect='default'){

		$this->errorLog = APPLICATION_PATH.'/log/mysqlError';

		$this->_config and $pConfig = $this->_config;
		# 通过主键取出数据

		if($pPK && $pPK = abs($pPK)){
			if($tRow = $this->fRow($pPK)){
                foreach($tRow as $k1 => $v1){
                    $this->$k1 = $v1;
                }
			} else{
                foreach($this->field as $k1 => $v1){
                    $this->$k1 = false;
                }
			}
		}

		//没改变$connect参数，默认共用一个数据库连接
		$dbLinkName = sprintf('CONF%s_CONN%s', $pConfig, $connect);
		if(isset(self::$staticDb[$dbLinkName]) && self::$staticDb[$dbLinkName])
		{	
			$this->db = self::$staticDb[$dbLinkName];
		}
		else
		{
			$tDB = Yaf_Registry::get("config")->db->$pConfig->toArray();
	       
			$this->db = new PDO($tDB['dsn'], $tDB['username'], $tDB['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") );	
			//错误时抛出异常
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//
			//$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			self::$staticDb[$dbLinkName] = $this->db;

			
		}



	}

	/**
	 * 特殊方法实现
	 * @param string $pMethod
	 * @param array $pArgs
	 * @return mixed
	 */
	public function __call($pMethod, $pArgs){
		
		
		# 连贯操作的实现
		if(in_array($pMethod, array('field', 'table', 'where', 'order', 'limit', 'page', 'having', 'group', 'lock', 'distinct'), true)){
			$this->options[$pMethod] = $pArgs[0];
			return $this;
		}
		# 统计查询的实现
		if(in_array($pMethod, array('count', 'sum', 'min', 'max', 'avg'))){
			$field = isset($pArgs[0])? $pArgs[0]: '*';
			return $this->fOne("$pMethod($field)");
		}
		# 根据某个字段获取记录
		if('ff' == substr($pMethod, 0, 2)){
			return $this->where(array(strtolower(substr($pMethod, 2))=>$pArgs[0]))->fRow();
		}
	}

	/**
	 * 数据库连接
	 */
	static function &instance($pConfig = 'default'){

		if(empty(self::$instance[$pConfig])){  
			self::$instance[$pConfig] = new Orm_Base('', $pConfig);	 
		}
		
		return self::$instance[$pConfig];
	}
	
	
	
	
    /**
     * model实例化
     */
    public static function getInstance()
    {
        $class_now = get_called_class();
        if(empty(self::$instance_model[$class_now])){
            self::$instance_model[$class_now] = new $class_now;
        }
        return self::$instance_model[$class_now];
    }

	/**
	 * 过滤危险数据
	 * @param array $pData
	 */
	private function _filter(&$pData){
		foreach($pData as $k1 => &$v1){
			$v1 = strtr($v1, array('\\' => '', "'" => "\'"));
		}
		return $pData? true: false;
	}



	/**
	 * 查询参数
	 * @param mixed $pOpt
	 */
	private function _options($pOpt = array()){
		# 合并查询条件
		$tOpt = $pOpt? array_merge($this->options, $pOpt): $this->options;
		$this->options = array();
		# 数据表
		empty($tOpt['table']) && $tOpt['table'] = $this->table;
		empty($tOpt['field']) && $tOpt['field'] = '*';
		#  查询条件
        if(isset($tOpt['where']) && is_array($tOpt['where'])){
            $this->_varType($tOpt['where']);
		}
		return $tOpt;
	}


	/**
	 * 类型转换  
	 * @param mixed $data
	 */
	private function _varType(&$data=array())
	{
		if(is_array($data))
		{
			foreach($data as $k => &$v) 
			{
				$isArray = false;
                if((!is_scalar($v) && !$isArray = is_array($v)))
                {
                    unset($data[$k]);
                    continue;
                }
             	
             	if($isArray)
             	{
             		$thisVal = &$v[1];
             	}
             	else
             	{
             		$thisVal = &$v;
             	}

        		# 整型格式化
                if(false !== strpos($this->field[$k]['type'], 'int')){
                    $thisVal = intval($thisVal);
                }
                # 浮点格式化(不格式化，直接float精度会损失)
			}
		}
	}



	/**
	 * 执行SQL
	 */
	function exec($pSql){

		try
		{
			if($tReturn = $this->db->exec($pSql)){
				$this->error = array();
			}else{
				$this->error = $this->db->errorInfo();
				isset($this->error[1]) || $this->error = array();
			}
		}
		catch(Exception $e)
		{	
			$this->eHandle($e);
		}
		
		$this->_lastSql = $pSql;
		self::$lastSql = $pSql;
		return $tReturn;
	}

	/**
	 * 设置出错信息
	 * @param $pMsg 信息
	 * @param int $pCode 错误码
	 * @param string $pState SQL错误码
	 */
	function setError($pMsg, $pCode = 1, $pState = 'BTC001'){
		$this->error = array($pState, $pCode, $pMsg);
		return false;
	}

	/**
	 * 获取出错信息
	 */
	public function getError($index=null)
	{
		if(isset($index))
			return $this->error[$index];
		return $this->error;
	}

	/**
	 * 开启本次查询缓存
	 * @param str $pKey MemKey
	 * @param int $pExpire 有效期
	 */
	private $cache = array();
	function cache($pKey = 'md5', $pExpire = 86400){
		$this->cache['key'] = $pKey;
		$this->cache['expire'] = $pExpire;
		return $this;
	}

	/**
	 * 执行SQL，并返回结果
	 */
	function query(){
                
		$tArgs = func_get_args();

		$tSql = array_shift($tArgs);

		if($tArgs && is_array($tArgs[0]))
		{
			$tArgs = $tArgs[0];
		}

		# 锁表查询
		if($this->_lock) {
			$tSql.= ' '.$this->_lock;
			$this->_lock = '';
		}
		
		@$this->_lastSql = call_user_func_array('sprintf', array_merge(array(str_replace('?', '"%s"', $tSql)), (array)$tArgs));
		self::$lastSql = $this->_lastSql;

		# 使用缓存
		if($this->cache){
			$tMem = &Cache_Redis::instance('mysql');
			if('md5' == $this->cache['key']){
				$this->cache['key'] = md5($tSql . ($tArgs? join(',', $tArgs): ''));
			}
			if(false !== ($tData = $tMem->get($this->cache['key']))){
				return json_decode($tData, true);
			}
		}
		# 查询数据库
		try
		{
			if($tArgs){
				$tQuery = $this->db->prepare($tSql);
				$tQuery->execute($tArgs);
			} else{
				$tQuery = $this->db->query($tSql);
			}
		}
		catch(Exception $e)
		{	
			$this->eHandle($e);
		}
		
		if(!$tQuery) {
			$this->error = $this->db->errorInfo();
			isset($this->error[1]) || $this->error = array();
			return array();
		}
		$tData = $tQuery->fetchAll(PDO::FETCH_ASSOC);
		# 不缓存查询结果
		if(!$this->cache){
			return $tData;
		}
		# 设置缓存
		$tMem->set($this->cache['key'], json_encode($tData));
        $tMem->expire($this->cache['key'], $this->cache['expire']);
		$this->cache = array();
		return $tData;
	}

	/**
	 * 错误日志
	 */
	function errorLog($msg)
	{
		@Tool_Log::wlog($msg."\n", $this->errorLog, true);
	}


	/**
	 * 异常处理
	 */
	function eHandle($exception)
	{
		if (php_sapi_name() == "cli") 
		{
			$str = sprintf('[%s] error:%s, file:%s, line:%s, lastSql:%s'.PHP_EOL, date('m-d H:i'), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $this->getLastSql());
			Tool_Fnc::warning($str);
			sleep(3);
            echo $str;
            die;
        }
		$this->errorLog($exception);
	}

	/**
	 * 保存记录(自动区分 增/改)
	 */
	function save($pData){
		return isset($pData[$this->pk])? $this->update($pData): $this->insert($pData);
	}

	/**
	 * 添加记录
     * return lastid
	 */
	function insert($pData, $pReplace = false)
	{
		if($this->_filter($pData))
		{
			$tField = '`'.join('`,`', array_keys($pData)).'`';

			$tSign = array();
			$tVals = array();
			foreach($pData as $v)
			{
				$tSign[] = '?';
				$tVals[] = $v;
			}

			$tSign = join(',', $tSign);
			$tSql = ($pReplace? "REPLACE": "INSERT") . " INTO `$this->table`($tField) VALUES ($tSign)";
			if($this->_execute($tSql, $tVals))
			{
				return $this->db->lastInsertId();
			}
		}
		return 0;
	}

	 /**
     * 批量添加记录
     * return lastid
     */
    function batchInsert($pData, $pReplace = false)
    {
        $current = current($pData);
        $baseKey = array_keys($current);
        $tField  = '`'.join('`,`', $baseKey).'`';

        $tSign = array();
        $tVals = array();
        foreach($pData as $field=>$one)
        {
            if($this->_filter($one))
            {
                $tSignOne = array();
                foreach($baseKey as $key)
                {
                    if(!isset($one[$key]))
                    {
                        $this->setError('key values not match');
                        return false;
                    }
                    $tSignOne[] = '?';
                    $tVals[] = $one[$key];
                }

                $tSign[] = '(' . join(',', $tSignOne) . ')';
            }
        }
        $tSign = join(',', $tSign);
        $tSql = ($pReplace? "REPLACE": "INSERT") . " INTO `$this->table`($tField) VALUES $tSign";
        
        return $this->_execute($tSql, $tVals);
    }
	
	/**
	 * 添加记录
     * return lastid
	 */
	function add($pData, $pReplace = false){
		return $this->insert($pData, $pReplace);
	}

	/**
	 * 更新记录
	 */
	function update($pData){
		# 过滤
		if(!$this->_filter($pData)) return false;
		# 条件
		$tOpt = array();
		if(!$this->options['where'] && isset($pData[$this->pk])){
			$tOpt = array(
				'where' => array(
					$this->pk=>$pData[$this->pk],
				)
			);
		}
		$tOpt = $this->_options($tOpt);


		$pWhereVals = array();
		if($tOpt['where'] && is_array($tOpt['where']))
		{
			$pWhere = $this->getPrepareData($tOpt['where']);
			if($pWhere)
			{
				$tOpt['where'] = $pWhere['str'];
				$pWhereVals = $pWhere['values'];
			}
		}
		# 更新
		$tVals = array();
		if($pData && !empty($tOpt['where']))
		{
            $this->_varType($pData);

            foreach($pData as $k1 => $v1)
            {
                $tSet[] = "`$k1`=? ";
                $tVals[] = $v1;
            }
			
			$tSql = "UPDATE `" . $tOpt['table'] . "` SET " . join(',', $tSet) . " WHERE " . $tOpt['where'];
			return $this->_execute($tSql, array_merge_recursive($tVals, $pWhereVals));
		}
		return false;
	}


	/**
	 * 执行预处理语句
	 */
	private function _execute($sql, $values, $return='rowCount')
	{
		@$this->_lastSql = call_user_func_array('sprintf', array_merge(array(str_replace('?', '"%s"', $sql)), $values));
		self::$lastSql = $this->_lastSql;
		try
		{
			$tQuery = $this->db->prepare($sql);
			$result = $tQuery->execute($values);
		}
		catch(Exception $e)
		{	
			$this->eHandle($e);
		}

		if(!$result)
		{
			return $this->setError(implode(',', $tQuery->errorInfo()));
		}

		if($return=='rowCount')
		{
			return $tQuery->rowCount();
		}
		return $tQuery;
	}

	/**
	 * 删除记录
	 */
	function del(){
		if($tArgs = func_get_args()){
			# 主键删除
			$tSql = "DELETE FROM `$this->table` WHERE ";
			if(intval($tArgs[0]) || count($tArgs) > 1){
				return $this->exec($tSql.$this->pk . ' IN(' . join(',', array_map("intval", $tArgs)) . ')');
			}
			# 条件删除
			false === strpos($tArgs[0], '=') && exit('删除条件错误!');
			return $this->exec($tSql . $tArgs[0]);
		}
		# 连贯删除
		$tOpt = $this->_options();
		if(empty($tOpt['where'])) return false;

		$pWhereVals = array();
		if(is_array($tOpt['where']))
		{
			$pWhere = $this->getPrepareData($tOpt['where']);
			if($pWhere)
			{
				$tOpt['where'] = $pWhere['str'];
				$pWhereVals = $pWhere['values'];
			}
		}
		$tSql = "DELETE FROM `" . $tOpt['table'] . "` WHERE " . $tOpt['where'];
		return $this->_execute($tSql, $pWhereVals);
	}

	/**
	 * 查找一条
	 */
	function fRow($pId = 0){
		$pVals = array();
		if(false === stripos($pId, 'SELECT')){
			$tOpt = $pId? $this->_options(array('where' => $this->pk . '=' . abs($pId))): $this->_options();
			if($tOpt['where'] && is_array($tOpt['where']))
			{
				$pData = $this->getPrepareData($tOpt['where']);
				if($pData)
				{
					$tOpt['where'] = $pData['str'];
					$pVals = $pData['values'];
				}
			}

			$tOpt['where'] = empty($tOpt['where'])? '': ' WHERE ' . $tOpt['where'];
			$tOpt['order'] = empty($tOpt['order'])? '': ' ORDER BY ' . $tOpt['order'];
			$tSql = "SELECT {$tOpt['field']} FROM `{$tOpt['table']}` {$tOpt['where']} {$tOpt['order']}  LIMIT 0,1";
		} else {
			$tSql = &$pId;
		}
		if($tResult = $this->query($tSql, $pVals)){
			return $tResult[0];
		}
		return array();
	}

	/**
	 * 查找一字段 ( 基于 fRow )
	 *
	 * @param string $pField
	 * @return string
	 */
	function fOne($pField){
		$this->field($pField);
		if(($tRow = $this->fRow()) && isset($tRow[$pField])){
			return $tRow[$pField];
		}
		return false;
	}

	/**
	 * 查找多条
	 */
	function fList($pOpt = array()){
		if(!is_array($pOpt)){
			$pOpt = array('where' => $this->pk . (strpos($pOpt, ',')? ' IN(' . $pOpt . ')': '=' . $pOpt));
		}
		$tOpt = $this->_options($pOpt);
		$tSql = "SELECT {$tOpt['field']} FROM  `{$tOpt['table']}`";
		$this->join && $tSql .= implode(' ', $this->join);

		$pVals = array();
		if(isset($tOpt['where']) && $tOpt['where'] && is_array($tOpt['where']))
		{
			$pData = $this->getPrepareData($tOpt['where']);
			if($pData)
			{
				$tOpt['where'] = $pData['str'];
				$pVals = $pData['values'];
			}
		}

		empty($tOpt['where']) || $tSql .= ' WHERE ' . $tOpt['where'];
		empty($tOpt['group']) || $tSql .= ' GROUP BY ' . $tOpt['group'];
		empty($tOpt['order']) || $tSql .= ' ORDER BY ' . $tOpt['order'];
		empty($tOpt['having']) || $tSql.= ' HAVING '.$tOpt['having'];
		empty($tOpt['limit']) || $tSql .= ' LIMIT ' . $tOpt['limit'];
		return $this->query($tSql, $pVals);
	}


	/**
	 * 转成预处理格式
	 */
	function getPrepareData($where=array())
	{
		if(!is_array($where))
			return $this->setError('where 格式错误');

		$pWhere = array();
		$pVals  = array();
		foreach($where as $k => $v)
		{
			if(is_scalar($v))
			{
				$pWhere[] = sprintf(' `%s` = ?', $k);
				$pVals[] = $v;
			}
			elseif(is_array($v))
			{
				$pWhere[] = sprintf(' `%s` %s ?', $k, $v[0]);
				$pVals[] = $v[1];
			}
		}
		$pStr = implode(' and ', $pWhere);

		return array('str'=>$pStr, 'values'=>$pVals);
	}


	/**
	 * 查询并处理为哈西数组 ( 基于 fList )
	 *
	 * @param string $pField
	 * @return array
	 */
	function fHash($pField){
		$this->field($pField);
		$tList = array();
		$tField = explode(',', $pField);
		if(2 == count($tField)) foreach($this->fList() as $v1) $tList[$v1[$tField[0]]] = $v1[$tField[1]];
		else foreach($this->fList() as $v1) $tList[$v1[$tField[0]]] = $v1;
		return $tList;
	}

	/**
	 * 库 > (所有)数据表
	 * @return array
	 */
	function getTables(){
		return $this->db->query("SHOW TABLES")->fetchAll(3);
	}

	/**
	 * 数据表 > (所有)字段
	 * @return array
	 */
	function getFields($pTable){

		$tQuery = $this->db->query("SHOW FULL FIELDS FROM `$pTable`");
		return $tQuery? $tQuery->fetchAll(2): array();
	}

	public $join = array();

	function join($pTable, $pWhere, $pPrefix = ''){
		$this->join[] = " $pPrefix JOIN `$pTable` ON $pWhere ";
		return $this;
	}

	/**
	 * 事务开始
	 */
	private $_begin_transaction = false;
	function begin(){
		# 已经有事务，退出事务
		$this->back();
		if(!$this->db->beginTransaction()){
			return false;
		}
		$this->_begin_transaction = true;
		return true;
	}

	/**
	 * 事务提交
	 */
	function commit(){
		if($this->_begin_transaction) {
			$this->_begin_transaction = false;
			$this->db->commit();
		}
		return true;
	}

	/**
	 * 事务回滚
	 */
	function back(){
		if($this->_begin_transaction) {
			$this->_begin_transaction = false;
			$this->db->rollback();
		}
		return false;
	}

	function getLastSql()
	{
		return $this->_lastSql;	
	}

	/**
	 * 锁表
	 */
	function lock($pSql = 'FOR UPDATE'){
		$this->_lock = $pSql;
		return $this;
	}


	/**
	 * 分页
	 */
	public function page($page, $size)
	{
		$page = intval($page);
		$size = intval($size);
		$this->options['limit'] = (($page-1)*$size).','.$size;
		return $this;
	}

}
