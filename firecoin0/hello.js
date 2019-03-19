var page = require('webpage').create();
// console.log('The default user agent is ' + page.settings.userAgent);
// page.settings.userAgent = 'SpecialAgent';
page.open('https://www.btchkgj.com/trade/index/market/wcg_hkd', function (status) {
    if (status !== 'success') {
        console.log('Unable to access network');
    } else {
        var ua = page.evaluate(function () {
            return document.getElementById('market_new_price').textContent;
        });
        console.log(ua);
    }
    phantom.exit();
});



