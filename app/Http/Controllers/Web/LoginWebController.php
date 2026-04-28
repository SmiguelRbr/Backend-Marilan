<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginWebController extends Controller
{
    public function showLogin() {
        return view('login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'cracha' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Verifica se é almoxarife antes de deixar entrar no dash
            if (Auth::user()->role !== 'almoxarife') {
                Auth::logout();
                return back()->withErrors(['acesso' => 'Acesso restrito ao almoxarifado.']);
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['cracha' => 'Credenciais inválidas.']);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
