<?php

namespace Tests\Feature;

use App\Events\PlayerJoined;
use App\Models\GameRoom;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PlayerJoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_player_join_form(): void
    {
        $this->get(route('join'))
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee('name="nickname"', false)
            ->assertSee(route('join.store'));
    }

    public function test_player_can_join_a_waiting_room_and_view_the_lobby(): void
    {
        $room = GameRoom::factory()->create(['code' => 'ABC123']);
        $otherRoom = GameRoom::factory()->create();
        Event::fake([PlayerJoined::class]);

        $this->post(route('join.store'), [
            'code' => ' abc123 ',
            'nickname' => '  Luna  ',
            'game_room_id' => $otherRoom->id,
            'score' => 500,
        ])
            ->assertRedirect(route('join.lobby', Player::query()->sole()))
            ->assertSessionHas('joined_players.'.Player::query()->sole()->id, true);

        $player = Player::query()->sole();

        $this->assertSame($room->id, $player->game_room_id);
        $this->assertSame('Luna', $player->nickname);
        $this->assertSame(0, $player->score);

        $this->get(route('join.lobby', $player))
            ->assertOk()
            ->assertSee('Te uniste a la sala')
            ->assertSee($room->quiz->title)
            ->assertSee('Luna (tú)')
            ->assertSee('ABC123');

        $this->actingAs($room->host)
            ->get(route('admin.salas.show', $room))
            ->assertOk()
            ->assertSee('Luna');

        Event::assertDispatched(
            PlayerJoined::class,
            fn (PlayerJoined $event): bool => $event->player->is($player)
                && $event->broadcastOn()[0]->name === 'private-game-room.'.$room->id
                && $event->broadcastWith()['players_count'] === 1,
        );
    }

    public function test_player_state_and_advance_endpoints_require_a_joined_player_session(): void
    {
        $player = GameRoom::factory()->create()->players()->create(['nickname' => 'Luna']);

        $this->getJson(route('join.state', $player))
            ->assertNotFound();

        $this->postJson(route('join.advance', $player), ['question_position' => 1])
            ->assertNotFound();
    }

    public function test_player_can_return_to_home_after_the_match_finishes(): void
    {
        $room = GameRoom::factory()->create(['status' => GameRoom::STATUS_FINISHED]);
        $player = $room->players()->create(['nickname' => 'Luna']);

        $this->withSession(['joined_players' => [$player->id => true]])
            ->get(route('join.lobby', $player))
            ->assertOk()
            ->assertSee('Volver al inicio')
            ->assertSee(route('home'));
    }

    public function test_player_cannot_join_a_room_that_does_not_exist_or_is_not_waiting(): void
    {
        GameRoom::factory()->create([
            'code' => 'PLAY01',
            'status' => GameRoom::STATUS_PLAYING,
        ]);
        GameRoom::factory()->create([
            'code' => 'DONE01',
            'status' => GameRoom::STATUS_FINISHED,
        ]);

        foreach (['MISS01', 'PLAY01', 'DONE01'] as $code) {
            $this->from(route('join'))
                ->post(route('join.store'), [
                    'code' => $code,
                    'nickname' => 'Luna',
                ])
                ->assertRedirect(route('join'))
                ->assertSessionHasErrors([
                    'code' => 'No encontramos una sala abierta con ese código.',
                ]);
        }

        $this->assertDatabaseCount('players', 0);
    }

    public function test_player_cannot_use_a_nickname_already_taken_in_the_same_room(): void
    {
        $room = GameRoom::factory()->create(['code' => 'ABC123']);
        $existingPlayer = $room->players()->create(['nickname' => 'Luna']);

        $this->from(route('join'))
            ->post(route('join.store'), [
                'code' => $room->code,
                'nickname' => 'luna',
            ])
            ->assertRedirect(route('join'))
            ->assertSessionHasErrors([
                'nickname' => 'Ese nombre ya está siendo usado en esta sala.',
            ]);

        $this->assertDatabaseCount('players', 1);
        $this->assertModelExists($existingPlayer);
    }

    public function test_nickname_length_limits_are_enforced_with_user_facing_errors(): void
    {
        $room = GameRoom::factory()->create(['code' => 'ABC123']);

        $this->from(route('join'))
            ->post(route('join.store'), [
                'code' => $room->code,
                'nickname' => 'X',
            ])
            ->assertRedirect(route('join'))
            ->assertSessionHasErrors([
                'nickname' => 'El nombre debe tener al menos 2 caracteres.',
            ]);

        $this->from(route('join'))
            ->post(route('join.store'), [
                'code' => $room->code,
                'nickname' => str_repeat('X', 41),
            ])
            ->assertRedirect(route('join'))
            ->assertSessionHasErrors([
                'nickname' => 'El nombre no puede superar los 40 caracteres.',
            ]);

        $this->assertDatabaseCount('players', 0);
    }

    public function test_nickname_can_be_reused_in_a_different_room(): void
    {
        $targetRoom = GameRoom::factory()->create(['code' => 'ABC123']);
        $otherRoom = GameRoom::factory()->create();
        $otherRoom->players()->create(['nickname' => 'Luna']);
        Event::fake([PlayerJoined::class]);

        $response = $this->post(route('join.store'), [
            'code' => $targetRoom->code,
            'nickname' => 'Luna',
        ]);

        $player = $targetRoom->players()->where('nickname', 'Luna')->sole();

        $response->assertRedirect(route('join.lobby', $player));

        $this->assertDatabaseHas('players', [
            'game_room_id' => $targetRoom->id,
            'nickname' => 'Luna',
        ]);
    }

    public function test_lobby_escapes_player_provided_nickname(): void
    {
        $room = GameRoom::factory()->create(['code' => 'ABC123']);
        Event::fake([PlayerJoined::class]);

        $this->post(route('join.store'), [
            'code' => $room->code,
            'nickname' => '<script>alert(1)</script>',
        ])->assertRedirect();

        $player = Player::query()->sole();

        $this->get(route('join.lobby', $player))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_join_form_reports_missing_fields_and_invalid_codes(): void
    {
        $this->from(route('join'))
            ->post(route('join.store'), [])
            ->assertRedirect(route('join'))
            ->assertSessionHasErrors([
                'code' => 'Ingresa el código de la sala.',
                'nickname' => 'Ingresa el nombre que verán los demás jugadores.',
            ]);

        $this->from(route('join'))
            ->post(route('join.store'), [
                'code' => 'ABC12!',
                'nickname' => 'Luna',
            ])
            ->assertRedirect(route('join'))
            ->assertSessionHasErrors([
                'code' => 'El código solo puede contener letras y números.',
            ]);

        $this->from(route('join'))
            ->post(route('join.store'), [
                'code' => 'ABC12',
                'nickname' => 'Luna',
            ])
            ->assertRedirect(route('join'))
            ->assertSessionHasErrors([
                'code' => 'El código debe tener exactamente 6 caracteres.',
            ]);

        $this->assertDatabaseCount('players', 0);
    }

    public function test_lobby_is_only_visible_in_the_session_that_joined_that_player(): void
    {
        $room = GameRoom::factory()->create();
        $player = $room->players()->create(['nickname' => 'Luna']);

        $this->get(route('join.lobby', $player))
            ->assertNotFound();
    }
}
