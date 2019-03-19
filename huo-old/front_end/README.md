# 前端开发环境使用说明

  ---
  - 开发环境启动命令

  ` npm start `

  - dll 打包公共模块

  ` npm run dll `

  - 打包压缩前端文件 (js, css, imgs)

  ` npm run build `

  ---

  ## 工具配置说明

  * front_end 文件夹为前端开发文件夹

  * front_end/config 工具配置文件夹

  * front_end/wPack 为 webpack 配置文件夹

  * front_end/svg_domo 为字体demo

  * front_end/src 为 前端开发代码文件夹
  

  ## dll 说明

  > dll 打包了前端公共文件，包括 vue，jquery, axios, promise，src/nComponents/register.js等的公共库，这个配置可以在【config/index.js】 里面修改。<br><br>
  > <font color="#f932000">【注意】<br> </font>
  在调试模式默认关闭 dll， 这意味着可以在开发模式直接修改
  【src/nComponents/register.js】 等公共文件，并且这些修改都是有效的。
  修改了dll配置的公共文件，必须要在执行 `npm run dll` ，否则直接执行 `npm run build` 那些修改过的公共模块不会起作用
