/**
 * 解析JSON语言包
 */
export default function getLanguagePack(wrapper) {
  let wrapperNew = wrapper || document.getElementById('errTips');
  const strPlaceholder = wrapperNew.innerHTML;
  // console.log(strPlaceholder);
  var strObj = null;

  try {
    strObj = JSON.parse(strPlaceholder);
    return strObj;
  } catch (error) {
    console.log(error);
  }
  return strObj;
}
