<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Valida las credenciales y autentica al usuario.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin'))
                ->with('ok', 'Sesión iniciada correctamente.');
        }

        return back()
            ->withErrors(['email' => 'Las credenciales ingresadas son incorrectas.'])
            ->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home')->with('ok', 'Sesión cerrada correctamente.');
    }
}
