@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Resultado '.$gameRoom->code.' — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Navegación de resultado">
            <a href="{{ route('admin.resultados') }}" class="qa-menu-link">Resultados</a>
            <a href="{{ route('admin.partidas') }}" class="qa-menu-link">Partidas</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Partida finalizada</div>
        <h1 class="qa-title" style="font-size: clamp(30px, 6vw, 48px);">{{ $gameRoom->quiz->title }}</h1>
        <p class="qa-desc">
            Sala {{ $gameRoom->code }}
            @if ($gameRoom->finished_at)
                · Finalizada {{ $gameRoom->finished_at->format('d/m/Y H:i') }}
            @endif
            · Puntaje máximo {{ $maximumScore }}
        </p>

        <section class="qa-card" style="margin-top: 28px; text-align: left;">
            <h2 style="font-size: 24px;">Clasificación final</h2>
            @if ($gameRoom->players->isEmpty())
                <p class="qa-desc" style="margin-top: 12px;">La partida terminó sin jugadores.</p>
            @else
                <ol style="display: grid; gap: 10px; list-style: none; margin-top: 18px;">
                    @foreach ($gameRoom->players as $index => $player)
                        <li style="display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;">
                            <div>
                                <strong>{{ $index + 1 }}. {{ $player->nickname }}</strong>
                                <p class="qa-dashboard-meta" style="margin-top: 5px;">
                                    {{ $player->correct_answers_count }} {{ $player->correct_answers_count === 1 ? 'respuesta correcta' : 'respuestas correctas' }}
                                </p>
                            </div>
                            <strong>{{ $player->score }} puntos</strong>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        @if ($gameRoom->questionSnapshots->isNotEmpty())
            <section style="display: grid; gap: 18px; margin-top: 28px;" aria-label="Detalle de respuestas">
                <h2 style="font-size: 24px;">Detalle por pregunta</h2>
                @foreach ($gameRoom->questionSnapshots as $question)
                    @php
                        $correctOption = collect($question->options)->firstWhere('is_correct', true);
                    @endphp
                    <article class="qa-card" style="text-align: left;">
                        <h3 style="font-size: 18px;">Pregunta {{ $question->position }} · {{ $question->points }} puntos</h3>
                        <p style="line-height: 1.6; margin-top: 10px;">{{ $question->prompt }}</p>
                        @if ($correctOption)
                            <p class="qa-dashboard-meta" style="margin-top: 10px;">Respuesta correcta: {{ $correctOption['label'] }}. {{ $correctOption['text'] }}</p>
                        @endif

                        <div style="display: grid; gap: 10px; margin-top: 16px;">
                            @foreach ($gameRoom->players as $player)
                                @php
                                    $answer = $question->answers->firstWhere('player_id', $player->id);
                                @endphp
                                <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 12px; border-radius: 10px; background: rgba(5,6,15,0.55);">
                                    <span>
                                        <strong>{{ $player->nickname }}:</strong>
                                        @if ($answer)
                                            {{ $answer->selected_option_label }}. {{ $answer->selected_option_text }}
                                            <span class="{{ $answer->is_correct ? 'qa-status qa-status-active' : 'qa-status qa-status-draft' }}">
                                                {{ $answer->is_correct ? 'Correcta' : 'Incorrecta' }}
                                            </span>
                                        @else
                                            <span class="qa-dashboard-meta">Sin respuesta</span>
                                        @endif
                                    </span>
                                    <span class="qa-dashboard-meta">
                                        {{ $answer?->points_earned ?? 0 }} puntos
                                        @if ($answer)
                                            · {{ number_format($answer->response_time_ms / 1000, 2) }} s
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        <a href="{{ route('admin.resultados') }}" class="qa-btn qa-btn-secondary" style="margin-top: 24px;">Volver al historial</a>
    </main>
@endsection
