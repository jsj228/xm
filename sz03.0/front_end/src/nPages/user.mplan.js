import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import "nStyle/user/common.scss";
import "nStyle/user_tables.scss";
import 'nStyle/user/mplan/user_mplan.scss';

import axios from 'axios';
import Vue from 'vue';
import http from 'plugins/http';
import nav from 'nComponents/nav';
import getLanguagePack from '@/components/tradeLanguagePack';
import Alert from 'nComponents/Alert';
//table
import scrollTable from 'nComponents/scrollTable';
const qs = require('qs'); 

Vue.use(http);

let language = getLanguagePack();
//  || {
//   COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
//   COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
//   COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。",
//   BTN_SURE: "確定",
//   FORM_ERROR_ADDRESS: "請輸入有效的接受地址",
//   FORM_ERROR_NUMBER: "請輸入提取數額",
//   FORM_ERROR_OVER_NUMBER: "提取數額不符合要求",
//   FORM_MSG_TRADE_PWD: "請輸入交易密碼",
//   FORM_ERROR_TRADE_PWD: "交易密碼錯誤",
//   FORM_MSG_CAPTCHA: "看不清？點擊圖片更換驗證碼",
//   FORM_ERROR_CAPTCHA: "位數不正確",
//   FORM_MSG_CODE: "請輸入手機驗證碼",
//   FORM_ERROR_CODE: "手機驗證碼錯誤",
//   REWARD_SUCCESS: "獎勵提取成功"
// };
console.dir('language',language);
const mplan = new Vue({
  data() {
    return {
      index: 1,
      tabLoging: false,
      no_data: false,
      tabData: [],
      //提取奖励弹窗
      drawErr: {
        drawtype: 0,
        drawNum: 0
      },
      //php返回类型 匹配奖励余额
      optionData: { pair: '' },
      drawTypes: '',
      //提交
      submit_now: false,
      //奖励提取
      drawData: {
        coin: '',
        number: '',
        //trade  reg
        type: ''
      },
      showAlert: false,
      //最大输入值
      maxLength: 31,
      //错误提示弹窗
      nowStatus: false,
      errMesAlert: '',
      //弹窗点击回调
      callType: '',
       //分页
       cur: 1,
       all: '',

    };
  },
  watch: {
		cur: function(newValue, oldValue){
			
		}
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
  mounted() {
    nav();
    // this.tabs();
    this.getList();

    setTimeout(() => {
      this.tabs();
    }, 1000);
    //
    // let inputDom = document.getElementById('mplan_copy');
    // let oldUrl = inputDom.value;
    // console.log(oldUrl);
    // inputDom.value = oldUrl.replace(/[a-z.]+.com/, function(key) {
    //   return location.protocol + '//' + key;
    // });
  },
  methods: {
    btnClick(num){
      if(num!=this.cur){
        this.cur=num;
      }
    },
     //上一页
   pageClick(){
    axios.post('/ajax_user/invite',qs.stringify({page:this.cur}))
        .then(response => {
          mplan.tabData =response.data.data.list;
         
  
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
    //数据初始化
    init() {
      this.drawData = { coin: '', number: '', type: '' };
    },
    //tab 切换
    tabChange(index) {
      this.index = index;
      this.tabs();
    },
    //表格
    tabs() {
      this.$refs.scrollTab.getTbodyTds();
      this.tabLoging = true;
      this.$http.post('/ajax_user/invite', { type: this.index })
        .then(({ data, status, msg }) => { 
          var obj =data.list;
          this.tabData = obj;
          var totalPage =data.pages;
          this.all= totalPage;
          this.tabLoging = false;
          if (status == 1 && data.length == 0) {
            this.no_data = true;
          } else if (status == 1 && data.length > 0) {
            this.tabData = [...data];
            this.no_data = false;
          }
        })
        .then(() => {
          this.$refs.scrollTab.getTbodyTds();
          this.$nextTick(() => {
          this.$refs.scrollTab.getTbodyTds();
          });
        });
    },
    copy() {
      if (!document.execCommand) {
        this.nowStatus = true;
        this.errMesAlert = language['COPY_NOT_SUPPORT'];//複製不成功，不支持
      }
      let transfer = document.getElementById('J_CopyTransfer');
      let val = document.getElementById('mplan_copy').value;
      if (!transfer) {
        transfer = document.createElement('textarea');
        transfer.id = 'J_CopyTransfer';
        transfer.style.position = 'absolute';
        transfer.style.opacity = '0';
        transfer.style.width = '0';
        transfer.style.left = '0px';
        transfer.style.bottom = '0px';
        document.body.appendChild(transfer);
      }
      transfer.value = val || '';
      transfer.focus();
      transfer.select();
      try {
        let succ = document.execCommand('Copy', false, null);
        if (succ) {
          this.nowStatus = true;
          this.errMesAlert = language['COPY_SUCCESS'];//復製成功
        } else {
          this.nowStatus = true;
          this.errMesAlert = language['COPY_NOT_SUPPORT'];//複製不成功，不支持
        }
      } catch (e) {
        if (e) {
          this.nowStatus = true;
          this.errMesAlert = language['COPY_NOT_SUPPORT_UPDATE'];//複製不成功，升級瀏覽器
        }
      }
    },
    getList() {
      this.$http.post('/Ajax_User/limit', {})
      .then(({ data, status, msg }) => {
        if (status == 1) {
          this.optionData = data;
        }
      });
    },
    //验证
    verify(type, clickType) {
      switch (type) {
        case 'coin':
          if (!this.drawTypes) {
            this.drawErr.drawtype = 2;
            return false;
          } else {
            this.drawErr.drawtype = 0;
            return true;
          }
          break;
        case 'number':
          if (this.drawData.number && clickType != 'focus') {

            let num = this.drawData.number;
            //最低提取额
            let min = this.getMin();

            //提取额判断
            if (min > num) {
              this.drawErr.drawNum = 3;
              return false;
            } else {

              //小数点后位数
              let limit = this.getLimit();
              //小数点判断
              let spots = num.split('.').length;

              if (limit == 0 && spots > 1) {
                //不存在小数点
                this.drawErr.drawNum = 3;
                return false;
              } else if (limit > 0 && spots > 2) {
                //存在小数点
                this.drawErr.drawNum = 3;
                return false;
              } else {
                //获取小数点前的数字
                let halfNum = num.split('.')[0];
                if (halfNum.indexOf('0') == 0 && halfNum > 1) {
                  //第一位为0
                  this.drawErr.drawNum = 3;
                  return false;
                } else {
                  this.drawErr.drawNum = 0;
                  //限制最大输入
                  this.maxLength = parseInt(halfNum) + parseInt(limit);
                  return true;
                }
              }
            }
          } else if (clickType === 'focus') {
            this.drawErr.drawNum = 1;
            return false;
          } else if (clickType === 'blur') {
            this.drawErr.drawNum = 0;
          } else if (clickType === 'submit') {
            this.drawErr.drawNum = 2;
          }
          break;
        default:

      }
    },
    changeOp() {
      this.init();
      if (this.drawTypes === 'mcc_reg') {
        this.drawData.type = 'reg';
        this.drawData.coin = 'mcc';
      } else {
        this.drawData.type = 'trade';
        this.drawData.coin = this.drawTypes;
      }
      this.verify('coin');
    },
    //获取最小转出
    getMin() {
      if (this.optionData['pair'][`${this.drawTypes}`] || this.drawTypes === 'mcc_reg') {
        if (this.drawTypes === 'mcc_reg') return this.optionData['pair'].mcc.min;
        else return this.optionData['pair'][`${this.drawTypes}`].min;
      }
    },
    //获取小数点后限制
    getLimit() {
      if (this.optionData['pair'][`${this.drawTypes}`] || this.drawTypes === 'mcc_reg') {
        if (this.drawTypes === 'mcc_reg') return this.optionData['pair'].mcc.numLimit;
        else return this.optionData['pair'][`${this.drawTypes}`].numLimit;
      }
    },
    //提交
    submitData() {
      //验证
      this.submit_now = true;
      let coin = this.verify('coin', 'submit');
      let number = this.verify('number', 'submit');
      if (coin && number) {
        this.$http.post('/ajax_user/rebateIn', this.drawData)
        .then(({ data, status, msg }) => {
          this.nowStatus = true;
          this.errMesAlert = msg;
          this.submit_now = false;
          if (status == 1) {
            this.callType = 'draw';
          }
        });
      }
    },
    callfn() {
      if (this.callType == 'draw') {
        window.location.reload();
      }
    }
  },
  components: {
    scrollTable
  }
}).$mount('#mplan');
$(function(){
  $('[type="button"]').click(function(){
    //选择文本
  $(this).siblings().select()
      //执行复制命令
  document.execCommand('Copy');
  
  });
})


export default mplan;
