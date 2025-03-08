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
      
      <v-col cols="12" md="6" v-if="!user">
        <v-text-field
          v-model="formData.password"
          label="Password"
          prepend-icon="mdi-lock"
          autocomplete="off"
          :rules="[v => !!v || 'Password is required']"
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
      roles: ['admin', 'user'],
      formData: {
        name: '',
        email: '',
        role: 'user',
        password: ''
      }
    };
  },
  
  created() {
    if (this.user) {
      this.formData = {
        ...this.user,
        password: ''
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
      if (!data.password) {
        delete data.password;
      }
      this.$emit('save', data);
    }
  }
};
</script>
