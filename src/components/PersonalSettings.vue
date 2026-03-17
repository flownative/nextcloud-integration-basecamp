<!--
  Personal settings for Basecamp integration
  Allows users to connect/disconnect their Basecamp account via OAuth
-->

<template>
	<div class="basecamp-personal-settings">
		<h2>
			<img :src="iconUrl" class="basecamp-icon" alt="">
			Basecamp
		</h2>

		<div v-if="!state.client_id_configured" class="warning">
			{{ t('integration_basecamp', 'Basecamp OAuth has not been configured by the administrator. Ask your admin to set up the Basecamp integration in the admin settings.') }}
		</div>

		<template v-else>
			<div v-if="connected" class="connected-info">
				<span class="connected-badge">
					{{ t('integration_basecamp', 'Connected as {name}', { name: state.user_name }) }}
				</span>
				<button class="disconnect-button" @click="disconnect">
					{{ t('integration_basecamp', 'Disconnect') }}
				</button>
			</div>
			<div v-else>
				<button class="connect-button" @click="connect">
					{{ t('integration_basecamp', 'Connect to Basecamp') }}
				</button>
			</div>
		</template>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'

export default {
	name: 'PersonalSettings',
	data() {
		return {
			state: loadState('integration_basecamp', 'personal-config'),
		}
	},
	computed: {
		connected() {
			return this.state.token !== ''
		},
		iconUrl() {
			return imagePath('integration_basecamp', 'app.png')
		},
	},
	mounted() {
		// Check URL params for OAuth result
		const urlParams = new URLSearchParams(window.location.search)
		if (urlParams.get('basecampToken') === 'success') {
			window.history.replaceState({}, document.title, window.location.pathname)
			// Reload to get fresh state
			window.location.reload()
		}
	},
	methods: {
		connect() {
			// Generate a random state parameter
			const state = Array.from(crypto.getRandomValues(new Uint8Array(16)))
				.map(b => b.toString(16).padStart(2, '0'))
				.join('')

			// Save the state server-side
			axios.put(generateUrl('/apps/integration_basecamp/config'), {
				values: { oauth_state: state },
			}).then(() => {
				// Redirect to Basecamp OAuth
				const oauthUrl = this.state.oauth_url + '&state=' + state
				window.location.href = oauthUrl
			})
		},
		async disconnect() {
			try {
				await axios.put(generateUrl('/apps/integration_basecamp/config/disconnect'))
				this.state.token = ''
				this.state.user_name = ''
			} catch (e) {
				console.error('Failed to disconnect', e)
			}
		},
	},
}
</script>

<style scoped>
.basecamp-personal-settings {
	padding: 20px;
}

h2 {
	display: flex;
	align-items: center;
	gap: 8px;
}

.basecamp-icon {
	width: 24px;
	height: 24px;
}

.warning {
	color: var(--color-warning);
	margin: 12px 0;
}

.connected-info {
	display: flex;
	align-items: center;
	gap: 12px;
	margin: 12px 0;
}

.connected-badge {
	color: var(--color-success);
	font-weight: 600;
}

.connect-button {
	margin: 12px 0;
}
</style>
