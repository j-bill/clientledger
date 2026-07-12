<template>
    <span class="group/tt relative inline-flex">
        <slot />
        <span
            class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded border border-ink-700 bg-ink-850 px-2 py-1 text-xs text-bone-300 opacity-0 shadow-lg transition-opacity delay-150 group-hover/tt:opacity-100"
        >
            {{ text }}
        </span>
    </span>
</template>

<script setup>
defineProps({
    text: { type: String, required: true },
});
</script>
