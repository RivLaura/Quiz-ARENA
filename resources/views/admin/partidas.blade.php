@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Partidas — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Navegación de partidas">
            <a href="{{ route('admin') }}" class="qa-menu-link">Panel</a>
            <a href="{{ route('admin.salas') }}" class="qa-menu-link">Salas</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Administración</div>
        <h1 class="qa-title" style="font-size: clamp(32px, 6vw, 54px);">PARTIDAS</h1>
        <p class="qa-desc">Inicia las salas que tengan jugadores y consulta partidas activas o finalizadas.</p>

        @if ($rooms->isEmpty())
            <section class="qa-card" style="margin-top: 32px;">
                <h2 style="font-size: 20px;">Todavía no hay partidas</h2>
                <p class="qa-desc" style="margin-top: 10px;">Crea una sala y espera a que se una al menos un jugador.</p>
                <a href="{{ route('admin.salas') }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Ir a salas</a>
            </section>
        @else
            <section aria-label="Tus partidas" style="display: grid; gap: 16px; margin-top: 32px;">
                @foreach ($rooms as $room)
                    <article class="qa-card" style="text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap;">
                            <div>
                                <h2 style="font-size: 20px; font-weight: 800;">{{ $room->quiz->title }}</h2>
                                <p class="qa-dashboard-meta" style="margin-top: 8px;">
                                    Sala {{ $room->code }} · {{ $room->players_count }} {{ $room->players_count === 1 ? 'jugador' : 'jugadores' }}
                                    @if ($room->started_at)
                                        · Iniciada {{ $room->started_at->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </div>
                            <div class="qa-quiz-actions">
                                <span class="qa-status qa-status-{{ $room->status === \App\Models\GameRoom::STATUS_PLAYING ? 'active' : 'draft' }}">
                                    {{ match ($room->status) {
                                        \App\Models\GameRoom::STATUS_WAITING => 'En espera',
                                        \App\Models\GameRoom::STATUS_PLAYING => 'En curso',
                                        \App\Models\GameRoom::STATUS_FINISHED => 'Finalizada',
                                        default => ucfirst($room->status),
                                    } }}
                                </span>
                                <a href="{{ route('admin.salas.show', $room) }}" class="qa-btn qa-btn-secondary">Abrir partida</a>
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
