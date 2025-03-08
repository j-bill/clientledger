<template>
	<div class="login-container">
		<form @submit.prevent="handleLogin"
			  class="login-form">
			<h2 class="pb-4">Login</h2>
			<div class="form-group">
				<v-text-field v-model="form.email"
							  type="email"
							  variant="outlined"
							  label="Email"
							  required />
			</div>
			<div class="form-group">
				<v-text-field v-model="form.password"
							  type="password"
							  variant="outlined"
							  label="Password"
							  required />
			</div>
			<v-btn type="submit"
				   block
				   :loading="loading"
				   color="primary">
				Login
			</v-btn>
		</form>
	</div>
</template>

<script>
import { mapActions } from 'pinia'
import { store } from '../store'

export default {
	name: 'Login',
	data() {
		return {
			form: {
				email: 'admin@admin.de',
				password: 'adminadmin'
			},
			loading: false
		}
	},
	methods: {
		...mapActions(store, ['login']),
		async handleLogin() {
			this.loading = true
			try {
				await this.login(this.form.email, this.form.password)
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
	background: linear-gradient(270deg, #000000, #333333, #8640ea);
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
</style>
