<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // --- Identidad ---
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            
            // --- Perfil y Roles ---
            $table->string('role')->default('asesor')->comment('administrador, supervisor, asesor');
            $table->boolean('is_active')->default(true);
            
            // --- Relaciones ---
            // 1. Relación con Equipos (teams)
            // Usamos 'equipo_id' apuntando a la tabla 'teams'
            $table->foreignId('equipo_id')
                  ->nullable()
                  ->constrained('teams') // Busca tabla 'teams', columna 'id'
                  ->nullOnDelete();      // Si borras el equipo, el usuario queda sin equipo (no se borra)

            // 2. Relación con Supervisor (misma tabla users)
            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // --- Auditoría ---
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};