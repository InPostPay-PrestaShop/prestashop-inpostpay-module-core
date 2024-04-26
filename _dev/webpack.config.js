const path = require('path');
const { EsbuildPlugin } = require('esbuild-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

module.exports = (env, options) => ({
  entry: {
    prestashopizi: [
      './src/index.js',
    ],
    product: [
      './src/css/product.scss',
    ],
  },
  output: {
    filename: 'js/[name].[contenthash].js',
    path: path.resolve(__dirname, '../views/'),
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'esbuild-loader',
          options: {
            target: 'es2015',
          }
        }
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              implementation: require('sass')
            },
          },
        ]
      }
    ]
  },
  stats: {
    colors: true,
  },
  devtool: options.mode !== 'production' ? 'eval-source-map' : 'hidden-source-map',
  plugins: [
    new FixStyleOnlyEntriesPlugin(),
    new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css',
    }),
    new EsbuildPlugin({
      target: 'es2015',
      format: 'iife',
      minify: options.mode === 'production',
      sourcemap: options.mode !== 'production',
    }),
    new CleanWebpackPlugin({
      cleanOnceBeforeBuildPatterns: [
        'js/*.js',
        'js/*.js.map',
        'css/*.css',
        'css/*.css.map',
      ],
    }),
    new WebpackManifestPlugin({
      publicPath: '',
    }),
  ],
});
