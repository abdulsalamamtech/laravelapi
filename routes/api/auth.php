<?php 

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;



Route::prefix('api')
    ->name('api.')
    ->group(function () {
        // Guest user
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // Authenticated user
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
        });
    });