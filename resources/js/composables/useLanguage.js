import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

/**
 * Composable for managing application language and localization
 */
export function useLanguage() {
  const i18n = useI18n()
  const router = useRouter()

  /**
   * Set the application language
   */
  const setLanguage = (languageCode) => {
    if (i18n.locale.value !== languageCode) {
      i18n.locale.value = languageCode
      localStorage.setItem('appLanguage', languageCode)
      document.documentElement.lang = languageCode
    }
  }

  /**
   * Get the current language
   */
  const getCurrentLanguage = () => {
    return i18n.locale.value
  }

  /**
   * Get available languages
   */
  const getAvailableLanguages = () => {
    return Object.keys(i18n.messages)
  }

  /**
   * Initialize language from settings or localStorage
   * Call this on app initialization
   */
  const initializeLanguage = async () => {
    try {
      // First check if we have a saved preference
      const savedLanguage = localStorage.getItem('appLanguage')
      if (savedLanguage && getAvailableLanguages().includes(savedLanguage)) {
        setLanguage(savedLanguage)
        return
      }

      // Otherwise try to load from settings
      // Note: This assumes the settings have been loaded
      // You may need to adjust based on your store structure
      const allSettings = localStorage.getItem('allSettings')
      if (allSettings) {
        const settings = JSON.parse(allSettings)
        const language = settings.language || 'en'
        if (getAvailableLanguages().includes(language)) {
          setLanguage(language)
          return
        }
      }

      // Default to English
      setLanguage('en')
    } catch (error) {
      console.error('Error initializing language:', error)
      setLanguage('en')
    }
  }

  /**
   * Translate a key with optional parameters
   */
  const t = (key, defaultMessage = '') => {
    try {
      const translated = i18n.t(key)
      return translated !== key ? translated : defaultMessage
    } catch (error) {
      return defaultMessage
    }
  }

  return {
    setLanguage,
    getCurrentLanguage,
    getAvailableLanguages,
    initializeLanguage,
    t,
    i18n
  }
}
