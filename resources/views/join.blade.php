@extends('layouts.arena')

@section('title', 'Ingresar a partida — QUIZ ARENA')

@section('content')
    <div style="max-width: 560px; margin: 0 auto;">
        <a href="{{ route('home') }}" class="qa-menu-link" style="display: inline-block; margin-bottom: 20px;">← Inicio</a>
        <div class="qa-badge">Sin registro</div>
        <h1 class="qa-title" style="font-size: clamp(30px, 7vw, 42px);">INGRESAR A PARTIDA</h1>
        <p class="qa-desc">Escribe el código que te compartió el anfitrión y elige el nombre con el que aparecerás en la sala.</p>

        <form method="POST" action="{{ route('join.store') }}" class="qa-form" style="display: grid; gap: 18px; margin-top: 28px; text-align: left;">
            @csrf

            <div class="qa-field">
                <label for="code">Código de sala</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    value="{{ old('code') }}"
                    minlength="6"
                    maxlength="6"
                    pattern="[A-Za-z0-9]{6}"
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                    required
                    aria-describedby="code-help"
                    placeholder="Ejemplo: A1B2C3"
                >
                <span id="code-help" class="qa-dashboard-meta">El código tiene 6 letras o números.</span>
            </div>

            <div class="qa-field">
                <label for="nickname">Tu nombre en la partida</label>
                <input
                    id="nickname"
                    name="nickname"
                    type="text"
                    value="{{ old('nickname') }}"
                    minlength="2"
                    maxlength="40"
                    autocomplete="nickname"
                    required
                    placeholder="Ejemplo: Luna"
                >
            </div>

            <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">Unirme a la sala</button>
        </form>
    </div>
@endsection
