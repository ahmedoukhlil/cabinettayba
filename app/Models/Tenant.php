<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'is_active',
    ];

    protected $casts = [
        'db_port' => 'integer',
        'db_password' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
