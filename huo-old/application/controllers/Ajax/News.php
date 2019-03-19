<?php

class Ajax_NewsController extends Ajax_BaseController
{
    # 启用 SESSION
    protected $_auth = 1;

    // 发表评论
    public function newsCommentAction()
    {
        // 判断是否登录
        $this->_ajax_islogin();
        //安全校验
        $this->checkReqToken();

        $auMo = new AutonymModel();
        $aulist=$auMo->where('status=2 and uid='. $this->mCurUser['uid'] )->fList();
        if(empty($aulist)){
            $this->ajax($GLOBALS['MSG']['AUTONYM_COMMENT'], 0);//請先實名再評論
        }

        if(Tool_Request::method()=="POST")
        {
            // 接收数据
            $pdata = $_POST?$_POST:$_GET;
            // 校验数据
            $valMo = new ValidatelogicModel();
            $result = $valMo->scene("newsComment")->check($pdata);
            if(!$result||$valMo->getError())
            {
                $this->ajax($valMo->getError(),0,$_POST);
            }
            unset($pdata['reqToken']);
            $pdata['mo'] = $this->mCurUser['mo'];
            $pdata['area'] = $this->mCurUser['area'];
            $pdata['email'] = $this->mCurUser['email'];
            $pdata['uid']   = $this->mCurUser['uid'];
            if(!$pdata['mo']&&!$pdata['email'])
            {
                $this->ajax($GLOBALS['MSG']['LOGIN_COMMENT'],0);//請先登錄再評論
            }
            $pdata['created'] = time();


            $newComMo = new News_CommentModel();
            if($pdata['mo'])
            {
                $backlistData=$newComMo->where("mo=$pdata[mo] and area='$pdata[area]' and backlist=1")->fList();

            }
            else
            {
                $backlistData=$newComMo->where("email=$pdata[email]  and backlist=1")->fList();
            }
            //$newComMo->getLastSql($backlistData);
            //$backlistData = $newComMo->where(array('mo'=>$pdata['mo'], 'backlist'=>1))->fList();
            if(!empty($backlistData)){//如果已拉黑
                $pdata['backlist']=1;
                $pdata['shield'] = 1;
            }
            // 插入数据
            if($newComMo->insert($pdata))
            {
                $this->ajax($GLOBALS['MSG']['D_SUCCESS'],1);//成功
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['D_FAIL'],0);//失败
            }
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['REQUEST_ERROR'],0);//请求错误
        }
    }

    /**
     * 看看评论
     */
    public function commentListAction()
    {
        if($id = intval($_GET['id']))
        {
            $comMo = new News_CommentModel();
            $data = $comMo->field('email,area,mo,nid,content,created,shield')->where("nid = ".$id)->order('created asc')->fList();
            $arr=array();

            if ($this->mCurUser['mo'])//登录
            {
                foreach ($data as $key =>&$v)
                {
                    if ($v['shield'] == 1&& !($v['mo']== $this->mCurUser['mo']&&$v['area'] == $this->mCurUser['area']) )//过滤掉屏蔽的并且不是自己的评论
                    {
                        unset($data[$key]);
                    }
                    else{
                        $v['mo'] = substr($v['mo'], 0, 3) . '****' . substr($v['mo'], 7, 4);
                        array_push($arr, $data[$key]);
                    }
                }
            }
            if ($this->mCurUser['email'])//登录
            {
                foreach ($data as $key=>&$v)
                {
                    if ($v['shield'] == 1&& !($v['email']== $this->mCurUser['email']) )//过滤掉屏蔽的并且不是自己的评论
                    {
                        unset($data[$key]);
                    }
                    else{
                        $email_array = explode("@", $v['email']);
                        $not= substr_replace($email_array[0], '****', 3);
                        $v['email']= $not.'@'.$email_array[1];
                        array_push($arr, $data[$key]);
                    }
                }
            }
            else//未登录
            {
                foreach ($data as $key => &$v)
                {
                    if ($v['shield'] == 1)//过滤掉屏蔽的评论
                    {
                        unset($data[$key]);
                    }
                    else
                    {
                        $v['mo'] = substr($v['mo'], 0, 3) . '****' . substr($v['mo'], 7, 4);
                        array_push($arr, $data[$key]);
                    }
                }
            }
            $this->ajax($GLOBALS['MSG']['D_SUCCESS'], 1, $arr);//成功
        }
        else
        {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'],0);//参数错误
        }

    }
}
