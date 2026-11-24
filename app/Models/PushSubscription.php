<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'target_key',
        'endpoint_hash',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_id',
        'public_appointment_request_id',
        'cabinet_id',
        'target_url',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'public_appointment_request_id' => 'integer',
        'cabinet_id' => 'integer',
    ];
}
