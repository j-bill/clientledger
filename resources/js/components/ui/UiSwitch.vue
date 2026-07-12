<template>
    <label class="flex cursor-pointer select-none items-center gap-3" :class="disabled ? 'cursor-not-allowed opacity-50' : ''">
        <button
            type="button"
            role="switch"
            :aria-checked="!!modelValue"
            :disabled="disabled"
            class="relative h-[22px] w-10 shrink-0 rounded-full border transition-colors"
            :class="modelValue ? 'border-brass-500 bg-brass-500/90' : 'border-ink-600 bg-ink-800'"
            @click="$emit('update:modelValue', !modelValue)"
        >
            <span
                class="absolute top-1/2 h-4 w-4 -translate-y-1/2 rounded-full transition-all"
                :class="modelValue ? 'left-[19px] bg-ink-950' : 'left-[3px] bg-bone-500'"
            ></span>
        </button>
        <span v-if="label || $slots.default" class="text-sm text-bone-300">
            <slot>{{ label }}</slot>
            <span v-if="hint" class="mt-0.5 block text-xs text-bone-700">{{ hint }}</span>
        </span>
    </label>
</template>

<script setup>
defineProps({
    modelValue: { type: [Boolean, Number], default: false },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    disabled: Boolean,
});

defineEmits(['update:modelValue']);
</script>
