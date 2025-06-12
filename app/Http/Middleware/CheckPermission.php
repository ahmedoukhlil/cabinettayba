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
        
        if (!$user->hasPermission($permission)) {
            // Rediriger ou renvoyer une erreur 403
            return response()->view('errors.403', [], 403);
        }

        return $next($request);
    }
}