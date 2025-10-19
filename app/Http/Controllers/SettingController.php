<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Setting::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings',
            'value' => 'required|string',
        ]);

        $setting = Setting::create($validated);
        return response()->json($setting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        return response()->json($setting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'key' => 'sometimes|required|string|max:255|unique:settings,key,' . $setting->id,
            'value' => 'sometimes|required|string',
        ]);

        $setting->update($validated);
        return response()->json($setting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();
        return response()->json(null, 204);
    }

    /**
     * Get all settings as a key-value object
     */
    public function getBatch()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Save multiple settings at once (upsert)
     */
    public function saveBatch(Request $request)
    {
        $data = $request->all();
        
        // Validate that we have an object/array
        if (!is_array($data)) {
            return response()->json(['message' => 'Invalid data format'], 400);
        }

        try {
            // Upsert all settings
            foreach ($data as $key => $value) {
                // Skip if key is empty
                if (empty($key)) {
                    continue;
                }
                
                // Convert boolean to string for storage
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                
                // Convert null to empty string
                if (is_null($value)) {
                    $value = '';
                }
                
                // Ensure value is a string
                $value = (string) $value;
                
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // Clear settings cache
            \App\Helpers\SettingsHelper::clearCache();

            return response()->json([
                'message' => 'Settings saved successfully',
                'settings' => Setting::all()->pluck('value', 'key')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public settings (no authentication required)
     * Returns only safe-to-display settings like company logo
     */
    public function getPublicSettings()
    {
        $publicSettings = Setting::whereIn('key', [
            'company_logo',
            'company_name',
        ])->pluck('value', 'key');

        return response()->json($publicSettings);
    }
}
