<template>
    <div :class="wrapperClass">
        <label v-if="label" :for="id" class="mb-1.5 block font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">
            {{ label }}
        </label>
        <div class="relative">
            <component
                :is="icon"
                v-if="icon"
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bone-700"
            />
            <input
                :id="id"
                ref="inputEl"
                v-bind="$attrs"
                :value="modelValue"
                :type="type"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                class="w-full rounded-md border bg-ink-900 py-2 text-[15px] text-bone-100 transition-colors placeholder:text-bone-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                :class="[
                    icon ? 'pl-9' : 'pl-3',
                    clearable || suffix ? 'pr-9' : 'pr-3',
                    errorMessage ? 'border-clay-500' : 'border-ink-700 focus:border-brass-500',
                ]"
                @input="onInput"
            />
            <span v-if="suffix" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 font-mono text-xs text-bone-500">{{ suffix }}</span>
            <button
                v-else-if="clearable && modelValue"
                type="button"
                tabindex="-1"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-bone-700 hover:text-bone-300"
                @click="clear"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>
        <p v-if="errorMessage" class="mt-1 text-xs text-clay-400">{{ errorMessage }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-bone-700">{{ hint }}</p>
    </div>
</template>

<script setup>
import { toRef, ref, useId } from 'vue';
import { X } from 'lucide-vue-next';
import { useFormField } from './useFormField';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    rules: { type: Array, default: () => [] },
    icon: { type: [Object, Function], default: null },
    suffix: { type: String, default: '' },
    clearable: Boolean,
    disabled: Boolean,
    readonly: Boolean,
    wrapperClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
const id = useId();
const inputEl = ref(null);

const { errorMessage, validate, resetValidation } = useFormField(props, toRef(props, 'modelValue'));

function onInput(e) {
    const v = e.target.value;
    emit('update:modelValue', props.type === 'number' && v !== '' ? Number(v) : v);
}

function clear() {
    emit('update:modelValue', props.type === 'number' ? null : '');
}

defineExpose({ validate, resetValidation, focus: () => inputEl.value?.focus() });
</script>
