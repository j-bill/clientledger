<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Resource routes
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('worklogs', WorkLogController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('settings', SettingController::class);
    Route::apiResource('users', UserController::class);
    
    // Invoice generation
    Route::post('/invoices/generate', [InvoiceController::class, 'generateFromWorkLogs']);
    
    // Reports
    Route::get('/reports/time/by-project', [ReportController::class, 'timeByProject']);
    Route::get('/reports/financial/by-customer', [ReportController::class, 'financialByCustomer']);
    
    // Special route for completing active time tracking
    Route::post('worklogs/{workLog}/complete', [WorkLogController::class, 'completeTracking'])
        ->name('worklogs.complete');
});