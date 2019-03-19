<?php

class Db_Mongo
{
    private static $instance;
    private $_conn;
    private $_db;
    private $options;


    private function __construct()
    {
        $config      = Yaf_Registry::get("config")->mongodb->toArray();
        $host        = 'mongodb://' . $config['host'] . ':' . $config['port'];
        $this->host  = $host;
        $this->_conn = new MongoDB\Driver\Manager($host, array('connectTimeoutMS'=>3000));
        $this->_db   = $config['db'];
    }

    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function insert($table, $data)
    {
        $bulk         = new MongoDB\Driver\BulkWrite;
        $_id          = $bulk->insert($data);
        $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        try {
            $result = $this->_conn->executeBulkWrite($this->_db . '.' . $table, $bulk, $writeConcern);
        }
        catch (Exception $e)
        {
            if($e->getMessage()!='norepl')
                die('MongoDB Error : '.$e->getMessage());
        }
        return $result;
    }

    public function select()
    {

        // $filter  = ['x' => ['$gt' => 1]];
        // $options = [
        //     'projection' => ['_id' => 0],
        //     'sort'       => ['x' => -1],
        // ];

        $options = array();
        $where   = array();

        if (!$this->options['table'])
        {
            throw new Exception('no table selected');
        }

        if ($this->options['where'] && is_array($this->options['where']))
        {
            $where = $this->options['where'];
        }

        if ($this->options['field'])
        {
            $options['projection'] = array_fill_keys(explode(',', $this->options['field']), 1);
        }

        if ($this->options['sort'] && is_array($this->options['sort']))
        {
            $options['sort'] = $this->options['sort'];
        }

        if ($this->options['db'])
        {
            $this->_db = $this->options['db'];
        }

        if ($this->options['limit'])
        {
            $limit            = explode(',', $this->options['limit']);
            if (isset($limit[1]))
            {
                $options['limit'] = abs($limit[1]);
                $options['skip']  = abs($limit[0]);
            }
            else
            {
                $options['limit'] = abs($limit[0]);
                $options['skip']  = 0;
            }
            if($options['limit']<=0)
            {
                throw new Exception("MongoDB Error : param limit error");    
            }
        }

        // 查询数据
        $query  = new MongoDB\Driver\Query($where, $options);
        $cursor = $this->_conn->executeQuery($this->_db . '.' . $this->options['table'], $query);

        $result = array();
        foreach ($cursor as $document)
        {
            $result[] = json_decode(json_encode($document), true);
        }
        return $result;
    }

    private function setOptions($action, $data)
    {
        if ($data && is_array($data))
        {
            foreach ($data as $k => $v)
            {
                $this->options[$action][$k] = $v;
            }
        }
        else
        {
            $this->options[$action] = $data;
        }
        return $this;
    }

    public function __call($pMethod, $pArgs)
    {
        if (in_array($pMethod, array('db','field', 'table', 'where', 'sort', 'limit'), true))
        {
            return $this->setOptions($pMethod, $pArgs[0]);
        }
        throw new Exception("no method " .$pMethod); 
    }


}
