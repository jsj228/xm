// http://eslint.org/docs/user-guide/configuring

module.exports = {
  root: true,
  parser: 'babel-eslint',
  parserOptions: {
    sourceType: 'module'
  },
  env: {
    browser: true,
    // node: true,
    commonjs: true,
    amd: true,
    es6: true,
    jquery: true,
    // bootstrap:true
  },
  extends: 'airbnb-base',
  // required to lint *.vue files
  plugins: [
    'html'
  ],
  // check if imports actually resolve
  'settings': {
    'import/resolver': {
      'webpack': {
        'config': 'wPack/webpack.base.config.js'
      }
    }
  },
  "parserOptions": {
      "ecmaVersion": 6,
      "sourceType": "module",
      "ecmaFeatures": {
        "jsx": true
      }
    },
  // add your custom rules here
  'rules': {
    // don't require .vue extension when importing
    'import/extensions': [0, 'error', 'always', {
      'js': 'never',
    }],
    // allow optionalDependencies
    'import/no-extraneous-dependencies': 0, // 导入外部 依赖项 方法
    // allow debugger during development process.env
    'no-debugger': process.env.NODE_ENV === "production" ? 2 : 0,
    'no-console': 1,
    'no-var': 0,
    'prefer-arrow-callback': 0,
    'prefer-template': 0,
    'quotes': 0,
    'newline-per-chained-call': 0,
    'eol-last': 1, // 文件以单一的换行符结束
    "space-before-function-paren": [0, "always"], // 函数定义时括号前面要不要有空格
    'no-undef': 2, // 不能有未定义的变量
    'linebreak-style': 0, // CRLF or LF
    'no-param-reassign': 0, // 禁止给参数重新赋值
    "no-use-before-define": 2, //未定义前不能使用
    'global-require': 2,
    "comma-dangle": [2, "never"], // 对象字面量项尾不能有逗号
    "one-var": 1, // 连续声明
    "no-extra-semi": 2, // 禁止多余的冒号
    "spaced-comment": 0, // 注释风格要不要有空格什么的
    "padded-blocks": 0, // 块语句内行首行尾是否要空行
    "space-infix-ops": 1, // 中缀操作符周围要不要有空格
    'no-mixed-operators': 1, // 没有混合运算
    "vars-on-top": 1, // var必须放在作用域顶部
    "use-isnan": 2, // 禁止比较时使用NaN，只能用isNaN()
    "eqeqeq": 1, // 必须使用全等
    "radix": 0, // parseInt必须指定第二个参数
    "indent": 0,
    'no-restricted-properties': 1,
    // "no-unused-vars": [1, {"vars": "all", "args": "after-used"}], // 不能有声明后未被使用的变量或参数
    "no-unused-vars": 0,
    'no-else-return': 1, //
    'import/no-unresolved': [0, {commonjs: true, amd: true}],
    'import/newline-after-import': 1, // import 后 另起一行
    "wrap-iife": 0, //立即执行函数表达式的小括号风格
    "wrap-regex": 0, //正则表达式字面量用小括号包起来
    "no-unused-expressions": 2, //禁止无用的表达式
    "no-floating-decimal": 0, //禁止省略浮点数中的0 .5 3.
    'no-plusplus': 0, // 不能使用 -- 或 ++
    "no-trailing-spaces": 1, //一行结束后面不要有空格
    "consistent-return": 0, //return 后面是否允许省略
    "func-names": 0, //函数表达式必须有名字
    'no-else-return': 0, // else 不 return
    'object-shorthand': 1, // 对象 属性 简写 模式
    'camelcase': 0, // 命名駝峰
    'dot-notation': 0, // 對象 不用中括號
    'prefer-const': 0, // 常量 检测
    'class-methods-use-this': 0,  // class 静态属性
    'no-useless-escape': 0,
    'brace-style': 0, // if else 允许换行
    'no-return-assign': 0, // 是否允许 函数中途 return 停止函数
    'eqeqeq': 0, // 是用全等 === | !==
    'max-len': 0,
    'no-mixed-operators': 0,
    'no-inner-declarations': 0, // 函数内容部 函数
    'no-unreachable': 0,
    'arrow-parens': 0, // 箭头函数 可以不用 {}
    'no-shadow': 0,
    'guard-for-in': 0,
    'no-restricted-syntax': 0,
    'space-unary-ops': 0,
    'no-nested-ternary': 0
  }
}
