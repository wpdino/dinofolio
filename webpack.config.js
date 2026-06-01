/**
 * External dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

/**
 * WordPress dependencies
 */
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

module.exports = {
	...defaultConfig,
	entry: {
		'index': './src/index.js',
		'dinofolio': './src/blocks/dinofolio/index.js',
	},
	output: {
		path: path.resolve(__dirname, 'build'),
		filename: '[name].js',
		clean: {
			keep: /\.(php|scss)$/,
		},
	},
	plugins: [
		...defaultConfig.plugins.map((plugin) => {
			// Customize MiniCssExtractPlugin to rename CSS files
			if (plugin.constructor.name === 'MiniCssExtractPlugin') {
				return new plugin.constructor({
					...plugin.options,
					filename: (chunkData) => {
						// Rename editor CSS file (from editor.scss)
						if (chunkData.chunk.name === 'dinofolio') {
							return 'editor-dinofolio.css';
						}
						// Rename frontend CSS file (from style.scss) 
						if (chunkData.chunk.name.includes('style-dinofolio')) {
							return 'dinofolio.css';
						}
						return '[name].css';
					},
				});
			}
			// Filter out default DependencyExtractionWebpackPlugin
			if (plugin.constructor.name === 'DependencyExtractionWebpackPlugin') {
				return null;
			}
			return plugin;
		}).filter(Boolean),
		new DependencyExtractionWebpackPlugin({
			outputFilename: '[name].asset.php',
			combineAssets: true,
		}),
	],
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve.alias,
			'@': path.resolve(__dirname, 'includes/blocks'),
		},
	},
}; 