<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Repository bindings
        $this->app->bind(
            \App\Repositories\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\ProductRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\OrderRepositoryInterface::class,
            \App\Repositories\OrderRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\CartRepositoryInterface::class,
            \App\Repositories\CartRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (App::environment('production')) {
            URL::forceScheme('https');
        }
    }
}
