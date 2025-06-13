<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Vérifier si l'utilisateur a la permission requise
     */
    public function handle($request, Closure $next, $permission)
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();
        
        // Vérification spéciale pour les actions de la secrétaire
        if ($user->isSecretaire()) {
            $secretaireActions = [
                'rendez-vous' => 'manage_rdv',
                'patient' => 'manage_patient',
                'caisse' => 'manage_caisse',
                'actes' => 'view_actes',
                'assureurs' => 'view_assureurs'
            ];

            foreach ($secretaireActions as $prefix => $action) {
                if (strpos($permission, $prefix) === 0 && $user->canSecretairePerform($action)) {
                    return $next($request);
                }
            }
        }

        if (!$user->hasPermission($permission)) {
            // Rediriger ou renvoyer une erreur 403
            return response()->view('errors.403', [], 403);
        }

        return $next($request);
    }
}