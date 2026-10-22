@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Sala '.$room->code.' — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Navegación de sala">
            <a href="{{ route('admin.salas') }}" class="qa-menu-link">Salas</a>
            <a href="{{ route('admin.partidas') }}" class="qa-menu-link">Partidas</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">
            {{ match ($room->status) {
                \App\Models\GameRoom::STATUS_WAITING => 'Sala en espera',
                \App\Models\GameRoom::STATUS_PLAYING => 'Partida en curso',
                \App\Models\GameRoom::STATUS_FINISHED => 'Partida finalizada',
                default => ucfirst($room->status),
            } }}
        </div>
        <h1 class="qa-title" style="font-size: clamp(32px, 7vw, 54px);">{{ $room->quiz->title }}</h1>
        <p class="qa-desc">Código de sala: <strong style="color: #fff; letter-spacing: 3px;">{{ $room->code }}</strong></p>

        <section class="qa-card" style="margin-top: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div>
                    <h2 style="font-size: 20px;">Jugadores ({{ $room->players->count() }})</h2>
                    <p class="qa-dashboard-meta" style="margin-top: 8px;">Los jugadores nuevos aparecerán automáticamente.</p>
                </div>
                @if ($room->status === \App\Models\GameRoom::STATUS_WAITING)
                    <form method="POST" action="{{ route('admin.salas.start', $room) }}">
                        @csrf
                        <button type="submit" class="qa-btn qa-btn-primary" @disabled($room->players->isEmpty())>Iniciar partida</button>
                    </form>
                @elseif ($room->status === \App\Models\GameRoom::STATUS_PLAYING)
                    <form id="advance-game-form" method="POST" action="{{ route('admin.salas.advance', $room) }}">
                        @csrf
                        <input type="hidden" name="question_position" value="{{ $gameState['question']['position'] }}">
                        <button type="submit" name="automatic" value="0" class="qa-btn qa-btn-primary">
                            {{ $gameState['question']['position'] === $gameState['question']['total'] ? 'Finalizar partida' : 'Siguiente pregunta' }}
                        </button>
                    </form>
                @endif
            </div>

            <ul id="room-player-list" aria-live="polite" style="display: grid; gap: 10px; list-style: none; margin-top: 18px;">
                @forelse ($room->players as $player)
                    <li data-player-id="{{ $player->id }}" style="padding: 12px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;">{{ $player->nickname }}</li>
                @empty
                    <li id="room-player-empty" style="color: #94a3b8;">Todavía no se ha unido ningún jugador.</li>
                @endforelse
            </ul>
        </section>

        @if ($room->status === \App\Models\GameRoom::STATUS_WAITING)
            <section class="qa-card" style="margin-top: 20px;">
                <h2 style="font-size: 20px;">Listos para jugar</h2>
                <p class="qa-desc">Cuando haya al menos un jugador, inicia la partida. Las preguntas se presentarán en el orden del concurso.</p>
            </section>
        @elseif ($room->status === \App\Models\GameRoom::STATUS_PLAYING && $gameState['question'])
            <section class="qa-card" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <h2 style="font-size: 20px;">Pregunta {{ $gameState['question']['position'] }} de {{ $gameState['question']['total'] }}</h2>
                    <span class="qa-status qa-status-active">Tiempo restante: <span id="game-countdown">--:--</span></span>
                </div>
                <p id="auto-advance-status" class="qa-dashboard-meta" aria-live="polite" hidden></p>
                <p style="font-size: 22px; font-weight: 800; line-height: 1.5; margin-top: 18px;">{{ $gameState['question']['prompt'] }}</p>
                <ol class="qa-option-list" style="list-style: none;">
                    @foreach ($gameState['question']['options'] as $option)
                        <li>
                            <span class="qa-option-label">{{ $option['label'] }}</span>
                            <span>{{ $option['text'] }}</span>
                        </li>
                    @endforeach
                </ol>
                <p class="qa-dashboard-meta" style="margin-top: 18px;">{{ $gameState['question']['points'] }} puntos · El jugador recibe puntos solo por una respuesta correcta. Avanza manualmente cuando quieras pasar a la siguiente pregunta.</p>
            </section>
        @else
            <section class="qa-card" style="margin-top: 20px;">
                <h2 style="font-size: 24px;">Resultados finales</h2>
                @if (empty($gameState['leaderboard']))
                    <p class="qa-desc">La partida terminó sin jugadores.</p>
                @else
                    <ol style="display: grid; gap: 10px; list-style: none; margin-top: 20px;">
                        @foreach ($gameState['leaderboard'] as $index => $entry)
                            <li style="display: flex; justify-content: space-between; gap: 16px; padding: 14px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;">
                                <strong>{{ $index + 1 }}. {{ $entry['nickname'] }}</strong>
                                <span>{{ $entry['score'] }} puntos</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endif
    </main>

    <script>
        const updatePlayerList = (event) => {
            const list = document.getElementById('room-player-list');
            const playerId = String(event.player.id);

            if (list.querySelector(`[data-player-id="${playerId}"]`)) {
                return;
            }

            document.getElementById('room-player-empty')?.remove();

            const item = document.createElement('li');
            item.dataset.playerId = playerId;
            item.textContent = event.player.nickname;
            item.style.cssText = 'padding: 12px; border-radius: 10px; background: rgba(5,6,15,0.55); color: #e2e8f0;';
            list.append(item);

            list.previousElementSibling.querySelector('h2').textContent = `Jugadores (${event.players_count})`;
        };

        if (window.Echo) {
            window.Echo.private(@js('game-room.'.$room->id))
                .subscribed(() => {
                    const status = document.getElementById('room-channel-status');
                    if (status) {
                        status.textContent = 'Conectado en tiempo real';
                    }
                })
                .listen('.PlayerJoined', updatePlayerList);

            window.Echo.channel(@js('game-play.'.$room->channel_token))
                .listen('.GameStateChanged', () => window.location.reload());
        }

        @if ($room->status === \App\Models\GameRoom::STATUS_PLAYING && $gameState['question'])
            const deadline = Date.parse(@js($gameState['question']['started_at']))
                + {{ $gameState['question']['time_limit_seconds'] }} * 1000;
            const countdown = document.getElementById('game-countdown');
            const advanceForm = document.getElementById('advance-game-form');
            const advanceStatus = document.getElementById('auto-advance-status');
            let automaticAdvanceInProgress = false;
            let automaticAdvanceFailed = false;
            const advanceAutomatically = async () => {
                if (automaticAdvanceInProgress || automaticAdvanceFailed) {
                    return;
                }

                automaticAdvanceInProgress = true;
                const payload = new FormData(advanceForm);
                payload.set('automatic', '1');

                try {
                    const response = await fetch(advanceForm.action, {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`No se pudo avanzar la partida (${response.status}).`);
                    }

                    const result = await response.json();

                    if (result.advanced) {
                        window.location.reload();
                        return;
                    }

                    automaticAdvanceInProgress = false;
                } catch (error) {
                    automaticAdvanceFailed = true;
                    advanceStatus.hidden = false;
                    advanceStatus.textContent = 'No se pudo avanzar automáticamente. Comprueba la conexión o usa el botón para continuar.';
                    console.error(error);
                }
            };
            const updateCountdown = () => {
                const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                countdown.textContent = `${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`;

                if (remaining === 0) {
                    advanceAutomatically();
                }
            };
            updateCountdown();
            window.setInterval(updateCountdown, 250);
        @endif
    </script>
@endsection
