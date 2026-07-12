<template>
    <div ref="root" class="relative">
        <label v-if="label" class="mb-1.5 block font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">
            {{ label }}
        </label>
        <button
            type="button"
            :disabled="disabled"
            class="flex w-full items-center justify-between gap-2 rounded-md border bg-ink-900 px-3 py-2 text-left text-[15px] transition-colors focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            :class="errorMessage ? 'border-clay-500' : open ? 'border-brass-500' : 'border-ink-700 hover:border-ink-600'"
            @click="toggle"
            @keydown.down.prevent="open ? move(1) : toggle()"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="open ? pick(highlighted) : toggle()"
            @keydown.esc="open = false"
        >
            <span class="truncate" :class="selectedLabel ? 'text-bone-100' : 'text-bone-700'">
                {{ selectedLabel || placeholder }}
            </span>
            <span class="flex shrink-0 items-center gap-1">
                <span
                    v-if="clearable && modelValue !== null && modelValue !== ''"
                    class="rounded p-0.5 text-bone-700 hover:text-bone-300"
                    @click.stop="$emit('update:modelValue', null)"
                >
                    <X class="h-3.5 w-3.5" />
                </span>
                <ChevronDown class="h-4 w-4 text-bone-700 transition-transform" :class="open ? 'rotate-180' : ''" />
            </span>
        </button>

        <Transition name="ui-pop">
            <ul
                v-if="open"
                class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-ink-700 bg-ink-850 py-1 shadow-xl scrollbar-thin"
            >
                <li v-if="!normalizedItems.length" class="px-3 py-2 text-sm text-bone-700">—</li>
                <li
                    v-for="(item, i) in normalizedItems"
                    :key="i"
                    class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm"
                    :class="[
                        i === highlighted ? 'bg-ink-800' : '',
                        isSelected(item) ? 'text-brass-400' : 'text-bone-300',
                    ]"
                    @mouseenter="highlighted = i"
                    @click="pick(i)"
                >
                    <span class="truncate">{{ item.title }}</span>
                    <Check v-if="isSelected(item)" class="h-3.5 w-3.5 shrink-0" />
                </li>
            </ul>
        </Transition>

        <p v-if="errorMessage" class="mt-1 text-xs text-clay-400">{{ errorMessage }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-bone-700">{{ hint }}</p>
    </div>
</template>

<script setup>
import { ref, computed, toRef, onMounted, onBeforeUnmount } from 'vue';
import { ChevronDown, Check, X } from 'lucide-vue-next';
import { useFormField } from './useFormField';

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, Object], default: null },
    items: { type: Array, default: () => [] },
    itemTitle: { type: String, default: 'title' },
    itemValue: { type: String, default: 'value' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    rules: { type: Array, default: () => [] },
    clearable: Boolean,
    disabled: Boolean,
    // when true, modelValue is the whole item object
    returnObject: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const highlighted = ref(-1);
const root = ref(null);

const normalizedItems = computed(() =>
    props.items.map((it) =>
        typeof it === 'object' && it !== null
            ? { title: it[props.itemTitle], value: props.returnObject ? it : it[props.itemValue], raw: it }
            : { title: String(it), value: it, raw: it }
    )
);

function valueOf(v) {
    return props.returnObject && v && typeof v === 'object' ? v[props.itemValue] : v;
}

function isSelected(item) {
    return valueOf(item.value) === valueOf(props.modelValue);
}

const selectedLabel = computed(() => normalizedItems.value.find((i) => isSelected(i))?.title ?? '');

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) highlighted.value = normalizedItems.value.findIndex((i) => isSelected(i));
}

function move(dir) {
    if (!open.value) return;
    const n = normalizedItems.value.length;
    highlighted.value = (highlighted.value + dir + n) % n;
}

function pick(i) {
    const item = normalizedItems.value[i];
    if (!item) return;
    emit('update:modelValue', item.value);
    open.value = false;
}

function onClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false;
}
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

const { errorMessage, validate, resetValidation } = useFormField(props, toRef(props, 'modelValue'));
defineExpose({ validate, resetValidation });
</script>

<style>
.ui-pop-enter-active,
.ui-pop-leave-active {
    transition: opacity 0.12s ease, transform 0.12s ease;
}
.ui-pop-enter-from,
.ui-pop-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
