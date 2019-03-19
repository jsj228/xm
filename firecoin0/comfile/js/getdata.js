$('.price_today_ull > .click-sort').each(function () {
    $(this).click(function () {
        click_sortList(this);
    })
})
function allcoin_callback(priceTmp) {
    for (var i in priceTmp) {
        var c = priceTmp[i][8];
        if (typeof (trends[c]['data']) && typeof (trends[c]['data']) != 'null') {
            if (typeof (trends[c]) != 'undefined' && typeof (trends[c]['data']) != 'undefined') {
                $.plot($("#" + c + "_plot"), [{shadowSize: 0, data: trends[c]['data']}], {
                    grid: {borderWidth: 0},
                    xaxis: {mode: "time", ticks: false},
                    yaxis: {tickDecimals: 0, ticks: false},
                    colors: ['#fb0803']//k线颜色
                });
            }
        }
    }
}
function click_sortList(sortdata) {
    var a = $(sortdata).attr('data-toggle');
    var b = $(sortdata).attr('data-sort');
    $(".price_today_ull > li").find('.cagret-up').css('border-bottom-color', '#848484');
    $(".price_today_ull > li").find('.cagret-down').css('border-top-color', '#848484');
    $(".price_today_ull > li").attr('data-flaglist', 0).attr('data-toggle', 0);
    $(".price_today_ull > li").css('color', '');
    $(sortdata).css('color', '#FB1D1D');
    if (a == 0) {
        priceTmp = priceTmp.sort(sortcoinList('dec', b));
        $(sortdata).find('.cagret-down').css('border-top-color', '#FB1D1D');
        $(sortdata).find('.cagret-up').css('border-bottom-color', '#848484');
        $(sortdata).attr('data-flaglist', 1).attr('data-toggle', 1)
    }
    else if (a == 1) {
        $(sortdata).attr('data-toggle', 0).attr('data-flaglist', 2);
        ;
        $(sortdata).find('.cagret-up').css('border-bottom-color', '#FB1D1D');
        $(sortdata).find('.cagret-down').css('border-top-color', '#848484');
        priceTmp = priceTmp.sort(sortcoinList('asc', b));
    }
    renderPage(priceTmp);
    change_line_bg('price_today_ul', 'li');
    allcoin_callback(priceTmp);
}
function trends() {
    $.getJSON('/ajax/index_b_trends?t=' + Math.random(), function (d) {
        trends = d;
        allcoin();
    });
}
function allcoin() {
    var trade_qu_id = $('.trade_qu_list .current').attr('data');
    $.get('/ajax/allcoin_a/id/' + trade_qu_id + '?t=' + Math.random(), function (data) {
        var d;
        if (data.status == 1) {
            d = data.url;
        }
        ALLCOIN  = d;
        var t    = 0;
        var img  = '';
        priceTmp = [];
        for (var x in d) {
            if (typeof(trends[x]) != 'undefined' && parseFloat(trends[x]['yprice']) > 0) {
                rise1 = (((parseFloat(d[x][4]) + parseFloat(d[x][5])) / 2 - parseFloat(trends[x]['yprice'])) * 100) / parseFloat(trends[x]['yprice']);
                rise1 = rise1.toFixed(2);
            } else {
                rise1 = 0;
            }
            img = d[x].pop();
            d[x].push(rise1);
            d[x].push(x);
            d[x].push(img);
            priceTmp.push(d[x]);
        }
        $('.price_today_ull > .click-sort').each(function () {
            var listId = $(this).attr('data-sort');
            if ($(this).attr('data-flaglist') == 1 && $(this).attr('data-sort') !== 0) {
                priceTmp = priceTmp.sort(sortcoinList('dec', listId))
            } else if ($(this).attr('data-flaglist') == 2 && $(this).attr('data-sort') !== 0) {
                priceTmp = priceTmp.sort(sortcoinList('asc', listId))
            }
        });
        renderPage(priceTmp);
        allcoin_callback(priceTmp);
        change_line_bg('price_today_ul', 'li');
        // t = setTimeout('allcoin()', 5000);
    }, 'json');
}
function renderPage(ary) {
    var html = '';
    for (var i in ary) {
        var coinfinance = 0;
        if (typeof FINANCE == 'object') coinfinance = parseFloat(FINANCE.data[ary[i][8] + '_balance']);
        html += '<li><a href="/trade/index/market/' + ary[i][8] + '/">' +
            '<img src="__DOMAIN____UPLOAD__/coin/' + ary[i][9] + '" style="vertical-align: middle;margin-right: 5px;width: 19px;margin-left:5px;">' + ary[i][0] +
            '<span style="margin-left:10px">' +
             (parseFloat(ary[i][7]) < 0 ? '' : '+') +  

             ((parseFloat(ary[i][7]) < 0.01 && parseFloat(ary[i][7]) > -0.01) ? "0.00" : (parseFloat(ary[i][7])).toFixed(2)) +'%</span>'+
            '<br><b style="color:#fff;margin-right:3px">' +ary[i][1] +'</b>'+formatCount(ary[i][6])+
            '<input type="button" value="{:L(\'GO_TRADE\')}" class="btns2" onclick="top.location=\'/trade/index/market/'+ 
            ary[i][8] + '/\'" />'+'</a>'+'<dd id="' + ary[i][8] + '_plot"  style="width:100px;height:60px;"></dd></li>'
    }
    $('#price_today_ul').html(html);
}
function formatCount(count) {
    var countokuu = (count / 100000000).toFixed(3)
    var countwan  = (count / 10000).toFixed(3)
    if (count > 100000000)
        return countokuu.substring(0, countokuu.lastIndexOf('.') + 3) + '{:L(\'BILLION\')}'
    if (count > 10000)
        return countwan.substring(0, countwan.lastIndexOf('.') + 3) + '万'
    else
        return count
}
//交易列表底部背景颜色
function change_line_bg(id, tag, nobg) {
    var oCoin_list = $('#' + id);
    var oC_li      = oCoin_list.find(tag);
    var oInp       = oCoin_list.find('input');
    var oldCol     = null;
    var newCol     = null;
    if (!nobg) {
        for (var i = 0; i < oC_li.length; i++) {
            oC_li.eq(i).css('background-color', i % 2 ? 'transparent' : 'transparent');
        }
    }
    oCoin_list.find(tag).hover(function () {
        oldCol = $(this).css('backgroundColor');
        $(this).css('background-color', 'transparent');
    }, function () {
        $(this).css('background-color', oldCol);
    })
}
function sortcoinList(order, sortBy) {
    var ordAlpah = (order == 'asc') ? '>' : '<';
    var sortFun  = new Function('a', 'b', 'return parseFloat(a[' + sortBy + '])' + ordAlpah + 'parseFloat(b[' + sortBy + '])? 1:-1');
    return sortFun;
}
function trade_qu(o){
    $('.trade_qu_pai').removeClass('current');
    $(o).addClass('current');
    allcoin();
}
trends();
    