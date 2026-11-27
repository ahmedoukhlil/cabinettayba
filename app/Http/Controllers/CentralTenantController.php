<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Tenancy\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CentralTenantController extends Controller
{
    public function index()
    {
        return view('central.tenants.index', [
            'tenants' => Tenant::orderBy('name')->paginate(15),
            'activeCount' => Tenant::where('is_active', true)->count(),
            'inactiveCount' => Tenant::where('is_active', false)->count(),
        ]);
    }

    public function create()
    {
        return view('central.tenants.form', [
            'tenant' => new Tenant([
                'db_port' => 3306,
                'primary_color' => '#1e3a8a',
                'secondary_color' => '#0e7490',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request, TenantManager $manager)
    {
        $tenant = new Tenant();
        $data = $this->validatedData($request);
        $this->fillTenant($tenant, $data, $request);

        if ($message = $this->connectionError($tenant, $manager)) {
            return back()->withInput()->withErrors(['database' => $message]);
        }

        $tenant->save();

        return redirect()->route('central.dashboard')
            ->with('success', "Le cabinet {$tenant->name} a été créé et sa base a été vérifiée.");
    }

    public function edit(Tenant $tenant)
    {
        return view('central.tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant, TenantManager $manager)
    {
        $data = $this->validatedData($request, $tenant);
        $this->fillTenant($tenant, $data, $request);

        if ($message = $this->connectionError($tenant, $manager)) {
            return back()->withInput()->withErrors(['database' => $message]);
        }

        $tenant->save();

        return redirect()->route('central.dashboard')
            ->with('success', "Le cabinet {$tenant->name} a été mis à jour.");
    }

    public function toggle(Tenant $tenant)
    {
        $tenant->is_active = !$tenant->is_active;
        $tenant->save();

        return back()->with(
            'success',
            $tenant->is_active ? 'Le cabinet a été réactivé.' : 'Le cabinet a été suspendu.'
        );
    }

    private function validatedData(Request $request, ?Tenant $tenant = null): array
    {
        $tenantId = $tenant?->getKey();

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'slug' => [
                'required', 'alpha_dash', 'max:80',
                Rule::unique('landlord.tenants', 'slug')->ignore($tenantId),
            ],
            'domain' => [
                'required', 'string', 'max:190',
                'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                Rule::notIn(config('tenancy.central_domains', [])),
                Rule::unique('landlord.tenants', 'domain')->ignore($tenantId),
            ],
            'database' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_$-]+$/',
                Rule::unique('landlord.tenants', 'database')->ignore($tenantId),
            ],
            'db_host' => ['required', 'string', 'max:190'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => [$tenant?->exists ? 'nullable' : 'required', 'string', 'max:500'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'reference_prefix' => ['nullable', 'alpha_num', 'max:12'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function fillTenant(Tenant $tenant, array $data, Request $request): void
    {
        $tenant->fill([
            'name' => trim($data['name']),
            'name_ar' => $data['name_ar'] ?? null,
            'slug' => Str::slug($data['slug']),
            'domain' => strtolower(trim($data['domain'])),
            'database' => trim($data['database']),
            'db_host' => trim($data['db_host']),
            'db_port' => (int) $data['db_port'],
            'db_username' => trim($data['db_username']),
            'primary_color' => strtolower($data['primary_color'] ?? '#1e3a8a'),
            'secondary_color' => strtolower($data['secondary_color'] ?? '#0e7490'),
            'reference_prefix' => strtoupper($data['reference_prefix'] ?? 'RDV'),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (!empty($data['db_password'])) {
            $tenant->db_password = $data['db_password'];
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = 'logo-' . Str::random(12) . '.' . strtolower($file->getClientOriginalExtension());
            $file->storeAs('branding/' . $tenant->slug, $fileName, 'public');
            $tenant->logo_path = $tenant->slug . '/' . $fileName;
        }
    }

    private function connectionError(Tenant $tenant, TenantManager $manager): ?string
    {
        try {
            $manager->activate($tenant);

            foreach (['t_user', 'patients', 'rendezvous', 'facture'] as $requiredTable) {
                if (!Schema::connection(config('tenancy.tenant_connection'))->hasTable($requiredTable)) {
                    return "Connexion établie, mais la table métier {$requiredTable} est absente.";
                }
            }
        } catch (\Throwable $exception) {
            return 'Connexion impossible : ' . $exception->getMessage();
        } finally {
            $manager->deactivate();
        }

        return null;
    }
}
