@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Resultados — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Navegación de resultados">
            <a href="{{ route('admin') }}" class="qa-menu-link">Panel</a>
            <a href="{{ route('admin.partidas') }}" class="qa-menu-link">Partidas</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Administración</div>
        <h1 class="qa-title" style="font-size: clamp(32px, 6vw, 54px);">RESULTADOS</h1>
        <p class="qa-desc">Historial de partidas finalizadas y clasificación de sus jugadores.</p>

        @if ($rooms->isEmpty())
            <section class="qa-card" style="margin-top: 32px;">
                <h2 style="font-size: 20px;">Todavía no hay resultados</h2>
                <p class="qa-desc" style="margin-top: 10px;">Las partidas aparecerán aquí cuando hayan finalizado.</p>
                <a href="{{ route('admin.partidas') }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Ir a partidas</a>
            </section>
        @else
            <section aria-label="Historial de resultados" style="display: grid; gap: 16px; margin-top: 32px;">
                @foreach ($rooms as $room)
                    <article class="qa-card" style="text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap;">
                            <div>
                                <h2 style="font-size: 20px; font-weight: 800;">{{ $room->quiz->title }}</h2>
                                <p class="qa-dashboard-meta" style="margin-top: 8px;">
                                    Sala {{ $room->code }} · {{ $room->players_count }} {{ $room->players_count === 1 ? 'jugador' : 'jugadores' }}
                                    @if ($room->finished_at)
                                        · Finalizada {{ $room->finished_at->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('admin.resultados.show', $room) }}" class="qa-btn qa-btn-secondary">Ver resultados</a>
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
