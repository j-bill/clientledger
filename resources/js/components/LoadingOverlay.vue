<template>
  <v-overlay v-model="showOverlay" opacity="0.3">
    <div class="centercenter">
      <v-progress-circular
        :size="100"
        :width="10"
        color="primary"
        indeterminate
        :transition="{
          enter: 'fade-transition',
          leave: 'fade-transition',
        }"
      ></v-progress-circular>
    </div>
  </v-overlay>
</template>

<script>
export default {
  name: "LoadingOverlay",
  data() {
    return {
      showOverlay: true,
    };
  },
};
</script>

<!-- css -->
<style scoped>
.centercenter {
  width: 100vw;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
</style>
