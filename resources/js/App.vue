<template>
  <v-app>
    <!-- Include the navigation component -->
    <NavigationBar v-if="shouldShowNavigation" />

    
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
import { mapState, mapActions } from 'pinia';
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
    ...mapState(store, ['snackbar', 'isAuthenticated', 'loading', 'user']),
    is2FAPage() {
      return this.$route.path.startsWith('/2fa/');
    },
    isLoginPage() {
      return this.$route.path === '/login';
    },
    has2FAEnabled() {
      const enabled = this.user?.has_two_factor_enabled === true;
      console.log('[App.vue] has2FAEnabled check:', {
        user: this.user,
        hasTwoFactorSecret: !!this.user?.two_factor_secret,
        hasTwoFactorConfirmedAt: !!this.user?.two_factor_confirmed_at,
        hasHasTwoFactorEnabled: this.user?.has_two_factor_enabled,
        enabled
      });
      return enabled;
    },
    shouldShowNavigation() {
      // Don't show navigation if not authenticated
      if (!this.isAuthenticated) {
        console.log('[App.vue] shouldShowNavigation: false - not authenticated');
        return false;
      }
      
      // Don't show navigation on login or 2FA pages
      if (this.isLoginPage || this.is2FAPage) {
        console.log('[App.vue] shouldShowNavigation: false - on login or 2FA page');
        return false;
      }
      
      // Don't show navigation if user doesn't have 2FA enabled yet
      if (!this.has2FAEnabled) {
        console.log('[App.vue] shouldShowNavigation: false - 2FA not enabled');
        return false;
      }
      
      console.log('[App.vue] shouldShowNavigation: true');
      return true;
    },
  },
  watch: {
    // Watch for changes to user and 2FA status
    has2FAEnabled(newVal) {
      if (newVal) {
        // Only load settings when 2FA is enabled
        this.fetchSettings();
      }
    }
  },
  created() {
    // Only load settings if user already has 2FA enabled
    if (this.has2FAEnabled) {
      this.fetchSettings();
    }
  },
  methods: {
    ...mapActions(store, ['fetchSettings']),
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

