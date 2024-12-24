import { createApp } from 'vue';
import '@mdi/font/css/materialdesignicons.css'; // Ensure you are using css-loader

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { de, en } from 'vuetify/locale';
import { VTimePicker } from 'vuetify/labs/VTimePicker';

// Components
import App from './App.vue';

const vuetify = createVuetify({
    icons: {
        defaultSet: 'mdi', // This is already the default value - only for display purposes
    },
    locale: {
        locale: 'de',
        fallback: 'en',
        messages: {
            de,
            en,
        },
    },
    components: {
        VTimePicker,
        ...components,
    },
    directives,
});

createApp(App).use(vuetify).mount('#app');
