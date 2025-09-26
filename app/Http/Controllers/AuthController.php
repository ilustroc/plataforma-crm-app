<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller{
  public function form(){ return view('auth.login'); }
    public function login(Request $r){
        $cred = $r->validate(['email'=>'required|email','password'=>'required']);
    
        if (Auth::attempt($cred, $r->boolean('remember'))) {
            $r->session()->regenerate();
            return redirect()->intended(route('panel'));  
        }
    
        return back()->withErrors(['email'=>'Credenciales inválidas'])->onlyInput('email');
    }

  public function logout(Request $r){ 
    Auth::logout(); $r->session()->invalidate(); 
    $r->session()->regenerateToken(); return redirect()->route('login'); 
  }
}