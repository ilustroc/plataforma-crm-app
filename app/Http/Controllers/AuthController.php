<?php

namespace App\Http\Controllers;

use App\Application\Auth\LoginUser;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request, LoginUser $loginUser)
    {
        $ok = $loginUser->handle(
            $request->credentials(),
            $request->remember()
        );

        if ($ok) {
            $request->session()->regenerate();

            $request->session()->put('usuario', Auth::id());

            return redirect()->intended(route('panel'));
        }

        return back()
            ->withErrors(['email' => 'Credenciales inválidas'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget('usuario');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
