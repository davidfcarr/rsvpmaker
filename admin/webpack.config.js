const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const splitChunks = defaultConfig?.optimization?.splitChunks || {};
const cacheGroups = splitChunks.cacheGroups || {};

module.exports = {
	...defaultConfig,
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...splitChunks,
			chunks: 'async',
			maxInitialRequests: 20,
			maxAsyncRequests: 30,
			cacheGroups: {
				...cacheGroups,
				reactQueryVendor: {
					test: /[\\/]node_modules[\\/](react-query|@tanstack)[\\/]/,
					name: 'vendor-react-query',
					chunks: 'async',
					priority: 40,
					enforce: true,
				},
				wpDataviewsVendor: {
					test: /[\\/]node_modules[\\/]@wordpress[\\/]dataviews[\\/]/,
					name: 'vendor-wp-dataviews',
					chunks: 'async',
					priority: 35,
					enforce: true,
				},
				domPurifyVendor: {
					test: /[\\/]node_modules[\\/]dompurify[\\/]/,
					name: 'vendor-dompurify',
					chunks: 'async',
					priority: 30,
					enforce: true,
				},
				emotionVendor: {
					test: /[\\/]node_modules[\\/](@emotion[\\/]|stylis[\\/])/,
					name: 'vendor-emotion',
					chunks: 'async',
					priority: 28,
					enforce: true,
				},
				ariakitVendor: {
					test: /[\\/]node_modules[\\/](@ariakit[\\/]|@floating-ui[\\/])/,
					name: 'vendor-ariakit',
					chunks: 'async',
					priority: 27,
					enforce: true,
				},
				dateFnsVendor: {
					test: /[\\/]node_modules[\\/](date-fns|@date-fns[\\/]|react-day-picker)[\\/]/,
					name: 'vendor-date',
					chunks: 'async',
					priority: 26,
					enforce: true,
				},
				framerVendor: {
					test: /[\\/]node_modules[\\/](framer-motion|motion-dom|motion-utils)[\\/]/,
					name: 'vendor-framer',
					chunks: 'async',
					priority: 25,
					enforce: true,
				},
				vendorMisc: {
					test: /[\\/]node_modules[\\/]/,
					name: 'vendor-misc',
					chunks: 'async',
					priority: 10,
					reuseExistingChunk: true,
				},
			},
		},
	},
};
