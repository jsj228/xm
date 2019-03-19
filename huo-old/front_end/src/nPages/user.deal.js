import "flatpickr/dist/flatpickr.min.css";
import 'nStyle/user/common.scss';
import "styles/userCenter/coinInTable.scss";
import 'nStyle/user/deal/deal.scss';
import "@/styles/userCenter/sureDialog.scss";
import 'styles/userCenter/index.scss';
import Flatpickr from "flatpickr";
import Zh from "flatpickr/dist/l10n/zh.js";
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
import condition from '@/nComponents/user.condition';

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
  // 篩選條件 提交
  condition(phpLang);

  const myAlert = new Alert("");
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

  // 撤銷 委托
  $('#cancelOrder [data-cancel]').click(function() {
    const $this =$(this);
    const id = $this.data('cancel');
    const coinTo = $this.data('cointo');
    const coin_from = $("#allCoinList .sel-coin").data('coincode');
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
});
