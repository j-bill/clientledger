<template>
	<div class="two-factor-setup-container">
		<v-card class="setup-card"
				max-width="600"
				elevation="8">
			<v-card-title class="text-h5 pa-6">
				<v-icon left
						color="primary"
						size="large">mdi-shield-lock</v-icon>
				Set Up Two-Factor Authentication
			</v-card-title>

			<v-card-text class="pa-6">
				<v-stepper v-model="step"
						   elevation="0">
					<v-stepper-header>
						<v-stepper-item :complete="step > 1"
										:value="1"
										title="Generate QR Code"></v-stepper-item>
						<v-divider></v-divider>
						<v-stepper-item :complete="step > 2"
										:value="2"
										title="Verify Code"></v-stepper-item>
						<v-divider></v-divider>
						<v-stepper-item :value="3"
										title="Save Recovery Codes"></v-stepper-item>
					</v-stepper-header>

					<v-stepper-window>
					<!-- Step 1: Generate QR Code -->
					<v-stepper-window-item :value="1">
						<div class="text-center py-6">
							<p class="mb-4">
								Two-Factor Authentication adds an extra layer of security to your account.
								You'll need an authenticator app like Google Authenticator or Authy.
							</p>
							<div class="d-flex gap-2 justify-center flex-wrap">
								<v-btn color="primary"
									   size="large"
									   :loading="loading"
									   @click="generateQRCode">
									Generate QR Code
								</v-btn>
								<v-btn v-if="isAdminDemo"
									   variant="outlined"
									   size="large"
									   @click="skipSetup">
									Skip Setup (Demo)
								</v-btn>
							</div>
						</div>
					</v-stepper-window-item>						<!-- Step 2: Scan QR Code and Verify -->
						<v-stepper-window-item :value="2">
							<div class="text-center">
								<p class="mb-4">
									Scan this QR code with your authenticator app:
								</p>
								<div v-if="qrCode"
									 class="qr-code-container mb-4"
									 v-html="qrCode"></div>
								
								<div class="manual-entry-container mb-4">
									<p class="text-caption text-medium-emphasis text-center mb-2">
										<strong>Manual Entry:</strong><br>
										If you can't scan the QR code, enter this key manually:
									</p>
									<code class="manual-key">{{ secret }}</code>
								</div>

								<v-text-field v-model="verificationCode"
											  label="Enter 6-digit code"
											  variant="outlined"
											  :rules="[rules.required, rules.sixDigits]"
											  maxlength="6"
											  @keyup.enter="verifyCode"
											  class="mb-4"></v-text-field>

								<div class="d-flex">
									<v-btn variant="outlined"
										   @click="step = 1">
										Back
									</v-btn>
									<v-spacer></v-spacer>
									<v-btn color="primary"
										   :loading="loading"
										   @click="verifyCode">
										Verify & Continue
									</v-btn>
								</div>
							</div>
						</v-stepper-window-item>

						<!-- Step 3: Save Recovery Codes -->
						<v-stepper-window-item :value="3">
							<div>
								<v-alert type="warning"
										 variant="tonal"
										 class="mb-4">
									<strong>Important:</strong> Save these recovery codes in a safe place. 
									You can use them to access your account if you lose your phone.
								</v-alert>

								<v-card variant="outlined"
										class="recovery-codes-card mb-4">
									<v-card-text>
										<div class="recovery-codes">
											<div v-for="(code, index) in recoveryCodes"
												 :key="index"
												 class="recovery-code">
												{{ code }}
											</div>
										</div>
									</v-card-text>
								</v-card>

								<div class="d-flex justify-space-between align-center mb-4">
									<v-btn variant="outlined"
										   prepend-icon="mdi-content-copy"
										   @click="copyRecoveryCodes">
										Copy All
									</v-btn>
									<v-btn variant="outlined"
										   prepend-icon="mdi-download"
										   @click="downloadRecoveryCodes">
										Download
									</v-btn>
								</div>

								<v-checkbox v-model="confirmedSaved"
											label="I have saved my recovery codes in a safe place"
											:rules="[rules.mustConfirm]"></v-checkbox>

								<v-btn color="success"
									   block
									   size="large"
									   :disabled="!confirmedSaved"
									   @click="completeSetup">
									Complete Setup
								</v-btn>
							</div>
						</v-stepper-window-item>
					</v-stepper-window>
				</v-stepper>
			</v-card-text>
		</v-card>
	</div>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'

export default {
	name: 'TwoFactorSetup',
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
			return this.user?.email === 'admin@admin.de'
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
				
				// Then verify with the bypass code
				const confirmResponse = await axios.post('/api/2fa/confirm', {
					code: '000000'
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
				const response = await axios.post('/api/2fa/confirm', {
					code: this.verificationCode
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

<style scoped>
.two-factor-setup-container {
	display: flex;
	justify-content: center;
	align-items: center;
	min-height: 100vh;
	padding: 2rem;
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

.setup-card {
	width: 100%;
	background: rgba(0, 0, 0, 0.434) !important;
	border: 2px solid rgba(220, 220, 220, 0.701) !important;
}

.qr-code-container {
	display: flex;
	justify-content: center;
	padding: 1.5rem;
	background: white;
	border-radius: 8px;
	margin: 0 auto;
	width: fit-content;
	box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.manual-entry-container {
	text-align: center;
}

.manual-key {
	background: rgba(255, 255, 255, 0.1);
	padding: 0.75rem 1rem;
	border-radius: 4px;
	border: 1px solid rgba(255, 255, 255, 0.2);
	font-family: monospace;
	font-size: 1.1rem;
	letter-spacing: 0.1em;
	word-break: break-all;
	display: block;
	text-align: center;
	color: #fff;
}

.recovery-codes-card {
	background: #f5f5f5;
}

.recovery-codes {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 0.5rem;
}

.recovery-code {
	font-family: monospace;
	font-size: 0.9rem;
	padding: 0.5rem;
	background: white;
	border-radius: 4px;
	text-align: center;
	color: #000;
}
</style>
