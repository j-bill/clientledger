<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\SettingsHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply mail configuration from database settings
        try {
            if (app()->environment() !== 'testing') {
                SettingsHelper::applyMailConfig();
            }
        } catch (\Exception $e) {
            // Silently fail if settings table doesn't exist yet (during migrations)
        }
    }
}
