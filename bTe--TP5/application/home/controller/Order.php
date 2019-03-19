<?php
/**
 * Created by PhpStorm.
 * User: wqdkv
 * Date: 2017/9/12
 * Time: 17:59
 */

namespace app\home\controller;

use think\Db;

class Order extends Home
{

    public function index()
    {
        if (!userid()) {
            $this->redirect('/#login');
        }
        $user = Db::name('User')->where(array('id'=>userid()))->find();
        $this->assign("user", $user);
        $id = input("id/d");
        $data = null;
        if ($id) {
            $uid = session('userId');
            $data = Db::name('Order')->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc")->where(array("id" => $id, "uid" => $uid))->find();

        }
        $list = Db::name('OrderManage')->field("type")->group("type")->select();
        $this->assign("data", $data);
        $this->assign("list", $list);
        return $this->fetch();
    }

    //工单提交
    public function jieshou()
    {
        $id = input('id/d');
        $emailAdd = input("emailAdd/s");//邮箱
        $requestSub = input("requestSub/s");//标题
        $requestType = input("requestType/s");//货币类型
        $res = input("requestDescription/s");
        $res = str_replace(",","，",$res);
        $requestDescription = addslashes($res);//详情内容
        $time = time();
        if (!$emailAdd){
            $this->error('账号不能为空');
        }
        if (!trim($requestSub)){
            $this->error('标题不能为空');
        }
        if (!trim($requestDescription)){
            $this->error('内容描述不能为空');
        }
        if (empty($id)) {
            //工单提交判断
            $last_time = Db::name('order')->where(['uid' => userid()])->order('id desc')->value('addtime');
            if(time() > ($last_time + 60)) {
                $retu = Db::table('weike_order')->insert(array(
                    'uid' => userid(),
                    'emailAdd' => $emailAdd,
                    'requestSub' => $requestSub,
                    'requestType' => $requestType,
                    'requestDescription' => $requestDescription,
                    'addtime' => $time,
                ));
            } else {
                $retu = 0;
            }
        } else {
            $order = Db::name('Order')->where(['id' => $id])->find();
            $str_req=[];
            if($order['requestDescription']){
                $str_req = explode(',', $order['requestDescription']);
            }

            if(count($str_req)>15){
                $retu = Db::name('Order')->insert([
                    'uid' => userid(),
                    'emailAdd' => $emailAdd,
                    'requestSub' => $requestSub,
                    'requestType' => $requestType,
                    'requestDescription' => $requestDescription,
                    'addtime' => $time
                ]);
            }else{
                $str_time=[];
                if($order['addtime']){
                    $str_time = explode(',', $order['addtime']);
                }
                array_push($str_req, $requestDescription);
                array_push($str_time, time());
                $str_req = implode(',', $str_req);
                $str_time = implode(',', $str_time);
                $retu = Db::name('Order')->where(['id' => $id])->update([
                    'emailAdd' => $emailAdd,
                    'requestSub' => $requestSub,
                    'requestType' => $requestType,
                    'requestDescription' => $str_req,
                    'addtime' => $str_time,
                    'wt_type' => 0
                ]);
            }
        }

        if (false !== $retu) {
            $this->success("提交成功！", '/Order/history');
        } else {
            $this->error("每分钟只能提交一次！", '/Order/history');
        }
    }

    //排序
    public function history()
    {
        $types = Db::name('order_manage')->field('type')->group("type")->select();
        $list = Db::name('Order')->where(['uid' => session('userId')])->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,wt_type")->order('id desc')->select();

        foreach ($list as $key => $value) {
            $list[$key]['description'] = explode(',', $list[$key]['description']);
            $list[$key]['addtime'] = explode(',', $list[$key]['addtime']);
            $list[$key]['huifudesc'] = explode(',', $list[$key]['huifudesc']);
            for ($i = 0; $i < count($list[$key]['addtime']); $i++) {
                $list[$key]['addtime'][$i] = date("Y-m-d H:i:s", $list[$key]['addtime'][$i]);
            }
        }
        $this->assign("types", $types);
        $this->assign("list", $list);
        return $this->fetch();
    }


    public function order_history(){
        if(IS_POST) {
            $se_type = input("type/s");
            //1 未回复  2 已回复
            $se_huifu = input("huifu/s");
            $se_name = input("name/s");
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
            $list = Db::name('Order')->where($where)->field("id,emailAdd as email,requestSub title,requestType type,requestDescription as description,addtime,huifudesc")->order('id desc')->select();
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
        $id = input('id/d');
        $data = model('Order')->get_order_info($id);
//        dd($data['addtime']);
        $this->assign("data", $data);
        $this->assign("uname", session('userName'));
        return $this->fetch();
    }
}