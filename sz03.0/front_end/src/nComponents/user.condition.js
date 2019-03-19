import Zh from "flatpickr/dist/l10n/zh.js";
import Flatpickr from "flatpickr";
import Alert from "@/tools/alert/alert";

const myAlert = new Alert("");

export default function (phpLang) {
  let nowLang = document.getElementById('LANG').innerHTML;
  if (nowLang === 'cn') {
    Flatpickr.localize(Zh.zh);
  }
  // 日歷
  const startTimtOptions = {
    enableTime: true,
    dateFormat: "Y-m-d H:i:S"
  };

  const startTime = new Flatpickr("#startTime", startTimtOptions);
  const endTimtOptions = {
    enableTime: true,
    // defaultDate: "today",
    // minDate: $("#startTime").val() || "",
    dateFormat: "Y-m-d H:i:S"
  };

  const endTime = new Flatpickr("#endTime", endTimtOptions);
  // 幣篩選條件
  $('#allCoinList span[data-coincode]').click(function() {
    $("#allCoinList .sel_coin").removeClass("sel_coin");
    const coinCode = $(this).data('coincode');
    $(this).addClass("sel_coin");
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
  $("#allCoin").click(function() {
    quTypePost(this);
  });
  // 當天
  $("#todayCoin").click(function() {
    quTypePost(this);
  });
  // 30天
  $("#monthCoin").click(function() {
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
  // 篩選
  $("#timeselect").click(function() {
    // 選擇時間區間，更換 快捷鍵狀態 -1 表示不在 全部，當天，30天 中任意壹個
    $("#coinPost input[name='days']").val('-1');
    let startTime = $('#startTime').val();
    let endTime = $('#endTime').val();
    // 有开始时间，没有结束时间
    // "START_TIME": "Please select start time.",
    // "END_TIME": "Please select end time.",
    // "START_GT_END": "Start time cannot be equal to or greater than end time!",
    if (!startTime) {
      myAlert.show(phpLang.START_TIME);
      return false;
    }
    if (!endTime) {
      myAlert.show(phpLang.END_TIME);
      return false;
    }
    if (startTime && endTime) {
      if (startTime >= endTime) {
        // '開始時間不能大于等于結束時間'
        myAlert.show(phpLang.TIME_OVER_TIPS);
        return false;
      }
    }
    $("#coinPost").submit();
  });

  // user trust 委託管理
  $("#coinFlag span[data-flag]").click(function() {
    const flag = $(this).data('flag');
    $("#coinPost input[name='flag']").val(flag);
    $("#coinPost").submit();
  });

  // 點擊導出excel
  $('#excel').click(function() {
    if (location.href.indexOf('coinin') > -1) {
      let coin = $("#allCoinList .sel_coin").html().trim().toLowerCase();
      let coinType = $('[name=coinType]').val();
      let type = $('[name=type]').val();
      let startTime = $('[name=startTimeex]').val();
      let endTime = $('[name=endTimeex]').val();
      location.href = '/user/coinRecordCsvOut?coin=' + coin + '&coinType=' + coinType + '&type=' + type + '&startTime=' + startTime + '&endTime=' + endTime;
    } else {
      $("#coinPost input[name='excel']").val(1);
      $("#coinPost").submit();
      $("#coinPost input[name='excel']").val(0);
    }
  });
 
}
