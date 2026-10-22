@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Dashboard — QUIZ ARENA')

@section('content')
    <div class="qa-dashboard-layout">
        <aside class="qa-dashboard-sidebar">
            <a href="{{ route('home') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
            <nav class="qa-dashboard-nav" aria-label="Navegación administrativa">
                <a href="{{ route('admin') }}" class="qa-menu-link" aria-current="page">Resumen</a>
                <a href="{{ route('admin.concursos') }}" class="qa-menu-link">Concursos</a>
                <a href="{{ route('admin.preguntas') }}" class="qa-menu-link">Preguntas</a>
                <a href="{{ route('admin.salas') }}" class="qa-menu-link">Salas</a>
                <a href="{{ route('admin.partidas') }}" class="qa-menu-link">Partidas</a>
                <a href="{{ route('admin.resultados') }}" class="qa-menu-link">Resultados</a>
            </nav>
        </aside>

        <main class="qa-dashboard-main">
            <div class="qa-dashboard-top">
                <div>
                    <div class="qa-badge" style="margin-bottom: 14px;">Panel administrativo</div>
                    <h1 style="font-size: clamp(26px, 4vw, 36px); font-weight: 900;">¡Hola, Administrador!</h1>
                    <p class="qa-dashboard-meta" style="margin-top: 8px;">Resumen general de QUIZ ARENA.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="qa-btn qa-btn-secondary qa-dashboard-logout">Cerrar sesión</button>
                </form>
            </div>

            <section class="qa-dashboard-stats" aria-label="Estadísticas generales">
                <article class="qa-dashboard-stat">
                    <span class="qa-dashboard-meta">Concursos activos</span>
                    <strong>{{ $activeQuizzes }}</strong>
                </article>
                <article class="qa-dashboard-stat">
                    <span class="qa-dashboard-meta">Salas</span>
                    <strong>{{ $totalRooms }}</strong>
                </article>
                <article class="qa-dashboard-stat">
                    <span class="qa-dashboard-meta">Partidas en curso</span>
                    <strong>{{ $activeMatches }}</strong>
                </article>
            </section>

            <section class="qa-dashboard-home">
                <h2>Tu espacio de administración</h2>
                <p>Administra concursos, preguntas, salas y partidas, y consulta sus resultados desde el menú.</p>
            </section>
        </main>
    </div>
@endsection
