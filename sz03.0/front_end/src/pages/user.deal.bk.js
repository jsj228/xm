import '@/styles/common/index.scss';
import '@/styles/userCenter/index.scss';
import '@/styles/userCenter/userTrust.scss';
import "@/styles/userCenter/sureDialog.scss";
import Flatpickr from "flatpickr";
import Zh from "flatpickr/dist/l10n/zh.js";
import nav from '@/components/nav';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import http from '@/tools/http';
import dialog from "@/tools/dialog";
import is from "@/tools/is";
import Alert from '@/tools/alert/alert';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import talking from '@/tools/talking/talking';
import cookie from '@/tools/cookie';

// process.env.NODE_ENV
// 我的賬戶 轉幣 提幣 委托 成交 頁面js
$(document).ready(function() {
  const navNew = navVue();
  //打開聊天室接受數據功能
  talking();
  // 切換 請求處理 語言包
  chkhttpLang("#baseLang", http);
  // 配置语言
  const phpLang = getLanguagePack() || {
    canNotCopy: '您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。',
    sureBtn: '確定',
    copyRight: '複制成功，請通過 ctrl + v 鍵粘貼。',
    copyError: '您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。'
  };

  const myAlert = new Alert("");
  // myAlert.setHeader("提示");
  nav();
  activeNav();

  // 提交成功 提示彈框
  function postSucceDialog(msg, callback) {
    const template = `<div class="au_dialog">
      <u class="close_btn" data-diclose="btn"></u>
      <div>
        <p>${msg}</p>
        <button class="submit_btn" id="sureDeal" data-diclose="btn">${phpLang.sureBtn}</button>
      </div>
    </div>`;
    // 清空顯示內容
    dialog.html(template);
    // dialog.html()
    dialog.css({ width: "452px", height: "274px", borderRadius: "8px", position: "relative" });
    dialog.show(true);
    $('[data-diclose="btn"]').click(function() {
      // 重定向
      if (callback && is(callback, "Function")) {
        callback();
      }
      dialog.hide(true);
    });
  }
  let nowLang = cookie.getItem('LANG');
  // 切换语言
  if (nowLang === 'cn') {
    Flatpickr.localize(Zh.zh);
  }
  // 日曆
  const startTimtOptions = {
    enableTime: true,
    // defaultDate: "today",
    maxDate: $("#endTime").val() || "",
    dateFormat: "Y-m-d H:i:S"
  };
  const startTime = new Flatpickr("#startTime", startTimtOptions);

  const endTimtOptions = {
    enableTime: true,
    // defaultDate: "today",
    minDate: $("#startTime").val() || "",
    dateFormat: "Y-m-d H:i:S"
  };
  const endTime = new Flatpickr("#endTime", endTimtOptions);
  // 重置表格顯示
  function setTableStyle() {
    // copy thead
    if ($("#tHead").length === 0) {
      const myTbHead = document.createElement('div');
      myTbHead.id = "tHead";
      myTbHead.className = "t-head";
      $(".coin-in-table").eq(0).prepend(myTbHead);
      const catchTable = document.createElement("table");
      $("#tHead").append(catchTable);
      $("#tHead table").append($("#tBody table thead").clone());
      // set style
      const tbodyTr = $("#tBody table tbody tr").eq(0);
      const tbodyTds = [...tbodyTr.find("td")];
      const tHeadTds = $("#tHead table thead tr").eq(0).find("td");
      tbodyTds.forEach((td, key) => {
        const bodyTdWidth = $(td).css("width");
        tHeadTds.eq(key).css("width", bodyTdWidth);
      });
    }
    // 滾動情況
    if (parseInt($("#tBody").css("height")) < parseInt($("#tBody table").css("height"))) {
      $("#tHead table td:last-child").css("paddingRight", "45px");
    }
  }
  // 初始化
  setTableStyle();
  //
  window.onresize = function() {
    setTableStyle();
  };

  /* eslint-disable */
  if (location.href.indexOf("coinin") > -1) {
    const html = $("span[data-ccodetext='code']").html();
    new window.QRCode(document.getElementById("qrcodeImg"), {
      text: html,
      width: 99,
      height: 99,
      colorDark : "#000000",
      colorLight : "#ffffff",
      correctLevel : window.QRCode.CorrectLevel.H
    });
  }
  // 沒有數據顯示圖標  isHideTips: boolean。如果需要隱藏，傳 true

  // 複制粘貼
  $('[data-copy="ele"]').click(function (e) {
    if (!document.execCommand) {
      // '您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。'
      alert(phpLang.canNotCopy);
    }
    const text = $("#coinAddr").html();
    console.log(text);
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
        // '複制成功，請通過 ctrl + v 鍵粘貼。'
        myAlert.show(phpLang.copyRight);
      } else {
        // 您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。
        myAlert.show(phpLang.copyError);
      }
    } catch (e) {
      if (e) {
        myAlert.show(phpLang.copyError);
      }
    }
  });

  // 幣篩選條件
  $('#coinList span[data-coincode]').click(function() {
    $("#coinList .sel-coin").removeClass("sel-coin");
    const coinCode = $(this).data('coincode');
    $(this).addClass("sel-coin");
    $("#coinPost input[name='coin']").val(coinCode);
    $("#coinPost").submit();
  });
  // 快捷方式提交
  function quTypePost($this) {
    const type = $($this).data("days");
    $("#coinPost input[name='days']").val(type);
    $("#coinPost").submit();
  }
  // 全部
  $("#allCoin").click(function(){
    quTypePost(this);
  });
  // 當天
  $("#todayCoin").click(function(){
    quTypePost(this);
  });
  // 30天
  $("#monthCoin").click(function(){
    quTypePost(this);
  });
  // 重置
  $("#reset").click(function() {
    const inputs = [...$("#coinPost input")];
    inputs.forEach((input) => {
      if ($(input).attr('name') !== 'coin') {
        $(input).val("");
      }
    });
    quTypePost($("#allCoin"));
  });
  const coinCodeCatch = $("#coinList .sel-coin").data('coincode');
  $("#coinPost input[name='coin']").val(coinCodeCatch);
  $("#timeselect").mousedown(function() {
    // 選擇時間區間，更換 快捷鍵狀態 -1 表示不在 全部，當天，30天 中任意一個
    $("#coinPost input[name='days']").val('-1');
  });
  // 撤銷 委托
  $('#cancelOrder [data-cancel]').click(function() {
    const $this =$(this);
    const id = $this.data('cancel');
    const coinTo = $this.data('cointo');
    const coin_from = $("#coinList .sel-coin").data('coincode');
    http({
      url: '/ajax_trade/trustcancel',
      method: "POST",
      data: {
        id: id,
        coin_to: coinTo,
        coin_from: coin_from
      },
      success(data) {
        if (data && parseInt(data.status) === 1) {
          postSucceDialog(data.msg, function() {
            window.location.reload();
          });
        }
      },
      error(err) {
        if (err) {
          myAlert.show(err);
        }
      }
    });
  });
  // console.log(new Date());
  $("#coinFlag span[data-flag]").click(function() {
    const flag = $(this).data('flag');
    // const type = $($this).data("days");
    $("#coinPost input[name='flag']").val(flag);
    $("#coinPost").submit();
  });

  // 判断时间 开始 不大于 结束
  $("#coinPost").on("submit", function() {
    let startTime = $('#startTime').val();
    let endTime = $('#endTime').val();
    if (startTime && endTime) {
      if (startTime >= endTime) {
        myAlert.show('開始時間不能大于等于結束時間'); //開始時間不能大于等于結束時間
        return false;
      }
    }
  });
});
