import "@/styles/userCenter/leftNav.scss";
import HoverWin from '@/components/hoverWin';

export default function() {
  // user || user/index 默认 ; realinfo 实名; coinin 充币; coinout 提币; deal 成交查询; trust 委托;
  let href = '';
  if (window.location.href.indexOf("?") > -1) {
    const parmas = window.location.href.indexOf("?");
    href = window.location.href.slice(0, parmas);
  } else {
    href = window.location.href;
  }
  let url = href.slice(href.lastIndexOf('/') + 1);
  // console.log($(`#userNav a[data-page="${url}"]`));
  if (url === 'index') {
    url = 'user';
  }
  $("#goPage input[name='p']").on('input', function() {
    let val = $(this).val();
    let reg = /^([0-9]*)$/;
    if (!reg.test(val)) {
      val = parseInt(val);
    }
    if (val) {
      $(this).val(val);
    } else {
      $(this).val('');
    }
  });
  // $(`#userNav a[data-page="${url}"]`).addClass('u-active');
  function jumpTo() {
    let url = $("#pageNumList ul li").last().find("a").eq(0).attr('href');
    let goPage = $("#goPage input[name='p']").val();
    // 获取最后一页 页码
    url = url.replace(/\/[0-9]*\?/, function(key) {
      let keyMatch = key.match(/[0-9]+/);
      let lastNum = 1;
      if (keyMatch) {
        lastNum = keyMatch[0];
      }
      let where = '';
      if (goPage - lastNum > 0) {
        where = lastNum;
      } else {
        where = goPage;
      }
      return `/${where}?`;
      // return key;
    });
    window.location.href = url;
  }
  $("#go").click(function() {
    jumpTo();
  });

  // 回车提交
  $("#goPage input[name='p']").on('keydown', function(e) {
    let keyCode = e.keyCode;
    if (keyCode === 13) {
      jumpTo();
    }
  });
}
