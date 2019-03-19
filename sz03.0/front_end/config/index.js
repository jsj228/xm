let version = "v0.0.14"
var path = require("path");
function resolve (dir) {
  return path.join(__dirname, '../../public/', dir)
}

function sResolve (dir) {
  return path.join(__dirname, '../src/', dir)
}
module.exports = {
  entry: [
    // index
    "index.index",
    "index.mcc", "index.passwordupgrade",
    // "mob.index.index", "mob.index.register", "mob.index.login",
    // trade
    "trade.index",
    // jsj + c2c
    "c2c.index","otc.index",
    // "mob.trade.index",
    // user 用户个人中心 打包的入口文件
    "user.index", "user.realinfo",  "user.coinin",'user.recharge',"user.coinout", 
    "user.trust", "user.deal", "user.mplan", "user.candy", "user.bank","user.otc",'user.bonusinvite','user.bonusdetails',
    // news
    "news.index", "news.detail",
  ],
  // 入口 js 文件 名字 ,
  output: {
    filename: 'js', // 配置 js 输出文件夹 名字
    path: "",   // 配置 打包 目录文件夹 名字
    publicPath: "/",         // html 文件路径公共 路径
    version: version
  },
  dll: {
    entry: {
      libs: ['jsencrypt', 'fastclick', 'axios', 'promise', 'qs', 'lodash', 'Promise',
        // sResolve('nComponents/register.js'), sResolve('nComponents/nav.js')
        // sResolve('nComponents/register.js'), sResolve('nComponents/nav.js')
      ],
    },
    output: {
      path: resolve('libs'),
      filename: 'dll_[name]_[chunkhash:8].js',
      library: '[name]_[chunkhash:8]'
    }
  },
  cssImgPath: '/', // css 的图片 公共 路径
  devUglify: false, // 开发环境 是否压缩 js css
  proUglify: true, // 生产环境 是否压缩 js css
  imgTranslateBase64MaxValue: 10000,
  env: {
    dev: {
      BundleAnalyzer: false
    },
    build: {
      BundleAnalyzer: false
    }
  }
}
