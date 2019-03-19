export default function GetQueryString(name) {
  let reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
  // 获取url中"?"符后的字符串并正则匹配
  let query = window.location.search.substr(1).match(reg);
  let context = "";
  if (query != null) context = query[2];
  reg = null;
  query = null;
  return context == null || context == "" || context == "undefined" ? "" : context;
}
