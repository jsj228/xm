import '@/styles/userCenter/mplan.scss';
import nav from '@/components/nav';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import Alert from '@/tools/alert/alert';
import DialogBox from '@/tools/dialogBox/dialogBox';
import dialog from '@/tools/dialog';
import is from '@/tools/is';
import http from '@/tools/http';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import talking from '@/tools/talking/talking'

// process.env.NODE_ENV
$(document).ready(function() {
  const navNew = navVue();
  //打開聊天室接受數據功能
  talking();
  chkhttpLang("#baseLang", http);
  nav();
  var language = getLanguagePack() || {
    COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
    COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
    COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。",
    BTN_SURE: "確定",
    FORM_ERROR_ADDRESS: "請輸入有效的接受地址",
    FORM_ERROR_NUMBER: "請輸入提取數額",
    FORM_ERROR_OVER_NUMBER: "提取數額不符合要求",
    FORM_MSG_TRADE_PWD: "請輸入交易密碼",
    FORM_ERROR_TRADE_PWD: "交易密碼錯誤",
    FORM_MSG_CAPTCHA: "看不清？點擊圖片更換驗證碼",
    FORM_ERROR_CAPTCHA: "位數不正確",
    FORM_MSG_CODE: "請輸入手機驗證碼",
    FORM_ERROR_CODE: "手機驗證碼錯誤",
    REWARD_SUCCESS: "獎勵提取成功"
  };
  //获取语言包
  activeNav();
  // alert
  let myAlert = new Alert();
  //
  // 链接+协议
  let oldUrl = $("#coinAddr").val();
  oldUrl = oldUrl.replace(/[a-z.]+.com/, function(key) {
    return location.protocol + '//' + key;
  })
  $("#coinAddr").val(oldUrl);
  // 複制粘貼
  $('[data-copy="ele"]').click(function(e) {
    if (!document.execCommand) {
      alert(language['COPY_NOT_SUPPORT']); //複製不成功，不支持
    }
    const text = $("#coinAddr").val();
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
        myAlert.show(language['COPY_SUCCESS']); //復製成功
      } else {
        myAlert.show(language['COPY_NOT_SUPPORT']); //複製不成功，不支持
      }
    } catch (e) {
      if (e) {
        myAlert.show(language['COPY_NOT_SUPPORT_UPDATE']); //複製不成功，升級瀏覽器
      }
    }
  });
  
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
    dialog.css({width: "452px", height: "274px", borderRadius: "8px", position: "relative"});
    dialog.show(true);
    $('[data-diclose="btn"]').click(function() {
      // 重定向
      if (callback && is(callback, "Function")) {
        callback();
      }
      dialog.hide(true);
    });
  }
  // 提幣 form 彈框
  const myBox = new DialogBox();
  myBox.css({ height: 'auto' });
  const regs = {
    wallet: {
      msg: language['FORM_ERROR_ADDRESS'], //請輸入有效的接受地址
      errMsg: language['FORM_ERROR_ADDRESS'] //請輸入有效的接受地址
    },
    number: {
      msg: language['FORM_ERROR_NUMBER'], //請輸入提取數額
      errMsg: language['FORM_ERROR_NUMBER'], //請輸入提取數額
      overMsg: language['FORM_ERROR_OVER_NUMBER'], //提取數額不符合要求
      less: ""
    },
    mcc: {
      msg: language['FORM_ERROR_NUMBER'], //請輸入提取數額
      errMsg: language['FORM_ERROR_NUMBER'], //請輸入提取數額
      overMsg: language['FORM_ERROR_OVER_NUMBER'], //提取數額不符合要求
      less: ""
    },
    btc: {
      msg: language['FORM_ERROR_NUMBER'], //請輸入提取數額
      errMsg: language['FORM_ERROR_NUMBER'], //請輸入提取數額
      overMsg: language['FORM_ERROR_OVER_NUMBER'], //提取數額不符合要求
      less: ""
    },
    pwdtrade: {
      msg: language['FORM_MSG_TRADE_PWD'], //請輸入交易密碼
      errMsg: language['FORM_ERROR_TRADE_PWD'] //交易密碼錯誤
    },
    captcha: {
      msg: language['FORM_MSG_CAPTCHA'], //看不清？點擊圖片更換驗證碼
      errMsg: language['FORM_ERROR_CAPTCHA'] //位數不正確
    },
    code: {
      msg: language['FORM_MSG_CODE'], //請輸入手機驗證碼
      errMsg: language['FORM_ERROR_CODE'] //手機驗證碼錯誤
    }
  };
  // 提幣彈框
  function coinOutBox(coin) {
    const coinOutBoxTpl = $("#coinOutTpl").html();
    // 清空 模板
    $("#coinOutTpl").html('');
    myBox.tryCallback = function() {
      $("#coinOutTpl").html(coinOutBoxTpl);
    }
    myBox.show(coinOutBoxTpl, function() {
      // 提示框
      function tipWinCtrl($input, isHide, tipsType) {
        const name = $input.attr('name');
        if (!isHide) {
          // 顯示 提示 類型
          switch (tipsType) {
            case 'err':
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(regs[name].errMsg).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            case 'over':
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(regs[name].overMsg).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            case 'less':
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(regs[name].less).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            default:
              $input.siblings('.warn-tips-lf').html(regs[name].msg).show();
              $input.parents('.form-tab').removeClass('warn-outline');
          }
        } else {
          $input.siblings('.warn-tips-lf').hide();
          $input.parents('.warn-outline').removeClass('warn-outline');
        }
      }
      // 保存验证码类型
      let phoneAction = '';
      let coinOutType = '';
      let areaType = '';
      // 切換提幣類型
      $("#outType").change(function() {
        let selVal = $(this).val();
        let coinName = $(this).children("option:selected").data('cname');
        let coinType = $(this).children("option:selected").data('ctype');
        coinOutType = coinName;
        areaType = coinType;
        $('#outType').parent('.top-tab').removeClass('warn-outline');
        $('#outType').siblings('.warn-tips-lf').removeClass('err-tips').hide();
        if(!selVal) {
          $(this).css({color: '#666'});
        } else {
          $(this).css({color: '#333'});
        }
        // 切换提示内容
        myBox.content.find('[data-ccoin]').hide();
        myBox.content.find(`[data-ccoin="${coinName}"][data-ctype="${areaType}"]`).fadeIn('fast');
        // 清空错误提示
        myBox.content.find('.err-tips').removeClass('err-tips');
        myBox.content.find('.warn-outline').removeClass('warn-outline');
        // 清空已经填写的内容
        myBox.content.find('input[name="number"]').val('');
      });
      // 檢驗輸入結果
      function checkInput($input, name, val, isSubmit) {
        let result = true;
        // 輸入為 空
        if (!val) {
          // 按提交按鈕 顯示錯誤提示
          if (isSubmit) {
            tipWinCtrl($input, false, 'err');
          } else {
            tipWinCtrl($input, false, '');
          }
          //
          result = false;
          // return false;
        } else {
          // 提取 mcc
          if (coinOutType === 'mcc') {
            let rule = /^([1-9]|([1-9][0-9]+))$/;
            if (!rule.test(val)) {
              // 提取数额不符合要求
              tipWinCtrl($input, false, 'over');
              result = false;
            } else {
              // 输入不符合规则
              if (parseFloat(val) < 50) {
                result = false;
                //
                if (isSubmit) {
                  // $input.
                  tipWinCtrl($input, false, 'over');
                } else {
                  tipWinCtrl($input, false, 'msg');
                }
                $(this).siblings('.err-tips').removeClass('err-tips');
                result = false;
              }
            }
          } else {
            let floatLength = parseInt($(`p[data-ccoin='${coinOutType}'] span[data-limit]`).html());
            let rule = /^(([1-9]([0-9]{0,9})|0))$/;
            if (floatLength) {
              rule = new RegExp(`^(([1-9]([0-9]{0,9})|0)?(?:\.[0-9]{0,${floatLength}})?)$`);
            }
            // btc 提取奖励规则
            //
            if (!rule.test(val)) {
              result = false;
              tipWinCtrl($input, false, 'err');
            } else {
              let minLimit = parseFloat($(`p[data-ccoin='${coinOutType}'] span[data-minlimit="min"]`).html());
              if (!minLimit) {
                minLimit = 0
              }
              // 是否小于最小值
              if (parseFloat(val) - minLimit >= 0) {
                result = true;
                if (isSubmit) {
                  tipWinCtrl($input, true);
                } else {
                  tipWinCtrl($input, false);
                  // 去除提示红色文字
                  $input.siblings('.err-tips').removeClass('err-tips');
                }
              } else {
                if (parseFloat(val) !== 0) {
                  tipWinCtrl($input, false, 'over');
                }
                if (isSubmit) {
                  tipWinCtrl($input, false, 'over');
                }
                result = false;
              }
            }
          }
        }
        return result;
      }
      // input 事件
      function inputEvent($this, isBlur) {
        const $input = $($this);
        const name = $input.attr('name');
        const val = $input.val();
        // 校验input 值的结果
        const inputValComplete = checkInput($input, name, val);
        if (inputValComplete && isBlur) {
          tipWinCtrl($input, 'isHide');
        }
        //
        if (!inputValComplete && isBlur) {
          tipWinCtrl($input, false, 'over');
        }
      }
      myBox.content.find("input").on('focus', function() {
        $(this).siblings('.warn-tips-lf').removeClass("err-tips");
        inputEvent(this);
      });
      myBox.content.find("input").on('blur', function() {
        const $input = $(this);
        // 為空 隱藏提示框
        if (!$(this).val().trim()) {
          tipWinCtrl($input, 'isHide');
        } else {
          inputEvent(this, true);
        }
        // $(this).siblings('.warn-tips-lf').hide();
      });
      myBox.content.find("input").on('input', function() {
        let val = $(this).val();
        if (val) {
          let rule = null;  // /^(([1-9]([0-9]{0,9})|0)?(?:\.[0-9]{0,8})?)$/;
          // 提取mcc 奖励币
          if (coinOutType === 'mcc') {
            rule = /^([1-9]|([1-9][0-9]+))$/;
            if (!rule.test(val)) {
              // 提取数额不符合要求
              tipWinCtrl($(this), false, 'over');
            } else {
              tipWinCtrl($(this), false, 'msg');
              $(this).siblings('.err-tips').removeClass('err-tips');
            }
          }
          // btc
          else {
            let floatLength = parseInt($(`p[data-ccoin='${coinOutType}'] span[data-limit]`).html());
            rule = new RegExp(`^(([1-9]([0-9]{0,9})|0)?(?:\.[0-9]{0,${floatLength}})?)$`);
            if (!rule.test(val)) {
              if (!isNaN(parseFloat(val))) {
                // 獲取小數點后20位 取消科學計數法
                let dot = val.indexOf('.');
                let smallNum = '';
                let parNum = '';
                if (dot > -1) {
                  smallNum = val.slice(dot, floatLength + 1 + dot);
                  parNum = val.slice(0, dot);
                }
                //
                $(this).val(parNum + smallNum);
              } else {
                $(this).val('');
              }
            } else {
              // $input, name, val, isSubmit
              checkInput($(this), $(this).attr('name'), val, false);
            }
          }
        }
      });
      $('img[data-imgcode="code"]').click(function() {
        $(this).attr({src: `/index/captcha?v=${ramdomNum()}`});
      });
      let coinOutBusy = false;
      function coinOutBusyDone() {
        coinOutBusy = false;
        $("#formCoinOutBtn").removeClass('submit-ing').find('u').html('');
      }
      // 提幣 提交表單
      $("#formCoinOutBtn").click(function() {
        // 防止重复提交
        if (coinOutBusy)
          return;
        //
        $(this).addClass('submit-ing').find('u').html('...');
        coinOutBusy = true;
        // 提交結果
        const postData = {};
        // 獲取提幣類型
        let sel = myBox.content.find('select');
        if (!sel.val()) {
          $('#outType').parent('.top-tab').addClass('warn-outline');
          $('#outType').siblings('.warn-tips-lf').addClass('err-tips').show();
        }
        postData.coin = sel.val();
        // 獲取所有輸入框
        const inputs = [...myBox.content.find('input')];
        let isComplete = true;
        inputs.forEach((input) => {
          const $input = $(input);
          const name = $input.attr('name');
          const val = $input.val();
          if (checkInput($input, name, val, 'isSubmit')) {
            postData[name] = val;
          } else {
            isComplete = false;
          }
        });
        // 警告框项
        // myBox.content.find('.warn-outline')
        if (myBox.content.find('.warn-outline').length > 0) {
          isComplete = false;
          coinOutBusy = false;
        }
        if (isComplete) {
          postData.type = areaType;
          http({
            url: '/ajax_user/rebateIn',
            method: "POST",
            data: postData,
            success(req) {
              let {status} = req;
              if (status && parseInt(status) === 1) {
                postSucceDialog(language['REWARD_SUCCESS'], function() {
                  window.location.reload();
                });
                myBox.hide();
              } else {
                // 重置表單 按鈕 條件
                coinOutBusyDone();
              }
            },
            error(err) {
              coinOutBusyDone();
              myBox.hide();
              if (err) {
                myAlert.show(err);
              }
            }
          });
        } else {
          coinOutBusyDone();
        }
      });
    });
  }
  //
  $("#coinOutEvent").click(function() {
    const coinDom = $("#coinList .sel-coin");
    // 獲取幣信息
    const coins = {
      name: "BTC"
    }
    coinOutBox(coins);
  });
  // 獲取表格head
  const headTpls = [...$("#tableTpls thead")];
  function resetTableHead() {
    const tarEle = $(`[data-table="${tableNum}"]`);
  }
  // 重置表格顯示
  function setTableStyle(tableNum, theadTpl) {
    // return;
    const tarEle = $(`[data-table="${tableNum}"]`);
    // copy thead
    if (tarEle.find(`[data-thead='${tableNum}']`).length === 0) {
      // 創建 head div 和 table
      const myTbHead = document.createElement('div');
      // myTbHead.id = "tHead";
      myTbHead.className = "t-head";
      myTbHead.setAttribute("data-thead", tableNum);
      myTbHead.innerHTML = "<table></table>";
      // 添加到 當前表格前面
      tarEle.eq(0).prepend(myTbHead);
      // 複制 表頭到 新建的 表格
      // tarEle.find(`[data-thead="${tableNum}"] table`).append(tarEle.find("[data-tbody] thead").clone());
      tarEle.find(`[data-thead="${tableNum}"] table`).html(`<thead>${theadTpl}</thead>`);
      // set style
      const tbodyTr = tarEle.find("[data-tbody] tbody tr").eq(0);
      const tbodyTds = [...tbodyTr.find("td")];
      const tHeadTds = tarEle.find("[data-thead] thead tr").eq(0).find("td");
      tbodyTds.forEach((td, key) => {
        const bodyTdWidth = $(td).css("width");
        tHeadTds.eq(key).css("width", bodyTdWidth);
      });
    } else {
      // 重置表頭 內容
      tarEle.find(`[data-thead='${tableNum}'] thead`).html(theadTpl);
      // 获取表格 tr
      const tbodyTr = tarEle.find("[data-tbody] tbody tr").eq(0);
      // 清空表格表头
      tarEle.find("[data-tbody]").remove('thead');
      // 获取数据表格 的 td 宽度
      const tbodyTds = [...tbodyTr.find("td")];
      // 获取显示表头 表格
      const tHeadTds = tarEle.find("[data-thead] thead tr").eq(0).find("td");
      // 设置表头 表格 样式 宽度
      tbodyTds.forEach((td, key) => {
        const bodyTdWidth = $(td).css("width");
        // 设置表头样式
        tHeadTds.eq(key).css("width", bodyTdWidth);
      });
    }
    // 滾動情況
    if (parseInt($("#tBody").css("height")) < parseInt($("#tBody table").css("height"))) {
      $("#tHead table td:last-child").css("paddingRight", "45px");
    }
  }
  // 刷新 表頭
  function updateTHead(viewNum) {
    const $table = $("#tableContent");

    // 設置表頭 head
    $table.children('thead').html(headTpls[viewNum - 1].innerHTML);
    //
    setTableStyle(1, headTpls[viewNum - 1].innerHTML);
  }
  //
  // setTableStyle(1);
  function getTableData(viewNum) {
    const $table = $("#tableContent");
    const $loading = $("#tableLoading");
    // 隱藏沒有數據顯示
    $table.siblings('div[data-id="tableNoData"]').hide();
    $loading.show();
    // 清空 表格 内容
    $table.find('tbody').html('');
    //
    http({
      url: '/ajax_user/invite',
      method: 'POST',
      data: {
        type: viewNum
      },
      success({status, data}) {
        $loading.hide();
        if (parseInt(status) === 1) {
          // test
          if (data && data.length > 0) {

            let dcFrame = document.createDocumentFragment();
            switch (viewNum) {
              case 1:
                // "phone": "137****3897",
                // "date": "2017-10-26 09:30:14",
                // "status": "已實名",
                // "reward": "10MCC"
                data.forEach((trData) => {
                  let tr = document.createElement('tr');
                  let tds = `<td>${trData.phone}</td>
                              <td>${trData.date}</td>
                              <td class="${trData.status.code == 1 ? 'green-font' : 'red-font'}">${trData.status.txt}</td>
                              <td>${trData.reward}</td>`;
                  tr.innerHTML = tds;
                  dcFrame.appendChild(tr);
                });

                break;
              case 2:
                // "id": "12",
                // "coin": "mcc",
                // "number": "10",
                // "uid": "13231173",
                // "type": "0",
                // "created": "2017-10-26 14:27:50",
                // "exchange_id": "18",
                // "be_invited": "0"
                data.forEach((trData) => {
                  let tr = document.createElement('tr');
                  let tds = `<td>${trData.id}</td>
                              <td>${trData.created}</td>
                              <td>${trData.number}</td>
                              <td>${trData.coin}</td>`;
                  tr.innerHTML = tds;
                  dcFrame.appendChild(tr);
                });
                break;
              case 3:
                data.forEach((trData) => {
                  let tr = document.createElement('tr');
                  let tds = `<td>${trData.created}</td>
                              <td>${trData.number+trData.coin}</td>`;
                  tr.innerHTML = tds;
                  dcFrame.appendChild(tr);
                });
                break;
              case 4:
                // "uid": "13231173",
                // "mo": "13751773897",
                // "invite_count": "1",
                // "coin_count": "10MCC",
                // "no": 1
                data.forEach((trData) => {
                  let tr = document.createElement('tr');
                  let tds = `<td>${trData.no}</td>
                              <td>${trData.mo}</td>
                              <td>${trData.invite_count}</td>
                              <td>${trData.reward}</td>
                              <td>${trData.coin_count}</td>`;
                  tr.innerHTML = tds;
                  dcFrame.appendChild(tr);
                });
                break;
              default:
            }
            $table.find('tbody').append(dcFrame);

          } else {
            $table.find('tbody').html('');
            $table.siblings('div[data-id="tableNoData"]').show();
          }

          // 設置表頭 head
          updateTHead(viewNum);
        }
      }
    });
  }
  // 初始化
  getTableData(1);
  // 表格 导航切换
  $("#inNav span").click(function() {
    let $actTab = $(this);
    $actTab.siblings('.at-in-nav').removeClass('at-in-nav');
    $actTab.addClass("at-in-nav");
    // 切换视图
    let viewNum = $actTab.data('view');
    if (viewNum < 5) {
      $('#inNavWin div[data-table]').show();
      $('#inNavWin div[data-rule]').hide();
      // 切換表頭
      updateTHead(viewNum);
      // 切換表格內容
      getTableData(viewNum);
    } else {
      $('#inNavWin div[data-table]').hide();
      $('#inNavWin div[data-rule]').show();
    }
  });
});
