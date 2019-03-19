import 'nStyle/index.index.scss';

import Vue from 'vue';
import http from 'plugins/http';
import Alert from 'nComponents/Alert';
import _ from 'lodash';
import { toFixedNum } from 'filters/filters';
import nav from '@/nComponents/nav';
import Progressive from '@/utils/progressive';
// import { swiper, swiperSlide } from 'vue-awesome-swiper';
import "nStyle/swiper.reset.scss";
import Swiper from 'swiper/dist/js/swiper.js';
import banner from '@/libs/bannerAnimation';
import register from 'nComponents/register';

Vue.use(http);
nav();
const lazyload = new Progressive({
  el: '#bannerContainer',
  lazyClass: 'lazy',
  removePreview: true,
  scale: true
});
// banner
function createSwiper() {
	return new Swiper('#bannerContainer', {
    loop:true,
    autoplay:1000,
		grabCursor : true, //为true时，鼠标覆盖Swiper时指针会变成手掌形状
		setWrapperSize :true, //在对flexbox布局的支持不是很好的浏览器中可能需要用到
		// 按钮
		navigation: {
			nextEl: '.swiper-button-next',
			prevEl: '.swiper-button-prev',
		},
		// // 分页
		pagination: {
			el: '.swiper-pagination',
			clickable: true,
			bulletClass: 'swiper_normal',
			bulletActiveClass: 'swiper_active'
    },
		slidesPerView:3,
		slidesPerGroup:3,
		spaceBetween : 30,
	});
}

if (sessionStorage.getItem('rssucc')) {
  register.loginAlert();
}

