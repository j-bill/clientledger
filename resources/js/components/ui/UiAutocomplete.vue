<template>
    <div ref="root" class="relative">
        <label v-if="label" class="mb-1.5 block font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">
            {{ label }}
        </label>
        <div class="relative">
            <input
                :value="open ? query : selectedLabel"
                :placeholder="placeholder || selectedLabel"
                :disabled="disabled"
                class="w-full rounded-md border bg-ink-900 py-2 pl-3 pr-9 text-[15px] text-bone-100 transition-colors placeholder:text-bone-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                :class="errorMessage ? 'border-clay-500' : open ? 'border-brass-500' : 'border-ink-700'"
                @focus="openList"
                @input="query = $event.target.value; open = true; highlighted = 0"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="pick(highlighted)"
                @keydown.esc="open = false"
            />
            <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bone-700 transition-transform" :class="open ? 'rotate-180' : ''" />
        </div>

        <Transition name="ui-pop">
            <ul v-if="open" class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-ink-700 bg-ink-850 py-1 shadow-xl scrollbar-thin">
                <li v-if="!filtered.length" class="px-3 py-2 text-sm text-bone-700">—</li>
                <li
                    v-for="(item, i) in filtered"
                    :key="i"
                    class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm"
                    :class="[i === highlighted ? 'bg-ink-800' : '', isSelected(item) ? 'text-brass-400' : 'text-bone-300']"
                    @mouseenter="highlighted = i"
                    @mousedown.prevent="pick(i)"
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
import { ChevronDown, Check } from 'lucide-vue-next';
import { useFormField } from './useFormField';

const props = defineProps({
    modelValue: { type: [String, Number, Object], default: null },
    items: { type: Array, default: () => [] },
    itemTitle: { type: String, default: 'title' },
    itemValue: { type: String, default: 'value' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    rules: { type: Array, default: () => [] },
    disabled: Boolean,
    returnObject: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const highlighted = ref(0);
const root = ref(null);

const normalizedItems = computed(() =>
    props.items.map((it) =>
        typeof it === 'object' && it !== null
            ? { title: String(it[props.itemTitle] ?? ''), value: props.returnObject ? it : it[props.itemValue] }
            : { title: String(it), value: it }
    )
);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return normalizedItems.value;
    return normalizedItems.value.filter((i) => i.title.toLowerCase().includes(q));
});

function valueOf(v) {
    return props.returnObject && v && typeof v === 'object' ? v[props.itemValue] : v;
}
function isSelected(item) {
    return valueOf(item.value) === valueOf(props.modelValue);
}

const selectedLabel = computed(() => normalizedItems.value.find((i) => isSelected(i))?.title ?? '');

function openList() {
    if (props.disabled) return;
    query.value = '';
    open.value = true;
    highlighted.value = 0;
}
function move(dir) {
    if (!open.value) return openList();
    const n = filtered.value.length;
    if (n) highlighted.value = (highlighted.value + dir + n) % n;
}
function pick(i) {
    const item = filtered.value[i];
    if (!item) return;
    emit('update:modelValue', item.value);
    open.value = false;
    query.value = '';
}

function onClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false;
}
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

const { errorMessage, validate, resetValidation } = useFormField(props, toRef(props, 'modelValue'));
defineExpose({ validate, resetValidation });
</script>
