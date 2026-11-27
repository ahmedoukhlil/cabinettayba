<?php

namespace App\Console\Commands;

use App\Models\CentralUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateCentralAdmin extends Command
{
    protected $signature = 'central-admin:create {email} {--name=Administrateur}';
    protected $description = 'Crée un administrateur de la plateforme centrale';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');
            return self::FAILURE;
        }

        if (CentralUser::where('email', $email)->exists()) {
            $this->error('Un administrateur utilise déjà cette adresse.');
            return self::FAILURE;
        }

        $password = (string) $this->secret('Mot de passe');
        $confirmation = (string) $this->secret('Confirmez le mot de passe');

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');
            return self::FAILURE;
        }

        if (!hash_equals($password, $confirmation)) {
            $this->error('Les mots de passe ne correspondent pas.');
            return self::FAILURE;
        }

        CentralUser::create([
            'name' => trim((string) $this->option('name')) ?: 'Administrateur',
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info('Administrateur central créé.');
        return self::SUCCESS;
    }
}
