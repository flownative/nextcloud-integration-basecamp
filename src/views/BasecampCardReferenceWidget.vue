<!--
  Basecamp Card Reference Widget
  Compact inline preview for Basecamp card URLs
-->

<template>
	<div class="basecamp-card-reference">
		<div v-if="isError" class="error-wrapper">
			<img :src="logoUrl" class="basecamp-logo" alt="">
			<span>{{ richObject.error || t('integration_basecamp', 'Error loading Basecamp card') }}</span>
		</div>
		<a v-else
			:href="richObject.app_url || richObject.link"
			class="card-wrapper"
			target="_blank"
			rel="noopener noreferrer">
			<img :src="logoUrl" class="basecamp-logo" alt="Basecamp">
			<div class="card-content">
				<div class="card-line">
					<span class="status-dot" :class="statusClass"
						:title="statusLabel" />
					<span class="card-title">{{ richObject.title }}</span>
					<span v-if="richObject.due_on" class="due-date" :class="{ overdue: isOverdue }">
						{{ richObject.due_on }}
					</span>
				</div>
				<div class="card-line secondary">
					<span>{{ richObject.project }}</span>
					<span v-if="richObject.column" class="separator">&rsaquo;</span>
					<span v-if="richObject.column">{{ richObject.column }}</span>
					<template v-if="richObject.assignees && richObject.assignees.length > 0">
						<span class="separator">&middot;</span>
						<span v-for="(assignee, idx) in richObject.assignees" :key="assignee.name">
							{{ assignee.name }}<span v-if="idx < richObject.assignees.length - 1">,&nbsp;</span>
						</span>
					</template>
					<template v-if="richObject.comments_count > 0">
						<span class="separator">&middot;</span>
						<svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="14" height="14">
							<path fill="currentColor" d="M2 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H8l-4 3v-3H4a2 2 0 0 1-2-2V5z"/>
						</svg>
						<span>{{ richObject.comments_count }}</span>
					</template>
				</div>
			</div>
		</a>
	</div>
</template>

<script>
import { imagePath } from '@nextcloud/router'

export default {
	name: 'BasecampCardReferenceWidget',
	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: () => ({}),
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},
	computed: {
		isError() {
			return this.richObject.basecamp_type === 'card-error'
		},
		statusClass() {
			return this.richObject.completed ? 'completed' : 'active'
		},
		statusLabel() {
			return this.richObject.completed
				? t('integration_basecamp', 'Completed')
				: t('integration_basecamp', 'Open')
		},
		isOverdue() {
			if (!this.richObject.due_on) return false
			return new Date(this.richObject.due_on) < new Date()
		},
		logoUrl() {
			return imagePath('integration_basecamp', 'app.png')
		},
	},
}
</script>

<style scoped>
.basecamp-card-reference {
	width: 100%;
}

.card-wrapper {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 10px 14px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	text-decoration: none;
	color: var(--color-main-text);
	background-color: var(--color-main-background);
	transition: border-color 0.2s;
}

.card-wrapper:hover {
	border-color: var(--color-primary);
	background-color: var(--color-background-hover);
}

.basecamp-logo {
	width: 32px;
	height: 32px;
	border-radius: 6px;
	flex-shrink: 0;
}

.card-content {
	min-width: 0;
	flex: 1;
}

.card-line {
	display: flex;
	align-items: center;
	gap: 6px;
	line-height: 1.4;
}

.card-line.secondary {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin-top: 2px;
}

.status-dot {
	display: inline-block;
	width: 10px;
	height: 10px;
	border-radius: 50%;
	flex-shrink: 0;
}

.status-dot.active {
	background-color: #2da44e;
}

.status-dot.completed {
	background-color: #8250df;
}

.card-title {
	font-weight: 600;
	font-size: 14px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.due-date {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	white-space: nowrap;
	margin-left: auto;
}

.due-date.overdue {
	color: #c62828;
	font-weight: 600;
}

.icon {
	flex-shrink: 0;
	opacity: 0.6;
}

.separator {
	opacity: 0.5;
}

.error-wrapper {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 10px 14px;
	border: 1px solid var(--color-error);
	border-radius: var(--border-radius-large);
	color: var(--color-error);
	font-size: 13px;
}
</style>
