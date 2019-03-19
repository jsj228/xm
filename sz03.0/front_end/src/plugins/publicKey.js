import http from 'tools/http';
import { JSEncrypt } from 'jsencrypt/bin/jsencrypt';

const publickFn = {};

publickFn.install = function(Vue) {
  // 保存 publicKey
  Vue.publicKey = '';
  // 注册 JSEncrypt
  Vue.prototype.$encrypt = new JSEncrypt();
  //
  Vue.prototype.$getPublicKey = function() {
    const publicKeyFn = () => {
      //
      http({
        url: '/ajax_user/getCommonRsaKey',
        method: "GET",
        success: ({ status, data }) => {
          let intStatus = parseInt(status);
          if (intStatus === 1) {
            this.publicKey = data;
            this.$encrypt.setPublicKey(data);
          } else {
            publicKeyFn();
          }
        },
        error(err) {
          if (err) publicKeyFn();
        }
      }, 'noAlert');
    };
    // once
    publicKeyFn();
  };
  Vue.prototype.$jsencrypt = function(oldData) {
    let data = oldData;

    if (this.publicKey) {
      try {
        let pukdata = '';
        let jsonStri = JSON.stringify(data);
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
            pukdata.push(this.$encrypt.encrypt(part));
          }
        } else {
          pukdata = this.$encrypt.encrypt(jsonStri);
        }

        data = {
          data: pukdata
        };
      } catch (err) {
        /* eslint-disable */
        console.log(err);
        /* eslint-enable */
      }
    }
    return data;
  };
};

export default publickFn;
