import 'nStyle/index.mcc.scss';

import register from 'nComponents/register';
import nav from 'nComponents/nav';

$(document).ready(function() {
  nav();
  $("#toTop").click(function() {
    let sTop = document.body || document.documentElement;
    let clearTime = setInterval(() => {
      if (sTop.scrollTop > 0) {
        sTop.scrollTop -= 30;
      } else {
        clearInterval(clearTime);
      }
    }, 1);
  })
});
