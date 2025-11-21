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
const fs = require("fs");

// Directory paths
const SRC_DIR = path.resolve(__dirname, "assets/src");
const BUILD_DIR = path.resolve(__dirname, "assets/build");

// Function to dynamically get block entries
function getBlockEntries() {
  const blocksDir = path.join(SRC_DIR, 'blocks');
  const entries = {};
  
  if (!fs.existsSync(blocksDir)) {
    return entries;
  }

  const blockFolders = fs.readdirSync(blocksDir).filter(item => {
    return fs.statSync(path.join(blocksDir, item)).isDirectory();
  });

  blockFolders.forEach(block => {
    const blockJsPath = path.join(SRC_DIR, 'blocks', block, 'index.js');
    const blockEditorScssPath = path.join(SRC_DIR, 'blocks', block, 'editor.scss');
    const blockStyleScssPath = path.join(SRC_DIR, 'blocks', block, 'style.scss');
    const blockViewJsPath = path.join(SRC_DIR, 'blocks', block, 'view.js');
    
    // Main block entry (JS + editor CSS)
    const blockEntry = [];
    
    if (fs.existsSync(blockJsPath)) {
      blockEntry.push(blockJsPath);
    }
    if (fs.existsSync(blockEditorScssPath)) {
      blockEntry.push(blockEditorScssPath);
    }
    
    if (blockEntry.length > 0) {
      entries[`blocks/${block}/index`] = blockEntry;
    }
    
    // Separate frontend style entry
    if (fs.existsSync(blockStyleScssPath)) {
      entries[`blocks/${block}/style`] = [blockStyleScssPath];
    }
    
    // Separate frontend view script entry
    if (fs.existsSync(blockViewJsPath)) {
      entries[`blocks/${block}/view`] = [blockViewJsPath];
    }
  });

  return entries;
}

const entry = {
  customizer: [path.join(SRC_DIR, 'js/customizer.js')],
  editor: [path.join(SRC_DIR, 'js/editor.js')],
  main: [path.join(SRC_DIR, 'js/main.js')],
  admin: [path.join(SRC_DIR, 'js/admin.js')],
  ...getBlockEntries(),
};

// Custom output function to handle different output paths
function getOutputPath(chunk) {
  // Check if this is a block entry
  if (chunk.name && chunk.name.startsWith('blocks/')) {
    // Extract just the filename (index.js or style.js)
    const filename = path.basename(chunk.name) + '.js';
    return `blocks/${chunk.name.split('/')[1]}/${filename}`;
  }
  
  // Default output for non-block entries
  return 'js/[name].js';
}

// Custom CSS filename function
function getCssFilename(chunk) {
  // Check if this is a block entry
  if (chunk.name && chunk.name.startsWith('blocks/')) {
    // Extract just the filename (index.css or style.css)
    const filename = path.basename(chunk.name) + '.css';
    return `blocks/${chunk.name.split('/')[1]}/${filename}`;
  }
  
  // Default output for non-block entries
  return 'css/[name].css';
}

// Custom RTL CSS filename function
function getRtlCssFilename(chunk) {
  // Check if this is a block entry
  if (chunk.name && chunk.name.startsWith('blocks/')) {
    // Extract just the filename (index-rtl.css or style-rtl.css)
    const filename = path.basename(chunk.name) + '-rtl.css';
    return `blocks/${chunk.name.split('/')[1]}/${filename}`;
  }
  
  // Default output for non-block entries
  return 'css/[name]-rtl.css';
}

const output = {
  path: BUILD_DIR,
  filename: (chunkData) => getOutputPath(chunkData.chunk),
};

// Plugin: remove any `*.asset.php` files that don't have a corresponding JS file.
class RemoveAssetPhpWithoutJsPlugin {
  apply(compiler) {
    compiler.hooks.emit.tap('RemoveAssetPhpWithoutJsPlugin', (compilation) => {
      Object.keys(compilation.assets).forEach((assetName) => {
        if (!assetName.endsWith('.asset.php')) {
          return;
        }

        // For block entries, check in the corresponding block folder
        if (assetName.startsWith('blocks/')) {
          const blockName = assetName.split('/')[1];
          const entryName = path.basename(assetName, '.asset.php');
          const jsPath = `blocks/${blockName}/${entryName}.js`;
          
          if (!Object.prototype.hasOwnProperty.call(compilation.assets, jsPath)) {
            delete compilation.assets[assetName];
          }
        } else {
          // For non-block entries, check in js folder
          const jsPath = assetName.replace(/\.asset\.php$/, '.js').replace(/^[^\/]+\//, 'js/');
          if (!Object.prototype.hasOwnProperty.call(compilation.assets, jsPath)) {
            delete compilation.assets[assetName];
          }
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

      // Custom CSS extraction with dynamic filenames for blocks
      new MiniCssExtractPlugin({
        filename: (chunkData) => getCssFilename(chunkData.chunk),
      }),

      // RTL CSS generation with dynamic filenames for blocks
      new RtlCssPlugin({
        filename: (chunkData) => getRtlCssFilename(chunkData.chunk),
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
          {
            from: SRC_DIR + '/fonts',
            to: BUILD_DIR + '/fonts',
            noErrorOnMissing: true,
          },
          {
            from: SRC_DIR + '/blocks',
            to: BUILD_DIR + '/blocks',
            noErrorOnMissing: true,
            filter: (resourcePath) => {
              // Only copy non-JS/CSS files (PHP, JSON, etc.)
              const ext = path.extname(resourcePath);
              return !['.js', '.jsx', '.ts', '.tsx', '.scss', '.css'].includes(ext);
            },
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