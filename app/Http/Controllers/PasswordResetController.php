<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($validated['email'])])
            ->where('role', 'admin')
            ->first();

        if ($user !== null) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with(
            'status',
            'Si el correo corresponde a una cuenta de administrador, recibirás un enlace para restablecer la contraseña.',
        );
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'email' => $request->query('email', ''),
            'token' => $token,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($validated['email'])])
            ->where('role', 'admin')
            ->first();

        if ($user === null) {
            return back()
                ->withErrors(['email' => 'El enlace de recuperación no es válido o ya venció.'])
                ->onlyInput('email');
        }

        $status = Password::reset([
            'email' => $user->email,
            'password' => $validated['password'],
            'token' => $validated['token'],
        ], function (User $user, string $password): void {
            if (! $user->isAdmin()) {
                throw ValidationException::withMessages([
                    'email' => 'El enlace de recuperación no es válido o ya venció.',
                ]);
            }

            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withErrors(['email' => 'El enlace de recuperación no es válido o ya venció.'])
                ->onlyInput('email');
        }

        return redirect()
            ->route('login')
            ->with('ok', 'La contraseña se actualizó correctamente. Ya puedes iniciar sesión.');
    }
}
