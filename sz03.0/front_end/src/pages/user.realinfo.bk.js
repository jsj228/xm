// import '@/styles/common/index.scss';
// import '@/styles/userCenter/index.scss';
// import '@/styles/userCenter/authen.scss';
// import '@/styles/userCenter/sureDialog.scss';
import 'nStyle/user/realinfo.scss';

// import nav from '@/components/nav';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import Tips from '@/tools/alert/alert.js';
import dialog from '@/tools/dialog';
import is from '@/tools/is';
import getLanguagePack from '@/components/tradeLanguagePack';
import talking from '@/tools/talking/talking'

// 實名認證
$(document).ready(function() {
  const navNew = navVue();
  //打開聊天室接受數據功能
  talking();
  // console.log(getLanguagePack());
  var language = getLanguagePack() || {
    BTN_SURE: "確定",
    SUBMIT_SUCC: "實名認證資料提交成功，審核時間為1~3個工作日。",
    PIC_FRONT: "正面照片未選擇",
    PIC_BACK: "背面照片未選擇",
    PIC_HAND: "手持照片未選擇",
    FILE_READER_NO: "抱歉，你的浏覽器不支持 FileReader,請使用谷歌(chrome)或火狐(firefox)浏覽器操作！",
    PIC_SIZE_MAX: "照片大小不能超過2M，請重新上傳！",
    PIC_FORMAT_ERROR: "證件圖片支持jpg/jpeg/png格式,暫時還不支持其他格式。",
    PICK_PIC: "選擇圖片",
    PIC_FULL: "證件照片需要上傳完整"
  };
  //获取语言包
  // const lan = getLanguagePack($('#errTips'));
  // console.log(lan);
  // if (lan.userAuthJs.length > 0) { //language.json中tradeJs為空時輸出數組，不為空時輸出的是對象
  //   language = lan.userAuthJs;
  // }

  // 實名狀態  $user=='空'(待認證) 1(待審核) 2(已認證) 3(審核失敗)
  const status = $("[data-status]").data('status');
  const errMsg = $('[data-errmsg]').data('errmsg');
  // 提交成功 提示彈框
  function postSucceDialog(msg, callback) {
    const template = `<div class="au_dialog">
      <u class="close_btn" data-diclose="btn"></u>
      <div>
        <p>${msg}</p>
        <button class="submit_btn" data-diclose="btn">${language['BTN_SURE']}</button>
      </div>
    </div>`;
    // 清空顯示內容
    dialog.html(template);
    // dialog.html()
    dialog.css({ width: "454px", height: "274px", borderRadius: "8px", position: "relative" });
    dialog.show(true);
    $('[data-diclose="btn"]').click(function() {
      // 重定向
      if (callback && is(callback, "Function")) {
        callback();
      }
      dialog.hide(true);
    });
  }
  if (window.location.href.indexOf("success") > -1) {
    postSucceDialog(language['SUBMIT_SUCC'], function() { //'實名認證資料提交成功，審核時間為1~3個工作日。'
      window.location.href = '/user/realinfo';
    });
  } else {

    $("input").change(function () {
      $('button[data-submit="form"]').removeAttr('disabled');
    });
    $("input").on('input', function () {
      $('button[data-submit="form"]').removeAttr('disabled');
    });
  }
  // 錯誤 提示
  if (errMsg) {
    postSucceDialog(errMsg);
  }
  // 提交成功彈框提示
  const myAlert = new Tips("");
  // myAlert.show("提示");
  nav();
  activeNav();
  let submit_busy = false;
  // 實名 校驗正則
  const regs = {
    name: /(^[\u4e00-\u9fa5]{1}[\u4e00-\u9fa5·]{0,98}[\u4e00-\u9fa5]{1}$)|(^[a-zA-Z]{1}[a-zA-Z\s]{0,98}[a-zA-Z]{1}$)/,
    idcard: {
      1: {
        // id15: /^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}[0-9Xx]$/,
        id18: /^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/
      },
      2: /^[a-zA-Z\d\-\.]{1,20}$/,
      // 2: /^(P[.]\d{7}|G\d{8}|S\d{8}|S[.]\d{7}|D\d{7,8}|1[4,5]\d{7})$/
    },
    imgFile: {

      frontFace: {
        result: '',
        tips: language['PIC_FRONT'] //正面照片未選擇
      },
      backFace: {
        result: '',
        tips: language['PIC_BACK'] //背面照片未選擇
      },
      handkeep: {
        result: '',
        tips: language['PIC_HAND'] //手持照片未選擇
      }
    },
    msg: 0,
    isChangeFile: false
  };
  //
  if (status === 1) {
    regs.msg = 3;
    $('input[type="file"]').change(function () {
      regs.isChangeFile = true;
    });
  }
  // 實名
  /* eslint-disable */
  if ($("[data-pname]").data("")) {
    if (typeof(FileReader) === 'undefined') {
      myAlert.show(language['FILE_READER_NO']);  //抱歉，你的浏覽器不支持 FileReader,請使用谷歌(chrome)或火狐(firefox)浏覽器操作！
    }
  }
  /* eslint-enable */
  const imgMaxSize = 2097152;
  function readFile() {
    const imgDom = $(this).siblings('img');
    const fileBit = this.files[0].size;
    // 圖片大小判斷
    if (fileBit) {
      if (fileBit > imgMaxSize) {
        return myAlert.show(language['PIC_SIZE_MAX']); //照片大小不能超過2M，請重新上傳！
      }
    }

    // 圖片類型判斷
    const fileType = this.files[0].type;
    if (!fileType.match(/(jpg|jpeg|png)/g)) {
      return myAlert.show(language['PIC_FORMAT_ERROR']); //證件圖片支持jpg/jpeg/png格式,暫時還不支持其他格式。
    }

    const file = this.files[0];
    //判斷是否是圖片類型
    if (!/image\/\w+/.test(fileType)) {
      myAlert.show(language['PIC_FORMAT_ERROR']); //證件圖片支持jpg/jpeg/png格式,暫時還不支持其他格式。
      return false;
    }

    const reader = new FileReader();
    reader.readAsDataURL(file);

    /* eslint-disable */
    reader.onload = function(e) {
      // 設置 預覽圖 會變形
      imgDom.attr('src', this.result).css({zIndex: '20'});
      // 標記
      imgDom.siblings('b[data-del="img"]').show();
      imgDom.siblings('p[data-select="btn"]').addClass('correct_statu').find('span').text('');

    };
  }
  // 下拉列表
  $('[data-select="cardtype"]').click(function() {
    const selectDom = $(this);
    selectDom.val(1);
    const data = selectDom.data("select");
    const targetList = $(`#${data}`);
    if (targetList.is(":visible")) {
      targetList.hide();
    } else {
      targetList.show();
      targetList.find("div").click(function(e) {
        const optionData = $(e.target).data("value");
        selectDom.val(optionData);
        targetList.hide();
      });
    }
  });
  // 選擇圖片
  $('input[data-file="cert"]').change(readFile);
  // 刪除 圖片
  $('b[data-del="img"]').click(function() {
    var target = $(this);
    target.siblings('img').attr('src', '').css({ zIndex: '1' });
    target.siblings('input').val("").removeAttr('disabled', 'disabled');
    target.hide();
    target.siblings('p[data-select="btn"]').removeClass('correct_statu').find('span').text(language['PICK_PIC']); //選擇圖片
    target.siblings('img').data('src', 0);
  });
  // 提示框
  function warnTips(thisInput, isCorrect, isSubmit) {
    // console.log(thisInput, isCorrect, isSubmit);
    const tipsElm = $(thisInput).siblings("[data-tips]");
    let tipsMsg = "";
    //
    if (isCorrect) {
      tipsMsg = tipsElm.data("msg");
    } else {
      tipsMsg = tipsElm.data("err");
    }
    // 驗證失敗 內容不為 空
    if ( !isCorrect) {
      if (isSubmit || $(thisInput).val()) {
        // 驗證失敗 提示 sjdlfkj
        tipsElm.html(tipsMsg).show().addClass("input-tips-err");
      }
    } else {
      tipsElm.removeClass("input-tips-err").hide();
    }
  }

  // 校驗 input 內容是否正確
  function checkInput(input, isSubmit) {
    // console.log(input, isSubmit);
    const $input = $(input);
    const val = $input.val();
    const inputName = $input.attr("name");
    const inputType = $input.attr("type");
    let result = false;
    if (inputType === 'text') {
      switch (inputName) {
        case 'name':
          result = regs[inputName].test(val);
          //
          if (!result && isSubmit) {
            warnTips(input, result, isSubmit);
          }
          break;
        case 'idcard': {
          const type = $('[name="cardtype"]').val();
          if (type === '1') {
            // \u4e00-\u9fa5]
            if (regs[inputName][type].id18.test(val)) {
              result = true;
            }
          } else {
            result = regs[inputName][type].test(val);
          }
          // 提示框
          if (!result && isSubmit) {
            warnTips(input, result, isSubmit);
          }
          break;
        }
        default:
          result = false;
      }
    } else if (inputType === 'file') {
      result = true;
    }
    return result;
  }
  function imgIsComplete() {
    const imgs = [...$("#realForm img")];
    let result = true;
    // 判斷圖片是否有值
    let fronId = $('#realForm');
    let url = fronId.attr('action');
    imgs.forEach(function(img) {
      if (!$(img).attr('src')) {
        result = false;
      }
    });
    // 判斷 input 是否有值
    const inputs = [...$('#realForm input[type="file"]')];
    inputs.forEach((input) => {
      if (!$(input).val()) {
        result = false;
      }
    });
    // console.log(result);
    return result;
  }
  imgIsComplete();

  // 獲取所有form 表單 input
  function getFormInput() {
    if (submit_busy) return;
    submit_busy = true;
    let isComplete = true;
    const inputs = [...$("#realForm input")];
    inputs.forEach((input) => {
      if (!checkInput(input, 'isSubmit')) {
        isComplete = false;
        submit_busy = false;
      }
    });
    //
    if (status !== 1 && !imgIsComplete()) {
      postSucceDialog(language['PIC_FULL']);
      isComplete = false;
      submit_busy = false;
    }
    if (status === 1 && regs.isChangeFile) {
      if (!imgIsComplete()) {
        postSucceDialog(language['PIC_FULL']);
        isComplete = false;
        submit_busy = false;
      }
    }
    return isComplete;
  }

  // input fouce
  $("#realForm input[type='text']").focus(function() {
    const tipsElm = $(this).siblings("[data-tips]");
    let tipsMsg = "";
    // console.log(checkInput(this));
    if ($(this).val()) {
      if (checkInput(this)) {
        tipsMsg = tipsElm.data("msg");
      } else {
        tipsMsg = tipsElm.data("err");
      }
    } else {
      tipsElm.removeClass('input-tips-err');
      tipsMsg = tipsElm.data("msg");
    }
    $(this).siblings("[data-tips]").html(tipsMsg).show();
  });

  $("#realForm input[type='text']").blur(function() {
    const isCorrect = checkInput(this);
    // 為空
    if (!$(this).val()) {
      warnTips(this, true);
    } else {
      warnTips(this, isCorrect);
    }
  });
  // 實名提交檢驗
  $("#realForm").on("submit", function() {
    return getFormInput();
  });
});
