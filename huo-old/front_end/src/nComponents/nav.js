import 'nStyle/nav/nav.scss';
import register from 'nComponents/register';
import cookie from '@/tools/cookie';
import https from 'plugins/http';
import Vue from 'vue';

Vue.use(https);

export default () => {
  if (document.getElementById("navApp")) {
    return new Vue({
      data: {
        userMainu: false,
        lang: false,
        showMenu: false,
        userCenter: false,
        changeLang: false,
        appDown: false,
        lan: "cn"
      },
      mounted() {
        if (cookie.getItem('LANG')) {
          this.lan = cookie.getItem('LANG');
        }
        this.bodyClick();
      },
      methods: {
        bodyClick() {
          let t = this;
          document.getElementById('body_all').addEventListener('click', function(e) {
            t.init();
          }, false);
        },
        login() {
          register.loginAlert();
        },
        register() {
          register.registerAlert();
        },
        changeLangs(type) {
          // log
          this.$http.post('/ajax_user/setlang', { lang: type })
            .then(() => {
              let hostName = location.hostname;
              cookie.removeItem('LANG', type, null, '/', hostName);

              if (hostName && hostName.indexOf('dobitrade.com') > -1) {
                hostName = '.dobitrade.com';
              }
              cookie.setItem('LANG', type, null, '/', hostName);
              window.location.reload();
            });
        },
        changeTab(set) {
          this[set] = !this[set];
          if (set === 'lang') {
            this.userMainu = false;
            this.appDown = false;
          } else if (set === 'userMainu') {
            this.appDown = false;
            this.lang = false;
          } else {
            this.userMainu = false;
            this.lang = false;
          }
        },
        init() {
          this.userMainu = false;
          this.lang = false;
          this.appDown = false;
        }
      }
    }).$mount("#navApp");
  } else {
    return {};
  }
};
