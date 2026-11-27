<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantResolver
{
    public function isCentralHost(string $host): bool
    {
        $host = strtolower(trim($host));

        return in_array($host, array_map(
            static fn ($domain) => strtolower(trim((string) $domain)),
            config('tenancy.central_domains', [])
        ), true);
    }

    public function resolve(Request $request): ?Tenant
    {
        $host = strtolower($request->getHost());

        $tenant = Tenant::active()->where('domain', $host)->first();
        if ($tenant) {
            return $tenant;
        }

        foreach (config('tenancy.central_domains', []) as $centralDomain) {
            $centralDomain = strtolower($centralDomain);
            $suffix = '.' . $centralDomain;

            if (str_ends_with($host, $suffix)) {
                $slug = substr($host, 0, -strlen($suffix));

                if ($slug !== '' && !str_contains($slug, '.')) {
                    return Tenant::active()->where('slug', $slug)->first();
                }
            }
        }

        $defaultTenant = config('tenancy.default_tenant');

        if ($defaultTenant) {
            return Tenant::active()
                ->where(function ($query) use ($defaultTenant): void {
                    $query->where('slug', $defaultTenant);

                    if (ctype_digit((string) $defaultTenant)) {
                        $query->orWhereKey((int) $defaultTenant);
                    }
                })
                ->first();
        }

        return null;
    }
}
