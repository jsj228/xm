import '@/styles/news/newsDefault.scss';
// import nav from '@/components/nav';
import nav from 'nComponents/nav';
// import navVue from ''
import register from 'nComponents/register';

$(document).ready(function() {
  const navVue = nav();
  //
  const pageReg = /^(([1-9]([0-9]+)?)+)$/;

  $("#pageForm input").on('input', function() {
    const $inp = $(this);
    let val = $inp.val();
    if (val && !pageReg.test(val)) {
      // 获取 输入的 整数部分
      let numPart = parseInt(val);
      if (!isNaN(numPart)) {
        $inp.val(numPart);
      } else {
        $inp.val('');
      }
    }
  });
  // 提交
  $("#pageForm").on("submit", function() {
    let val = $(this).find('input').val();
    let result = false;
    if (val && pageReg.test(val)) {
      result = true;
    } else {
      result = false;
    }
    // console.log(1212, result);
    return result;
  });
  
  $("#go").click(function () {
    let val= $("input[name='p']").val();
    let cate = document.getElementsByName("cate")[0].value;
    console.log($(".page-list ul li:last-child"));
    let li = $(".page-list ul li:last-child a").attr('href');
    let lastPage = '';
    li.replace(/([0-9]+)$/, (key) => {
      //
      console.log(key);
      lastPage = key;
    });

    //
    if (val && pageReg.test(val)) {
      if (val - lastPage > 0) {
        val = lastPage;
      }

      location.href='/news/category/'+cate+'/page/'+ val;
    } else {
      $("input[name='p']").val("1");
    }
  });
  // if (document.getElementById("go")) {
  //   var btn = document.getElementById("go").onclick = function () {
  //     var val= document.getElementsByName("p")[0].value;
  //     var cate = document.getElementsByName("cate")[0].value;
  //     let result = false;
  //
  //     if (val && pageReg.test(val)) {
  //       result = true;
  //       location.href='/news/category/'+cate+'/page/'+ val;
  //     } else {
  //       result = false;
  //     }
  //
  //    }
  // }
});
