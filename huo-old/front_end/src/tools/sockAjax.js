import http from './http';

class SockAjax {
  constructor(options) {
    if (options) {
      this.timeout = options.timeout;
    }
  }
  http(opts, callback) {
    this.sock = setInterval(() => {
      http(opts, callback);
    }, this.timeout);
  }
}

export default SockAjax;
