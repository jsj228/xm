import is from './is';
import math from 'mathjs';


export default function (num) {

  math.config({
    // Default type of number:
    number: 'BigNumber',
    // Number of significant digits for BigNumbers
    precision: 64
  });

  let numStr = null;
  let result = '';
  // 数字，转字符串
  if (is(num, 'Number')) {
    numStr = num + '';
  }
  // 字符串不用转
  if (is(num, 'String')) {
    numStr = num;
  }
  // 是科学记数法 包含 e 是小数部分
  if (/^([0-9]+(?:\.[0-9]+)?(e|E)(\+|\-)\d+)$/.test(numStr)) {
    // 科学计数法 指数部分
    let powNumPart = numStr.match(/(\d+)$/g);
    // console.log('powNumPart ' + powNumPart);
    // 指数
    if (powNumPart) {
      // 10 的指数 倍
      let powNum = Math.pow(10, powNumPart[0]);
      // 避免 舍入误差
      let bigNum = math.multiply(math.bignumber(numStr), powNum);
      // debuger;
      // 转换 字符串
      let bigNumStr = bigNum.valueOf();
      // 获取整数部分
      let intNum = bigNumStr.match(/^(\d)+/)[0];
      let intLeng = intNum.length;
      // 整数部分 小于 小数 指数部分 需要在前面 补 pownum - intLeng 个 0
      if (intLeng < powNum) {
        // 补全 的 0
        let zeroPart = '00000000000000000000'.slice(0, powNumPart - intLeng);
        //
        let noDotBigNum = (bigNum + '').replace('.', '');
        result = '0.' + zeroPart + noDotBigNum;
      }
      // 整数部分 大于指数长度 不用补 0 小数点 【左】 移动 指数 个 位置
      else {
        let dotIndex = bigNumStr.indexOf('.');
        // 删除原来的小数点
        bigNumStr = bigNumStr.replace('.', '');
        let bigNumInt = bigNumStr.substr(0, dotIndex - powNumPart);
        let bigNumFloat = bigNumStr.substr(dotIndex - powNumPart);
        result = bigNumInt + '.' + bigNumFloat;
      }
    }
  }
  // console.log(result);
  return result;
}
