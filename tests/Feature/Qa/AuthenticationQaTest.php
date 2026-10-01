<?php

namespace Tests\Feature\Qa;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\QaTestCase;

class AuthenticationQaTest extends QaTestCase
{
    // Uses isolated MySQL test database admin_crms_test via QaTestCase

    /**
     * Test 1: Login page renders with HTTP 200.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get(route('login.form'));
        $response->assertStatus(200);
        $response->assertSee('login', false);
    }

    /**
     * Test 2: Valid credentials authenticate the user and redirect to dashboard.
     */
    public function test_valid_login_authenticates_and_redirects(): void
    {
        $user = User::factory()->create([
            'email' => 'qa_tester@housefix360.com',
            'password' => Hash::make('SecretPass123!'),
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'qa_tester@housefix360.com',
            'password' => 'SecretPass123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test 3: Invalid password fails authentication and returns validation error.
     */
    public function test_invalid_password_fails_authentication(): void
    {
        User::factory()->create([
            'email' => 'qa_tester@housefix360.com',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $response = $this->from(route('login.form'))->post(route('login'), [
            'email' => 'qa_tester@housefix360.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertRedirect(route('login.form'));
        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    /**
     * Test 4: Missing required fields (empty email and password) are rejected.
     */
    public function test_empty_credentials_validation_errors(): void
    {
        $response = $this->from(route('login.form'))->post(route('login'), [
            'email' => '',
            'password' => '',
        ]);

        $response->assertRedirect(route('login.form'));
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    /**
     * Test 5: Inactive user cannot log in.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive_user@housefix360.com',
            'password' => Hash::make('SecretPass123!'),
            'status' => 'inactive',
        ]);

        $response = $this->from(route('login.form'))->post(route('login'), [
            'email' => 'inactive_user@housefix360.com',
            'password' => 'SecretPass123!',
        ]);

        $this->assertGuest();
    }

    /**
     * Test 6: Authenticated user can log out successfully.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /**
     * Test 7: Unauthenticated guest accessing protected routes is redirected to login.
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $protectedRoutes = [
            route('dashboard'),
            route('clients.index'),
            route('projects.index'),
            route('tasks.index'),
            route('expenses.history'),
            route('reports.index'),
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }
}
