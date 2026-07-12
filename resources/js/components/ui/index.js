import UiAlert from './UiAlert.vue';
import UiAutocomplete from './UiAutocomplete.vue';
import UiAvatar from './UiAvatar.vue';
import UiButton from './UiButton.vue';
import UiCard from './UiCard.vue';
import UiCheckbox from './UiCheckbox.vue';
import UiChip from './UiChip.vue';
import UiDataTable from './UiDataTable.vue';
import UiDialog from './UiDialog.vue';
import UiFileInput from './UiFileInput.vue';
import UiForm from './UiForm.vue';
import UiInput from './UiInput.vue';
import UiMenu from './UiMenu.vue';
import UiMenuItem from './UiMenuItem.vue';
import UiSelect from './UiSelect.vue';
import UiSpinner from './UiSpinner.vue';
import UiStepper from './UiStepper.vue';
import UiSwitch from './UiSwitch.vue';
import UiTabs from './UiTabs.vue';
import UiTextarea from './UiTextarea.vue';
import UiTooltip from './UiTooltip.vue';

const components = {
    UiAlert,
    UiAutocomplete,
    UiAvatar,
    UiButton,
    UiCard,
    UiCheckbox,
    UiChip,
    UiDataTable,
    UiDialog,
    UiFileInput,
    UiForm,
    UiInput,
    UiMenu,
    UiMenuItem,
    UiSelect,
    UiSpinner,
    UiStepper,
    UiSwitch,
    UiTabs,
    UiTextarea,
    UiTooltip,
};

// Registers the whole kit globally: pages use <ui-button> etc. without imports.
export default {
    install(app) {
        for (const [name, component] of Object.entries(components)) {
            app.component(name, component);
        }
    },
};
