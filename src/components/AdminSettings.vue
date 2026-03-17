<!--
  Admin settings for Basecamp integration
  Allows configuring the Basecamp OAuth Client ID and Secret
-->

<template>
	<div class="basecamp-admin-settings">
		<h2>Basecamp Integration</h2>
		<p class="settings-hint">
			{{ t('integration_basecamp', 'To connect Basecamp, register an application at') }}
			<a href="https://launchpad.37signals.com/integrations" target="_blank" rel="noopener noreferrer">
				launchpad.37signals.com/integrations
			</a>
		</p>
		<p class="settings-hint">
			{{ t('integration_basecamp', 'Use the following redirect URI:') }}
			<code>{{ redirectUri }}</code>
		</p>

		<div class="field">
			<label for="basecamp-client-id">
				{{ t('integration_basecamp', 'Client ID') }}
			</label>
			<input id="basecamp-client-id"
				v-model="state.client_id"
				type="text"
				:placeholder="t('integration_basecamp', 'Basecamp OAuth Client ID')"
				@input="onSensitiveInput">
		</div>
		<div class="field">
			<label for="basecamp-client-secret">
				{{ t('integration_basecamp', 'Client Secret') }}
			</label>
			<input id="basecamp-client-secret"
				v-model="state.client_secret"
				type="password"
				:placeholder="t('integration_basecamp', 'Basecamp OAuth Client Secret')"
				@input="onSensitiveInput">
		</div>
		<div class="field">
			<input id="basecamp-link-preview"
				v-model="state.link_preview_enabled"
				type="checkbox"
				class="checkbox"
				@change="onCheckboxChanged">
			<label for="basecamp-link-preview">
				{{ t('integration_basecamp', 'Enable Basecamp link previews') }}
			</label>
		</div>
		<div v-if="saved" class="saved-message">
			{{ t('integration_basecamp', 'Settings saved') }}
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '../../node_modules/@nextcloud/password-confirmation/dist/style.css'

export default {
	name: 'AdminSettings',
	data() {
		return {
			state: loadState('integration_basecamp', 'admin-config'),
			saved: false,
			sensitiveTimer: null,
		}
	},
	computed: {
		redirectUri() {
			return window.location.origin + generateUrl('/apps/integration_basecamp/oauth-redirect')
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
		onCheckboxChanged() {
			this.saveConfig()
		},
		async saveConfig() {
			try {
				await axios.put(generateUrl('/apps/integration_basecamp/admin-config'), {
					values: {
						link_preview_enabled: this.state.link_preview_enabled ? '1' : '0',
					},
				})
				this.showSaved()
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
				this.showSaved()
			} catch (e) {
				console.error('Failed to save sensitive settings', e)
			}
		},
		showSaved() {
			this.saved = true
			setTimeout(() => { this.saved = false }, 3000)
		},
	},
}
</script>

<style scoped>
.basecamp-admin-settings {
	padding: 20px;
}

.settings-hint {
	color: var(--color-text-maxcontrast);
	margin-bottom: 12px;
}

.settings-hint code {
	background: var(--color-background-dark);
	padding: 2px 6px;
	border-radius: 4px;
	font-size: 12px;
	user-select: all;
}

.field {
	margin-bottom: 12px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.field label {
	min-width: 150px;
}

.field input[type="text"],
.field input[type="password"] {
	width: 400px;
}

.saved-message {
	color: var(--color-success);
	margin-top: 8px;
}
</style>
