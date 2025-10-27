<template>
	<div class="not-found-container">
		<div class="not-found-content">
			<!-- Animated 404 -->
			<div class="error-code">
				<span class="four">4</span>
				<span class="zero">0</span>
				<span class="four">4</span>
			</div>
			
			<!-- Message -->
			<h1 class="error-title">{{ $t('errorPages.pageNotFound') }}</h1>
			<p class="error-message">
				{{ $t('errorPages.pageNotFoundMessage') }}
			</p>
			
			<!-- Action Buttons -->
			<div class="action-buttons">
				<v-btn
					color="primary"
					size="large"
					prepend-icon="mdi-home"
					@click="goHome"
					class="mr-3"
				>
					{{ $t('errorPages.goHome') }}
				</v-btn>
				<v-btn
					variant="outlined"
					size="large"
					prepend-icon="mdi-arrow-left"
					@click="goBack"
				>
					{{ $t('errorPages.goBack') }}
				</v-btn>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'NotFound',
	methods: {
		goHome() {
			this.$router.push('/')
		},
		goBack() {
			this.$router.go(-1)
		}
	}
}
</script>

<style scoped>
.not-found-container {
	display: flex;
	justify-content: center;
	align-items: center;
	min-height: 100vh;
	background: linear-gradient(270deg, #0f172a, #1e293b, #3b82f6, #8b5cf6);
	background-size: 400% 400%;
	animation: gradientAnimation 30s ease infinite;
	position: relative;
	overflow: hidden;
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

.not-found-content {
	text-align: center;
	padding: 2rem;
	border-radius: 16px;
	background: rgba(0, 0, 0, 0.434);
	border: 2px solid rgba(220, 220, 220, 0.701);
	box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
	backdrop-filter: blur(10px);
	max-width: 600px;
	width: 90%;
	position: relative;
	z-index: 2;
}

/* Animated 404 */
.error-code {
	font-size: 120px;
	font-weight: 900;
	line-height: 1;
	margin-bottom: 1rem;
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 10px;
}

.error-code span {
	display: inline-block;
	background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	animation: pulse 2s ease-in-out infinite;
}

.error-code .four:first-child {
	animation-delay: 0s;
}

.error-code .zero {
	animation-delay: 0.2s;
	animation: spin 3s linear infinite;
}

.error-code .four:last-child {
	animation-delay: 0.4s;
}

@keyframes pulse {
	0%, 100% {
		transform: scale(1);
		opacity: 1;
	}
	50% {
		transform: scale(1.1);
		opacity: 0.8;
	}
}

@keyframes spin {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
}

/* Title and Message */
.error-title {
	font-size: 2.5rem;
	font-weight: 700;
	color: #ffffff;
	margin-bottom: 1rem;
	text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.error-message {
	font-size: 1.1rem;
	color: rgba(255, 255, 255, 0.85);
	margin-bottom: 2rem;
	line-height: 1.6;
}

/* Action Buttons */
.action-buttons {
	display: flex;
	justify-content: center;
	align-items: center;
	flex-wrap: wrap;
	gap: 1rem;
	margin-top: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
	.error-code {
		font-size: 80px;
	}
	
	.error-title {
		font-size: 2rem;
	}
	
	.error-message {
		font-size: 1rem;
	}
	
	.action-buttons {
		flex-direction: column;
		gap: 0.5rem;
	}
	
	.action-buttons .v-btn {
		width: 100%;
		margin: 0 !important;
	}
}

@media (max-width: 480px) {
	.error-code {
		font-size: 60px;
	}
	
	.error-title {
		font-size: 1.5rem;
	}
	
	.not-found-content {
		padding: 1.5rem;
	}
}
</style>
