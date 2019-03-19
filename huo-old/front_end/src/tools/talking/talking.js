// // import sock from 'tools/sock';
// // 未登录 返回 false ， 登陆返回 open, msg对象 未连接sockect
// const talkStart = function () {
//   const talkingUrl = $('#wsurl').val();
//
//   // const ws = new WebSocket(`${talkingUrl}`);
//
//   const token = $("#hahaha").text();
//
//   const phone = $('.getPhones').text();
//   // 未登录
//   if (!phone) {
//     // 退出登錄清空所以聊天數據
//     localStorage.all = [];
//     localStorage.mesNum = 0;
//     return false;
//   } else {
//     //进入聊天室聊天室未弹出时计算消息数
//     if (localStorage.mesNum) {
//       var mesNum = JSON.parse(localStorage.mesNum);
//     } else {
//       var mesNum = 0;
//       localStorage.mesNum = mesNum;
//     }
//
//     //聊天框弹出
//     $('.talking_down').click(function(event) {
//       mesNum = 0;
//       localStorage.mesNum = 0;
//       $('.talking_down span').text('0');
//       $('.talking_up').fadeIn();
//       $('.talking_down').fadeOut();
//       localStorage.status = 'block';
//       oldHtml();
//     });
//     //聊天框收起
//     $('.talking_sj').click(function(event) {
//       $('.talking_up').fadeOut();
//       $('.talking_down').fadeIn();
//       localStorage.status = 'none';
//     });
//     //点击退出
//     $('.talking_close').click(function(event) {
//       $('.login_btn').fadeIn();
//       $('.talking_inner').fadeOut();
//       $('.talking_text').fadeOut();
//       $('.talking_footer').fadeOut();
//       // 关闭聊天室
//       $('.talking_up').hide();
//       $('.talking_down').show();
//       //隐藏退出按钮
//       $('.talking_close').hide();
//       //清空数据
//       $("#talkList") .html('');
//       //全部信息
//       localStorage.all = [];
//       //保存退出標識
//       localStorage.out = 'true';
//     });
//     //点击登录
//     $('.login_btn').click(function(event) {
//       $('.login_btn').fadeOut();
//       $('.talking_inner').fadeIn();
//       $('.talking_text').fadeIn();
//       $('.talking_footer').fadeIn();
//       //显示退出按钮
//       $('.talking_close').show();
//       localStorage.all = [];
//       localStorage.out = 'false';
//     });
//     //发送
//     $('.sent_btn').click(function(event) {
//       let msg = $('.talking_text textarea').val();
//       let reg = /^[\u4E00-\u9FA5a-zA-Z0-9_,，.。|！!~·%^&?？;、(=)(（）)(+)\s：:@(*)(《》)($￥)]+$/u;
//       // let reg = /<\/?[^>]*>|<\/?[^>]*/;
//       if (reg.test(msg)) {
//         msg = msg.replace(/&gt|&lt/g,'');
//       } else if (msg.indexOf("http://")  > -1 || msg.indexOf(msg.indexOf("https://") > 0)) {
//         if (msg.indexOf("http://") > -1) {
//           msg = msg.split("http://").join("");
//         } else {
//           msg = msg.split("https://").join("");
//         }
//       }
//       else {
//         msg = msg.replace(/<|>|\//g,'');
//       }
//       if (msg.split(" ").join('') !== '') {
//         // ws.send(phone+','+msg);
//         $('.talking_text textarea').val('');
//         let down = true;
//         mesAjax({phone, msg, down});
//       }
//       else {
//         $('.talking_text textarea').val('');
//         return  false;
//       }
//     });
//     document.onkeydown = function(event) {
//       if (event.keyCode == 13) {
//         event.preventDefault();
//         $('.sent_btn').click();
//       }
//     }
//     //点击头像
//     $('body').on('click', '.icon_peo', function() {
//       let phone = $(this).next('.peo_mes').find('.peo_phone').text();
//       sentMes(phone);
//     })
//     //点击电话
//     $('body').on('click', '.peo_phone', function() {
//       let phone = $(this).text();
//       sentMes(phone);
//     })
//     //艾特对方手机号
//     function sentMes(phone) {
//       $('.talking_text textarea').val('@'+phone+"  ");
//     }
//
//     if (localStorage.all) {
//       var talking_mes = JSON.parse(localStorage.all);
//     } else {
//       var talking_mes = [];
//     }
//     //判断刷新页面之前聊天室为弹出还是收起
//     if (localStorage.status == 'block') {
//       $("#talkList") .text('');
//       if (localStorage.out && localStorage.out == 'true') {
//         //已判断为登出
//         $('.login_btn').show();
//         $('.talking_inner').hide();
//         $('.talking_text').hide();
//         $('.talking_footer').hide();
//       }
//       else {
//         if (localStorage.all) {
//           //填充历史数据
//           oldHtml();
//         }
//       }
//       $('.talking_up').show();
//       $('.talking_down').hide();
//     }
//     else {
//       //填入消息数
//       if (localStorage.mesNum) {
//         const mesNum = JSON.parse(localStorage.mesNum);
//         if (mesNum > 99) {
//           $(".talking_down span").text('99+');
//         } else {
//           $(".talking_down span").text(mesNum);
//         }
//
//       }
//     }
//
//     //重新加载内容
//     function oldHtml() {
//       if (localStorage.all) {
//         const historyData = JSON.parse(localStorage.all);
//         talking_mes = historyData;
//         let historyHtml = '';
//         historyData.forEach((el, item) => {
//           historyHtml += talkingHtmlFn(el);
//         });
//         $("#talkList") .html(historyHtml);
//         // setTimeout(() => {
//         //   const talking_height = $("#talkList") .height();
//         //   const talking_inner = $('.talking_inner').height();
//         //   $('.talking_inner').scrollTop(talking_height-talking_inner);
//         // });
//         heightGet().then((value) => {
//           const talking_height = $("#talkList") .height();
//           const talking_inner = $('.talking_inner').height();
//           $('.talking_inner').scrollTop(talking_height - talking_inner);
//         })
//       }
//     }
//     function heightGet() {
//       return new Promise(function(resolve, reject){
//         setTimeout(resolve)
//       });
//     }
//     // 滚动
//     function scrollnow(down) {
//       down ? down : (down = false);
//       let innerH = $('.talking_inner').height();
//       let talkingH = $("#talkList") .height();
//       if (down == true) {
//         $('.talking_inner').scrollTop(talkingH - innerH);
//       }
//     }
//
//     // ws.onopen = function () {
//     //     // console.log("连接成功");
//     //     ws.send(JSON.stringify({token:token, channel:'Thechatroom'}));
//     //     setTimeout(() => {ws.onopen()},15000);
//     // };
//
//     //
//     // function wsOpen(ws) {
//     //   ws.send(JSON.stringify({token:token, channel:'Thechatroom'}));
//     // }
//     //定時連接
//
//     //判断是否滚动
//     let canScroll = true;
//
//     // ws.onmessage =
//
//     function wsMsg(e) {
//         let innerH = $('.talking_inner').height();
//         let talkingH = $("#talkList").height();
//         //现在可以滚动的高度
//         let scrollHeight = talkingH - innerH;
//         //已滚动高度
//         let now_scorll = $('.talking_inner').scrollTop();
//         //
//         if (scrollHeight == now_scorll) {
//           canScroll = true;
//         } else {
//           canScroll = false;
//         }
//         //返回数据
//         let mesData =''
//         try {
//           mesData = JSON.parse(e.data).data;
//         } catch (e) {
//           mesData = '';
//         }
//         if (mesData) {
//           //聊天室是否弹出
//           const talking_display = $('.talking_up').css('display');
//           if (!localStorage.out || localStorage.out == 'false') {
//             let all_content = mesData.split(':');
//             const userName = mesData.split(":")[0];
//             let content = '';
//             console.log(mesData.split(":"));
//             //傳送的字符串裡面含有：的時候 長度就大於2
//             if (all_content.length > 2) {
//               for (let i = 0; i < all_content.length; i++) {
//                 if (i >= 1 && i <all_content.length - 1) {
//                   content += all_content[i] + ':'
//                 } else if (i == all_content.length - 1) {
//                   content += all_content[i]
//                 }
//               }
//             } else {
//               content = all_content[1];
//             }
//             const now_mes = {userName: userName, content: content};
//             const talkingHtml = talkingHtmlFn(now_mes);
//             $("#talkList").append(talkingHtml);
//             //储存对话消息
//             //99条之后显示
//             let moreNes = '99+';
//             talking_mes.push(now_mes);
//             localStorage.all = JSON.stringify(talking_mes);
//             //talking_display 聊天室弹窗状态display none
//             if (talking_display == "none" || talking_display == undefined) {
//               mesNum ++;
//               if (mesNum > 99) {
//                 $(".talking_down span").text(moreNes);
//               } else {
//                 $(".talking_down span").text(mesNum);
//               }
//               localStorage.mesNum = JSON.stringify(mesNum);
//             }
//             else {
//               mesNum = 0;
//               localStorage.mesNum = mesNum;
//               //进行滚动
//               scrollnow(canScroll);
//             }
//           }
//           localStorage.status = talking_display;
//         }
//         else {
//           return false;
//         }
//     };
//
//     window.REGX_HTML_ENCODE = /"|&|'|<|>|[\x00-\x20]|[\x7F-\xFF]|[\u0100-\u2700]/g;
//     // 转义 html
//     window.encodeHtml = function(s){
//       console.log(window.REGX_HTML_ENCODE);
//       return (typeof s != "string") ? s :
//         s.replace(window.REGX_HTML_ENCODE, function($0){
//             var c = $0.charCodeAt(0), r = ["&#"];
//             c = (c == 0x20) ? 0xA0 : c;
//             r.push(c); r.push(";");
//             return r.join("");
//         });
//     };
//     //后台保存消息
//     function mesAjax(now_mes) {
//       let msg = encodeHtml(now_mes.msg);
//       $.ajax({
//         url: 'Ajax_Push/push',
//         type: 'POST',
//         data: {
//           mo: now_mes.phone,
//           themessage: msg
//         },
//         success(res) {
//           scrollnow(true);
//         },
//         error(err) {
//
//         }
//       });
//     }
//     //组成聊天内容
//     function talkingHtmlFn(mes){
//       return `<li class="clearFix">
//         <div class="icon_peo">
//           <img src="/imgs/people_default.png" alt="">
//         </div>
//         <div class="peo_mes">
//           <span class="peo_phone">${mes.userName.slice(0,3)}****${mes.userName.slice(7,11)}</span>
//           <p>${mes.content}</p>
//         </div>
//       </li>`
//       return li;
//     }
//   }
//
//   // const ws =
//   return {
//     open(ws) {
//       ws.send(JSON.stringify({token:token, channel:'Thechatroom'}));
//     },
//     msg(e) {
//       wsMsg(e);
//     }
//   }
//   // new WebSocket(`${talkingUrl}`);
// }
//
// export default talkStart;
export default () => {}
