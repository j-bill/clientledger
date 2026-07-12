<template>
    <section class="rounded-lg border border-ink-700/60 bg-ink-900" :class="flat ? '' : 'shadow-[0_1px_0_0_rgba(0,0,0,0.4)]'">
        <header v-if="title || $slots.title || $slots.actions" class="ledger-rule flex items-center justify-between gap-3 px-5 py-4">
            <h2 class="text-[15px] font-semibold text-bone-100">
                <slot name="title">{{ title }}</slot>
            </h2>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </header>
        <div :class="dense ? '' : 'p-5'">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="flex items-center justify-end gap-2 border-t border-ink-700/60 px-5 py-3">
            <slot name="footer" />
        </footer>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    dense: Boolean, // no body padding (tables)
    flat: Boolean,
});
</script>
