var foo = {
  devServer: {
    proxy: {}
  },
  publicPath: process.env.VUE_APP_BASE_URL,
  productionSourceMap: false,
  configureWebpack: config => {
    if (config.optimization && config.optimization.minimizer)
      config.optimization.minimizer.forEach(minimizer => {
        if (
          minimizer.options &&
          minimizer.options.terserOptions &&
          minimizer.options.terserOptions.compress
        )
          minimizer.options.terserOptions.compress.drop_console = true;
      });
  }
};
foo.devServer.proxy[`${process.env.VUE_APP_BASE_URL}api`] = {
  target: process.env.VUE_APP_PROXY,
  secure: false,
  changeOrigin: true
};
foo.devServer.proxy[`${process.env.VUE_APP_BASE_URL}upload`] = {
  target: process.env.VUE_APP_PROXY,
  secure: false,
  changeOrigin: true
};

module.exports = foo;
