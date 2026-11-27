<?php

namespace App\Http\Livewire;

use App\Services\CabinetBranding;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class CabinetHeaderManager extends Component
{
    use WithFileUploads;

    public $name = '';
    public $nameAr = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $primaryColor = '#1e3a8a';
    public $secondaryColor = '#0e7490';
    public $referencePrefix = 'RDV';
    public $logo;
    public $currentLogoUrl = '';
    public $hasLogo = false;
    public $showRemoveLogoModal = false;

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->isDocteurProprietaire(), 403);
    }

    public function mount(TenantManager $tenants, CabinetBranding $branding): void
    {
        $tenant = $tenants->current();
        abort_unless($tenant, 404, 'Configuration du cabinet introuvable.');

        $this->name = (string) $tenant->name;
        $this->nameAr = (string) $tenant->name_ar;
        $this->phone = (string) $tenant->phone;
        $this->email = (string) $tenant->email;
        $this->address = (string) $tenant->address;
        $this->primaryColor = $tenant->primary_color ?: '#1e3a8a';
        $this->secondaryColor = $tenant->secondary_color ?: '#0e7490';
        $this->referencePrefix = $tenant->reference_prefix ?: 'RDV';

        $brand = $branding->get();
        $this->currentLogoUrl = $brand['logo_url'];
        $this->hasLogo = (bool) $brand['has_logo'];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'nameAr' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:500'],
            'primaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'referencePrefix' => ['required', 'alpha_num', 'max:12'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    public function save(TenantManager $tenants, CabinetBranding $branding): void
    {
        $this->validate();
        $tenant = $tenants->current();
        abort_unless($tenant, 404, 'Configuration du cabinet introuvable.');

        $tenant->fill([
            'name' => trim($this->name),
            'name_ar' => $this->nullableText($this->nameAr),
            'phone' => $this->nullableText($this->phone),
            'email' => $this->nullableText($this->email),
            'address' => $this->nullableText($this->address),
            'primary_color' => strtolower($this->primaryColor),
            'secondary_color' => strtolower($this->secondaryColor),
            'reference_prefix' => strtoupper($this->referencePrefix),
        ]);

        if ($this->logo) {
            $this->deleteStoredLogo($tenant->logo_path);
            $fileName = 'logo-' . Str::random(12) . '.' . strtolower($this->logo->getClientOriginalExtension());
            $tenant->logo_path = $this->logo->storeAs($tenant->slug, $fileName, 'branding');
        }

        $tenant->save();
        $this->logo = null;
        $branding->forget();

        $brand = $branding->get();
        $this->currentLogoUrl = $brand['logo_url'];
        $this->hasLogo = (bool) $brand['has_logo'];
        $this->dispatch('brandingUpdated');
        session()->flash('message', 'Les informations du cabinet ont été enregistrées.');
    }

    public function removeLogo(TenantManager $tenants, CabinetBranding $branding): void
    {
        $tenant = $tenants->current();
        abort_unless($tenant, 404, 'Configuration du cabinet introuvable.');

        $this->deleteStoredLogo($tenant->logo_path);
        $tenant->logo_path = null;
        $tenant->save();

        $this->logo = null;
        $this->showRemoveLogoModal = false;
        $branding->forget();
        $brand = $branding->get();
        $this->currentLogoUrl = $brand['logo_url'];
        $this->hasLogo = false;
        $this->dispatch('brandingUpdated');
        session()->flash('message', 'Le logo personnalisé a été retiré.');
    }

    private function deleteStoredLogo(?string $path): void
    {
        if ($path && !Str::startsWith($path, ['http://', 'https://', '/']) && !Str::contains($path, '..')) {
            Storage::disk('branding')->delete($path);
        }
    }

    private function nullableText($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    public function render()
    {
        return view('livewire.cabinet-header-manager');
    }
}
