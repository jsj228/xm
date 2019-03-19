<?php
/**
 * 队列接口
 */

namespace app\common\model;

use think\Model;
class Redis extends Model
{
    protected $redis;
    public $queue_name;
    public $model;
    public $market;
    public $type;

    public function __construct($market, $type)
    {
        $this->redis = new \Redis();
        $this->redis->connect(REDIS_HOST, REDIS_PORT);
        $this->redis->auth(REDIS_AUTH);

        $this->model = new Origin();
        $this->queue_name = $market . '_' . $type;
        $this->market = $market;
        $this->type = $type;
    }

    //重新实例化市场 队列
    public function boss()
    {
        $this->redis->del($this->queue_name);
        $data = $this->model->getTrade($this->market, $this->type);
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $this->right_add_data($data[$i]);
            }
        }
        return $this->get_list_len();
    }

    //获取Redis中的值
    public function get_redis($key)
    {
        return json_decode($this->redis->get($key), true);
    }

    //设置Redis中的值
    public function set_redis($key, $value)
    {
        return $this->redis->set($key, json_encode($value));
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

    //获取队列中所有元素
    public function get_all_data()
    {
        return $this->redis->lrange($this->queue_name, 0, -1);
    }

    //添加元素某元素前
    public function add_index_befor($index,$new_data){
        $old_data = $this->read_index_data($index);
        $this->redis->lInsert($this->queue_name,'before', $old_data, json_encode($new_data));
    }

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
