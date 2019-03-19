// import "@/styles/userCenter/candy.scss";
import 'nStyle/user/candy/user_candy.scss'
import http from '@/tools/http';
import nav from '@/components/nav';
import navVue from '@/nComponents/nav';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import activeNav from '@/components/userNav.js';
import setTableStyle from '@/components/makeTableHead';
import Alert from '@/tools/alert/alert';
import is from '@/tools/is';
import talking from '@/tools/talking/talking'

$(document).ready(function() {
  const navNew = navVue();
  let url = window.location.href;
  //打開聊天室接受數據功能
  talking();
  chkhttpLang("#baseLang", http);
  // 获取语言包
  const lang = getLanguagePack() || {
    "CANDY_TH_COIN": "幣種",
    "CANDY_TH_START": "開始日期",
    "CANDY_TH_DATA": "截止日期",
    "CANDY_TH_GOT": "已領取",
    "GET_DATA_FAIL": "獲取數據失敗",
    "CANDY_ACTIVE_COIN": "活動贈送幣",
    "CANDY_CANGET": "領取",
    "CANDY_GOT": "恭喜妳已經成功領取",
    "CANDY_GET": "領取甜品"
  };
  nav();
  activeNav();
  getCoin(1);

  //从领取奖励部分跳过去

  const myAlert = new Alert("");
  const $tab = $("#tableContent");
  const $ul = $('.range_coin li a');
  const content = $tab.find('thead tr').html();
  const $bot = $('.header-header-bottom');
  const $act = $('.range_coin').find('li');
  // const canGetCoin = 'BTC';
  // 初始化
  setTableStyle();
  window.onresize = function() {
    setTableStyle();
  };

  //点击切换
  $ul.click(function(){
    const t = $(this);
    classTab(t, content)
  })
  // 是否有參數
  if (url.indexOf('?index') > -1) {
    classTab($('[data-nums="3"]'));
  } else {
    getCoinList(1);
  }

  function classTab(t, content) {
    if (!t.hasClass('active')) {
      $('.active').removeClass('active');
      t.addClass('active');
      if (t.attr('data-nums') === '3') {
        $tab.find('thead').html('');
        //清空thead底下存在的表头
        $('#tHead').remove();
        //改变表头
        const newTrs = newTr();
        $tab.find('thead').html(newTrs);
        //重置表头
        setTableStyle();

        $bot.hide();
      }
      ///ajax_user/getUserInfo
      else {
         $('#tHead').remove();
         $tab.find('thead tr').html(content);
         setTableStyle();
         if (t.attr('data-nums') === '1') {
           $bot.find('span.coin_type').text('BTC');
         }
         else {
           $bot.find('span.coin_type').text('ETH');
         }
         $bot.show();
      }
    }
    //獲取幣數據
    getCoin(t.attr('data-nums'));
    //獲取列表數據
    getCoinList(t.attr('data-nums'));
  }
  //切换新表头
  function newTr () {
    return `<tr>
      <td>${lang.CANDY_TH_COIN}</td>
      <td>${lang.CANDY_TH_START}</td>
      <td>${lang.CANDY_TH_DATA}</td>
      <td>${lang.CANDY_TH_GOT}</td>
      <td>${lang.CANDY_CANGET}</td>
    </tr>`;
  }
  //可用分叉币获取
  function getCoin(nums) {
    http({
      url: '/ajax_user/getUserInfo',
      type: 'POST',
      dataType: 'json',
      data: {},
      success(data) {
        if (data.status == 1) {
          if (nums == 1) {
            $('.coin_datas').html(data.data.btc_over);
          }
          else if (nums == 2) {
            $('.coin_datas').html(data.data.eth_over);
          }
        }
      },
      error(err) {
        if (err)
          myAlert.show(lang['GET_DATA_FAIL']);
      }
    })
  }
  function getCoinList(nums) {
    $('#dataBody').html('');
    $('#tableNoData').hide();
    $('#tableLoading').show();
    let url = '';
    if (nums == 3) {
      url = '/ajax_user/giftList';
    } else {
      url = '/ajax_user/branchlist';
    }
    http({
      url: url,
      type: 'POST',
      dataType: 'json',
      data: {},
      success(data) {
        let dataList = [];
        let html = '';
        if (nums == 1) {
          dataList = data.data.BTC;
        }
        else if (nums == 2) {
          dataList = data.data.ETH;
        } else {
          dataList = data.data;
        }
        if (is(dataList, 'Array')) {
          if (dataList.length > 0) {
            //<td class="canGetCoin">${coin.unreceived}</td>
            if (nums == 3) {
              dataList.forEach((coin) => {
                html += `<tr>
                <td class="getBranch">${coin.coin}</td>
                <td>${coin.begin}</td>
                <td>${coin.end}</td>
                <td>${coin.number}</td>
                <td class='getCoinsTd' data-nums="3" data-parent="${coin.branch}" data-type="${coin.type}"><a href="javascript:void(0);" class='${coin.button != 1 ? "not_get" : "noraml_get"}'>${lang['CANDY_CANGET']}</a></td>
                </tr>`;
              });
            } else {
              dataList.forEach((coin) => {
                html += `<tr>
                <td class="getBranch">${coin.branch}</td>
                <td>${coin.height}</td>
                <td>${coin.percent}</td>
                <td>${coin.expire}</td>
                <td>${coin.received}</td>
                <u style="display:none;">${coin.unreceived}</u>
                <td class='getCoinsTd' data-nums="${nums}" data-parent="${coin.parent}" data-type="${coin.type}"><a href="javascript:void(0);" class='${coin.unreceived <= 0 ? "not_get" : "noraml_get"}'>${lang['CANDY_CANGET']}</a></td>
                </tr>`;
              });
            }

            $('#dataBody').html(html);
            $('#tableLoading').hide();
            // 重置表頭
            setTableStyle();
          }
          else {
            $('#tableNoData').show();
            $('#tableLoading').hide();
          }
        }
      },
      error(err) {
        if (err)
          myAlert.show(lang['GET_DATA_FAIL']);
          $('#tableLoading').hide();
      }
    })
  }
  $('body').on('click', '.getCoinsTd', function() {
    const t = $(this);
    if (t.find('a').hasClass('not_get')) {
      return  false;
    }
    else {
      let nums = t.attr('data-nums');
      getCoins(t, nums);
    }
  })
  //弹窗
  function getCoinsAlert(coin) {
    let mus = coin.nums;
    let canGetCoin =''
    if (coin.coinNum) {
      canGetCoin = coin.coinNum + coin.branch + '';
    } else if (coin.dataNum) {
      canGetCoin = coin.dataNum + coin.branch + '';
    }
    // let nums = $act.find('a.acitve').attr('data-nums');
    let coinTypes = '';
    // nums == 1 ? coinTypes = 'BTC' : nums == 2 ? coinTypes = 'ETH' : coinTypes = lang['CANDY_ACTIVE_COIN'];
    //${lang['CANDY_GOT']}
    let htmls = `
                <p class="candy_tips_get">${coin.msg}</p>
                <p class="candy_green">${canGetCoin}</p>
                `;
    //添加标题
    myAlert.setTitle(lang['CANDY_GET']);
    myAlert.show(htmls);
    //设定弹窗宽
    myAlert.setBtnWidth('80%');
    //设定点击按钮之后的回调函数
    $('[data-btnsu="sureBtn"]').click(function(event) {
      // console.log('mus==='+ mus);
      getCoinList(mus);
    });
  }
  //领取分叉币
  function getCoins(t, nums) {
    let  parent = t.attr('data-parent').toLocaleLowerCase();
    let  branch = t.parent('tr').find('.getBranch').text().toLocaleLowerCase();
    const coinNum = t.prevAll('u').text();

    if (nums != 3) {
      http({
        url: '/ajax_user/getBranch',
        type: 'POST',
        dataType: 'json',
        data: {branch:branch, parent:parent},
        success(data) {
          let { msg } = data;
          if (data.status != 3) {
            getCoinsAlert({branch, coinNum, nums, msg});
          } else {
            myAlert.show(data.msg);
          }
        },
        error(err) {
          if (err)
            myAlert.show(lang['GET_DATA_FAIL']);
        }
      })
    } else {
      let type = t.data('type');
      let url = '/ajax_user/coinGift';
      if (type) {
        url = `/Ajax_User/coin${type}`;
      }

      http({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: {coin:branch},
        success(data) {
          let { msg } = data;
          if (data.status == 1) {
            if (branch === "nano") {
              branch = "Nano";
            }
            let dataNum = data.data;
            getCoinsAlert({branch, dataNum, nums, msg});
          } else {
            myAlert.setTitle(lang['CANDY_GET']);
            myAlert.show(data.msg);
            if (data.data.need_real_auth == 1) {
              $('[data-btnsu="sureBtn"]').click(function(event) {
                window.location.href = '/user/realinfo';
              });
            }
          }
        },
        error(err) {
          if (err)
            myAlert.show(lang['GET_DATA_FAIL']);
        }
      })
    }

  }
})
