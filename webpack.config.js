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

// Function to dynamically get component entries
function getComponentEntries(componentType) {
  const componentsDir = path.join(SRC_DIR, componentType);
  const entries = {};
  
  if (!fs.existsSync(componentsDir)) {
    return entries;
  }

  const componentFolders = fs.readdirSync(componentsDir).filter(item => {
    return fs.statSync(path.join(componentsDir, item)).isDirectory();
  });

  componentFolders.forEach(component => {
    const componentJsPath = path.join(SRC_DIR, componentType, component, 'index.js');
    const componentEditorScssPath = path.join(SRC_DIR, componentType, component, 'editor.scss');
    const componentStyleScssPath = path.join(SRC_DIR, componentType, component, 'style.scss');
    const componentViewJsPath = path.join(SRC_DIR, componentType, component, 'view.js');
    
    // Main component entry (JS + editor CSS)
    const componentEntry = [];
    
    if (fs.existsSync(componentJsPath)) {
      componentEntry.push(componentJsPath);
    }
    if (fs.existsSync(componentEditorScssPath)) {
      componentEntry.push(componentEditorScssPath);
    }
    
    if (componentEntry.length > 0) {
      entries[`${componentType}/${component}/index`] = componentEntry;
    }
    
    // Separate frontend style entry
    if (fs.existsSync(componentStyleScssPath)) {
      entries[`${componentType}/${component}/style`] = [componentStyleScssPath];
    }
    
    // Separate frontend view script entry
    if (fs.existsSync(componentViewJsPath)) {
      entries[`${componentType}/${component}/view`] = [componentViewJsPath];
    }
  });

  return entries;
}

const entry = {
  customizer: [path.join(SRC_DIR, 'js/customizer.js')],
  editor: [path.join(SRC_DIR, 'js/editor.js')],
  main: [path.join(SRC_DIR, 'js/main.js')],
  admin: [path.join(SRC_DIR, 'js/admin.js')],
  ...getComponentEntries('blocks'),
  ...getComponentEntries('components'),
  // You can add more component types if needed:
  // ...getComponentEntries('widgets'),
};

// Custom output function to handle different output paths
function getOutputPath(chunk) {
  // Check if this is a component entry (blocks, components, widgets, etc.)
  if (chunk.name && chunk.name.includes('/')) {
    const parts = chunk.name.split('/');
    // Only process if it has at least 3 parts (componentType/componentName/filename)
    if (parts.length >= 3) {
      const componentType = parts[0];
      const componentName = parts[1];
      const filename = path.basename(chunk.name) + '.js';
      return `${componentType}/${componentName}/${filename}`;
    }
  }
  
  // Default output for non-component entries
  return 'js/[name].js';
}

// Custom CSS filename function
function getCssFilename(chunk) {
  // Check if this is a component entry
  if (chunk.name && chunk.name.includes('/')) {
    const parts = chunk.name.split('/');
    // Only process if it has at least 3 parts (componentType/componentName/filename)
    if (parts.length >= 3) {
      const componentType = parts[0];
      const componentName = parts[1];
      const filename = path.basename(chunk.name) + '.css';
      return `${componentType}/${componentName}/${filename}`;
    }
  }
  
  // Default output for non-component entries
  return 'css/[name].css';
}

// Custom RTL CSS filename function
function getRtlCssFilename(chunk) {
  // Check if this is a component entry
  if (chunk.name && chunk.name.includes('/')) {
    const parts = chunk.name.split('/');
    // Only process if it has at least 3 parts (componentType/componentName/filename)
    if (parts.length >= 3) {
      const componentType = parts[0];
      const componentName = parts[1];
      const filename = path.basename(chunk.name) + '-rtl.css';
      return `${componentType}/${componentName}/${filename}`;
    }
  }
  
  // Default output for non-component entries
  return 'css/[name]-rtl.css';
}

const output = {
  path: BUILD_DIR,
  filename: (chunkData) => getOutputPath(chunkData.chunk),
};

// Plugin: remove any `*.asset.php` files that don't have a corresponding JS file.
class RemoveAssetPhpWithoutJsPlugin {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap('RemoveAssetPhpWithoutJsPlugin', (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: 'RemoveAssetPhpWithoutJsPlugin',
          stage: compilation.constructor.PROCESS_ASSETS_STAGE_ADDITIONS,
        },
        (assets) => {
          Object.keys(assets).forEach((assetName) => {
            if (!assetName.endsWith('.asset.php')) {
              return;
            }

            // Check if this is a component entry
            if (assetName.includes('/')) {
              const parts = assetName.split('/');
              if (parts.length >= 3) {
                const componentType = parts[0];
                const componentName = parts[1];
                const entryName = path.basename(assetName, '.asset.php');
                const jsPath = `${componentType}/${componentName}/${entryName}.js`;

                if (!assets[jsPath]) {
                  compilation.deleteAsset(assetName);
                }
              }
            } else {
              const jsPath = assetName
                .replace(/\.asset\.php$/, '.js')
                .replace(/^[^\/]+\//, 'js/');

              if (!assets[jsPath]) {
                compilation.deleteAsset(assetName);
              }
            }
          });
        }
      );
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

      // Custom CSS extraction with dynamic filenames for components
      new MiniCssExtractPlugin({
        filename: (chunkData) => getCssFilename(chunkData.chunk),
      }),

      // RTL CSS generation with dynamic filenames for components
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
          // Copy block assets (non-JS/CSS files)
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
          // You can add more component types for copying if needed:
          {
            from: SRC_DIR + '/components',
            to: BUILD_DIR + '/components',
            noErrorOnMissing: true,
            filter: (resourcePath) => {
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