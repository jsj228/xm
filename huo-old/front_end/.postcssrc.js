module.exports = {
  plugins: [
    require('precss'),
    require('autoprefixer')({
      browsers: [
        '> 0.01%',
        'Last 100 versions',
        'IE >= 8',
        'iOS > 7'
      ]
    })
  ]
}
