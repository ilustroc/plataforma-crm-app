<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getHierarchy()
    {
        return User::where('role', 'supervisor')
            ->with('asesores')
            ->withCount('asesores')
            ->get();
    }

    public function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updatePassword(User $user, string $newPassword)
    {
        $user->update(['password' => Hash::make($newPassword)]);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
    }

    public function reassign(User $user, int $newSupervisorId)
    {
        $user->update(['supervisor_id' => $newSupervisorId]);
    }
}