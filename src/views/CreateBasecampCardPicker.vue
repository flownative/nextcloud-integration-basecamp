<template>
	<div class="create-basecamp-card" ref="wrapper">
		<h2>{{ t('integration_basecamp', 'Create a Basecamp card') }}</h2>

		<div class="form-field">
			<label for="bc-title">{{ t('integration_basecamp', 'Title') }}</label>
			<input
				id="bc-title"
				ref="titleInput"
				v-model="title"
				type="text"
				:placeholder="t('integration_basecamp', 'Card title')"
				:disabled="creating"
				@keydown.enter.prevent="onSubmit">
		</div>

		<div class="form-row">
			<div class="form-field half">
				<label for="bc-project">{{ t('integration_basecamp', 'Project') }}</label>
				<select id="bc-project" v-model="selectedProjectId" :disabled="loadingProjects || creating" @change="onProjectSelected">
					<option value="">{{ loadingProjects ? t('integration_basecamp', 'Loading…') : t('integration_basecamp', 'Select project') }}</option>
					<option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
				</select>
			</div>

			<div class="form-field half">
				<label for="bc-column">{{ t('integration_basecamp', 'Column') }}</label>
				<select id="bc-column" v-model="selectedColumnId" :disabled="!selectedProjectId || loadingColumns || creating">
					<option value="">{{ loadingColumns ? t('integration_basecamp', 'Loading…') : t('integration_basecamp', 'Select column') }}</option>
					<option v-for="col in columns" :key="col.id" :value="col.id">{{ col.title }}</option>
				</select>
			</div>
		</div>

		<div class="form-field">
			<label for="bc-assignees">{{ t('integration_basecamp', 'Assignees') }}</label>
			<select id="bc-assignees" v-model="selectedAssigneeIds" :disabled="!selectedProjectId || loadingPeople || creating" multiple>
				<option v-for="person in people" :key="person.id" :value="person.id">{{ person.name }}</option>
			</select>
		</div>

		<div class="form-field">
			<label for="bc-due">{{ t('integration_basecamp', 'Due date') }}</label>
			<input id="bc-due" v-model="dueOn" type="date" :disabled="creating">
		</div>

		<div class="form-field">
			<label for="bc-description">{{ t('integration_basecamp', 'Description') }}</label>
			<textarea
				id="bc-description"
				v-model="description"
				rows="3"
				:placeholder="t('integration_basecamp', 'Optional description')"
				:disabled="creating" />
		</div>

		<p v-if="error" class="error-message">{{ error }}</p>

		<div class="form-actions">
			<button class="cancel-button" @click="onCancel" :disabled="creating">
				{{ t('integration_basecamp', 'Cancel') }}
			</button>
			<button class="submit-button primary" @click="onSubmit" :disabled="!canSubmit || creating">
				<span v-if="creating" class="spinner" />
				{{ creating ? t('integration_basecamp', 'Creating…') : t('integration_basecamp', 'Create card') }}
			</button>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'CreateBasecampCardPicker',
	props: {
		providerId: {
			type: [String, Number],
			default: '',
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			title: '',
			description: '',
			dueOn: '',
			selectedAccountId: '',
			selectedProjectId: '',
			selectedCardTableId: '',
			selectedColumnId: '',
			selectedAssigneeIds: [],
			accounts: [],
			projects: [],
			cardTables: [],
			columns: [],
			people: [],
			loadingProjects: false,
			loadingColumns: false,
			loadingPeople: false,
			creating: false,
			error: '',
		}
	},
	computed: {
		canSubmit() {
			return this.title.trim() !== '' && this.selectedProjectId !== '' && this.selectedColumnId !== ''
		},
	},
	async mounted() {
		await this.loadAccounts()
		this.$nextTick(() => {
			this.$refs.titleInput?.focus()
		})
	},
	methods: {
		async loadAccounts() {
			try {
				const response = await axios.get(generateUrl('/apps/integration_basecamp/api/accounts'))
				this.accounts = response.data
				if (this.accounts.length === 1) {
					this.selectedAccountId = String(this.accounts[0].id)
					await this.loadProjects()
				}
			} catch (e) {
				this.error = t('integration_basecamp', 'Failed to load Basecamp accounts. Please check your connection.')
			}
		},
		async loadProjects() {
			if (!this.selectedAccountId) return
			this.loadingProjects = true
			this.projects = []
			this.cardTables = []
			this.columns = []
			this.people = []
			this.selectedProjectId = ''
			this.selectedCardTableId = ''
			this.selectedColumnId = ''
			try {
				const url = generateUrl('/apps/integration_basecamp/api/accounts/{accountId}/projects', { accountId: this.selectedAccountId })
				const response = await axios.get(url)
				this.projects = response.data
			} catch (e) {
				this.error = t('integration_basecamp', 'Failed to load projects')
			} finally {
				this.loadingProjects = false
			}
		},
		async onProjectSelected() {
			this.cardTables = []
			this.columns = []
			this.people = []
			this.selectedCardTableId = ''
			this.selectedColumnId = ''
			this.selectedAssigneeIds = []
			if (!this.selectedProjectId) return

			await Promise.all([
				this.loadCardTables(),
				this.loadPeople(),
			])
		},
		async loadCardTables() {
			this.loadingColumns = true
			try {
				const url = generateUrl('/apps/integration_basecamp/api/accounts/{accountId}/projects/{projectId}/card-tables', {
					accountId: this.selectedAccountId,
					projectId: this.selectedProjectId,
				})
				const response = await axios.get(url)
				this.cardTables = response.data
				// Auto-select if only one card table
				if (this.cardTables.length === 1) {
					this.selectedCardTableId = String(this.cardTables[0].id)
					await this.loadColumns()
				}
			} catch (e) {
				this.error = t('integration_basecamp', 'Failed to load card tables')
				this.loadingColumns = false
			}
		},
		async loadColumns() {
			if (!this.selectedCardTableId) {
				this.loadingColumns = false
				return
			}
			try {
				const url = generateUrl('/apps/integration_basecamp/api/accounts/{accountId}/projects/{projectId}/card-tables/{cardTableId}/columns', {
					accountId: this.selectedAccountId,
					projectId: this.selectedProjectId,
					cardTableId: this.selectedCardTableId,
				})
				const response = await axios.get(url)
				this.columns = response.data
			} catch (e) {
				this.error = t('integration_basecamp', 'Failed to load columns')
			} finally {
				this.loadingColumns = false
			}
		},
		async loadPeople() {
			this.loadingPeople = true
			try {
				const url = generateUrl('/apps/integration_basecamp/api/accounts/{accountId}/projects/{projectId}/people', {
					accountId: this.selectedAccountId,
					projectId: this.selectedProjectId,
				})
				const response = await axios.get(url)
				this.people = response.data
			} catch (e) {
				// People loading is not critical
			} finally {
				this.loadingPeople = false
			}
		},
		async onSubmit() {
			if (!this.canSubmit) return
			this.creating = true
			this.error = ''
			try {
				const response = await axios.post(generateUrl('/apps/integration_basecamp/api/cards'), {
					accountId: this.selectedAccountId,
					projectId: String(this.selectedProjectId),
					columnId: String(this.selectedColumnId),
					title: this.title.trim(),
					content: this.description.trim() || null,
					dueOn: this.dueOn || null,
					assigneeIds: this.selectedAssigneeIds.length > 0 ? this.selectedAssigneeIds : null,
				})
				const appUrl = response.data.app_url
				if (appUrl) {
					this.$refs.wrapper.dispatchEvent(new CustomEvent('submit', { bubbles: true, detail: appUrl }))
				} else {
					this.error = t('integration_basecamp', 'Card created but no URL returned')
				}
			} catch (e) {
				this.error = e.response?.data?.error || t('integration_basecamp', 'Failed to create card')
			} finally {
				this.creating = false
			}
		},
		onCancel() {
			this.$refs.wrapper.dispatchEvent(new CustomEvent('cancel', { bubbles: true }))
		},
	},
}
</script>

