@extends('layouts.arena')

@section('title', 'Nueva contraseña — QUIZ ARENA')

@section('content')
    <div style="max-width: 440px; margin: 0 auto;">
        <h1 class="qa-title" style="font-size: 40px;">NUEVA CLAVE</h1>
        <p class="qa-subtitle">Crea una nueva contraseña de administrador</p>

        @if ($errors->any())
            <div class="qa-alert qa-alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="qa-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div style="text-align: left; margin-bottom: 16px;">
                <label for="email" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">
                    Correo electrónico
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    autocomplete="email"
                    class="qa-input"
                    style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;"
                />
            </div>

            <div style="text-align: left; margin-bottom: 16px;">
                <label for="password" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">
                    Nueva contraseña
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="qa-input"
                    style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;"
                />
            </div>

            <div style="text-align: left; margin-bottom: 20px;">
                <label for="password_confirmation" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">
                    Confirmar contraseña
                </label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="qa-input"
                    style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;"
                />
            </div>

            <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">Guardar nueva contraseña</button>
        </form>
    </div>
@endsection
