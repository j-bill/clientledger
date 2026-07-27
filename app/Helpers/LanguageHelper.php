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
    public static function getCurrentLanguage(): string
    {
        $language = SettingsHelper::get('language', 'en');

        // Ensure the language is supported
        if (! is_string($language) || ! self::isLanguageSupported($language)) {
            return 'en';
        }

        return $language;
    }

    /**
     * Check if a language is supported
     *
     * @param  mixed  $languageCode
     */
    public static function isLanguageSupported($languageCode): bool
    {
        return is_string($languageCode) && isset(self::SUPPORTED_LANGUAGES[$languageCode]);
    }

    /**
     * Get all supported languages as an array
     *
     * @return array<string, string>
     */
    public static function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Get language name by code
     */
    public static function getLanguageName(string $languageCode): ?string
    {
        return self::SUPPORTED_LANGUAGES[$languageCode] ?? null;
    }

    /**
     * Get list of language codes
     *
     * @return array<int, string>
     */
    public static function getLanguageCodes(): array
    {
        return array_keys(self::SUPPORTED_LANGUAGES);
    }
}
