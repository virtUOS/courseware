var path = require('path'),
    webpack = require('webpack'),
    ExtractTextPlugin = require('extract-text-webpack-plugin'),
    dirAssets = path.join(__dirname, 'assets'),
    dirBlocks = path.join(__dirname, 'blocks');

const nodeEnv = process.env.NODE_ENV || 'development';
const isProd = nodeEnv === 'production';

module.exports = {
  devtool: isProd ? 'source-map' : '#eval-source-map',
  entry: {
    courseware: path.join(dirAssets, 'js', 'courseware.js')
  },
  output: {
    path: path.join(dirAssets, './static'),
    chunkFilename: '[name].chunk.js',
    filename: '[name].js',
    pathinfo: !isProd,
    publicPath: !isProd ? 'http://localhost:8081/' : undefined
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader',
          options: {
            cacheDirectory: true
          }
        }
      },
      {
        test: /\.less$/,
        use: ExtractTextPlugin.extract({
          fallback: 'style-loader',
          use: [
            {
              loader: 'css-loader',
              options: {
                url: false,
                sourceMap: true,
                importLoaders: 2
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: true
              }
            },
            {
              loader: 'less-loader',
              options: {
                sourceMap: true
              }
            }
          ]
        })
      }
    ]
  },
  resolve: {
    extensions: [ '.js' ],
    modules: [
      'node_modules',
      dirAssets,
      dirBlocks
    ]
  },
  plugins: [
    new webpack.DefinePlugin({
      IS_DEV: !isProd
    }),
    new webpack.LoaderOptionsPlugin({
      minimize: true,
      debug: !isProd,
      options: {
      }
    }),
    new webpack.optimize.UglifyJsPlugin({
      comments: !isProd,
      sourceMap: true
    }),
    new webpack.DefinePlugin({
      'process.env': { NODE_ENV: JSON.stringify(nodeEnv) }
    }),
    new ExtractTextPlugin('courseware.css')
  ],
  externals: {
    jquery: 'jQuery'
  }
};
