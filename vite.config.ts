import { createAppConfig } from '@nextcloud/vite-config'

const isProduction = process.env.NODE_ENV === 'production'

export default createAppConfig({
	adminSettings: 'src/adminSettings.js',
	personalSettings: 'src/personalSettings.js',
	reference: 'src/reference.js',
}, {
	config: {
		css: {
			modules: {
				localsConvention: 'camelCase',
			},
		},
		build: {
			cssCodeSplit: true,
		},
	},
	inlineCSS: { relativeCSSInjection: true },
	minify: isProduction,
})
