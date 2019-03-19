<?php
/**
 * 轉幣通知
 */
class Cli_TransnoticeController extends Ctrl_Cli
{
    const IN_NOTICE_RETRY_KEY  = 'TransInNoticeRetry';
    const OUT_NOTICE_RETRY_KEY = 'TransOutNoticeRetry';

    public $logDir = 'trans/';
    # Run
    public function runAction()
    {
        try
        {
            while(true)
            {
                $r = Api_Trans_Client::holdRequest(array(&$this, 'trans'), array(&$this, 'sqlPing'));
            }
        }
        catch(Exception $e)
        {
            Tool_Fnc::warning(sprintf('Cli_Transnotice error:%s', json_encode($e)));
        }
        exit;
    }

    public function trans($data)
    {
        $this->logDir = preg_replace('#/[\d]*#', '/'.date('Ymd'), $this->logDir);
        if($data['args'])
        {
            if($data['command']=='trans_in')
            {
                $this->in($data['args']);
            }
            elseif($data['command']=='trans_out_check')
            {
                $this->out($data['args']);
            }
        }
    }

    protected function in($args)
    {
 
        foreach ($args as $v) 
        {
            $coin = strtolower($v['coin']);
            $user = AddressModel::getInstance()->field('uid')->where(['address'=>$v['addr'], 'coin'=>$coin])->fRow();
            $mo = 'Exchange_'.ucfirst($coin).'Model';
            $exMo = $mo::getInstance();  
            //检查重复
            $exists = $exMo->where(['tid'=>$v['tid']])->fRow();
            if($exists)
            {
                Tool_Log::wlog(sprintf('重复通知, data:%s',json_encode($v)), $this->logDir, true, '[H:i:s]');
                continue;
            }
            $exMo->begin();
            $r = $exMo->insert(array(
                'uid'=>$user['uid'],
                'admin' => 6,
                'email'=>'',
                'txid'=>$v['txid'],
                'wallet'=>$v['addr'],
                'opt_type'=>'in',
                'number'=>$v['num'],
                'status'=>'成功',
                'created'=>time(),
                'createip'=>'0.0.0.1',
                'updated'=>time(),
                'updateip'=>'0.0.0.1',
                'tid'=>$v['tid']
            ));
            if(!$r)
            {
                $exMo->back();
                Tool_Log::wlog(sprintf('轉入錯誤, sql:%s, data:%s',$exMo->getLastSql(), json_encode($v)), $this->logDir, true, '[H:i:s]');
                $this->addRetryList(self::IN_NOTICE_RETRY_KEY, $v);
                continue;
            }
            $r = UserModel::getInstance()->safeUpdateCli($user, array($coin.'_over'=>$v['num']));
            if(!$r)
            {
                $exMo->back();
                Tool_Log::wlog(sprintf('轉入錯誤, sql:%s, data:%s',$exMo->getLastSql(),json_encode($v)), $this->logDir, true, '[H:i:s]');
                $this->addRetryList(self::IN_NOTICE_RETRY_KEY, $v);
                continue;
            }
            $exMo->commit();
        }
        
    }

    protected function out($args)
    {
        foreach ($args as $v) 
        {
            $coin = strtolower($v['coin']);
            $user = AddressModel::getInstance()->field('uid')->where(['address'=>$v['from'], 'coin'=>$coin])->fRow();
            $mo = 'Exchange_'.ucfirst($coin).'Model';
            $exMo = $mo::getInstance();
            $ex = $exMo->where(['tid'=>$v['tid']])->fRow();
            if(!$ex)
            {
                Tool_Log::wlog(sprintf('數據庫無此記錄, data:%s', json_encode($v)), $this->logDir, true, '[H:i:s]');
                continue;
            }
            $exMo->begin();
            $r = $exMo->where(array('tid'=>$v['tid']))->update(array(
                'txid'=>isset($v['txid'])?$v['txid']:$v['msg'],
                'updated'=>time(),
                'updateip'=>'0.0.0.1',
                'status'=>$v['status']==0?'成功':'已取消',
                'tid'=>isset($v['tid'])?$v['tid']:0,
            ));
            if(!$r)
            {
                $exMo->back();
                Tool_Log::wlog(sprintf('轉出錯誤, sql:%s, data:%s',$exMo->getLastSql(), json_encode($v)), $this->logDir, true, '[H:i:s]');
                $this->addRetryList(self::OUT_NOTICE_RETRY_KEY, $v);
                continue;
            }
            if($v['status']==0)
            {
                $r = UserModel::getInstance()->safeUpdateCli($user, array($coin.'_lock'=>Tool_Math::mul(-1, $ex['number'])));
            }
            else
            {
                $r = UserModel::getInstance()->safeUpdateCli($user, array($coin.'_over'=>$ex['number'], $coin.'_lock'=>Tool_Math::mul(-1, $ex['number'])));
            }
            
            if(!$r)
            {
                Tool_Log::wlog(sprintf('轉出錯誤, error:%s, sql:%s, data:%s',UserModel::getInstance()->getError(2), UserModel::getInstance()->getLastSql(), json_encode($v)), $this->logDir, true, '[H:i:s]');
                $this->addRetryList(self::OUT_NOTICE_RETRY_KEY, $v);
                continue;
            }
            $exMo->commit();
        }
        
    }

    /*
    * 添加到重試列表
    */
    protected function addRetryList($key, $data)
    {
        return Cache_Redis::instance()->lpush($key, json_encode($data));
    }

    /*
    * 重試列表
    */
    protected function getRetryList($data)
    {
        return Cache_Redis::instance()->rpop($key);
    }


    public function sqlPing()
    {
        UserModel::getInstance()->query('select CONNECTION_ID();');
    }
}
