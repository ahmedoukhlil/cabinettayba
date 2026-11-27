<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('tenancy.enabled')) {
            return $next($request);
        }

        $tenant = app(TenantResolver::class)->resolve($request);
        abort_unless($tenant, 404, 'Cabinet introuvable.');

        $manager = app(TenantManager::class);

        try {
            $manager->activate($tenant);
            return $next($request);
        } finally {
            $manager->deactivate();
        }
    }
}
