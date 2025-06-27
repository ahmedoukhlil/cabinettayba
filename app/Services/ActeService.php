<?php

namespace App\Services;

use App\Models\Acte;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ActeService
{
    /**
     * Recherche d'actes avec cache et optimisations
     */
    public function searchActes(string $searchTerm, ?int $assureurId = null): Collection
    {
        $cacheKey = "acte_search_{$assureurId}_{$searchTerm}";
        
        return Cache::remember($cacheKey, 300, function () use ($searchTerm, $assureurId) {
            $query = Acte::select('ID', 'Acte', 'PrixRef')
                ->where('Masquer', 0)
                ->where(function($q) use ($searchTerm) {
                    $q->where('Acte', 'like', $searchTerm . '%')  // Recherche par préfixe (plus rapide)
                      ->orWhere('Acte', 'like', '% ' . $searchTerm . '%')  // Recherche par mot
                      ->orWhere('Acte', 'like', '%' . $searchTerm . '%');  // Recherche générale
                })
                ->orderBy(DB::raw("CASE WHEN Acte LIKE '{$searchTerm}%' THEN 1 ELSE 2 END")) // Priorité aux résultats commençant par le terme
                ->orderBy('nordre', 'asc')
                ->limit(30);

            if ($assureurId) {
                $query->where('fkidassureur', $assureurId);
            }

            return $query->get()->unique('Acte')->values();
        });
    }

    /**
     * Récupère un acte par ID avec cache
     */
    public function getActeById(int $id): ?Acte
    {
        $cacheKey = "acte_{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return Acte::select('ID', 'Acte', 'PrixRef')->find($id);
        });
    }

    /**
     * Récupère les actes populaires avec cache
     */
    public function getPopularActes(?int $assureurId = null): Collection
    {
        $cacheKey = "popular_actes_{$assureurId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($assureurId) {
            $query = Acte::select('ID', 'Acte', 'PrixRef')
                ->where('Masquer', 0)
                ->orderBy('nordre', 'asc')
                ->limit(20);

            if ($assureurId) {
                $query->where('fkidassureur', $assureurId);
            }

            return $query->get()->unique('Acte')->values();
        });
    }

    /**
     * Récupère tous les actes pour un assureur avec cache
     */
    public function getAllActesForAssureur(?int $assureurId = null): Collection
    {
        $cacheKey = "all_actes_{$assureurId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($assureurId) {
            $query = Acte::select('ID', 'Acte', 'PrixRef')
                ->where('Masquer', 0)
                ->orderBy('Acte');

            if ($assureurId) {
                $query->where('fkidassureur', $assureurId);
            }

            return $query->get();
        });
    }

    /**
     * Invalide le cache pour un acte spécifique
     */
    public function invalidateActeCache(int $acteId): void
    {
        Cache::forget("acte_{$acteId}");
        // Invalider aussi les caches de recherche
        $this->clearSearchCache();
    }

    /**
     * Invalide tous les caches de recherche
     */
    public function clearSearchCache(): void
    {
        // Note: Dans un environnement de production, vous pourriez utiliser Redis avec des patterns
        // Pour simplifier, on invalide les caches populaires
        Cache::forget('popular_actes_null');
        Cache::forget('all_actes_null');
    }

    /**
     * Recherche rapide avec index optimisé
     */
    public function quickSearch(string $searchTerm, ?int $assureurId = null, int $limit = 10): Collection
    {
        // Utilisation d'une requête plus légère pour les recherches rapides
        $query = Acte::select('ID', 'Acte', 'PrixRef')
            ->where('Masquer', 0)
            ->where('Acte', 'like', $searchTerm . '%')
            ->orderBy('nordre', 'asc')
            ->limit($limit);

        if ($assureurId) {
            $query->where('fkidassureur', $assureurId);
        }

        return $query->get();
    }
} 