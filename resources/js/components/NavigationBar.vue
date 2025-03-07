<template>
  <div>
    <!-- App Bar -->
    <v-app-bar app color="primary" dark>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>Freelancer App</v-toolbar-title>
      <v-spacer></v-spacer>
      
      <!-- Desktop Navigation Links -->
      <div class="d-none d-md-flex">
        <v-btn to="/" text>Home</v-btn>
        <v-btn to="/projects" text>Projects</v-btn>
        <v-btn to="/customers" text>Customers</v-btn>
        <v-btn to="/invoices" text>Invoices</v-btn>
        <v-btn to="/work-logs" text>Work Logs</v-btn>
      </div>
      
      <!-- User Menu -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn icon v-bind="props">
            <v-icon>mdi-account-circle</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item to="/profile">
            <v-list-item-title>Profile</v-list-item-title>
          </v-list-item>
          <v-list-item to="/settings">
            <v-list-item-title>Settings</v-list-item-title>
          </v-list-item>
          <v-list-item @click="logout">
            <v-list-item-title>Logout</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Navigation Drawer -->
    <v-navigation-drawer v-model="drawer" temporary app>
      <v-list>
        <!-- <v-list-item title="Freelancer App" prepend-icon="mdi-view-dashboard" to="/"></v-list-item> -->
        <v-divider></v-divider>
        <v-list-item prepend-icon="mdi-home" title="Home" to="/"></v-list-item>
        <v-list-item prepend-icon="mdi-folder" title="Projects" to="/projects"></v-list-item>
        <v-list-item prepend-icon="mdi-account-multiple" title="Customers" to="/customers"></v-list-item>
        <v-list-item prepend-icon="mdi-receipt" title="Invoices" to="/invoices"></v-list-item>
        <v-list-item prepend-icon="mdi-account-group" title="Users" to="/users"></v-list-item>
        <v-list-item prepend-icon="mdi-clock" title="Work Logs" to="/work-logs"></v-list-item>
        <v-divider></v-divider>
        <!-- <v-list-item prepend-icon="mdi-cog" title="Settings" to="/settings"></v-list-item> -->
      </v-list>
    </v-navigation-drawer>
  </div>
</template>

<script>

import { mapActions } from 'pinia'
import { store } from '../store'


export default {
  name: 'NavigationBar',
  data() {
    return {
      drawer: false,
    }
  },
  methods: {
    ...mapActions(store, ['logout'])
  },
}
</script>
