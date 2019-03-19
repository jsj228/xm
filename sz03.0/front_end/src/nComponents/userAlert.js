import 'nStyle/init.scss';
import 'nStyle/theme.scss';

import Vue from 'vue';
import http from 'tools/http';
import Alert from '@/tools/alert/alert';

export default () => {
  if (document.getElementById('userAlert')) {
    return new Vue({
      data: {
        userShow: false,
        alertData: {},
        // chooseCoin: 'btc',
        //划转数量判断
        //0为不显示 1为默认提示 2为空提示 3格式 4后台返回错误提示 5通过
        verifyNums: 0,
        verifyCaptcha: 0,
        changeNums: '',
        changeCaptcha: '',
        //提交后 后台返回错误提示
        errMes: "",
        //可输入长度
        maxlength: '',
        //loading
        loading: false,
        //弹窗
        myAlert: '',
        outShow: true,
        inShow: false,
        //禁止按钮点击
        stopClick: false,
        //划转数量限额
        errNum: '',
        imgSrc: ''
      },
      mounted() {
        this.myAlert = new Alert();
        this.img_ver();
      },
      methods: {
        //图形验证码
        img_ver() {
          this.imgSrc = `/index/captcha?v=${parseInt(Math.random() * 10000000)}`;
        },
        //币币到法币弹窗
        showUserAlert(showUserAlert) {
          if (showUserAlert) {
            this.alertData = showUserAlert;
          }
          // console.log(this.alertData);
          this.userShow = true;
          this.outShow = true;
          this.inShow = false;
          this.init();
        },
        //法币到币币弹窗
        showUserFaAlert(showUserAlert) {
          if (showUserAlert) {
            this.alertData = showUserAlert;
          }
          this.userShow = true;
          this.outShow = false;
          this.inShow = true;
          this.init();
        },
        init() {
          this.changeNums = "";
          this.changeCaptcha = "";
          this.verifyNums = 0;
          this.errMes = "";
        },
        //关闭弹窗
        closeFn() {
          this.userShow = false;
        },
        //点击后全部划转
        allexchange() {
          //outshow true 币币到法币
          //inShow true 法币到币币
          let allRange = '';
          let maxNum = parseFloat(this.alertData.maxrange);
          let faNum = parseFloat(this.alertData.priceoverfa);
          let biNum = parseFloat(this.alertData.priceoverbi);

          if (this.outShow && maxNum > biNum) {
            allRange = biNum;
          } else if (this.outShow && maxNum <= biNum) {
            allRange = maxNum;
          }

          if (this.inShow && maxNum > faNum) {
            allRange = faNum;
          } else if (this.inShow && maxNum <= faNum) {
            allRange = maxNum;
          }
          this.changeNums = allRange;
        },
        //验证
        verifInput(type, method) {
          //5通过判断
          switch (type) {
            case 'nums':
              if (this.changeNums) {
                let val = this.changeNums;
                if (val == 0) {
                  this.verifyNums = 2;
                } else {
                  let maxValBi = parseFloat(this.alertData.priceoverbi);
                  let maxValFa = parseFloat(this.alertData.priceoverfa);
                  let vals = parseFloat(val);
                  if ((this.outShow && maxValBi >= vals) || (this.inShow && maxValFa >= vals)) {
                    this.verifyNums = 5;
                  } else if ((this.outShow && maxValBi < vals) || (this.inShow && maxValFa < vals)) {
                    this.verifyNums = 8;
                  }
                }
              } else if (!this.verifyNums && (method === 'submit' || method === 'enter')) {
                this.verifyNums = 2;
              } else if (!this.verifyNums && method === 'focus') {
                this.verifyNums = 1;
              } else {
                this.verifyNums = 0;
              }
              break;
              case 'captcha':
              if (this.changeCaptcha) {
                let val = this.changeCaptcha;
                if (val.length < 4) {
                  this.verifyCaptcha = 2;
                } else {
                  this.verifyCaptcha = 5;
                }
              } else if (!this.changeCaptcha && (method === 'submit' || method === 'enter')) {
                this.verifyCaptcha = 3;
              } else if (!this.changeCaptcha && method === 'focus') {
                this.verifyCaptcha = 1;
              } else {
                this.verifyCaptcha = 0;
              }
              break;
            default:
            break;

          }
        },
        //按键 输入是限制输入内容 只能为 数字和点
        inputVer(val) {
          this.changeNums = val.replace(/[^\d.]/g, "");
          //有小数点的时候限制小数点后的输入
          if (val.indexOf(".") != '-1' && val.split('.').length <= 2) {
            let len1 = val.split(".")[0].length;
            //暂时限定小数点后八位
            this.maxlength = (len1 + 1) + parseInt(this.alertData.max);
            if (val.indexOf('.') === 0) {
              this.changeNums = '0' + val;
            }
          } else if (val.indexOf(".") != '-1' && val.split('.').length > 2) {
            this.verifyNums = 2;
          }
          //清空的时候 把maxlength限制清空
          if (!val) {
            this.maxlength = "";
          }
        },
        //只能輸入數字和字母
        OnlyNum(val) {
          this.changeCaptcha = val.replace(/[^\w\/]/g, "");
        },
        //提交
        goChange() {
          this.verifInput('nums', 'submit');
          this.verifInput('captcha', 'submit');
          let vm = this;
          if (this.verifyNums === 5 && this.verifyCaptcha === 5) {
            this.loading = true;
            this.stopClick = true;
            http({
              url: '/Ajax_Yibi/userInturn',
              method: 'POST',
              data: {
                uid: this.alertData.uid,
                coin: this.alertData.coin,
                opt_type: this.alertData.opt_type,
                number: this.changeNums,
                captcha: this.changeCaptcha
              },
              success({ status, msg, data }) {
                vm.loading = false;
                vm.stopClick = false;
                if (status === 1) {
                  //弹窗点击确定后 进行页面刷新
                  $('[data-btnsu="sureBtn"]').click(function() {
                    window.location.reload();
                  });
                  $('.close_cur').click(function(event) {
                    window.location.reload();
                  });
                  vm.userShow = false;
                  vm.myAlert.show(msg);
                } else if (status === '0' && data.min) {
                  vm.verifyNums = 7;
                  vm.errNum = data.min;
                } else if (status === '0' && data.max) {
                  vm.verifyNums = 6;
                  vm.errNum = data.max;
                } else if (status === '0' && data === 'captcha') {
                  vm.verifyCaptcha = 4;
                  vm.errMes = msg;
                } else {
                  vm.userShow = false;
                  vm.myAlert.show(msg);
                }
                vm.img_ver();
              },
              error() {
                vm.loading = false;
                vm.stopClick = false;
              }
            }, "no err alert");
          } else {
            this.loading = false;
            this.stopClick = false;
            return false;
          }
        },
        alertfn() {

        }
      },
      watch: {
        userShow() {
          if (this.userShow && document.getElementById('bodyContent')) {
            document.getElementById('bodyContent').classList.add('fn_hiden');
          } else if (document.getElementById('bodyContent')) {
            document.getElementById('bodyContent').classList.remove('fn_hiden');
          }
        }
      }
    }).$mount('#userAlert');
  } else {
    return {};
  }
};
