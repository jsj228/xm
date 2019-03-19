import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import "nStyle/user/common.scss";
import "nStyle/user_tables.scss";
import 'nStyle/c2c/c2c.scss';
import 'element-ui/lib/theme-chalk/index.css';

import "element-ui/lib/index.js";
import axios from 'axios';


import Vue from 'vue';
import Pagination from 'element-ui';
// import {Pagination} from 'element-ui';


import http from 'plugins/http';
import nav from 'nComponents/nav';
nav();
import getLanguagePack from '@/components/tradeLanguagePack';
import Alert from 'nComponents/Alert';
const qs = require('qs'); 


Vue.use(Pagination) ;
Vue.use(http);

let language = getLanguagePack() || {
  COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
  COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
  COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。",
  BTN_SURE: "確定",
  FORM_ERROR_ADDRESS: "請輸入有效的接受地址",
  FORM_ERROR_NUMBER: "請輸入提取數額",
  FORM_ERROR_OVER_NUMBER: "提取數額不符合要求",
  FORM_MSG_TRADE_PWD: "請輸入交易密碼",
  FORM_ERROR_TRADE_PWD: "交易密碼錯誤",
  FORM_MSG_CAPTCHA: "看不清？點擊圖片更換驗證碼",
  FORM_ERROR_CAPTCHA: "位數不正確",
  FORM_MSG_CODE: "請輸入手機驗證碼",
  FORM_ERROR_CODE: "手機驗證碼錯誤",
  REWARD_SUCCESS: "獎勵提取成功"
};

