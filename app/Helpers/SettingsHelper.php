<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Get all settings as a key-value array
     */
    public static function all()
    {
        return Cache::remember('all_settings', 3600, function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear settings cache
     */
    public static function clearCache()
    {
        Cache::forget('all_settings');
        $keys = Setting::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting_{$key}");
        }
    }

    /**
     * Apply mail configuration from settings
     */
    public static function applyMailConfig()
    {
        $mailHost = self::get('mail_host');
        
        // Only apply if mail settings exist
        if ($mailHost) {
            config([
                'mail.mailers.smtp.host' => $mailHost,
                'mail.mailers.smtp.port' => self::get('mail_port', 587),
                'mail.mailers.smtp.username' => self::get('mail_username'),
                'mail.mailers.smtp.password' => self::get('mail_password'),
                'mail.mailers.smtp.encryption' => self::get('mail_encryption', 'tls'),
                'mail.from.address' => self::get('mail_from_address', config('mail.from.address')),
                'mail.from.name' => self::get('mail_from_name', config('mail.from.name')),
            ]);
        }
    }
}
