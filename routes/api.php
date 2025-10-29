<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkLogController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Public settings route (for company logo, etc.)
Route::get('/settings/public', [SettingController::class, 'getPublicSettings']);

// Public legal content routes
Route::get('/legal/privacy', [LegalController::class, 'privacy']);
Route::get('/legal/imprint', [LegalController::class, 'imprint']);

// 2FA verification route (public, but requires pending login)
Route::post('/2fa/verify', [TwoFactorAuthenticationController::class, 'verify']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // 2FA routes (accessible for initial setup and management)
    Route::prefix('2fa')->group(function () {
        Route::post('/enable', [TwoFactorAuthenticationController::class, 'enable']);
        Route::post('/confirm', [TwoFactorAuthenticationController::class, 'confirm']);
        Route::post('/disable', [TwoFactorAuthenticationController::class, 'disable']);
        Route::get('/status', [TwoFactorAuthenticationController::class, 'status']);
        Route::get('/recovery-codes', [TwoFactorAuthenticationController::class, 'getRecoveryCodes']);
        Route::post('/recovery-codes/regenerate', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes']);
        Route::get('/devices', [TwoFactorAuthenticationController::class, 'getTrustedDevices']);
        Route::delete('/devices', [TwoFactorAuthenticationController::class, 'removeTrustedDevice']);
    });

    // Email verification routes (accessible for users with 2FA)
    Route::prefix('email-verification')->group(function () {
        Route::post('/send-code', [EmailVerificationController::class, 'sendCode']);
        Route::post('/verify-code', [EmailVerificationController::class, 'verifyCode']);
        Route::get('/status', [EmailVerificationController::class, 'checkStatus']);
    });

    // Routes that require 2FA setup and verification
    Route::middleware([Require2FASetup::class, Verify2FA::class])->group(function () {
        // Profile routes
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::get('/profile/statistics', [ProfileController::class, 'statistics']);
        Route::get('/profile/activity', [ProfileController::class, 'activity']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Customer routes
        Route::get('/customers', [CustomerController::class, 'index']); // Allow all authenticated users to view customers

        // Admin-only customer routes
        Route::middleware(CheckRole::class.':admin')->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::get('/customers/{customer}', [CustomerController::class, 'show']);
            Route::put('/customers/{customer}', [CustomerController::class, 'update']);
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        });

        // Project routes
        // Routes accessible by both admin and freelancer
        Route::get('/projects', [ProjectController::class, 'index']);

        // Admin-only project routes
        Route::middleware(CheckRole::class.':admin')->group(function () {
            Route::post('/projects', [ProjectController::class, 'store']);
            Route::put('/projects/{project}', [ProjectController::class, 'update']);
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        });

        // Work log routes
        Route::get('/worklogs', [WorkLogController::class, 'index']);
        Route::post('/worklogs', [WorkLogController::class, 'store']);
        Route::get('/worklogs/{workLog}', [WorkLogController::class, 'show']);
        Route::put('/worklogs/{workLog}', [WorkLogController::class, 'update']);
        Route::delete('/worklogs/{workLog}', [WorkLogController::class, 'destroy']);
        Route::post('/worklogs/{workLog}/complete', [WorkLogController::class, 'completeTracking']);
        Route::get('/active-worklog', [WorkLogController::class, 'getActiveWorkLog']);

        // Invoice routes (admin only)
        Route::middleware(CheckRole::class.':admin')->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::post('/invoices', [InvoiceController::class, 'store']);
            // Put specific routes before the parameter route to prevent shadowing
            Route::post('/invoices/generate', [InvoiceController::class, 'generateFromWorkLogs']);
            Route::get('/invoices/unbilled-worklogs', [InvoiceController::class, 'unbilledWorkLogs']);
            Route::get('/invoices/customer-projects', [InvoiceController::class, 'customerProjects']);
            Route::post('/invoices/{invoice}/upload-pdf', [InvoiceController::class, 'uploadPdf']);
            Route::post('/invoices/{invoice}/generate-pdf', [InvoiceController::class, 'generatePdf']);
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'viewPdf']);
            Route::get('/invoices/{invoice}/pdf-download', [InvoiceController::class, 'downloadPdf']);
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
        });

        // Settings routes (admin only)
        Route::middleware(CheckRole::class.':admin')->group(function () {
            // Batch endpoints first to prevent route shadowing
            Route::get('/settings/batch', [SettingController::class, 'getBatch']);
            Route::post('/settings/batch', [SettingController::class, 'saveBatch']);

            // Individual setting endpoints
            Route::get('/settings', [SettingController::class, 'index']);
            Route::post('/settings', [SettingController::class, 'store']);
            Route::get('/settings/{setting}', [SettingController::class, 'show']);
            Route::put('/settings/{setting}', [SettingController::class, 'update']);
            Route::delete('/settings/{setting}', [SettingController::class, 'destroy']);
        });

        // User management routes (admin only)
        Route::middleware(CheckRole::class.':admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        });
    });
});
