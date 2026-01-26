<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes; // Importante para no perder datos

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',            // administrador, supervisor, asesor
        'is_active',       // true/false
        'equipo_id',       // ID del equipo
        'supervisor_id',   // ID del jefe directo
        'avatar',
        'last_login_at',   // Fecha último acceso
        'last_login_ip',   // IP último acceso
    ];

    /**
     * Atributos ocultos para arrays y JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversiones automáticas de tipos de datos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',      // Convierte 1/0 a true/false
        'last_login_at' => 'datetime', // Convierte timestamp a objeto Carbon
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Relación: Un usuario pertenece a un Equipo
    public function equipo()
    {
        return $this->belongsTo(Team::class, 'equipo_id');
    }

    // Relación: Un asesor "pertenece" a un Supervisor (Jefe)
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // Relación: Un Supervisor tiene muchos Asesores a su cargo
    public function asesores()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }
    
    /*
    |--------------------------------------------------------------------------
    | Helpers (Opcionales pero útiles)
    |--------------------------------------------------------------------------
    */
    
    // Verifica si es administrador
    public function isAdmin()
    {
        return $this->role === 'administrador';
    }

    // Verifica si es supervisor
    public function isSupervisor()
    {
        return $this->role === 'supervisor';
    }
}