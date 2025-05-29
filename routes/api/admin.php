<?php 

use Illuminate\Support\Facades\Route;



// Protected product routes
Route::middleware('auth:sanctum')->group(function () {
    // Route::apiResource('products', ProductController::class);
});