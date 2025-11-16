<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Notifications\EmailVerificationCode;

class EmailVerificationController extends Controller
{
    /**
     * Send verification code to user's email
     */
    public function sendCode(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user already has a verified email
        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified',
                'already_verified' => true
            ]);
        }

        // Generate a random 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Save the code and expiration time
        $user->email_verification_code = $code;
        $user->email_verification_code_expires_at = now()->addHour();
        $user->save();

        // Send notification
        try {
            $user->notify(new EmailVerificationCode($code));
            
            // Log::info('Email verification code sent', [
            //     'user_id' => $user->id,
            //     'email' => $user->email,
            // ]);

            return response()->json([
                'message' => 'Verification code sent to your email',
                'code_sent' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email verification code', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to send verification code. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify the code submitted by user
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if already verified
        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified',
                'verified' => true
            ]);
        }

        // Check if code exists
        if (!$user->email_verification_code) {
            return response()->json([
                'message' => 'No verification code found. Please request a new one.',
            ], 400);
        }

        // Check if code has expired
        if (now()->isAfter($user->email_verification_code_expires_at)) {
            return response()->json([
                'message' => 'Verification code has expired. Please request a new one.',
                'expired' => true
            ], 400);
        }

        // Verify the code
        if ($request->code !== $user->email_verification_code) {
            Log::warning('Invalid email verification code attempt', [
                'user_id' => $user->id,
                'submitted_code' => $request->code,
            ]);
            
            return response()->json([
                'message' => 'Invalid verification code. Please try again.',
            ], 400);
        }

        // Code is valid - mark email as verified
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        // Log::info('Email verified successfully', [
        //     'user_id' => $user->id,
        //     'email' => $user->email,
        // ]);

        return response()->json([
            'message' => 'Email verified successfully!',
            'verified' => true
        ]);
    }

    /**
     * Check if user needs email verification
     */
    public function checkStatus(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'email_verified' => !is_null($user->email_verified_at),
            'has_2fa_enabled' => $user->hasTwoFactorEnabled(),
            'should_verify' => $user->hasTwoFactorEnabled() && is_null($user->email_verified_at),
        ]);
    }
}
