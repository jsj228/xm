import 'nStyle/activity.scss';
import http from 'plugins/http';
import Alert from 'nComponents/Alert';
import register from 'nComponents/register';
import Vue from 'vue';

Vue.use(http);

const packed = new Vue({
  data() {
    return {
      showMask: false,
      showContent: false,
      showIcon: false,
      showSuccess: false,
      fristShow: false,
      //小时
      hours: '',
      //分钟
      minutes: '',
      //秒
      second: '',
      nowStatus: false,
      errMesAlert: "",
      msg: "",
      callType: '',
      jumpUrl: ''
    };
  },
  mounted() {
    if (document.getElementById('alertStauts')) {
      let sta = document.getElementById('alertStauts').innerHTML;
      if (sta == 1) {
        this.setTimes();
        this.showMask = true;
        this.showContent = true;
        this.showIcon = false;

        setTimeout(() => {
          this.showMask = false;
          this.showContent = false;
          this.showIcon = true;
        }, 10000);
      }
    }

  },
  methods: {
    //时间转换
    changeTimes(dataType, dataValue) {
      let timeType = '';
      switch (dataType) {
        case 'hour':
          timeType = 1000 * 3600;

          break;
        case 'minute':
          timeType = 1000 * 60;
          break;
        case 'second':
          timeType = 1000;
          break;
        default:
          break;
      }
      return parseInt(parseInt(dataValue) / parseInt(timeType));
    },
    //倒计时
    setTimes() {
      //当前时间戳
      let timestamp = (new Date()).valueOf();
      //今天10点的时间戳
      let times = (new Date().setHours(10, 0, 0, 0)).valueOf();
      //下午15点的时间戳
      let toTimes = (new Date().setHours(15, 0, 0, 0)).valueOf();
      //距離第二天10點
      let seTimes = new Date(new Date().setDate(new Date().getDate() + 1)).setHours(10, 0, 0, 0).valueOf();
      //时间戳差值
      let timePlus = '';
      // 10点前
      if (times - timestamp > 0) {
        timePlus = times - timestamp;
      }
      //已经过了今天10点
      else if (toTimes - timestamp > 0) {
        timePlus = toTimes - timestamp;
      } else {
        timePlus = seTimes - timestamp;
      }
      this.hours = this.changeTimes('hour', timePlus);
      this.minutes = this.changeTimes('minute', timePlus) - this.hours * 60;
      let sec = this.changeTimes('second', timePlus) - this.changeTimes('minute', timePlus) * 60;
      if (sec < 10) {
        this.second = '0' + sec;
      } else {
        this.second = sec;
      }

      setTimeout(() => {
        this.setTimes();
      }, 1000);
    },
    shows() {
      if (!this.showSuccess) {
        this.fristShow = false;
      }
    },
    hides() {
      this.fristShow = true;
    },
    hideShow(type) {
      this.showMask = false;
      this.showContent = false;
      this.showIcon = true;
      this.showSuccess = false;
      if (type === 'close' && this.jumpUrl) {
        window.location.href = this.jumpUrl;
      }
    },
    showPacked() {
      if (!this.showSuccess) {
        this.showMask = true;
        this.showContent = true;
        this.showIcon = false;
        this.showSuccess = false;
      } else {
        this.showMask = true;
        this.showContent = false;
        this.showIcon = false;
        this.showSuccess = true;
      }
    },
    getPacked() {
      this.$http.post('/ajax_user/coindob', { coin: 'dob' })
      .then(({ data, status, msg }) => {
        if (status == 1) {
          this.showContent = false;
          this.showSuccess = true;
          this.msg = msg;
          if (data.type === 'dob') {
            this.jumpUrl = "/user";
          } else {
            this.jumpUrl = "/trade";
          }
        } else {
          this.nowStatus = true;
          this.showIcon = true;
          this.showContent = false;
          this.errMesAlert = msg;
          if (data.need_login == 1) {
            this.callType = 'gift';
          } else if (data.need_real_auth) {
            this.callType = 'identity';
          }
        }
      })
      .catch((err) => {
        // console.log(err);
      });
    },
    callfn() {
      if (this.callType === 'gift') {
        this.callType = '';
        register.loginAlert();
      } else if (this.callType === 'identity') {
        window.location.href = '/user/realinfo';
      }
    }
  }

}).$mount('#packed');
export default packed;
