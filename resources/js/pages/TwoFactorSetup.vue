<template>
	<div class="flex min-h-screen items-center justify-center px-4 py-8">
		<div class="w-full max-w-lg">
			<!-- Wordmark -->
			<div class="mb-6 flex items-center justify-center gap-2">
				<span class="flex h-7 w-7 items-center justify-center rounded-md bg-brass-500 font-mono text-[13px] font-semibold text-ink-950">CL</span>
				<span class="text-[15px] font-semibold tracking-tight text-bone-100">
					Client<span class="text-brass-400">ledger</span>
				</span>
			</div>

			<div class="rounded-lg border border-ink-700 bg-ink-900 p-6">
				<h2 class="mb-6 flex items-center gap-2 text-xl font-semibold tracking-tight text-bone-100">
					<ShieldCheck class="h-5 w-5 text-brass-400" />
					{{ $t('pages.twoFactor.setupTitle') }}
				</h2>

				<ui-stepper v-model="step"
							:steps="[$t('pages.twoFactor.step1Title'), $t('pages.twoFactor.step2Title'), $t('pages.twoFactor.step3Title')]">
					<template #default="{ step: currentStep }">
						<!-- Step 1: Generate QR Code -->
						<div v-if="currentStep === 1" class="py-6 text-center">
							<p class="mb-4 text-sm text-bone-300">
								{{ $t('pages.twoFactor.setupDescription') }}
							</p>
							<div class="flex flex-wrap justify-center gap-2">
								<ui-button variant="primary"
										   size="lg"
										   :loading="loading"
										   @click="generateQRCode">
									{{ $t('pages.twoFactor.generateQRCode') }}
								</ui-button>
							</div>

							<!-- Demo Site Info -->
							<ui-alert v-if="isAdminDemo"
									  type="info"
									  class="mt-4 text-left"
									  title="Demo Account Setup">
								<div class="text-xs">
									When setting up 2FA on the demo site, use code <code class="mx-1 rounded border border-ink-700 bg-ink-950/60 px-1.5 py-0.5 font-mono font-bold">000000</code> for testing purposes.
								</div>
							</ui-alert>
						</div>

						<!-- Step 2: Scan QR Code and Verify -->
						<div v-else-if="currentStep === 2" class="text-center">
							<p class="mb-4 text-sm text-bone-300">
								{{ $t('pages.twoFactor.scanQRCode') }}
							</p>
							<div v-if="qrCode"
								 class="mx-auto mb-4 w-fit rounded-lg bg-white p-4"
								 v-html="qrCode"></div>

							<div class="mb-4">
								<p class="mb-2 text-xs text-bone-500">
									<strong>{{ $t('pages.twoFactor.manualEntry') }}:</strong><br>
									{{ $t('pages.twoFactor.manualEntryHint') }}
								</p>
								<code class="block break-all rounded-md border border-ink-700 bg-ink-950 px-4 py-3 text-center font-mono text-sm tracking-[0.1em] text-bone-100">{{ secret }}</code>
							</div>

							<!-- Demo Site Verification Info -->
							<ui-alert v-if="isAdminDemo"
									  type="info"
									  class="mb-4 text-left"
									  title="Demo Verification Code">
								<div class="text-center text-xs">
									<p class="mb-2">For testing on the demo site, use the verification code:</p>
									<code class="inline-block rounded border border-ink-700 bg-ink-950/60 px-3 py-1.5 font-mono text-base font-bold tracking-[0.2em]">000000</code>
								</div>
							</ui-alert>

							<div class="mb-4">
								<ui-input v-model="verificationCode"
										  :label="$t('pages.twoFactor.enterSixDigitCode')"
										  inputmode="numeric"
										  maxlength="6"
										  class="text-center font-mono tracking-[0.3em]"
										  :rules="[rules.required, rules.sixDigits]"
										  @keyup.enter="verifyCode" />
							</div>

							<div class="flex items-center justify-between">
								<ui-button variant="outline"
										   @click="step = 1">
									{{ $t('common.back') || 'Back' }}
								</ui-button>
								<ui-button variant="primary"
										   :loading="loading"
										   @click="verifyCode">
									{{ $t('pages.twoFactor.verifyAndContinue') }}
								</ui-button>
							</div>
						</div>

						<!-- Step 3: Save Recovery Codes -->
						<div v-else-if="currentStep === 3">
							<ui-alert type="warning" class="mb-4">
								<strong>{{ $t('common.important') || 'Important' }}:</strong> {{ $t('pages.twoFactor.recoveryCodesWarning') }}
							</ui-alert>

							<div class="mb-4 rounded-md border border-ink-700 bg-ink-950 p-4">
								<div class="grid grid-cols-2 gap-2">
									<div v-for="(code, index) in recoveryCodes"
										 :key="index"
										 class="rounded bg-ink-900 px-2 py-1.5 text-center font-mono text-[13px] text-bone-100">
										{{ code }}
									</div>
								</div>
							</div>

							<div class="mb-4 flex items-center justify-between">
								<ui-button variant="outline"
										   :icon="Copy"
										   @click="copyRecoveryCodes">
									{{ $t('pages.profile.copyAll') || 'Copy All' }}
								</ui-button>
								<ui-button variant="outline"
										   :icon="Download"
										   @click="downloadRecoveryCodes">
									{{ $t('common.download') }}
								</ui-button>
							</div>

							<div class="mb-4">
								<ui-checkbox v-model="confirmedSaved"
											 :label="$t('pages.twoFactor.confirmSavedRecoveryCodes')" />
							</div>

							<ui-button variant="primary"
									   block
									   size="lg"
									   :disabled="!confirmedSaved"
									   @click="completeSetup">
								{{ $t('pages.twoFactor.completeSetup') }}
							</ui-button>
						</div>
					</template>
				</ui-stepper>
			</div>
		</div>
	</div>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'
