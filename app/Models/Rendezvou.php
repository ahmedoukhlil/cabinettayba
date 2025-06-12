<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Rendezvou extends Model
{
    // Nom de la table
    protected $table = 'rendezvous';

    // Clé primaire
    protected $primaryKey = 'IDRdv';

    // Pas de timestamps Laravel (created_at, updated_at)
    public $timestamps = false;

    // Champs à caster en date/heure
    protected $dates = [
        'DtAjRdv',
        'dtPrevuRDV',
        'HeureRdv',
        'HeureConfRDV'
    ];

    // Champs assignables en masse
    protected $fillable = [
        'ActePrevu',
        'DtAjRdv',
        'dtPrevuRDV',
        'user',
        'HeureRdv',
        'fkidPatient',
        'rdvConfirmer',
        'fkidMedecin',
        'OrdreRDV',
        'HeureConfRDV',
        'fkidcabinet',
        'fkidFacture'
    ];

    /**
     * Relation : le médecin associé à ce rendez-vous
     */
    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidMedecin', 'idMedecin');
    }

    /**
     * Relation : le patient associé à ce rendez-vous
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    /**
     * Relation : le cabinet associé à ce rendez-vous
     */
    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idEntete');
    }

    /**
     * Génère le prochain numéro d'ordre pour la date donnée
     */
    public static function generateNextOrderNumber($date)
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        $lastOrder = self::whereDate('dtPrevuRDV', $date->format('Y-m-d'))
            ->where('fkidcabinet', Auth::user()->fkidcabinet)
            ->max('OrdreRDV');

        return ($lastOrder ?? 0) + 1;
    }
} 