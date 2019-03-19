<?php

echo 1111;
?>
<html>
<head>
    <title>JS   PHP  测试</title>
</head>
<body>
<script>
    var page = require('webpage').create();
    // console.log('The default user agent is ' + page.settings.userAgent);
    // page.settings.userAgent = 'SpecialAgent';
    page.open('https://gateio.io/trade/XRP_USDT', function (status) {
        if (status !== 'success') {
            console.log('Unable to access network');
        } else {
            var ua = page.evaluate(function () {
                return document.getElementById('currFiat').textContent;
            });
            console.log(page);
            alert(111)
        }
        phantom.exit();
    });
</script>
</body>
</html>

 
 




