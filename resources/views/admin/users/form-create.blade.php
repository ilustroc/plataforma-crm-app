<div class="admin-card">
    <div class="admin-card-header">
        <div class="p-2 bg-brand/5 text-brand rounded-lg">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
        </div>
        <h3 class="admin-card-title ml-2">{{ $title }}</h3>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">

        <div class="form-group">
            <label class="form-label">Nombre Completo</label>
            <input type="text" name="name" class="form-input" placeholder="Ej: Juan Pérez" required>
        </div>

        <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-input" placeholder="usuario@empresa.com" required>
        </div>

        <div class="form-group">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-input" placeholder="Mínimo 6 caracteres" minlength="6" required>
        </div>

        @if($role === 'asesor')
            <div class="form-group">
                <label class="form-label">Asignar a Supervisor</label>
                <select name="supervisor_id" class="form-select" required>
                    <option value="">Selecciona un supervisor...</option>
                    @foreach($listaSupervisores as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <button type="submit" class="btn-primary mt-4">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Crear Usuario
        </button>
    </form>
</div>