<?php
/**
 * Created by PhpStorm.
 * User: wqdkv
 * Date: 2017/9/12
 * Time: 17:52
 */
namespace Admin\Controller;



class OrderController extends AdminController
{

    public function index()
    {
        $se_type = I("type/s");
        $se_huifu = I("huifu/d");     //1 未回复  2 已回复
        $se_name = I("name/s");
        $where = [];

        if ($se_type) {
            $where['requestType'] = $se_type;
        }
        if ($se_huifu){
            $where['wt_type'] = $se_huifu - 1;
        }
        if($se_name) {
            $where['emailAdd'] = array('like', "%" . $se_name . "%");
        }

        $count = M('order')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('order')
            ->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,attrimg,wt_type")
            ->where($where)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        foreach ($list as $key => $value){
            $list[$key]['attrimg'] = explode("***", $list[$key]['attrimg']);
            $list[$key]['description'] = explode(",", $list[$key]['description']);
            $list[$key]['addtime'] = explode(",", $list[$key]['addtime']);
            for ($i = 0; $i < count($list[$key]['addtime']); $i++) {
                $list[$key]['addtime'][$i] = date("Y-m-d H:i:s", $list[$key]['addtime'][$i]);
            }
        }

        $this->assign("list", $list);
        $this->assign("page", $show);
        $types = M('order_manage')->field("type")->group('type')->select();
        $this->assign("types", $types);
        $this->display();

    }

