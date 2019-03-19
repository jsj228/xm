<?php
/**
 * Created by PhpStorm.
 * User: dream
 * Date: 4/25/2018
 * Time: 15:26 PM
 */

namespace Admin\Controller;

class ApiController extends AdminController
{
    //合并表
/*
Field           Type              Collation        Null    Key     Default  Extra           Privileges                       Comment                                             
--------------  ----------------  ---------------  ------  ------  -------  --------------  -------------------------------  ----------------------------------------------------
id              int(11)           (NULL)           NO      PRI     (NULL)   auto_increment  select,insert,update,references                                                      
userid          int(11) unsigned  (NULL)           NO              0                        select,insert,update,references  用户id                                            
award_currency  varchar(50)       utf8_general_ci  NO                                       select,insert,update,references  奖励币种                                        
award_num       int(11) unsigned  (NULL)           NO              0                        select,insert,update,references  奖励数量                                        
addtime         int(11) unsigned  (NULL)           NO              0                        select,insert,update,references  添加时间                                        
type            int(1) unsigned   (NULL)           NO              0                        select,insert,update,references  1 认证奖励 2 邀请充值奖励 3 分享奖励  
status          int(1) unsigned   (NULL)           NO              0                        select,insert,update,references  0 未奖励 1 已奖励                             
czr             varchar(50)       utf8_general_ci  NO                                       select,insert,update,references  操作人                                           


Field        Type              Collation        Null    Key     Default  Extra           Privileges                       Comment                                                 
-----------  ----------------  ---------------  ------  ------  -------  --------------  -------------------------------  --------------------------------------------------------
id           int(10) unsigned  (NULL)           NO      PRI     (NULL)   auto_increment  select,insert,update,references                                                          
users        varchar(50)       utf8_general_ci  NO              (NULL)                   select,insert,update,references  认证人，邀请人                                   
one          varchar(20)       utf8_general_ci  NO              (NULL)                   select,insert,update,references  当前用户                                            
two          varchar(20)       utf8_general_ci  NO              (NULL)                   select,insert,update,references  邀请人                                               
n            tinyint(4)        (NULL)           NO              (NULL)                   select,insert,update,references  奖励人数                                            
nums         decimal(9,4)      (NULL)           NO              (NULL)                   select,insert,update,references  奖励数量                                            
coin         int(11)           (NULL)           NO              (NULL)                   select,insert,update,references  奖励币种                                            
active_time  int(11)           (NULL)           NO              (NULL)                   select,insert,update,references  活动开始时间                                      
admin_id     int(11)           (NULL)           NO              (NULL)                   select,insert,update,references  活动策划人                                         
times        tinyint(4)        (NULL)           NO              (NULL)                   select,insert,update,references  活动次数级别                                      
add_time     int(11)           (NULL)           NO              (NULL)                   select,insert,update,references  添加时间                                            
status       tinyint(4)        (NULL)           NO              1                        select,insert,update,references  0未发放1已发放                                    
type         tinyint(4)        (NULL)           YES             1                        select,insert,update,references  1:认证奖励 2：邀请充值奖励 3：分享奖励  
*/
    function index(){
        /*$data = M('UserAwardIfc')
            ->alias('a')
            ->join('left join weike_user u on a.userid = u.id')
            ->join('left join weike_admin ad on a.czr = ad.username')
            ->join('left join weike_coin c on a.award_currency = c.name')
            ->field('0 as id,u.username as users,u.username as one,"" as two,1 as n,a.award_num as nums,c.id as coin,a.addtime as active_time,ad.id as admin_id,0 as times,a.addtime as add_time,a.status,a.type')
            ->select();
        //print(M('UserAwardIfc')->getLastSql());die;
        M('RegisterAward')->addAll($data);
        $i=0;
        foreach ($data as $k => $v) {
            $i++;
            print_r($v);
            print("&nbsp;&nbsp;&nbsp;&nbsp;".$i);
            print("<br /><br />");
        }*/
        //file_put_contents('/tmp/model.md', date('Y-m-d H:i:s',time()).M()->getLastSql()."\n", FILE_APPEND | LOCK_EX);
        die(phpinfo());//ESKKWkYU12vJNKVf
    }
}