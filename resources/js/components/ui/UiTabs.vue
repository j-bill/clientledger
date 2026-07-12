<template>
    <div class="ledger-rule flex gap-1 overflow-x-auto overflow-y-hidden scrollbar-thin" role="tablist">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            :aria-selected="modelValue === tab.value"
            class="relative flex shrink-0 items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors"
            :class="modelValue === tab.value ? 'text-brass-400' : 'text-bone-500 hover:text-bone-300'"
            @click="$emit('update:modelValue', tab.value)"
        >
            <component :is="tab.icon" v-if="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
            <span
                v-if="modelValue === tab.value"
                class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-brass-400"
            ></span>
        </button>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: [String, Number], required: true },
    // [{ value, label, icon? }]
    tabs: { type: Array, required: true },
});

defineEmits(['update:modelValue']);
</script>
