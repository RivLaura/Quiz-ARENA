@extends('layouts.arena')

@section('title', 'Recuperar contraseña — QUIZ ARENA')

@section('content')
    <div style="max-width: 440px; margin: 0 auto;">
        <h1 class="qa-title" style="font-size: 40px;">RECUPERAR</h1>
        <p class="qa-subtitle">Restablece tu acceso de administrador</p>

        @if (session('status'))
            <div class="qa-alert qa-alert-ok" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="qa-alert qa-alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="qa-form">
            @csrf
            <div style="text-align: left; margin-bottom: 20px;">
                <label for="email" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">
                    Correo de administrador
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    class="qa-input"
                    style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;"
                />
            </div>
            <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">Enviar enlace de recuperación</button>
        </form>

        <p class="qa-footer"><a href="{{ route('login') }}">Volver al acceso administrador</a></p>
    </div>
@endsection
