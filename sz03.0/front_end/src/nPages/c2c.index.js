import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import "nStyle/user/common.scss";
import "nStyle/user_tables.scss";
import 'nStyle/c2c/c2c.scss';




// import "element-ui/lib/index.js";
import axios from 'axios';
import Vue from 'vue';
import http from 'plugins/http';
import nav from 'nComponents/nav';
// import register from 'nComponents/register';
// import getLanguagePack from '@/components/tradeLanguagePack';
// import Pagination from 'element-ui';
// import {Pagination} from 'element-ui';
// Vue.use(Pagination) ;


nav();
// import getLanguagePack from '@/components/tradeLanguagePack';
// import Alert from 'nComponents/Alert';
const qs = require('qs'); 



Vue.use(http);


const c2c = new Vue({
  data() {
    return {
      lookData:[],
      list:{},
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
      //分页
      cur: 1,
      all: '',
      //token
      token:'',
      //冻结余额
      cnyxlock:'',
      //可用余额
      cnyxover:'',
      //加急
      Urgent:'1',
      //充值
      recharge:false,
      //提现
      drawal:false,
      //充值密码：
      sellpwds:'',
      //充值数量：
      numwer:'',
      rechargeId:'',
      msgg:'',
      msgs:'',
      numdrawal:'',
      pwdrawal:'',
      //忘记密码
      forgetPwd:false,
      Revokes:true,
      //双次点击
      dubclick:true,
      //页面中的唯一值token
      repeat_del:document.getElementById('token').value,
    };
  },
  watch: {
		cur: function(newValue, oldValue){
		}
	},
  //生命周期
  mounted() {
    this.tabs();
  },
  computed: {
      
     indexs(){
			var left = 1;
			var right = this.all;
			var arr = [];
			if(this.all>=7){
				if(this.cur>4 && this.cur<this.all-3){
					left = this.cur-3;
					right = this.cur+3;
				}else if(this.cur<=4){
                    left=1;
                    right=7;
				}else{
					left=this.all-6;
					right=this.all;
				}
			}
			while(left<=right){
				arr.push(left);
				left++;
			}
			return arr;
		}
  },
  //方法
  methods: {
    //登录弹框
    // login() {
    //   register.loginAlert();
    // },
    // register() {
    //       register.registerAlert();
    //     },
    focus(){
      this.msgs='';
      this.msgg='';
    },

    //充值
    Recharges(id){
      this.recharge=true;
      this.rechargeId=id;
    }, 
    cashwithdrawal(id){
      this.drawal=true;
      this.rechargeId=id;
    },
    Recharge(){
     var orids=this.rechargeId;
     var sellpwds=this.sellpwds;
     var numwer=this.numwer;
     axios.post("/ajax_c2c/unregister",
      qs.stringify({id:orids,type:1,sellpwds:sellpwds,numwer:numwer})
    ).then(function (response) {
      if(response.data.data=='price'){
        c2c.msgg=response.data.msg;
      }else if(response.data.data=='psw'){
        c2c.msgs=response.data.msg;
        c2c.forgetPwd=true;
      }else{
       c2c.mask=true;
       c2c.Backgroundpop=true;
       c2c.bckpop=response.data.msg;
       c2c.recharge=false;
       c2c.rechargeId='';
       c2c.sellpwds='';
       c2c.numwer='';
       c2c.forgetPwd=true;
       setTimeout(() => {
        c2c.mask=false;
        c2c.Backgroundpop=false;
      },2000);
      }
     
      
     
    })
    .catch(function (error) {
    　　console.log('error');
    });
    },


   //提现确认按钮
     withdrawal(){
      var orids=this.rechargeId;
      var numdrawal=this.numdrawal;
      var pwdrawal=this.pwdrawal;
       axios.post("/ajax_c2c/handtrade",
       qs.stringify({id:orids,type:2,sellpwds:pwdrawal,price:numdrawal})
     ).then(function (response) {
       if(response.data.data=='price'){
         c2c.msgg=response.data.msg;
       }else if(response.data.data=='psw'){
         c2c.msgs=response.data.msg;
         c2c.forgetPwd=true;
       }else{
        c2c.mask=true;
        c2c.Backgroundpop=true;
        c2c.bckpop=response.data.msg;
        c2c.drawal=false;
        c2c.rechargeId='';
        c2c.pwdrawal='';
        c2c.numdrawal='';
        c2c.forgetPwd=true;
        setTimeout(() => {
         c2c.mask=false;
         c2c.Backgroundpop=false;
       },2000);
       }
      
       
      
     })
     .catch(function (error) {
     　　console.log('error');
     });
     },
    
  //分页
  closeicon(){
    this.Selltransaction=false;
    this.transactionpwd=false;
    this.recharge=false;
    this.drawal=false;
  },
  btnClick(num){
    if(num!=this.cur){
      this.cur=num;
    }
  },
  //上一页
  pageClick(){
    axios.post('/ajax_c2c/ajaxorder',qs.stringify({page:this.cur}))
        .then(response => {
           c2c.list =response.data.data.list;
    })
  },
  //跳转
  pageSkip(){
    var maxPage = this.all;
    var skipPage = Number(document.getElementsByClassName("jumppage")[0].value);
    if(!skipPage){
      alert("请输入跳转页码");
      return;
    }else if(skipPage<1 || skipPage>maxPage){
      alert("您输入的页码超过页数范围了！");
      return;
    }else{
      //this.cur=skipPage;
      this.btnClick(skipPage);
      this.pageClick();
    }
  },
  
   
    
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
          // this.mask=true;
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
          // this.mask=true;
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
      c2c.mask=false;
      c2c.popup=false;
    },
    //交易买入密码的确认按钮
    Determineplaypwd(){
      if(!this.dubclick)return false;
      this.dubclick=false;

      var pur=this.Purchase;
      var trpwd=this.trpwd;
      this.$http.post("/ajax_c2c/trade",
        {'num':pur,'type':1,'val':trpwd,'repeat_del':this.repeat_del}
      ).then((res)=> {
          c2c.mask=true;
          c2c.Backgroundpop=true;
          c2c.bckpop=res.msg;
            setTimeout(() => {
              c2c.mask=false;
              c2c.Backgroundpop=false;
            },3000);
            c2c.tabs();
          // 回复状态
          this.dubclick=true;
          this.repeat_del=res.data.repeat;
          if(res.status){c2c.trpwd='';}
      }).catch(function (error) {
        console.log(err)
      });
      c2c.mask=false;
      c2c.transactionpwd=false;
    },
   //卖出交易密码确认按钮
    Selltransac(){
      //防重复点击
      if(!this.dubclick)return false;
      this.dubclick=false;
      var pur=this.Sellout;
      var sellpwd=this.sellpwd;
      var Urgent=this.Urgent;
      this.$http.post('/ajax_c2c/trade',
        {'num':pur,'type':2,'val':sellpwd,'selltype':Urgent,'repeat_del':c2c.repeat_del}
      ).then((res)=>{
        c2c.mask=true;
        c2c.Backgroundpop=true;
        c2c.bckpop=res.msg;
          setTimeout(() => {
            c2c.mask=false;
            c2c.Backgroundpop=false;
          },2000);
          c2c.tabs();

          // 回复状态
          c2c.dubclick=true;
          c2c.repeat_del=res.data.repeat;
          if(res.status){  c2c.sellpwd='';}
      }).catch((err)=> {
        　　console.log(err);
      });
    c2c.mask=false;
    c2c.Selltransaction=false;
    },
  //撤销
  Revoke(id,i){
    if(!this.dubclick)return false;
    this.dubclick=false;
    this.$http.post("/ajax_c2c/revoke",{id,'repeat_del':this.repeat_del}
    ).then((res)=>{
      if(res.status){c2c.list[i].status=3;}
      c2c.mask=true;
      c2c.isshow=true;
      c2c.condow=res.msg;
      setTimeout(() => {
        c2c.mask=false;
        c2c. isshow=false;
      },2000);
      // 回复状态jsj???
      this.dubclick=true;
      this.repeat_del=res.data.repeat;
    }).catch(function (error) {
    　　console.log('error');
    });
  },
//确认收款按钮
  Confirmreceipt(id,i){
      if(!this.dubclick)return false;
      this.dubclick=false;

      
      this.$http.post("/ajax_c2c/confirm",
      {id,type:1,'repeat_del':this.repeat_del}
      ).then(function (res) {
        if(res.status){c2c.list[i].status=1}
        c2c.mask=true;
        c2c.isshow=true;
        c2c.condow=res.msg;
      setTimeout(() => {
        c2c.mask=false;
        c2c.isshow=false;
      },2000);
      c2c.dubclick=true;
      c2c.repeat_del=res.data.repeat;
    }).catch( (error)=>{
    　　console.log('error');
    });
  },
//我已付款
  paid(id,i){
    if(!this.dubclick)return false;
    this.dubclick=false;
    c2c.Revokes=false;
	  this.$http.post("/ajax_c2c/pay",
	    {id,'type':1,'repeat_del':this.repeat_del}
     ).then(function (res) {
      if(res.status){c2c.list[i].status=2}
	    c2c.mask=true;
      c2c.isshow=true;
      c2c.condow=res.msg;
        setTimeout(() => {
          c2c.mask=false;
          c2c.isshow=false;
        },1000);
        c2c.dubclick=true;
        c2c.repeat_del=res.data.repeat;
     }).catch(function (error) {
     　　console.log(error);
     });
	},
  //查看支付方式
  //???
	show(id,type){
    if(!this.dubclick){return false;}
    this.dubclick=false;
		axios.post("/c2c/alert",
		qs.stringify({id,type})
	   ).then( (res)=>{
		  c2c.lookData=res.data;
      c2c.mask=true;
      c2c.paydetails=true;
      c2c.dubclick=true;
	   }).catch(function (error) {
	   　　console.log('error');
	   });
	},
	//关闭支付详情弹窗
	clones(){
		c2c.mask=false;
		c2c.paydetails=false;
	},
  //表格
  tabs() {
      this.$http.post('/ajax_c2c/ajaxorder')
      .then(response => {
        this.list = response.data.list;
        var totalPage =response.data.totalPage;
        this.all= totalPage;
        this.cnyxlock=response.data.cnyx_lock;
        this.cnyxover=response.data.cnyx_over;
      })
  },
  },
  components: {
    pageinfo
  }
 
}).$mount('#c2c');


//分页
import pageinfo from 'nComponents/pageinfo.js';

export default c2c;




