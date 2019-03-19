import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import 'nStyle/user/common.scss';
import http from 'plugins/http';
import Promise from 'Promise';
import getLanguagePack from '@/components/tradeLanguagePack';
import Vue from 'vue';

Vue.use(http);

let language = getLanguagePack() || {
  COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
  COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
  COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。"
};

export default () => {
  if (document.getElementById('bindInfo')) {
    return new Vue({
      data() {
        return {
          //彈窗顯示
          showAlert: false,
          //判斷是郵箱 還是 手機
          showType: '',
          //步数
          showStep: 1,
          //google 第三步
          threeStep: false,
          //提交菊花
          submit_now: false,
          //綁定數據 邮箱
          emailData: {
            regtype: 'email',
            email: '',
            code: ''
          },
          //綁定數據 手机
          phoneData: {
            regtype: 'phone',
            phone: '',
            code: '',
            area: ''
          },
          //google 验证
          googleData: {
            //pinless绑定 remove 解除绑定
            regtype: 'pinless',
            //google key 解绑不用传
            secret: "",
            //谷歌验证码
            code: "",
            pwd: ""
          },
          verifyType: {
            email: 0,
            code: 0,
            mo: 0,
            pwd: 0
          },
          firstData: {
            // email phone
            regtype: '',
            code: ''
          },
          area: '+86',
          //区域选择
          index: 0,
          //区域选择框
          chooseArea: false,
          //输入框聚焦
          focusInputEmail: false,
          focusInputCode: false,
          focusGoogle: false,
          //倒计时
          //语音验证码出现
          voice: false,
          //语音验证码获取倒计时
          voiceTimes: false,
          //重复点击
          overClick: false,
          //点击获取语音验证码之后
          phoneWarin: false,
          times: 60,
          //定时器
          setTimes: '',
          //验证码发送 邮箱
          setEmailData: {
            email: '',
            regtype: 'pinless'
          },
          setPhoneData: {
            action: 7,
            phone: '',
            regtype: 'phone'
          },
          //弹窗
          nowStatus: false,
          errMesAlert: '',
          //弹窗callback类型
          callType: '',
          //后台返回错误
          errPhpMes: '',
          //google key
          googleKeys: "",
          //google success alert
          successGoo: false
        };
      },
      methods: {
        //google key
        getKey(data) {
          this.googleKeys = data;
        },
        //
        init() {
          this.showStep = 1;
          this.errPhpMes = '';
          this.verifyType = {
            email: 0,
            code: 0,
            mo: 0,
            pwd: 0
          };
          this.focusInputEmail = false;
          this.focusInputCode = false;
          this.focusGoogle = false;
          //綁定數據 邮箱
          this.emailData = {
            regtype: 'email',
            email: '',
            code: ''
          };
          //綁定數據 手机
          this.phoneData = {
            regtype: 'phone',
            phone: '',
            code: '',
            area: ''
          };
          //google data
          this.googleData = {
            //pinless绑定 remove 解除绑定
            regtype: 'pinless',
            //google key 解绑不用传
            secret: "",
            //谷歌验证码
            code: "",
            pwd: ""
          };
          //验证码初始化
          this.times = '60';
          this.overClick = false;
          this.voiceTimes = false;
          this.voice = false;
          this.phoneWarin = false;
          clearInterval(this.setTimes);
          this.threeStep = false;
        },
        //調用彈窗
        alertBind(type) {
          /**
          * 绑定手机 mo
          * 绑定邮箱 email
          * 绑定google验证 google
          * 解绑google验证 del
          */
          this.init();
          this.showType = type;
          if (type === 'del') {
            this.googleData.regtype = 'remove';
            this.threeStep = true;
            this.showStep = 3;
          }
          this.showAlert = true;
        },
        closeAlert() {
          this.init();
          this.showAlert = false;
          this.showStep = 1;
          this.successGoo = false;
          this.mesInit();
        },
        //google key copy
        copyClikc() {
          if (!document.execCommand) {
            this.nowStatus = true;
            this.errMesAlert = language['COPY_NOT_SUPPORT'];//複製不成功，不支持
          }
          let transfer = document.getElementById('J_CopyTransfer');
          let val = document.getElementById('bind_copy').innerHTML;
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
        areaClick(data) {
          this.index = data.split(',')[0];
          this.area = data.split(',')[1];
          this.chooseArea = false;
        },
        //點擊其他位置收起區域選擇
        closeArea(e) {
          let lists = document.getElementsByClassName('fn_bind_area')[0];
          let listLabel = document.getElementsByClassName('fn_bind_label')[0];
          if (lists && listLabel) {
            if (!lists.contains(e.target) && !listLabel.contains(e.target)) {
              this.chooseArea = false;
            }
          }
        },
        //输入限定
        inputVer(type) {
          let nums = /^[0-9]*$/;
          let sign = /[^\a-\z\A-\Z0-9\~\!@#%\$\^&\*()\+\-\_\"\'\=\|:\;\,\.<>\/?*]/g;
          switch (type) {
            case 'code':
              if (this.emailData.code && !nums.test(this.emailData.code)) {
                this.emailData.code = this.emailData.code.replace(/\D/g, '');
              } else if (this.googleData.code && !nums.test(this.googleData.code)) {
                this.googleData.code = this.googleData.code.replace(/\D/g, '');
              }
              break;
            case 'mo':
              if (this.phoneData.phone && !nums.test(this.phoneData.phone)) {
                this.phoneData.phone = this.phoneData.phone.replace(/\D/g, '');
              }
              break;
            case 'pwd':
              if (this.googleData.pwd) {
                this.googleData.pwd = this.googleData.pwd.replace(sign, '');
              }
              break;
            default:

          }
        },
        //判定
        verify(type, method) {
          let email = /^[A-Za-z0-9\u4e00-\u9fa5-_\.]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;
          switch (type) {
            case 'email':
              if (this.emailData.email && method != 'focus') {
                let val = this.emailData.email;
                if (method === 'blur') {
                  this.focusInputEmail = false;
                }
                if (email.test(val)) {
                  this.verifyType.email = 0;
                  return true;
                } else {
                  this.verifyType.email = 3;
                  return false;
                }
                if (method === 'blur') {
                  this.focusInputEmail = false;
                }
              } else if (method === 'focus') {
                this.verifyType.email = 1;
                this.focusInputEmail = true;
                return false;
              } else if (method === 'blur') {
                this.verifyType.email = 0;
                this.focusInputEmail = false;
                return false;
              } else {
                this.verifyType.email = 2;
                return false;
              }
              break;
            case 'code':
              if ((this.emailData.code || this.googleData.code) && method != 'focus') {
                let val = '';
                if (this.threeStep) {
                  val = this.googleData.code;
                } else {
                  val = this.emailData.code;
                }
                if (method === 'blur') {
                  this.focusInputCode = false;
                }
                if (val.length < 6) {
                  this.verifyType.code = 3;
                  return false;
                } else {
                  this.verifyType.code = 0;
                  return true;
                }

              } else if (method === 'focus') {
                this.verifyType.code = 1;
                this.focusInputCode = true;
                return false;
              } else if (method === 'blur') {
                this.verifyType.code = 0;
                this.focusInputCode = false;
                return false;
              } else {
                this.verifyType.code = 2;
                return false;
              }
              break;
            case 'mo':
              if (this.phoneData.phone && method != 'focus') {
                let areas = this.area.split('+')[1];
                if (method === 'blur') {
                  this.focusInputEmail = false;
                }
                if (areas === '86' && this.phoneData.phone.length < 11) {
                  this.verifyType.mo = 3;
                  return false;
                } else {
                  this.verifyType.mo = 0;
                  return true;
                }
              } else if (method === 'focus') {
                this.verifyType.mo = 1;
                this.focusInputEmail = true;
                return false;
              } else if (method === 'blur') {
                this.verifyType.mo = 0;
                this.focusInputEmail = false;
                return false;
              } else {
                this.verifyType.mo = 2;
                return false;
              }
              break;
            case 'pwd':
              if (this.googleData.pwd && method != 'focus') {
                let val = this.googleData.pwd;
                if (method === 'blur') {
                  this.focusGoogle = false;
                }
                if (val.length < 6) {
                  this.verifyType.pwd = 3;
                  return false;
                } else {
                  this.verifyType.pwd = 0;
                  return true;
                }
              } else if (method === 'focus') {
                this.verifyType.pwd = 1;
                this.focusGoogle = true;
                return false;
              } else if (method === 'blur') {
                this.verifyType.pwd = 0;
                this.focusGoogle = false;
                return false;
              } else {
                this.verifyType.pwd = 2;
                return false;
              }
            default:
          }
        },
        mesInit() {
          clearInterval(this.setTimes);
          this.times = 60;
          if (this.voiceTimes) this.voiceTimes = !this.voiceTimes;
          if (this.phoneWarin) this.phoneWarin = !this.phoneWarin;
          this.overClick = false;

          switch (this.showType) {
            case 'mo':
              if (this.showStep == 1) {
                this.voice = false;
              } else {
                this.voice = true;
              }
              break;
            case 'email':
              if (this.showStep == 1) {
                this.voice = true;
              } else {
                this.voice = false;
              }
              break;
            default:

          }
        },
        //发送短信验证码
        getVerCode(type) {
          if (this.overClick) {
            return false;
          } else {
            let url = '';
            switch (this.showType) {
              case 'email':
                //第一步获取手机号的短袖验证码
                //第二步获取邮箱验证码
                if (this.showStep == 1) {
                  url = '/Ajax_User/smsnocap';
                  this.messageSet(true, type);
                } else {
                  if (!this.verify('email')) return false;
                  url = '/Emailverify/findpwd';
                  this.messageSet(false);
                }
                break;
              case "mo":
                //绑定邮箱号
                if (this.showStep == 1) {
                  url = '/Emailverify/findpwd';
                  this.messageSet(false);
                } else {
                  if (!this.verify('mo')) return false;
                  url = '/Ajax_User/msg';
                  this.messageSet(true, type);
                }
                break;
              default:
              break;
            }
            this.overClick = true;
            this.timesOut();
            new Promise((resolve) => {
              let postData = this.setDatas();
              resolve(postData);
            }).then((postData) => {
              this.sentMes(url, postData);
            });
          }
        },
        //短信邮箱 语音验证部分
        messageSet(mes, type) {
          if (mes) {
            //需要开启语音验证码的情况
            this.voice = false;
            if (type === 'voice') {
              this.phoneWarin = true;
              this.voiceTimes = false;
              this.setPhoneData.type = 8;
            } else {
              //点击之后开始语言验证码倒计时
              this.voiceTimes = true;
              this.phoneWarin = false;
            }
            //不开启语音验证码的情况
          } else if (this.voiceTimes || this.phoneWarin) {
            this.voice = false;
            this.voiceTimes = false;
            this.phoneWarin = false;
          }
        },
        //短信验证码提交数据
        setDatas() {
          let postData = '';
          if (this.showType === 'mo') {
            this.setPhoneData.phone = this.phoneData.phone;
            this.setPhoneData.area = this.area;
            if (this.showStep == 1) {
              //获取邮箱验证码 第一步
              postData = { regtype: 'trust' };
            } else {
              //获取手机验证码 第二步
              postData = this.setPhoneData;
            }
          } else {
            this.setEmailData.email = this.emailData.email;
            if (this.showStep == 1) {
              //获取手机验证码 第一步
              //短信action 7 语言 8
              if (this.setPhoneData.type == 8) postData = { action: 8 };
              else postData = { action: this.setPhoneData.action };

            } else {
              //获取邮箱验证码 第二步
              postData = this.setEmailData;
            }

          }
          return postData;
        },
        //验证码
        sentMes(url, postData) {
          this.$http.post(url, postData)
          .then(({ data, msg, status }) => {
            if (status != 1) {
              this.mesInit();
              if (data === 'email') {
                if (this.showStep == 1) {
                  this.verifyType.code = 4;
                } else {
                  this.verifyType.email = 4;
                }
                this.errPhpMes = msg;
              } else if (data === 'code') {
                this.verifyType.code = 4;
                this.errPhpMes = msg;
              } else {
                this.nowStatus = true;
                this.errMesAlert = msg;
              }
            }
          });
        },
        //倒计时
        timesOut() {
          this.setTimes = setInterval(() => {
            if (this.times > 0) {
              this.times --;
            } else {
              this.mesInit();
            }
          }, 1000);
        },
        //提交
        submit() {
          this.submit_now = true;
          switch (this.showStep) {
            case 1: {
              let url = '/Ajax_User/verifyUser';
              if (this.showType === 'google') {
                this.submit_now = false;
                this.showStep = 2;
              } else {
                if (this.showType === 'mo') {
                  this.firstData.regtype = 'email';
                } else {
                  this.firstData.regtype = 'phone';
                }
                let code = this.verify('code', 'submit');
                if (code) {
                  this.firstData.code = this.emailData.code;
                  this.submitAjax(url, this.firstData);
                } else {
                  this.submit_now = false;
                }
              }
            }
              break;
            case 2: {
              let url = "/Ajax_User/moemail";
              if (this.showType === 'mo') {
                let mo = this.verify('mo', 'submit');
                let code = this.verify('code', 'submit');
                this.phoneData.area = this.area;
                this.phoneData.code = this.emailData.code;
                if (mo && code) {
                  this.submitAjax(url, this.phoneData);
                } else {
                  this.submit_now = false;
                }
              } else if (this.showType === 'google') {
                this.threeStep = true;
                this.showStep = 3;
                this.submit_now = false;
              } else {
                let email = this.verify('email', 'submit');
                let code = this.verify('code', 'submit');
                if (email && code) {
                  this.submitAjax(url, this.emailData);
                } else {
                  this.submit_now = false;
                }
              }
            }
              break;
            case 3: {
              let url = "Ajax_User/googleadd";
              if (this.showType === 'google' || this.showType === 'del') {
                let code = this.verify('code', 'submit');
                let pwd = this.verify('pwd', 'submit');
                if (code && pwd) {
                  this.googleData.secret = this.googleKeys;
                  this.submitAjax(url, this.googleData);
                } else {
                  this.submit_now = false;
                }
              }
            }
              break;
            default:
          }
        },
        submitAjax(url, data) {
          console.log(data);
          this.$http.post(url, data)
          .then(({ data, msg, status }) => {
            this.submit_now = false;
            if (status != 1) {
              this.errPhpMes = msg;
              if (data === 'email_code' || data === 'code') {
                this.verifyType.code = 4;
              } else if (data === 'email') {
                this.verifyType.email = 4;
                this.errPhpMes = msg;
              } else if (data === 'smsCaptch') {
                this.verifyType.code = 4;
                this.errPhpMes = msg;
              } else if (data === 'mo') {
                this.verifyType.mo = 4;
                this.errPhpMes = msg;
              } else if (data === 'code') {
                this.verifyType.code = 4;
                this.errPhpMes = msg;
              } else {
                this.nowStatus = true;
                this.errMesAlert = msg;
              }
            } else if (this.showStep == 2) {
              this.nowStatus = true;
              this.errMesAlert = msg;
              this.callType = 'email';
            } else if (this.showType === 'google' || this.showType === 'del') {
              this.showAlert = false;
              this.successGoo = true;
            } else {
              this.init();
              this.showStep = 2;
            }
          });
        },
        //弹窗callback
        callfn() {
          if (this.callType === 'email') {
            window.location.reload();
          }
        },
        //关闭弹窗 刷新页面
        reloadW() {
          this.successGoo = false;
          this.init();
          window.location.reload();
        }
      }

    }).$mount('#bindInfo');
  } else {
    return {};
  }
};
