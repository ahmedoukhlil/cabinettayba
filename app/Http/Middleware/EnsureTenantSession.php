<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;

class EnsureTenantSession
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('tenancy.enabled')) {
            return $next($request);
        }

        if (app(TenantResolver::class)->isCentralHost($request->getHost())) {
            $request->session()->forget('tenant_id');
            return $next($request);
        }

        $tenant = app(TenantManager::class)->current();
        abort_unless($tenant, 404, 'Cabinet introuvable.');

        $sessionTenant = $request->session()->get('tenant_id');

        if ($sessionTenant !== null && (int) $sessionTenant !== (int) $tenant->getKey()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $request->session()->put('tenant_id', (int) $tenant->getKey());

        return $next($request);
    }
}
