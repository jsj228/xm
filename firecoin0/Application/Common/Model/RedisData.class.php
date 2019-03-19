<?php

namespace Common\Model;
abstract class RedisData
{
    protected $redis;
    public $market;
    public $queue_name;

    public function __construct($config, $market, $queue_name)
    {
        $this->redis = new \Redis();
        $this->redis->connect($config['host'], $config['port']);
        $this->redis->auth($config['auth']);
        $this->market = $market;
        $this->queue_name = $queue_name;
    }

    //左侧进入队列
    public function left_add_data($data)
    {
        //存储数据到列表中
        $this->redis->lpush($this->queue_name, json_encode($data));
    }

    //右侧进入队列
    public function right_add_data($data)
    {
        $this->redis->rpush($this->queue_name, json_encode($data));
    }

    //读取左侧第一个元素
    public function read_first_data()
    {
        return $this->redis->lrange($this->queue_name, 0, 0);
    }

    //读取右侧第一个元素
    public function read_last_data()
    {
        return $this->redis->lrange($this->queue_name, -1, -1);
    }

    //通过索引读取值
    public function read_index_data($index)
    {
        return $this->redis->lindex($this->queue_name, $index);
    }

    //取出左侧第一个元素
    public function get_first_data()
    {
        return $this->redis->lpop($this->queue_name);
    }

    //取出右侧第一个元素
    public function get_last_data()
    {
        return $this->redis->rpop($this->queue_name);
    }

    //重写队列
    abstract function init();

    //获取队列中所有元素
    public function get_all_data()
    {
        return $this->redis->lrange($this->queue_name, 0, -1);
    }

    //添加元素某元素前
    /*public function add_index_befor($index,$new_data){
        $old_data = $this->read_index_data($index);
        $this->redis->linsert($this->queue_name,'before',$old_data,json_encode($new_data));
    }*/

    //移除值
    public function del_index_data($index)
    {
        $old_data = $this->read_index_data($index);
        $this->redis->lRem($this->queue_name, $old_data, 0);
    }

    //获取列表长度
    public function get_list_len()
    {
        return $this->redis->llen($this->queue_name);
    }
}