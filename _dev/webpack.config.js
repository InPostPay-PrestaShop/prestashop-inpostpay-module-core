const path = require('path');
const { EsbuildPlugin } = require('esbuild-loader');

module.exports = {
  entry: {
    prestashopizi: [
      './src/index.js',
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
    ]
  },
  stats: {
    colors: true,
  },
  devtool: 'hidden-source-map',
  plugins: [
    new EsbuildPlugin({
      target: 'es2015',
      format: 'iife',
      minify: true,
      sourcemap: true,
    }),
  ],
};
