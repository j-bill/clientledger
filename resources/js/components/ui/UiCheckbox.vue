<template>
    <label class="flex cursor-pointer select-none items-start gap-2.5" :class="disabled ? 'cursor-not-allowed opacity-50' : ''">
        <span class="relative mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center">
            <input
                type="checkbox"
                class="peer h-[18px] w-[18px] cursor-pointer appearance-none rounded-sm border border-ink-600 bg-ink-900 transition-colors checked:border-brass-500 checked:bg-brass-500 disabled:cursor-not-allowed"
                :checked="!!modelValue"
                :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.checked)"
            />
            <Check class="pointer-events-none absolute h-3 w-3 text-ink-950 opacity-0 transition-opacity peer-checked:opacity-100" />
        </span>
        <span v-if="label || $slots.default" class="text-sm text-bone-300">
            <slot>{{ label }}</slot>
            <span v-if="hint" class="mt-0.5 block text-xs text-bone-700">{{ hint }}</span>
        </span>
    </label>
</template>

<script setup>
import { Check } from 'lucide-vue-next';

defineProps({
    modelValue: { type: [Boolean, Number], default: false },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    disabled: Boolean,
});

defineEmits(['update:modelValue']);
</script>
