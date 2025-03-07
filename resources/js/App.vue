<template>
  <v-app>
    <!-- Include the navigation component -->
    <NavigationBar />
    
    <!-- Main content area -->
    <v-main>
      <!-- Keep your existing commented code here -->
      
      <!-- Router view for page content -->
      <router-view></router-view>
    </v-main>
    <Snackbar />
  </v-app>
</template>

<script>
import NavigationBar from './components/NavigationBar.vue';
import Snackbar from './components/Snackbar.vue';
import axios from 'axios';
import { mapState } from 'pinia';
import { store } from './store';

export default {
  name: 'App',
  components: {
    NavigationBar,
    Snackbar,
  },
  data() {
    return {
      email: 'admin@admin.de',
      password: 'adminadmin',
      response: '',
      type: 'error',
      tab: null,
    };
  },
  computed: {
    ...mapState(store, ['snackbar'])
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
    printDate(date) {
      alert(date);
    },
  },
};
</script>

<style scoped>
body {
  font-family: Arial, sans-serif;
}

.code-block {
  background-color: #333;
  color: #e0e0e0;
  padding: 16px;
  font-family: monospace;
  border-radius: 8px;
  white-space: pre-wrap;
  /* Preserves formatting and line breaks */
  overflow: auto;
  margin: 8px 0;
  line-height: 1.5;
  /* Improves readability */
}
</style>
