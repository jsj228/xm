import is from './is';

class Dialog {
  constructor() {
    this.elemClass = '.dialog_mo';
    this.init();
    this.bgLock = false;
    this.addClassName = '';
    this.closeCallBack = "";
  }
  init() {
    // 防止多次初始化，生成多过重复元素
    if (document.getElementById('dialogBox')) {
      return;
    }
    const styles = document.createElement('style');
    styles.type = 'text/css';
    //
    if (styles.styleSheet) {
      styles.styleSheet.cssText = '.dialog_mo { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 9999; background: rgba(0, 0, 0, .5); display: none; } .dialog_mo .dialog_content { position: relative;margin: 210px auto 0; background: #fff; }';
    } else {
      styles.innerHTML = '.dialog_mo { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 9999; background: rgba(0, 0, 0, .5); display: none; }.dialog_mo .dialog_content { margin: 210px auto 0; padding: 10px 0; background: #fff; }';
    }

    const contain = document.createElement('div');
    contain.id = "dialogBox";
    contain.className = 'dialog_mo mob_dialog';
    contain.innerHTML = '<div class="content dialog_content"></div>';

    $('head').append(styles);
    $('body').append(contain);
    this.elem = $(this.elemClass);
    this.content = this.elem.find('.dialog_content');
    this.elem.click((e) => {
      if (e.target.className === 'dialog_mo' && !this.bgLock) {
        $(this).hide();
        this.bgLock = false;
      }
    });
  }
  show(lockBG) {
    this.elem.show();
    if (lockBG) {
      this.bgLock = true;
    }
  }
  hide(unLockBG) {
    // 清空添加的 class
    if (this.addClassName) {
      this.elem.find('.dialog_content').removeClass(this.addClassName);
    }
    if (this.closeCallBack && typeof this.closeCallBack === "function") {
      this.closeCallBack();
    }
    this.elem.hide();
    if (unLockBG) {
      this.bgLock = false;
    }
  }
  html(tpls) {
    this.elem.find('.dialog_content').html(tpls);
  }
  addClass(className) {
    this.addClassName = className;
    this.elem.find('.dialog_content').addClass(this.addClassName);
  }
  css(styles) {
    if (is(styles, 'Object')) {
      this.elem.find('.dialog_content').css(styles);
    } else {
      throw new Error('Dialog css 方法接受的参数必须是一个对象 object');
    }
  }
}

export default new Dialog();
