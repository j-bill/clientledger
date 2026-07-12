<template>
    <Teleport to="body">
        <Transition name="ui-dialog">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-[8vh] backdrop-blur-[2px] sm:p-6 sm:pt-[10vh]"
                @mousedown.self="onBackdrop"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    class="ui-dialog-panel w-full rounded-lg border border-ink-700 bg-ink-900 shadow-2xl"
                    :style="{ maxWidth }"
                    @keydown.esc="onEsc"
                >
                    <header v-if="title || $slots.title" class="ledger-rule flex items-center justify-between px-5 py-4">
                        <h2 class="text-base font-semibold text-bone-100">
                            <slot name="title">{{ title }}</slot>
                        </h2>
                        <button
                            v-if="!persistent"
                            type="button"
                            class="rounded p-1 text-bone-700 hover:bg-ink-800 hover:text-bone-300"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </header>
                    <div class="max-h-[70vh] overflow-y-auto p-5 scrollbar-thin">
                        <slot />
                    </div>
                    <footer v-if="$slots.actions" class="flex items-center justify-end gap-2 border-t border-ink-700/60 px-5 py-3">
                        <slot name="actions" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch, onBeforeUnmount } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: Boolean,
    title: { type: String, default: '' },
    maxWidth: { type: String, default: '560px' },
    persistent: Boolean,
});

const emit = defineEmits(['update:modelValue']);

function close() {
    emit('update:modelValue', false);
}

function onBackdrop() {
    if (!props.persistent) close();
}

function onEsc() {
    if (!props.persistent) close();
}

// Scroll lock while any dialog is open
watch(
    () => props.modelValue,
    (open) => {
        document.documentElement.classList.toggle('overflow-hidden', open);
    }
);
onBeforeUnmount(() => document.documentElement.classList.remove('overflow-hidden'));
</script>

<style>
.ui-dialog-enter-active,
.ui-dialog-leave-active {
    transition: opacity 0.15s ease;
}
.ui-dialog-enter-active .ui-dialog-panel {
    transition: transform 0.18s ease, opacity 0.15s ease;
}
.ui-dialog-enter-from,
.ui-dialog-leave-to {
    opacity: 0;
}
.ui-dialog-enter-from .ui-dialog-panel {
    transform: translateY(8px) scale(0.99);
}
</style>
