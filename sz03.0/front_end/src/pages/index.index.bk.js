// import 'progressive-image/dist/index.css';
import '@/styles/common/index.scss';
import Swiper from 'swiper';
import nav from '@/nComponents/nav';
import "nStyle/theme.scss";
import 'nStyle/index.index.scss';
// import '@/styles/main.scss';

import DialogBox from '@/tools/dialogBox/dialogBox';
import http from '@/tools/http';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import talking from '@/tools/talking/talking';
import Alert from "@/tools/alert/alert";
import Vue from 'vue';
import getLanguagePacks from '@/components/tradeLanguagePack';
import register from 'nComponents/register';
import Progressive from '@/utils/progressive';
import cookie from '@/tools/cookie';
import sock from 'tools/sock';
import is from 'tools/is';
import activity from 'nComponents/activity';

// process.env.NODE_ENV
$(document).ready(function() {

  const lang = getLanguagePacks() || {
    "login_now": "請先登錄",
    "GET_DATA_FAIL": "獲取數據失敗",
    "CANDY_GOT": "恭喜妳已經成功領取"
    };
  // //打開聊天室接受數據功能
  // talking();
  chkhttpLang("#baseLang", http);
  let myAlert = new Alert();
  nav();

  const lazyload = new Progressive({
    el: '#bannerContainer',
    lazyClass: 'lazy',
    removePreview: true,
    scale: true
  });
  //
  let banner = new Swiper('#bannerContainer', {
    loop: true,
    simulateTouch: false,
    parallax: true,
    // 点击操作swiper，autoplay不关闭-
    autoplayDisableOnInteraction : false,
    autoplay: 5000, // 3000
    pagination: '.swiper-pagination',
    paginationClickable: true,
    // Navigation arrows
    nextButton: '.swiper-button-next',
    prevButton: '.swiper-button-prev'
  });

  $('#bannerContainer div[data-stop="swiper"]').on('mouseover', function(){
    banner.stopAutoplay();
  });
  $('#bannerContainer div[data-stop="swiper"]').on('mouseout', function(){
    banner.startAutoplay();
  });

  lazyload.fire();

  // 弹框
  const myBox = new DialogBox();
  myBox.outsideCss({ width: '80%', height: '80%' });
  myBox.css({ width: "100%", height: '100%', padding: "0 33px", overflow: 'auto' });
  // 新闻 弹框
  function showNotice(category, title, content) {
    const tpls = `<div class="notice-content"><p class="dialog-title">${title}</p>
      <div class="notice-detail">${content}</div>
    </div>`;
    myBox.show(tpls);
  }

  $("#newsTag a").click(function() {
    return;
    const selId = $(this).attr('lang');
    http({
      url: '/index/newsDetail',
      method: "POST",
      data: {
        id: selId
      },
      success(req) {
        let { category, title, content } = req.data;
        showNotice(category, title, content);
      },
      error(err) {
        if (err) {
          alert(err);
        }
      }
    })
  });

  //活動點擊
  $('#bannerContainer .acitvity_btn').click(function(event) {
    console.log(12345);
    let $this = $(this);
    if ($this.hasClass('.read')) return;
    let coinName = $this.data('coin');
    let type = $this.data('type');

    let url = '/ajax_user/coinGift';
    if (type) {
      url = `/Ajax_User/coin${type}`;
    }
    http({
      url,
      type: 'POST',
      dataType: 'json',
      data: {
        coin: coinName
      },
      success(data) {
        // 领取成功
        if (data.status == 1) {

          if (coinName == 'dob') {
            activity.nowStatus = true;
            activity.errMesAlert = data.msg;
          }  else {

            if (coinName == 'nano') {
              coinName = 'Nano';
            }


            let htmls = `
            <p class="candy_tips_get">${data.msg}</p>
            <p class="candy_green">${data.data + ' ' + coinName}</p>
            `;
            //data.msg
            myAlert.show(htmls);
            //
            $('[data-btnsu="sureBtn"]').click(function(event) {
              window.location.href = '/user/candy?index=3';
            });
          }
        }
        // 已经领取过
        else {
          if (data.data.need_real_auth == 1) {
            $('[data-btnsu="sureBtn"]').click(function(event) {
              window.location.href = '/user/realinfo';
            });
          }

          if (coinName == 'dob') {
            activity.nowStatus = true;
            activity.errMesAlert = data.msg;
          } else {
            myAlert.show(data.msg);
          }
        }
      },
      error(err) {
        if (err)
          myAlert.show(lang['GET_DATA_FAIL']);
      }
    }, true);
  });

  $('#bannerContainer .no_login_btn').click(function(event) {
    myAlert.show(lang['login_now']);
    $('[data-btnsu="sureBtn"]').click(function(event) {
      // $('.login-btn').click();
      register.loginAlert();
    });
  });

  // newsTab
  const btcData = new Swiper('#btcData', {
    // Optional parameters
    loop: true,
    slidesPerView: 5,
    spaceBetween: 0,
    autoplay: 1,
    autoplayDisableOnInteraction: false,
    speed: 6000,
    paginationClickable: true
  });

  const mccData = new Swiper('#mccData', {
    // Optional parameters
    loop: true,
    slidesPerView: 5,
    spaceBetween: 0,
    autoplay: 1,
    autoplayDisableOnInteraction: false,
    speed: 6000,
    paginationClickable: true
  });

  //
  const ethData = new Swiper('#ethData', {
    // Optional parameters
    loop: true,
    slidesPerView: 5,
    spaceBetween: 0,
    autoplay: 1,
    autoplayDisableOnInteraction: false,
    speed: 6000,
    paginationClickable: true
  });
  //
  const dobData = new Swiper('#dobData', {
    // Optional parameters
    loop: true,
    slidesPerView: 5,
    spaceBetween: 0,
    autoplay: 1,
    autoplayDisableOnInteraction: false,
    speed: 6000,
    paginationClickable: true
  });

  $('[data-register="registerBtn"]').click(function () {
    register.registerAlert();
  });

  function dealQuote(data) {
    Object.keys(data).forEach((areaName) => {
      let area = data[areaName];
      Object.keys(area).forEach((coinName) => {
        // amount 成交量  price 成交价 ratio 涨跌幅
        const amount = area[coinName].amount;
        const $coinDom = $(`#${areaName}Data li[data-coincode="${coinName}"]`);
        // 成交价
        let price = parseFloat(area[coinName].price);
        // 获取小数 位数
        const strNum = price + '';
        let numLength;
        let beautyNum;
        if (strNum.indexOf('e') > -1) {
          numLength = strNum.slice(-1);
          beautyNum = price.toFixed(numLength);
          $coinDom.find("span[data-price]").html(beautyNum);
        } else {
          $coinDom.find("span[data-price]").html(strNum);
        }
        // 成交量
        $coinDom.find("span[data-amount]").html(area[coinName].amount);
        // 成交額
        $coinDom.find("span[data-money]").html(area[coinName].money);
        // 涨跌幅
        $coinDom.find("span[data-radio]").html(area[coinName].ratio + '%');
        // 涨 不变 class ， 跌要 添加 class down-data
        if (parseFloat(area[coinName].ratio) < 0) {
          $coinDom.find(".price-tab").addClass("down-data").find(".up-array").attr("class", "down-array");
        } else {
          $coinDom.find(".price-tab").removeClass("down-data").find(".down-array").attr("class", "up-array");
        }
      })
    });
  }
  // 币数据
  function upDataPrice() {
    http({
      url: '/ajax_market/getAllQuote',
      method: "GET",
      success(result) {
        if (result && parseInt(result.status) === 1) {
          //
          const { data } = result;
          dealQuote(data);
        }
      },
      error(err) {
        if (err)
        console.log(err);
      }
    });
  }
  // 首次获取
  upDataPrice();
  let sockTimes = 0;
  // function createSock() {
  //   let token = cookie.getItem('WSTK');
  //   //
  //   let mySock = sock({
  //     url: document.getElementById('wsurl').value,
  //     open() {
  //       // 比列表
  //       mySock.send(`{"token": "${token}", "channel": "public"}`);
  //       // 定时发送空消息，防止掉线
  //       setInterval(() => {
  //         if (mySock) {
  //           mySock.send('');
  //         }
  //       }, 20000);
  //     },
  //     message(response) {
  //       console.log(response);
  //       let { data } = response;
  //       if (data && is(data, 'String') && data.indexOf('{') > -1) {
  //         let res = JSON.parse(data);
  //         if (res.channel === 'public') {
  //           dealQuote(res.data);
  //         }
  //       }
  //     },
  //     close() {
  //       sockTimes++;
  //       setTimeout(() => {
  //         if (sockTimes < 3) {
  //           createSock();
  //         } else {
  //           setInterval(function(){
  //             upDataPrice();
  //           }, 2000);
  //         }
  //       }, 3000);
  //     }
  //   });
  //
  //   return mySock;
  // }
  //
  // createSock();
  // let
  // setInterval(function(){
  //   upDataPrice();
  // }, 2000);
  upDataPrice();

  let allUpDownData = 0;
  //
  const $allUpDownDom = $("#allUpDown");
  // 判断是否首页
  if ($allUpDownDom.is(":visible")) {
    function getAllUpDown() {
      http({
        url: '/ajax_market/getCoinIndex',
        method: 'GET',
        success(res) {
          if (res && parseInt(res.status) === 1) {
            const $allUpDownDom = $("#allUpDown");
            if (res.data.curIndex) {
              // down
              if (res.data.curIndex < allUpDownData) {
                $allUpDownDom.addClass('down-pc');
              } else {
                $allUpDownDom.removeClass('down-pc');
              }
              allUpDownData = res.data.curIndex;
              $allUpDownDom.html(res.data.curIndex);
            }
          }
        }
      });
    }
    // 首次加载
    getAllUpDown();
    // 涨跌幅
    setInterval(function(){
      getAllUpDown();
    }, 5000);
  }

  // 切换 BTC交易区
  $("#changeAreaBtn ul>li").click(function() {
    let li = $(this);
    let areaName = li.data('aname');
    $("#changeAreaBtn .at_area").removeClass("at_area");
    li.addClass("at_area");
    // 显示对应交易区数据
    $("#vueDom div[data-tradearea='coin']").addClass('no-act-area');
    $(`#${areaName}Data`).parent('div').removeClass('no-act-area');
  });

});
