<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;

class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            app(TenantResolver::class)->isCentralHost($request->getHost()),
            404
        );

        return $next($request);
    }
}
