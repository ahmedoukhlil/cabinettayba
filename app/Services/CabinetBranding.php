<?php

namespace App\Services;

use App\Models\Infocabinet;
use App\Tenancy\TenantManager;
use Illuminate\Support\Str;

class CabinetBranding
{
    private TenantManager $tenants;
    private ?array $resolved = null;
    private ?string $resolvedFor = null;

    public function __construct(TenantManager $tenants)
    {
        $this->tenants = $tenants;
    }

    public function get(): array
    {
        $tenant = $this->tenants->current();
        $resolutionKey = $tenant ? 'tenant:' . $tenant->getKey() : 'legacy';

        if ($this->resolved !== null && $this->resolvedFor === $resolutionKey) {
            return $this->resolved;
        }

        $this->resolvedFor = $resolutionKey;
        $legacy = null;

        try {
            $legacy = Infocabinet::find((int) config('appointments.public_cabinet_id', 1));
        } catch (\Throwable $exception) {
            // La marque centrale doit rester disponible même sans base métier.
        }

        $logoPath = $tenant->logo_path ?? null;
        $name = trim((string) ($tenant->name ?? $legacy->NomCabFr ?? 'Cabinet dentaire'));
        $nameAr = trim((string) ($tenant->name_ar ?? $legacy->NomCabAr ?? 'عيادة الأسنان'));

        $primaryColor = $this->color($tenant->primary_color ?? null, '#1e3a8a');

        return $this->resolved = [
            'name' => $name !== '' ? $name : 'Cabinet dentaire',
            'name_ar' => $nameAr !== '' ? $nameAr : 'عيادة الأسنان',
            'logo_url' => $this->logoUrl($logoPath),
            'has_logo' => !empty($logoPath),
            'logo_mime' => $this->logoMime($logoPath),
            'primary_color' => $primaryColor,
            'primary_rgb' => implode(', ', sscanf($primaryColor, '#%02x%02x%02x')),
            'secondary_color' => $this->color($tenant->secondary_color ?? null, '#0e7490'),
            'reference_prefix' => $this->prefix($tenant->reference_prefix ?? null),
            'phone' => trim((string) ($tenant->phone ?? $legacy->TelephonePublic ?? $legacy->ContactFR ?? '')),
            'email' => trim((string) ($tenant->email ?? $legacy->AdresseMail ?? '')),
            'address' => trim((string) ($tenant->address ?? $legacy->AdresseFr1 ?? '')),
        ];
    }

    public function forget(): void
    {
        $this->resolved = null;
        $this->resolvedFor = null;
    }

    private function logoUrl(?string $path): string
    {
        if (!$path) {
            return route('branding.logo');
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/branding/' . ltrim($path, '/'));
    }

    private function color(?string $color, string $fallback): string
    {
        return is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : $fallback;
    }

    private function logoMime(?string $path): string
    {
        return match (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => $path ? 'image/png' : 'image/svg+xml',
        };
    }

    private function prefix(?string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $prefix));
        return $prefix !== '' ? substr($prefix, 0, 12) : 'RDV';
    }
}
