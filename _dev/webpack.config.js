const Encore = require('@symfony/webpack-encore');

const configs = [];

Encore.cleanupOutputBeforeBuild([
  '*.json',
  'js/front/*.js',
  'css/front/*.css',
  'js/front/*.js.map',
  'css/front/*.css.map',
])
  .addEntry('v2', ['./src/front/js/v2/index.js'])
  .addStyleEntry('product', ['./src/front/css/product.scss'])
  .addStyleEntry('button', ['./src/front/css/button.scss'])
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
  .enableSourceMaps(!Encore.isProduction());

const frontConfig = Encore.getWebpackConfig();
frontConfig.name = 'front';

configs.push(frontConfig);

module.exports = configs;
