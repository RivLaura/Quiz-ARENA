<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_links_to_the_password_recovery_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('¿Olvidaste tu contraseña?')
            ->assertSee(route('password.request'));
    }

    public function test_admin_can_request_a_password_reset_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Notification::fake();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Enviar enlace de recuperación');

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($admin, ResetPassword::class, function (ResetPassword $notification) use ($admin): bool {
            $url = $notification->toMail($admin)->actionUrl;

            return $notification->token !== ''
                && str_starts_with($url, route('password.reset', ['token' => $notification->token]));
        });
    }

    public function test_password_reset_request_does_not_disclose_or_send_links_for_unknown_or_non_admin_emails(): void
    {
        $player = User::factory()->create(['role' => 'player']);
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $player->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_admin_can_set_a_new_password_using_a_valid_reset_token(): void
    {
        $admin = User::factory()->create([
            'password' => 'old-password',
            'role' => 'admin',
        ]);
        $token = Password::broker()->createToken($admin);

        $this->get(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->assertOk()
            ->assertSee('Guardar nueva contraseña')
            ->assertSee($admin->email);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('ok', 'La contraseña se actualizó correctamente. Ya puedes iniciar sesión.');

        $this->assertTrue(Hash::check('new-secure-password', $admin->fresh()->password));
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'new-secure-password',
        ])->assertRedirect(route('admin'));
    }

    public function test_invalid_reset_token_does_not_change_the_admin_password(): void
    {
        $admin = User::factory()->create([
            'password' => 'old-password',
            'role' => 'admin',
        ]);

        $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $admin->email]))
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $admin->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('password.reset', ['token' => 'invalid-token', 'email' => $admin->email]))
            ->assertSessionHasErrors([
                'email' => 'El enlace de recuperación no es válido o ya venció.',
            ]);

        $this->assertTrue(Hash::check('old-password', $admin->fresh()->password));
    }

    public function test_non_admin_accounts_cannot_use_password_reset_tokens(): void
    {
        $player = User::factory()->create([
            'password' => 'old-password',
            'role' => 'player',
        ]);
        $token = Password::broker()->createToken($player);

        $this->from(route('password.reset', ['token' => $token, 'email' => $player->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $player->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('password.reset', ['token' => $token, 'email' => $player->email]))
            ->assertSessionHasErrors([
                'email' => 'El enlace de recuperación no es válido o ya venció.',
            ]);

        $this->assertTrue(Hash::check('old-password', $player->fresh()->password));
    }

    public function test_reset_requires_a_confirmed_password_of_at_least_eight_characters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = Password::broker()->createToken($admin);

        $this->from(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $admin->email,
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->assertSessionHasErrors(['password']);

        $this->assertFalse(Hash::check('short', $admin->fresh()->password));
    }
}
