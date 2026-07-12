<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9998] flex items-center justify-center bg-ink-950/70 backdrop-blur-[2px]">
      <ui-spinner :size="44" />
    </div>
  </Teleport>
</template>

<script>
export default {
  name: "LoadingOverlay",
};
</script>
