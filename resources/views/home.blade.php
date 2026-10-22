@extends('layouts.arena')

@section('title', 'QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <div class="qa-brand">QUIZ ARENA</div>
        <nav class="qa-nav">
            <a href="{{ route('home') }}">Inicio</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Tiempo real · Multijugador</div>
        <h1 class="qa-title">QUIZ ARENA</h1>
        <p class="qa-subtitle">La arena de los mejores.</p>
        <p class="qa-desc">Convoca una partida con tus amigos, compite en tiempo real y decide quién se queda con la victoria.</p>
        <div class="qa-actions">
            <a class="qa-btn qa-btn-primary" href="{{ route('join') }}">Ingresar a una partida</a>
            <a class="qa-btn qa-btn-secondary" href="{{ route('admin') }}">Acceso administrador</a>
        </div>
    </main>

    <footer class="qa-footer">Sin registro necesario para los jugadores.</footer>
@endsection
