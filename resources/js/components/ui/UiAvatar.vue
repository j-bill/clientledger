<template>
    <span
        class="inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full border border-ink-600 bg-ink-800 font-mono text-brass-400"
        :style="{ width: size + 'px', height: size + 'px', fontSize: size / 2.6 + 'px' }"
    >
        <img v-if="image" :src="image" class="h-full w-full object-cover" alt="" />
        <slot v-else>{{ initials }}</slot>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, default: '' },
    image: { type: String, default: '' },
    size: { type: [Number, String], default: 36 },
});

const initials = computed(() =>
    props.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('')
);
</script>
