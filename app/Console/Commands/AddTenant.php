<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddTenant extends Command
{
    protected $signature = 'tenant:add
        {slug : Identifiant court du cabinet}
        {database : Nom de la base métier déjà créée}
        {--name= : Nom affiché du cabinet}
        {--domain= : Domaine complet, par exemple clinique.example.com}
        {--host= : Hôte SQL spécifique}
        {--port= : Port SQL spécifique}
        {--username= : Utilisateur SQL spécifique}
        {--ask-password : Demander un mot de passe SQL spécifique}';

    protected $description = 'Enregistre un cabinet et vérifie sa base de données';

    public function handle(TenantManager $manager): int
    {
        $slug = Str::slug((string) $this->argument('slug'));

        if ($slug === '') {
            $this->error('Le slug du cabinet est invalide.');
            return self::FAILURE;
        }

        if (Tenant::where('slug', $slug)->exists()) {
            $this->error("Le cabinet {$slug} existe déjà.");
            return self::FAILURE;
        }

        $password = $this->option('ask-password')
            ? $this->secret('Mot de passe SQL du cabinet')
            : null;

        $tenant = new Tenant([
            'name' => $this->option('name') ?: Str::headline($slug),
            'slug' => $slug,
            'domain' => $this->option('domain') ?: null,
            'database' => $this->argument('database'),
            'db_host' => $this->option('host') ?: null,
            'db_port' => $this->option('port') ?: null,
            'db_username' => $this->option('username') ?: null,
            'db_password' => $password,
            'is_active' => true,
        ]);

        try {
            $manager->activate($tenant);

            foreach (['t_user', 'patients', 'rendezvous', 'facture'] as $requiredTable) {
                if (!Schema::connection(config('tenancy.tenant_connection'))->hasTable($requiredTable)) {
                    throw new \RuntimeException("La table métier {$requiredTable} est absente.");
                }
            }

            $manager->deactivate();
        } catch (\Throwable $exception) {
            $manager->deactivate();
            $this->error('Connexion impossible : ' . $exception->getMessage());
            return self::FAILURE;
        }

        $tenant->save();
        $this->info("Cabinet {$tenant->name} enregistré avec succès.");

        return self::SUCCESS;
    }
}
