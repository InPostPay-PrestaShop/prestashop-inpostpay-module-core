const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const { EsbuildPlugin } = require('esbuild-loader');

const entries = [
  'admin',
  'consents',
  'gui',
  'support',
];

const getEntries = () => {
  const entry = {};

  entries.forEach((name) => {
    entry[name] = [
      `./src/js/${name}.js`,
      `./src/css/${name}.scss`,
    ];
  });

  return entry;
}

module.exports = {
  entry: getEntries(),
  output: {
    filename: 'js/admin/[name].js',
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
            target: 'es2015'
          }
        }
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                config: path.resolve(__dirname, 'postcss.config.js'),
              },
            }
          },
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
  devtool: 'source-map',
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/admin/[name].css',
    }),
    new EsbuildPlugin({
      target: 'es2016',
      format: 'iife',
      minify: true,
    }),
  ],
};
