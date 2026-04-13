<?php
// app/Models/ApiKey.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_name',
        'key',
        'is_active',
        'last_used_at',
        'last_used_ip',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['key'];

    public function isValid(): bool
    {
        return $this->is_active && !$this->trashed();
    }
}