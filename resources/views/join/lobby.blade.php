@extends('layouts.arena')

@section('title', 'Sala de espera — QUIZ ARENA')

@section('content')
    <main style="max-width: 760px; margin: 0 auto; text-align: center;">
        @if ($gameState['status'] === \App\Models\GameRoom::STATUS_FINISHED)
            <div class="qa-badge">Partida finalizada</div>
        @elseif ($gameState['status'] === \App\Models\GameRoom::STATUS_PLAYING)
            <div class="qa-badge">Partida en curso</div>
        @else
            <div class="qa-badge">Te uniste a la sala</div>
        @endif
        <h1 class="qa-title" style="font-size: clamp(32px, 7vw, 54px);">{{ $player->gameRoom->quiz->title }}</h1>
        <p class="qa-desc">Hola, {{ $player->nickname }}.
            @if ($gameState['status'] === \App\Models\GameRoom::STATUS_WAITING)
                Ya estás dentro; espera a que el anfitrión inicie la partida.
            @elseif ($gameState['status'] === \App\Models\GameRoom::STATUS_PLAYING)
                Responde antes de que termine el tiempo.
            @else
                Aquí están los resultados finales.
            @endif
        </p>

        <section class="qa-card" style="margin-top: 32px;">
            <p class="qa-dashboard-meta">CÓDIGO DE SALA</p>
            <p style="font-size: clamp(40px, 12vw, 64px); font-weight: 900; letter-spacing: 10px; color: #fff; margin-top: 12px;" aria-label="Código de sala">{{ $player->gameRoom->code }}</p>
        </section>

        @if ($gameState['status'] === \App\Models\GameRoom::STATUS_WAITING)
            <section class="qa-card" style="margin-top: 20px; text-align: left;">
                <h2 style="font-size: 20px;">Jugadores en la sala</h2>
                <ul id="lobby-player-list" aria-live="polite" style="display: grid; gap: 10px; list-style: none; margin-top: 16px;">
                    @foreach ($player->gameRoom->players as $roomPlayer)
                        <li data-player-id="{{ $roomPlayer->id }}" style="padding: 12px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;">
                            {{ $roomPlayer->nickname }}{{ $roomPlayer->is($player) ? ' (tú)' : '' }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @elseif ($gameState['status'] === \App\Models\GameRoom::STATUS_PLAYING && $gameState['question'])
            <section class="qa-card" style="margin-top: 20px; text-align: left;">
                <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <h2 style="font-size: 20px;">Pregunta {{ $gameState['question']['position'] }} de {{ $gameState['question']['total'] }}</h2>
                    <span class="qa-status qa-status-active">Tiempo: <span id="player-countdown">--:--</span></span>
                </div>
                <p style="font-size: 22px; font-weight: 800; line-height: 1.5; margin-top: 18px;">{{ $gameState['question']['prompt'] }}</p>

                @if ($currentAnswer)
                    <div class="qa-alert qa-alert-ok" role="status" style="margin-top: 20px;">Respuesta registrada. Espera la siguiente pregunta.</div>
                @else
                    <form method="POST" action="{{ route('join.answer', $player) }}" style="display: grid; gap: 12px; margin-top: 20px;">
                        @csrf
                        @foreach ($gameState['question']['options'] as $option)
                            <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 10px; border: 1px solid rgba(139,92,246,0.25); background: rgba(5,6,15,0.55); color: #e2e8f0; cursor: pointer;">
                                <input type="radio" name="option_id" value="{{ $option['id'] }}" required>
                                <span class="qa-option-label">{{ $option['label'] }}</span>
                                <span>{{ $option['text'] }}</span>
                            </label>
                        @endforeach
                        <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">Enviar respuesta</button>
                    </form>
                @endif
            </section>
        @else
            <section class="qa-card" style="margin-top: 20px; text-align: left;">
                <h2 style="font-size: 20px;">Resultados finales</h2>
                <ol style="display: grid; gap: 10px; list-style: none; margin-top: 16px;">
                    @foreach ($gameState['leaderboard'] as $index => $entry)
                        <li style="display: flex; justify-content: space-between; gap: 16px; padding: 14px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;">
                            <strong>{{ $index + 1 }}. {{ $entry['nickname'] }}</strong>
                            <span>{{ $entry['score'] }} puntos</span>
                        </li>
                    @endforeach
                </ol>
                <a class="qa-btn qa-btn-primary" href="{{ route('home') }}" style="display: block; margin-top: 20px; text-align: center;">Volver al inicio</a>
            </section>
        @endif

        @if ($gameState['status'] !== \App\Models\GameRoom::STATUS_FINISHED)
            <p id="game-connection-status" class="qa-dashboard-meta" role="status" aria-live="polite" style="margin-top: 20px;">Conectado al servidor de la partida.</p>
        @endif
        <p class="qa-dashboard-meta" style="margin-top: 8px;">Tu respuesta se guarda una sola vez por pregunta.</p>
    </main>

    <script>
        @if ($gameState['status'] !== \App\Models\GameRoom::STATUS_FINISHED)
            const initialGameStatus = @js($gameState['status']);
            const initialQuestionPosition = @js($gameState['question']['position'] ?? null);
            const connectionStatus = document.getElementById('game-connection-status');
            let stateRequestPending = false;

            const reloadIfGameStateChanged = (state) => {
                if (
                    state.status !== initialGameStatus
                    || (state.question?.position ?? null) !== initialQuestionPosition
                ) {
                    window.location.reload();
                }
            };

            const refreshGameState = async () => {
                if (stateRequestPending) {
                    return;
                }

                stateRequestPending = true;

                try {
                    const response = await fetch(@js(route('join.state', $player)), {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });

                    if (!response.ok) {
                        throw new Error(`No se pudo consultar el estado de la partida (${response.status}).`);
                    }

                    reloadIfGameStateChanged(await response.json());
                    connectionStatus.textContent = 'Conectado al servidor de la partida.';
                } catch (error) {
                    connectionStatus.textContent = 'No se pudo actualizar la partida. Reintentando…';
                    console.error(error);
                } finally {
                    stateRequestPending = false;
                }
            };

            window.setInterval(refreshGameState, 4000);

            @if ($gameState['status'] === \App\Models\GameRoom::STATUS_PLAYING && $gameState['question'])
                let advanceRequested = false;
                let nextAdvanceAttemptAt = 0;

                const advanceExpiredQuestion = async () => {
                    if (advanceRequested || Date.now() < nextAdvanceAttemptAt) {
                        return;
                    }

                    advanceRequested = true;
                    connectionStatus.textContent = 'Tiempo agotado. Cargando la siguiente pregunta…';

                    try {
                        const response = await fetch(@js(route('join.advance', $player)), {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @js(csrf_token()),
                            },
                            body: JSON.stringify({ question_position: initialQuestionPosition }),
                        });

                        if (!response.ok) {
                            throw new Error(`No se pudo avanzar la partida (${response.status}).`);
                        }

                        const result = await response.json();
                        reloadIfGameStateChanged(result.state);
                        advanceRequested = false;
                        nextAdvanceAttemptAt = Date.now() + (result.advanced ? 0 : 1000);
                    } catch (error) {
                        advanceRequested = false;
                        nextAdvanceAttemptAt = Date.now() + 3000;
                        connectionStatus.textContent = 'No se pudo actualizar la partida. Reintentando…';
                        console.error(error);
                    }
                };
            @endif
        @endif

        if (window.Echo) {
            const gameChannel = window.Echo.channel(@js('game-play.'.$player->gameRoom->channel_token));
            gameChannel.listen('.GameStateChanged', () => window.location.reload());

            @if ($gameState['status'] === \App\Models\GameRoom::STATUS_WAITING)
                gameChannel.listen('.PlayerJoined', (event) => {
                    const list = document.getElementById('lobby-player-list');
                    if (list.querySelector(`[data-player-id="${event.player.id}"]`)) {
                        return;
                    }

                    const item = document.createElement('li');
                    item.dataset.playerId = event.player.id;
                    item.textContent = event.player.nickname;
                    item.style.cssText = 'padding: 12px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;';
                    list.append(item);
                });
            @endif
        }

        @if ($gameState['status'] === \App\Models\GameRoom::STATUS_PLAYING && $gameState['question'])
            const deadline = Date.parse(@js($gameState['question']['started_at']))
                + {{ $gameState['question']['time_limit_seconds'] }} * 1000;
            const countdown = document.getElementById('player-countdown');
            const updateCountdown = () => {
                const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                countdown.textContent = `${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`;

                if (remaining === 0) {
                    document.querySelectorAll('input[name="option_id"], button[type="submit"]').forEach((input) => input.disabled = true);
                    advanceExpiredQuestion();
                }
            };
            updateCountdown();
            window.setInterval(updateCountdown, 250);
        @endif
    </script>
@endsection
