import { createApp } from "vue";

// Vue Router
import router from "./router";

// Pinia
import { createPinia } from "pinia";
import { store } from "./store";

// Vue i18n
import i18n from "./i18n";

// UI kit (global components)
import UiKit from "./components/ui";

// Components
import App from "./App.vue";
import axios from "axios";
import "./bootstrap";

// create store
const pinia = createPinia();

// create app
const app = createApp(App);

// use
app.use(UiKit);
app.use(pinia);
app.use(i18n);

// Expose i18n globally for store access
window.$i18n = i18n;

// get store
const storeStore = store(pinia);

async function initApp() {
  try {
    // Attempt to fetch authenticated user
    await storeStore.getAuthUser();
    
    // Fetch settings and apply language
    try {
      await storeStore.fetchSettings();
      const language = storeStore.settings.language || 'en';
      if (i18n.global.locale.value !== language) {
        i18n.global.locale.value = language;
        localStorage.setItem('appLanguage', language);
        document.documentElement.lang = language;
      }
    } catch (error) {
      console.error('Error loading settings:', error);
      // Try to load language from localStorage as fallback
      const savedLanguage = localStorage.getItem('appLanguage');
      if (savedLanguage && ['en', 'de', 'fr', 'it', 'es'].includes(savedLanguage)) {
        i18n.global.locale.value = savedLanguage;
        document.documentElement.lang = savedLanguage;
      }
    }
  } catch (error) {
    // If promise rejects, user is not authenticated.
    // console.dir('app - user is not authenticated: ' + error);
    router.push({ name: "Login" });
  }

  // Mount app
  app.use(router);
  app.mount("#app");
}

initApp();
