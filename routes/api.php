<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/', function () {
    return [
        'title' => 'Welcome to my API',
        'description' => 'This is a simple API for managing users and their settings.',
        'version' => '1.0.0',
        'documentation_url' => 'https://example.com/api/documentation',
        'contact' => [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'url' => 'https://johndoe.example.com',
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
        'links' => [
            'self' => route('home'),
        ],
    ];
})->name('api.home');




require __DIR__.'/api/auth.php';
require __DIR__.'/api/users.php';
require __DIR__.'/api/guest.php';
require __DIR__.'/api/admin.php';
