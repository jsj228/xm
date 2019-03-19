var page = require('webpage').create();
// console.log('The default user agent is ' + page.settings.userAgent);
// page.settings.userAgent = 'SpecialAgent';
page.open('https://www.feixiaohao.com/currencies/ripple', function (status) {//https://gateio.io/trade/XRP_CNYXg
    // console.log(status);
    if (status !== 'success') {
        console.log('Unable to access network');
    } else {
        var ua = page.evaluate(function () {
            var obj = document.getElementsByClassName('convert');
            return obj[3].textContent;
        });
        console.log(ua);
    }
    // https://gateio.io/c2c/usdt_cny1
    phantom.exit();
});