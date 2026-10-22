@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Salas — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Acciones de salas">
            <a href="{{ route('admin') }}" class="qa-menu-link">Panel</a>
            <a href="{{ route('admin.salas.create') }}" class="qa-btn qa-btn-primary">Crear sala</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Administración</div>
        <h1 class="qa-title" style="font-size: clamp(32px, 6vw, 54px);">SALAS</h1>
        <p class="qa-desc">Crea salas para tus concursos activos y comparte el código con tus participantes.</p>

        @if ($rooms->isEmpty())
            <section class="qa-card" style="margin-top: 32px;">
                <h2 style="font-size: 20px;">Todavía no tienes salas</h2>
                <p class="qa-desc" style="margin-top: 10px;">Activa un concurso con preguntas completas y crea tu primera sala.</p>
                <a href="{{ route('admin.salas.create') }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Crear sala</a>
            </section>
        @else
            <section aria-label="Tus salas" style="display: grid; gap: 16px; margin-top: 32px;">
                @foreach ($rooms as $room)
                    <article class="qa-card" style="text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap;">
                            <div>
                                <h2 style="font-size: 20px; font-weight: 800;">{{ $room->quiz->title }}</h2>
                                <p class="qa-dashboard-meta" style="margin-top: 8px;">
                                    Código <strong style="color: #fff; letter-spacing: 2px;">{{ $room->code }}</strong>
                                    · {{ $room->players_count }} {{ $room->players_count === 1 ? 'jugador' : 'jugadores' }}
                                </p>
                            </div>
                            <div class="qa-quiz-actions">
                                <span class="qa-status qa-status-{{ $room->status === \App\Models\GameRoom::STATUS_WAITING ? 'active' : 'draft' }}">
                                    {{ $room->status === \App\Models\GameRoom::STATUS_WAITING ? 'En espera' : ucfirst($room->status) }}
                                </span>
                                <a href="{{ route('admin.salas.show', $room) }}" class="qa-btn qa-btn-secondary">Ver sala</a>
                                @if ($room->status === \App\Models\GameRoom::STATUS_WAITING)
                                    <form method="POST" action="{{ route('admin.salas.destroy', $room) }}" onsubmit="return confirm('¿Eliminar esta sala? También se quitarán los jugadores que esperan en ella.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="qa-btn qa-btn-danger">Eliminar sala</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div style="margin-top: 24px;">
                {{ $rooms->links() }}
            </div>
        @endif
    </main>
@endsection
