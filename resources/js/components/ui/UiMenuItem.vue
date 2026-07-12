<template>
    <button
        type="button"
        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm transition-colors"
        :class="danger ? 'text-clay-400 hover:bg-clay-900' : 'text-bone-300 hover:bg-ink-800 hover:text-bone-100'"
        @click="$emit('click', $event)"
    >
        <component :is="icon" v-if="icon" class="h-4 w-4 shrink-0 opacity-70" />
        <span class="min-w-0 flex-1">
            <span class="block truncate">{{ title }}<slot /></span>
            <span v-if="subtitle" class="block truncate text-xs text-bone-700">{{ subtitle }}</span>
        </span>
    </button>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    danger: Boolean,
});

defineEmits(['click']);
</script>
