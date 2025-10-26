<?php

namespace App\Helpers;

use App\Models\Setting;

class LanguageHelper
{
    /**
     * Supported languages in the application
     */
    const SUPPORTED_LANGUAGES = [
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'it' => 'Italiano',
        'es' => 'Español',
    ];

    /**
     * Get the current language setting
     * Returns the language code stored in settings, or default to 'en'
     */
    public static function getCurrentLanguage()
    {
        $language = SettingsHelper::get('language', 'en');
        
        // Ensure the language is supported
        if (!self::isLanguageSupported($language)) {
            return 'en';
        }
        
        return $language;
    }

    /**
     * Check if a language is supported
     */
    public static function isLanguageSupported($languageCode)
    {
        return isset(self::SUPPORTED_LANGUAGES[$languageCode]);
    }

    /**
     * Get all supported languages as an array
     */
    public static function getSupportedLanguages()
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Get language name by code
     */
    public static function getLanguageName($languageCode)
    {
        return self::SUPPORTED_LANGUAGES[$languageCode] ?? null;
    }

    /**
     * Get list of language codes
     */
    public static function getLanguageCodes()
    {
        return array_keys(self::SUPPORTED_LANGUAGES);
    }
}
