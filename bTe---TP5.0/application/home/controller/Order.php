<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
class Order extends HomeCommon
{

    public function index()
    {
        $uid = userid();
        if (!$uid) {
           return redirect('/#login');
        }
        $id = input("id/d");
        if ($id) {
            $data = Db::name("order")->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc")->where(["id" => $id, "uid" => $uid])->find();
            $this->assign("data", $data);
        }
        if(!$id) {
            $data=null;
            $this->assign("data", $data);
        }
        $user = Db::name('User')->where(['id'=> $uid])->find();
        $list = Db::name('order_manage')->field("type")->group("type")->select();
        $this->assign("list", $list);
        $this->assign("user", $user);
        return $this->fetch();
    }

    public function jieshou()
    {
        $id = input('id');
        $uid = userid();
        $emailAdd = input("emailAdd");//邮箱
        $requestSub = trim(input("requestSub"));//标题
        $requestType = input("requestType");//货币类型
        $res = input("requestDescription");
        $requestDescription =trim(addslashes($res));//详情内容
        $requestDescription = preg_replace("/(,)/" ,'，' ,$requestDescription);
        $time = time();
        $img1=input('data1');
        $img2=input('data2');
        $order = Db::name('Order')->where(['id' => $id])->find();

        if (!$id || $id==''){
           
            if(!$requestSub){
                $this->error('请填写标题');
            }elseif(!$requestDescription){
                $this->error('请填写内容');
            }
            $user=Db::name('order')->where(['uid'=>$uid])->order('id desc')->value('addtime');
            if($user){
                $newstr = (substr($user, -10))+5;  
            }
           
            $time=time();

            if($time<$newstr){
                $this->error('提交过于频繁，5秒后再试！');
            }
            $retu = Db::name('order')->where(['id' => $id])->insertGetid([
                'uid' => $uid,
                'emailAdd' => $emailAdd,
                'requestSub' => $requestSub,
                'requestType' => $requestType,
                'requestDescription' => $requestDescription,
                'addtime' => $time,

            ]);
           if($img1 ||$img2){
               
                 
                $first=$this->base64_upload($img1);
                $second=$this->base64_upload($img2);
                if($first && !$second){
                    $data=$first;
                    $data=array(
                        'attrimg'=>$data,
                    );
                }elseif($second && !$first){
                    $data=$second;
                    $data=array(
                        'attrimg'=>$data,
                    );
                }elseif($first && $second ){
                    $data=$first.'_'.$second;
                    $data=array(
                        'attrimg'=>$data,
                    );
                }

                Db::name('Order')->where(['id'=>$retu])->update($data);
           }
        }
        if($id!='' || $id!=null){
            if(!$requestDescription){
                $this->error('请填写内容');
            }
            $user=Db::name('order')->where(['id'=>$id])->value('addtime');
            $newstr = (substr($user, -10))+5; //mn
               $time=time();

               if($time<$newstr){
                   $this->error('提交过于频繁，5秒后再试！');
               }
            //上传图片
                $img1=input('data1');
                $img2=input('data2');
                $first=$this->base64_upload($img1);
                $second=$this->base64_upload($img2);

                if($first && !$second){
                    $data=$first;
                    $data=array(
                        'attrimg'=>$data,
                    );
                }elseif($second && !$first){
                    $data=$second;
                    $data=array(
                        'attrimg'=>$data,
                    );
                }elseif($first && $second ){
                    $data=$first.'_'.$second;
                    $data=array(
                        'attrimg'=>$data,
                    );
                }
                Db::name('Order')->where(['id'=>$id])->update($data);

               $str_req = explode(',', $order['requestdescription']);
                $str_time = explode(',', $order['addtime']);
                array_push($str_req, $requestDescription);
                array_push($str_time, time());
                $str_req = implode(',', $str_req);
                $str_time = implode(',', $str_time);
                $retu = Db::name('order')->where(['id' => $id])->update([
                    'emailAdd' => $emailAdd,
                    'requestSub' => $requestSub,
                    'requestType' => $requestType,
                    'requestDescription' => $str_req,
                    'addtime' => $str_time,
                    'wt_type' => 0
                ]);

        }
        if ($retu) {
            Return(array('status'=>1,'msg'=>'发送成功！'));
            // $this->success('发送成功！','Order/history');
        } else {
            $this->error('发送失败！');
        }
    }


