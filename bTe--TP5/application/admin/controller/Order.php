<?php
/**
 * Created by PhpStorm.
 * User: wqdkv
 * Date: 2017/9/12
 * Time: 17:52
 */
namespace app\admin\controller;



use think\Db;

class Order extends Admin
{

    public function index()
    {
        $se_type = input("type/s");
        $se_huifu = input("huifu/d");     //1 未回复  2 已回复
        $se_name = input("name/s");
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

        $list = Db::name('order')
            ->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,attrimg,wt_type")
            ->where($where)
            ->order('id desc')
            ->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
                $item['attrimg'] = explode("***", $item['attrimg']);
                $item['description'] = explode(",", $item['description']);
                $item['addtime'] = explode(",", $item['addtime']);
                $addtime_count = count($item['addtime']);
                for ($i = 0; $i < $addtime_count; $i++) {
                    $int_date = (int)$item['addtime'][$i];
                    if($int_date >0){
                        $item['addtime'][$i] = date("Y-m-d H:i:s", $int_date);
                    }

                }
                return $item;
            });
        $show = $list->render();


        $this->assign("list", $list);
        $this->assign("page", $show);
        $types = Db::name('order_manage')->field("type")->group('type')->select();
        $this->assign("types", $types);
        return $this->fetch();

    }

    public function ajax(){
        $se_type=input("param.type");
        //1 未回复  2 已回复
        $se_huifu=input("param.huifu");
        $se_name=input("param.name");
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

        $list = Db::name('order')->where($where)->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,attrimg,addtime,huifudesc,wt_type")->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key) use($success){
            $success.="<tr>";
            $success.='<td><input class="ids" type="checkbox" name="id[]" value="'.$item['id'].'"/></td>';
            $success.='<td>'.$item['id'].'</td>';
            $success.='<td>'.$item['email'].'</td>';
            $success.='<td>'.$item['title'].'</td>';
            $success.='<td>'.$item['type'].'</td>';
//            $str=explode("***",$list[$key]['attrimg']);
            $success.='<td></td>';
            $success.='<td>'.$item['attrimg'].'</td>';
            $value['addtime'] = explode(',',$item['addtime']);
            $cut = count($item['addtime']);
            for($i = 0;$i<$cut;$i++){
                $item['addtime'][$i] = addtime($item['addtime'][$i]);
            }
            $success.='<td>'.$item['addtime'][$cut-1].'</td>';
            if($item['wt_type'] == 1){
                $success.="<td>已解决</td>";
            }else{
                $success.="<td>未解决</td>";
            }
            $success.='<td><a href="'.__MODULE__.'/Order/huifuorder/id/'. $value['id'].'" class="btn btn-primary btn-xs">回复</a></td>';
            $success.="</tr>";
            return $item;
        });
        $show = $list-render();


        echo json_encode(array('success'=>$success,'page'=>$show));
    }


    public function huifuorder(){
        $id=input("id/d");
        $order = model("Order");
        $data = $order->get_order_info($id);
        //图片
        $imgstr='';
        if($data['attrimg']){
            $img_arr = explode("_",$data['attrimg']);
            foreach ($img_arr as $k => $v){
                $imgstr = $imgstr.'<li style="height:100px;"><img style="width:300px;height:100px;" src="'.config('view_replace_str.__DOMAIN__').'/Upload/order/'.$data['uid'].'/'.$v.'" /></li>';
            }

        }
        $this->assign('imgstr', $imgstr);
        $this->assign("data", $data);
        if (IS_POST) {
            $_POST = input('post.');
            $search_huifu = Db::name("order")->field("id,huifudesc,huifutime,adminuser")->where(["id"=>$_POST['id']])->select();
            $content = input("post.content");
            if(empty($content)){
                $this->error('回复内容必填');
            }
            $content = str_replace(',','，',$content);
            if(!$search_huifu[0]['huifudesc'] && !$search_huifu[0]['huifutime']){
                //dd(111);
                $list['huifudesc'] = $content ;
                $list['huifutime'] = time();
                $list['adminuser'] = session('admin_username');
                $list['wt_type'] = 1;
                if (Db::name("order")->where(['id'=>$id])->update($list)){
                    $this->success("回复成功");
                }else {
                    $this->error("回复失败");
                }
            }else{
                $_POST = input('post.');
                $huifu_arr =explode(',',$search_huifu[0]['huifudesc']);
                array_push($huifu_arr,$content);
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
                if (Db::name("order")->where(['id'=>$id])->update(['huifudesc'=>$huifu_str,'huifutime'=>$hf_time_str,'adminuser'=>$adminuser,'wt_type'=>1])){
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
        $id=input("param.id");
        $order=Db::name("order");
        if($order->delete($id)){
            $this->success("删除成功",url("index"));
          }else{
            $this->error("删除失败");
          }
    }

    //工单常见问题管理
    public function manage()
    {
        $type = input("type/s");
        if ($type) {
            $where['type'] = array('like', '%' . $type . '%');
            $list = Db::name('order_manage')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
                $item['problem'] = mb_substr($item['problem'], 0, 30, 'utf8');
                $item['answer'] = mb_substr($item['answer'], 0, 30, 'utf8');
                return $item;
            });
            $show = $list->render();
            $this->assign("type", $type);
        } else {

            $list = Db::name("order_manage")->order("id desc")->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
                $item['problem'] = mb_substr($item['problem'], 0, 30, 'utf8');
                $item['answer'] = mb_substr($item['answer'], 0, 30, 'utf8');
                return $item;
            });
            $show = $list->render();
        }


        $this->assign("list", $list);
        $this->assign("page", $show);
        return $this->fetch();
    }

    //工单常见问题增加修改
    public function order_edit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $order = Db::name('order_manage')->where(array('id' => trim($id)))->find();
                $this->data = $order;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');
            $_POST['addtime'] = time();
            if (isset($_POST['id'])) {
                $rs = Db::name('order_manage')->update($_POST);
            } else {
                $rs[] = Db::name('order_manage')->insert($_POST);
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
        $id = input('id/d');
        if (isset($id)) {
            $rs = Db::name('order_manage')->where(['id' => $id])->delete();

            if ($rs) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        }
    }
}