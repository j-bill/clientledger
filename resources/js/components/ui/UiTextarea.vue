<template>
    <div>
        <label v-if="label" :for="id" class="mb-1.5 block font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">
            {{ label }}
        </label>
        <textarea
            :id="id"
            v-bind="$attrs"
            :value="modelValue"
            :rows="rows"
            :placeholder="placeholder"
            :disabled="disabled"
            :readonly="readonly"
            class="w-full rounded-md border bg-ink-900 px-3 py-2 text-[15px] text-bone-100 transition-colors placeholder:text-bone-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            :class="errorMessage ? 'border-clay-500' : 'border-ink-700 focus:border-brass-500'"
            @input="$emit('update:modelValue', $event.target.value)"
        ></textarea>
        <p v-if="errorMessage" class="mt-1 text-xs text-clay-400">{{ errorMessage }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-bone-700">{{ hint }}</p>
    </div>
</template>

<script setup>
import { toRef, useId } from 'vue';
import { useFormField } from './useFormField';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    rows: { type: [Number, String], default: 3 },
    rules: { type: Array, default: () => [] },
    disabled: Boolean,
    readonly: Boolean,
});

defineEmits(['update:modelValue']);
const id = useId();

const { errorMessage, validate, resetValidation } = useFormField(props, toRef(props, 'modelValue'));
defineExpose({ validate, resetValidation });
</script>
