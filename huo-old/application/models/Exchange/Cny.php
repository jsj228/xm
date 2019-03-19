<?php
class Exchange_CnyModel extends Orm_Base
{
    public $table = 'exchange_cny';
    public $field = array(
        'id'          => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'uid'         => array('type' => "int(11) unsigned", 'comment' => '用户id'),
        'email'       => array('type' => "char(60)", 'comment' => '用户名'),
        'admin'       => array('type' => "int(11) unsigned", 'comment' => '管理员'),
        'money'       => array('type' => "decimal(8,2) unsigned", 'comment' => '金额'),
        'money_u'     => array('type' => "decimal(8,2) unsigned", 'comment' => '实际到账金额'),
        'order'       => array('type' => "char(120)", 'comment' => '汇款单号'),
        'account'     => array('type' => "char(120)", 'comment' => '汇款账号'),
        'bank'        => array('type' => "char(255)", 'comment' => '开户行'),
        'bankfrom'    => array('type' => "varchar(255)", 'comment' => '开户行'),
        'accounttype' => array('type' => "enum('支付宝','财付通','银行卡')", 'comment' => '收款类别'),
        'name'        => array('type' => "char(12)", 'comment' => '姓名'),
        'opt_type'    => array('type' => "enum('in','out')", 'comment' => '类别'),
        'status'      => array('type' => "enum('等待','成功','打款中','失败','待审核','审核不通过','已取消')", 'comment' => '状态'),
        'created'     => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'createip'    => array('type' => "char(15)", 'comment' => '创建ip'),
        'updated'     => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
        'updateip'    => array('type' => "char(15)", 'comment' => '更新ip'),
        'province'    => array('type' => "char", 'comment' => ''),
        'city'        => array('type' => "char", 'comment' => ''),
        'district'    => array('type' => "char", 'comment' => ''),
        'subbranch'   => array('type' => "char", 'comment' => '支行'),
        'bak'         => array('type' => "char", 'comment' => ''),
        'rmbinvals'   => array('type' => "varchar(120)", 'comment' => '记录重复提交金额'),
    );
    public $pk = 'id';

    const RMBOUT_FEE      = 0.005;
    const LOWEST_IN_MONEY = 100;

    /**
     * 人民幣轉出
     * @param $pUser 用戶信息
     * @return bool
     */
    public function out(&$pUser, $bank = false)
    {
        # 保存用户
        $tMO = new UserModel();
        # 事務開始
        $tMO->begin();
        if (!$tMO->safeUpdate($pUser, array('cny_lock' => $_POST['money'], 'cny_over' => -$_POST['money'])))
        {
            $tMO->back();
            Tool_Fnc::showMsg($tMO->error[2]);
        }
        if (!$bank)
        {
            # 默认数据
            $tData = array('money' => 0, 'accounttype' => '银行卡', 'account' => '', 'name' => '', 'bank' => '', 'city' => 0, 'province' => 0, 'bak' => '');
            # 用户提交数据
            foreach ($tData as $k1 => $v1)
            {
                empty($_POST[$k1]) || $tData[$k1] = trim($_POST[$k1]);
            }
        }
        else
        {
            $tData = array('money' => $_POST['money'], 'accounttype' => '银行卡', 'account' => $bank['account'], 'name' => $bank['name'], 'bank' => $bank['bank'], 'city' => 0, 'province' => 0,
                'bak'                  => '');
        }

        # 系统数据
        $tData['opt_type']  = 'out';
        $tData['money_u']   = $tData['money'] * (1 - self::RMBOUT_FEE);
        $tData['uid']       = $pUser['uid'];
        $tData['email']     = $pUser['email'];
        $tData['created']   = $_SERVER['REQUEST_TIME'];
        $tData['createip']  = Tool_Fnc::realip();
        $tData['status'] = '等待';
        $tData['province']  = $bank['province'];
        $tData['city']      = $bank['city'];
        $tData['district']  = $bank['district'];
        $tData['subbranch'] = $bank['subbranch'];
        # 写入DB
        if (!$tData['id'] = $this->insert($tData))
        {
            $tMO->back();
            Tool_Fnc::showMsg('系统错误，请通知管理员 [错误编号:S_R_001]');
        }
        PhoneCodeModel::updateCode($pUser, 1, $tData['id']);
        # 提交
        return $tMO->commit();
    }

