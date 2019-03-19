<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/12/27
 * Time: 10:48
 */

class OutController extends Ctrl_Base
{
    protected $_auth = 0;

    /**
     * user表
     */
    public function indexAction()
    {

        //连接数据库
        $huo = new Orm_Base(0,'huo','weike');
        $mo = new Orm_Base();

        $mo->table = "user";
        $huo->table = "weike_user";

        $mo->exec("truncate table user");


        $max_uid = $mo->order('uid desc')->fOne('uid');
        if(!$max_uid) $max_uid = 0;


        $userids = $huo->field('id')->where("id>{$max_uid}")->fList();

        foreach ($userids as $v){
            $huo->table = "weike_user";
            $u = $huo->where("id={$v['id']}")->fRow();

            $huo->table = "weike_user_coin";
            $ucoin = $huo->where("userid={$v['id']}")->fRow();


            $area = substr($u['moble'], 0, 3);
            $prand = Tool_Md5::getUserRand();
            $password = md5(Tool_Md5::getUserSeed($prand).'login'.$u['password'].Tool_Md5::SEED_FOR_PASSWD);

            $pwdtrade = md5(Tool_Md5::getUserSeed($prand).'trade'.$u['paypassword'].Tool_Md5::SEED_FOR_PASSWD);


            $in_data = [
                'uid'=>$u['id'],
                'mo'=>$u['username'],
                'area'=>$area,
                'name'=>$u['username'],
                'idcard'=>$u['idcard'],
                'pwd'=>$password,
                'pwdtrade'=>$pwdtrade,
                'role'=>'user',
                'prand'=>$prand,
                'btc_over'=>$ucoin['btc']?$ucoin['btc']:0,
                'btc_lock'=>$ucoin['btcd']?$ucoin['btcd']:0,
                'eth_over'=>$ucoin['eth']?$ucoin['eth']:0,
                'eth_lock'=>$ucoin['ethd']?$ucoin['ethd']:0,
                'eos_over'=>$ucoin['eos']?$ucoin['eos']:0,
                'eos_lock'=>$ucoin['eosd']?$ucoin['eosd']:0,
                'etc_over'=>$ucoin['etc']?$ucoin['etc']:0,
                'etc_lock'=>$ucoin['etcd']?$ucoin['etcd']:0,
                'xrp_over'=>$ucoin['xrp']?$ucoin['xrp']:0,
                'xrp_lock'=>$ucoin['xrpd']?$ucoin['xrpd']:0,
                'doge_over'=>$ucoin['doge']?$ucoin['doge']:0,
                'doge_lock'=>$ucoin['doged']?$ucoin['doged']:0,
                'ifc_over'=>$ucoin['ifc']?$ucoin['ifc']:0,
                'ifc_lock'=>$ucoin['ifcd']?$ucoin['ifcd']:0,
                'wc_over'=>$ucoin['wc']?$ucoin['wc']:0,
                'wc_lock'=>$ucoin['wcd']?$ucoin['wcd']:0,
                'wcg_over'=>$ucoin['wcg']?$ucoin['wcg']:0,
                'wcg_lock'=>$ucoin['wcgd']?$ucoin['wcgd']:0,
                'bcd_over'=>$ucoin['bcd']?$ucoin['bcd']:0,
                'bcd_lock'=>$ucoin['bcdd']?$ucoin['bcdd']:0,
                'qtum_over'=>$ucoin['qtum']?$ucoin['qtum']:0,
                'qtum_lock'=>$ucoin['qtumd']?$ucoin['qtumd']:0,
                'oioc_over'=>$ucoin['oioc']?$ucoin['oioc']:0,
                'oioc_lock'=>$ucoin['oiocd']?$ucoin['oiocd']:0,
                'eic_over'=>$ucoin['eic']?$ucoin['eic']:0,
                'eic_lock'=>$ucoin['eicd']?$ucoin['eicd']:0,
                'bcx_over'=>$ucoin['bcx']?$ucoin['bcx']:0,
                'bcx_lock'=>$ucoin['bcxd']?$ucoin['bcxd']:0,
                'btm_over'=>$ucoin['btm']?$ucoin['btm']:0,
                'btm_lock'=>$ucoin['btmd']?$ucoin['btmd']:0,
                'btmz_over'=>$ucoin['btmz']?$ucoin['btmz']:0,
                'btmz_lock'=>$ucoin['btmzd']?$ucoin['btmzd']:0,
                'credit'=>0,
                'created'=>$u['addtime'],
                'createip'=>$u['addip'],
                'updated'=>0,
                'updateip'=>'',
                'source'=>0,
                'registertype'=>2,
                'from_uid'=>$u['invit_1']?$u['invit_1']:0,
                'rebate'=>0,
                'google_key'=>'',
                'cnyx_over'=>$ucoin['cny']?$ucoin['cny']:0,
                'cnyx_lock'=>$ucoin['cnyd']?$ucoin['cnyd']:0,
            ];
            $id = $mo->insert($in_data);

            if(!$id){
                echo $mo->getLastSql();
                die("\n".$v['id'].'时出错');
            }

        }


        $mo_count = $mo->fOne("count(uid)");
        $huo_count = $huo->fOne("count(id)");

        echo $mo_count.'---'.$huo_count;

        die;
        "USER表导入结束";
    }


