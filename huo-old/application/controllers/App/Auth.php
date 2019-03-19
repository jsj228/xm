<?php

class App_AuthController extends App_BaseController
{
    protected $_auth = 1;

    public function rpkeyAction()
    {
    	$path = CONF_PATH.'AppRsaPublic.key';
        is_file($path) and $data = file_get_contents($path);
        if(!$data)
        {
            $this->response($GLOBALS['MSG']['SYS_BUSY']);
        }
        $this->response('', 1, $data);
    }
}
