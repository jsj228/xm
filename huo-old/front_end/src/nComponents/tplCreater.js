export default (id) => {
  let templateElm = document.getElementById(id);
  let tplHTML = '';
  if (templateElm) {
    tplHTML = templateElm.innerHTML;
    templateElm.innerHTML = '';
  }
  return tplHTML;
};
