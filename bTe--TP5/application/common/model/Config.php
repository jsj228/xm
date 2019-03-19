<?php
namespace app\common\model;

use think\Model;
use think\Db;

class Config extends Model
{
    protected $key = 'home_config';

    public function getConfig($flush = false){
        $config = (config('app_debug') || $flush) ? null : cache($this->key);
        if (!$config) {
            $config = Db::name('config')->where(array('id' => 1))->find();
            cache($this->key, $config);
        }

        return $config;
    }
}

?>