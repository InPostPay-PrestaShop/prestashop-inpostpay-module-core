const path = require('path');
const { EsbuildPlugin } = require('esbuild-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");

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
    filename: 'js/[name].js',
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
      filename: 'css/[name].css',
    }),
    new EsbuildPlugin({
      target: 'es2015',
      format: 'iife',
      minify: options.mode === 'production',
      sourcemap: options.mode !== 'production',
    }),
  ],
});
