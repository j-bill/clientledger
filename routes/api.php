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
use App\Http\Middleware\CheckRole;

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
    
    // Customer routes
    Route::get('/customers', [CustomerController::class, 'index']); // Allow all authenticated users to view customers
    
    // Admin-only customer routes
    Route::middleware(CheckRole::class . ':admin')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    });
    
    // Project routes
    // Routes accessible by both admin and freelancer
    Route::get('/projects', [ProjectController::class, 'index']);

    // Admin-only project routes
    Route::middleware(CheckRole::class . ':admin')->group(function () {
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
    Route::middleware(CheckRole::class . ':admin')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    // Put specific routes before the parameter route to prevent shadowing
    Route::post('/invoices/generate', [InvoiceController::class, 'generateFromWorkLogs']);
    Route::get('/invoices/unbilled-worklogs', [InvoiceController::class, 'unbilledWorkLogs']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    });
    
    // Settings routes (admin only)
    Route::middleware(CheckRole::class . ':admin')->group(function () {
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::get('/settings/{setting}', [SettingController::class, 'show']);
        Route::put('/settings/{setting}', [SettingController::class, 'update']);
        Route::delete('/settings/{setting}', [SettingController::class, 'destroy']);
    });
    
    // User management routes (admin only)
    Route::middleware(CheckRole::class . ':admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
    
    // Reports (admin only)
    Route::middleware(CheckRole::class . ':admin')->group(function () {
        Route::get('/reports/time/by-project', [ReportController::class, 'timeByProject']);
        Route::get('/reports/financial/by-customer', [ReportController::class, 'financialByCustomer']);
    });
});