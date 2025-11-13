/**
 * Webpack configuration file.
 *
 */

const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const RemoveEmptyScriptsPlugin = require("webpack-remove-empty-scripts");
const CopyPlugin = require("copy-webpack-plugin");
const RtlCssPlugin = require("rtlcss-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserPlugin = require("terser-webpack-plugin");
const path = require("path");

// Directory paths
const SRC_DIR = path.resolve(__dirname, "assets/src");
const BUILD_DIR = path.resolve(__dirname, "assets/build");

const entry = {
  customizer: [path.join(SRC_DIR, 'js/customizer.js')],
  editor: [path.join(SRC_DIR, 'js/editor.js')],
  main: [path.join(SRC_DIR, 'js/main.js')],
  admin: [path.join(SRC_DIR, 'js/admin.js')],
};

const output = {
  path: BUILD_DIR,
  filename: 'js/[name].js',
};

// Plugin: remove any `*.asset.php` files that don't have a corresponding JS file.
// For example, if `main.asset.php` is generated but `js/main.js` doesn't exist,
// this plugin will delete `main.asset.php` so it isn't written to disk.
class RemoveAssetPhpWithoutJsPlugin {
  apply(compiler) {
    compiler.hooks.emit.tap('RemoveAssetPhpWithoutJsPlugin', (compilation) => {
      Object.keys(compilation.assets).forEach((assetName) => {
        if (!assetName.endsWith('.asset.php')) {
          return;
        }

        // corresponding JS file path (e.g. js/main.js)
        const jsPath = assetName.replace(/\.asset\.php$/, '.js');

        if (!Object.prototype.hasOwnProperty.call(compilation.assets, jsPath)) {
          // remove the .asset.php since no matching js file was emitted
          delete compilation.assets[assetName];
        }
      });
    });
  }
}

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    ...defaultConfig,
    entry,
    output,
    module: {
      ...defaultConfig.module,
      rules: [
        // Keep all default rules except the ones we explicitly want to override
        ...defaultConfig.module.rules.map(rule => {
          const test = rule.test?.toString() || '';

          // Replace the default image and font rules with our custom ones
          if (test.includes('png') || test.includes('jpg') || test.includes('jpeg') ||
            test.includes('gif') || test.includes('svg') || test.includes('ico') ||
            test.includes('woff') || test.includes('ttf') || test.includes('eot')) {
            return null; // Remove this rule, we'll add our custom ones below
          }

          return rule;
        }).filter(Boolean), // Remove null entries

        // Custom image rule - no content hash
        {
          test: /\.(png|jpg|jpeg|gif|svg|ico)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name][ext]',
          },
        },
        // Custom font rule - no content hash
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name][ext]',
          },
        },
      ],
    },
    plugins: [
      // Remove default CSS-related plugins and optimizations
      ...defaultConfig.plugins.filter((plugin) => {
        const pluginName = plugin.constructor.name;
        // Filter out DependencyExtractionWebpackPlugin for entries that only have SCSS
        if (pluginName === 'DependencyExtractionWebpackPlugin') {
          // Create a new plugin instance only for entries with JS files
          const jsEntries = Object.entries(entry)
            .filter(([, paths]) =>
              paths.some((filePath) => filePath.endsWith('.js'))
            )
            .reduce(
              (acc, [key, value]) => ({ ...acc, [key]: value }),
              {}
            );

          if (Object.keys(jsEntries).length === 0) {
            return false;
          }
        }

        return (
          pluginName !== 'MiniCssExtractPlugin' &&
          pluginName !== 'RtlCssPlugin'
        );
      }),

      // Custom CSS extraction
      new MiniCssExtractPlugin({
        filename: "css/[name].css",
      }),

      // RTL CSS generation
      new RtlCssPlugin({
        filename: "css/[name]-rtl.css",
      }),

      // Remove empty JS files
      new RemoveEmptyScriptsPlugin({
        stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS,
      }),

      // Remove any .asset.php that don't have a matching js file (e.g. for SCSS-only entries)
      new RemoveAssetPhpWithoutJsPlugin(),

      // Copy static assets
      new CopyPlugin({
        patterns: [
          {
            from: SRC_DIR + '/library',
            to: BUILD_DIR + '/library',
            noErrorOnMissing: true,
          },
          {
            from: SRC_DIR + '/images',
            to: BUILD_DIR + '/images',
            noErrorOnMissing: true,
          },
        ],
      }),
    ],
    externals: {
      ...defaultConfig.externals,
      jquery: "jQuery",
    },
    // Disable content hashing and configure optimization conditionally
    optimization: {
      ...defaultConfig.optimization,
      realContentHash: false,
      minimize: isProduction, // Only minimize in production mode
      minimizer: [
        // Use terser for JS (keep default)
        ...defaultConfig.optimization.minimizer.filter(plugin =>
          plugin.constructor.name !== 'CssMinimizerPlugin' &&
          plugin.constructor.name !== 'TerserPlugin'
        ),
        // Custom Terser configuration for JS
        new TerserPlugin({
          terserOptions: {
            compress: {
              drop_console: isProduction, // Remove console.log only in production
            },
            format: {
              comments: false,
            },
          },
          extractComments: false,
        }),
        // CSS minification only in production
        ...(isProduction ? [
          new CssMinimizerPlugin({
            minimizerOptions: {
              preset: [
                'default',
                {
                  discardComments: { removeAll: true },
                },
              ],
            },
          })
        ] : []),
      ],
    },
    performance: {
      maxAssetSize: 512000
    }
  };
};