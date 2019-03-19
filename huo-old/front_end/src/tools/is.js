export default function is(obj, target) {
  var type = Object.prototype.toString.call(obj).slice(8, -1);
  if (!target) return type;
  if (!Object.prototype.toString.call(obj).slice(8, -1) === "String") {
    throw new Error('目标必须为一个javascript 首字母大写的类型字符串');
  }
  return target === type ? 1 : 0;
}
