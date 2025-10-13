<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ⛳️ SOLO UNA VEZ
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'supervisor_id',
        'equipo_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Opcional (si lo usas, puedes guardar el password en texto y se hashea solo)
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relaciones
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function asesores()   { return $this->hasMany(User::class, 'supervisor_id')->where('role','asesor'); }

    // Scopes
    public function scopeSupervisores($q){ return $q->where('role','supervisor'); }
    public function scopeAsesores($q){ return $q->where('role','asesor'); }
    
}

