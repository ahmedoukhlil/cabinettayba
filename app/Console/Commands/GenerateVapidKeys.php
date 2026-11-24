<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'pwa:vapid-keys';
    protected $description = 'Génère les clés VAPID utilisées par les notifications Web Push';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->line('Ajoutez ces valeurs dans le fichier .env du serveur :');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('VAPID_SUBJECT=' . config('app.url'));

        return self::SUCCESS;
    }
}
