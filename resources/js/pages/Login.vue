<template>
	<div class="login-container">
		<form @submit.prevent="handleLogin"
			  class="login-form">
			<!-- Company Logo -->
			<div class="logo-container mb-4" v-if="companyLogo">
				<img :src="companyLogo" alt="Company Logo" class="company-logo" />
			</div>
			<h2 class="pb-4">Login</h2>
			<div class="form-group">
				<v-text-field v-model="form.email"
							  data-test="login-email"
							  type="email"
							  variant="outlined"
							  label="Email"
							  required />
			</div>
			<div class="form-group">
				<v-text-field v-model="form.password"
							  data-test="login-password"
							  type="password"
							  variant="outlined"
							  label="Password"
							  required />
			</div>
			<v-btn type="submit"
				   data-test="btn-login"
				   block
				   :loading="loading"
				   color="primary">
				Login
			</v-btn>

			<!-- Legal Links Footer -->
			<div class="legal-links mt-6">
				<router-link :to="{ name: 'Privacy' }" class="legal-link">
					Privacy Notice
				</router-link>
				<div class="flex-grow"></div>
				<router-link :to="{ name: 'Imprint' }" class="legal-link">
					Imprint
				</router-link>
			</div>
		</form>
	</div>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'

export default {
	name: 'Login',
	data() {
		return {
			form: {
				email: 'admin@admin.de',
				password: 'adminadmin'
			},
			loading: false,
			companyLogo: null
		}
	},
	computed: {
		...mapState(store, ['settings'])
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

<style scoped>
.login-container {
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

.login-form {
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

.legal-links {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 1rem;
	border-top: 1px solid rgba(220, 220, 220, 0.3);
}

.legal-link {
	color: rgba(255, 255, 255, 0.9);
	text-decoration: none;
	font-size: 0.875rem;
	transition: color 0.2s ease;
}

.legal-link:hover {
	color: #ffffff;
	text-decoration: underline;
}

.logo-container {
	display: flex;
	justify-content: center;
	align-items: center;
}

.company-logo {
	max-width: 200px;
	max-height: 80px;
	object-fit: contain;
}
</style>
