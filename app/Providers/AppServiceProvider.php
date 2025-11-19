<?php

namespace App\Providers;

use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\OrderRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Repository Interfaces to Concrete Implementations
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);

        // Services are automatically resolved by Laravel's dependency injection
        // ProductService and OrderService will be auto-injected when needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Global API rate limit - 60 requests per minute per IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Authentication endpoints - stricter limits to prevent brute force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many authentication attempts. Please try again later.',
                        'data' => null,
                        'errors' => ['rate_limit' => 'Maximum 5 attempts per minute']
                    ], 429, $headers);
                });
        });

        // Login specific - 3 attempts per minute per email
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email', '');
            return [
                Limit::perMinute(3)->by($email . '|' . $request->ip())
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Too many login attempts. Please try again in 1 minute.',
                            'data' => null,
                            'errors' => ['rate_limit' => 'Maximum 3 login attempts per minute']
                        ], 429, $headers);
                    }),
                Limit::perHour(10)->by($email)
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Account temporarily locked due to too many failed login attempts.',
                            'data' => null,
                            'errors' => ['rate_limit' => 'Maximum 10 login attempts per hour. Please try again later.']
                        ], 429, $headers);
                    })
            ];
        });

        // Registration - 2 per hour per IP to prevent spam accounts
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(2)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many registration attempts. Please try again later.',
                        'data' => null,
                        'errors' => ['rate_limit' => 'Maximum 2 registrations per hour per IP']
                    ], 429, $headers);
                });
        });

        // Authenticated users - higher limit based on user ID
        RateLimiter::for('authenticated', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        // Admin operations - higher limits
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(200)->by($request->user()?->id ?? $request->ip());
        });

        // Vendor operations - moderate limits
        RateLimiter::for('vendor', function (Request $request) {
            return Limit::perMinute(150)->by($request->user()?->id ?? $request->ip());
        });

        // Customer operations - standard limits
        RateLimiter::for('customer', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?? $request->ip());
        });

        // Order creation - prevent spam orders
        RateLimiter::for('orders', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->user()?->id ?? $request->ip()),
                Limit::perHour(20)->by($request->user()?->id ?? $request->ip())
            ];
        });

        // Product creation (for vendors)
        RateLimiter::for('products', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->user()?->id ?? $request->ip()),
                Limit::perHour(50)->by($request->user()?->id ?? $request->ip())
            ];
        });
    }
}
