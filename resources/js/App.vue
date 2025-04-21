<template>
  <v-app>
    <!-- Include the navigation component -->
    <NavigationBar v-if="isAuthenticated" />

    
    <!-- Main content area -->
    <v-main>
      <!-- Keep your existing commented code here -->
      
      <!-- Router view for page content -->
      <router-view></router-view>

    <LoadingOverlay v-if="loading"/>

    </v-main>
    <Snackbar />
  </v-app>
</template>

<script>
import NavigationBar from './components/NavigationBar.vue';
import Snackbar from './components/Snackbar.vue';
import LoadingOverlay from './components/LoadingOverlay.vue';
import axios from 'axios';
import { mapState } from 'pinia';
import { store } from './store';

export default {
  name: 'App',
  components: {
    NavigationBar,
    Snackbar,
    LoadingOverlay,
  },
  data() {
    return {
    };
  },
  computed: {
    ...mapState(store, ['snackbar', 'isAuthenticated', 'loading']),
  },
  methods: {
    async validate() {
      const { valid } = await this.$refs.form.validate();

      if (valid) {
        alert('Form is valid');
      } else {
        alert('Form is invalid');
      }
    },
    async login() {
      this.response = '';
      this.type = '';

      try {
        const response = await axios.post('/api/login', {
          email: this.email,
          password: this.password,
        });
        this.type = 'success';
        this.response = response.data.message;
      } catch (error) {
        this.type = 'error';
        this.response = error.response?.data?.message || 'An error occurred';
      }
    },

  },
};
</script>

