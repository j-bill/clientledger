<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex select-none items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50"
        :class="[sizeClasses, variantClasses, block ? 'w-full' : '']"
        @click="$emit('click', $event)"
    >
        <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
        <component :is="icon" v-else-if="icon" :class="iconOnly ? 'h-4.5 w-4.5' : 'h-4 w-4'" />
        <slot />
    </button>
</template>

<script setup>
import { computed, useSlots } from 'vue';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    // primary | outline | ghost | danger | danger-ghost
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' }, // sm | md | lg
    type: { type: String, default: 'button' },
    icon: { type: [Object, Function], default: null },
    loading: Boolean,
    disabled: Boolean,
    block: Boolean,
});

defineEmits(['click']);
const slots = useSlots();

const iconOnly = computed(() => props.icon && !slots.default);

const sizeClasses = computed(() => {
    if (iconOnly.value) {
        return { sm: 'h-7 w-7', md: 'h-9 w-9', lg: 'h-10 w-10' }[props.size];
    }
    return {
        sm: 'h-8 px-3 text-[13px]',
        md: 'h-9 px-4 text-sm',
        lg: 'h-11 px-5 text-[15px]',
    }[props.size];
});

const variantClasses = computed(() => ({
    primary: 'bg-brass-500 text-ink-950 hover:bg-brass-400 active:bg-brass-600',
    outline: 'border border-ink-700 text-bone-300 hover:border-ink-600 hover:bg-ink-850 hover:text-bone-100',
    ghost: 'text-bone-300 hover:bg-ink-800 hover:text-bone-100',
    danger: 'bg-clay-500 text-ink-950 hover:bg-clay-400',
    'danger-ghost': 'text-clay-400 hover:bg-clay-900',
    'brass-ghost': 'text-brass-400 hover:bg-brass-900',
}[props.variant]));
</script>
