<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Team;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Equipo Principal
        $equipo = Team::create([
            'name' => 'Gerencia General',
            'description' => 'Equipo administrativo principal',
            'is_active' => true,
        ]);

        // 2. Crear Admin Test (Asignado al equipo)
        User::create([
            'name' => 'Admin Test',
            'email' => 'test@test.com',
            'password' => Hash::make('test1234'), // Contraseña segura
            'role' => 'administrador',
            'is_active' => true,
            'email_verified_at' => now(),
            'equipo_id' => $equipo->id, // Asignamos el ID del equipo creado arriba
        ]);

        // 3. Crear un Supervisor de prueba (opcional)
        User::create([
            'name' => 'Supervisor Demo',
            'email' => 'sup@test.com',
            'password' => Hash::make('test1234'),
            'role' => 'supervisor',
            'is_active' => true,
            'equipo_id' => $equipo->id,
        ]);
    }
}