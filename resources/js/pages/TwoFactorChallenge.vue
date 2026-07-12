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

			<form @submit.prevent="verifyCode"
				  class="rounded-lg border border-ink-700 bg-ink-900 p-6">
				<div class="mb-5 text-center">
					<ShieldCheck class="mx-auto mb-3 h-10 w-10 text-brass-400" />
					<h2 class="mb-1 text-xl font-semibold tracking-tight text-bone-100">{{ $t('pages.twoFactor.title') }}</h2>
					<p class="text-sm text-bone-500">
						{{ $t('pages.twoFactor.subtitle') }}
					</p>
				</div>

				<div class="mb-4">
					<ui-input v-model="code"
							  :label="$t('pages.twoFactor.authenticationCode')"
							  type="text"
							  inputmode="numeric"
							  maxlength="6"
							  class="text-center font-mono tracking-[0.3em]"
							  :rules="[rules.required, rules.sixDigits]"
							  autofocus
							  @keyup.enter="verifyCode" />
				</div>

				<!-- Demo Site 2FA Info -->
				<ui-alert v-if="isDemoSite"
						  type="info"
						  class="mb-4"
						  title="Demo 2FA Code">
					<div class="text-center text-xs">
						<p class="mb-2">For testing purposes on the demo site, use the code:</p>
						<code class="inline-block rounded border border-ink-700 bg-ink-950/60 px-3 py-1.5 font-mono text-base font-bold tracking-[0.2em]">000000</code>
					</div>
				</ui-alert>

				<div class="mb-4">
					<ui-checkbox v-model="trustDevice"
								 :label="$t('pages.twoFactor.trustDeviceLabel')" />
				</div>

				<ui-button type="submit"
						   variant="primary"
						   block
						   size="lg"
						   :loading="loading">
					{{ $t('pages.twoFactor.verify') }}
				</ui-button>

				<hr class="my-4 border-ink-700/60" />

				<div class="text-center">
					<ui-button variant="ghost"
							   size="sm"
							   @click="showRecoveryInput = !showRecoveryInput">
						{{ showRecoveryInput ? $t('pages.twoFactor.useAuthenticatorCode') : $t('pages.twoFactor.useRecoveryCodeInstead') }}
					</ui-button>
				</div>

				<div v-if="showRecoveryInput"
					 class="mt-4">
					<div class="mb-4">
						<ui-input v-model="recoveryCode"
								  :label="$t('pages.twoFactor.recoveryCode')"
								  placeholder="XXXXXXXXXX-XXXXXXXXXX"
								  class="font-mono"
								  :hint="$t('pages.twoFactor.recoveryCodeHint')"
								  @keyup.enter="verifyRecoveryCode" />
					</div>
					<ui-button variant="outline"
							   block
							   :loading="loading"
							   @click="verifyRecoveryCode">
						{{ $t('pages.twoFactor.useRecoveryCode') }}
					</ui-button>
				</div>

				<div class="mt-4 text-center">
					<ui-button variant="danger-ghost"
							   size="sm"
							   @click="cancelLogin">
						{{ $t('pages.twoFactor.cancelAndLogout') }}
					</ui-button>
				</div>
			</form>
		</div>
	</div>
</template>

<script>
import { mapActions } from 'pinia'
import { store } from '../store'
import axios from 'axios'
import { getDeviceFingerprint } from '../utils/deviceFingerprintUtil'
import { ShieldCheck } from 'lucide-vue-next'

export default {
	name: 'TwoFactorChallenge',
	components: { ShieldCheck },
	data() {
		return {
			code: '',
			recoveryCode: '',
			trustDevice: true, // Default to trusting device
			showRecoveryInput: false,
			loading: false,
			rules: {
				required: v => !!v || 'This field is required',
				sixDigits: v => /^\d{6}$/.test(v) || 'Must be 6 digits'
			}
		}
	},
	computed: {
		isDemoSite() {
			return window.location.hostname === 'clientledger.billinger.dev'
		}
	},
	methods: {
		...mapActions(store, ['showSnackbar', 'getAuthUser']),

		async verifyCode() {
			if (!this.code || this.code.length !== 6) {
				this.showSnackbar('Please enter a valid 6-digit code', 'error')
				return
			}

			this.loading = true
			try {
				// Get client fingerprint for better device trust
				const clientFingerprint = await getDeviceFingerprint()

				const response = await axios.post('/api/2fa/verify', {
					code: this.code,
					trust_device: this.trustDevice,
					client_fingerprint: clientFingerprint
				})

				// Check if 2FA setup is required (recovery code was used)
				if (response.data.requires_2fa_setup) {
					this.showSnackbar('Recovery code used. Please set up 2FA again for security.', 'warning', 5000)
					// Set a flag to allow 2FA setup without full user data
					sessionStorage.setItem('2fa_setup_pending', 'true')
					// Redirect to 2FA setup
					this.$router.push({ name: 'TwoFactorSetup' })
					return
				}

				// Get user data and redirect
				await this.getAuthUser()
				this.showSnackbar('Login successful!', 'success')
				this.$router.push('/')
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Invalid code', 'error')
			} finally {
				this.loading = false
			}
		},

		async verifyRecoveryCode() {
			if (!this.recoveryCode) {
				this.showSnackbar('Please enter a recovery code', 'error')
				return
			}

			this.loading = true
			try {
				// Get client fingerprint for better device trust
				const clientFingerprint = await getDeviceFingerprint()

				const response = await axios.post('/api/2fa/verify', {
					code: this.recoveryCode,
					trust_device: this.trustDevice,
					client_fingerprint: clientFingerprint
				})

				// Check if 2FA setup is required (recovery code was used)
				if (response.data.requires_2fa_setup) {
					this.showSnackbar('Recovery code used. Please set up 2FA again for security.', 'warning', 5000)
					// Set a flag to allow 2FA setup without full user data
					sessionStorage.setItem('2fa_setup_pending', 'true')
					// Redirect to 2FA setup
					this.$router.push({ name: 'TwoFactorSetup' })
					return
				}

				// Get user data and redirect
				await this.getAuthUser()
				this.showSnackbar('Login successful!', 'success')
				this.$router.push('/')
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Invalid recovery code', 'error')
			} finally {
				this.loading = false
			}
		},

		async cancelLogin() {
			await axios.post('/api/logout').catch(() => {})
			this.$router.push('/login')
		}
	}
}
</script>
