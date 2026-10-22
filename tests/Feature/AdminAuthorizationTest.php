<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['GET', '/admin'],
            'quizzes' => ['GET', '/admin/concursos'],
            'quiz creation form' => ['GET', '/admin/concursos/create'],
            'quiz creation endpoint' => ['POST', '/admin/concursos'],
            'questions' => ['GET', '/admin/preguntas'],
            'rooms' => ['GET', '/admin/salas'],
            'room deletion' => ['DELETE', '/admin/salas/1'],
            'games' => ['GET', '/admin/partidas'],
            'results' => ['GET', '/admin/resultados'],
            'result details' => ['GET', '/admin/resultados/1'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guest_is_redirected_to_login_from_admin_routes(string $method, string $uri): void
    {
        $this->call($method, $uri)->assertRedirect(route('login'));
    }

    public function test_authenticated_non_admin_receives_forbidden_response(): void
    {
        $user = User::factory()->create(['role' => 'player']);

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertForbidden();
    }

    public function test_admin_can_login_and_view_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'role' => 'admin',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin'));

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('¡Hola, Administrador!')
            ->assertSee('Concursos activos')
            ->assertSee('Salas')
            ->assertSee('Partidas en curso')
            ->assertSee('Cerrar sesión');
    }

    public function test_dashboard_shows_owned_active_quizzes_rooms_and_in_progress_matches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $activeQuiz = Quiz::factory()->for($admin, 'admin')->create([
            'status' => Quiz::STATUS_ACTIVE,
        ]);
        Quiz::factory()->for($admin, 'admin')->create([
            'status' => Quiz::STATUS_DRAFT,
        ]);
        $otherQuiz = Quiz::factory()->for($otherAdmin, 'admin')->create([
            'status' => Quiz::STATUS_ACTIVE,
        ]);

        $admin->hostedRooms()->create([
            'quiz_id' => $activeQuiz->id,
            'code' => 'ROOM01',
            'status' => GameRoom::STATUS_WAITING,
        ]);
        $admin->hostedRooms()->create([
            'quiz_id' => $activeQuiz->id,
            'code' => 'ROOM02',
            'status' => GameRoom::STATUS_PLAYING,
        ]);
        $admin->hostedRooms()->create([
            'quiz_id' => $activeQuiz->id,
            'code' => 'ROOM04',
            'status' => GameRoom::STATUS_FINISHED,
        ]);
        $otherAdmin->hostedRooms()->create([
            'quiz_id' => $otherQuiz->id,
            'code' => 'ROOM03',
            'status' => GameRoom::STATUS_PLAYING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('<span class="qa-dashboard-meta">Concursos activos</span>', false)
            ->assertSee('<span class="qa-dashboard-meta">Salas</span>', false)
            ->assertSee('<span class="qa-dashboard-meta">Partidas en curso</span>', false)
            ->assertSeeInOrder([
                '<strong>1</strong>',
                '<strong>3</strong>',
                '<strong>1</strong>',
            ], false)
            ->assertDontSee('Concursos creados')
            ->assertDontSee('Usuarios registrados');
    }

    public function test_admin_can_logout_and_loses_dashboard_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get(route('admin'))->assertRedirect(route('login'));
    }
}
