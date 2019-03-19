<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 1/26/2018
 * Time: 10:56 AM
 */

namespace Admin\Controller;

class ActivityController extends AdminController
{
    //华克金 利息
    public function issue()
    {
        $name = trim(I('name/s'));
        $field = I('field/s');

        $type = I('type/s');

        $where = array();
        $where['coinname'] = $type;
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
        $wc_interestSum = M('IssueWc')->where($where)->sum('interest');
        $count = M('IssueWc')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('IssueWc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->assign('wc_interestSum', $wc_interestSum);
        $this->display();
    }

    //管理员 日志
    public function adminLog()
    {
        $name = I('name/s');
        $field = I('field/s');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('Admin')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('AdminLog')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('AdminLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('Admin')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->display();
    }
    public function userInherit(){
       
        if($_GET['name']){
           
          $where=array(
            'moble'=>$_GET['name'],
          );
        }else{
            $where=array(
                'id'=>array('neq',0),
              );  
        }
        $count = M('Inherit')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $Inherit=M('Inherit')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($Inherit as $k=>$val){
            $Inherit[$k]['moble1']=M('User')->where(['id'=>$val['userid']])->getField('moble');
        }
        $this->assign('Inherit',$Inherit);
        $this->assign('page',$show);
        $this->display();
    }
}