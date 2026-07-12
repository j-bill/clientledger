<template>
  <Teleport to="body">
    <Transition name="snackbar">
      <div
        v-if="snackbar.show"
        class="fixed right-4 top-4 z-[9999] flex max-w-sm cursor-pointer items-start gap-3 rounded-md border px-4 py-3 text-sm shadow-2xl"
        :class="colorClasses"
        role="status"
        @click="close"
      >
        <component :is="icon" class="mt-0.5 h-4 w-4 shrink-0" />
        <span class="min-w-0 flex-1">{{ snackbar.message }}</span>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { mapState } from 'pinia';
import { store } from '../store';
import { CircleCheck, CircleAlert, TriangleAlert, Info } from 'lucide-vue-next';

export default {
  name: 'Snackbar',
  data() {
    return {
      hideTimer: null,
    };
  },
  computed: {
    ...mapState(store, ['snackbar']),
    colorClasses() {
      return {
        success: 'border-sage-500/40 bg-sage-900 text-sage-400',
        error: 'border-clay-500/40 bg-clay-900 text-clay-400',
        warning: 'border-ochre-400/40 bg-ochre-900 text-ochre-400',
        info: 'border-slate-400/40 bg-slate-900 text-slate-400',
      }[this.snackbar.color] || 'border-ink-700 bg-ink-850 text-bone-300';
    },
    icon() {
      return {
        success: CircleCheck,
        error: CircleAlert,
        warning: TriangleAlert,
        info: Info,
      }[this.snackbar.color] || Info;
    },
  },
  watch: {
    // Vuetify's v-snackbar auto-hid via :timeout; replicate that here
    'snackbar.show'(show) {
      clearTimeout(this.hideTimer);
      if (show) {
        this.hideTimer = setTimeout(this.close, this.snackbar.timeout || 4000);
      }
    },
  },
  beforeUnmount() {
    clearTimeout(this.hideTimer);
  },
  methods: {
    close() {
      clearTimeout(this.hideTimer);
      this.snackbar.show = false;
    },
  },
};
</script>

<style>
.snackbar-enter-active,
.snackbar-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.snackbar-enter-from,
.snackbar-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
