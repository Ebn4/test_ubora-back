<?php

namespace App\Providers;

use App\FakeAuthenticationServiceImpl;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\UserServiceImpl;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(UserService::class, function ($app) {
            return new UserServiceImpl();
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
