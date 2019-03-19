import Vue from 'vue';
import is from 'tools/is';
import { delUselessZero, numUseNoDot, toFixedNum, int } from './filters';

// 去除 交易场 名字
Vue.filter('beat-name', function(name) {
  return name.toUpperCase().replace(/(_.*)/, '');
});

Vue.filter('area-name', function(name) {
  return name.toUpperCase().replace(/^(.*_)/, '');
});

// 整数
Vue.filter('int', function(num) {
  return int(num);
});

// 去除数字多余 0
Vue.filter('del-zero', function(name) {
  return delUselessZero(name);
});

Vue.filter('del-floatzero', function(num) {
  if (num - 1 < 0) {
    return delUselessZero(num);
  } else {
    return num;
  }
});

// 获取 数字末尾 无效 0
Vue.filter('get-zero', function(name) {
  let zeros = name.match(/(0+)$/, '');
  let result = '';
  if (zeros) {
    result = zeros[0];
  }
  return result;
});

// 限制 10个字符
Vue.filter('numLen10', function(str) {
  let maxLength = 10;
  let result = '';
  let numStr = '';
  if (is(str, 'Number')) {
    // 转换成字符串
    numStr = str + '';
  } else {
    numStr = str;
  }
  // 是否存在有效数字
  let strNum = parseFloat(str);
  // 提取有效数字
  if (!isNaN(strNum)) {
    let useNum = numStr.match(/^([0-9]*(?:\.[0-9]{1,8})?)/g);
    if (useNum) {
      result = useNum[0] + '';
    }
  }
  // 不够 10位，补全 0
  let resLen = result.length;
  if (resLen < maxLength) {
    let overZero = '';
    // 是否有小数点
    if (result.indexOf('.') > -1) {
      overZero = '0000000000'.slice(0, maxLength - resLen);
    } else {
      overZero = '.00000000000'.slice(0, maxLength - resLen);
    }
    result += overZero;
  }

  return result.slice(0, maxLength);
});

// rmb 限制 2 两位小数
Vue.filter('numLen2', function (numStr) {
  return toFixedNum(numStr, 2);
});

// 保留8位小数
Vue.filter('numLen8', function (numStr) {
  return toFixedNum(numStr);
});

// 买卖 表格 数字格式化 整数部分
Vue.filter('numUse', function(numStr) {
  let result = '';
  if (!isNaN(parseFloat(numStr))) {
    // 获取小数部分
     result = numStr.replace(/(\.[0-9]+)$/, function(dec) {
      return dec.replace(/(0*)$/, '');
    });
  }
  return result;
});

// 买卖 表格 数字格式化 整数部分 没有点 结束
Vue.filter('numUseNoDot', function(numStr) {
  return numUseNoDot(numStr);
});

// 允许 -- 显示
Vue.filter('numUseAndAnd', function (numStr) {
  let result = '';
  if (!isNaN(parseFloat(numStr))) {
    if (!is(numStr, 'String')) {
      numStr += '';
    }
    // 获取小数部分
    result = numStr.replace(/(\.[0-9]+)$/, function(dec) {
      return dec.replace(/(0*)$/, '');
    });
    result = result.replace(/(\.)$/g, '');
  }
  // 允许 -- 显示
  if (numStr === '--') {
    result = numStr;
  }
  return result;
});

// 买卖 表格 数字格式化 有效 小数 部分
Vue.filter('numZero', function(numStr) {
  let dec = '';
  numStr.replace(/(0*)$/, function(zero) {
    dec = zero;
  });
  return dec;
});
