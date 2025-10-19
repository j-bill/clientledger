<template>
  <v-dialog v-model="dialog" max-width="500px" persistent>
    <v-card>
      <v-card-title class="text-h5 pa-4">
        <v-icon class="mr-2" color="warning">mdi-email-check</v-icon>
        Verify Your Email
      </v-card-title>
      
      <v-card-text class="pa-4">
        <div v-if="!codeSent">
          <p class="mb-4">
            For additional security, please verify your email address.
            Click the button below to receive a verification code.
          </p>
          
          <v-alert v-if="error" type="error" class="mb-3" closable @click:close="error = null">
            {{ error }}
          </v-alert>
        </div>
        
        <div v-else>
          <p class="mb-4">
            A verification code has been sent to <strong>{{ userEmail }}</strong>.
            Please enter the 6-digit code below.
          </p>
          
          <v-text-field
            v-model="verificationCode"
            label="Verification Code"
            placeholder="000000"
            variant="outlined"
            maxlength="6"
            :error-messages="error"
            @keyup.enter="verifyCode"
            autofocus
          />
          
          <v-alert v-if="success" type="success" class="mb-3">
            {{ success }}
          </v-alert>
          
          <p class="text-caption text-grey">
            The code will expire in 1 hour. Didn't receive it?
            <a @click="resendCode" class="text-primary cursor-pointer">Resend code</a>
          </p>
        </div>
      </v-card-text>
      
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          v-if="!codeSent"
          color="grey"
          variant="text"
          @click="skipVerification"
        >
          Skip for now
        </v-btn>
        <v-btn
          v-if="!codeSent"
          color="primary"
          :loading="loading"
          @click="sendCode"
        >
          Send Verification Code
        </v-btn>
        
        <v-btn
          v-if="codeSent"
          color="grey"
          variant="text"
          @click="skipVerification"
          :disabled="loading"
        >
          Skip for now
        </v-btn>
        <v-btn
          v-if="codeSent"
          color="primary"
          :loading="loading"
          :disabled="verificationCode.length !== 6"
          @click="verifyCode"
        >
          Verify
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
import axios from 'axios'
import { mapActions, mapState } from 'pinia'
import { store } from '../store'

export default {
  name: 'EmailVerificationDialog',
  props: {
    modelValue: {
      type: Boolean,
      required: true
    }
  },
  emits: ['update:modelValue', 'verified', 'skipped'],
  data() {
    return {
      codeSent: false,
      verificationCode: '',
      loading: false,
      error: null,
      success: null
    }
  },
  computed: {
    ...mapState(store, ['user']),
    dialog: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    },
    userEmail() {
      return this.user?.email || ''
    }
  },
  methods: {
    ...mapActions(store, ['showSnackbar', 'updateAuthUser']),
    
    async sendCode() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/email-verification/send-code')
        
        if (response.data.already_verified) {
          this.success = 'Your email is already verified!'
          setTimeout(() => {
            this.dialog = false
            this.$emit('verified')
          }, 1500)
        } else if (response.data.code_sent) {
          this.codeSent = true
          this.showSnackbar('Verification code sent to your email', 'success')
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to send verification code'
        this.showSnackbar(this.error, 'error')
      } finally {
        this.loading = false
      }
    },
    
    async resendCode() {
      this.verificationCode = ''
      this.error = null
      await this.sendCode()
    },
    
    async verifyCode() {
      if (this.verificationCode.length !== 6) {
        this.error = 'Please enter a valid 6-digit code'
        return
      }
      
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/email-verification/verify-code', {
          code: this.verificationCode
        })
        
        if (response.data.verified) {
          this.success = 'Email verified successfully!'
          this.showSnackbar('Email verified successfully!', 'success')
          
          // Update the user in store to reflect verification
          if (this.user) {
            this.updateAuthUser({
              ...this.user,
              email_verified_at: new Date().toISOString()
            })
          }
          
          setTimeout(() => {
            this.dialog = false
            this.$emit('verified')
          }, 1500)
        }
      } catch (error) {
        if (error.response?.data?.expired) {
          this.error = 'Code has expired. Please request a new one.'
        } else {
          this.error = error.response?.data?.message || 'Invalid verification code'
        }
      } finally {
        this.loading = false
      }
    },
    
    skipVerification() {
      this.dialog = false
      this.$emit('skipped')
    },
    
    reset() {
      this.codeSent = false
      this.verificationCode = ''
      this.loading = false
      this.error = null
      this.success = null
    }
  },
  watch: {
    dialog(newValue) {
      if (newValue) {
        this.reset()
      }
    }
  }
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  text-decoration: underline;
}
</style>
