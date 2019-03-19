<?php
/**
 *  静态资源
 */
class SqlController extends Ctrl_Base
{
    protected $_auth = 0;

    //测试
    public function indexAction()
    {
        $exchangeModel = new Exchange_BtcModel();
        $res = $exchangeModel->fRow();

        Tool_Out::p($res);die;

        $id = $exchangeModel->where(['id'=>985])->update(['admin'=>3]);

        echo $exchangeModel->getLastSql();die;

    }
}