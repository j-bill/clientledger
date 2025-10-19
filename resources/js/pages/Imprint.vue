<template>
	<div class="legal-container">
		<v-container fluid class="legal-page-container">
			<v-row>
				<v-col cols="12">
					<v-card class="legal-hero" elevation="0">
						<div class="hero-gradient"></div>
						<v-card-text class="text-center position-relative">
							<div class="avatar-wrapper">
								<v-avatar size="120" class="legal-avatar elevation-8">
									<v-icon size="60" color="white">mdi-gavel</v-icon>
								</v-avatar>
							</div>
							<h1 class="text-h4 font-weight-bold mt-6 text-white">Imprint</h1>
							<p class="text-subtitle-1 text-white mb-0">Legal information and contact</p>
						</v-card-text>
					</v-card>
				</v-col>
			</v-row>

			<v-row class="mt-2">
				<v-col cols="12">
					<v-card elevation="2" class="legal-content-card">
						<v-card-text class="pa-8">
							<!-- Loading state -->
							<div v-if="loading" class="text-center py-8">
								<v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
								<p class="text-body-1 mt-4 text-medium-emphasis">Loading imprint...</p>
							</div>

							<!-- No content state -->
							<div v-else-if="!content" class="text-center py-8">
								<v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-file-document-alert</v-icon>
								<p class="text-h6 text-medium-emphasis">No imprint available</p>
								<p class="text-body-2 text-medium-emphasis">
									The administrator has not yet configured the imprint.
								</p>
							</div>

							<!-- Content -->
							<div v-else class="legal-content" v-html="content"></div>
						</v-card-text>

						<v-divider></v-divider>

						<v-card-actions class="pa-4">
							<v-btn variant="text" prepend-icon="mdi-arrow-left" @click="goBack">
								Go Back
							</v-btn>
							<v-spacer></v-spacer>
							<v-btn variant="text" :to="{ name: 'Privacy' }">
								View Privacy Notice
								<v-icon end>mdi-arrow-right</v-icon>
							</v-btn>
						</v-card-actions>
					</v-card>
				</v-col>
			</v-row>
		</v-container>
	</div>
</template>

<script>
import axios from 'axios'
import { mapActions } from 'pinia'
import { store } from '../store'

export default {
	name: 'Imprint',
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
				const response = await axios.get('/api/legal/imprint')
				this.content = response.data.content
			} catch (error) {
				console.error('Error fetching imprint:', error)
				if (error.response?.status !== 404) {
					this.showSnackbar('Failed to load imprint', 'error')
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
.legal-container {
	min-height: 100vh;
	background: linear-gradient(270deg, #0f172a, #1e293b, #3b82f6, #8b5cf6);
	background-size: 400% 400%;
	animation: gradientAnimation 30s ease infinite;
}

@keyframes gradientAnimation {
	0% {
		background-position: 0% 50%;
	}
	50% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0% 50%;
	}
}

.legal-page-container {
	max-width: 1400px;
	margin: 0 auto;
}

.legal-hero {
	position: relative;
	overflow: hidden;
	border-radius: 16px !important;
}

.hero-gradient {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
	opacity: 0.95;
}

.position-relative {
	position: relative;
}

.avatar-wrapper {
	position: relative;
	display: inline-block;
	margin-top: 20px;
}

.legal-avatar {
	border: 5px solid white;
	background: rgba(255, 255, 255, 0.2);
}

.legal-content-card {
	border-radius: 16px !important;
}

.legal-content {
	font-size: 1rem;
	line-height: 1.7;
}

.legal-content :deep(h1) {
	font-size: 2rem;
	font-weight: 700;
	margin-top: 2rem;
	margin-bottom: 1rem;
}

.legal-content :deep(h2) {
	font-size: 1.5rem;
	font-weight: 600;
	margin-top: 1.5rem;
	margin-bottom: 0.75rem;
}

.legal-content :deep(h3) {
	font-size: 1.25rem;
	font-weight: 600;
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
}

.legal-content :deep(a) {
	color: rgb(102, 126, 234);
	text-decoration: underline;
}

.legal-content :deep(a:hover) {
	color: rgb(118, 75, 162);
}
</style>
