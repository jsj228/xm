import axios from 'axios';
import qs from 'qs';
import Promise from 'Promise';
import { JSEncrypt } from 'jsencrypt/bin/jsencrypt';
import clone from 'clone';
import is from '../tools/is.js';
// import pubfn from 'plugins/publicKeyNew';

window.Promise = Promise;
const http = {};

let waitG = '';
let pubuliceKey = null;
let isLoadingKey = false;
let encrypt = new JSEncrypt();

// 等待列表
let waitList = [];
// 获取完成
function keyDone() {
  waitList.forEach(item => item());
}
// 获取key
function getKey() {
  isLoadingKey = true;
  return new Promise((resolve) => {
    axios({
      url: '/ajax_user/getCommonRsaKey',
      method: "GET"
    })
    .then(res => {
      let { status, data } = res.data;
      let intStatus = parseInt(status);
      if (intStatus === 1) {
        encrypt.setPublicKey(data);
        isLoadingKey = false;
        pubuliceKey = data;
        resolve();
        keyDone();
      } else {
        getKey();
      }
    })
    .catch(err => {
      console.log(err);
      getKey();
    });
  });
}
function jsencrypt(oldData) {

  let data = Object.assign({}, oldData);
  if (pubuliceKey) {
    try {
      let pukdata = '';
      let jsonStri = JSON.stringify(data);
      // 是否包含中文 编码
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
      // 编码开关
      if (encode) {
        data.ud = encode;
      }
    } catch (err) {
      /* eslint-disable */
      console.log(err);
      /* eslint-enable */
    }
  }
  return data;
}

http.install = function(Vue) {
  Vue.prototype.$http = axios.create({
    timeout: 30000
  });
  Vue.prototype.$jsencrypt = jsencrypt;
  // 请求劫持 添加一个请求拦截器
  Vue.prototype.$http.interceptors.request.use(async function (config) {
    if (config) {
      let { data = "", headers = '', rsa = true } = config;
      //
      if (!pubuliceKey && config.method.toUpperCase() != "GET") {
        if (!isLoadingKey) {
          await getKey();
        } else {
          await new Promise(resolve => {
            waitList.push(resolve);
          });
        }
      }
      if (data) {
        let rsaData = data;
        if (rsa) {
          rsaData = jsencrypt(data);
        }
        let dataStr = qs.stringify(rsaData, { arrayFormat: 'brackets' });
        config.data = dataStr;
      }
      // headers
      if (headers) {
        headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        config.headers = headers;
      }
    }
    return config;
  }, function (error) {
    // 对响应错误做点什么
    return Promise.reject(error);
  });
  // 响应 劫持
  Vue.prototype.$http.interceptors.response.use(function (response) {
    let result = '';
    // 对响应数据做点什么
    if (response) {
      let { data, config } = response;
      if (data) {
        result = data;
      }
      // 返回不是对象 或status == 0
      if (!is(result, 'Object') || parseInt(result.status) === 0) {
        let errResult = {};
        errResult.reqUrl = config.url;
        errResult.response = clone(data);
        let errData = config.data ? clone(config.data) : '';
        errResult.param = errData || '';

        // 报告错误
        function reqErr() {
          console.log(pubuliceKey);
          let jsEnc = jsencrypt(errResult);
          let headers = {};
          headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
          axios.post('/ajax_common/reqFailedLog', qs.stringify(jsEnc, { arrayFormat: 'brackets' }), { headers });
        }

        if (!isLoadingKey) {
          reqErr();
        } else {
          waitList.push((resolve) => {
            // 报告错误
            let errResult = {};
            errResult.reqUrl = config.url;
            errResult.response = clone(data);
            let errData = config.data ? clone(config.data) : '';
            errResult.param = errData || '';
            let jsEnc = jsencrypt(errResult);
            let headers = {};
            headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
            axios.post('/ajax_common/reqFailedLog', qs.stringify(jsEnc, { arrayFormat: 'brackets' }), { headers });
          });
          // reqErr();
        }
      }

    }
    // config.errAlert 作为 响应消息 status == 0 时 是否弹框提示用户的开关
    return result;
  }, function (error) {
    // 对响应错误做点什么
    return Promise.reject(error);
  });
};

export default http;
