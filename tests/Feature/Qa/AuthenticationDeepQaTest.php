<?php

namespace Tests\Feature\Qa;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\QaTestCase;

class AuthenticationDeepQaTest extends QaTestCase
{
    public function test_guest_login_and_password_reset_pages_are_public(): void
    {
        $this->get(route('login.form'))
            ->assertOk()
            ->assertViewIs('pages.auth.login');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('pages.auth.forgot-password');
    }

    public function test_authenticated_user_is_redirected_away_from_login_and_reset_request_pages(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get(route('login.form'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)->get(route('password.request'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_login_authenticates_regenerates_session_and_redirects_to_intended_url(): void
    {
        $user = User::factory()->create([
            'email' => 'deep-auth-valid@example.test',
            'password' => Hash::make('DeepPassword123!'),
            'status' => 'active',
        ]);

        $this->get(route('login.form'));
        $sessionBeforeLogin = $this->app['session']->getId();

        $response = $this->from(route('login.form'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'DeepPassword123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionBeforeLogin, $this->app['session']->getId());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_invalid_password_unknown_email_and_malformed_input_do_not_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'deep-auth-invalid@example.test',
            'password' => Hash::make('DeepPassword123!'),
            'status' => 'active',
        ]);

        $this->from(route('login.form'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login.form'))->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->from(route('login.form'))->post(route('login'), [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login.form'))->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->from(route('login.form'))->post(route('login'), [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertRedirect(route('login.form'))
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'deep-auth-inactive@example.test',
            'password' => Hash::make('DeepPassword123!'),
            'status' => 'inactive',
        ]);

        $this->from(route('login.form'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'DeepPassword123!',
        ])->assertRedirect(route('login.form'));

        $this->assertGuest();
    }

    public function test_login_rate_limit_blocks_the_sixth_attempt_for_same_email_and_ip(): void
    {
        $payload = [
            'email' => 'deep-auth-throttle@example.test',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), $payload)->assertRedirect(route('login.form'));
        }

        $this->post(route('login'), $payload)->assertStatus(429);
        $this->assertGuest();
    }

    public function test_logout_requires_post_and_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get(route('logout'))->assertStatus(405);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->post(route('logout'))->assertRedirect('/login');

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect('/login');
        $this->post(route('logout'))->assertRedirect('/login');
    }

    public function test_authentication_mutation_routes_use_csrf_protected_web_middleware(): void
    {
        $webMiddleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'];
        $this->assertContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $webMiddleware);

        foreach (['login', 'password.email', 'password.update', 'logout'] as $routeName) {
            $this->assertContains('web', Route::getRoutes()->getByName($routeName)->gatherMiddleware());
        }

        $this->get(route('login.form'))->assertSee('name="_token"', false);
        $this->get(route('password.request'))->assertSee('name="_token"', false);
    }

    public function test_password_reset_request_validates_email_and_sends_a_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'deep-reset-request@example.test',
            'status' => 'active',
        ]);

        $this->post(route('password.email'), ['email' => 'not-an-email'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_unknown_email_does_not_disclose_account_existence(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'definitely-not-registered@example.test',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status', 'If an account exists for that email, a password reset link has been sent.')
            ->assertSessionMissing('errors');
    }

    public function test_password_reset_rate_limit_blocks_the_fourth_request(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'deep-reset-throttle@example.test']);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertStatus(429);
    }

    public function test_password_reset_rejects_invalid_token_and_does_not_change_password(): void
    {
        $oldPassword = 'OldDeepPassword123!';
        $user = User::factory()->create([
            'email' => 'deep-reset-invalid@example.test',
            'password' => Hash::make($oldPassword),
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewDeepPassword123!',
            'password_confirmation' => 'NewDeepPassword123!',
        ])->assertRedirect()->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check($oldPassword, $user->fresh()->password));
    }

    public function test_password_reset_token_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'email' => 'deep-reset-reuse@example.test',
            'password' => Hash::make('OldDeepPassword123!'),
        ]);
        $token = Password::broker()->createToken($user);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewDeepPassword123!',
            'password_confirmation' => 'NewDeepPassword123!',
        ];

        $this->post(route('password.update'), $payload)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewDeepPassword123!', $user->fresh()->password));

        $this->post(route('password.update'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_authentication_responses_do_not_echo_passwords_or_session_identifiers(): void
    {
        $password = 'DoNotEchoDeepPassword123!';
        $response = $this->from(route('login.form'))->post(route('login'), [
            'email' => 'sensitive-response@example.test',
            'password' => $password,
        ]);

        $body = $response->getContent();
        $this->assertIsString($body);
        $this->assertStringNotContainsString($password, $body);
        $this->assertStringNotContainsString('remember_token', strtolower($body));
        $this->assertStringNotContainsString('password_hash', strtolower($body));
    }
}
