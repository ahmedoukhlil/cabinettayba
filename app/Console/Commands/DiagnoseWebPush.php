<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Services\WebPushService;
use App\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseWebPush extends Command
{
    protected $signature = 'notifications:diagnose
        {tenant? : Slug ou domaine du cabinet}
        {--send-test : Envoyer une notification aux abonnements du personnel}
        {--user= : Limiter le test à un identifiant utilisateur}';

    protected $description = 'Vérifie la configuration Web Push et les abonnements de chaque tenant';

    public function handle(TenantManager $tenants, WebPushService $webPush): int
    {
        $configurationValid = $this->checkVapidConfiguration();

        if (!config('tenancy.enabled')) {
            $tenantValid = $this->diagnoseCurrentDatabase('historique', $webPush);
            return $configurationValid && $tenantValid ? self::SUCCESS : self::FAILURE;
        }

        $query = Tenant::active();
        if ($identifier = $this->argument('tenant')) {
            $query->where(function ($tenantQuery) use ($identifier) {
                $tenantQuery->where('slug', $identifier)->orWhere('domain', $identifier);
            });
        }

        $registeredTenants = $query->orderBy('name')->get();
        if ($registeredTenants->isEmpty()) {
            $this->error('Aucun tenant actif ne correspond à cette valeur.');
            return self::FAILURE;
        }

        $allValid = $configurationValid;
        foreach ($registeredTenants as $tenant) {
            $this->newLine();
            $this->info("Cabinet : {$tenant->name} ({$tenant->domain})");

            try {
                $tenants->activate($tenant);
                $allValid = $this->diagnoseCurrentDatabase($tenant->slug, $webPush) && $allValid;
            } catch (\Throwable $exception) {
                $allValid = false;
                $this->error('Connexion impossible : ' . $exception->getMessage());
            } finally {
                $tenants->deactivate();
            }
        }

        return $allValid ? self::SUCCESS : self::FAILURE;
    }

    private function checkVapidConfiguration(): bool
    {
        $vapid = config('webpush.vapid');
        $subjectValid = is_string($vapid['subject']) && (
            filter_var($vapid['subject'], FILTER_VALIDATE_URL)
            || str_starts_with($vapid['subject'], 'mailto:')
        );
        $rows = [
            ['Sujet VAPID', $subjectValid ? $vapid['subject'] : 'ABSENT OU INVALIDE'],
            ['Clé publique', empty($vapid['public_key']) ? 'ABSENTE' : 'présente'],
            ['Clé privée', empty($vapid['private_key']) ? 'ABSENTE' : 'présente'],
        ];
        $this->table(['Configuration', 'Valeur'], $rows);

        if (!$subjectValid || empty($vapid['public_key']) || empty($vapid['private_key'])) {
            $this->error('La configuration VAPID est incomplète ou invalide.');
            return false;
        }

        return true;
    }

    private function diagnoseCurrentDatabase(string $label, WebPushService $webPush): bool
    {
        $database = DB::connection()->getDatabaseName();
        if (!Schema::hasTable('push_subscriptions')) {
            $this->error("{$label} : table push_subscriptions absente dans {$database}.");
            return false;
        }

        $staffQuery = PushSubscription::whereNotNull('user_id');
        if ($userId = $this->option('user')) {
            $staffQuery->where('user_id', (int) $userId);
        }

        $staffSubscriptions = $staffQuery->get();
        $patientCount = PushSubscription::whereNotNull('public_appointment_request_id')->count();
        $this->table(['Base', 'Personnel', 'Patients', 'Total'], [[
            $database,
            $staffSubscriptions->count(),
            $patientCount,
            PushSubscription::count(),
        ]]);

        if (!$this->option('send-test')) {
            return true;
        }

        if ($staffSubscriptions->isEmpty()) {
            $this->warn('Aucun abonnement du personnel ne correspond au test. Activez les notifications dans la PWA.');
            return false;
        }

        $result = $webPush->sendDiagnostic($staffSubscriptions);
        $this->table(['Mis en file', 'Réussis', 'Expirés', 'Échecs'], [[
            $result['queued'],
            $result['success'],
            $result['expired'],
            $result['failed'],
        ]]);

        if ($result['skipped'] || $result['success'] === 0) {
            $this->error('Aucune notification de test n’a été acceptée par le fournisseur Push.');
            return false;
        }

        $this->info('Notification de test acceptée par le fournisseur Push.');
        return true;
    }
}
