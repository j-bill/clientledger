<template>
	<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
		<h1 class="text-2xl font-semibold tracking-tight">Privacy Notice</h1>
		<p class="mt-1 text-sm text-bone-500">How we handle your data</p>

		<div class="mt-8">
			<!-- Loading state -->
			<div v-if="loading" class="flex flex-col items-center py-12">
				<ui-spinner :size="32" />
				<p class="mt-4 text-sm text-bone-500">Loading privacy notice...</p>
			</div>

			<!-- No content state -->
			<div v-else-if="!content" class="py-12 text-center">
				<p class="text-bone-300">No privacy notice available</p>
				<p class="mt-1 text-sm text-bone-500">
					The administrator has not yet configured the privacy notice.
				</p>
			</div>

			<!-- Content -->
			<div v-else class="legal-content text-bone-300" v-html="content"></div>
		</div>

		<hr class="my-8 border-ink-700/60" />

		<div class="flex items-center justify-between">
			<ui-button variant="ghost" :icon="ArrowLeft" @click="goBack">
				Go Back
			</ui-button>
			<ui-button variant="ghost" @click="$router.push({ name: 'Imprint' })">
				View Imprint
				<ArrowRight class="h-4 w-4" />
			</ui-button>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import { mapActions } from 'pinia'
import { store } from '../store'
import { ArrowLeft, ArrowRight } from 'lucide-vue-next'

export default {
	name: 'Privacy',
	components: { ArrowRight },
	setup() {
		return { ArrowLeft }
	},
	data() {
		return {
			content: '',
			loading: false
		}
	},
	mounted() {
		this.fetchContent()
	},
	methods: {
		...mapActions(store, ['showSnackbar']),

		async fetchContent() {
			this.loading = true
			try {
				const response = await axios.get('/api/legal/privacy')
				this.content = response.data.content
			} catch (error) {
				console.error('Error fetching privacy notice:', error)
				if (error.response?.status !== 404) {
					this.showSnackbar('Failed to load privacy notice', 'error')
				}
			} finally {
				this.loading = false
			}
		},

		goBack() {
			if (window.history.length > 1) {
				this.$router.go(-1)
			} else {
				this.$router.push('/')
			}
		}
	}
}
</script>

<style scoped>
.legal-content {
	font-size: 0.9375rem;
	line-height: 1.7;
}

.legal-content :deep(h1) {
	font-size: 1.5rem;
	font-weight: 600;
	color: var(--color-bone-100);
	margin-top: 2rem;
	margin-bottom: 1rem;
}

.legal-content :deep(h2) {
	font-size: 1.25rem;
	font-weight: 600;
	color: var(--color-bone-100);
	margin-top: 1.5rem;
	margin-bottom: 0.75rem;
}

.legal-content :deep(h3) {
	font-size: 1.0625rem;
	font-weight: 600;
	color: var(--color-bone-100);
	margin-top: 1.25rem;
	margin-bottom: 0.5rem;
}

.legal-content :deep(p) {
	margin-bottom: 1rem;
}

.legal-content :deep(ul),
.legal-content :deep(ol) {
	margin-bottom: 1rem;
	padding-left: 2rem;
}

.legal-content :deep(li) {
	margin-bottom: 0.5rem;
}

.legal-content :deep(strong) {
	font-weight: 600;
	color: var(--color-bone-100);
}

.legal-content :deep(a) {
	color: var(--color-brass-400);
	text-decoration: underline;
}

.legal-content :deep(a:hover) {
	color: var(--color-brass-300);
}
</style>
