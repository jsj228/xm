import is from './is';

// 科学计算法，转字符串
export default function eToNum(eNum) {
  let result = eNum + '';
  // 是否是一个数字类型的变量
  try {
    eNum = Number(eNum);
  } catch (error) {
    
  }
  if (is(eNum, 'Number')) {
    let eNumStr = eNum + '';
    let numLength;
    if (eNumStr.indexOf('e') > -1) {
      numLength = eNumStr.slice(-1);
      result = eNum.toFixed(numLength);
    }
  }
  return result;
}
