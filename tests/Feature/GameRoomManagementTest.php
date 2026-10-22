<?php

namespace Tests\Feature;

use App\Events\RoomCreated;
use App\Models\GameRoom;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_room_list_and_create_form_with_only_owned_active_quizzes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $ownedQuiz = $this->createActiveQuiz($admin, 'Mi concurso');
        $otherQuiz = $this->createActiveQuiz($otherAdmin, 'Concurso ajeno');
        Quiz::factory()->for($admin, 'admin')->create(['title' => 'Concurso en borrador']);

        $this->actingAs($admin)
            ->get(route('admin.salas'))
            ->assertOk()
            ->assertSee('Todavía no tienes salas');

        $this->get(route('admin.salas.create'))
            ->assertOk()
            ->assertSee('Mi concurso')
            ->assertDontSee('Concurso ajeno')
            ->assertDontSee('Concurso en borrador')
            ->assertSee(route('admin.salas.store'));

        $this->assertSame(Quiz::STATUS_ACTIVE, $ownedQuiz->status);
    }

    public function test_admin_can_create_a_waiting_room_with_a_unique_code_and_broadcast_event(): void
    {
        Event::fake([RoomCreated::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);

        $this->actingAs($admin)
            ->post(route('admin.salas.store'), [
                'quiz_id' => $quiz->id,
                'host_id' => $otherAdmin->id,
                'status' => GameRoom::STATUS_FINISHED,
            ])
            ->assertRedirect(route('admin.salas.show', GameRoom::query()->sole()))
            ->assertSessionHas('ok', 'Sala creada correctamente.');

        $room = GameRoom::query()->sole();

        $this->assertSame($admin->id, $room->host_id);
        $this->assertSame($quiz->id, $room->quiz_id);
        $this->assertSame(GameRoom::STATUS_WAITING, $room->status);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $room->code);

        $this->get(route('admin.salas.show', $room))
            ->assertOk()
            ->assertSee($quiz->title)
            ->assertSee($room->code)
            ->assertSee('Sala en espera')
            ->assertSee('Listos para jugar')
            ->assertSee('Los jugadores nuevos aparecerán automáticamente.');

        Event::assertDispatched(RoomCreated::class, fn (RoomCreated $event): bool => $event->gameRoom->is($room));
    }

    public function test_room_codes_are_unique_and_room_list_is_limited_to_the_host(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $otherQuiz = $this->createActiveQuiz($otherAdmin);

        $firstRoom = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => GameRoom::generateUniqueCode(),
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $secondRoom = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => GameRoom::generateUniqueCode(),
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $foreignRoom = $otherAdmin->hostedRooms()->create([
            'quiz_id' => $otherQuiz->id,
            'code' => GameRoom::generateUniqueCode(),
            'status' => GameRoom::STATUS_WAITING,
        ]);

        $this->assertNotSame($firstRoom->code, $secondRoom->code);

        $this->actingAs($admin)
            ->get(route('admin.salas'))
            ->assertOk()
            ->assertSee($firstRoom->code)
            ->assertSee($secondRoom->code)
            ->assertDontSee($foreignRoom->code);

        $this->get(route('admin.salas.show', $foreignRoom))
            ->assertForbidden();
    }

    public function test_room_creation_rejects_missing_inactive_foreign_and_incomplete_quizzes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $draftQuiz = Quiz::factory()->for($admin, 'admin')->create();
        $foreignQuiz = $this->createActiveQuiz($otherAdmin);
        $incompleteQuiz = Quiz::factory()->for($admin, 'admin')->create([
            'status' => Quiz::STATUS_ACTIVE,
        ]);

        foreach ([[], ['quiz_id' => $draftQuiz->id], ['quiz_id' => $foreignQuiz->id], ['quiz_id' => $incompleteQuiz->id]] as $payload) {
            $this->actingAs($admin)
                ->from(route('admin.salas.create'))
                ->post(route('admin.salas.store'), $payload)
                ->assertRedirect(route('admin.salas.create'))
                ->assertSessionHasErrors('quiz_id');
        }

        $this->assertDatabaseCount('game_rooms', 0);
    }

    public function test_admin_can_delete_a_waiting_room_and_its_lobby_players(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $room = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'DEL123',
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $player = $room->players()->create(['nickname' => 'Luna']);

        $this->actingAs($admin)
            ->delete(route('admin.salas.destroy', $room))
            ->assertRedirect(route('admin.salas'))
            ->assertSessionHas('ok', 'Sala eliminada correctamente.');

        $this->assertDatabaseMissing('game_rooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    public function test_admin_cannot_delete_rooms_that_are_not_waiting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $playingRoom = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'PLAY12',
            'status' => GameRoom::STATUS_PLAYING,
        ]);
        $finishedRoom = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'DONE12',
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);

        foreach ([$playingRoom, $finishedRoom] as $room) {
            $this->actingAs($admin)
                ->from(route('admin.salas'))
                ->delete(route('admin.salas.destroy', $room))
                ->assertRedirect(route('admin.salas'))
                ->assertSessionHas('error', 'Solo se pueden eliminar salas en espera sin respuestas registradas. Las partidas iniciadas conservan su historial.');

            $this->assertModelExists($room);
        }
    }

    public function test_admin_cannot_delete_a_waiting_room_that_has_answer_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $room = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'ANS123',
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $player = $room->players()->create(['nickname' => 'Luna']);
        $question = $quiz->questions()->firstOrFail();
        $option = $question->options()->firstOrFail();
        $player->answers()->create([
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'is_correct' => false,
            'response_time_ms' => 100,
            'points_earned' => 0,
            'answered_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.salas'))
            ->delete(route('admin.salas.destroy', $room))
            ->assertRedirect(route('admin.salas'))
            ->assertSessionHas('error');

        $this->assertModelExists($room);
        $this->assertModelExists($player);
    }

    public function test_only_the_room_host_can_delete_a_waiting_room(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $room = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'OWN123',
            'status' => GameRoom::STATUS_WAITING,
        ]);

        $this->actingAs($otherAdmin)
            ->delete(route('admin.salas.destroy', $room))
            ->assertForbidden();

        $this->assertModelExists($room);
    }

    public function test_room_list_offers_deletion_only_for_waiting_rooms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $waitingRoom = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'WAIT12',
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $playingRoom = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => 'PLAY12',
            'status' => GameRoom::STATUS_PLAYING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.salas'))
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('admin.salas.destroy', $waitingRoom).'"', false)
            ->assertDontSee('<form method="POST" action="'.route('admin.salas.destroy', $playingRoom).'"', false);
    }

    public function test_guests_and_non_admins_cannot_create_rooms(): void
    {
        $this->get(route('admin.salas.create'))
            ->assertRedirect(route('login'));

        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('admin.salas.create'))
            ->assertForbidden();
    }

    public function test_only_the_room_host_can_authorize_the_private_room_channel(): void
    {
        config([
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        Broadcast::setDefaultDriver('reverb');
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = $this->createActiveQuiz($admin);
        $room = $admin->hostedRooms()->create([
            'quiz_id' => $quiz->id,
            'code' => GameRoom::generateUniqueCode(),
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $channel = 'private-game-room.'.$room->id;
        $payload = [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ];

        $this->actingAs($admin)
            ->postJson('/broadcasting/auth', $payload)
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->actingAs($otherAdmin)
            ->postJson('/broadcasting/auth', $payload)
            ->assertForbidden();
    }

    private function createActiveQuiz(User $admin, string $title = 'Concurso listo'): Quiz
    {
        $quiz = Quiz::factory()->for($admin, 'admin')->create([
            'title' => $title,
            'status' => Quiz::STATUS_ACTIVE,
        ]);
        $question = Question::factory()->for($quiz)->create();

        $question->options()->createMany([
            ['label' => 'A', 'text' => 'Opción A', 'position' => 1, 'is_correct' => true],
            ['label' => 'B', 'text' => 'Opción B', 'position' => 2, 'is_correct' => false],
            ['label' => 'C', 'text' => 'Opción C', 'position' => 3, 'is_correct' => false],
            ['label' => 'D', 'text' => 'Opción D', 'position' => 4, 'is_correct' => false],
        ]);

        return $quiz;
    }
}
