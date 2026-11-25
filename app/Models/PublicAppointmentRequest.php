<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicAppointmentRequest extends Model
{
    protected $casts = [
        'fkidcabinet' => 'integer',
        'fkidmedecin' => 'integer',
        'fkidacte' => 'integer',
        'date_souhaitee' => 'date',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected $fillable = [
        'fkidcabinet',
        'fkidmedecin',
        'fkidacte',
        'nom',
        'prenom',
        'telephone',
        'date_souhaitee',
        'heure_souhaitee',
        'message',
        'status',
        'reference',
        'tracking_token_hash',
        'slot_key',
        'confirmed_by',
        'rejected_by',
        'patient_id',
        'appointment_id',
        'confirmed_at',
        'rejected_at',
    ];

    protected $hidden = [
        'tracking_token_hash',
        'slot_key',
    ];

    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidmedecin', 'idMedecin');
    }

    public function acte()
    {
        return $this->belongsTo(Acte::class, 'fkidacte', 'ID');
    }

    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idEntete');
    }

    public function appointment()
    {
        return $this->belongsTo(Rendezvou::class, 'appointment_id', 'IDRdv');
    }
}
