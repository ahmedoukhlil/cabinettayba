<?php

namespace App\Providers;

use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
use App\Services\CabinetBranding;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

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
        $this->app->singleton(CabinetBranding::class, fn ($app) => new CabinetBranding($app->make(TenantManager::class)));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        View::composer('*', function ($view): void {
            $view->with('cabinetBrand', app(CabinetBranding::class)->get());
        });
    }
}
