<template>
	<div class="two-factor-challenge-container">
		<form @submit.prevent="verifyCode"
			  class="challenge-form">
			<div class="text-center mb-4">
				<v-icon color="primary"
						size="60"
						class="mb-3">mdi-shield-lock-outline</v-icon>
				<h2 class="mb-2">{{ $t('pages.twoFactor.title') }}</h2>
				<p class="text-subtitle-1">
					{{ $t('pages.twoFactor.subtitle') }}
				</p>
			</div>

			<div class="form-group">
				<v-text-field v-model="code"
							  :label="$t('pages.twoFactor.authenticationCode')"
							  variant="outlined"
							  type="text"
							  inputmode="numeric"
							  maxlength="6"
							  :rules="[rules.required, rules.sixDigits]"
							  autofocus
							  @keyup.enter="verifyCode">
				</v-text-field>
			</div>

			<v-alert type="info" variant="tonal" class="mb-4">
				<strong>Demo Account:</strong> If you're using the demo admin account (admin@admin.de), use code <code>000000</code> for testing purposes.
			</v-alert>

			<div class="form-group">
				<v-checkbox v-model="trustDevice"
							:label="$t('pages.twoFactor.trustDeviceLabel')"
							density="compact"
							hide-details></v-checkbox>
			</div>

			<v-btn type="submit"
				   color="primary"
				   block
				   size="large"
				   :loading="loading">
				{{ $t('pages.twoFactor.verify') }}
			</v-btn>

			<v-divider class="my-4"></v-divider>

			<div class="text-center">
				<v-btn variant="text"
					   size="small"
					   @click="showRecoveryInput = !showRecoveryInput">
					{{ showRecoveryInput ? $t('pages.twoFactor.useAuthenticatorCode') : $t('pages.twoFactor.useRecoveryCodeInstead') }}
				</v-btn>
			</div>

			<v-expand-transition>
				<div v-if="showRecoveryInput"
					 class="mt-4">
					<div class="form-group">
						<v-text-field v-model="recoveryCode"
									  :label="$t('pages.twoFactor.recoveryCode')"
									  variant="outlined"
									  placeholder="XXXXXXXXXX-XXXXXXXXXX"
									  :hint="$t('pages.twoFactor.recoveryCodeHint')"
									  @keyup.enter="verifyRecoveryCode">
						</v-text-field>
					</div>
					<v-btn color="secondary"
						   block
						   :loading="loading"
						   @click="verifyRecoveryCode">
						{{ $t('pages.twoFactor.useRecoveryCode') }}
					</v-btn>
				</div>
			</v-expand-transition>

			<div class="text-center mt-4">
				<v-btn variant="text"
					   size="small"
					   color="error"
					   @click="cancelLogin">
					{{ $t('pages.twoFactor.cancelAndLogout') }}
				</v-btn>
			</div>
		</form>
	</div>
</template>

<script>
import { mapActions } from 'pinia'
import { store } from '../store'
import axios from 'axios'

export default {
	name: 'TwoFactorChallenge',
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
	methods: {
		...mapActions(store, ['showSnackbar', 'getAuthUser']),

		async verifyCode() {
			if (!this.code || this.code.length !== 6) {
				this.showSnackbar('Please enter a valid 6-digit code', 'error')
				return
			}

			this.loading = true
			try {
				const response = await axios.post('/api/2fa/verify', {
					code: this.code,
					trust_device: this.trustDevice
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
				const response = await axios.post('/api/2fa/verify', {
					code: this.recoveryCode,
					trust_device: this.trustDevice
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

<style scoped>
.two-factor-challenge-container {
	display: flex;
	justify-content: center;
	align-items: center;
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

.challenge-form {
	width: 100%;
	max-width: 400px;
	padding: 2rem;
	border-radius: 8px;
	background: rgba(0, 0, 0, 0.434);
	border: 2px solid rgba(220, 220, 220, 0.701);
	box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.form-group {
	margin-bottom: 1rem;
}
</style>
