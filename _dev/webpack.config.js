const Prelude = require('@waynetecommerce/webpack-prelude');

const configs = [];

const preludeFront = new Prelude('front');

preludeFront
  .cleanupOutputBeforeBuild([
    '*.json',
    'js/front/*.js',
    'css/front/*.css',
    'js/front/*.js.map',
    'css/front/*.css.map',
  ])
  .addEntry('prestashopizi', ['./src/front/js/v1/index.js'])
  .addEntry('v2', ['./src/front/js/v2/index.js'])
  .addStyleEntry('product', ['./src/front/css/product.scss'])
  .setPublicPath('../../views/')
  .setOutputPath('../views/')
  .setManifestKeyPrefix('')
  .configureFilenames({
    js: 'js/front/[name].[contenthash].js',
    css: 'css/front/[name].[contenthash].css',
  })
  .configureManifestPlugin(() => ({
    publicPath: '',
  }))
  .enableSassLoader()
  .enablePostCssLoader()
  .disableSingleRuntimeChunk()
  .enableSourceMaps(!Prelude.isProduction());

configs.push(preludeFront.getWebpackConfig());

module.exports = configs;
