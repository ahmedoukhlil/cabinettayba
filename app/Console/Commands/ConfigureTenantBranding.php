<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConfigureTenantBranding extends Command
{
    protected $signature = 'tenant:brand
        {slug : Identifiant du cabinet}
        {--name= : Nom français}
        {--name-ar= : Nom arabe}
        {--logo= : Fichier local PNG, JPG, WEBP ou SVG}
        {--primary= : Couleur principale, par exemple #1e3a8a}
        {--secondary= : Couleur secondaire}
        {--prefix= : Préfixe des demandes de rendez-vous}
        {--phone= : Téléphone public}
        {--email= : Adresse e-mail publique}
        {--address= : Adresse publique}';

    protected $description = 'Configure l’identité visuelle d’un cabinet dans la base centrale';

    public function handle(): int
    {
        $tenant = Tenant::where('slug', $this->argument('slug'))->first();
        if (!$tenant) {
            $this->error('Cabinet introuvable.');
            return self::FAILURE;
        }

        $values = array_filter([
            'name' => $this->option('name'),
            'name_ar' => $this->option('name-ar'),
            'primary_color' => $this->validatedColor('primary'),
            'secondary_color' => $this->validatedColor('secondary'),
            'reference_prefix' => $this->validatedPrefix(),
            'phone' => $this->option('phone'),
            'email' => $this->option('email'),
            'address' => $this->option('address'),
        ], fn ($value) => $value !== null);

        if ($this->option('logo')) {
            $values['logo_path'] = $this->storeLogo($tenant, (string) $this->option('logo'));
            if ($values['logo_path'] === null) {
                return self::FAILURE;
            }
        }

        $tenant->fill($values)->save();
        $this->info("Identité de {$tenant->name} mise à jour.");
        return self::SUCCESS;
    }

    private function validatedColor(string $option): ?string
    {
        $value = $this->option($option);
        if ($value === null) {
            return null;
        }
        if (!preg_match('/^#[0-9a-f]{6}$/i', (string) $value)) {
            throw new \InvalidArgumentException("La couleur --{$option} doit utiliser le format #RRGGBB.");
        }
        return strtolower((string) $value);
    }

    private function validatedPrefix(): ?string
    {
        $value = $this->option('prefix');
        if ($value === null) {
            return null;
        }
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $value));
        if ($prefix === '' || strlen($prefix) > 12) {
            throw new \InvalidArgumentException('Le préfixe doit contenir entre 1 et 12 lettres ou chiffres.');
        }
        return $prefix;
    }

    private function storeLogo(Tenant $tenant, string $source): ?string
    {
        if (!File::isFile($source)) {
            $this->error("Logo introuvable : {$source}");
            return null;
        }

        $extension = strtolower(File::extension($source));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
            $this->error('Le logo doit être au format PNG, JPG, WEBP ou SVG.');
            return null;
        }

        $directory = storage_path('app/public/branding/' . $tenant->slug);
        File::ensureDirectoryExists($directory);
        $fileName = 'logo-' . Str::random(8) . '.' . $extension;
        File::copy($source, $directory . '/' . $fileName);

        return $tenant->slug . '/' . $fileName;
    }
}
