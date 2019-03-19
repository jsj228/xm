<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/16
 * Time: 10:32
 */

class OtcController extends Ctrl_Base
{

    protected $_auth = 1;

    function init(){
        parent::init();
        $this->assign('pageName', $this->_request->action);
    }

    //首页
    public function indexAction(){
        $repeat_del = md5(time().rand(10000,9999));;
        $_SESSION['repeat_del'] = $repeat_del;
        $this->assign("repeat_del",$repeat_del);
        if($this->mCurUser['uid']) Tool_Session::mark($this->mCurUser['uid']);
    }
}



