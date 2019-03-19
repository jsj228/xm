import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import 'nStyle/user/common.scss';
import 'nStyle/user_tables.scss';
import 'nStyle/user/candy/user_candy.scss';

import Vue from 'vue';
import http from 'plugins/http';
import nav from 'nComponents/nav';
import Alert from 'nComponents/Alert';
//table
import scrollTable from 'nComponents/scrollTable';
import getQuery from 'tools/getQuery.js';

Vue.use(http);
const candy = new Vue({
  data() {
    return {
      //tab的状态
      // btc分叉 1 eth分叉 2 活动赠送币 3
      tab: 1,
      //btc分叉数据
      btcList: [],
      //eth分叉数据
      ethList: [],
      //展示用数据
      showList: [],
      //logding
      tabLoging: false,
      //没数据
      no_data: false,
      nowStatus: false,
      errMesAlert: '',
      //弹窗类型
      alertype: '',
      //币种详情
      coinDetail: false,
      //币种详情数据
      coinList: {},
      moverClick: false,
      tabChnage: true
    };
  },
  mounted() {
    nav();
    if (location.href.indexOf('?tab') > -1) {
      this.coinClick(parseInt(getQuery('tab')));
    } else {
      this.getCoinList();
    }
  },
  methods: {
    changetab(type) {
      if (type === 'start') {
        this.moverClick = true;
      } else if (type === 'over') {
        this.moverClick = false;
      } else if (type === 'end' && this.moverClick) {
        this.tabChnage = !this.tabChnage;
      }
    },
    coinClick(nums) {
      this.tab = nums;
      this.no_data = false;
      this.getCoinList();
    },
    //币种详情
    coin_detail(type, data) {
      //移动端
      if (type === 'start') {
        this.moverClick = true;
        this.coinList = data;
      } else if (type === 'over') {
        this.moverClick = false;
      } else if (type === 'end' && this.moverClick === true) {
        this.coinDetail = true;
      }
    },
    //币种列表获取
    getCoinList() {
      this.tabLoging = true;
      let url = '';
      // 分叉币
      if (this.tab === 1 || this.tab === 2) {
        url = '/ajax_user/branchlist';
      }
      // 活动赠送币
      else {
        url = '/ajax_user/giftList';
      }
      this.$http.post(url, {})
      .then(({ data, status }) => {
        if (status == 1 && this.tab != 3) {
          let { BTC, ETH } = data;
          this.btcList = BTC;
          this.ethList = ETH;
          if (this.tab === 1) {
            this.showList = [...this.btcList];
          } else {
            this.showList = [...this.ethList];
          }
        } else if (status == 1 && this.tab === 3) {
          this.showList = [...data];
        }
      })
      .then(() => {
        this.tabLoging = false;
        if (this.showList.length > 0) {
          this.no_data = false;
        } else {
          this.no_data = true;
        }
        this.$nextTick(() => {
          if (this.$refs.scrollTab) {
            this.$refs.scrollTab.getTbodyTds();
          } else {
            this.$refs.scrollTabs.getTbodyTds();
          }
        });
      });
    },
    gifts(data) {
      let url = '';
      let datas = {};
      if (this.tab != 3) {
        let { branch, parent } = data;
        url = '/ajax_user/getBranch';
        datas = {
          branch, parent
        };
      } else if (this.tab === 3) {
        let { coin, type } = data;
        datas = {
          coin
        };
        if (type) {
          url = `/ajax_user/coin${type}`;
        } else {
          url = '/ajax_user/coinGift';
        }
      }
      this.$http.post(url, datas)
      .then(({ data, status, msg }) => {
        this.nowStatus = true;
        this.errMesAlert = msg;
        this.alertype = 'gift';
      });
    },
    //关闭币种详情
    close() {
      this.getCoinList();
      this.coinDetail = false;
    },
    callfn() {
      if (this.alertype === 'gifr') {
        this.getCoinList();
      }
    }
  },
  components: {
    scrollTable
  }
}).$mount('#candy');
export default candy;