import { getDeviceFingerprint } from '../utils/deviceFingerprintUtil'
import { ShieldCheck, Copy, Download } from 'lucide-vue-next'

export default {
	name: 'TwoFactorSetup',
	components: { ShieldCheck },
	setup() {
		return { Copy, Download }
	},
	data() {
		return {
			step: 1,
			qrCode: null,
			secret: null,
			verificationCode: '',
			recoveryCodes: [],
			confirmedSaved: false,
			loading: false,
			rules: {
				required: v => !!v || 'This field is required',
				sixDigits: v => /^\d{6}$/.test(v) || 'Must be 6 digits',
				mustConfirm: v => v === true || 'You must confirm you have saved the recovery codes'
			}
		}
	},
	computed: {
		...mapState(store, ['user']),
		isAdminDemo() {
			return window.location.hostname === 'clientledger.billinger.dev'
		}
	},
	async mounted() {
		// Clear the pending flag if it exists (recovery code flow)
		if (sessionStorage.getItem('2fa_setup_pending')) {
			sessionStorage.removeItem('2fa_setup_pending')
		}
	},
	methods: {
		...mapActions(store, ['showSnackbar', 'getAuthUser']),

		async generateQRCode() {
			this.loading = true
			try {
				const response = await axios.post('/api/2fa/enable')
				this.qrCode = response.data.qr_code
				this.secret = response.data.secret
				this.step = 2
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Failed to generate QR code', 'error')
			} finally {
				this.loading = false
			}
		},

		async skipSetup() {
			// For demo purposes, auto-verify with the bypass code
			this.loading = true
			try {
				// First generate the QR code (creates the secret)
				const enableResponse = await axios.post('/api/2fa/enable')

				// Get client fingerprint
				const clientFingerprint = await getDeviceFingerprint()

				// Then verify with the bypass code
				const confirmResponse = await axios.post('/api/2fa/confirm', {
					code: '000000',
					client_fingerprint: clientFingerprint
				})

				this.recoveryCodes = confirmResponse.data.recovery_codes
				this.step = 3
				this.confirmedSaved = true
				this.showSnackbar('2FA setup completed successfully!', 'success')

				// Refresh user data
				await this.getAuthUser()

				// Small delay and redirect
				await new Promise(resolve => setTimeout(resolve, 500))
				this.$router.push('/')
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Failed to complete setup', 'error')
			} finally {
				this.loading = false
			}
		},

		async verifyCode() {
			if (!this.verificationCode || this.verificationCode.length !== 6) {
				this.showSnackbar('Please enter a valid 6-digit code', 'error')
				return
			}

			this.loading = true
			try {
				// Get client fingerprint for better device trust
				const clientFingerprint = await getDeviceFingerprint()

				const response = await axios.post('/api/2fa/confirm', {
					code: this.verificationCode,
					client_fingerprint: clientFingerprint
				})
				this.recoveryCodes = response.data.recovery_codes
				this.step = 3
				this.showSnackbar('2FA verified successfully!', 'success')
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Invalid verification code', 'error')
			} finally {
				this.loading = false
			}
		},

		copyRecoveryCodes() {
			const codesText = this.recoveryCodes.join('\n')
			navigator.clipboard.writeText(codesText)
			this.showSnackbar('Recovery codes copied to clipboard', 'success')
		},

		downloadRecoveryCodes() {
			const codesText = this.recoveryCodes.join('\n')
			const blob = new Blob([codesText], { type: 'text/plain' })
			const url = window.URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = url
			a.download = '2fa-recovery-codes.txt'
			document.body.appendChild(a)
			a.click()
			document.body.removeChild(a)
			window.URL.revokeObjectURL(url)
			this.showSnackbar('Recovery codes downloaded', 'success')
		},

		async completeSetup() {
			console.log('[TwoFactorSetup] Completing setup...')

			// Refresh user data to get updated 2FA status
			console.log('[TwoFactorSetup] Fetching user data...')
			await this.getAuthUser()
			console.log('[TwoFactorSetup] User data fetched')

			// Mark in local storage that 2FA was just completed
			// to prevent immediate re-verification
			sessionStorage.setItem('2fa_just_completed', 'true')

			this.showSnackbar('2FA setup completed successfully!', 'success')

			// Small delay to ensure session is established
			await new Promise(resolve => setTimeout(resolve, 500))

			console.log('[TwoFactorSetup] Redirecting to home...')
			this.$router.push('/')
		}
	}
}
</script>
