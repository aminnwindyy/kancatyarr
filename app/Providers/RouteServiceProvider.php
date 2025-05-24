<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Livewire\Livewire;
use App\Filament\Pages\Auth\Login;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerLivewireComponents();
        $this->mapRoutes();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register Livewire components
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('filament.pages.auth.login', Login::class);
    }

    /**
     * Define the routes for the application.
     */
    protected function mapRoutes(): void
    {
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Custom login route for Filament admin panel
            Route::middleware('web')
                ->get('admin/login', function () {
                    return view('filament.pages.auth.login');
                })
                ->name('filament.admin.auth.login');
        });
    }
}
