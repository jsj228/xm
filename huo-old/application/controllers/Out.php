<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/12/27
 * Time: 10:48
 */

class OutController extends Ctrl_Base
{
    protected $_auth = 1;

    /**
     * 首页
     */
    public function indexAction()
    {
        $exchangeModel = new Exchange_BtcModel();
        $exchangeModel->exec('set autocommit=0');
        $exchangeModel->exec("lock tables user write,exchange_btc write");

        $exchangeModel->exec('unlock tables');
        $id = $exchangeModel->where(['id'=>985])->update(['admin'=>5]);
        $exchangeModel->exec('rollback');

        sleep(5);
        echo $exchangeModel->getLastSql();die;


        die;

    }

}