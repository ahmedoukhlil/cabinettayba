<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Http\Middleware\ResolveTenant;
use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
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
            'tenancy.central_domains' => ['tayba.test'],
            'tenancy.default_tenant' => null,
        ]);

        DB::purge('landlord');
        DB::purge('tenant');

        Schema::connection('landlord')->create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
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
        $this->assertTrue($subdomainTenant->is($resolver->resolve(Request::create('https://cabinet-b.tayba.test'))));
        $this->assertNull($resolver->resolve(Request::create('https://inconnu.tayba.test')));
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

    private function createTenant(string $slug, ?string $domain = null): Tenant
    {
        $file = tempnam(sys_get_temp_dir(), 'tayba-tenant-');
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
