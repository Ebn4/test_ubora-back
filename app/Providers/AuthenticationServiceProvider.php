<?php

namespace App\Providers;

use App\FakeAuthenticationServiceImpl;
use App\Services\AuthenticationService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthenticationService::class, function ($app) {
            $userService = $app->make(UserService::class);
            return new FakeAuthenticationServiceImpl($userService);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
