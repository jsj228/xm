<?php

class Ajax_PushController extends Ajax_BaseController
{
    # 启用 SESSION
    protected $_auth = 1;

      //WebSocket消息存sql
     public  function  pushAction()
     {
         if (empty($this->mCurUser))
         {
             $data['reUrl'] = '/?login';
             $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
         }
         $tData = Tool_Request::post();

         $time=time();
      
         $webMo = new ChatModel();

         // 校验数据
         $valMo = new ValidatelogicModel();
         $result = $valMo->scene("pushComment")->check($tData);
         if(!$result||$valMo->getError())
         {
             $this->ajax($valMo->getError(),0);
         }
         $tMO     = new Tool_UploadOne();
         $img=$tMO->uploadOne($tData['img'],'url');
         //组装数据
         $tData = array(
             'mo' => $_SESSION['user']['mo'],
             'themessage' => $tData['themessage'],
             'created' =>$time,
             'img'=>$img
         );
         $mo = substr_replace($_SESSION['user']['mo'], '****', 3, 4);
         $list = $mo.':'.$tData['themessage'];
         if (!$webMo->insert($tData))
         {
             $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL']);//资料提交失败,请重試
         }
         else
         { //show($webMo->getLastSql());
             //插入数据库成功发送消息至服务器
             $message = new Tool_Push();
             //参数一聊天室频道:二是消息
            // show($list);
             $message->send('Thechatroom',$list);
             $this->ajax('', 1);
         }
     }





}
