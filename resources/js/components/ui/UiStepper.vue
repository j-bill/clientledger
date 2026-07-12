<template>
    <div>
        <ol class="flex items-center gap-2">
            <li v-for="(step, i) in steps" :key="i" class="flex flex-1 items-center gap-2">
                <span
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border font-mono text-xs transition-colors"
                    :class="
                        i + 1 < modelValue
                            ? 'border-brass-500 bg-brass-500 text-ink-950'
                            : i + 1 === modelValue
                              ? 'border-brass-500 text-brass-400'
                              : 'border-ink-600 text-bone-700'
                    "
                >
                    <Check v-if="i + 1 < modelValue" class="h-3.5 w-3.5" />
                    <template v-else>{{ i + 1 }}</template>
                </span>
                <span
                    class="hidden text-sm sm:block"
                    :class="i + 1 === modelValue ? 'font-medium text-bone-100' : 'text-bone-700'"
                >
                    {{ step }}
                </span>
                <span v-if="i < steps.length - 1" class="h-px flex-1 bg-ink-700"></span>
            </li>
        </ol>
        <div class="mt-6">
            <slot :step="modelValue" />
        </div>
    </div>
</template>

<script setup>
import { Check } from 'lucide-vue-next';

defineProps({
    modelValue: { type: Number, required: true },
    steps: { type: Array, required: true }, // labels
});

defineEmits(['update:modelValue']);
</script>
