import "@/styles/news/detail.scss";
import nav from 'nComponents/nav';
import register from 'nComponents/register';
import http from '@/tools/http';
import is from  '@/tools/is';
import cookie from '@/tools/cookie';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import Alert from "@/tools/alert/alert";

// process.env.NODE_ENV
$(document).ready(function() {
  chkhttpLang("#baseLang", http);

  let language = getLanguagePack() || {
    just: "剛剛",
    yesterday: "昨天",
    the_day_before_yesterday: "前天",
    min: "分鐘",
    hour: "小時前"
  };
  let myalert = new Alert();
  // let t_lang = $("#detailLang").html();
  // console.log(t_lang);
  //获取语言包
  // const lan = getLanguagePack();
  // console.log(lan);
  // console.log(lan);
  // if (.length > 0) { //language.json中tradeJs為空時輸出數組，不為空時輸出的是對象
  //   language = lan.detailJs;
  // }

  // let locUrl = location.href.replace(/http(s)?:\/\/[a-zA-Z.]*(:\d+)?/, '');
  cookie.setReUrl();
  const navVue = nav();
  //
  const registerFn = register;

  // 評論輸入 顯示字數
  $('#newsIsset').on('input', function() {
    let val = $(this).val();
    $("#issLength").html(val.length);
  });
  //
  // $('#newsIsset').on('keydown', function() {
  //   console.log($(this).val());
  // });
  // 獲取路由參數
  // function GetQueryString(name) {
  //   const reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
  //   const r = window.location.search.substr(1).match(reg);
  //   if (r != null) return unescape(r[2]);
  //   return null;
  // }
  // let href = window.location.href;
  // let startD = href.lastIndexOf("/detail/");
  // let lenId = href.slice(startD + 2);
  // let comId = '';
  // if (lenId.indexOf(".html") > -1) {
  //   comId = lenId.replace(".html", "");
  // } else {
  //   comId = lenId;
  // }
  // 獲取評論 id
  const comId = $("#detailid").attr("data-dds");
  // const comId = href.slice(startD + 2).replace(".html", "");
  // GetQueryString('id');

  // 是否登陆
  function userLogin() {
    http({
      url: '/ajax_user/getUserInfo',
      method: 'GET',
      success({ status,data }) {
        if (parseInt(status) === 1 && data) {
          // 登陆
          $('#issNLG').hide();
          // 评论 issDom
          $('#issDom').css({opacity: 1 }).show();
        } else {
          $('#issNLG').show();
          $('#issDom').hide();
        }
      }
    });
  }
  //
  userLogin();
  // 获取评论内容
  function getIss() {
    http({
      url: '/ajax_news/commentList?id=' + comId,
      method: "GET",
      success({ status, data }) {
        // 更新評論列表
        if (is(data, 'Array') && data.length > 0) {
          $("#noIssData").hide();
          let issListStr = '';
          // 缓存当前时间
          let nowTimeObj = new Date();
          function monents(time) {
            let timeObj = new Date(time * 1000);
            let minute = timeObj.getMinutes();
            if (minute - 9 <= 0) {
              minute = '0' + minute;
            }
            return {
              year: timeObj.getFullYear(),
              month: timeObj.getMonth() < 9 ? ('0' + (timeObj.getMonth() + 1)) : (timeObj.getMonth() + 1),
              date: timeObj.getDate(),
              hh: timeObj.getHours() + ":" + minute
            }
          }
          // 评论显示时间
          function timeFilter(time) {
            if (!time) return '';
            let nowTime = nowTimeObj.getTime();
            let InputTime = parseInt(time);
            let diffTime = parseInt(nowTime / 1000) - InputTime;
            let result = '';
            // 一分鐘內 0 ~ 60s  out: 剛剛
            if (diffTime <= 60) {
              result = language['just']; //剛剛
            }
            // 一小時內  ((60s +1) ~ (60 * 60S)  out： xx分鐘前
            if (diffTime > 60 && diffTime <= 60 * 60) {
              result = `${parseInt(diffTime / 60) + language['min']}`;
            }
            // 一天內  ((60 * 60S + 1) ~ ((60 * 60S) * 24)  out: xx小時前
            if (diffTime > 60 * 60 && diffTime <= (60 * 60) * 24) {
              result = `${parseInt(diffTime / 60 / 60) + language['hour']}`;
            }
            // 24~ 48h
            if (diffTime > (60 * 60) * 24 && diffTime <= 60 * 60 * 48) {
              result = `${language['yesterday'] + monents(time).hh}`; //昨天
            }
            // 48~72h
            if (diffTime > (60 * 60) * 48 && diffTime <= 60 * 60 * 72) {
              result = `${language['the_day_before_yesterday'] +  monents(time).hh}`; //前天
            }
            // 72h 后
            if (diffTime > 60 * 60 * 72) {
              let monentTime = monents(time);
              // 今年
              if (monentTime.year == nowTimeObj.getFullYear()) {
                result = `${monentTime.month}-${monentTime.month} ${monentTime.hh}`;
              } else {
                // 不在當年
                result = `${monentTime.year}-${monentTime.month}-${monentTime.month} ${monentTime.hh}`;
              }
            }

            return result;
          }
          // content : "asdkjfkldsjakfa"
          // created : "1508401262"
          // mo : "15602471482"
          // nid : "254"
          data.reverse().forEach((iss) => {
            let users = '';
            if (iss.mo) {
              users = iss.mo.slice(0,3) + '****' + iss.mo.slice(-4);
            } else {
              users = iss.email;
            }
            issListStr += `<li>
              <div class="iss-li-top">
                <span>${users}</span><span>${timeFilter(iss.created)}</span>
              </div>
              <div class="iss-describe">${iss.content}</div>
            </li>`;
          });
          $("#issListShow").html(issListStr);
        } else if (Array.isArray(data) && data.length === 0) {
          $("#noIssData").show();
        }
      }
    });
  }

  window.REGX_HTML_ENCODE = /"|&|'|<|>|[\x00-\x20]|[\x7F-\xFF]|[\u0100-\u2700]/g;
  // 转义 html
  window.encodeHtml = function(s) {
    return (typeof s != "string") ? s :
      s.replace(this.REGX_HTML_ENCODE, function($0){
          var c = $0.charCodeAt(0), r = ["&#"];
          c = (c == 0x20) ? 0xA0 : c;
          r.push(c); r.push(";");
          return r.join("");
      });
  };
  // 發表評論
  $("#seedIss").click(function() {
    let $textArea = $('#newsIsset');
    let reqToken =$('#hahaha').text();
    if ($textArea.length > 0) {
      let contentText = window.encodeHtml($textArea.val());
      http({
        url: '/ajax_news/newsComment',
        method: 'POST',
        data: {
          nid: comId,
          content: contentText,
          reqToken: reqToken
        },
        success({ status, data, msg }) {
          if (parseInt(status) === 1) {
            $textArea.val('');
            $("#issLength").text('0');
            getIss();
          }
          else {
            if (msg.content) {
              myalert.show(msg.content);
          }
            else {
              myalert.show(msg);
            }
            if (data.need_login == 1) {
              $('[data-btnsu="sureBtn"]').click(function(event) {
                myalert.closed(function() {
                  registerFn.loginAlert();
                })
              });
            }
          }
        }
      }, 'noAlert');
    }
  });
  $('#loginBtn').click(function() {

    registerFn.$watch("isLogin", function() {
      $('#issNLG').hide();
      // 评论 issDom
      $('#issDom').css({opacity: 1 }).show();
    });

    registerFn.loginAlert();

  });

  //新聞頁返回列表按鈕，點擊后返回上一次瀏覽的tab
  $('#backToNewsList').on('click', function() {
    // if (/news\/detail/.test(window.location.href)) { //跳回到上一頁帶參數的url
    //   history.go(-1);
    // } else {
    //   location.href = '/news?cate=3'; //默認到幫助tab
    // }
    //獲取tab類型
    let url = $('.act-ti').attr('href');
    location.href = url;
  });

  getIss();
});
