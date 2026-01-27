<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        // Agregamos paginación aquí para mejor rendimiento
        $supervisores = $this->userService->getHierarchy(); 
        $listaSupervisores = User::where('role', 'supervisor')->pluck('name', 'id');

        return view('admin.users.index', compact('supervisores', 'listaSupervisores'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());
        return back()->with('ok', 'Usuario creado correctamente.');
    }

    // USAMOS EL REQUEST DEDICADO
    public function password(UpdateUserPasswordRequest $request, User $user)
    {
        // La validación ya ocurrió automáticamente en el Request
        $this->userService->updatePassword($user, $request->password);
        return back()->with('ok', 'Contraseña actualizada.');
    }

    public function toggle(User $user)
    {
        $this->userService->toggleStatus($user);
        return back()->with('ok', 'Estado actualizado.');
    }

    // USAMOS EL REQUEST DEDICADO
    public function reassign(UpdateUserRequest $request, User $user)
    {
        $this->userService->reassign($user, $request->supervisor_id);
        return back()->with('ok', 'Asesor reasignado.');
    }
}