<template>
    <div class="flex items-start gap-3 rounded-md border px-4 py-3 text-sm" :class="typeClasses">
        <component :is="iconComponent" class="mt-0.5 h-4 w-4 shrink-0" />
        <div class="min-w-0 flex-1">
            <p v-if="title" class="mb-0.5 font-semibold">{{ title }}</p>
            <slot>{{ text }}</slot>
        </div>
        <button v-if="closable" type="button" class="shrink-0 opacity-60 hover:opacity-100" @click="$emit('close')">
            <X class="h-4 w-4" />
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { CircleCheck, CircleAlert, TriangleAlert, Info, X } from 'lucide-vue-next';

const props = defineProps({
    type: { type: String, default: 'info' }, // success | error | warning | info
    title: { type: String, default: '' },
    text: { type: String, default: '' },
    closable: Boolean,
});

defineEmits(['close']);

const typeClasses = computed(() => ({
    success: 'border-sage-500/40 bg-sage-900 text-sage-400',
    error: 'border-clay-500/40 bg-clay-900 text-clay-400',
    warning: 'border-ochre-400/40 bg-ochre-900 text-ochre-400',
    info: 'border-slate-400/40 bg-slate-900 text-slate-400',
}[props.type]));

const iconComponent = computed(() => ({
    success: CircleCheck,
    error: CircleAlert,
    warning: TriangleAlert,
    info: Info,
}[props.type]));
</script>
