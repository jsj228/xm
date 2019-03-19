function getLang(where, who) {
  try {
    let lang = JSON.parse($(where).html());
    who.__proto__.lang = lang;
  } catch (e) {
    alert("系統繁忙，請稍後重試。(The system is busy, please try again later.)")
  }
}
export default getLang;
