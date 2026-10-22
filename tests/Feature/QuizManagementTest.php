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

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_quiz_as_a_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.concursos.store'), [
                'title' => 'Ciencias naturales',
                'description' => 'Preguntas de ciencias',
                'category' => 'Ciencias',
                'time_per_question' => 30,
                'status' => 'active',
                'admin_id' => $otherAdmin->id,
            ])
            ->assertRedirect(route('admin.concursos'));

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Ciencias naturales',
            'description' => 'Preguntas de ciencias',
            'category' => 'Ciencias',
            'time_per_question' => 30,
            'status' => Quiz::STATUS_DRAFT,
            'admin_id' => $admin->id,
        ]);
    }

    public function test_invalid_quiz_data_is_rejected_without_creating_a_quiz(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.concursos.create'))
            ->post(route('admin.concursos.store'), [
                'title' => '',
                'description' => str_repeat('x', 5001),
                'category' => '',
                'time_per_question' => 4,
            ])
            ->assertRedirect(route('admin.concursos.create'))
            ->assertSessionHasErrors([
                'title' => 'El título es obligatorio.',
                'description',
                'category' => 'La categoría es obligatoria.',
                'time_per_question' => 'El tiempo mínimo por pregunta es de 5 segundos.',
            ]);

        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_quiz_list_shows_only_quizzes_owned_by_the_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $ownedQuiz = Quiz::factory()->for($admin, 'admin')->create(['title' => 'Mi concurso']);
        Quiz::factory()->for($otherAdmin, 'admin')->create(['title' => 'Concurso ajeno']);

        $this->actingAs($admin)
            ->get(route('admin.concursos'))
            ->assertOk()
            ->assertSee('Mi concurso')
            ->assertDontSee('Concurso ajeno')
            ->assertSee(route('admin.concursos.edit', $ownedQuiz));
    }

    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create([
            'title' => 'Concurso editable',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.concursos.create'))
            ->assertOk()
            ->assertSee('Crear concurso')
            ->assertSee('Los concursos se crean como borrador.');

        $this->get(route('admin.concursos.edit', $quiz))
            ->assertOk()
            ->assertSee('Concurso editable')
            ->assertSee(route('admin.concursos.update', $quiz));
    }

    public function test_admin_can_update_owned_quiz_without_changing_its_owner_or_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create([
            'status' => Quiz::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.concursos.update', $quiz), [
                'title' => 'Título actualizado',
                'description' => 'Nueva descripción',
                'category' => 'Historia',
                'time_per_question' => 45,
                'status' => Quiz::STATUS_DRAFT,
                'admin_id' => $otherAdmin->id,
            ])
            ->assertRedirect(route('admin.concursos'));

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Título actualizado',
            'description' => 'Nueva descripción',
            'category' => 'Historia',
            'time_per_question' => 45,
            'status' => Quiz::STATUS_ACTIVE,
            'admin_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_edit_or_delete_another_admins_quiz(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($owner, 'admin')->create();

        $this->actingAs($admin)
            ->get(route('admin.concursos.edit', $quiz))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.concursos.destroy', $quiz))
            ->assertForbidden();

        $this->assertModelExists($quiz);
    }

    public function test_invalid_quiz_update_does_not_change_existing_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create([
            'title' => 'Título original',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.concursos.edit', $quiz))
            ->put(route('admin.concursos.update', $quiz), [
                'title' => '',
                'description' => null,
                'category' => 'Historia',
                'time_per_question' => 30,
            ])
            ->assertRedirect(route('admin.concursos.edit', $quiz))
            ->assertSessionHasErrors(['title' => 'El título es obligatorio.']);

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Título original',
        ]);
    }

    public function test_admin_can_delete_owned_quiz_without_game_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();

        $this->actingAs($admin)
            ->delete(route('admin.concursos.destroy', $quiz))
            ->assertRedirect(route('admin.concursos'))
            ->assertSessionHas('ok', 'Concurso eliminado correctamente.');

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }

    public function test_quiz_with_game_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        GameRoom::factory()->for($quiz)->create();

        $this->actingAs($admin)
            ->from(route('admin.concursos'))
            ->delete(route('admin.concursos.destroy', $quiz))
            ->assertRedirect(route('admin.concursos'))
            ->assertSessionHas('error', 'No se puede eliminar un concurso que ya tiene salas o historial de partidas.');

        $this->assertModelExists($quiz);
    }

    public function test_quiz_without_questions_cannot_be_activated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();

        $this->actingAs($admin)
            ->from(route('admin.concursos'))
            ->post(route('admin.concursos.status', $quiz))
            ->assertRedirect(route('admin.concursos'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'status' => Quiz::STATUS_DRAFT,
        ]);
    }

    public static function invalidQuestionOptionSets(): array
    {
        return [
            'fewer than four options' => [3, 1],
            'more than one correct option' => [4, 2],
            'missing an A-to-D label' => [4, 1, ['A', 'B', 'C', 'X']],
        ];
    }

    #[DataProvider('invalidQuestionOptionSets')]
    public function test_quiz_with_invalid_question_options_cannot_be_activated(
        int $optionCount,
        int $correctCount,
        ?array $labels = null,
    ): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = Question::factory()->for($quiz)->create();
        $optionLabels = $labels ?? ['A', 'B', 'C', 'D'];

        foreach (range(1, $optionCount) as $position) {
            QuestionOption::factory()->for($question)->create([
                'label' => $optionLabels[$position - 1],
                'position' => $position,
                'is_correct' => $position <= $correctCount,
            ]);
        }

        $this->actingAs($admin)
            ->from(route('admin.concursos'))
            ->post(route('admin.concursos.status', $quiz))
            ->assertRedirect(route('admin.concursos'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'status' => Quiz::STATUS_DRAFT,
        ]);
    }

    public function test_admin_can_activate_and_deactivate_a_quiz_with_valid_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = Question::factory()->for($quiz)->create();

        foreach (['A', 'B', 'C', 'D'] as $position => $label) {
            QuestionOption::factory()->for($question)->create([
                'label' => $label,
                'position' => $position + 1,
                'is_correct' => $label === 'B',
            ]);
        }

        $this->actingAs($admin)
            ->from(route('admin.concursos'))
            ->post(route('admin.concursos.status', $quiz))
            ->assertRedirect(route('admin.concursos'))
            ->assertSessionHas('ok', 'Concurso activado correctamente.');

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'status' => Quiz::STATUS_ACTIVE,
        ]);

        $this->post(route('admin.concursos.status', $quiz))
            ->assertRedirect(route('admin.concursos'))
            ->assertSessionHas('ok', 'Concurso desactivado correctamente.');

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'status' => Quiz::STATUS_DRAFT,
        ]);
    }
}
