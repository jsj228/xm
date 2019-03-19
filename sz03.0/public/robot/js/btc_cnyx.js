var page = require('webpage').create();
// console.log('The default user agent is ' + page.settings.userAgent);
// page.settings.userAgent = 'SpecialAgent';
page.open('https://gateio.io/trade/BTC_CNYX', function (status) {
    if (status !== 'success') {
        console.log('Unable to access network');
    } else {
        var ua = page.evaluate(function () {
            return document.getElementById('currPrice').textContent;
        });
        console.log(ua);
    }
    // https://gateio.io/c2c/usdt_cny
    phantom.exit();
});