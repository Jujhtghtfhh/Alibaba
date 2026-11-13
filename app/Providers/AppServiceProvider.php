<?php

namespace App\Providers;

use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // The "jwt" guard driver (used by the "api" guard in config/auth.php):
        // reads "Authorization: Bearer <access token>" and returns the logged-in user or null.
        Auth::viaRequest('jwt', fn (Request $request) => app(JwtService::class)->userFromAccessToken($request->bearerToken()));
    }
}
