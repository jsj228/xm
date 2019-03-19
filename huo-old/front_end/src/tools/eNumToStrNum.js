export default function(num) {
  if (num) {
    let str = num + '';
    let reg = /^(\d+)(e)([\-]?\d+)$/;
    let arr, len, zero = '';
    /*6e7或6e+7 都会自动转换数值*/
    if (!reg.test(str)) {
      return num;
    } else {
      /*6e-7 需要手动转换*/
      arr = reg.exec(str);
      len = Math.abs(arr[3]) - 1;
      for (let i = 0; i < len; i++) {
        zero += '0';
      }
      return '0.' + zero + arr[1];
    }
  }
  return '';
}
