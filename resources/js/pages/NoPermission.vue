<template>
  <div class="no-permission-container">
    <v-card class="no-permission-card">
      <v-card-title class="text-h4 mb-4">Access Denied</v-card-title>
      <v-card-text>
        <p class="text-body-1 mb-4">You don't have permission to access this page.</p>
        <v-btn color="primary" to="/">Return to Home</v-btn>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
export default {
  name: 'NoPermission'
}
</script>

<style scoped>
.no-permission-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 80vh;
  padding: 20px;
}

.no-permission-card {
  max-width: 400px;
  width: 100%;
  text-align: center;
}
</style> 