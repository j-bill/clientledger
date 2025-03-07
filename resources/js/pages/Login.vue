<template>
  <div class="login-container">
    <form @submit.prevent="handleLogin" class="login-form">
      <h2>Login</h2>
      <div class="form-group">
        <input
          v-model="form.email"
          type="email"
          placeholder="Email"
          required
        />
      </div>
      <div class="form-group">
        <input
          v-model="form.password"
          type="password"
          placeholder="Password"
          required
        />
      </div>
      <button type="submit" :disabled="loading">
        {{ loading ? 'Logging in...' : 'Login' }}
      </button>
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
        email: '',
        password: ''
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
}

.login-form {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.form-group {
  margin-bottom: 1rem;
}

input {
  width: 100%;
  padding: 0.5rem;
  margin-bottom: 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

button {
  width: 100%;
  padding: 0.75rem;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  background-color: #cccccc;
}
</style>
