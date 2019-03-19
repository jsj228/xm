<?php
use think\Route;


Route::group(['method'=>'get','ext'=>''],function(){
    Route::get('/login/paypassword','home/login/paypassword',[],[]);
   //注册
    Route::get('/login/register','home/login/register',[],[]);
    Route::get('/login/upregister','home/login/upregister',[],[]);
    Route::get('/login/truename','home/login/truename',[],[]);
    Route::get('/login/info','home/login/info',[],[]);
    //找回密码
    Route::get('/login/findpwdconfirm','home/login/findpwdconfirm',[],[]);
    Route::get('/Findpwd/findpwdinfo','home/Findpwd/findpwdinfo',[],[]);
   //交易密码
    Route::get('/login/findpwd','home/login/findpwd',[],[]);
    Route::get('/Findpwd/findpwdconfirm','home/Findpwd/findpwdconfirm',[],[]);
    //导航
   
	Route::get('/Finance/index','home/Finance/index',[],[]);
	Route::get('/Article/index','home/Article/index',[],[]);
	Route::get('/Order/index','home/Order/index',[],[]);
    //工单	
	Route::get('/Article/index/:id','home/Article/index',[],[]);
	Route::get('/Order/history','home/Order/history',[],[]);
	Route::get('/Order/reply/','home/Order/reply',[],[]);


    //工单	
    Route::get('/Order/index','home/Order/index',[],[]);
	Route::get('/Article/detail/','home/Article/detail',[],[]);
	Route::get('/Order/history','home/Order/history',[],[]);
	Route::get('/Order/reply/','home/Order/reply',[],[]);

	//交易请求
	Route::get('/trade/index/','home/trade/index',[],[]);
	Route::get('/trade/ordinary/','home/trade/ordinary',[],[]);
	Route::get('/Chart/getMarketOrdinaryJson/','home/Chart/getMarketOrdinaryJson',[],[]);
	Route::get('/chart/ordinary/','home/chart/ordinary',[],[]);
	Route::get('/Chart/getJsonData/','home/Chart/getJsonData',[],[]);
	
	Route::get('/weike/index/','home/weike/index',[],[]);
	Route::get('/weike/weike_pro/','home/weike/weike_pro',[],[]);

	Route::get('/weike/weike_kline_h_depths','home/weike/weike_kline_h_depths',[],[]);
	Route::get('/weike/weike_kline_h_kline','home/weike/weike_kline_h_kline',[],[]);
    Route::get('/weike/weike_chart_json','home/weike/weike_chart_json',[],[]);

	Route::get('/Trade/chart/','home/Trade/chart',[],[]);

    //财务
     Route::get('/Finance/index','home/Finance/index',[],[]);
	Route::get('/Finance/mycz','home/Finance/mycz',[],[]);
    Route::get('/Pay/mycz','home/Pay/mycz',[],[]);
	Route::get('/Finance/mytx','home/Finance/mytx',[],[]);
	Route::get('/Activity/wcg_zr','home/Activity/wcg_zr',[],[]);
	Route::get('/Finance/myzr','home/Finance/myzr',[],[]);
	Route::get('/Finance/myzc','home/Finance/myzc',[],[]);
	Route::get('/Finance/myjp','home/Finance/myjp',[],[]);
	Route::get('/Finance/mytj','home/Finance/mytj',[],[]);
	Route::get('/Finance/mywd','home/Finance/mywd',[],[]);
	Route::get('/Finance/mywt','home/Finance/mywt',[],[]);
	Route::get('/Finance/mycj','home/Finance/mycj',[],[]);
	Route::get('/Finance/myawardifc','home/Finance/myawardifc',[],[]);
	Route::get('/Finance/fenhong','home/Finance/fenhong',[],[]);
	Route::get('/Activity/mywc','home/Activity/mywc',[],[]);
	Route::get('/Activity/mywcg','home/Activity/mywcg',[],[]);
	Route::get('/Finance/bank','home/Finance/bank',[],[]);
	Route::get('/Finance/qianbao','home/Finance/qianbao',[],[]);
    Route::get('/Activity/mywc','home/Activity/mywc',[],[]);
    Route::get('/Activity/mywcg','home/Activity/mywcg',[],[]);
    Route::get('/Finance/bank','home/Finance/bank',[],[]);
    Route::get('/Finance/qianbao','home/Finance/qianbao',[],[]);
    Route::get('/Finance/outlog','home/Finance/outlog',[],[]);
    Route::get('/finance/coinoutlog','home/Finance/coinoutlog',[],[]);
    Route::get('/finance/fhindex','home/Finance/fhindex',[],[]);
    Route::get('/finance/myfhroebx','home/Finance/myfhroebx',[],[]);
    Route::get('/Pay/mycz','home/Pay/mycz',[],[]);
    Route::get('/Finance/mtrzr','home/Finance/mtrzr',[],[]);
    Route::get('/Finance/c2czfb','home/Finance/c2czfb',[],[]);
    Route::get('/Finance/c2cwx','home/Finance/c2cwx',[],[]);
    Route::get('/Finance/myinherit','home/Finance/myinherit',[],[]);



	//安全中心
	Route::get('/User/index','home/User/index',[],[]);
	Route::get('/User/log','home/User/log',[],[]);
	Route::get('/User/moble','home/User/moble',[],[]);
	Route::get('/User/tpwdsetting','home/user/tpwdsetting',[],[]);
	Route::get('/User/nameauth','home/User/nameauth',[],[]);
	Route::get('/User/password','home/User/password',[],[]);
    Route::get('/user/paypassword','home/user/paypassword',[],[]);
    Route::get('/Activity/index','home/Activity/index',[],[]);
    Route::get('/Login/findpwd','home/Login/findpwd',[],[]);
    Route::get('/Findpwd/findpwd','home/Findpwd/findpwd',[],[]);
    Route::get('/Fenhong/index','home/Fenhong/index',[],[]);
    Route::get('/Issue/index','home/Issue/index',[],[]);
    Route::get('/Vote/index','home/Vote/index',[],[]);


    //Login
    Route::get('/Login/loginout','home/Login/loginout',[],[]);
    //公告
    Route::get('/article/index','home/article/index',[],[]);

    Route::get('/article/index/:id','home/article/index',[],[]);
    Route::get('/article/detail/:id','home/article/detail',[],[]);

    Route::get('/Activity/wcgChexiao/','home/Activity/wcgChexiao',[],[]);
    //C2C
    Route::get('/C2c/index/','home/C2c/index',[],[]);
});
Route::group(['method'=>'post','ext'=>''],function(){

     Route::post('/user/uptpwdsetting','home/user/uptpwdsetting',[],[]);
     //工单
     Route::post('/Order/jieshou','home/Order/jieshou',[],[]);

     Route::post('/Order/reply/','home/Order/reply',[],[]);
     //注册
     Route::post('/Login/upregister/','home/Login/upregister',[],[]);
     Route::post('/Login/upregister2/','home/Login/upregister2',[],[]);
     Route::post('/Login/upregister3/','home/Login/upregister3',[],[]);
     //云尊币
     Route::post('/Activity/mywc','home/Activity/mywc',[],[]);
     //华克金
     Route::post('/Activity/mywcg','home/Activity/mywcg',[],[]);
     //修密码
     Route::post('/user/uppassword_qq','home/user/uppassword_qq',[],[]);
     //找回密码
     Route::post('/login/password_up','home/login/password_up',[],[]);
     //交易密码
     Route::post('/Findpwd/password_up','home/Findpwd/password_up',[],[]);
     Route::post('/user/uppaypassword_qq','home/user/uppaypassword_qq',[],[]);
     Route::post('/Findpwd/findpwd','home/Findpwd/findpwd',[],[]);
     Route::post('/user/uptpwdsetting','home/user/uptpwdsetting',[],[]);
     //财务中心
     Route::post('/Finance/autoczUp','home/Finance/autoczUp',[],[]);
     Route::post('/Finance/myczChexiao','home/Finance/myczChexiao',[],[]);
     Route::post('/User/upqianbao/','home/User/upqianbao',[],[]);
     Route::post('/finan/pay_huikuan/','home/finan/pay_huikuan',[],[]);
     Route::post('/Finance/mytxChexiao/','home/Finance/mytxChexiao',[],[]);
     Route::post('/Finance/myczUp/','home/Finance/myczUp',[],[]);
     Route::post('/Finance/autoczUp/','home/Finance/autoczUp',[],[]);
     Route::post('/Finance/myczHuikuan/','home/Finance/myczHuikuan',[],[]);
     Route::post('/Activity/wcgzr/','home/Activity/wcgzr',[],[]);
     Route::post('/Activity/wcgChexiao/','home/Activity/wcgChexiao',[],[]);
     Route::post('/Activity/wcgzr_info/','home/Activity/wcgzr_info',[],[]);
    Route::post('/weike/weike_kline_h_kline','home/weike/weike_kline_h_kline',[],[]);
     //安全中心
     Route::post('/Login/check_pwdmoble/','home/Login/check_pwdmoble',[],[]);
     Route::post('/verify/moble_findpwd/','home/verify/moble_findpwd',[],[]);
     Route::post('/Login/findpwd','home/Login/findpwd',[],[]);
     Route::post('/Login/findpwd','home/Login/findpwd',[],[]);
     Route::post('/ajax/imgUser','home/ajax/imgUser',[],[]);
     Route::post('/User/userImage','home/User/userImage',[],[]);
     //后台
      Route::post('/Login/send_login_code','admin/Login/send_login_code',[],[]);
      Route::post('/Login/index','admin/Login/index',[],[]);
    //Route::post('/Finance/myInherit','home/Finance/myInherit',[],[]);
     


});

