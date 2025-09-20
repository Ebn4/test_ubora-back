<?php

namespace App\Providers;

use App\EvaluatorServiceImpl;
use App\Services\EvaluatorService;
use Illuminate\Support\ServiceProvider;

class EvaluatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EvaluatorService::class, function ($app) {
            return new EvaluatorServiceImpl();
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
