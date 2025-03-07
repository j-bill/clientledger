<template>
  <v-snackbar v-model="snackbar.show"
              :color="snackbar.color"
              elevation="24"
              close-on-content-click
              transition="slide-y-transition"
              location="top right"
              attach="body"
              :timeout="snackbar.timeout">
    {{ snackbar.message }}
  </v-snackbar>
</template>

<script>
import { mapState } from 'pinia';
import { store } from '../store';

export default {
  name: 'Snackbar',
  computed: {
    ...mapState(store, ['snackbar'])
  }
}
</script>

<style>
.v-snackbar {
    z-index: 9999 !important;
}
</style>
