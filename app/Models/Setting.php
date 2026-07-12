<?php

// Setting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Setting keys whose value is encrypted at rest. Reads are decrypted
     * transparently here so every existing caller (SettingsHelper, direct
     * model access) keeps working; only the HTTP layer is responsible for
     * masking these before they reach the browser.
     */
    public const SENSITIVE_KEYS = ['openai_api_key'];

    public function setValueAttribute($value)
    {
        if (in_array($this->key, self::SENSITIVE_KEYS, true) && $value !== '' && $value !== null) {
            $value = Crypt::encryptString($value);
        }

        $this->attributes['value'] = $value;
    }

    public function getValueAttribute($value)
    {
        if (! in_array($this->key, self::SENSITIVE_KEYS, true) || $value === '' || $value === null) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Value predates encryption support. Upgrade it in place so it's
            // encrypted from the next read onward, then return it as-is now.
            static::where('id', $this->id)->update(['value' => Crypt::encryptString($value)]);

            return $value;
        }
    }
}
