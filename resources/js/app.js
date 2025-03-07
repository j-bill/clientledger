import { createApp } from 'vue';
import '@mdi/font/css/materialdesignicons.css';

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { de, en } from 'vuetify/locale';

import "vuetify/dist/vuetify.min.css";

/* Theme variables */
import "@mdi/font/css/materialdesignicons.css";

// Vue Router
import router from './router';

// Pinia
import { createPinia } from "pinia";
import { store } from "./store";

// Components
import App from './App.vue';
import axios from 'axios';

// create store
const pinia = createPinia();

const dark = {
    dark: true,
    colors: {
        primary: '#8640ea',
    },
};

// create app
const app = createApp(App);
const vuetify = createVuetify({
    components,
    directives,
    locale: {
        locale: 'de',
        fallback: 'en',
        messages: { de, en },
    },
    theme: {
        defaultTheme: 'dark',
        themes: {
            dark,
        },
    },
});

// use
app.use(vuetify);
app.use(pinia);

// get store
const storeStore = store(pinia);

async function initApp() {
    try {
        // Attempt to fetch authenticated user
        await storeStore.getAuthUser();
    } catch (error) {
        // If promise rejects, user is not authenticated.
        // console.dir('app - user is not authenticated: ' + error);
        router.push({ name: 'Login' });
    }

    // Mount app
    app.use(router);
    app.mount('#app');
}

initApp();

