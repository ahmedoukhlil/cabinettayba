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

        $resolver = app(TenantResolver::class);

        if ($resolver->isCentralHost($request->getHost())) {
            $request->attributes->set('central_domain', true);

            if (!$request->is('/') && !$request->is('admin') && !$request->is('admin/*')) {
                return redirect()->route('central.dashboard');
            }

            return $next($request);
        }

        $tenant = $resolver->resolve($request);
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
