import 'nStyle/index.activity.scss';
import Vue from 'vue';
import http from 'plugins/http';
import Alert from 'nComponents/Alert';
import register from 'nComponents/register';
import nav from 'nComponents/nav';

window.onload = function() {
  nav();
  // 注册插件
  Vue.use(http);

  new Vue({
    data: {
      my_order: '',
      my_total: 0,
      page: 1,
      pagetotal: 1,
      list: [],
      hasMore: false,
      noMore: false,
      nowStatus: false,
      maxPage: 5,
      errMesAlert: '',
      showAlert: false
    },
    mounted() {
      this.getData();
    },
    methods: {
      getData() {
        if (this.page > this.maxPage) {
          this.noMore = true;
          this.hasMore = false;
          return;
        }
        this.$http.post('/Ajax_yibi/read', {
          page: this.page,
          size: 10
        })
        .then(({ status, data, msg }) => {
          if (parseInt(status) === 1) {
            this.my_total = data.my.my_total;
            this.my_order = data.my.my_order || 101;
            this.page = data.currentpage + 1;
            this.list = [...this.list, ...data.list];
            this.pagetotal = data.pagetotal;
            if (this.page > data.pagetotal) {
              this.noMore = true;
              this.hasMore = false;
            } else {
              this.noMore = false;
              this.hasMore = true;
            }
          } else {
            if (data.need_login == 1) {
              this.errMesAlert = msg;
              this.showAlert = true;
            }
          }
        })
        .catch(err => {
          console.log(err);
        });
      },
      alertCallBack() {
        register.loginAlert();
      }
    }
  }).$mount("#activity");
}
