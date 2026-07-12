<template>
	<div class="flex min-h-screen items-center justify-center px-4">
		<div class="w-full max-w-sm">
			<!-- Wordmark -->
			<div class="mb-6 flex items-center justify-center gap-2">
				<span class="flex h-7 w-7 items-center justify-center rounded-md bg-brass-500 font-mono text-[13px] font-semibold text-ink-950">CL</span>
				<span class="text-[15px] font-semibold tracking-tight text-bone-100">
					Client<span class="text-brass-400">ledger</span>
				</span>
			</div>

			<form @submit.prevent="handleLogin"
				  class="rounded-lg border border-ink-700 bg-ink-900 p-6">
				<!-- Company Logo -->
				<div class="mb-4 flex items-center justify-center" v-if="companyLogo">
					<img :src="companyLogo" alt="Company Logo" class="max-h-20 max-w-[200px] object-contain" />
				</div>
				<h2 class="mb-5 text-xl font-semibold tracking-tight text-bone-100">{{ $t('pages.login.login') }}</h2>
				<div class="space-y-4">
					<ui-input v-model="form.email"
							  data-test="login-email"
							  type="email"
							  :icon="Mail"
							  :label="$t('pages.login.email')"
							  required />
					<ui-input v-model="form.password"
							  data-test="login-password"
							  type="password"
							  :icon="Lock"
							  :label="$t('pages.login.password')"
							  required />
				</div>

				<!-- Demo Account Info (only shown on demo site) -->
				<ui-alert v-if="isDemoSite"
						  type="info"
						  class="mt-4"
						  title="Demo Admin Account">
					<div class="space-y-1.5 text-xs">
						<div class="flex items-center gap-2">
							<span class="min-w-[70px] font-medium">Email:</span>
							<code class="rounded border border-ink-700 bg-ink-950/60 px-1.5 py-0.5 font-mono">admin@admin.de</code>
						</div>
						<div class="flex items-center gap-2">
							<span class="min-w-[70px] font-medium">Password:</span>
							<code class="rounded border border-ink-700 bg-ink-950/60 px-1.5 py-0.5 font-mono">adminadmin</code>
						</div>
					</div>
					<hr class="my-2 border-ink-700/60" />
					<div class="text-xs">
						<strong>2FA Code:</strong> Use <code class="mx-1 rounded border border-ink-700 bg-ink-950/60 px-1.5 py-0.5 font-mono font-bold">000000</code> for testing
					</div>
				</ui-alert>

				<ui-button type="submit"
						   data-test="btn-login"
						   variant="primary"
						   block
						   :loading="loading"
						   class="mt-5">
					{{ $t('pages.login.login') }}
				</ui-button>

				<!-- Legal Links Footer -->
				<div class="mt-6 flex items-center justify-between border-t border-ink-700/60 pt-4">
					<router-link :to="{ name: 'Privacy' }" class="text-xs text-bone-500 transition-colors hover:text-bone-300 hover:underline">
						{{ $t('pages.login.privacyNotice') }}
					</router-link>
					<router-link :to="{ name: 'Imprint' }" class="text-xs text-bone-500 transition-colors hover:text-bone-300 hover:underline">
						{{ $t('pages.login.imprint') }}
					</router-link>
				</div>
			</form>
		</div>
	</div>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'
import { Mail, Lock } from 'lucide-vue-next'

export default {
	name: 'Login',
	setup() {
		return { Mail, Lock }
	},
	data() {
		return {
			form: {
				email: '',
				password: ''
			},
			loading: false,
			companyLogo: null
		}
	},
	computed: {
		...mapState(store, ['settings']),
		isDemoSite() {
			return window.location.hostname === 'clientledger.billinger.dev'
		}
	},
	async created() {
		// Fetch settings to get the company logo
		try {
			const response = await axios.get('/api/settings/public')
			const settings = response.data
			this.companyLogo = settings.company_logo || null
		} catch (error) {
			console.error('Error fetching settings:', error)
		}
	},
	methods: {
		...mapActions(store, ['login']),
		async handleLogin() {
			this.loading = true
			try {
				const result = await this.login(this.form.email, this.form.password)

				// Handle 2FA verification required
				if (result?.requires_2fa_verification) {
					this.$router.push({
						name: 'TwoFactorChallenge',
						query: { email: result.email }
					})
					return
				}

				// Handle 2FA setup required
				if (result?.requires_2fa_setup) {
					this.$router.push({ name: 'TwoFactorSetup' })
					return
				}

				// Normal login success
				this.$router.push('/')
			} catch (error) {
				console.error('Login failed:', error)
			} finally {
				this.loading = false
			}
		}
	}
}
</script>
