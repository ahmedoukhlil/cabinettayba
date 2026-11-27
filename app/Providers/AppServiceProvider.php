<?php

namespace App\Providers;

use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TenantManager::class, fn () => new TenantManager());
        $this->app->singleton(TenantResolver::class, fn () => new TenantResolver());
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }
}
