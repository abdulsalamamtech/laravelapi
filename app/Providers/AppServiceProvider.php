<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
        // Docs configuration
        Scramble::configure()
        ->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });
        
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
