import { ref, inject, onMounted, onBeforeUnmount, watch } from 'vue';

// Shared validation logic for kit form fields. Mirrors Vuetify's `rules` API:
// each rule is (value) => true | string.
export function useFormField(props, value) {
    const errorMessage = ref('');

    function validate() {
        for (const rule of props.rules || []) {
            const res = rule(value.value);
            if (res !== true) {
                errorMessage.value = typeof res === 'string' ? res : '';
                return false;
            }
        }
        errorMessage.value = '';
        return true;
    }

    function resetValidation() {
        errorMessage.value = '';
    }

    const form = inject('ui-form', null);
    const handle = { validate, resetValidation };
    onMounted(() => form?.register(handle));
    onBeforeUnmount(() => form?.unregister(handle));

    // Re-validate live once a field has shown an error
    watch(value, () => {
        if (errorMessage.value) validate();
    });

    return { errorMessage, validate, resetValidation };
}
