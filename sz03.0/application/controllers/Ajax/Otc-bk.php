<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/31
 * Time: 10:12
 */
class Ajax_OtcController extends Ajax_BaseController
{
    # 启用 SESSION
    protected $_auth = 1;
    //法币交易记录
    public function trustrecordAction()
    {
        //  $this->_ajax_islogin();
        $post = $this->rsaDecode()?:$_POST;
        $dob = new Otcorder_DobModel();
        $otcuser = new Otcorder_UserModel();

        $mo =$this->mCurUser['mo'];
        $area =$this->mCurUser['area'];
        /*   $mo = 18370628189;
          $area ='+86';*/


        if($this->mCurUser['mo'])
        {
            $mo =$this->mCurUser['mo'];
            $area =$this->mCurUser['area'];
            $coinList = $otcuser->field('uid,mo,email')->where(['mo'=>$mo,'area'=>$area])->fList();
        }
        else
        {
            $email =$this->mCurUser['email'];
            $coinList = $otcuser->field('uid,mo,email')->where(['email'=>$email])->fList();
        }
        $uid = $coinList[0]['uid'];

        $where = 'm_id='.$uid .' or from_uid='.$uid;
        if($post)
        {
            $status      =trim(addslashes($post['status']));
            $type        =  trim(addslashes($post['type']));
            $coin_to     = trim(addslashes($post['coin_to']));
            if($post['search_time']==1)
            {
                $search_time = 'created';
                $startTime   = $post['startTime'] ? strtotime(trim(addslashes($post['startTime']))) : strtotime(date('Y-m-d', time()) . '00:00:00');
                $endTime     = $post['endTime'] ? strtotime(trim(addslashes($post['endTime']))) : strtotime(date('Y-m-d', time()) . '23:59:59');
                $where .= ' and a.'.$search_time.' > ' . $startTime ." and a.".$search_time." < ".$endTime." ";
            }
            if($post['order_sn'])
            {
                $where.=' and order_sn='.trim($post['order_sn']);
            }
            elseif ($post['name'])
            {
                $where.=' and m_id='.trim($post['name']).'or from_uid='.trim($post['name']);
            }

        }

        if($status=='all')  //状态全部
        {

        }
        elseif ($status=='0')    //代付款
        {
            $where.='and status=0';
        }
        elseif ($status=='1')  //带确认
        {
            $where.=' and status=1';
        }
        elseif ($status=='2')  //已完成
        {
            $where.=' and status=2';
        }
        elseif ($status=='3')  //已关闭
        {
            $where.=' adn status=3';
        }
        elseif ($status=='4')  //超时关闭
        {
            $where.=" and status=3 and pay_time=''";
        }
        elseif ($status=='5')  //申诉
        {
            $where.=' and status=4';
        }

        if($type=='all')
        {

        }
        elseif ($type=='0') //出售
        {
            $where.=' and type=0';
        }
        elseif($type=='1')  //购买
        {
            $where.=' and type=1';
        }

        if($coin_to=='all')
        {

        }
        elseif($coin_to=='cny')   //人民币
        {
            $where.=" and coin_to='cny'";
        }
        elseif ($coin_to=='myr')//马来西亚
        {
            $where.=" and coin_to='myr'";
        }
        elseif($coin_to=='thb')//泰铢
        {
            $where.=" and coin_to='thb'";
        }
        elseif ($coin_to=='idr') //印度
        {
            $where.=" and coin_to='idr'";
        }


        $sql = "select COUNT(id) count from order_dob where $where";
        $total =$dob->query($sql);

        if ($total[0]['total'] == 0) {
            $data['list'] = '';
            $data['pagetotal'] = 0;
            $data['prev'] = '';
            $data['next'] = '';
            $data['currentpage'] = '';
        } else {

                $page = $_POST['page'] ? (int)addslashes($_POST['page']) : 1;//页码
                $pagenumber = $_POST['size'] ? (int)addslashes($_POST['size']) :7;//每页多少条
                $data['pagetotal'] = ceil($total[0]['total'] / $pagenumber);//总页数
                if ($page > $data['pagetotal']) {
                    $page = $data['pagetotal'];
                }
                if ($page < 1) {
                    $page = 1;
                }
                $p = ($page - 1) * $pagenumber;

                $data['list'] = $dob->query("select * count from order_dob where $where");

                foreach ($data['list'] as &$v) {//去0
                    $v['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v['number']), '.');
                }
                $data['prev'] = $page - 1;//上一页
                $data['next'] = $page + 1;//下一页
                if ($data['prev'] < 1) {
                    $data['prev'] = 1;
                }
                if ($data['next'] > $data['pagetotal']) {
                    $data['next'] = $data['pagetotal'];
                }
                $data['currentpage'] = $page;//当前页

        }



    }

    public function otcoutAction()
    {
        
    }






}