    function base64_upload($base64) {
        $base64_image = str_replace(' ', '+', $base64);
        //post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是post的数据，则注释掉这一行
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image, $result)){
            //匹配成功
            if($result[2] == 'jpeg'){
                $image_name = uniqid().'.jpg';
                //纯粹是看jpeg不爽才替换的
            }else{
                $image_name = uniqid().'.'.$result[2];
            }

           // $image_file = "Upload/order/{$image_name}";//'Upload/order/' . userid() . '/';
            $image_file = "Upload/order/". userid() . '/'."{$image_name}";
            $info = oss_upload_yz($image_file,base64_decode(end(explode(',',$base64_image))));
            if(!$info){
                return false;
            }else{
                return basename($info);
            }
        }else{
            return false;
        }
    }
//排序
    public function history()
    {

        $types = Db::name('order_manage')->field('type')->group("type")->select();

        $list = Db::name('order')->where(['uid' => session('userId')])->field("attrimg,id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,wt_type")->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
       $page = $list->render();
       if ($list){
           foreach ($list as $key => $value) {
               $data = $value;
               if($data['attrimg']!==''){
                   $data['description'] = explode(',', $data['description']);
                   $data['addtime'] = explode(',', $data['addtime']);
                   $data['huifudesc'] = explode(',', $data['huifudesc']);
                   $data['addtime'] = date("Y-m-d H:i:s", $data['addtime']);
               }

               $list->offsetSet($key,$data);
           }
       }
        $this->assign("types", $types);
        $this->assign("page", $page);
        $this->assign("list", $list);
        return $this->fetch();
    }

    public function order_history(){

        if($this->request->isPost()) {
            $se_type = input("type");
            //1 未回复  2 已回复
            $se_huifu = input("huifu");
            $se_name = input("name");
            $success = "";
            if ($se_huifu == 1) {
                $se_huifu = array('exp', 'IS NULL');
            } else if ($se_huifu == 2) {
                $se_huifu = array('neq', '');
            }
            $where = array(
                'uid' => session('userId'),
                'requestType' => $se_type,
                'huifudesc' => $se_huifu,
                'requestSub' => array('like', "%" . $se_name . "%")
            );
            if ($se_huifu == "0") {
                unset($where['huifudesc']);
            }
            if ($se_type == "") {
                unset($where['requestType']);
            }
            $list = Db::name('order')->where($where)->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc")->order('id desc')->select();
            foreach ($list as $key => $value) {
                $success .= "<tr>";
                $success .= "<td>" . $value['id'] . "</td>";
                $success .= "<td class='request-info requests-table-info'>";
                $success .= "<a href='reply/id/" . $value['id'] . "' class='striped-list-title' title='现在还能充值比特币和ETH吗'>";
                $success .= $value['title'];
                $success .= "</a>";
                $success .= "</td>";
                $success .= "<td>" . $value['type'] . "</td>";
                $value['addtime'] = explode(',', $value['addtime']);
                $cut = count($value['addtime']);
                for ($i = 0; $i < $cut; $i++) {
                    $value['addtime'][$i] = addtime($value['addtime'][$i]);
                }
                $success .= '<td>' . $value['addtime'][$cut - 1] . '</td>';
                $success .= "<td class='requests-table-status'>";
                if ($value['huifudesc']) {
                    $success .= "<span class='status-label status-label-solved' title='此请求已解决'>已解决";
                } else {
                    $success .= "<span class='status-label status-label' title='此请求未解决'>未解决";
                }
                $success .= "</span></td></tr>";
            }
        }
        echo $success;
    }

    public function reply()
    {

        $id = input('id');
        $data= model('Order')->get_order_info($id);
        $this->assign("data", $data);
        $this->assign("uname", session('userName'));
         return $this->fetch();
    }
}