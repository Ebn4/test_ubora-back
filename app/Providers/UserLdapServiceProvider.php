<?php

namespace App\Providers;

use App\FakeUserLdapServiceImpl;
use App\Services\UserLdapService;
use Illuminate\Support\ServiceProvider;

class UserLdapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(UserLdapService::class, function () {
            return new FakeUserLdapServiceImpl();
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
