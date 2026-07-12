<template>
    <div>
        <div class="relative overflow-x-auto scrollbar-thin">
            <!-- Loading bar -->
            <div v-if="loading" class="absolute inset-x-0 top-0 h-0.5 overflow-hidden">
                <div class="ui-table-progress h-full w-1/3 rounded-full bg-brass-500/70"></div>
            </div>

            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="ledger-rule">
                        <th
                            v-for="header in headers"
                            :key="header.key"
                            class="whitespace-nowrap px-4 py-3 font-mono text-[11px] font-medium uppercase tracking-[0.12em] text-bone-500"
                            :class="[
                                header.align === 'end' ? 'text-right' : '',
                                header.sortable !== false ? 'cursor-pointer select-none hover:text-bone-300' : '',
                            ]"
                            @click="header.sortable !== false && toggleSort(header.key)"
                        >
                            <span class="inline-flex items-center gap-1" :class="header.align === 'end' ? 'flex-row-reverse' : ''">
                                {{ header.title }}
                                <component
                                    :is="sortIcon(header.key)"
                                    v-if="sortIcon(header.key)"
                                    class="h-3 w-3 text-brass-400"
                                />
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!pagedItems.length && !loading">
                        <td :colspan="headers.length" class="px-4 py-10 text-center text-bone-700">
                            {{ noDataText }}
                        </td>
                    </tr>
                    <tr
                        v-for="(item, ri) in pagedItems"
                        :key="item.id ?? ri"
                        class="ledger-rule transition-colors hover:bg-ink-850"
                        :class="rowProps ? rowProps({ item })?.class : ''"
                        v-bind="rowProps ? { ...rowProps({ item }), class: undefined } : {}"
                    >
                        <td
                            v-for="header in headers"
                            :key="header.key"
                            class="px-4 py-2.5 text-bone-300"
                            :class="header.align === 'end' ? 'text-right' : ''"
                        >
                            <slot :name="`item.${header.key}`" :item="item">
                                {{ resolve(item, header.key) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer: pagination -->
        <div v-if="!hideFooter" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 font-mono text-xs text-bone-500">
            <div class="flex items-center gap-2">
                <span>{{ perPageText }}</span>
                <select
                    :value="perPage"
                    class="rounded border border-ink-700 bg-ink-900 px-1.5 py-1 text-bone-300 focus:outline-none"
                    @change="setPerPage(Number($event.target.value))"
                >
                    <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <span class="tabular-nums">{{ rangeText }}</span>
                <span class="flex items-center gap-1">
                    <button
                        type="button"
                        class="rounded p-1 text-bone-500 hover:bg-ink-800 hover:text-bone-300 disabled:opacity-40 disabled:hover:bg-transparent"
                        :disabled="page <= 1"
                        @click="setPage(page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded p-1 text-bone-500 hover:bg-ink-800 hover:text-bone-300 disabled:opacity-40 disabled:hover:bg-transparent"
                        :disabled="page >= pageCount"
                        @click="setPage(page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { ChevronLeft, ChevronRight, ChevronUp, ChevronDown } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    // [{ title, key, sortable?, align? ('end') }]
    headers: { type: Array, required: true },
    items: { type: Array, default: () => [] },
    loading: Boolean,
    search: { type: String, default: '' },
    // initial sort: [{ key, order: 'asc'|'desc' }]
    sortBy: { type: Array, default: () => [] },
    // fn({ item }) => attrs (Vuetify row-props compatible)
    rowProps: { type: Function, default: null },
    itemsPerPage: { type: Number, default: 10 },
    hideFooter: Boolean,
    // server mode: total count on server; sorting/paging delegated via @update:options
    serverItemsLength: { type: Number, default: null },
});

const emit = defineEmits(['update:options', 'update:itemsPerPage']);

const { t } = useI18n();
const noDataText = computed(() => t('common.noData'));
const perPageText = computed(() => t('common.rowsPerPage'));

const isServer = computed(() => props.serverItemsLength !== null);

const page = ref(1);
const perPage = ref(props.itemsPerPage);
const sort = ref([...props.sortBy]);

watch(() => props.itemsPerPage, (v) => (perPage.value = v));
watch(() => props.search, () => (page.value = 1));

function resolve(item, key) {
    return key.split('.').reduce((o, k) => (o == null ? o : o[k]), item);
}

function toggleSort(key) {
    const current = sort.value[0];
    if (current?.key === key) {
        sort.value = current.order === 'asc' ? [{ key, order: 'desc' }] : [];
    } else {
        sort.value = [{ key, order: 'asc' }];
    }
    emitOptions();
}

function sortIcon(key) {
    const s = sort.value[0];
    if (s?.key !== key) return null;
    return s.order === 'asc' ? ChevronUp : ChevronDown;
}

const filtered = computed(() => {
    if (isServer.value || !props.search) return props.items;
    const q = props.search.toLowerCase();
    return props.items.filter((item) =>
        props.headers.some((h) => {
            const v = resolve(item, h.key);
            return v != null && String(v).toLowerCase().includes(q);
        })
    );
});

const sorted = computed(() => {
    if (isServer.value || !sort.value.length) return filtered.value;
    const { key, order } = sort.value[0];
    const dir = order === 'desc' ? -1 : 1;
    return [...filtered.value].sort((a, b) => {
        const va = resolve(a, key);
        const vb = resolve(b, key);
        if (va == null) return 1;
        if (vb == null) return -1;
        if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir;
        return String(va).localeCompare(String(vb), undefined, { numeric: true }) * dir;
    });
});

const totalItems = computed(() => (isServer.value ? props.serverItemsLength : sorted.value.length));
const pageCount = computed(() => Math.max(1, Math.ceil(totalItems.value / perPage.value)));

const pagedItems = computed(() => {
    if (isServer.value) return props.items;
    const start = (page.value - 1) * perPage.value;
    return sorted.value.slice(start, start + perPage.value);
});

const rangeText = computed(() => {
    if (!totalItems.value) return '0 / 0';
    const start = (page.value - 1) * perPage.value + 1;
    const end = Math.min(page.value * perPage.value, totalItems.value);
    return `${start}–${end} / ${totalItems.value}`;
});

function setPage(p) {
    page.value = Math.min(Math.max(1, p), pageCount.value);
    emitOptions();
}

function setPerPage(n) {
    perPage.value = n;
    page.value = 1;
    emit('update:itemsPerPage', n);
    emitOptions();
}

function emitOptions() {
    emit('update:options', {
        page: page.value,
        itemsPerPage: perPage.value,
        sortBy: sort.value,
    });
}

// Server tables expect an initial load, mirroring Vuetify's v-data-table-server
if (isServer.value) {
    emitOptions();
}
</script>

<style>
.ui-table-progress {
    animation: ui-table-slide 1.2s ease-in-out infinite;
}
@keyframes ui-table-slide {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
</style>
