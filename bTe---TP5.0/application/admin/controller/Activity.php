<?php

namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;


class Activity extends AdminCommon
{
    //华克金 利息

    public function issueWc()
    {
        $name = input('name');
        $field = input('field');
        $where = array();
        $where['coinname'] = 'wc';
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        $wc_interestSum = Db::name('IssueWc')->where($where)->sum('interest');
        $count = Db::name('IssueWc')->where($where)->count();
        $list = Db::name('IssueWc')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('wc_interestSum', $wc_interestSum);
        return $this->fetch('activity/issuewc');
    }

    //管理员 日志
    public function adminLog()
    {

        $name = input('name');
        $field = input('field');
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('Admin')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = Db::name('AdminLog')->where($where)->count();

        $list = Db::name('AdminLog')->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k => $v) {

            $list[$k]['username'] = Db::name('Admin')->where(array('id' => $v['userid']))->value('username');
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('count', $count);
        return $this->fetch('adminLog');
    }

    public function issueWcg()
    {
        $name = input('name/s');
        $field = input('field/s');
        $where = array();
        $where['coinname'] = 'wcg';
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        $wc_interestSum = Db::name('IssueWc')->where($where)->sum('interest');
        $count = Db::name('IssueWc')->where($where)->count();

        $list = Db::name('IssueWc')->where($where)->order('id desc')->paginate(15);

        $page = $list->render();
        $list = $list->all();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = Db::name('User')->where(array('id' => $v['userid']))->value('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('wc_interestSum', $wc_interestSum);
        return $this->fetch('issueWcg');
    }
}