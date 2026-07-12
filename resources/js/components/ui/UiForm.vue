<template>
    <form novalidate @submit.prevent="$emit('submit', $event)">
        <slot />
    </form>
</template>

<script setup>
import { provide, ref } from 'vue';

defineEmits(['submit']);

const inputs = ref(new Set());
provide('ui-form', {
    register: (i) => inputs.value.add(i),
    unregister: (i) => inputs.value.delete(i),
});

async function validate() {
    let valid = true;
    for (const input of inputs.value) {
        if (!input.validate()) valid = false;
    }
    return { valid };
}

function resetValidation() {
    inputs.value.forEach((i) => i.resetValidation());
}

defineExpose({ validate, resetValidation });
</script>
