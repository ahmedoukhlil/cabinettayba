<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    private ?Tenant $tenant = null;
    private string $originalConnection;
    private array $originalRuntimeConfig;

    public function __construct()
    {
        $this->originalConnection = (string) config('database.default');
        $this->originalRuntimeConfig = [
            'cache.prefix' => config('cache.prefix'),
            'cache.stores.file.path' => config('cache.stores.file.path'),
            'filesystems.disks.local.root' => config('filesystems.disks.local.root'),
            'filesystems.disks.public.root' => config('filesystems.disks.public.root'),
            'filesystems.disks.public.url' => config('filesystems.disks.public.url'),
        ];
    }

    public function activate(Tenant $tenant): void
    {
        $connection = (string) config('tenancy.tenant_connection', 'tenant');
        $baseConfig = config("database.connections.{$connection}");
        $tenantConfig = array_merge($baseConfig, ['database' => $tenant->database]);
        $baseUrl = app()->runningInConsole()
            ? rtrim(config('app.url'), '/')
            : request()->getSchemeAndHttpHost();

        if (($baseConfig['driver'] ?? null) !== 'sqlite') {
            $tenantConfig['host'] = $tenant->db_host ?: ($baseConfig['host'] ?? null);
            $tenantConfig['port'] = $tenant->db_port ?: ($baseConfig['port'] ?? null);
            $tenantConfig['username'] = $tenant->db_username ?: ($baseConfig['username'] ?? null);
            $tenantConfig['password'] = $tenant->db_password ?: ($baseConfig['password'] ?? null);
        }

        config([
            "database.connections.{$connection}" => $tenantConfig,
            'database.default' => $connection,
            'cache.prefix' => $this->originalRuntimeConfig['cache.prefix'] . ':tenant:' . $tenant->slug,
            'cache.stores.file.path' => storage_path('framework/cache/tenants/' . $tenant->slug),
            'filesystems.disks.local.root' => storage_path('app/tenants/' . $tenant->slug),
            'filesystems.disks.public.root' => storage_path('app/public/tenants/' . $tenant->slug),
            'filesystems.disks.public.url' => $baseUrl . '/storage/tenants/' . $tenant->slug,
        ]);

        DB::purge($connection);
        DB::setDefaultConnection($connection);
        DB::connection($connection)->getPdo();

        $this->forgetResolvedStores();
        $this->tenant = $tenant;
    }

    public function deactivate(): void
    {
        $connection = (string) config('tenancy.tenant_connection', 'tenant');

        DB::purge($connection);
        DB::setDefaultConnection($this->originalConnection);
        config(array_merge(
            ['database.default' => $this->originalConnection],
            $this->originalRuntimeConfig
        ));

        $this->forgetResolvedStores();
        $this->tenant = null;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant ? (int) $this->tenant->getKey() : null;
    }

    private function forgetResolvedStores(): void
    {
        if (app()->resolved('cache')) {
            $cache = app('cache');

            if ($cache instanceof \Illuminate\Cache\CacheManager) {
                $cache->forgetDriver(config('cache.default'));
            }
        }

        if (app()->resolved('filesystem')) {
            app('filesystem')->forgetDisk(['local', 'public']);
        }
    }
}
