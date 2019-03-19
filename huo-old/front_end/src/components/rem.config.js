/*
 * 用于重置html fontsize 计算rem基数
 * 项目开始调用即可
 */
const config = () => {
  const docEl = document.documentElement;
  const fontEl = document.createElement('style');
  console.log(fontEl);
  const metaEl = document.querySelector('meta[name="viewport"]');
  const isAndroid = window.navigator.appVersion.match(/android/gi);
  const isIPhone = window.navigator.appVersion.match(/(iphone|ipad)/gi);
  let dpr = null;
  if (isAndroid || isIPhone) {
    dpr = window.devicePixelRatio;
  } else {
    dpr = 1;
  }
  const scale = 1 / dpr;
  let rem = docEl.clientWidth * (dpr / 10);
  rem = docEl.clientWidth * (dpr / 10);

  // 设置viewport，进行缩放，达到高清效果
  metaEl.setAttribute('content', `width=device-width, initial-scale=${scale}, maximum-scale=${scale}, minimum-scale=${scale}, user-scalable=no`);
  docEl.setAttribute('data-dpr', dpr);
  docEl.setAttribute('data-rem', rem);
  // 重置
  fontEl.innerHTML = `html{font-size: ${rem}px!important}`;
  docEl.firstElementChild.appendChild(fontEl);

  //
  window.onresize = function() {
    let rem = docEl.clientWidth / 10;
    fontEl.innerHTML = `html{font-size: ${rem}px!important}`;
    docEl.setAttribute('data-rem', rem);
  }
};

//
export default config;
