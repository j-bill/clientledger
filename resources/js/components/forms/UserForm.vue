<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.name"
          :label="$t('forms.user.name')"
          prepend-icon="mdi-account"
          :rules="[v => !!v || $t('forms.user.nameRequired')]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.email"
          :label="$t('common.email')"
          type="email"
          prepend-icon="mdi-email"
          autocomplete="off"
          :rules="[
            v => !!v || $t('forms.user.emailRequired'),
            v => /.+@.+\..+/.test(v) || $t('forms.user.emailValid')
          ]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.role"
          :items="roles"
          :label="$t('forms.user.role')"
          prepend-icon="mdi-shield-account"
          :rules="[v => !!v || $t('forms.user.roleRequired')]"
        ></v-select>
      </v-col>
      
      <v-col cols="12" md="6" v-if="formData.role === 'freelancer'">
        <v-text-field
          v-model="formData.hourly_rate"
          :label="$t('forms.user.hourlyRate')"
          type="number"
          prepend-icon="mdi-cash"
          :rules="[
            v => !!v || $t('forms.user.hourlyRateRequired'),
            v => v >= 0 || $t('forms.user.hourlyRatePositive')
          ]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" v-if="user">
        <v-checkbox
          v-model="changePassword"
          :label="$t('forms.user.changePassword')"
          hide-details
        ></v-checkbox>
      </v-col>
      
      <v-col cols="12" md="6" v-if="!user || changePassword">
        <v-text-field
          v-model="formData.password"
          :label="$t('forms.user.password')"
          type="password"
          prepend-icon="mdi-lock"
          autocomplete="new-password"
          :rules="[
            v => (!user || changePassword) ? (!!v || $t('forms.user.passwordRequired')) : true,
            v => !v || v.length >= 8 || $t('forms.user.passwordMinLength')
          ]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6" v-if="!user || changePassword">
        <v-text-field
          v-model="formData.password_confirmation"
          :label="$t('forms.user.confirmPassword')"
          type="password"
          prepend-icon="mdi-lock-check"
          autocomplete="new-password"
          :rules="[
            v => (!user || changePassword) ? (!!v || $t('forms.user.confirmPasswordRequired')) : true,
            v => v === formData.password || 'Passwords must match'
          ]"
        ></v-text-field>
      </v-col>
    </v-row>
  </v-form>
</template>

<script>
export default {
  name: 'UserForm',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  
  data() {
    return {
      roles: ['admin', 'freelancer'],
      changePassword: false,
      formData: {
        name: '',
        email: '',
        role: 'freelancer',
        password: '',
        password_confirmation: '',
        hourly_rate: 0
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
      
      // Only include password if creating new user or change password is checked
      if (!data.password || (this.user && !this.changePassword)) {
        delete data.password;
        delete data.password_confirmation;
      }
      
      this.$emit('save', data);
    }
  }
};
</script>
