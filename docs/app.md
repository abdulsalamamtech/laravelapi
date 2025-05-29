# Building a Complete API with Laravel 12: Step-by-Step Guide

This guide will walk you through creating a robust API with Laravel 12, including authentication, middleware, versioning, and best practices.

## Table of Contents
1. [Setting Up Laravel 12](#1-setting-up-laravel-12)
2. [API Authentication](#2-api-authentication)
3. [Creating API Resources](#3-creating-api-resources)
4. [API Routes and Controllers](#4-api-routes-and-controllers)
5. [Data Validation](#5-data-validation)
6. [API Versioning](#6-api-versioning)
7. [Rate Limiting](#7-rate-limiting)
8. [API Documentation](#8-api-documentation)
9. [Testing Your API](#9-testing-your-api)
10. [API Security Best Practices](#10-api-security-best-practices)

## 1. Setting Up Laravel 12

### Installation
```bash
# Create a new Laravel 12 project
composer create-project laravel/laravel api-project

# Navigate to the project directory
cd api-project
```

### Configure Database
Update your `.env` file with database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 2. API Authentication

Laravel 12 supports several authentication methods for APIs. We'll use Laravel Sanctum for token-based authentication.

### Install Laravel Sanctum
```bash
composer require laravel/sanctum
```

### Publish Sanctum Configuration
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### Run Migrations
```bash
php artisan migrate
```

### Configure User Model
Update your `User.php` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ...existing code
}
```

### Create Authentication Controller
```bash
php artisan make:controller AuthController
```

Update the `AuthController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $request->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
```

### Define Authentication Routes
Add these routes to your `routes/api.php`:

```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
```

## 3. Creating API Resources

API Resources in Laravel help transform your models into JSON responses.

### Create a Model, Migration, and Controller
```bash
php artisan make:model Product -m -c -r
```

Run the migration after updating it:
```bash
php artisan migrate
```

### Create API Resource
```bash
php artisan make:resource ProductResource
php artisan make:resource ProductCollection
```

Edit the `ProductResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## 4. API Routes and Controllers

### Define API Routes
Add to your `routes/api.php`:

```php
// Protected product routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### Update the Controller
Edit your `ProductController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return new ProductCollection(Product::paginate(10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::create($validated);

        return new ProductResource($product);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
        ]);

        $product->update($validated);

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
```

### Update Product Model
Make sure the fields are mass assignable:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price'];
}
```

## 5. Data Validation

### Create Form Requests
```bash
php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest
```

Update `StoreProductRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorize any authenticated user
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ];
    }
}
```

Update `UpdateProductRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
        ];
    }
}
```

### Update Controller to Use Form Requests
```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return new ProductResource($product);
    }

    // Other methods remain the same
}
```

## 6. API Versioning

### Structure for Versioning
Create directories for different API versions:

```bash
mkdir -p app/Http/Controllers/Api/V1
mkdir -p app/Http/Controllers/Api/V2
```

Move your controllers and create version-specific routes:

```php
// routes/api.php
Route::prefix('v1')->namespace('App\Http\Controllers\Api\V1')->group(function () {
    // V1 routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('products', ProductController::class);
        // Other V1 routes
    });
});

Route::prefix('v2')->namespace('App\Http\Controllers\Api\V2')->group(function () {
    // V2 routes
});
```

## 7. Rate Limiting

Laravel 12 includes built-in rate limiting. Configure it in `app/Providers/RouteServiceProvider.php`:

```php
/**
 * Configure the rate limiters for the application.
 */
protected function configureRateLimiting(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Create a more restrictive limiter for certain routes
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

Apply the rate limit to specific routes:

```php
// routes/api.php
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
```

## 8. API Documentation

### Install Scribe for API Documentation
```bash
composer require knuckleswtf/scribe
```

### Publish Configuration
```bash
php artisan vendor:publish --provider="Knuckles\Scribe\ScribeServiceProvider" --tag=scribe-config
```

### Add Annotations to Controllers
Example for the `ProductController.php`:

```php
/**
 * @group Product Management
 *
 * APIs for managing products
 */
class ProductController extends Controller
{
    /**
     * List all products
     *
     * Get a paginated list of all products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // method implementation
    }

    // Add similar annotations to other methods
}
```

### Generate Documentation
```bash
php artisan scribe:generate
```

## 9. Testing Your API

### Create API Tests
```bash
php artisan make:test ProductApiTest
```

Update `tests/Feature/ProductApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and generate token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_get_all_products()
    {
        Product::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'This is a test product',
            'price' => 19.99,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Product']);
    }

    // Add more tests for update, delete, etc.
}
```

### Run Tests
```bash
php artisan test
```

## 10. API Security Best Practices

### CORS Configuration
Update your `config/cors.php`:

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Add Middleware for Security Headers
Create a new middleware:

```bash
php artisan make:middleware SecurityHeaders
```

Update `app/Http/Middleware/SecurityHeaders.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        
        return $response;
    }
}
```

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\SecurityHeaders::class,
        // other middleware
    ],
];
```

### Add Request Logging
Create a middleware for logging API requests:

```bash
php artisan make:middleware LogApiRequests
```

Update `app/Http/Middleware/LogApiRequests.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Don't log sensitive routes
        if (!in_array($request->path(), ['api/v1/login', 'api/v1/register'])) {
            Log::channel('api')->info('API Request', [
                'method' => $request->method(),
                'uri' => $request->path(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'status' => $response->status(),
            ]);
        }
        
        return $response;
    }
}
```

Add it to your middleware groups in `Kernel.php`.

## Conclusion

You now have a robust, secure Laravel 12 API with authentication, validation, versioning, rate limiting, and comprehensive testing. This foundation can be expanded to include additional features like:

- OAuth2 integration for third-party authentication
- WebSockets for real-time features
- API caching for improved performance
- Advanced query filters and search capabilities
- File uploads and storage integration