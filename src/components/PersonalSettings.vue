<!--
  Personal settings for Basecamp integration
  Allows users to connect/disconnect their Basecamp account via OAuth
-->

<template>
	<div id="basecamp_prefs" class="section">
		<h2>
			<img :src="iconUrl" class="basecamp-icon" alt="">
			{{ t('integration_basecamp', 'Basecamp integration') }}
		</h2>
		<div id="basecamp-content">
			<div v-if="!state.client_id_configured" class="warning">
				{{ t('integration_basecamp', 'Basecamp OAuth has not been configured by the administrator. Ask your admin to set up the Basecamp integration in the admin settings.') }}
			</div>

			<template v-else>
				<div v-if="connected" class="connection-status">
					<label>
						<CheckIcon :size="20" class="icon" />
						{{ t('integration_basecamp', 'Connected as {name}', { name: state.user_name }) }}
					</label>
					<NcButton @click="disconnect">
						<template #icon>
							<CloseIcon :size="20" />
						</template>
						{{ t('integration_basecamp', 'Disconnect from Basecamp') }}
					</NcButton>
				</div>
				<div v-else>
					<NcButton @click="connect">
						<template #icon>
							<OpenInNewIcon :size="20" />
						</template>
						{{ t('integration_basecamp', 'Connect to Basecamp') }}
					</NcButton>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'

import NcButton from '@nextcloud/vue/components/NcButton'

import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'

export default {
	name: 'PersonalSettings',
	components: {
		CheckIcon,
		CloseIcon,
		OpenInNewIcon,
		NcButton,
	},
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
			return imagePath('integration_basecamp', 'app-bw.png')
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

	.warning {
		color: var(--color-warning);
		margin: 12px 0;
	}

	.connection-status {
		display: flex;
		flex-direction: column;
		gap: 8px;

		label {
			display: flex;
			align-items: center;
		}
	}
}
</style>