const index = new Vue({
  data() {
    return {
      getHeight: '',
      //无数据
      noList: false,
      //搜索切换
      search: false,
      //币种列表
      coinList: {},
      coinListNum: [],
      indexs: 1,
      //排序
      orderType: 'money',
      oldType: 'money',
      //排序方式 false 从高到低 true 从低到高
      orderFn: true,
      first: true,
      //模糊搜索的内容
      searchInpt: '',
      nowType: '',
      //提示彈窗
      errMesAlert: "",
      //彈窗顯示控制
      nowStatus: false,
      //回调
      types: '',
      //是否登录
      login: '',
      //搜索中
      searching: false,
      //第一次加载时自选的内容
      firstChosse: '',
      //点击后自选的内容
      chooseArray: '',
      //列表loading
      loading: false,
      //当前选择
      selectCoin: [],
      //保存币种列表
      newList: [],
      //保存币种顺序
      coinNum: [],
      //二維碼部分
      showEr: {
        tele: false,
        whatapp: false,
        qq: false,
        wechat: false
      },
      // active msg 活动领取提示
      activeMsg: '',
      actType: 'mcc',
      actGetting: false,
      //公告弹框
      winHtml:{
        header:'',
        body:'',
        footer:{},
      }
    };
  },
  mounted() {
    banner('particles');
    if (this.$refs.login) {
      this.login = this.$refs.login.innerText;
    }
    //獲得整體幣種列表
    this.getList(1);
    if (this.indexs == 1) {
      this.loading = true;
    }
    createSwiper();
    lazyload.fire();
    // if(!sessionStorage.win){
      // this.winAjax();
    // }
  },
  methods: {
    getList(num) {
      let count = 0;
      this.$http.get('/ajax_market/getAllQuoteV2')
      .then(({ data, msg, status }) => {
        _.forEach(data, (mark, key, index) => {

          //存储币种排序
          this.coinNum[count] = key;
          count ++;
          //
          _.forEach(mark, elem => {
            elem.selected = '';
          });
        });

        this.coinList = data;
        if (num == 1) {
          //獲得自選部分數據
          //再第一次的时候调用 不跟着多次循环
          if(!sessionStorage.star){
            this.tabChange(2,"cnyx");
            console.log('没有数据调用cnyx');
          }else{this.getmySelf(num);}
        }
        // this.$forceUpdate();
      });

      setTimeout(() => {
        this.getList();
      }, 5000);
    },
    //表格tab切換
    tabChange(index, type){
      this.indexs = index;
      this.coinListNum = [];
      if (this.indexs != 1) {
        this.coinListNum = this.coinList[`${type}`];
        _.forEach(this.coinListNum, (val) => {
          if (this.chooseArray.includes(val.coin)) {
            val.selected = 1;
          } else {
            val.selected = 0;
          }
        });
      } else {
        //自選部分
        this.getmySelf();
        //清空列表数据
        if (this.coinListNum) {
          this.loading = false;
        } else {
          this.loading = true;
        }
      }
      this.newList = this.coinListNum;
      this.nowType = type;
      this.searchInpt = '';
      this.order(this.orderType, 'tabs');
    },
    //获取自选
    getmySelf(num) {
      this.searching = false;
      this.chooseArray = [];
      if (this.login == 1) { //已经登陆
        //如果是登录状态 session 有值 则把 session 清空
        if (sessionStorage.star) {
          sessionStorage.removeItem('star');
        }else{
          // this.tabChange(2,"cnyx");
          // console.log('没有数据调用cnyx');
        }
        this.$http.get('/ajax_user/selected')
        .then(({ data, status, msg }) => {
          if (status == 1) {
            if (data) {
              this.chooseArray = _.split(data, ',');
            } else {
              this.loading = false;
            }
          }
        })
        .then(() => {
          if (this.indexs == 1) {
            this.forEachData();
          }
          if (num == 1) {
            let len = _.findIndex(this.coinNum, val => (val == 'dob'));
            this.tabChange((len + 2), 'dob');
          }
        });
      } else {//没有登陆
        let star = sessionStorage.star;
        if (star) {//sessionStorage中有数据
          this.chooseArray = _.split(sessionStorage.star, ',');
          //避免数组内部有空值
          this.chooseArray = _.remove(this.chooseArray, val => (val != ''));
        } else {//sessionStorage中没有数据
          this.loading = false;
          if (this.indexs == 1) {
            this.coinListNum = [];
          }
          this.chooseArray = [];
          // this.tabChange(2,"cnyx");
          // console.log('没有数据调用cnyx')
        }
        if (this.indexs == 1) {
          this.forEachData();
        }
        if (num == 1) {
          let len = _.findIndex(this.coinNum, val => (val == 'dob'));
          this.tabChange((len + 2), 'dob');
        }
      }
    },
    //点击自选
    clickMySelf(coin, index) {
      this.searching = false;
      //改变列表选择
      this.forEachCoin(index);
      if (this.login == 1) {
        //登錄后 保存
        let type = 'add';
        this.selectCoin = [coin];
        if (this.chooseArray.includes(coin)) {
          this.selectCoin = [];
          type = 'del';
        }
        this.$http.post('/ajax_user/setSelected', { coins: coin, type: type })
        .then(({ data, status, msg }) => {
          if (status != 1) {
            this.nowStatus = false;
            this.errMesAlert = msg;
          } else {
            this.getmySelf();
          }
        });
      } else {
        //未登錄 保存在本地session
        let star = sessionStorage.star;
        if (star) {
          //判断是否有这个值
          let coinAll = '';
          if (star.includes(coin)) {
            coinAll = _.split(star, ',');
            //删除对应币
            coinAll = _.remove(coinAll, function(val) {
              return val != coin;
            });
            coinAll = _.join(coinAll, ',');
            sessionStorage.setItem('star', coinAll);
            this.getmySelf();
          } else {
            sessionStorage.setItem('star', star + ',' + coin);
            this.getmySelf();
          }
        } else {
          sessionStorage.setItem('star', coin);
          this.getmySelf();
        }
      }
    },
    //chooseArray 内容为 'xxx_dob,xxx_btc' 为选择字段
    //自选选项的时候 抽取chooseArray部分的选中的内容
    //chooseArray 对比 coinList 内部所有的数据 找出选中数据
    forEachData() {
      //循环数组 对应整体列表 取出对应选中的数据
      if (this.searchInpt) {
        this.searchData();
      } else {
        if (this.indexs == 1) {
          this.coinListNum = [];
        }
        _.forEach(this.chooseArray, (value, index) =>
        {
          if (value) {
            let names = value.split('_')[1];
            let Arrays = this.coinList[`${names}`];
            let newArr = this.coinList.new;
            //普通交易区
            let ind = _.findLastIndex(Arrays, function(val) {
              let vals = val.coin;
              return val.coin == value;
            });

            //创新区
            let indNew = _.findLastIndex(newArr, function(val) {
              let vals = val.coin;
              return val.coin == value;
            });

            if (ind > -1) {
              this.coinListNum[index] = Arrays[`${ind}`];
              this.coinListNum[index].selected = 1;
              this.newList[index] = Arrays[`${ind}`];
            }

            if (indNew > -1) {
              this.coinListNum[index] = newArr[`${indNew}`];
              this.coinListNum[index].selected = 1;
              this.newList[index] = newArr[`${indNew}`];
            }
          }
        });
      }
      //排序
      this.order(this.orderType, 'tabs');
    },
    forEachCoin(index) {
      if(this.coinListNum[index].selected == 0) {
        this.coinListNum[index].selected = 1;
      } else {
        this.coinListNum[index].selected = 0;
      }
    },
    gotoTop() {
      var sTop = document.documentElement.scrollTop?document.documentElement:document.body;
      let clearTime = setInterval(() => {
        let stops = sTop.scrollTop;
        if (stops > 0) {
          sTop.scrollTop = stops - 15;
        } else {
          clearInterval(clearTime);
        }
      }, 1);
    },
     //限制8位数
    spliceEight(val) {
      if (val) {
        val = val.slice(0, 10);
        let newArr = val.indexOf('.');
        if (newArr > 0 && _.size(val) == newArr) {
          val = val.replace('.', '');
        }
        return val;
      }
    },
    //计算人民币
    moneyCal(rmb, price, num) {
      if (!rmb) rmb = 0;
      if (!price) price = 0;
      if (!num) num = 0;
      if (toFixedNum(price, 8) == 0) {
        return 0;
      } else {
        return toFixedNum((toFixedNum(rmb, 8) / toFixedNum(price, 8)) * toFixedNum(num, 8));
      }
    },
    //搜索功能切换
    searchBtn() {
      this.search = false;
      let len = 0;
      if (_.size(this.coinListNum)) {
        this.searchData();
      }
    },
    //模糊搜索
    searchData() {
      this.searching = true;
      this.searchInpt = this.searchInpt.replace(/[^\w\/]/ig, '');
      if (this.searchInpt) {
        //搜索框有值
        this.coinListNum = _.filter(this.newList, (o) => {
          let val = o.coin.split('_')[0];
          return val.indexOf(this.searchInpt.toLocaleLowerCase()) != -1;
        });
        if (_.size(this.coinListNum) == 0) {
          this.loading = false;
        }
      } else if (this.indexs != 1) {
        //搜索框没值 但是不为自选tab
        // this.coinListNum = this.coinList[`${this.nowType}`];
        this.coinListNum = this.newList;
      } else {
        //自选tab
        this.loading = true;
        this.coinListNum = this.newList;
      }
    },
    //排序
    order(type, now) {
      if (now != 'tabs') {
        this.first = false;
      }
      this.orderType = type;
      if (this.oldType === this.orderType && now != 'tabs') {
        this.orderFn = !this.orderFn;
      } else {
        this.orderFn = this.orderFn;
      }
      if (!this.orderFn) {
        //升序
        this.coinListNum = _.orderBy(this.coinListNum, (val) => {
          if (val && type != 'coin') {
            return parseFloat(val[`${type}`]);
          } else if (val && type == 'coin') {
            return val[`${type}`];
          }
        }, 'asc');
        this.oldType = type;
      } else {
        //降序
        this.coinListNum = _.orderBy(this.coinListNum, (val) => {
          if (val && type != 'coin') {
            return parseFloat(val[`${type}`]);
          } else if (val && type == 'coin') {
            return val[`${type}`];
          }
        }, 'desc');
        this.oldType = type;
      }
      //自选部分 要再调用自选单独的排序
      if (this.indexs == 1) {
        this.selfOrder();
      }
    },
    //针对自选部分币种单独排序
    //币种为一块
    selfOrder() {
      let arrays = [];
      let newArr = [];
      _.forEach(this.coinNum, (val, index) => {
        arrays[index] = _.filter(this.coinListNum, (coins) => {
          if (coins) {
             return val == coins.coin.split('_')[1];
          }
        });
        newArr = _.concat(newArr, arrays[index]);
      });
      this.coinListNum = newArr;
    },
    getCoin(e) {
      let url = '/ajax_user/coinGift';
      //点击的为button
      if (e.target.tagName == 'BUTTON') {

        if (this.login == 1) {
          let type = this.$refs.buttons.dataset.type;
          let coinName = this.$refs.buttons.dataset.name;
          if (type) {
            url = `/ajax_user/coin${type}`;
          }
          this.$http.post(url, { coin: coinName })
          .then(({ data, status, msg }) => {
            if (status == 1) {
              this.types = 'gift';
            }
            this.nowStatus = true;
            this.errMesAlert = msg;
          });
        } else {
          register.loginAlert();
        }
      }
    },
    //去登陆
    loginNow() {
      register.loginAlert();
    },
    erIn(type) {
      this.showEr[type] = true;
    },
    erOut(type) {
      this.showEr[type] = false;
    },
    //点击回调
    callfn() {
      if (this.types === 'gift') {
        location.href = '/user/candy';
      }
      // 需要實名認證
      else if (this.types === 'realinfo') {
        location.href = '/user/realinfo';
      }
    },
    //
    getRedPacket() {
      if (this.actGetting) return;
      this.actGetting = true;
      this.$http('ajax_user/mccactivity')
        .then((res) => {
          let { status, data, msg } = res;
          if (parseInt(status) === 1) {
            this.actType = data;
            this.activeMsg = msg.replace(/((,|，).+)$/g, '');
          }
          // 活動結束
          else if (res.data) {
            if (res.data.need_login) {
              register.loginAlert();
            }
            if (res.data.need_realinfo) {
              location.href = '/user/realinfo';
            }
          } else {
            this.nowStatus = true;
            this.errMesAlert = msg;
          }
          this.actGetting = false;
        })
        .catch((e) => {
          this.actGetting = false;
          if (e) console.log(e);
        });
    },
    winAjax(){
      this.$http.get('/ajax_market/getAllQuoteV2').then((res)=>{
        console.log(res);
      })
    }
  },
  computed: {
    swiper() {
      return this.$refs.mySwiper.swiper;
    }
  }
}).$mount('#indexs');
export default index;
