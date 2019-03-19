import is from 'tools/is';

// 删除 末尾 多余的 0
export function delUselessZero(str) {
  let result = '';
  if (is(str, 'String')) {
    result = str.replace(/(0+)$/, '');
  }
  return result;
}

export function int(strOld) {
  let result = '';
  let str = '';

  if (!is(strOld, 'String')) {
    str = strOld + '';
  } else {
    str = strOld;
  }

  if (str.indexOf('.') > -1) {
    result = str.replace(/(\.\d+)$/, '');
  } else {
    result = str;
  }

  return result;
}

// 删除 末尾 多余的 0 不保留 多余 小数点
export function numUseNoDot(num) {
  let result = '';
  if (!isNaN(parseFloat(num))) {
    // 转换为字符串
    let numStr = num + '';
    // 获取小数部分
    result = numStr.replace(/(\.[0-9]+)$/, function(dec) {
      return dec.replace(/(0*)$/, '');
    });
    // 末尾的 点 去掉
    result = result.replace(/(\.)$/g, '');
  }
  return result;
}

// 保留 指定小数位
export function toFixedNum(num, maxLen = 8) {
  let numStr = '';
  let result = '';
  if (!is(num, 'String')) {
    numStr = num + '';
  } else {
    numStr = num;
  }
  let reg = new RegExp(`^(([0-9]*)(?:\\.[0-9]{1,${maxLen}})?)`, 'g');
  // 截取有效部分数字
  let usefullPart = numStr.match(reg);

  if (usefullPart) {
    result = usefullPart[0];
  }
  return result;
}
