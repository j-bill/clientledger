<template>
    <div ref="root" class="relative inline-block">
        <div @click="open = !open">
            <slot name="activator" :open="open" />
        </div>
        <Transition name="ui-pop">
            <div
                v-if="open"
                class="absolute z-40 mt-1 min-w-44 overflow-hidden rounded-md border border-ink-700 bg-ink-850 py-1 shadow-xl"
                :class="align === 'right' ? 'right-0' : 'left-0'"
                @click="closeOnClick && (open = false)"
            >
                <slot />
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

defineProps({
    align: { type: String, default: 'left' }, // left | right
    closeOnClick: { type: Boolean, default: true },
});

const open = ref(false);
const root = ref(null);

function onClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false;
}
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

defineExpose({ close: () => (open.value = false) });
</script>
