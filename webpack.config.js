/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const CopyPlugin = require("copy-webpack-plugin");

/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const { dashboard } = require('@wordpress/icons');

// Directory paths
const SRC_DIR = path.resolve(__dirname, "assets/src");
const BUILD_DIR = path.resolve(__dirname, "assets/build");

// Ensure build directory exists
if (!fs.existsSync(BUILD_DIR)) {
	fs.mkdirSync(BUILD_DIR, { recursive: true });
}

// Extend the default config.
const sharedConfig = {
	...defaultConfig,
	output: {
		path: BUILD_DIR, // Use BUILD_DIR directly
		filename: '[name].js',
		chunkFilename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins,
		new RemoveEmptyScriptsPlugin(),
		// Copy static assets
		new CopyPlugin({
			patterns: [
				{
					from: path.join(SRC_DIR, 'images'),
					to: path.join(BUILD_DIR, 'images'),
					noErrorOnMissing: true,
				},
			],
		}),
	],
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
		},
		minimizer: defaultConfig.optimization.minimizer.concat([new CssMinimizerPlugin()]),
	},
};

// Generate a webpack config which includes setup for CSS extraction.
const styles = {
	...sharedConfig,
	output: {
		path: path.join(BUILD_DIR, 'css'), // Consistent with sharedConfig
		filename: '[name].js',
		chunkFilename: '[name].js',
	},
	entry: () => {
		const entries = {};

		const dir = path.join(SRC_DIR, 'css');
		if (fs.existsSync(dir)) {
			fs.readdirSync(dir).forEach((fileName) => {
				const fullPath = path.join(dir, fileName);
				if (
					!fs.lstatSync(fullPath).isDirectory() &&
					fileName.match(/\.(scss|css)$/)
				) {
					entries[fileName.replace(/\.[^/.]+$/, '')] = fullPath;
				}
			});
		}

		return entries;
	},
	plugins: [
		...sharedConfig.plugins.filter(
			(plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin',
		),
	],
};

const scripts = {
	...sharedConfig,
	output: {
		path: path.join(BUILD_DIR, 'js'), // Consistent with sharedConfig
		filename: '[name].js',
		chunkFilename: '[name].js',
	},
	entry: {
		dashboard: path.resolve(process.cwd(), 'assets', 'src', 'admin/dashboard', 'index.jsx'),
		settings: path.resolve(process.cwd(), 'assets', 'src', 'admin', 'index.jsx'),
	},
	module: {
		rules:
			sharedConfig?.module?.rules?.filter((rule) => {
				return (
					!rule.test ||
					(!rule.test.toString().includes('scss') &&
						!rule.test.toString().includes('css'))
				);
			}) || [],
	},
	resolve: {
		...sharedConfig.resolve,
		extensions: ['.tsx', '.ts', '.jsx', '.js'],
		alias: {
			...(sharedConfig.resolve?.alias || {}),
			'@': path.resolve(process.cwd(), 'assets', 'src'),
		},
	},
};

module.exports = [
	scripts,
	styles,
];