    //address表
    public function addressAction(){
        //连接数据库
        $huo = new Orm_Base(0,'huo','weike');
        $mo = new Orm_Base();

        $mo->table = "address_copy";
        $huo->table = "weike_user_coin";


        //记录最大ID
        $max_uid = $mo->order('id desc')->fOne('uid');
        if(!$max_uid) $max_uid = 0;



        $usercoins = $huo->field('id')->where("userid>{$max_uid}")->fList();
        foreach ($usercoins as $v){
            $v = $huo->where("id={$v['id']}")->fRow();

            if($v['btcb']){
                $btc = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['btcb'],
                    'coin'=>'btc',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$btc) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'btc错误');
                }
            }

            if($v['ethb']){
                $eth = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['ethb'],
                    'coin'=>'eth',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$eth) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$eth错误');
                }
            }

            if($v['etcb']){
                $etc = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['etcb'],
                    'coin'=>'etc',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$etc) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$etc错误');
                }
            }

            if($v['dogeb']){
               $doge = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['dogeb'],
                    'coin'=>'doge',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$doge) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$doge错误');
                }
            }
            if($v['wcb']){
                $wc = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['wcb'],
                    'coin'=>'wc',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$wc) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$wc错误');
                }
            }
            if($v['ifcb']){
                $ifc = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['ifcb'],
                    'coin'=>'ifc',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$ifc) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$ifc错误');
                }
            }
            if($v['qtumb']){
                $qtum = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['qtumb'],
                    'coin'=>'qtum',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$qtum) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$qtum错误');
                }
            }

            if($v['bcdb']){
                $bcd = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['bcdb'],
                    'coin'=>'bcd',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$bcd) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$bcd错误');
                }
            }

            if($v['bcxb']){
                $bcx = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['bcxb'],
                    'coin'=>'bcx',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$bcx) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$bcx错误');
                }
            }

            if($v['eacb']){
                $eac = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['eacb'],
                    'coin'=>'eac',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$eac){
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$eac错误');
                }
            }

            if($v['ejfb']){
                $ejf = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['ejfb'],
                    'coin'=>'ejf',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$ejf) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$ejf错误');
                }
            }

            if($v['oiocb']){
                $oioc = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['oiocb'],
                    'coin'=>'oioc',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$oioc) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$oioc错误');
                }
            }

            if($v['wcgb']){
                $wcg = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['wcgb'],
                    'coin'=>'wcg',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$wcg){
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$wcg错误');
                }
            }

            if($v['btmb']){
                $btm = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['btmb'],
                    'coin'=>'btm',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);

                if(!$btm){
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$btm错误');
                }
            }

            if($v['eosb']){
                $eos = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['eosb'],
                    'coin'=>'eos',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$eos) die($v['userid'].'$eos错误');
            }

            if(isset($v['eicb']) && $v['eicb']){
                $eic = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['eicb'],
                    'coin'=>'eic',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$eic) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$eic错误');
                }
            }

            if(isset($v['sieb']) && $v['sieb']){
                $sie = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['sieb'],
                    'coin'=>'sie',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$sie) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$sie错误');
                }
            }

            if(isset($v['drtb']) && $v['drtb']){
                $drt = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['drtb'],
                    'coin'=>'drt',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$drt) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$drt错误');
                }
            }

            if(isset($v['matb']) && $v['matb']){
                $mat = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['matb'],
                    'coin'=>'mat',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$mat) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$mat错误');
                }
            }

            if(isset($v['mtrb']) && $v['mtrb']){
                $mtr = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['mtrb'],
                    'coin'=>'mtr',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$mtr) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$mtr错误');
                }
            }
            if(isset($v['xrpb']) && $v['xrpb']){
                $xrp = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['xrpb'],
                    'coin'=>'xrp',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$xrp) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$xrp错误');
                }
            }

            if(isset($v['unihb']) && $v['unihb']){
                $unih = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['unihb'],
                    'coin'=>'unih',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$unih) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$unih错误');
                }
            }

            if(isset($v['wosb']) && $v['wosb']){
                $wos = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['wosb'],
                    'coin'=>'wos',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$wos){
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$wos错误');
                }
            }

            if(isset($v['eqtb']) && $v['eqtb']){
                $eqt = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['eqtb'],
                    'coin'=>'eqt',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$eqt) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$eqt错误');
                }
            }

            if(isset($v['ctmb']) && $v['ctmb']){
                $ctm = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['ctmb'],
                    'coin'=>'ctm',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$ctm) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$ctm错误');
                }
            }
            if(isset($v['btmzb']) && $v['btmzb']){
                $btmz = $mo->insert([
                    'uid'=>$v['userid'],
                    'address'=>$v['btmz'],
                    'coin'=>'btmz',
                    'status'=>0,
                    'created'=>time(),
                    'updated'=>time(),
                ]);
                if(!$btmz) {
                    echo $mo->getLastSql()."\n";
                    die($v['userid'].'$btmz错误');
                }
            }
        }
        $mo_count = $mo->fOne("count(id)");
        $huo_count = $huo->fOne("count(id)");

        echo $mo_count.'---'.$huo_count;

        die('成功');
    }



}