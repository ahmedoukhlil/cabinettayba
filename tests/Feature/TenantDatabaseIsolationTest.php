<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Http\Middleware\ResolveTenant;
use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
use App\Services\CabinetBranding;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantDatabaseIsolationTest extends TestCase
{
    private array $databaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.landlord' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'tenancy.enabled' => true,
            'tenancy.central_domains' => ['cabinet.test'],
            'tenancy.default_tenant' => null,
        ]);

        DB::purge('landlord');
        DB::purge('tenant');

        Schema::connection('landlord')->create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('name_ar')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#1e3a8a');
            $table->string('secondary_color', 7)->default('#0e7490');
            $table->string('reference_prefix', 12)->default('RDV');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('database')->unique();
            $table->string('db_host')->nullable();
            $table->unsignedInteger('db_port')->nullable();
            $table->string('db_username')->nullable();
            $table->text('db_password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        app(TenantManager::class)->deactivate();

        foreach ($this->databaseFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_each_tenant_uses_its_own_database(): void
    {
        $tenantA = $this->createTenant('cabinet-a');
        $tenantB = $this->createTenant('cabinet-b');
        $manager = app(TenantManager::class);

        $manager->activate($tenantA);
        $this->createPatientsTable();
        DB::table('patients')->insert(['name' => 'Patient A']);
        $manager->deactivate();

        $manager->activate($tenantB);
        $this->createPatientsTable();
        DB::table('patients')->insert(['name' => 'Patient B']);
        $this->assertSame(['Patient B'], DB::table('patients')->pluck('name')->all());
        $manager->deactivate();

        $manager->activate($tenantA);
        $this->assertSame(['Patient A'], DB::table('patients')->pluck('name')->all());
    }

    public function test_tenant_is_resolved_by_domain_or_subdomain(): void
    {
        $domainTenant = $this->createTenant('cabinet-a', 'dentiste.example.com');
        $subdomainTenant = $this->createTenant('cabinet-b');
        $resolver = app(TenantResolver::class);

        $this->assertTrue($domainTenant->is($resolver->resolve(Request::create('https://dentiste.example.com'))));
        $this->assertTrue($subdomainTenant->is($resolver->resolve(Request::create('https://cabinet-b.cabinet.test'))));
        $this->assertNull($resolver->resolve(Request::create('https://inconnu.cabinet.test')));
    }

    public function test_disabled_tenancy_keeps_the_historical_connection(): void
    {
        config(['tenancy.enabled' => false]);
        $middleware = app(ResolveTenant::class);
        $response = $middleware->handle(
            Request::create('https://localhost/login'),
            fn () => new Response('ok')
        );

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_central_domain_does_not_activate_a_tenant_database(): void
    {
        $resolver = app(TenantResolver::class);
        $middleware = app(ResolveTenant::class);
        $request = Request::create('https://cabinet.test/');

        $this->assertTrue($resolver->isCentralHost($request->getHost()));

        $response = $middleware->handle($request, fn () => new Response('central'));

        $this->assertSame('central', $response->getContent());
        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->assertNull(app(TenantManager::class)->current());
    }

    public function test_branding_comes_from_the_central_tenant_registry(): void
    {
        $tenant = $this->createTenant('cabinet-a');
        $tenant->update([
            'name' => 'Clinique Exemple',
            'name_ar' => 'عيادة مثال',
            'primary_color' => '#123456',
            'reference_prefix' => 'EXEMPLE',
            'phone' => '+222 45 00 00 00',
        ]);

        app(TenantManager::class)->activate($tenant->fresh());
        $brand = app(CabinetBranding::class)->get();

        $this->assertSame('Clinique Exemple', $brand['name']);
        $this->assertSame('عيادة مثال', $brand['name_ar']);
        $this->assertSame('#123456', $brand['primary_color']);
        $this->assertSame('EXEMPLE', $brand['reference_prefix']);
        $this->assertSame('+222 45 00 00 00', $brand['phone']);
    }

    public function test_branding_is_refreshed_when_a_console_job_switches_tenant(): void
    {
        $first = $this->createTenant('cabinet-a');
        $second = $this->createTenant('cabinet-b');
        $first->update(['name' => 'Cabinet A']);
        $second->update(['name' => 'Cabinet B']);

        $manager = app(TenantManager::class);
        $branding = app(CabinetBranding::class);

        $manager->activate($first->fresh());
        $this->assertSame('Cabinet A', $branding->get()['name']);
        $manager->deactivate();

        $manager->activate($second->fresh());
        $this->assertSame('Cabinet B', $branding->get()['name']);
    }

    private function createTenant(string $slug, ?string $domain = null): Tenant
    {
        $file = tempnam(sys_get_temp_dir(), 'cabinet-tenant-');
        $this->databaseFiles[] = $file;

        return Tenant::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'domain' => $domain,
            'database' => $file,
            'is_active' => true,
        ]);
    }

    private function createPatientsTable(): void
    {
        Schema::connection('tenant')->create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
    }
}
