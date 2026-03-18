<!--
  Admin settings for Basecamp integration
  Allows configuring the Basecamp OAuth Client ID and Secret
-->

<template>
	<div id="basecamp_prefs" class="section">
		<h2>
			<img :src="iconUrl" class="basecamp-icon" alt="">
			{{ t('integration_basecamp', 'Basecamp integration') }}
		</h2>
		<div id="basecamp-content">
			<p class="settings-hint">
				{{ t('integration_basecamp', 'To connect Basecamp, register an application at') }}
				<a href="https://launchpad.37signals.com/integrations" target="_blank" rel="noopener noreferrer">
					launchpad.37signals.com/integrations
				</a>
			</p>
			<p class="settings-hint">
				{{ t('integration_basecamp', 'Use the following redirect URI:') }}
				<br>
				<strong>{{ redirectUri }}</strong>
			</p>

			<div class="line">
				<label for="basecamp-client-id">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_basecamp', 'Client ID') }}
				</label>
				<input id="basecamp-client-id"
					v-model="state.client_id"
					type="text"
					:placeholder="t('integration_basecamp', 'Basecamp OAuth Client ID')"
					@input="onSensitiveInput">
			</div>
			<div class="line">
				<label for="basecamp-client-secret">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_basecamp', 'Client Secret') }}
				</label>
				<input id="basecamp-client-secret"
					v-model="state.client_secret"
					type="password"
					:placeholder="t('integration_basecamp', 'Basecamp OAuth Client Secret')"
					@input="onSensitiveInput">
			</div>

			<NcCheckboxRadioSwitch
				:checked="state.link_preview_enabled"
				@update:checked="onCheckboxChanged">
				{{ t('integration_basecamp', 'Enable Basecamp link previews') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import KeyIcon from 'vue-material-design-icons/Key.vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'
import { showSuccess } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '../../node_modules/@nextcloud/password-confirmation/dist/style.css'

export default {
	name: 'AdminSettings',
	components: {
		KeyIcon,
		NcCheckboxRadioSwitch,
	},
	data() {
		return {
			state: loadState('integration_basecamp', 'admin-config'),
			sensitiveTimer: null,
		}
	},
	computed: {
		redirectUri() {
			return window.location.origin + generateUrl('/apps/integration_basecamp/oauth-redirect')
		},
		iconUrl() {
			return imagePath('integration_basecamp', 'app-bw.png')
		},
	},
	methods: {
		onSensitiveInput() {
			if (this.sensitiveTimer) {
				clearTimeout(this.sensitiveTimer)
			}
			this.sensitiveTimer = setTimeout(() => {
				this.saveSensitiveConfig()
			}, 1500)
		},
		onCheckboxChanged(newValue) {
			this.state.link_preview_enabled = newValue
			this.saveConfig()
		},
		async saveConfig() {
			try {
				await axios.put(generateUrl('/apps/integration_basecamp/admin-config'), {
					values: {
						link_preview_enabled: this.state.link_preview_enabled ? '1' : '0',
					},
				})
				showSuccess(t('integration_basecamp', 'Settings saved'))
			} catch (e) {
				console.error('Failed to save settings', e)
			}
		},
		async saveSensitiveConfig() {
			try {
				await confirmPassword()
				await axios.put(generateUrl('/apps/integration_basecamp/sensitive-admin-config'), {
					values: {
						client_id: this.state.client_id,
						client_secret: this.state.client_secret,
					},
				})
				showSuccess(t('integration_basecamp', 'Settings saved'))
			} catch (e) {
				console.error('Failed to save sensitive settings', e)
			}
		},
	},
}
</script>

<style scoped lang="scss">
#basecamp_prefs {
	#basecamp-content {
		margin-left: 40px;
		max-width: 800px;
	}

	h2 {
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.basecamp-icon {
		width: 24px;
		height: 24px;
		margin-right: 8px;
	}

	.line {
		display: flex;
		align-items: center;
		margin-bottom: 8px;

		> label {
			width: 300px;
			display: flex;
			align-items: center;

			.icon {
				margin-right: 4px;
			}
		}

		> input {
			width: 300px;
		}
	}

	.settings-hint {
		color: var(--color-text-maxcontrast);
		margin-bottom: 12px;
	}
}
</style>