    /**
     * 人民币自动充值
     * @param admin 操作客服
     * @return bool
     */
    public function pay($payData, $admin = 0)
    {
        # 事务开始
        $this->begin();
        # 查询充值订单
        if (!$tPay = $this->lock()->fRow("SELECT uid,money,status FROM exchange_cny WHERE id=" . $payData['out_trade_no']))
        {
            $this->back();
            return '订单不存在';
        }
        # 验证
        if ($tPay['status'] == '成功')
        {
            return 0;
        }

        if ($tPay['status'] == '已取消')
        {
            return '订单已取消';
        }

        //if( !$admin  && abs(round($payData['total_fee']*0.99, 2) - $tPay['money']) > 1E-7 ) return "金额不匹配";

        # 更新用户余额，使用safeUpdate的时间间隔会导致充值失败
        $tMO   = new UserModel();
        $tUser = array('uid' => $tPay['uid']);
        if (!$tMO->safeUpdate($tUser, array('cny_over' => $payData['total_fee']), true))
        {
            $this->back();
            return "更新用户余额失败";
        }
        $tOrder = array(
            'id'      => $payData['out_trade_no'],
            'status'  => '成功',
            'order'   => $payData['trade_no'],
            'account' => $payData['account'],
            'money'   => $payData['total_fee'],
            'admin'   => $admin,
            'updated' => time(),
        );
        //查看是否是第一次充值
        $num = $this->fRow("SELECT count(*) num FROM exchange_cny WHERE uid={$tPay['uid']} and status='成功' and opt_type='in'");
        if (empty($num))
        {
            $num['num'] = 0;
        }
        $num1 = $this->fRow("SELECT count(*) num FROM exchange_cny WHERE uid={$tPay['uid']} and status='成功' and money>=1000 and opt_type='in'");
        if (empty($num1))
        {
            $num1['num'] = 0;
        }
        # 更新订单为成功
        if (!$this->update($tOrder))
        {
            $this->back();
            return "更新充值订单失败";
        }
        Cache_Redis::instance()->hSet('usersession', $tPay['uid'], 1);
        # 推荐用户奖励
        /* $user = $tMO->getById($tPay['uid']);
        if(!empty($user) && $user['pid'] != 0){
        $user1 = $tMO->getById($user['pid']);
        if(($user1['created'] + 3600*24*90) > time()){
        $reward = array(
        'uid'    => $user['pid'],
        'oid'    => $user['uid'],
        'bid'    => $payData['out_trade_no'],
        'reward'=> Tool_Str::format($payData['total_fee'] * 0.001),
        'creatime'=>time()
        );
        $rwd = new RewardModel();
        if(!$rwd -> insert($reward)){
        $rwd->back();
        }
        $prewd = array(
        'uid'    => $user['pid'],
        );
        if($tMO->safeUpdate($prewd, array('cny_over' => $reward['reward']), true) != true){
        $this->back();
        return "更新用户余额失败";
        }
        $uid1   = array('uid'=>1);
        if($tMO->safeUpdate($uid1, array('cny_over' => -$reward['reward']), true) !=  true){
        $this->back();
        return "更新用户余额失败";
        }
        }
        } */

        # 提交
        if ($this->commit())
        {
            if ($num['num'] == 0)
            {
                Member::AddUserCredit($tPay['uid'], 9); //首次充值
                if ($tPay['money'] >= 1000)
                {
                    Member::AddUserCredit($tPay['uid'], 10); //首次充值大于1000
                }
            }
            if (isset($num1) && $num['num'] > 0 && $num1['num'] == 0)
            {
                if ($tPay['money'] >= 1000)
                {
                    Member::AddUserCredit($tPay['uid'], 10); //首次充值大于1000
                }
            }
            //充值积分日志
            Member::RmbInAddlog($tPay['uid'], $tPay['money']);
            Cache_Redis::instance()->hSet('usersession', $tPay['uid'], 1);
            return 0;
        }
        else
        {
            return "提交commit失败";
        }

    }

    /*
     * 常用银行卡
     */

    public function bankCards($pUid)
    {
        $pOpt = array(
            'field' => 'account , bank , name , province , city',
            'where' => "uid = '{$pUid}' AND `accounttype` = '银行卡' AND opt_type = 'out' AND status='成功' AND created > 1389283200",
            'group' => 'account',
        );

        return $this->fList($pOpt);

    }

    /**
     * 获取随机金额，防重复订单
     *
     * @param     int     $money
     * @return     可以使用小数位
     */
    public static function randMoney($money)
    {
        $day      = strtotime(date('Ymd', time()));
        $moneyMax = $money + 1;
        $where    = "money<{$moneyMax} and money>={$money} and `accounttype` = '银行卡' AND opt_type = 'in' AND created > {$day}";
        $iMo      = new self();
        $moneyArr = $iMo->field('money')->where($where)->fList();
        $baseArr  = array();
        # 产生一个0-99的数组
        for ($i = 0; $i < 100; $i++)
        {
            $baseArr[] = $i;
        }

        if (!empty($moneyArr))
        {
            foreach ($moneyArr as &$v)
            {
                $tmp = explode('.', $v);
                $v   = (int) $tmp[1];
            }
        }
        else
        {
            $moneyArr = array();
        }

        $rand = array_diff($baseArr, $moneyArr);
        if (empty($rand))
        {
            return -1;
        }

        $_SESSION['cnyinrand'] = $rand[array_rand($rand, 1)];

        return $_SESSION['cnyinrand'];
    }

    /**
     * 防止重复提交相同金额
     * $param $money 金额
     * $param $uid 用户id
     **/
    public static function notRepeatMoney($money, $uid)
    {
        $iMo   = new self();
        $where = "`money` = {$money} and `uid` = {$uid} ";
        $res   = $iMo->field('money')->where($where)->fList();
        return $res;
    }

    /**
     * 查询用户是否冻结禁止人民币提现
     */
    public static function getRmbOutStatus($uid)
    {
        $forMo = new UserForbiddenModel;
        $fdata = $forMo->lock()->where("uid = {$uid} and status = 0")->fRow();

        if ($fdata)
        {
            return $fdata['canrmbout'];
        }
        else
        {
            return true;
        }
    }

    /**
     * 查询人民币的规则
     * @return bool
     */
    public static function cnylimit()
    {
        $iMo     = new self();
        $cnydata = $iMo->query("select minout,maxout,max_day,rate from cny where name='cny'");
        if ($cnydata)
        {
            return $cnydata[0];
        }
        else
        {
            return false;
        }

    }
}
