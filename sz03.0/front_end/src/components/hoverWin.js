import '@/styles/common/hoverWin.scss';
import eNumToStr from '@/tools/eNumToStr';
import { toFixedNum } from 'filters/filters';

export default class HoverWin {
  constructor() {
    this.init();
    this.width = 170;
    this.height = 30;
    this.borderHeight = 7;
    this.offsetY = 0;
    this.offsetX = 0;
    this.loadingTips = '';
  }
  init() {
    const hoverWin = document.createElement('div');
    hoverWin.id = 'hoverWin';
    hoverWin.className = 'hover-win';
    hoverWin.innerHTML = '<b class="tri-ang tri-right tri-left tri-bottom"></b><div class="hover-content">CNY ≈ 123456789</div>';
    $("body").append(hoverWin);
    this.$elem = $("#hoverWin");
    this.$content = this.$elem.find('.hover-content');
    this.triAng = this.$elem.find('.tri-ang');
  }
  // 设置元素方向和属性
  setHover(elem, attr, direction, type) {
    const $target = $(elem);
    var msg = $target.attr('data-' + attr);
    const width = $target.css("width");
    const height = $target.css("height");
    // { width: parseFloat(width), height: parseFloat(height) }
    this.setDirection(direction, { width: parseFloat(width), height: parseFloat(height) });
    if (msg || msg === 0) {
      this.position($target.offset());
      this.showLeft(msg, type);
    }
  }
  position({ top, left }) {
    this.$elem.css({ top: `${top - this.offsetTop + this.offsetY}px`, left: `${left - this.offsetLeft + this.offsetX}px` });
  }
  // 框 样式
  css(obj) {
    this.$elem.css(obj);
  }
  // 内容样式
  contentCss(obj) {
    this.$content.css(obj);
  }
  setDirection(dir, { width, height }) {
    this.triAng.attr("class", "tri-ang");
    // 在上面 显示
    switch (dir) {
      case 'right': {
        this.offsetTop = this.borderHeight;
        this.offsetLeft = -(width + this.borderHeight);
        this.triAng.addClass('tri-right');
        break;
      }
      case 'left': {
        this.offsetTop = this.borderHeight;
        this.offsetLeft = this.width + this.borderHeight;
        this.triAng.addClass('tri-left');
        break;
      }
      case 'bottom': {
        this.offsetLeft = -(width / 2) + (this.width / 2);
        this.offsetTop = -(height + this.borderHeight);
        this.triAng.addClass('tri-bottom');
        break;
      }
      default:
      // console.log(height, this.borderHeight);
        this.offsetTop = height + (this.borderHeight * 2) + 10;
        this.offsetLeft = -(width / 2) + (this.width / 2);
        this.triAng.addClass('tri-top');
    }
  }
  //普通显示
  dealMsgNor(msg) {
    let html = msg ? msg : this.loadingTips;
    this.$content.html(html);
    this.$elem.show();
  }
  // 计算显示内容数字
  dealMsg(msg) {
    if (msg == '--') {
      msg = '0';
    }
    var preResult = toFixedNum(msg, 2);

    // parseFloat(msg).toFixed(2);
    var middLabel = "≈";
    var result = '';
    if (preResult == "0.0000") {
      result = "0.0001";
      middLabel = "<";
    } else {
      result = eNumToStr(preResult);
    }
    if (msg == '0') {
      result = 0;
      middLabel = "≈";
    }
    let html = (isNaN(result) || 0) ? this.loadingTips : `CNY ${middLabel} ${result}`;
    if (isNaN(result) || result === 0) {
      html = this.loadingTips;
    } else {
      html = `$ ${middLabel} ${result}`;
    }
    //  = (preResult == "0.0000") ?  : ;
    this.$content.html(html);
    this.$elem.show();
  }
  showMsg(msg) {
    this.$content.html(msg);
    this.$elem.show();
  }
  showLeft(msg, type) {
    if (type === 'string') {
      this.dealMsgNor(msg);
    } else {
      this.dealMsg(msg);
    }
  }
  showRight(msg) {
    this.dealMsg(msg);
  }
  showBottom(msg) {
    this.dealMsg(msg);
  }
  hide() {
    this.$elem.hide();
  }
}
