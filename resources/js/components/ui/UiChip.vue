<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 font-mono text-[11px] uppercase tracking-wide"
        :class="colorClasses"
    >
        <component :is="icon" v-if="icon" class="h-3 w-3" />
        <slot>{{ text }}</slot>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    // neutral | brass | success | error | warning | info
    color: { type: String, default: 'neutral' },
    text: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
});

const colorClasses = computed(() => ({
    neutral: 'border-ink-600 bg-ink-800 text-bone-300',
    brass: 'border-brass-500/40 bg-brass-900 text-brass-300',
    success: 'border-sage-500/40 bg-sage-900 text-sage-400',
    error: 'border-clay-500/40 bg-clay-900 text-clay-400',
    warning: 'border-ochre-400/40 bg-ochre-900 text-ochre-400',
    info: 'border-slate-400/40 bg-slate-900 text-slate-400',
}[props.color]));
</script>