<style scoped>
.create-basecamp-card {
	padding: 16px 20px;
	min-width: 400px;
	max-width: 600px;
}

h2 {
	font-size: 18px;
	font-weight: 700;
	margin: 0 0 16px 0;
	text-align: center;
}

.form-field {
	margin-bottom: 12px;
}

.form-field label {
	display: block;
	font-size: 13px;
	font-weight: 600;
	margin-bottom: 4px;
	color: var(--color-text-maxcontrast);
}

.form-row {
	display: flex;
	gap: 12px;
}

.form-field.half {
	flex: 1;
	min-width: 0;
}

.form-field input[type="text"],
.form-field input[type="date"],
.form-field select,
.form-field textarea {
	width: 100%;
	box-sizing: border-box;
	padding: 8px 10px;
	border: 2px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
}

.form-field select[multiple] {
	height: auto;
	min-height: 60px;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
	border-color: var(--color-primary);
	outline: none;
}

.form-field textarea {
	resize: vertical;
}

.error-message {
	color: var(--color-error);
	font-size: 13px;
	margin: 8px 0;
}

.form-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 16px;
}

.cancel-button,
.submit-button {
	padding: 8px 20px;
	border-radius: var(--border-radius-pill);
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	border: 2px solid var(--color-border-dark);
	background: var(--color-main-background);
	color: var(--color-main-text);
}

.submit-button.primary {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
}

.submit-button.primary:hover:not(:disabled) {
	background: var(--color-primary-hover);
}

.submit-button:disabled,
.cancel-button:disabled {
	opacity: 0.5;
	cursor: not-allowed;
}

.spinner {
	display: inline-block;
	width: 14px;
	height: 14px;
	border: 2px solid currentColor;
	border-right-color: transparent;
	border-radius: 50%;
	animation: spin 0.6s linear infinite;
	margin-right: 6px;
	vertical-align: middle;
}

@keyframes spin {
	to { transform: rotate(360deg); }
}
</style>
