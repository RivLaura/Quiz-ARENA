<?php

namespace Tests\Feature;

use App\Events\GameStateChanged;
use App\Models\GameRoom;
use App\Models\PlayerAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_can_start_a_room_and_broadcast_the_first_question_without_its_correct_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create([
            'time_per_question' => 20,
        ]);
        $this->createQuestion($quiz, 1, 10);
        $this->createQuestion($quiz, 2);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);
        $this->freezeTime();

        $this->actingAs($admin)
            ->post(route('admin.salas.start', $room))
            ->assertRedirect(route('admin.salas.show', $room))
            ->assertSessionHas('ok', 'La partida comenzó.');

        $room->refresh();

        $this->assertSame(GameRoom::STATUS_PLAYING, $room->status);
        $this->assertSame(1, $room->current_question_position);
        $this->assertNotNull($room->started_at);
        $this->assertSame($room->started_at->toDateTimeString(), $room->current_question_started_at->toDateTimeString());
        $this->assertNotNull($room->channel_token);

        Event::assertDispatched(GameStateChanged::class, function (GameStateChanged $event) use ($quiz, $room): bool {
            $question = $event->broadcastWith()['question'];
            $firstSnapshot = $room->questionSnapshots()->where('position', 1)->firstOrFail();

            return $event->gameRoom->is($room)
                && $event->broadcastOn()[0]->name === 'game-play.'.$room->channel_token
                && $question['id'] === $firstSnapshot->question_id
                && $question['snapshot_id'] === $firstSnapshot->id
                && $question['time_limit_seconds'] === ($firstSnapshot->time_limit_seconds ?? $quiz->time_per_question)
                && count($question['options']) === 4
                && ! array_key_exists('is_correct', $question['options'][0]);
        });
    }

    public function test_match_snapshots_store_a_shared_shuffled_order_for_all_players(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $questions = collect(range(1, 5))
            ->map(fn (int $position): Question => $this->createQuestion($quiz, $position));
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $room->players()->createMany([
            ['nickname' => 'Luna'],
            ['nickname' => 'Sol'],
        ]);
        Event::fake([GameStateChanged::class]);

        $this->actingAs($admin)->post(route('admin.salas.start', $room));

        $snapshots = $room->questionSnapshots()->get();
        $this->assertSame(range(1, 5), $snapshots->pluck('position')->all());
        $this->assertEqualsCanonicalizing(
            $questions->pluck('id')->all(),
            $snapshots->pluck('question_id')->all(),
        );
        $this->assertSame(
            $snapshots->pluck('question_id')->all(),
            $room->fresh()->questionSnapshots()->pluck('question_id')->all(),
        );
    }

    public function test_host_cannot_start_a_room_without_players(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $this->createQuestion($quiz, 1);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        Event::fake([GameStateChanged::class]);

        $this->actingAs($admin)
            ->from(route('admin.salas.show', $room))
            ->post(route('admin.salas.start', $room))
            ->assertRedirect(route('admin.salas.show', $room))
            ->assertSessionHasErrors([
                'game' => 'Debe unirse al menos un jugador antes de iniciar la partida.',
            ]);

        $this->assertSame(GameRoom::STATUS_WAITING, $room->fresh()->status);
        Event::assertNotDispatched(GameStateChanged::class);
    }

    public function test_an_admin_cannot_start_or_advance_another_hosts_room(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $intruder = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($owner, 'admin')->create();
        $this->createQuestion($quiz, 1);
        $room = GameRoom::factory()->for($quiz)->for($owner, 'host')->create();
        Event::fake([GameStateChanged::class]);

        $this->actingAs($intruder)
            ->post(route('admin.salas.start', $room))
            ->assertForbidden();

        Event::assertNotDispatched(GameStateChanged::class);
    }

    public function test_player_can_answer_once_and_only_correct_answers_award_question_points(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz, 1, null, 350);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $player = $room->players()->create(['nickname' => 'Luna']);
        $incorrectPlayer = $room->players()->create(['nickname' => 'Sol']);
        Event::fake([GameStateChanged::class]);
        $this->freezeTime();

        $this->actingAs($admin)->post(route('admin.salas.start', $room));
        $correctOption = $question->options()->where('is_correct', true)->firstOrFail();
        $incorrectOption = $question->options()->where('is_correct', false)->firstOrFail();

        $this->withSession(['joined_players' => [$player->id => true]])
            ->post(route('join.answer', $player), ['option_id' => $correctOption->id])
            ->assertRedirect(route('join.lobby', $player))
            ->assertSessionHas('ok', 'Respuesta registrada.');

        $this->assertDatabaseHas('player_answers', [
            'player_id' => $player->id,
            'question_id' => $question->id,
            'question_option_id' => $correctOption->id,
            'is_correct' => true,
            'points_earned' => 350,
        ]);
        $this->assertSame(350, $player->fresh()->score);
        $answer = PlayerAnswer::query()
            ->where('player_id', $player->id)
            ->where('question_id', $question->id)
            ->firstOrFail();
        $this->assertGreaterThanOrEqual(0, $answer->response_time_ms);
        $this->assertLessThanOrEqual(20_000, $answer->response_time_ms);

        $this->withSession(['joined_players' => [$player->id => true]])
            ->from(route('join.lobby', $player))
            ->post(route('join.answer', $player), ['option_id' => $correctOption->id])
            ->assertRedirect(route('join.lobby', $player))
            ->assertSessionHasErrors([
                'answer' => 'Ya respondiste esta pregunta.',
            ]);

        $this->withSession(['joined_players' => [$incorrectPlayer->id => true]])
            ->post(route('join.answer', $incorrectPlayer), ['option_id' => $incorrectOption->id])
            ->assertRedirect(route('join.lobby', $incorrectPlayer));

        $this->assertDatabaseHas('player_answers', [
            'player_id' => $incorrectPlayer->id,
            'question_id' => $question->id,
            'is_correct' => false,
            'points_earned' => 0,
        ]);
        $this->assertSame(0, $incorrectPlayer->fresh()->score);
    }

    public function test_player_cannot_answer_with_an_option_from_another_question(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $firstQuestion = $this->createQuestion($quiz, 1);
        $secondQuestion = $this->createQuestion($quiz, 2);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $player = $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);

        $this->actingAs($admin)->post(route('admin.salas.start', $room));
        $activeSnapshot = $room->questionSnapshots()->where('position', 1)->firstOrFail();
        $otherSnapshot = $room->questionSnapshots()->where('position', 2)->firstOrFail();
        $otherQuestionOptionId = collect($otherSnapshot->options)->first()['id'];

        $this->withSession(['joined_players' => [$player->id => true]])
            ->from(route('join.lobby', $player))
            ->post(route('join.answer', $player), [
                'option_id' => $otherQuestionOptionId,
            ])
            ->assertRedirect(route('join.lobby', $player))
            ->assertSessionHasErrors([
                'option_id' => 'Selecciona una opción válida para esta pregunta.',
            ]);

        $this->assertDatabaseMissing('player_answers', [
            'player_id' => $player->id,
            'game_room_question_id' => $activeSnapshot->id,
        ]);
    }

    public function test_player_cannot_answer_after_the_question_time_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz, 1, 5);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $player = $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);
        $this->freezeTime();

        $this->actingAs($admin)->post(route('admin.salas.start', $room));
        $activeQuestionOptionId = collect(
            $room->questionSnapshots()->where('position', 1)->firstOrFail()->options,
        )->first(fn (array $option): bool => $option['is_correct'])['id'];
        $this->travel(5)->seconds();

        $this->withSession(['joined_players' => [$player->id => true]])
            ->from(route('join.lobby', $player))
            ->post(route('join.answer', $player), [
                'option_id' => $activeQuestionOptionId,
            ])
            ->assertRedirect(route('join.lobby', $player))
            ->assertSessionHasErrors([
                'answer' => 'Se terminó el tiempo para responder esta pregunta.',
            ]);

        $this->assertDatabaseCount('player_answers', 0);
    }

    public function test_host_can_advance_questions_and_finish_the_match_with_a_leaderboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $firstQuestion = $this->createQuestion($quiz, 1);
        $secondQuestion = $this->createQuestion($quiz, 2);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $player = $room->players()->create(['nickname' => 'Luna', 'score' => 250]);
        Event::fake([GameStateChanged::class]);

        $this->actingAs($admin)->post(route('admin.salas.start', $room));

        $this->actingAs($admin)
            ->post(route('admin.salas.advance', $room), [
                'question_position' => 1,
                'automatic' => false,
            ])
            ->assertRedirect(route('admin.salas.show', $room))
            ->assertSessionHas('ok', 'Se avanzó a la siguiente pregunta.');

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_PLAYING, $room->status);
        $this->assertSame(2, $room->current_question_position);
        $questionIds = $room->questionSnapshots()->pluck('question_id')->all();
        sort($questionIds);
        $expectedQuestionIds = [$firstQuestion->id, $secondQuestion->id];
        sort($expectedQuestionIds);
        $this->assertSame($expectedQuestionIds, $questionIds);
        $this->assertNotNull($room->current_question_started_at);

        $this->actingAs($admin)
            ->post(route('admin.salas.advance', $room), [
                'question_position' => 2,
                'automatic' => false,
            ])
            ->assertRedirect(route('admin.salas.show', $room))
            ->assertSessionHas('ok', 'La partida terminó.');

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_FINISHED, $room->status);
        $this->assertNotNull($room->finished_at);
        $this->assertNull($room->current_question_started_at);

        $this->actingAs($admin)
            ->get(route('admin.salas.show', $room))
            ->assertOk()
            ->assertSee('Resultados finales')
            ->assertSee('Luna')
            ->assertSee('250 puntos');

        $this->withSession(['joined_players' => [$player->id => true]])
            ->get(route('join.lobby', $player))
            ->assertOk()
            ->assertSee('Partida finalizada')
            ->assertSee('250 puntos');

        Event::assertDispatched(GameStateChanged::class, function (GameStateChanged $event) use ($room): bool {
            $state = $event->broadcastWith();

            return $event->gameRoom->is($room)
                && $state['status'] === GameRoom::STATUS_FINISHED
                && $state['leaderboard'] === [['nickname' => 'Luna', 'score' => 250]];
        });
    }

    public function test_question_timeout_advances_the_match_and_automatically_finishes_after_the_last_question(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['time_per_question' => 20]);
        $this->createQuestion($quiz, 1, 5);
        $this->createQuestion($quiz, 2, 5);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);
        $this->freezeTime();

        $this->actingAs($admin)->post(route('admin.salas.start', $room));

        $this->postJson(route('admin.salas.advance', $room), [
            'question_position' => 1,
            'automatic' => true,
        ])->assertOk()->assertExactJson(['advanced' => false]);

        $this->travel(5)->seconds();

        $this->postJson(route('admin.salas.advance', $room), [
            'question_position' => 1,
            'automatic' => true,
        ])->assertOk()->assertExactJson(['advanced' => true]);

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_PLAYING, $room->status);
        $this->assertSame(2, $room->current_question_position);

        $this->postJson(route('admin.salas.advance', $room), [
            'question_position' => 1,
            'automatic' => true,
        ])->assertOk()->assertExactJson(['advanced' => false]);

        $this->travel(5)->seconds();

        $this->postJson(route('admin.salas.advance', $room), [
            'question_position' => 2,
            'automatic' => true,
        ])->assertOk()->assertExactJson(['advanced' => true]);

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_FINISHED, $room->status);
        $this->assertNotNull($room->finished_at);

        Event::assertDispatched(GameStateChanged::class, 3);
    }

    public function test_player_lobby_advances_expired_questions_without_the_admin_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['time_per_question' => 5]);
        $this->createQuestion($quiz, 1);
        $this->createQuestion($quiz, 2);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $player = $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);
        $this->freezeTime();

        $this->actingAs($admin)->post(route('admin.salas.start', $room));

        $this->withSession(['joined_players' => [$player->id => true]])
            ->getJson(route('join.state', $player))
            ->assertOk()
            ->assertJsonPath('status', GameRoom::STATUS_PLAYING)
            ->assertJsonPath('question.position', 1);

        $this->withSession(['joined_players' => [$player->id => true]])
            ->postJson(route('join.advance', $player), ['question_position' => 1])
            ->assertOk()
            ->assertJsonPath('advanced', false);

        $this->travel(5)->seconds();

        $this->withSession(['joined_players' => [$player->id => true]])
            ->postJson(route('join.advance', $player), ['question_position' => 1])
            ->assertOk()
            ->assertJsonPath('advanced', true)
            ->assertJsonPath('state.question.position', 2);

        $this->travel(5)->seconds();

        $this->withSession(['joined_players' => [$player->id => true]])
            ->postJson(route('join.advance', $player), ['question_position' => 2])
            ->assertOk()
            ->assertJsonPath('advanced', true)
            ->assertJsonPath('state.status', GameRoom::STATUS_FINISHED);

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_FINISHED, $room->status);
        $this->assertNotNull($room->finished_at);
        Event::assertDispatched(GameStateChanged::class, 3);
    }

    public function test_only_the_player_whose_session_joined_can_submit_an_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz, 1);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $player = $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);
        $this->actingAs($admin)->post(route('admin.salas.start', $room));

        $this->post(route('join.answer', $player), [
            'option_id' => $question->options()->firstOrFail()->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('player_answers', 0);
    }

    private function createQuestion(Quiz $quiz, int $position, ?int $timeLimit = null, int $points = 100): Question
    {
        $question = $quiz->questions()->create([
            'prompt' => 'Pregunta '.$position,
            'position' => $position,
            'points' => $points,
            'time_limit_seconds' => $timeLimit,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            $question->options()->create([
                'label' => $label,
                'text' => 'Opción '.$label.' de la pregunta '.$position,
                'position' => $index + 1,
                'is_correct' => $label === 'A',
            ]);
        }

        return $question;
    }
}
