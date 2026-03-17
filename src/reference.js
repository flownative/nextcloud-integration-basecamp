import { registerWidget } from '@nextcloud/vue/components/NcRichText'

registerWidget('integration_basecamp_card', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: BasecampCardReferenceWidget } = await import('./views/BasecampCardReferenceWidget.vue')

	const app = createApp(
		BasecampCardReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })
