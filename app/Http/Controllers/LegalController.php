<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class LegalController extends Controller
{
    /**
     * Get privacy notice content
     */
    public function privacy(): JsonResponse
    {
        $setting = Setting::where('key', 'privacy_notice')->first();

        return response()->json([
            'content' => $setting ? $setting->value : null,
        ]);
    }

    /**
     * Get imprint content
     */
    public function imprint(): JsonResponse
    {
        $setting = Setting::where('key', 'imprint')->first();

        return response()->json([
            'content' => $setting ? $setting->value : null,
        ]);
    }
}
