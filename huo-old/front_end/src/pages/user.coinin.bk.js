import "@/styles/userCenter/coinIn.scss";
import Flatpickr from "flatpickr";
import Zh from "flatpickr/dist/l10n/zh.js";
import nav from '@/components/nav';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import http from '@/tools/http';
import Alert from '@/tools/alert/alert';
import is from '@/tools/is';
import setTableStyle from '@/components/makeTableHead';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import Vue from 'vue';
import '@/tools/page/page.scss';
import pages from '@/tools/page/page';
import Promise from 'promise';
import talking from '@/tools/talking/talking'
import cookie from '@/tools/cookie'
// process.env.NODE_ENVs
// 我的賬戶 轉幣 提幣 委托 成交 頁面js
$(document).ready(function() {
  const navNew = navVue();
  //打開聊天室接受數據功能
  talking();
  // 切換 請求處理 語言包
  chkhttpLang("#baseLang", http);
  const lang = getLanguagePack() || {
    tablePlatformIn: "平台內",
    tablePlatformOut: "平台外",
    COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
    COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
    COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。",
    GET_DATA_FAIL: "獲取數據失敗",
    START_TIME: "請選擇開始時間",
    END_TIME: "請選擇結束時間",
    START_GT_END: "開始時間不能大於等於結束時間"
    };

  // 彈框
  const myAlert = new Alert("");
  nav();
  activeNav();
  // cookie 切换语言
 let nowLang = cookie.getItem('LANG');
 if (nowLang === 'cn') {
   Flatpickr.localize(Zh.zh);
 }

  /* eslint-disable */
  // 日曆
  const startTimtOptions = {
    enableTime: true,
    // defaultDate: "today",
    // maxDate: $("#endTime").val() || "",
    dateFormat: "Y-m-d H:i:S"
  };
  const startTime = new Flatpickr("#startTime", startTimtOptions);
  const endTimtOptions = {
    enableTime: true,
    dateFormat: "Y-m-d H:i:S"
  };
  const endTime = new Flatpickr("#endTime", endTimtOptions);
  /* eslint-enable */
  // 初始化
  setTableStyle();
  //
  window.onresize = function() {
    setTableStyle();
  };
  // 分頁 vue object
  let pageVue = new Vue({
    data: {
      all: '',
      currentpage: ''
    },
    mounted() {
    },
    methods: {
      getTabs(num) {
        let coinsData = {};
        Object.assign(coinsData, JSON.parse(sessionStorage.oldData));
        Object.assign(coinsData, {page: num});
        getcoindata(coinsData);
      },
      down() {
        console.log(1);
      }
    }
  }).$mount('#tabsPages');
  // 防止币列表 多次点击
  let isUpdate = false;

  /* eslint-disable */
  // const html = $("span[data-ccodetext='code']").html();
  // new window.QRCode(document.getElementById("qrcodeImg"), {
  //   text: html,
  //   width: 99,
  //   height: 99,
  //   colorDark : "#000000",
  //   colorLight : "#ffffff",
  //   correctLevel : window.QRCode.CorrectLevel.H
  // });
  // 沒有數據顯示圖標  isHideTips: boolean。如果需要隱藏，傳 true

  // 複制粘貼
  $('[data-copy="ele"]').click(function (e) {
    if (!document.execCommand) {
      alert(lang['COPY_NOT_SUPPORT']); //您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。
    }
    const text = $("#coinAddr").html();
    var transfer = document.getElementById('J_CopyTransfer');
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
    transfer.value = text || '';
    transfer.focus();
    transfer.select();
    try {
      var succ = document.execCommand('Copy', false, null);
      if (succ) {
        myAlert.show(lang['COPY_SUCCESS']); //複制成功，請通過 ctrl + v 鍵粘貼。
      } else {
        myAlert.show(lang['COPY_NOT_SUPPORT']); //您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。
      }
    } catch (e) {
      if (e) {
        myAlert.show(lang['COPY_NOT_SUPPORT_UPDATE']); //您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。
      }
    }
  });

  // 幣篩選條件
  function showSelCoinName($this) {
    if (isUpdate) return;
    $("#coinList .sel-coin").removeClass("sel-coin");
    const coinCode = $(this).data('coincode');
    $(this).addClass("sel-coin");
    $("#coinName").html($($this).html().trim());
  }
  // 初始化
  $("#coinName").html($("#coinList .sel-coin").html().trim());
  $('#coinList span[data-coincode]').click(function() {
    showSelCoinName(this);
  });
  // $("#coinFlag span[data-flag]").click(function() {
  //   if (isUpdate) return;
  //   $("#coinFlag .sel-coin").removeClass("sel-coin");
  //   const coinFlag = $(this).data('flag');
  //   $(this).addClass("sel-coin");
  // });

  //初始化
  function inisetdata() {
    var coin = $('.sel-coin').attr('data-coincode');
    let canCoinIn = $('.sel-coin').attr('data-instatus');
    let coinsData = {coin: coin, coinType: 'in', type: 'all'};
    if (canCoinIn == 0) {
      // 獲取錢包地址
      getCoinAddress(coin);
    } else {
      // 不能充值
      $("#addrLoading").hide();
      $("#addrShow").hide();
      $("#canNotIn").show();
      $("#qrcodeImg img").removeClass('succ-img').attr("src", '/imgs/can_not_in.png');
    }
    getcoindata(coinsData);
    getcoinmessage(coin);
    $('span[data-ntcoin]').html($('.sel-coin').html().trim());
    // 獲取錢包地址
  }
  // 獲取幣列表數據
  //
  function getcoindata({coin, coinType, type, startTime, endTime, page, callback}) {
    const obj = {coin, coinType, type, startTime, endTime, page, callback};
    sessionStorage.oldData = JSON.stringify(obj);
    page == undefined ? page = 1 :page = page;
    http({
      url: '/ajax_user/coinRecord',
      type: 'POST',
      dataType: 'json',
      data: {
        coin: coin,
        coinType: coinType,
        type: type,
        startTime: startTime,
        endTime: endTime,
        page: page
      },
      success(req) {
        if (req.status && parseInt(req.status) === 1) {
          let { data } = req;
          let html = '';
          if (is(data.list, 'Array')) {
            // 有數據
            if (data.list.length > 0) {
              $('#tableNoData').hide();
              data.list.forEach((coin) => {
                html += `<tr>
                <td>${coin.id}</td>
                <td>${coin.time}</td>
                <td>${coin.txid}</td>
                <td>${coin.bid}</td>
                <td>${coin.number}</td>
                <td>${coin.confirm}</td>
                <td class="${coin.colour == 1
                    ? 'green-font'
                    : 'orange-font'}">${coin.status}</td>
                </tr>`;
              });
              $('#dataBody').html(html);
              // 重置表頭
              setTableStyle();

            } else {
              $('#tableNoData').show()
            }
          }
          pageVue.all = data.pagetotal;
          pageVue.currentpage = data.currentpage;
        }
      },
      error(err) {
        if (err)
          myAlert.show(lang['GET_DATA_FAIL']);
      }
    });
  }
  // 生産二維碼
  function makeCodeImg(addrText) {
    let img = document.createElement('img');
    img.src = `/Ajax_user/qrimages?text=${addrText}`;
    img.className = 'succ-img';
    $("#addrShow").hide();
    $("#addrLoading").show();
    img.onload = function() {
      $('#qrcodeImg').html('').append(img);
      $("#addrShow").show();
      $("#addrLoading").hide();
      $('#coinAddr').html(addrText);
      img.onload = '';
      isUpdate = false;
    }
  }
  // 獲取錢包地址
  function getCoinAddress(coin) {
    if (isUpdate) return;
    isUpdate = true;

    $('#qrcodeImg img').attr('src', '/imgs/creating_qrCode.png').removeClass('succ-img');
    $('#coinAddr').html("");

    // 生成中顯示
    // 不能充幣顯示
    $("#canNotIn").hide();
    // return;
    http({
      url: '/ajax_user/getCoinAddress',
      type: 'POST',
      data: {
        coin: coin,
      },
      success(req) {
        let currCoin = $("#coinList").find(".sel-coin");
        let tagNow = '';
        if (currCoin.length > 0) {
          tagNow = currCoin.html().trim().toUpperCase();
        }

        // 获取数据成功
        if (parseInt(req.status) === 1) {
          const { data } = req;
          if (!data['wallet']) {
            // $("#qrcodeImg").html("");
            $('#coinAddr').html("");
            $("#linkToOut").hide();
            isUpdate = false;
          } else {
            makeCodeImg(data['wallet']);
            // 显示跳转BTC场外交易
            if (tagNow == "BTC") {
              $("#linkToOut").show();
            }
            else {
              $("#linkToOut").hide();
            }
          }
        } else {
          $("#addrShow").hide();
          $("#addrLoading").show();
          isUpdate = false;
        }
        // 防止重复地址
      },
      error(err) {
        if (err) {
          // console.log(err);
          // alert('獲取錢包數據失敗');
        }
        isUpdate = false;
        // 防止重复地址
      }
    });
  }
  // 一句話
  function getcoinmessage(coin) {
    http({
      url: '/ajax_user/coinRecordMessage',
      type: 'POST',
      dataType: 'json',
      data: {
        coin: coin
      },
      success: function({ data }) {
        //
        if (is(data, "Object")) {
          if (data.message) {
            $('.u-riches').show().html(data.message);
            // no-tips-cond-time
            $('.u-riches').siblings(".cond-time").removeClass("no-tips-cond-time")
          } else {
            $('.u-riches').hide();
            $('.u-riches').siblings(".cond-time").addClass("no-tips-cond-time")
          }
        }
      },
      error: function(err) {
        // if (err) myAlert.show(err);
      }
    });

  }


  // 點擊幣種
  $('#coinList>span').click(function(e) {
    if (e.stopPropagation) e.stopPropagation();
    if (e.preventDefault) e.preventDefault();
    // 地址 加载中
    $("#addrLoading").show();
    $("#addrShow").hide();
    if (isUpdate) return;
    //
    var coin = $(this).attr('data-coincode');
    $('#dataBody').html('');
    let canCoinIn = $(this).data('instatus');
    // 是否可以充值
    if (canCoinIn === 0) {
      // 獲取錢包地址
      getCoinAddress(coin);
    } else {
      // 不能充值
      $("#addrLoading").hide();
      $("#addrShow").hide();
      $("#canNotIn").show();
      $("#qrcodeImg img").removeClass('succ-img').attr("src", '/imgs/can_not_in.png');
    }
    getcoinmessage(coin);
    $('span[data-ntcoin]').html($(this).html().trim());
    $('[name=coin]').val(coin);
    $(this).addClass("sel-coin");
    $("#qkBtn .sel-qk-btn").removeClass('sel-qk-btn');
    $('#allCoin').addClass('sel-qk-btn');

    let coinsData = {coin: coin, coinType: 'in', type: 'all'};
    getcoindata(coinsData);

  });
  // 點擊全部
  $('#allCoin').click(function() {
    $("#qkBtn .sel-qk-btn").removeClass('sel-qk-btn');
    $(this).addClass("sel-qk-btn");
    var coin = $('.sel-coin').attr('data-coincode');
    $('#dataBody').html('');
    $('[name=type]').val('all');
    let coinsData = {coin: coin, coinType: 'in', type: 'all'};
    getcoindata(coinsData);
  });
  // 點擊今天
  $('#todayCoin').click(function() {
    $("#qkBtn .sel-qk-btn").removeClass('sel-qk-btn');
    $(this).addClass("sel-qk-btn");
    var coin = $('.sel-coin').attr('data-coincode');
    $('#dataBody').html('');
    $('[name=type]').val(1);
    let coinsData = {coin: coin, coinType: 'in', type: 1};
    getcoindata(coinsData);
  });
  // 點擊30天
  $('#monthCoin').click(function() {
    $("#qkBtn .sel-qk-btn").removeClass('sel-qk-btn');
    $(this).addClass("sel-qk-btn");
    var coin = $('.sel-coin').attr('data-coincode');
    $('#dataBody').html('');
    $('[name=type]').val(2);
    let coinsData = {coin: coin, coinType: 'in', type: 2};
    getcoindata(coinsData);
  });
  // 點擊篩選
  $('#timeselect').click(function() {
    var startTime = $('#startTime').val();
    var endTime = $('#endTime').val();
    $('[name=type]').val(3);
    $('[name=startTime]').val(startTime);
    $('[name=endTime]').val(endTime);
    if (startTime == '') {
      myAlert.show(lang["START_TIME"]); //請選擇開始時間
      return false;
    }
    if (endTime == '') {
      myAlert.show(lang["END_TIME"]); //請選擇結束時間
      return false;
    }
    if (startTime && endTime) {
      if (startTime >= endTime) {
        myAlert.show(lang['START_GT_END']); //開始時間不能大于等于結束時間
        return false;
      }
    }
    $('#dataBody').html('');
    var coin = $('.sel-coin').attr('data-coincode');

    let coinsData = {coin: coin, coinType: 'in', type: 3, startTime: startTime, endTime: endTime};
    getcoindata(coinsData);
  });
  // 點擊重置
  $('#reset').click(function() {
    var coin = $('.sel-coin').attr('data-coincode');
    $('#dataBody').html('');
    $("#startTime").val('');
    $("#endTime").val('');
    $('[name=type]').val('all');
    $("#qkBtn .sel-qk-btn").removeClass('sel-qk-btn');
    $('#allCoin').addClass('sel-qk-btn');
    let coinsData = {coin: coin, coinType: 'in', type: 'all'};
    getcoindata(coinsData);
  });
  // 點擊導出excel
  $('#excel').click(function() {
    var coin = $("#coinList .sel-coin").html().trim().toLowerCase();
    var coinType = $('[name=coinType]').val();
    var type = $('[name=type]').val();
    var startTime = $('[name=startTimeex]').val();
    var endTime = $('[name=endTimeex]').val();
    location.href = '/user/coinRecordCsvOut?coin=' + coin + '&coinType=' + coinType + '&type=' + type + '&startTime=' + startTime + '&endTime=' + endTime;
  });
  // 獲取路由參數
  function GetQueryString(name) {
    const reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    const r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
  }
  // 默認選擇全部篩選
  $('#allCoin').addClass("sel-qk-btn");
  // 是否通過賬戶中心點擊跳轉過來
  const coin = GetQueryString('coin');
  // console.log(coin);
  if (coin) {
    $('#dataBody').html('');
    $(`#coinList .sel-coin`).removeClass("sel-coin");
    $(`#coinList span[data-coincode="${coin}"]`).addClass("sel-coin");
    // 模拟点击选择的币 点击事件
    $('#coinList span.sel-coin').click();
  } else {
    inisetdata();
  }
});
