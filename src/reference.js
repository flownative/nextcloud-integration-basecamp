import { registerWidget, registerCustomPickerElement, NcCustomPickerRenderResult } from '@nextcloud/vue/components/NcRichText'

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

registerCustomPickerElement('create-basecamp-card', async (el, { providerId, accessible }) => {
	const { createApp } = await import('vue')
	const { default: CreateBasecampCardPicker } = await import('./views/CreateBasecampCardPicker.vue')

	const app = createApp(CreateBasecampCardPicker, {
		providerId,
		accessible,
	})
	app.mixin({ methods: { t, n } })
	app.mount(el)

	return new NcCustomPickerRenderResult(el, app)
}, (el, renderResult) => {
	renderResult?.object?.unmount?.()
}, 'normal')
