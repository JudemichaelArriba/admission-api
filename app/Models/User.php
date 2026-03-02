<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ✅ Correctly define casts as a property
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    public function applicant()
    {
        return $this->hasOne(Applicant::class);
    }

    public function hasAnyRole(array $roles): bool
    {
        $currentRole = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : (string) $role;
            if ($currentRole === $value) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->hasAnyRole([$role]);
    }
}
