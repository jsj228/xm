<?php
class Ajax_CommonController extends Ajax_BaseController
{
    # 启用 SESSION
    protected $_auth = 1;

    /*
    * 前端请求失败日志
    */
    public function reqFailedLogAction()
    {
    	if(!$_POST['reqUrl'] || !$_POST['response'])
    	{
    		$this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 1);
    	}

    	$reqUrl   = $_POST['reqUrl'];
    	$param    = $_POST['param']?:'';
    	$response = $_POST['response'];

    	$data = array(
				'uid' =>isset($this->mCurUser['uid'])?$this->mCurUser['uid']:0,
		        'req_url' =>(string)$reqUrl,
		        'param' =>(string)$param,
		        'response' =>(string)$response,
		        'sql' => '',
		        'session' =>isset($_SESSION)?json_encode($_SESSION):'',
		        'req_time' =>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
		        'req_ip' =>Tool_Fnc::realip(),
		        'created' =>date('Y-m-d H:i:s'),
			);
		Cache_Redis::instance()->lpush('sqlQueue', json_encode(array('model'=>'ReqFailedLogModel', 'data'=>$data)));

		$this->ajax('' , 1);
    }

}