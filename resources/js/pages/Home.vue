<template>
    <div>
        <h1>Home</h1>
        <p>Welcome to the Home page!</p>
        <v-btn @click="testSnackbar" color="primary">
            Test Snackbar
        </v-btn>
    </div>
</template>

<script>
import { mapActions } from 'pinia';
import { store } from '../store';

export default {
    name: 'Home',
    methods: {
        ...mapActions(store, ['showSnackbar']),
        testSnackbar() {
            this.showSnackbar('This is a test message!', 'error', 5000);
        }
    }
};
</script>

<style scoped>
/* Add component-specific styles here */
</style>