    public function ajax(){
        $se_type=I("get.type");
        //1 未回复  2 已回复
        $se_huifu=I("get.huifu");
        $se_name=I("get.name");
        $success="";
        if($se_huifu == 1 ){
//            $se_huifu=array('exp', 'IS NULL');
            $wt_type = 0;
        }else if($se_huifu ==2){
//            $se_huifu=array('neq','');
            $wt_type = 1;
        }
        $where=array(
            'requestType' => $se_type,
//            'huifudesc' => $se_huifu,
            'wt_type' => $wt_type,
            'emailAdd' => array('like', "%".$se_name."%")
        );
        if($se_huifu == "0"){
            unset($where['wt_type']);
        }
        if($se_type == ""){
            unset($where['requestType']);
        }
        $count = M('order')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('order')->where($where)->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,attrimg,addtime,huifudesc,wt_type")->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $key => $value) {
            $success.="<tr>";
            $success.='<td><input class="ids" type="checkbox" name="id[]" value="'.$value['id'].'"/></td>';
            $success.='<td>'.$value['id'].'</td>';
            $success.='<td>'.$value['email'].'</td>';
            $success.='<td>'.$value['title'].'</td>';
            $success.='<td>'.$value['type'].'</td>';
//            $str=explode("***",$list[$key]['attrimg']);
            $success.='<td></td>';
            $success.='<td>'.$value['attrimg'].'</td>';
            $value['addtime'] = explode(',',$value['addtime']);
            $cut = count($value['addtime']);
            for($i = 0;$i<$cut;$i++){
                $value['addtime'][$i] = addtime($value['addtime'][$i]);
            }
            $success.='<td>'.$value['addtime'][$cut-1].'</td>';
            if($value['wt_type'] == 1){
                $success.="<td>已解决</td>";
            }else{
                $success.="<td>未解决</td>";
            }
            $success.='<td><a href="'.__MODULE__.'/Order/huifuorder/id/'. $value['id'].'" class="btn btn-primary btn-xs">回复</a></td>';
            $success.="</tr>";
        }
        echo json_encode(array('success'=>$success,'page'=>$show));
    }


    public function huifuorder(){
        $id=I("id/d");
        $order = D("Order");
        $data = $order->get_order_info($id);
        //图片
        if($data['attrimg']){
            $img_arr = explode("_",$data['attrimg']);
//            dump($img_arr);die;
            $imgstr = '';
            foreach ($img_arr as $k => $v){

                $imgstr = $imgstr.'<li style="height:100px;"><img style="width:300px;height:100px;" src="'.C('TMPL_PARSE_STRING.__DOMAIN__').'/Upload/order/'.$data['uid'].'/'.$v.'" /></li>';
            }
            $this->assign('imgstr', $imgstr);
        }
        $this->assign("data", $data);
        if (IS_POST) {
            $_POST = I('post./a');

            $search_huifu = M("order")->field("id,huifudesc,huifutime,adminuser")->where(["id"=>$_POST['id']])->select();
            $content = I("post.content");
            if(empty($content)){
                $this->error('回复内容必填');
            }
            //匹配进行替换
            $requestDescription = preg_replace("/(,)/" ,'，' ,$content);
            //判断客服是否回复
            if(!$search_huifu[0]['huifudesc'] && !$search_huifu[0]['huifutime']){
                $list['huifudesc'] = $requestDescription ;
                $list['huifutime'] = time();
                $list['adminuser'] = session('admin_username');
                $list['wt_type'] = 1;
                if (M("order")->where(['id'=>$id])->save($list)){
                    $this->success("回复成功");
                }else {
                    $this->error("回复失败");
                }
            }else{
                $_POST = I('post./a');
                $huifu_arr =explode(',',$search_huifu[0]['huifudesc']);
                array_push($huifu_arr,$requestDescription);
                $huifu_str =implode(',',$huifu_arr);
                $huifutime_arr = explode(',',$search_huifu[0]['huifutime']);
                array_push($huifutime_arr,time());
                $hf_time_str = implode(',',$huifutime_arr);
                if($search_huifu[0]['adminuser']){
                    $adminuser_array = explode(',',$search_huifu[0]['adminuser']);
                    array_push($adminuser_array,session("admin_username")); 
                    $adminuser = implode(',',$adminuser_array);
                }else{
                    $adminuser = session('admin_username');
                }
                if (M("order")->where(['id'=>$id])->save(['huifudesc'=>$huifu_str,'huifutime'=>$hf_time_str,'adminuser'=>$adminuser,'wt_type'=>1])){
                    $this->success("回复成功");
                }else {
                    $this->error("回复失败");
                }
            }
            exit;
        }
        $this->display();
    }

    public function del()
    {
        $id=I("get.id");
        $order=M("order");
        if($order->delete($id)){
            $this->success("删除成功",U("index"));
          }else{
            $this->error("删除失败");
          }
    }

    //工单常见问题管理
    public function manage()
    {
        $type = I("type/s");
        if ($type) {
            $where['type'] = array('like', '%' . $type . '%');
            $count = M('order_manage')->where($where)->count();
            $Page = new \Think\Page($count, 15);
            $show = $Page->show();
            $list = M('order_manage')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $this->assign("type", $type);
        } else {
            $count = M('order_manage')->count();
            $Page = new \Think\Page($count, 15);
            $show = $Page->show();
            $list = M("order_manage")->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        }

        foreach ($list as $key => $val) {
            $list[$key]['problem'] = mb_substr($val['problem'], 0, 30, 'utf8');
            $list[$key]['answer'] = mb_substr($val['answer'], 0, 30, 'utf8');
        }

        $this->assign("list", $list);
        $this->assign("page", $show);
        $this->display();;
    }

    //工单常见问题增加修改
    public function order_edit()
    {
        $id = I('id/d');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $order = M('order_manage')->where(array('id' => trim($id)))->find();
                $this->data = $order;
            }
            $this->display();
        } else {
            $_POST = I('post./a');
            $_POST['addtime'] = time();
            if (isset($_POST['id'])) {
                $rs = M('order_manage')->save($_POST);
            } else {
                $rs[] = M('order_manage')->add($_POST);
            }

            if ($rs) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        }
    }
    
    //工单常见问题删除
    public function order_del()
    {
        $id = I('id/d');
        if (isset($id)) {
            $rs = M('order_manage')->where(['id' => $id])->delete();

            if ($rs) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        }
    }
}