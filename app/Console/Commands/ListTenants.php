<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ListTenants extends Command
{
    protected $signature = 'tenant:list';
    protected $description = 'Liste les cabinets enregistrés dans la base centrale';

    public function handle(): int
    {
        $this->table(
            ['ID', 'Nom', 'Slug', 'Domaine', 'Base', 'Actif'],
            Tenant::orderBy('name')->get()->map(fn (Tenant $tenant) => [
                $tenant->getKey(),
                $tenant->name,
                $tenant->slug,
                $tenant->domain ?: '—',
                $tenant->database,
                $tenant->is_active ? 'oui' : 'non',
            ])->all()
        );

        return self::SUCCESS;
    }
}
