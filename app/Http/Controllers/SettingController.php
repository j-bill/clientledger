<?php

namespace App\Http\Controllers;

use App\Helpers\LanguageHelper;
use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Services\InvoicePdfGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->map(function (Setting $setting) {
            $setting->value = $this->maskIfSensitive($setting->key, $setting->value);

            return $setting;
        });

        return response()->json($settings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request, [
            'key' => 'required|string|max:255|unique:settings',
            'value' => 'required|string',
        ]);

        $setting = Setting::create($validated);
        $setting->value = $this->maskIfSensitive($setting->key, $setting->value);

        return response()->json($setting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting): JsonResponse
    {
        $setting->value = $this->maskIfSensitive($setting->key, $setting->value);

        return response()->json($setting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting): JsonResponse
    {
        $validated = $this->validated($request, [
            'key' => 'sometimes|required|string|max:255|unique:settings,key,'.$setting->id,
            'value' => 'sometimes|required|string',
        ]);

        $setting->update($validated);
        $setting->value = $this->maskIfSensitive($setting->key, $setting->value);

        return response()->json($setting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return response()->json(null, 204);
    }

    /**
     * Get all settings as a key-value object
     */
    public function getBatch(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        foreach (Setting::SENSITIVE_KEYS as $key) {
            if (isset($settings[$key])) {
                $raw = $settings[$key];
                $settings[$key] = $this->maskIfSensitive($key, is_string($raw) ? $raw : null);
            }
        }

        return response()->json($settings);
    }

    /**
     * Save multiple settings at once (upsert)
     */
    public function saveBatch(Request $request): JsonResponse
    {
        $data = $request->all();

        try {
            // Upsert all settings
            foreach ($data as $key => $value) {
                // Skip if key is empty
                if (empty($key)) {
                    continue;
                }

                // Validate language setting
                if ($key === 'language' && ! LanguageHelper::isLanguageSupported($value)) {
                    return response()->json([
                        'message' => 'Invalid language selected',
                        'error' => 'Language must be one of: '.implode(', ', LanguageHelper::getLanguageCodes()),
                    ], 422);
                }

                // Convert boolean to string for storage
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }

                // Convert null to empty string
                if (is_null($value)) {
                    $value = '';
                }

                // Arrays/objects are not valid setting values
                if (! is_scalar($value)) {
                    continue;
                }

                // Ensure value is a string
                $value = (string) $value;

                // Sensitive fields (e.g. openai_api_key) round-trip to the
                // browser as a masked preview, never the real value. If the
                // submitted value still looks like that mask, the field
                // wasn't actually changed, so leave the stored secret alone.
                if (in_array($key, Setting::SENSITIVE_KEYS, true) && $this->looksLikeMask($value)) {
                    continue;
                }

                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // Clear settings cache
            SettingsHelper::clearCache();

            $settings = Setting::all()->pluck('value', 'key');
            foreach (Setting::SENSITIVE_KEYS as $key) {
                if (isset($settings[$key])) {
                    $raw = $settings[$key];
                    $settings[$key] = $this->maskIfSensitive($key, is_string($raw) ? $raw : null);
                }
            }

            return response()->json([
                'message' => 'Settings saved successfully',
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Render a sample invoice PDF with the submitted (possibly unsaved)
     * appearance settings so admins can preview the invoice look.
     */
    public function invoicePreview(Request $request, InvoicePdfGenerator $pdfGenerator): SymfonyResponse
    {
        $validated = $this->validated($request, [
            'invoice_accent_color' => 'nullable|string|max:7',
            'invoice_font' => 'nullable|string|max:20',
            'invoice_density' => 'nullable|string|max:20',
            'invoice_table_style' => 'nullable|string|max:20',
            'invoice_footer_col1' => 'nullable|string|in:company_info,bank_info,page_info,empty',
            'invoice_footer_col2' => 'nullable|string|in:company_info,bank_info,page_info,empty',
            'invoice_footer_col3' => 'nullable|string|in:company_info,bank_info,page_info,empty',
        ]);

        // Invalid values fall back to defaults inside the generator
        return $pdfGenerator->preview(array_filter($validated, fn ($value) => $value !== null))
            ->name('invoice-preview.pdf')
            ->inline()
            ->toResponse($request);
    }

    /**
     * Get public settings (no authentication required)
     * Returns only safe-to-display settings like company logo
     */
    public function getPublicSettings(): JsonResponse
    {
        $publicSettings = Setting::whereIn('key', [
            'company_logo',
            'company_name',
            'ai_worklog_enabled',
        ])->pluck('value', 'key');

        return response()->json($publicSettings);
    }

    /**
     * Mask a sensitive value down to its last 4 characters (e.g. "••••••••ab12").
     * Non-sensitive keys and empty values pass through unchanged.
     */
    private function maskIfSensitive(string $key, ?string $value): ?string
    {
        if (! in_array($key, Setting::SENSITIVE_KEYS, true) || $value === '' || $value === null) {
            return $value;
        }

        return str_repeat('•', 8).substr($value, -4);
    }

    /**
     * Whether a submitted value is exactly the masked preview we hand back
     * for sensitive fields, meaning the user didn't type a new secret.
     */
    private function looksLikeMask(string $value): bool
    {
        return (bool) preg_match('/^•{8}.{0,4}$/u', $value);
    }
}
