<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.name"
          label="Name"
          prepend-icon="mdi-account"
          :rules="[v => !!v || 'Name is required']"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.email"
          label="Email"
          type="email"
          prepend-icon="mdi-email"
          autocomplete="off"
          :rules="[
            v => !!v || 'Email is required',
            v => /.+@.+\..+/.test(v) || 'Email must be valid'
          ]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.role"
          :items="roles"
          label="Role"
          prepend-icon="mdi-shield-account"
          :rules="[v => !!v || 'Role is required']"
        ></v-select>
      </v-col>
      
      <v-col cols="12" md="6" v-if="formData.role === 'freelancer'">
        <v-text-field
          v-model="formData.hourly_rate"
          label="Hourly Rate"
          type="number"
          prepend-icon="mdi-cash"
          :rules="[
            v => !!v || 'Hourly rate is required',
            v => v >= 0 || 'Hourly rate must be positive'
          ]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" v-if="user">
        <v-checkbox
          v-model="changePassword"
          label="Change Password"
          hide-details
        ></v-checkbox>
      </v-col>
      
      <v-col cols="12" md="6" v-if="!user || changePassword">
        <v-text-field
          v-model="formData.password"
          label="Password"
          type="password"
          prepend-icon="mdi-lock"
          autocomplete="new-password"
          :rules="[
            v => (!user || changePassword) ? (!!v || 'Password is required') : true,
            v => !v || v.length >= 8 || 'Password must be at least 8 characters'
          ]"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6" v-if="!user || changePassword">
        <v-text-field
          v-model="formData.password_confirmation"
          label="Confirm Password"
          type="password"
          prepend-icon="mdi-lock-check"
          autocomplete="new-password"
          :rules="[
            v => (!user || changePassword) ? (!!v || 'Password confirmation is required') : true,
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
