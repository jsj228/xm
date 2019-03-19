<?php
/**
 * Created by PhpStorm.
 * User: wqdkv
 * Date: 2017/9/12
 * Time: 17:59
 */

namespace Home\Controller;

class OrderController extends HomeController
{

    public function index()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $id = I("id/d");
        if ($id) {
            $uid = session('userId');
            $data = M("order")->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc")->where(array("id" => $id, "uid" => $uid))->find();
            $this->assign("data", $data);
        }

        $user = M('User')->where(array('id'=>session('userId')))->find();
        $list = M('order_manage')->field("type")->group("type")->select();
        $this->assign("list", $list);
        $this->assign("user", $user);
        $this->display();
    }

    public function jieshou()
    {
        $id = I('id/d');
        $emailAdd = I("emailAdd/s");//邮箱
        $requestSub = trim(I("requestSub/s"));//标题
        $requestType = I("requestType/s");//货币类型
        $res = I("requestDescription/s");
        $requestDescription =trim(addslashes($res));//详情内容
        $requestDescription = preg_replace("/(,)/" ,'，' ,$requestDescription);
        $time = time();
        $order = M('Order')->where(['id' => $id])->find();
        if (!$id){
            if(!$requestSub){
                $this->error('请填写标题');
            }elseif(!$requestDescription){
                $this->error('请填写内容');
            }

            $user=M('order')->where(['uid'=>userid()])->order('id desc')->getField('addtime');
            $newstr= $user+5;
            if($time<$newstr){
                $this->error('提交过于频繁，5秒后再试！');
            }
            $retu = M('order')->add([
                'uid' => userid(),
                'emailAdd' => $emailAdd,
                'requestSub' => $requestSub,
                'requestType' => $requestType,
                'requestDescription' => $requestDescription,
                'addtime' => $time,
                'pid'       => 0,
                'rid'       => 0,
            ]);
            $img1=I('data1');
            $img2=I('data2');
            $first=$this->base64_upload($img1);
            $second=$this->base64_upload($img2);
//            $img3=I('data3');
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
            M('Order')->where(array('id'=>$retu))->save($data);
        }
        if($id){
            if(!$requestDescription){
                $this->error('请填写内容');
            }
            $user=M('order')->where(['id'=>$id])->getField('addtime');
            $newstr = (substr($user, -10))+5; //mn
               $time=time();

               if($time<$newstr){
                   $this->error('提交过于频繁，5秒后再试！');
               }
            //上传图片
                $img1=I('data1');
                $img2=I('data2');
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
                M('Order')->where(array('id'=>$id))->save($data);

               $str_req = explode(',', $order['requestdescription']);
                $str_time = explode(',', $order['addtime']);
                array_push($str_req, $requestDescription);
                array_push($str_time, time());
                $str_req = implode(',', $str_req);
                $str_time = implode(',', $str_time);
                $retu = M('order')->where(['id' => $id])->save([
                    'emailAdd' => $emailAdd,
                    'requestSub' => $requestSub,
                    'requestType' => $requestType,
                    'requestDescription' => $str_req,
                    'addtime' => $str_time,
                    'wt_type' => 0
                ]);

        }
        if ($retu) {
            $this->success('发送成功！', '/Order/history');
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
        $types = M('order_manage')->field('type')->group("type")->select();
        $list = M('order')->where(['uid' => session('userId')])->field("attrimg,id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,wt_type")->order('id desc')->select();

        foreach ($list as $key => $value) {
           if($value[$key]['attrimg']!==''){
               $list[$key]['description'] = explode(',', $list[$key]['description']);
               $list[$key]['addtime'] = explode(',', $list[$key]['addtime']);
               $list[$key]['huifudesc'] = explode(',', $list[$key]['huifudesc']);
               for ($i = 0; $i < count($list[$key]['addtime']); $i++) {
                   $list[$key]['addtime'][$i] = date("Y-m-d H:i:s", $list[$key]['addtime'][$i]);
               }
           }
        }
        $this->assign("types", $types);
        $this->assign("list", $list);
        $this->display();
    }

    public function order_history(){
        if(IS_POST) {
            $se_type = I("type/s");
            //1 未回复  2 已回复
            $se_huifu = I("huifu/s");
            $se_name = I("name/s");
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
            $list = M('order')->where($where)->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc")->order('id desc')->select();
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
        $id = I('id/d');
        $data = D('Order')->get_order_info($id);
        $this->assign("data", $data);
        $this->assign("uname", session('userName'));
        $this->display();
    }
}