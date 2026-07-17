<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentVariationWalletFlowTest extends TestCase
{
    private User $admin;
    private int $clientId;
    private int $projectId;
    private int $stageId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'wallet',
            'variations',
            'payments',
            'payment_stages',
            'quotations',
            'projects',
            'clients',
            'employees',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            $table->string('role')->nullable();
            $table->string('address')->nullable();
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->date('hire_date')->nullable();
            $table->string('status')->default('active');
            $table->decimal('wallet', 14, 2)->default(0);
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->decimal('wallet', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('project_code')->unique();
            $table->foreignId('client_id');
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

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->string('payment_code')->nullable();
            $table->foreignId('client_id');
            $table->foreignId('project_id');
            $table->foreignId('quotation_id');
            $table->foreignId('stage_id');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->decimal('amount', 14, 2);
            $table->date('due_date')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('variations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->text('description');
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('wallet', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('project_id');
            $table->integer('amount');
            $table->integer('payment_mode');
            $table->integer('transfer_type')->default(0);
            $table->integer('stage_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('current_date')->nullable();
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->timestamps();
        });

        $this->admin = User::factory()->create(['role' => 'Super Admin', 'wallet' => 0]);
        $this->clientId = DB::table('clients')->insertGetId(['name' => 'Client', 'created_at' => now(), 'updated_at' => now()]);
        $this->projectId = DB::table('projects')->insertGetId([
            'name' => 'Site A',
            'project_code' => 'SITE-A',
            'client_id' => $this->clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->stageId = DB::table('payment_stages')->insertGetId(['stage_name' => 'Stage 1', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_payment_amount_cannot_exceed_quotation_remaining_amount(): void
    {
        $quotationId = $this->quotation(400000);

        $this->actingAs($this->admin)
            ->from(route('payments.index'))
            ->post(route('payments.store'), $this->paymentPayload($quotationId, 500000))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_creates_wallet_history_for_receiving_user(): void
    {
        $quotationId = $this->quotation(400000);

        $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->paymentPayload($quotationId, 150000))
            ->assertRedirect(route('payments.index'));

        $this->assertSame(150000.0, (float) $this->admin->fresh()->wallet);
        $this->assertDatabaseHas('wallet', [
            'user_id' => $this->admin->id,
            'amount' => 150000,
            'transfer_type' => 0,
            'project_id' => $this->projectId,
        ]);
    }

    public function test_approved_variation_creates_employee_wallet_history(): void
    {
        $employeeUser = User::factory()->create(['role' => 'Employee', 'wallet' => 0]);
        DB::table('employees')->insert([
            'id' => $employeeUser->id,
            'name' => $employeeUser->name,
            'email' => $employeeUser->email,
            'wallet' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('variations.store'), [
                'project_id' => $this->projectId,
                'description' => 'Extra work',
                'type' => 'additional',
                'amount' => 25000,
                'date' => '2026-07-17',
                'approved_by' => $employeeUser->id,
                'status' => 'approved',
            ])
            ->assertRedirect(route('variations.index', ['project_id' => $this->projectId]));

        $this->assertSame(25000.0, (float) $employeeUser->fresh()->wallet);
        $this->assertDatabaseHas('wallet', [
            'user_id' => $employeeUser->id,
            'amount' => 25000,
            'transfer_type' => 0,
            'project_id' => $this->projectId,
        ]);
    }

    private function quotation(float $amount): int
    {
        return DB::table('quotations')->insertGetId([
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'quotation_number' => 'QTN-001',
            'amount' => $amount,
            'total_amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentPayload(int $quotationId, float $amount): array
    {
        return [
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'quotation_id' => $quotationId,
            'stage_id' => $this->stageId,
            'method' => 'cash',
            'amount' => $amount,
            'paid_at' => '2026-07-17T10:00',
            'status' => 'partial',
        ];
    }
}
