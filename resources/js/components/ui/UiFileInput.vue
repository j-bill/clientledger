<template>
    <div>
        <label v-if="label" class="mb-1.5 block font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">
            {{ label }}
        </label>
        <label
            class="flex cursor-pointer items-center gap-3 rounded-md border border-dashed px-3 py-2.5 transition-colors"
            :class="errorMessage ? 'border-clay-500' : 'border-ink-600 hover:border-brass-500/60'"
        >
            <Paperclip class="h-4 w-4 shrink-0 text-bone-700" />
            <span class="min-w-0 flex-1 truncate text-sm" :class="fileName ? 'text-bone-100' : 'text-bone-700'">
                {{ fileName || placeholder }}
            </span>
            <button
                v-if="fileName"
                type="button"
                class="shrink-0 rounded p-0.5 text-bone-700 hover:text-bone-300"
                @click.prevent="clear"
            >
                <X class="h-3.5 w-3.5" />
            </button>
            <input ref="inputEl" type="file" class="hidden" :accept="accept" @change="onChange" />
        </label>
        <p v-if="errorMessage" class="mt-1 text-xs text-clay-400">{{ errorMessage }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-bone-700">{{ hint }}</p>
    </div>
</template>

<script setup>
import { ref, computed, toRef } from 'vue';
import { Paperclip, X } from 'lucide-vue-next';
import { useFormField } from './useFormField';

const props = defineProps({
    modelValue: { type: [File, Array], default: null },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    accept: { type: String, default: '' },
    rules: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);
const inputEl = ref(null);

const fileName = computed(() => {
    const v = props.modelValue;
    if (!v) return '';
    return Array.isArray(v) ? v.map((f) => f.name).join(', ') : v.name;
});

function onChange(e) {
    emit('update:modelValue', e.target.files[0] ?? null);
}

function clear() {
    if (inputEl.value) inputEl.value.value = '';
    emit('update:modelValue', null);
}

const { errorMessage, validate, resetValidation } = useFormField(props, toRef(props, 'modelValue'));
defineExpose({ validate, resetValidation });
</script>
