<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

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
        // Log viewer configuration access
        LogViewer::auth(function ($request) {
            info('LogViewer Auth', [
                'time' => now(),
                'user' => $request?->user(),
                'role' => $request?->user()?->role,
            ]);
            return ($request->user() && in_array(
                $request->user()->email, 
                ['abdulsalamamtech@gmail.com',]
            ));
        });
    }
}
