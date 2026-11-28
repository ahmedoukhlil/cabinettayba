<?php

namespace App\Http\Controllers;

use App\Services\CabinetBranding;
use Illuminate\Http\Response;

class BrandingController extends Controller
{
    public function manifest(string $audience, CabinetBranding $branding, ?string $manifestId = null)
    {
        abort_unless(in_array($audience, ['patient', 'staff'], true), 404);
        $brand = $branding->get();
        $patient = $audience === 'patient';

        return response()->json([
            'id' => $manifestId ?? ($patient ? '/reservation' : '/login'),
            'name' => $brand['pwa_name'],
            'short_name' => $brand['pwa_name'],
            'description' => $patient
                ? 'Réservation et suivi des rendez-vous'
                : 'Agenda et confirmation des rendez-vous',
            'start_url' => $patient ? route('public.appointment.create', [], false) : route('login', [], false),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f8fafc',
            'theme_color' => $brand['primary_color'],
            'lang' => $patient ? 'ar' : 'fr',
            'dir' => $patient ? 'rtl' : 'ltr',
            'icons' => [[
                'src' => $brand['logo_url'],
                'sizes' => 'any',
                'type' => $brand['logo_mime'],
                'purpose' => 'any',
            ]],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'private, max-age=300',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function staffManifest(CabinetBranding $branding)
    {
        return $this->manifest('staff', $branding, '/cabinet-pwa');
    }

    public function patientManifest(CabinetBranding $branding)
    {
        return $this->manifest('patient', $branding);
    }

    public function logo(CabinetBranding $branding): Response
    {
        $primaryColor = $branding->get()['primary_color'];
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">
  <rect width="128" height="128" rx="28" fill="#eef2ff"/>
  <path d="M64 25c-15-12-42-5-41 18 1 17 11 23 14 37 4 18 6 26 14 26 9 0 6-28 13-28s4 28 13 28c8 0 10-8 14-26 3-14 13-20 14-37 1-23-26-30-41-18Z" fill="none" stroke="{$primaryColor}" stroke-width="8" stroke-linejoin="round"/>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
