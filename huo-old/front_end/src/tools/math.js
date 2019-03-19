/* eslint-disable */
import is from '@/tools/is';
export default function () {
  const math = {};
  /**
   * 修正浮點數相乘
   * @param {Number} num1
   * @param {Number} num2
   * @returns num1乘num2
   */
  function fixMulti(num1, num2) {
    if (num1 != undefined && num2 != undefined && !isNaN(num1) && !isNaN(num2)) {
      var m = 0,
        s1 = num1.toString(),
        s2 = num2.toString();
      try {
        m += s1.split(".")[1].length
        m += s2.split(".")[1].length
      } catch (e) {

      }
      return Number(s1.replace(".", "")) * Number(s2.replace(".", "")) / Math.pow(10, m);
    }
    return;
  }

  /**
  ** 除法函數，用來得到精確的除法結果
  ** 說明：javascript的除法結果會有誤差，在兩個浮點數相除的時候會比較明顯。這個函數返回較為精確的除法結果。
  ** 調用：accDiv(arg1,arg2)
  ** 返回值：arg1除以arg2的精確結果
  **/
  function accDiv(arg1, arg2) {
    var t1 = 0, t2 = 0, r1, r2;
    try { t1 = arg1.toString().split(".")[1].length;}
    catch (e) {}
    try { t2 = arg2.toString().split(".")[1].length; }
    catch (e) {}
      r1 = Number(arg1.toString().replace(".", ""));
      r2 = Number(arg2.toString().replace(".", ""));
    return (r1 / r2) * Math.pow(10, t2 - t1);
  }
  /**
   *
   *
   * @param {Number or String} str
   * @param {Number} num
   * @returns 截取小数点后num位的结果
   */
  function cutFixedNum(str, num){
    var cutLen = num || 8;   //默认小数点后8位
    var hundredArr = ['1'];
    for(var i=0;i<cutLen;i++){
      hundredArr.push('0');
    }
    var hundred = hundredArr.join('');
    if(!is(str, "String")){
      str = str + "";
    }
    var newStr = str;
    if(str.indexOf('.') > -1){
      str = str.replace(/([0-9]+\.[0-9]{8})[0-9]*/, '$1');
      newStr = parseFloat(str) === 0 ? '0' : str;
    }
    return newStr;
  }


  math.fixMulti = fixMulti;
  math.accDiv = accDiv;
  math.cutFixedNum = cutFixedNum;
  return math;
}
