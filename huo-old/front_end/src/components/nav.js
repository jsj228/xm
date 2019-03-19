/* eslint-disable*/
import "@/styles/common/navDefault.scss";
// import register from '@/components/register';
import http from "@/tools/http";
import is from '@/tools/is';
import ResetPwd from '@/components/resetPassword';
import cookie from '@/tools/cookie';
import chkhttpLang from '@/tools/chgHpLg';

export default function(lang) {
  chkhttpLang("#baseLang", http);
  let relgPwdTips = $("#baseLang").html();
  let phpLang;
  try {
    phpLang = JSON.parse(relgPwdTips)
    // 切換語言包
    ResetPwd.lang = phpLang.resetPwd;
  } catch (e) {
    alert("網絡錯誤，請刷新頁面。");
  }
  // 重置url到cookie
  cookie.setReUrl();
  //
  const reg = register(function(reg) {
    // 注册忘记密码功能函数
    const resetPwdFn = new ResetPwd(reg);
    resetPwdFn.pushPhoneNum();

  }, phpLang.register);

  // reg.loginFn();
  $("[data-account]").hover(function() {
    const tog = $(this).data('account');
    $(`#${tog}`).show();
  });
  $("[data-account]").mouseleave(function() {
    const tog = $(this).data('account');
    $(`#${tog}`).hide();
  });
  $("#languageBtn").hover(function() {
    $("#langList").show();
  });
  $("#languageBtn").mouseleave(function() {
    $("#langList").hide();
  });
  $("#langList").click(function() {
    alert("即將開放!");
  });
  // 有奖活动
  $("#activityCtrl").hover(function() {
    $("#activityList").show();
  });
  $("#activityCtrl").mouseleave(function() {
    $("#activityList").hide();
  });

  $("#registerDom").click(function () {
    reg.registerFn();
  });

  $("#loginDom").click(function () {
    reg.loginFn();
  });
  $("#link-login").click(function () {
    reg.loginFn();
  });

  $(".layer .close-layer").click(function () {
    reg.close();
  });

  return reg;
}
