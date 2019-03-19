import './alert.css';

export default class TIPS {
  constructor(settings) {
    this.id = settings && settings.id
      ? settings.id
      : 'tips_win';
    //
    this.content = '提示内容';
    //
    this.elm = $("#tips_win")[0];

    this.init(settings || "");
    this.clickCallback = '';
  }
  init() {
    // 禁止浏览器滚动
    this.stopBodyScroll();
    //
    this.sureBtn = this.elm.getElementsByClassName('btn')[0];
    //
    this.sureBtn.onclick = () => this.sureBtnClick();
    //
    this.closeCur = this.elm.getElementsByClassName('close_cur')[0];
    this.closeCur.onclick = () => this.closed();
    this.closeCallback = null;
    //
    // this.clickCallback = null;
  }
  setHeader(headerContent) {
    // return;
    // const header = this.elm.getElementsByClassName('tips_header')[0];
    // header.innerHTML = headerContent;
  }
  //zj 设定标题
  setTitle(title) {
    const titles = this.elm.getElementsByClassName('alert_title')[0];
    if (title) {
      titles.innerHTML = title;
    }
    else {
      titles.innerHTML = '';
    }
  }
  setContent(chgMsg) {
    const content = this.elm.getElementsByClassName('tips_msg')[0];
    content.innerHTML = chgMsg;
  }
  //设定按钮宽
  setBtnWidth(btnWidth) {
    if (btnWidth) {
      this.sureBtn.style.width = btnWidth;
    }
  }
  show(msg, callback, clickCallback) {
    this.setContent(msg);
    this.elm.style.display = 'block';
    if (callback && typeof callback === "function") {
      this.closeCallback = callback;
    }
    //
    // if (clickCallback && typeof clickCallback === "function") {
    //   this.clickCallback = clickCallback;
    // }
  }

  btnContent(btnHtml) {
    $(this.elm).find('span[data-btnsu]').html(btnHtml);
  }
  setClickBack(callback) {
    this.clickCallback = callback;
  }
  //关闭后的回调函数 调用回调部分需要在回调函数里手动
  sureBtnClick() {
    if (this.clickCallback) {
      this.clickCallback();
    }
    else {
      this.closed();
    }
  }
  closed(callback) {
    this.elm.style.display = 'none';
    if (callback && typeof callback === "function") {
      callback();
    } else if (this.closeCallback && typeof this.closeCallback === 'function') {
      this.closeCallback();
    }
  }
  stopBodyScroll() {
    /* eslint-disable */
    this.elm.onmousewheel = function scrollWheel(e) {
      e = e || window.event;
      if (navigator.userAgent.toLowerCase().indexOf('msie') >= 0) {
        event.returnValue = false;
      } else {
        e.preventDefault();
      };
    };
    if (navigator.userAgent.toLowerCase().indexOf('firefox') >= 0) {
      //firefox支持onmousewheel
      addEventListener('DOMMouseScroll',
        function(e) {
          var obj = e.target;
          var onmousewheel;
          while (obj) {
            onmousewheel = obj.getAttribute('onmousewheel') || obj.onmousewheel;
            if (onmousewheel) break;
            if (obj.tagName == 'BODY') break;
            obj = obj.parentNode;
          };
          if (onmousewheel) {
            if (e.preventDefault) e.preventDefault();
            e.returnValue = false; //禁止页面滚动
            if (typeof obj.onmousewheel != 'function') {
              //将onmousewheel转换成function
              eval('window.onTmpFun = function(event){' + onmousewheel + '}');
              obj.onmousewheel = window.onTmpFun;
              window.onTmpFun = null;
            }
            // 不直接执行是因为若onmousewheel(e)运行时间较长的话，会导致锁定滚动失效，使用setTimeout可避免
            setTimeout(function() {
              obj.onmousewheel(e);
            }, 0);
          }
        }, false);
    }
  }
}
