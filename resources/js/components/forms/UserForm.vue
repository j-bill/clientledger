<template>
  <ui-form ref="form" @submit="submit">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <ui-input
        v-model="formData.name"
        :label="$t('forms.user.name')"
        :icon="User"
        :rules="[v => !!v || $t('forms.user.nameRequired')]"
      />

      <ui-input
        v-model="formData.email"
        :label="$t('common.email')"
        type="email"
        :icon="Mail"
        autocomplete="off"
        :rules="[
          v => !!v || $t('forms.user.emailRequired'),
          v => /.+@.+\..+/.test(v) || $t('forms.user.emailValid')
        ]"
      />

      <ui-select
        v-model="formData.role"
        :items="roles"
        :label="$t('forms.user.role')"
        :rules="[v => !!v || $t('forms.user.roleRequired')]"
      />

      <ui-input
        v-model="formData.hourly_rate"
        :label="$t('forms.user.hourlyRate')"
        type="number"
        step="0.01"
        :icon="Banknote"
        :rules="[
          v => (v !== null && v !== undefined && v !== '') || $t('forms.user.hourlyRateRequired'),
          v => (v === null || v === undefined || v === '' || v >= 0) || $t('forms.user.hourlyRatePositive')
        ]"
      />

      <div class="flex items-center md:col-span-2">
        <ui-checkbox
          v-model="formData.notify_on_project_assignment"
          :label="$t('forms.user.notifyOnProjectAssignment')"
        />
      </div>

      <ui-input
        v-if="!user"
        v-model="formData.password"
        :label="$t('forms.user.password')"
        type="password"
        :icon="Lock"
        autocomplete="new-password"
        :rules="[
          v => !!v || $t('forms.user.passwordRequired'),
          v => !v || v.length >= 8 || $t('forms.user.passwordMinLength')
        ]"
      />

      <ui-input
        v-if="!user"
        v-model="formData.password_confirmation"
        :label="$t('forms.user.confirmPassword')"
        type="password"
        :icon="LockKeyhole"
        autocomplete="new-password"
        :rules="[
          v => !!v || $t('forms.user.confirmPasswordRequired'),
          v => v === formData.password || 'Passwords must match'
        ]"
      />
    </div>
  </ui-form>
</template>

<script>
import { User, Mail, Banknote, Lock, LockKeyhole } from 'lucide-vue-next';

export default {
  name: 'UserForm',
  props: {
    user: {
      type: Object,
      default: null
    }
  },

  setup() {
    return { User, Mail, Banknote, Lock, LockKeyhole };
  },

  data() {
    return {
      roles: ['admin', 'freelancer'],
      formData: {
        name: '',
        email: '',
        role: 'freelancer',
        password: '',
        password_confirmation: '',
        hourly_rate: 0,
        notify_on_project_assignment: false
      }
    };
  },

  created() {
    if (this.user) {
      this.formData = {
        ...this.user,
        password: '',
        password_confirmation: ''
      };
    }
  },

  methods: {
    async submit() {
      const { valid } = await this.$refs.form.validate();

      if (!valid) {
        return;
      }

      const data = { ...this.formData };

      // Only include password if creating new user
      if (this.user || !data.password) {
        delete data.password;
        delete data.password_confirmation;
      }

      this.$emit('save', data);
    }
  }
};
</script>
