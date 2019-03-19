import './dialog.scss';
import is from '@/tools/is';

export default class dialogBox {
  constructor(text) {
    this.tpls = `<div class="module-win"><div class="box-win"><span class="box-close-btn" data-closed="close" id="boxCloseBtn"></span><div class="dialog-box-content">${text}</div></div></div>`;
    // console.log($("#dialogBox"));
    this.defaultIdIsExcit = $("#dialogBox").length === 0 ? 0 : 1;
    this.element = '';
    this.content = '';
    this.init();
    this.tryCallback = function() {};
    this.addClassName = '';
  }
  init() {
    const contain = document.createElement("div");
    contain.class="dialog-box-contain";
    // 如果存在元素 不在創建 新元素
    if (!this.defaultIdIsExcit) {
      contain.id = "dialogBox";
    } else {
      contain.setAttribute("data-dialogbox", 'dialogbox');
      contain.id = "dialogboxTwo";
    }
    contain.className = 'dialog-box-contain';
    contain.innerHTML = this.tpls;

    $('body').append(contain);

    if (!this.defaultIdIsExcit) {
      this.element = $("#dialogBox");
    } else {
      // this.element = $("div[data-dialogbox='dialogbox']");
      // dialogboxTwo
      this.element = $("#dialogboxTwo");
    }
    this.content = this.element.find(".dialog-box-content");
    //
    this.element.find('[data-closed="close"]').click(() => {
      // console.log(1212);
      this.close();
    });
  }
  show(tpl, callback) {
    if (is(tpl, 'String')) {
      // 设置
      this.content.html(tpl);
      this.element.show();
    }
    if (callback && is(callback, 'Function')) {
      callback();
    }
  }
  hide(callback) {
    this.element.hide();
    if (callback && is(callback, 'Function')) {
      callback();
    }
  }
  close(callback) {
    // console.log(callback);
    // 清除添加的class
    if (this.addClassName) {
      this.content.removeClass(this.addClassName);
    }
    this.element.hide();
    if (callback && is(callback, 'Function')) {
      callback();
    }
    if (is(this.tryCallback, 'Function')) {
      this.tryCallback();
    }
  }
  addClass(className) {
    this.addClassName = className;
    this.content.addClass(this.addClassName);
  }
  css(options) {
    if (is(options, 'Object')) {
      this.content.css(options);
    }
  }
  outsideCss(options) {
    if (is(options, 'Object')) {
      this.element.find('.box-win').css(options);
    }
  }
  clearContent() {
    this.content.html("");
  }
}
