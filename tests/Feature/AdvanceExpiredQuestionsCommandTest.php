<?php

namespace Tests\Feature;

use App\Events\GameStateChanged;
use App\Models\GameRoom;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdvanceExpiredQuestionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_questions_advance_without_a_host_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['time_per_question' => 5]);
        $this->createQuestion($quiz, 1);
        $this->createQuestion($quiz, 2);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create();
        $room->players()->create(['nickname' => 'Luna']);
        Event::fake([GameStateChanged::class]);
        $this->freezeTime();

        $this->actingAs($admin)->post(route('admin.salas.start', $room));

        $this->artisan('games:advance-expired')
            ->assertExitCode(0);

        $this->assertSame(1, $room->fresh()->current_question_position);

        $this->travel(5)->seconds();

        $this->artisan('games:advance-expired')
            ->assertExitCode(0);

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_PLAYING, $room->status);
        $this->assertSame(2, $room->current_question_position);

        $this->travel(5)->seconds();

        $this->artisan('games:advance-expired')
            ->assertExitCode(0);

        $room->refresh();
        $this->assertSame(GameRoom::STATUS_FINISHED, $room->status);
        $this->assertNotNull($room->finished_at);
        Event::assertDispatched(GameStateChanged::class, 3);
    }

    private function createQuestion(Quiz $quiz, int $position): void
    {
        $question = $quiz->questions()->create([
            'prompt' => 'Pregunta '.$position,
            'position' => $position,
            'points' => 100,
            'time_limit_seconds' => null,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            $question->options()->create([
                'label' => $label,
                'text' => 'Opción '.$label,
                'position' => $index + 1,
                'is_correct' => $label === 'A',
            ]);
        }
    }
}
