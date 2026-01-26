<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
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
        $supervisores = $this->userService->getHierarchy();
        $listaSupervisores = $supervisores->pluck('name', 'id'); 

        return view('admin.users.index', compact('supervisores', 'listaSupervisores'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());
        return back()->with('ok', 'Usuario creado correctamente.');
    }

    public function password(Request $request, User $user)
    {
        $request->validate(['password' => 'required|min:6|confirmed']);
        $this->userService->updatePassword($user, $request->password);
        return back()->with('ok', 'Contraseña actualizada.');
    }

    public function toggle(User $user)
    {
        $this->userService->toggleStatus($user);
        return back()->with('ok', 'Estado actualizado.');
    }

    public function reassign(Request $request, User $user)
    {
        $request->validate(['supervisor_id' => 'required|exists:users,id']);
        $this->userService->reassign($user, $request->supervisor_id);
        return back()->with('ok', 'Asesor reasignado.');
    }
}