import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import de from './locales/de.json'
import fr from './locales/fr.json'
import it from './locales/it.json'
import es from './locales/es.json'

export const i18n = createI18n({
  legacy: false, // use Composition API mode
  locale: 'en', // default locale
  fallbackLocale: 'en', // fallback locale
  messages: {
    en,
    de,
    fr,
    it,
    es
  },
  globalInjection: true,
  missingWarn: false,
  fallbackWarn: false
})

export default i18n
