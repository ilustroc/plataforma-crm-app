<div id="modal-create-{{ $role }}" class="modal-backdrop">
    <div class="modal-box">
        {{-- Header Modal --}}
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                @if($role == 'supervisor')
                    <svg class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                @else
                    <svg class="h-6 w-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                @endif
                {{ $title }}
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" data-modal-close>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Formulario --}}
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <input type="hidden" name="role" value="{{ $role }}">

            <div class="space-y-4">
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
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button type="button" class="btn-sm-action px-5 py-2.5" data-modal-close>Cancelar</button>
                <button type="submit" class="btn-primary w-auto px-6">
                    Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>