import is from 'tools/is.js';
import clone from 'clone';
import TIPS from './alert/alert';
import Promise from 'Promise';
import { JSEncrypt } from 'jsencrypt/bin/jsencrypt';

const httpAlert = new TIPS();
let encrypt = new JSEncrypt();
let publiceKey = null;
let task = [];

function getPublickey() {
  if (window.pubk) return;
  return new Promise((resolve, reject) => {
    $.ajax({
      url: '/ajax_user/getCommonRsaKey',
      method: "GET",
      success: ({ status, data }) => {
        let intStatus = parseInt(status);
        if (intStatus === 1) {
          encrypt.setPublicKey(data);
          publiceKey = data;
          window.pubk = data;
          if (task.length > 0) {
            Promise.all(task).then(() => {
              task = null;
            });
          }
          resolve();
        } else {
          reject();
        }
      },
      error(err) {
        if (err) reject();
      }
    });
  });
}
// 加密数据
function rsaData(oldData) {
  let data = Object.assign({}, oldData);
  try {
    let pukdata = '';
    let jsonStri = JSON.stringify(data);
    let encode = false;
    if (/.*[\u4e00-\u9fa5]+.*$/.test(jsonStri)) {
      encode = true;
      jsonStri = encodeURIComponent(jsonStri);
    }
    let jsonLength = jsonStri.length;
    // let keyLength = this.publicKey.length;
    let asslength = 117;
    // 超出 public key 长度 - 11 分段加密
    if (jsonLength > asslength) {
      pukdata = [];
      // 分段长度
      let partLength = asslength;
      // 分段数量
      let strLeng = Math.ceil(jsonLength / asslength);
      //
      for (let i = 0; i < strLeng; i++) {
        let start = i * partLength;
        let end = (i + 1) * partLength;
        let part = jsonStri.slice(start, end);
        pukdata.push(encrypt.encrypt(part));
      }
    } else {
      pukdata = encrypt.encrypt(jsonStri);
    }

    data = {
      data: pukdata
    };
    if (encode) {
      data.ud = encode;
    }
  } catch (err) {
    /* eslint-disable */
    console.log(err);
    /* eslint-enable */
  }
  return data;
}

getPublickey();

function http(settings, noAlert) {
  // getLang("#baseLang");
  let lang = {
    sysError: '系統錯誤，請聯系客服協助處理。',
    timeout: '請求超時,請稍後重試',
    reqError: '請求超時,請稍後重試'
  };
  // 切换语言
  // httpAlert.show('lang.reqError');

  if (http.lang) {
    lang = http.lang;
  }
  // httpAlert.setHeader("提示");
  // 弹窗 公共 方法
  const defaultSettings = {
    timeout: 60000
  };

  let errCallBack = '';
  // 获取全部请求配置
  if (is(settings, 'Object')) {
    Object.keys(settings).forEach((key) => {
      switch (key) {
        // 封装 成功 回调函数
        case 'success': {
          const callback = clone(settings[key]);
          defaultSettings.success = function(req, status, config) {
            //
            // console.log(config);
            // if (!is(req, 'Object') || parseInt(req.status) === 0) {
            //   let errResult = {};
            //   // errResult.reUrl = config.url;
            //   // errResult.reponse = clone(data);
            //   // errResult.param = clone(config.data) || '';
            //   // $.post('/ajax_common/reqFailedLog', errResult);
            // }
            // 请求失败 0
            if (parseInt(req.status) === 0) {
              const { data } = req;
              // alert(req.msg);
              // 判断是否有重定向url
              if (is(data, 'Object') && parseInt(data.need_login) === 1) {
                // 后台没给重定向 url 时
                if (!data.reUrl) {
                  httpAlert.show(req.msg, function() {
                    window.location.href = '/?login';
                  });
                } else {
                  httpAlert.show(req.msg, function() {
                    window.location.href = data.reUrl;
                  });
                }
              } else if (req.msg && !noAlert) {
                if (is(req.msg, 'Object')) {
                  httpAlert.show(req.msg.content);
                } else {
                  httpAlert.show(req.msg);
                }
              }
            }
            // 参数错误
            else if (parseInt(req.status) === 2) {
              // 系统错误
              httpAlert.show(lang.sysError);
            }
            //
            if (is(callback, 'Function')) {
              // 回调 成功callback
              callback(req);
            }
          };
          break;
        }
        case 'url': {
          defaultSettings[key] = settings[key] || window.location.href.host + "" + settings[key];
          break;
        }
        case 'error': {
          if (settings[key]) {
            errCallBack = clone(settings[key]);
          }
          break;
        }
        default:
          defaultSettings[key] = clone(settings[key]);
      }
    });
    // 錯誤處理
    defaultSettings.error = function errDeal(err, status) {
      // if ($("#tips_win").is(":hidden")) {
      //   if (err.status === 408 || err.status >= 500 || err.status === 0) {
      //     // 請求超時,請稍後重試
      //     httpAlert.show(lang.timeout);
      //   }
      //   if (status >= 500) {
      //     httpAlert.show(lang.timeout);
      //   }
      //   //
      //   if (err.status === 403) {
      //     // '請求錯誤'
      //     httpAlert.show(lang.reqError);
      //   }
      // }

      // 錯誤處理 回掉
      if (errCallBack) {
        errCallBack(err);
      }
    }
  }
  let method = defaultSettings.method;
  let type = defaultSettings.type;
  // post 请求
  if ((!defaultSettings.method || method.toUpperCase() != 'GET') && !publiceKey) {
    task.push(new Promise((resolve) => {
      let data = defaultSettings['data'];
      let jsencData = rsaData(data);
      defaultSettings.data = jsencData;
      $.ajax(defaultSettings);
      resolve();
    }));
  }
  else if ((!defaultSettings.type || type.toUpperCase() != 'GET') && !publiceKey) {
    task.push(new Promise((resolve) => {
      let data = defaultSettings['data'];
      let jsencData = rsaData(data);
      defaultSettings.data = jsencData;
      $.ajax(defaultSettings);
      resolve();
    }));
  }
  else {
    if (defaultSettings['data'] && (method.toUpperCase() != 'GET' || type.toUpperCase() != 'GET')) {
      let data = defaultSettings['data'];
      let jsencData = rsaData(data);
      defaultSettings.data = jsencData;
    }
    $.ajax(defaultSettings);
  }
  return
}

export default http;
