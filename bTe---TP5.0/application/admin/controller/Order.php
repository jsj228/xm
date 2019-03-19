<?php

namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;


class Order extends AdminCommon
{

    public function index()
    {
        $se_type = input("type");
        $se_huifu = input("huifu");     //1 未回复  2 已回复
        $se_name = input("name");
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
        $list = DB::name('order')
            ->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,attrimg,wt_type")
            ->where($where)
            ->order('id desc')
            ->paginate(15);
        $page = $list->render();
         $list = $list->all();
        foreach ($list as $key => $value){
            $list[$key]['attrimg'] = explode("***", $list[$key]['attrimg']);
            $list[$key]['description'] = explode(",", $list[$key]['description']);
            $list[$key]['addtime'] = explode(",", $list[$key]['addtime']);
            for ($i = 0; $i < count($list[$key]['addtime']); $i++) {
                // if($list[$key]['addtime'][0]==','){
                //     unset($list[$key]['addtime'][0]);
                // }
                $list[$key]['addtime'][$i] = date("Y-m-d H:i:s", $list[$key]['addtime'][$i]);
            }
        }

        $this->assign("list", $list);
        $this->assign("page", $page);
        $types = DB::name('order_manage')->field("type")->group('type')->select();
        $this->assign("types", $types);
        return $this->fetch();

    }

    public function ajax(){
        $se_type=input("get.type");
        //1 未回复  2 已回复
        $se_huifu=input("get.huifu");
        $se_name=input("get.name");
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
        $list = DB::name('order')->where($where)->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,attrimg,addtime,huifudesc,wt_type")->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
         $page = $list->render();
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
            $success.='<td><a href="'.request()->module().'/Order/huifuorder/id/'. $value['id'].'" class="btn btn-primary btn-xs">回复</a></td>';
            $success.="</tr>";
        }
        echo json_encode(array('success'=>$success,'page'=>$page));
    }


    public function huifuorder(){
        $id=input("id");
        $order = model("Order");
        $data = $order->get_order_info($id);
        $data = $data->toArray();

        //图片
        if($data['attrimg']){
            $img_arr = explode("_",$data['attrimg']);
            $imgstr = '';
            foreach ($img_arr as $k => $v){

                $imgstr = $imgstr.'<li style="height:100px;"><img style="width:300px;height:100px;" src="'.config('TMPL_PARSE_STRING.__DOMAIN__').'/Upload/order/'.$data['uid'].'/'.$v.'" /></li>';
            }

            $this->assign('imgstr', $imgstr);

        }
        $this->assign("data", $data);

        if ($this->request->isPost()) {
           
            $_POST = input('post.');
            // dump($_POST);die;
            $search_huifu = DB::name("order")->field("id,huifudesc,huifutime,adminuser")->where(["id"=>$_POST['id']])->select();
            $content = input("post.content");
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
                if (DB::name("order")->where(['id'=>$id])->update($list)){
                    $this->success("回复成功");
                }else {
                    $this->error("回复失败");
                }
            }else{
                $_POST = input('post.');
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
                if (DB::name("order")->where(['id'=>$id])->update(['huifudesc'=>$huifu_str,'huifutime'=>$hf_time_str,'adminuser'=>$adminuser,'wt_type'=>1])){
                    $this->success("回复成功");
                }else {
                    $this->error("回复失败");
                }
            }
            exit;
        }
        return $this->fetch();
    }

    public function del()
    {
        $id=input("get.id");
        $order=DB::name("order");
        if($order->delete($id)){
            $this->success("删除成功",url("index"));
          }else{
            $this->error("删除失败");
          }
    }

    //工单常见问题管理
    public function manage()
    {
        $type = input("type");
        if ($type) {
            $where['type'] = array('like', '%' . $type . '%');
            $list = DB::name('order_manage')->where($where)->order('id desc')->paginate(15);
            $page = $list->render();
            $this->assign("type", $type);
        } else {

            $list = DB::name("order_manage")->order("id desc")->paginate(15);
            $page = $list->render();
        }
        $list = $list->all();
        foreach ($list as $key => $val) {
            $list[$key]['problem'] = mb_substr($val['problem'], 0, 30, 'utf8');
            $list[$key]['answer'] = mb_substr($val['answer'], 0, 30, 'utf8');
        }

        $this->assign("list", $list);
        $this->assign("page", $page);
        return $this->fetch();
    }

    //工单常见问题增加修改
    public function order_edit()
    {
        $id = input('id');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $order = DB::name('order_manage')->where(array('id' => trim($id)))->find();
                $this->assign('data',$order);
            }
           return $this->fetch();
        } else {
            $_POST['addtime'] = time();
            if ($id) {
                $rs = DB::name('order_manage')->where(['id'=>$id])->update($_POST);
                if ($rs) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                $rs[] = DB::name('order_manage')->insert($_POST);
                if ($rs) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
           
        }
    }
    
    //工单常见问题删除
    public function order_del()
    {
        $id = input('id');
        if (isset($id)) {
            $rs = DB::name('order_manage')->where(['id' => $id])->delete();

            if ($rs) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        }
    }
}