/*
 *
 * !!! 【重要提醒 】 !!! 【重要提醒 】 !!! 【重要提醒】  !!! 【重要提醒】  !!! 【重要提醒】
 * TradingView 自定义样式， 在  publice/td_js/view.css
 *
 */

// bg color: #0e192b
// green: #0fbb89
// red: #dc1a5a
import cookie from 'tools/cookie';

/* eslint-disable */
export default (symbolType) => {
  // bg color: #0e192b
  // green: #0fbb89
  // red: #dc1a5a
  // orange #ffbd09

  const UP_COLOR = '#0fbb89';
  const DOWN_COLOR = '#dc1a5a';
  const BG_COLOR = '#0e192b';
  const GRID_COLOR = "#212c3f";
  const ORANGE_COLOR = '#ffbd09';

  // https://www.huocoin.com/ajax_tdv   https://demo_feed.tradingview.com
  let udf_datafeed = new Datafeeds.UDFCompatibleDatafeed("/ajax_tdv");
  let lang = cookie.getItem('LANG');
  // 默认 中文 繁体
  if (!lang || lang === 'cn') {
    lang = 'zh_TW';
  }

  let widget = window.tvWidget = new TradingView.widget({
    // debug: true, // uncomment this line to see Library errors and warnings in the console
    autosize: true,
    symbol: symbolType || 'mcc_btc',
    interval: "15",
    toolbar_bg: BG_COLOR,
    container_id: "tradingV",
    // BEWARE: no trailing slash is expected in feed URL
    datafeed: udf_datafeed,
    library_path: "/td_js/charting_library/",
    locale: lang,
    // 禁用的按钮列表
    disabled_features: [
      // "header_widget_dom_node",
      "save_chart_properties_to_local_storage",
      "volume_force_overlay",
      "header_symbol_search",
      // k 线类型 按钮，如： 日线，小时线
      "header_resolutions",

      "header_interval_dialog_button",
      // k线类型
      "header_chart_type",
      // 设置按钮
      // "header_settings",
      'header_toolbar_study',
      'study_buttons_in_legend',
      // "header_indicators",
      "header_compare",
      "header_undo_redo",
      "header_screenshot",
      "timeframes_toolbar",
      "show_hide_button_in_legend",
      //
      // "left_toolbar",
      "pane_context_menu",
      "legend_context_menu",
      // "create_volume_indicator_by_default",
      "scales_context_menu"
    ],
    // 启用的工具列表
    enabled_features: [
      "move_logo_to_main_pane",
      "study_templates",
      "control_bar",
      "keep_left_toolbar_visible_on_small_screens",
      "dont_show_boolean_study_arguments"
    ],
    // kline 样式配置
    overrides: {
      // k线图 主题颜色
      "mainSeriesProperties.style": 1,
      "symbolWatermarkProperties.color": BG_COLOR,
      "paneProperties.background": "#0e192b",

      // 网格
      "paneProperties.vertGridProperties.color": GRID_COLOR,
      "paneProperties.vertGridProperties.style": 2,
      "paneProperties.horzGridProperties.color": GRID_COLOR,
      "paneProperties.horzGridProperties.style": 2,
      "paneProperties.crossHairProperties.color": "#FFFFFF",

      // 默认隐藏 左上角 legend 指标列表
      "paneProperties.legendProperties.showLegend": true,

      // volume 高度  支持的值: large, medium, small, tiny
      "volumePaneSize": "small",

      // 边际（百分比）。 用于自动缩放
      "paneProperties.topMargin": 5,

      // 蜡烛样式
      "mainSeriesProperties.candleStyle.upColor": UP_COLOR,
      "mainSeriesProperties.candleStyle.downColor": DOWN_COLOR,
      "mainSeriesProperties.candleStyle.borderColor": GRID_COLOR,
      "mainSeriesProperties.candleStyle.borderUpColor": UP_COLOR,
      "mainSeriesProperties.candleStyle.borderDownColor": DOWN_COLOR,

      // 蜡烛 灯芯
      "mainSeriesProperties.candleStyle.wickUpColor": UP_COLOR,
      "mainSeriesProperties.candleStyle.wickDownColor": DOWN_COLOR,

      // 空心 蜡烛
      "mainSeriesProperties.hollowCandleStyle.upColor": UP_COLOR,
      "mainSeriesProperties.hollowCandleStyle.downColor": DOWN_COLOR,
      "mainSeriesProperties.hollowCandleStyle.borderColor": GRID_COLOR,
      "mainSeriesProperties.hollowCandleStyle.borderUpColor": UP_COLOR,
      "mainSeriesProperties.hollowCandleStyle.borderDownColor": DOWN_COLOR,
      // "symbolWatermarkProperties.color": "rgba(0, 0, 0, 0.5)"
      // 
      "headerToolbarIndicators.backgroundColor": DOWN_COLOR
    },
    studies_overrides: {
      "volume.volume.color.0": UP_COLOR,
      "volume.volume.color.1": DOWN_COLOR,
      "volume.volume.transparency": 20,
      "volume.show ma": false,
      "bollinger bands.median.color": "#33FF88",
      "bollinger bands.upper.linewidth": 3
    },
    widgetbar: {
      details: true,
      watchlist: false,
    },
    // 自定义css url
    custom_css_url: '/td_js/view.css?v=123',
    // 主题背景颜色 backgroundColor    菊花颜色 ： foregroundColor
    loading_screen: { backgroundColor: BG_COLOR, foregroundColor: ORANGE_COLOR },
    debug: false,
    time_frames: false,
    // charts_storage_url: 'http://saveload.tradingview.com',
    charts_storage_api_version: "1.1",
    client_id: "", // 'https://www.huocoin.com',
    user_id: 'public_user',
    favorites: {
      intervals: ["1", "15", "1D", "3D", "3W", "W", "M"],
      chartTypes: ["Area", "Line"]
    }
  });


  widget.onChartReady(function() {
    // 订阅 数据 数据完成后
    // widget.chart().onDataLoaded(function(Subscription) {
    //   console.log(Subscription);
    // });
    // 更改 k线图类型 按钮 样式
    function changeStaus(tar) {
      let groups = [...tar.parentNode.parentNode.childNodes];
      groups.forEach(gronp => {
        if (gronp.firstChild.className.indexOf('sel_btn') > -1) {
          gronp.firstChild.className = gronp.firstChild.className.replace(' sel_btn', '');
        }
      });
      tar.className += ' sel_btn';
    }

    // 白色
    widget.chart().createStudy("Moving Average", false, false, [5], null, {
      "plot.color": "#d5dde9",
      "plot.plottype": "line"
    });
    // 黄色
    widget.chart().createStudy("Moving Average", false, false, [10], null, {
      "plot.color": "#ffdf8a",
      "plot.plottype": "line"
    });
    // 紫色
    widget.chart().createStudy("Moving Average", false, false, [20], null, {
      "plot.color": "#cf70c0",
      "plot.plottype": "line"
    });
    // 绿色
    widget.chart().createStudy("Moving Average", false, false, [30], null, {
      "plot.color": "#0e9dc6",
      "plot.plottype": "line"
    });

    // 通过id设置 Study 能见度
    function visibStudy(isVisible) {
      let studies = widget.chart().getAllStudies();
      for (let study of studies) {
        if (study.name === 'Moving Average') {
          widget.chart().setEntityVisibility(study.id, isVisible);
        }
      }
    }

    // 分時線
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(false);
      // Bars = 0;   Candles = 1;   Line = 2;   Area = 3;   Heiken Ashi = 8;   Hollow Candles = 9;   Renko = 4;   Kagi = 5;   Point&Figure = 6;   Line Break = 7
      widget.applyOverrides({
        "mainSeriesProperties.style": 3
      });
      // 参数 必须字符串
      widget.chart().setResolution("1");
    })
    .append('<span>Line</span>');

    // 1 分鐘
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.applyOverrides({ "mainSeriesProperties.style": 1});
      widget.chart().setResolution("1");
    })
    .append('<span>1 min</span>');

    // 5 分鐘
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("5", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>5 min</span>');

    // 15 min
    widget.createButton()
    .attr("class", "button sel_btn")
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("15", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>15 min</span>');
    // 30 min
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("30", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>30 min</span>');
    // 60 min
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("60", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1 });
      });
    })
    .append('<span>1 hour</span>');
    // 4h
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("240", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>4 hour</span>');
    // 12h
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("720", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>12 hour</span>');
    // 1 Day
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("D", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>1 day</span>');
    // 1 week
    widget.createButton()
    .on('click', function (e) {
      changeStaus(e.currentTarget);
      visibStudy(true);
      widget.chart().setResolution("W", function() {
        widget.applyOverrides({ "mainSeriesProperties.style": 1});
      });
    })
    .append('<span>1 week</span>');

    // widget.createButton()
    // .on('click', function (e) {
    //   changeStaus(e.currentTarget);
    //   widget.chart().setResolution("M");
    // })
    // .append('<span>1 month</span>');


  }); // end of widget.onChartReady
};
