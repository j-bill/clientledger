<template>
  <ui-dialog v-model="dialog" max-width="500px" persistent>
    <template #title>
      <span class="flex items-center gap-2">
        <MailCheck class="h-4 w-4 text-ochre-400" />
        {{ $t('pages.emailVerification.title') }}
      </span>
    </template>

    <div v-if="!codeSent">
      <p class="mb-4 text-sm text-bone-300">
        {{ $t('pages.emailVerification.description') }}
      </p>

      <ui-alert v-if="error" type="error" class="mb-3" closable @close="error = null">
        {{ error }}
      </ui-alert>
    </div>

    <div v-else>
      <p class="mb-4 text-sm text-bone-300">
        {{ $t('pages.emailVerification.codeSentTo') }} <strong class="text-bone-100">{{ userEmail }}</strong>.
        {{ $t('pages.emailVerification.enterCodeBelow') }}
      </p>

      <ui-input
        v-model="verificationCode"
        :label="$t('pages.emailVerification.verificationCode')"
        placeholder="000000"
        maxlength="6"
        inputmode="numeric"
        class="text-center font-mono tracking-[0.3em]"
        @keyup.enter="verifyCode"
        autofocus
      />
      <p v-if="error" class="mt-1 text-xs text-clay-400">{{ error }}</p>

      <ui-alert v-if="success" type="success" class="mt-3">
        {{ success }}
      </ui-alert>

      <p class="mt-3 text-xs text-bone-500">
        {{ $t('pages.emailVerification.codeExpiration') }}
        <a @click="resendCode" class="cursor-pointer text-brass-400 underline hover:text-brass-300">{{ $t('pages.emailVerification.resendCode') }}</a>
      </p>
    </div>

    <template #actions>
      <ui-button
        v-if="!codeSent"
        variant="ghost"
        @click="skipVerification"
      >
        {{ $t('pages.emailVerification.skipForNow') }}
      </ui-button>
      <ui-button
        v-if="!codeSent"
        variant="primary"
        :loading="loading"
        @click="sendCode"
      >
        {{ $t('pages.emailVerification.sendCode') }}
      </ui-button>

      <ui-button
        v-if="codeSent"
        variant="ghost"
        @click="skipVerification"
        :disabled="loading"
      >
        {{ $t('pages.emailVerification.skipForNow') }}
      </ui-button>
      <ui-button
        v-if="codeSent"
        variant="primary"
        :loading="loading"
        :disabled="verificationCode.length !== 6"
        @click="verifyCode"
      >
        {{ $t('pages.emailVerification.verify') }}
      </ui-button>
    </template>
  </ui-dialog>
</template>

<script>
import axios from 'axios'
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import { MailCheck } from 'lucide-vue-next'

export default {
  name: 'EmailVerificationDialog',
  components: { MailCheck },
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
