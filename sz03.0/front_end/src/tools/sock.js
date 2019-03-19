// import clone from 'clone';
import is from 'tools/is';

export default function (settings) {
  if (!settings) return;
  let ws = null;
  // 浏览器是否支持 socket
  if (window.WebSocket) {
    // let preInt =
    // 建立 socket
    ws = new WebSocket(settings.url);
    // 连接
    ws.onopen = function() {
      if (is(settings.open, 'Function')) {
        settings.open();
      }
    }
    // 响应
    ws.onmessage = function(res) {
      if (is(settings.message, 'Function')) {
        settings.message(res);
      }
    }
    // 关闭
    ws.onclose = function() {
      if (is(settings.close, 'Function')) {
        settings.close();
      }
    }
  }
  return ws;
}
