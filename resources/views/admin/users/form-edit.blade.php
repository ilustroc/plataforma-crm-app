@if($type === 'password')
    {{-- Modal Change Password --}}
    <div id="modal-pw-{{ $user->id }}" class="modal-backdrop">
        <div class="modal-box">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg text-slate-800">Cambiar Contraseña</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close>✕</button>
            </div>
            <p class="text-sm text-slate-500 mb-4">Usuario: <strong>{{ $user->name }}</strong></p>
            
            <form action="{{ route('admin.users.password', $user) }}" method="POST">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-input" required minlength="6">
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="btn-sm-action px-4 py-2" data-modal-close>Cancelar</button>
                    <button type="submit" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#5B1F50]">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($type === 'reassign')
    {{-- Modal Reassign --}}
    <div id="modal-reassign-{{ $user->id }}" class="modal-backdrop">
        <div class="modal-box">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg text-slate-800">Reasignar Asesor</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close>✕</button>
            </div>
            <p class="text-sm text-slate-500 mb-4">Mover a <strong>{{ $user->name }}</strong> a otro equipo.</p>
            
            <form action="{{ route('admin.users.reassign', $user) }}" method="POST">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label class="form-label">Nuevo Supervisor</label>
                    <select name="supervisor_id" class="form-select" required>
                        @foreach($supervisores as $id => $name)
                            <option value="{{ $id }}" @selected($user->supervisor_id == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="btn-sm-action px-4 py-2" data-modal-close>Cancelar</button>
                    <button type="submit" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#5B1F50]">Reasignar</button>
                </div>
            </form>
        </div>
    </div>
@endif