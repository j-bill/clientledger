<template>
    <Loader2 class="animate-spin text-brass-400" :style="{ width: size + 'px', height: size + 'px' }" />
</template>

<script setup>
import { Loader2 } from 'lucide-vue-next';

defineProps({
    size: { type: [Number, String], default: 24 },
});
</script>
