<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectWideAjaxValidationTest extends TestCase
{
    private User $user;
    private Client $client;
    private Project $project;
    private Quotation $quotation;
    private PaymentStage $stage;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'wallet',
            'tasks',
            'payments',
            'quotations',
            'projects',
            'clients',
            'payment_methods',
            'payment_stages',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('role')->default('Super Admin');
            $table->decimal('wallet', 14, 2)->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('wallet', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->integer('payment_mode')->default(1);
            $table->integer('transfer_type')->default(0);
            $table->integer('stage_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('current_date')->nullable();
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('project_code')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('in_progress');
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('project_id');
            $table->string('quotation_number')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payment_stages', function (Blueprint $table): void {
            $table->id();
            $table->string('stage_name');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('active_status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->string('payment_code')->nullable();
            $table->foreignId('client_id');
            $table->foreignId('project_id');
            $table->foreignId('quotation_id');
            $table->foreignId('stage_id');
            $table->foreignId('payment_method_id')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->decimal('amount', 14, 2);
            $table->date('due_date')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@housefix.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'role' => 'Super Admin',
        ]);

        $this->client = Client::create(['name' => 'Acme Corporation']);
        $this->project = Project::create([
            'name' => 'Villa Construction',
            'project_code' => 'PRJ-101',
            'client_id' => $this->client->id,
        ]);
        $this->quotation = Quotation::create([
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_number' => 'QUO-1001',
            'amount' => 50000.00,
            'total_amount' => 50000.00,
        ]);
        $this->stage = PaymentStage::create(['stage_name' => 'Initial Advance']);
        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Bank Transfer',
            'code' => 'bank_transfer',
            'active_status' => true,
        ]);
    }

    /**
     * 1. Empty required fields return HTTP 422 JSON for AJAX submissions on payments.store.
     */
    public function test_payments_store_returns_422_json_with_field_errors_on_empty_ajax_submission(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('payments.store'), []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'client_id',
                    'project_id',
                    'quotation_id',
                    'stage_id',
                    'amount',
                    'payment_method_id',
                ],
            ]);

        $errors = $response->json('errors');
        $this->assertNotEmpty($errors['client_id']);
        $this->assertNotEmpty($errors['amount']);
    }

    /**
     * 2. Non-AJAX empty submission preserves standard 302 redirect with session errors.
     */
    public function test_payments_store_preserves_standard_redirect_for_non_ajax_submission(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('payments.index'))
            ->post(route('payments.store'), []);

        $response->assertStatus(302)
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors(['client_id', 'amount']);
    }

    /**
     * 3. Valid AJAX submission on payments.store saves payment and returns structured HTTP 200 JSON.
     */
    public function test_payments_store_succeeds_and_returns_structured_json_for_ajax_submission(): void
    {
        $payload = [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $this->quotation->id,
            'stage_id' => $this->stage->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => '15000.00',
            'status' => 'paid',
            'paid_at' => now()->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('payments.store'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'redirect' => route('payments.index'),
            ]);

        $this->assertDatabaseHas('payments', [
            'client_id' => $this->client->id,
            'amount' => 15000.00,
            'status' => 'paid',
        ]);
    }

    /**
     * 4. Method-spoofed PUT request with AJAX returns 422 JSON on invalid data.
     */
    public function test_payments_update_returns_422_json_for_invalid_ajax_put(): void
    {
        $payment = Payment::create([
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $this->quotation->id,
            'stage_id' => $this->stage->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 10000.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('payments.update', $payment), [
                '_method' => 'PUT',
                'amount' => '', // Empty amount to trigger validation
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['amount']]);
    }

    /**
     * 5. Method-spoofed PUT request with AJAX returns 200 JSON on valid update.
     */
    public function test_payments_update_succeeds_and_returns_200_json_for_valid_ajax_put(): void
    {
        $payment = Payment::create([
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $this->quotation->id,
            'stage_id' => $this->stage->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 10000.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('payments.update', $payment), [
                '_method' => 'PUT',
                'client_id' => $this->client->id,
                'project_id' => $this->project->id,
                'quotation_id' => $this->quotation->id,
                'stage_id' => $this->stage->id,
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => '10000.00',
                'status' => 'paid',
                'notes' => 'Updated via AJAX PUT',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'redirect' => route('payments.index'),
            ]);

        $this->assertEquals('Updated via AJAX PUT', $payment->fresh()->notes);
    }

    /**
     * 6. Clients module: empty required fields return 422 JSON via AJAX.
     */
    public function test_clients_store_returns_422_json_for_ajax_submission(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('clients.store'), []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * 7. Clients module: valid AJAX submission returns 200 JSON.
     */
    public function test_clients_store_succeeds_and_returns_200_json_for_ajax_submission(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('clients.store'), [
                'name' => 'Valid AJAX Client',
                'email' => 'ajax@test.com',
                'phone' => '9876543210',
                'status' => 'active',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'redirect' => route('clients.index'),
            ]);

        $this->assertDatabaseHas('clients', ['name' => 'Valid AJAX Client']);
    }

    /**
     * 8. Tasks module: empty required fields return 422 JSON via AJAX.
     */
    public function test_tasks_store_returns_422_json_for_ajax_submission(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('tasks.store'), []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['title']]);
    }

    /**
     * 9. GET filter / search form behaves normally without interference.
     */
    public function test_get_filter_form_continues_to_behave_normally(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('payments.index', ['q' => 'QUO-1001']));

        $response->assertStatus(200);
    }

    /**
     * 10. HandleAjaxFormResponse middleware correctly preserves session flash data across redirect.
     */
    public function test_handle_ajax_form_response_middleware_preserves_flash_data(): void
    {
        $request = Request::create('/test-ajax-form', 'POST');
        $request->headers->set('X-CRM-Form-Ajax', '1');
        $request->headers->set('Accept', 'application/json');

        $session = app('session')->driver();
        $session->setId('test-session-id');
        $session->start();
        $request->setLaravelSession($session);

        $middleware = new \App\Http\Middleware\HandleAjaxFormResponse();

        $redirect = redirect('/destination')->with('success', 'Custom success flash message');

        $response = $middleware->handle($request, function () use ($redirect) {
            return $redirect;
        });

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Custom success flash message', $data['message']);
        $this->assertEquals(url('/destination'), $data['redirect']);
    }

    /**
     * 11. Layout files properly include crm-ajax-form-validator.js script tag.
     */
    public function test_layouts_include_crm_ajax_form_validator_script(): void
    {
        $loginResponse = $this->get(route('login.form'));
        $loginResponse->assertStatus(200)
            ->assertSee('crm-ajax-form-validator.js');

        $paymentsResponse = $this->actingAs($this->user)->get(route('payments.index'));
        $paymentsResponse->assertStatus(200)
            ->assertSee('crm-ajax-form-validator.js');
    }

    /**
     * 12. Login form: empty required fields return 422 JSON via AJAX without reload.
     */
    public function test_login_returns_422_json_for_empty_ajax_submission(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-CRM-Form-Ajax' => '1',
        ])->post(route('login'), []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    /**
     * 13. Login form: invalid credentials return 422 JSON via AJAX.
     */
    public function test_login_returns_422_json_for_invalid_credentials_ajax_submission(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-CRM-Form-Ajax' => '1',
        ])->post(route('login'), [
            'email' => 'wrong@housefix.test',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    /**
     * 14. Quotations module: empty required fields return 422 JSON via AJAX.
     */
    public function test_quotations_store_returns_422_json_for_ajax_submission(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CRM-Form-Ajax' => '1',
            ])
            ->post(route('quotations.store'), []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quotation_date', 'client_id']]);
    }

    /**
     * 15. Unauthenticated user request returns appropriate status for AJAX submission.
     */
    public function test_unauthenticated_user_ajax_submission_is_protected(): void
    {
        Auth::logout();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-CRM-Form-Ajax' => '1',
        ])->post(route('payments.store'), [
            'client_id' => $this->client->id,
        ]);

        // Unauthenticated request expecting JSON receives 401 Unauthorized
        $this->assertContains($response->getStatusCode(), [401, 302]);
    }
}

