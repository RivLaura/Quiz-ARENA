<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_question_management_for_owned_quizzes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['title' => 'Historia universal']);

        $this->actingAs($admin)
            ->get(route('admin.preguntas'))
            ->assertOk()
            ->assertSee('Historia universal')
            ->assertSee(route('admin.concursos.preguntas.index', $quiz));

        $this->get(route('admin.concursos.preguntas.index', $quiz))
            ->assertOk()
            ->assertSee('Todavía no hay preguntas')
            ->assertSee(route('admin.concursos.preguntas.create', $quiz));
    }

    public function test_admin_can_open_create_and_edit_question_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['title' => 'Cultura general']);
        $question = $this->createQuestion($quiz, 'Pregunta editable');

        $this->actingAs($admin)
            ->get(route('admin.concursos.preguntas.create', $quiz))
            ->assertOk()
            ->assertSee('NUEVA PREGUNTA')
            ->assertSee('name="options[A]"', false)
            ->assertSee('name="correct_option"', false);

        $this->get(route('admin.concursos.preguntas.edit', [$quiz, $question]))
            ->assertOk()
            ->assertSee('Pregunta editable')
            ->assertSee('Respuesta A')
            ->assertSee(route('admin.concursos.preguntas.update', [$quiz, $question]));
    }

    public function test_admin_can_create_question_with_four_options_one_correct_answer_and_scoring(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.concursos.preguntas.store', $quiz), [
                'prompt' => '¿Cuál es la capital de Uruguay?',
                'points' => 750,
                'time_limit_seconds' => 25,
                'options' => [
                    'A' => 'Montevideo',
                    'B' => 'Salto',
                    'C' => 'Colonia',
                    'D' => 'Maldonado',
                ],
                'correct_option' => 'A',
            ])
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz))
            ->assertSessionHas('ok', 'Pregunta creada correctamente.');

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $quiz->id,
            'prompt' => '¿Cuál es la capital de Uruguay?',
            'position' => 1,
            'points' => 750,
            'time_limit_seconds' => 25,
        ]);
        $this->assertDatabaseCount('question_options', 4);
        $question = Question::query()->where('quiz_id', $quiz->id)->sole();
        $this->assertDatabaseHas('question_options', [
            'question_id' => $question->id,
            'label' => 'A',
            'text' => 'Montevideo',
            'is_correct' => true,
        ]);
        $this->assertSame(
            1,
            QuestionOption::query()
                ->where('question_id', $question->id)
                ->where('is_correct', true)
                ->count(),
        );
    }

    public function test_invalid_question_options_are_rejected_without_saving_partial_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();

        $this->actingAs($admin)
            ->from(route('admin.concursos.preguntas.create', $quiz))
            ->post(route('admin.concursos.preguntas.store', $quiz), [
                'prompt' => 'Pregunta incompleta',
                'points' => 100,
                'options' => [
                    'A' => 'Opción A',
                    'B' => 'Opción B',
                    'C' => 'Opción C',
                ],
                'correct_option' => 'X',
            ])
            ->assertRedirect(route('admin.concursos.preguntas.create', $quiz))
            ->assertSessionHasErrors([
                'options.D' => 'La opción D es obligatoria.',
                'correct_option' => 'La respuesta correcta debe ser A, B, C o D.',
            ]);

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }

    public static function invalidQuestionScoring(): array
    {
        return [
            'zero points' => ['points', 0, 'El puntaje mínimo es 1.'],
            'excessive points' => ['points', 10001, 'El puntaje máximo es 10000.'],
            'too short time limit' => ['time_limit_seconds', 4, 'El tiempo límite mínimo es de 5 segundos.'],
            'too long time limit' => ['time_limit_seconds', 301, 'El tiempo límite máximo es de 300 segundos.'],
        ];
    }

    #[DataProvider('invalidQuestionScoring')]
    public function test_invalid_question_scoring_is_rejected(string $field, int $value, string $message): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $payload = $this->validQuestionPayload();
        $payload[$field] = $value;

        $this->actingAs($admin)
            ->from(route('admin.concursos.preguntas.create', $quiz))
            ->post(route('admin.concursos.preguntas.store', $quiz), $payload)
            ->assertRedirect(route('admin.concursos.preguntas.create', $quiz))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_admin_can_edit_a_question_and_replace_its_options_atomically(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz, 'Pregunta anterior');

        $this->actingAs($admin)
            ->put(route('admin.concursos.preguntas.update', [$quiz, $question]), [
                'prompt' => 'Pregunta actualizada',
                'points' => 500,
                'time_limit_seconds' => null,
                'options' => [
                    'A' => 'Nueva A',
                    'B' => 'Nueva B',
                    'C' => 'Nueva C',
                    'D' => 'Nueva D',
                ],
                'correct_option' => 'C',
            ])
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz))
            ->assertSessionHas('ok', 'Pregunta actualizada correctamente.');

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'prompt' => 'Pregunta actualizada',
            'points' => 500,
            'time_limit_seconds' => null,
        ]);
        $this->assertDatabaseCount('question_options', 4);
        $this->assertDatabaseHas('question_options', [
            'question_id' => $question->id,
            'label' => 'C',
            'text' => 'Nueva C',
            'is_correct' => true,
        ]);
        $this->assertDatabaseMissing('question_options', [
            'question_id' => $question->id,
            'text' => 'Respuesta A',
        ]);
    }

    public function test_admin_cannot_edit_another_admins_question(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($owner, 'admin')->create();
        $question = $this->createQuestion($quiz);

        $this->actingAs($admin)
            ->get(route('admin.concursos.preguntas.edit', [$quiz, $question]))
            ->assertForbidden();

        $this->assertModelExists($question);
    }

    public function test_nested_question_route_rejects_a_question_from_another_quiz(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $otherQuiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($otherQuiz);

        $this->actingAs($admin)
            ->get(route('admin.concursos.preguntas.edit', [$quiz, $question]))
            ->assertNotFound();
    }

    public function test_admin_can_delete_question_and_its_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz);

        $this->actingAs($admin)
            ->delete(route('admin.concursos.preguntas.destroy', [$quiz, $question]))
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz))
            ->assertSessionHas('ok', 'Pregunta eliminada correctamente.');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseCount('question_options', 0);
    }

    public function test_admin_can_move_questions_up_and_down_without_duplicate_positions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $first = $this->createQuestion($quiz, 'Primera', 1);
        $second = $this->createQuestion($quiz, 'Segunda', 2);
        $third = $this->createQuestion($quiz, 'Tercera', 3);

        $this->actingAs($admin)
            ->post(route('admin.concursos.preguntas.move', [$quiz, $third]), ['direction' => 'up'])
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz))
            ->assertSessionHas('ok', 'Orden de preguntas actualizado.');

        $this->assertDatabaseHas('questions', ['id' => $third->id, 'position' => 2]);
        $this->assertDatabaseHas('questions', ['id' => $second->id, 'position' => 3]);

        $this->post(route('admin.concursos.preguntas.move', [$quiz, $third]), ['direction' => 'up']);

        $this->assertDatabaseHas('questions', ['id' => $third->id, 'position' => 1]);
        $this->assertDatabaseHas('questions', ['id' => $first->id, 'position' => 2]);
    }

    public function test_question_at_order_boundary_stays_in_place(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz, 'Primera', 1);

        $this->actingAs($admin)
            ->post(route('admin.concursos.preguntas.move', [$quiz, $question]), ['direction' => 'up'])
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz));

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'position' => 1]);
    }

    public function test_question_management_is_blocked_while_a_room_is_waiting_or_playing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['status' => Quiz::STATUS_ACTIVE]);
        GameRoom::factory()->for($quiz)->create([
            'status' => GameRoom::STATUS_PLAYING,
        ]);
        $question = $this->createQuestion($quiz);

        $this->actingAs($admin)
            ->from(route('admin.concursos.preguntas.index', $quiz))
            ->delete(route('admin.concursos.preguntas.destroy', [$quiz, $question]))
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz))
            ->assertSessionHas('error', 'Finaliza o elimina las salas abiertas antes de modificar las preguntas.');

        $this->assertModelExists($question);
    }

    public function test_admin_can_delete_questions_after_rooms_finish_without_changing_historical_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $this->createQuestion($quiz, 'Pregunta original');
        $options = $question->options()->orderBy('position')->get();
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create([
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);
        $snapshot = $room->questionSnapshots()->create([
            'question_id' => $question->id,
            'position' => $question->position,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'time_limit_seconds' => null,
            'options' => $options->map(fn (QuestionOption $option): array => [
                'id' => $option->id,
                'label' => $option->label,
                'text' => $option->text,
                'is_correct' => $option->is_correct,
            ])->all(),
        ]);
        $player = $room->players()->create(['nickname' => 'Luna', 'score' => 100]);
        $player->answers()->create([
            'game_room_question_id' => $snapshot->id,
            'question_id' => $question->id,
            'question_option_id' => $options->first()->id,
            'selected_option_label' => 'A',
            'selected_option_text' => 'Respuesta A',
            'is_correct' => true,
            'response_time_ms' => 1250,
            'points_earned' => 100,
            'answered_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.concursos.preguntas.index', $quiz))
            ->assertOk()
            ->assertSee('Agregar pregunta')
            ->assertDontSee('Finaliza las partidas');

        $this->delete(route('admin.concursos.preguntas.destroy', [$quiz, $question]))
            ->assertRedirect(route('admin.concursos.preguntas.index', $quiz))
            ->assertSessionHas('ok', 'Pregunta eliminada correctamente.');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseHas('player_answers', [
            'game_room_question_id' => $snapshot->id,
            'question_id' => null,
            'question_option_id' => null,
            'selected_option_label' => 'A',
            'selected_option_text' => 'Respuesta A',
        ]);

        $this->get(route('admin.resultados.show', $room))
            ->assertOk()
            ->assertSee('Pregunta original')
            ->assertSee('Respuesta correcta: A. Respuesta A')
            ->assertSee('Luna:')
            ->assertSee('A. Respuesta A');
    }

    private function validQuestionPayload(): array
    {
        return [
            'prompt' => '¿Qué planeta habitamos?',
            'points' => 100,
            'time_limit_seconds' => null,
            'options' => [
                'A' => 'Tierra',
                'B' => 'Marte',
                'C' => 'Venus',
                'D' => 'Júpiter',
            ],
            'correct_option' => 'A',
        ];
    }

    private function createQuestion(Quiz $quiz, string $prompt = 'Pregunta de prueba', int $position = 1): Question
    {
        $question = $quiz->questions()->create([
            'prompt' => $prompt,
            'position' => $position,
            'points' => 100,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            $question->options()->create([
                'label' => $label,
                'text' => 'Respuesta '.$label,
                'position' => $index + 1,
                'is_correct' => $label === 'A',
            ]);
        }

        return $question;
    }
}