const mplan = new Vue({
  
  data() {
    return {
      lookData:{},
      list:[],
      //买入量
      Purchase:'',
      //卖出量
      Sellout:'',
      mask:false,
      sellwindow:false,
      Payment:false,
      buyingquantity:false,
      popup:false,
      //弹窗的提示
      updisplay:'',
      //交易买入密码弹窗
      transactionpwd:false,
      //交易卖出密码弹窗
      Selltransaction:false,
      //密码输入错误等等所有弹窗
      Backgroundpop:false,
      //确认收款按钮
      isshow:false,
      //交易密码里面的输入值
      trpwd:'',
      sellpwd:'',
      //密码输入错误等等所有弹窗里面的提示语
      bckpop:'',
      //确认收款按钮提示语
	  condow:'',
	  //支付弹窗
	  paydetails:false,

	//   微信账号
	  WeChataccount:'',
	//   支付方式
	  Paymentmethod:'',
	//   卖方手机号
	  Cellphone:'',
	//   交易方式
	  Transactionmode:'',
	//   卖方姓名
	  Sellern:'',
	//   交易金额
	  tramount:'',
	//   订单号
	  Ordernumber:'',
	//   状态
	  state:'',


      currentPage:1, //初始页
      pagesize:10,    //    每页的数据
      
      
    };
  },
   
  //生命周期
  mounted() {
    this.tabs();
    // setTimeout(() => {
    //   this.tabs();
    // }, 1000);
 
   
  },
  //方法
  methods: {
  
   
    
    //立即买入按钮
    buyimmediately(){ 
      if(!this.Purchase){
        this.mask=true;
        this.buyingquantity=true;
      }else{
        if(this.Purchase<100){
          this.mask=true;
          this.popup=true;
          this.updisplay="交易的金额最少100";
          
        }else if(this.Purchase%100 !== 0){
          this.mask=true;
          this.popup=true;
          this.updisplay="交易价格必须是100的整数倍";
        }else{
          this.mask=true;
          this.transactionpwd=true;
        }  
      }
      
    },
    //立即卖出按钮
    sellimmediately(){
      if(!this.Sellout){
        this.mask=true;
        this.sellwindow=true;
      }else{
        if(this.Sellout<100){
          this.mask=true;
          this.popup=true;
          this.updisplay="交易的金额最少100";
          
        }else if(this.Sellout%100 !== 0){
          this.mask=true;
          this.popup=true;
          this.updisplay="交易价格必须是100的整数倍";
        }else{
          this.mask=true;
          this.Selltransaction=true;
        }  
      }
      
    },
    //确定按钮
    Determine(){
      this.mask=false;
      this.sellwindow=false;
    },
    Determines(){
      this.mask=false;
      this.buyingquantity=false;
    },
    
    Determineplay(){
      mplan.mask=false;
      mplan.popup=false;
    },
    //交易买入密码的确认按钮
    Determineplaypwd(){
      
      var pur=this.Purchase;
      var trpwd=this.trpwd;
      console.log(trpwd);
      axios.post("/c2c/trade",
       qs.stringify({num:pur,type:1,val:trpwd})
     ).then(function (response) {
        mplan.mask=true;
        mplan.Backgroundpop=true;
        mplan.bckpop=response.data.msg;
          setTimeout(() => {
            mplan.mask=false;
            mplan.Backgroundpop=false;
            location.reload();
          },2000)
     })
     .catch(function (error) {
     });
       mplan.mask=false;
       mplan.transactionpwd=false;
       
    },
   //卖出交易密码确认按钮
    Selltransac(){
      var pur=this.Sellout;
      var sellpwd=this.sellpwd;
      console.log(sellpwd);
      axios.post("/c2c/trade",
       qs.stringify({num:pur,type:2,val:sellpwd})
     ).then(function (response) {
      mplan.mask=true;
      mplan.Backgroundpop=true;
      mplan.bckpop=response.data.msg;
        setTimeout(() => {
          mplan.mask=false;
          mplan.Backgroundpop=false;
          location.reload();
        },2000)
     })
     .catch(function (error) {
     　　console.log('error');
     });
       mplan.mask=false;
       mplan.Selltransaction=false;
       
    },
//确认收款按钮
    Confirmreceipt(id){
	  
	  axios.post("c2c/confirm",
	  qs.stringify({id,type:1})
    ).then(function (response) {
		mplan.mask=true;
		mplan.isshow=true;
		mplan.condow=response.data.msg;
		console.log(response.data.msg);
		setTimeout(() => {
			mplan.mask=false;
			mplan.isshow=false;
			location.reload();
		},2000)
    })
    .catch(function (error) {
    　　console.log('error');
    });
    },
    //撤销
    Revoke(id){
	  
      axios.post("c2c/revoke",qs.stringify({id})
     ).then(function (response) {
      mplan.mask=true;
      mplan.isshow=true;
      mplan.condow=response.data.msg;
        setTimeout(() => {
          mplan.mask=false;
          mplan. isshow=false;
          location.reload();
        },2000)
     })
     .catch(function (error) {
     　　console.log('error');
     });
    },
    //我已付款
    paid(id){
	axios.post("c2c/pay",
	  qs.stringify({id,type:1})
     ).then(function (response) {
	    mplan.mask=true;
      mplan.isshow=true;
      mplan.condow=response.data.msg;
        setTimeout(() => {
          mplan.mask=false;
          mplan.isshow=false;
           location.reload();
        },2000)
        
     }).catch(function (error) {
     　　console.log('error');
     });
	},
	//查看支付方式
	show(id,type){
		axios.post("c2c/alert",
		qs.stringify({id,type})
	   ).then(function (response) {
       console.log(response);
		 mplan.mask=true;
     mplan.paydetails=true;
     mplan.lookData=response.data;
		  
	   }).catch(function (error) {
	   　　console.log('error');
	   });
	},
	//关闭支付详情弹窗
	clones(){
		mplan.mask=false;
		mplan.paydetails=false;
	},
    

    //tab 切换
    tabChange(index) {
      this.index = index;
      this.tabs();
    },

    //表格
    tabs() {
      this.$http.post('/c2c/index')
      .then(response => {
        console.log(response);
        var obj = JSON.parse(response.data);
        //this.list = obj;
        this.list = [obj];
        //this.list.push(response.data);
       
        })
        
    },
   
  },
  components: {
    pageinfo
  }
 
}).$mount('#c2c');


//分页
import pageinfo from 'nComponents/pageinfo.js';

export default mplan;




