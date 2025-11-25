<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    /**
     * Generate a device fingerprint combining server-side and client-side data.
     */
    public static function generate(Request $request, ?string $clientFingerprint = null): string
    {
        $fingerprints = [
            'user_agent' => self::normalizeUserAgent($request->userAgent() ?? ''),
            'accept_language' => $request->header('Accept-Language', ''),
            'accept_encoding' => $request->header('Accept-Encoding', ''),
        ];
        
        if ($clientFingerprint) {
            $fingerprints['client'] = $clientFingerprint;
        }
        
        $combined = implode('|', array_filter($fingerprints));
        return hash('sha256', $combined);
    }
    
    /**
     * Normalize user agent string to reduce noise from version updates.
     */
    private static function normalizeUserAgent(string $userAgent): string
    {
        // Simplify UA to reduce noise from version updates
        $normalized = preg_replace('/(\d+\.\d+)\.\d+\.\d+/', '$1', $userAgent);
        $normalized = preg_replace('/AppleWebKit\/\d+\.\d+\.\d+/', 'AppleWebKit', $normalized);
        $normalized = preg_replace('/Version\/[\d.]+/', '', $normalized);
        return trim($normalized);
    }

    /**
     * Get device information from request headers.
     */
    public static function getDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';
        $isMobile = preg_match('/(iPhone|iPad|Android|Mobile)/i', $userAgent);
        
        return [
            'device_type' => $isMobile ? 'mobile' : 'desktop',
            'user_agent' => $userAgent,
            'ip_address' => $request->ip(),
        ];
    }
}
