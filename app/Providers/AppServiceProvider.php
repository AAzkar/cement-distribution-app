<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        // Older MySQL/MariaDB builds common on budget shared hosting default to a
        // utf8mb4 index prefix limit that breaks on unconstrained string columns.
        // Harmless on modern MySQL — only bites if the server is old enough to need it.
        Schema::defaultStringLength(191);
    }
}
