<?php

namespace Tests\Feature;

use App\Models\ToolMaterial;
use App\Models\ToolMaterialAssignment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ToolMaterialFlowTest extends TestCase
{
    protected User $admin;

    protected int $siteA;

    protected int $siteB;

    protected int $vendorId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'tool_material_assignments',
            'tools_materials',
            'vendors',
            'projects',
            'clients',
            'user_roles',
            'role_permission',
            'permissions',
            'roles',
            'mobile_api_tokens',
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

        Schema::create('mobile_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('mobile');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('project_code')->unique();
            $table->foreignId('client_id')->nullable();
            $table->string('type')->default('residential');
            $table->string('priority')->default('medium');
            $table->string('status')->default('planning');
            $table->integer('progress')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 14, 2)->default(0);
            $table->decimal('spent', 14, 2)->default(0);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('advance_amount', 14, 2)->default(0);
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('tools_materials', function (Blueprint $table): void {
            $table->id();
            $table->string('item_type', 50)->default('material');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('unit', 50)->default('Nos');
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->date('date');
            $table->decimal('opening_quantity', 12, 2)->default(0);
            $table->decimal('opening_rate', 12, 2)->default(0);
            $table->decimal('opening_amount', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->boolean('active_status')->default(true);
            $table->timestamps();
        });

        Schema::create('tool_material_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no')->nullable()->unique();
            $table->string('status', 30)->default('draft');
            $table->foreignId('tool_material_id');
            $table->foreignId('from_project_id')->nullable();
            $table->foreignId('to_project_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('handled_by')->nullable();
            $table->string('transfer_type', 50);
            $table->string('transaction_type', 50);
            $table->string('source_type', 50)->nullable();
            $table->string('destination_type', 50)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit', 50)->default('Nos');
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('receiver_name')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('transferred_at');
            $table->timestamps();
        });

        $this->admin = User::factory()->create(['role' => 'Super Admin', 'password' => Hash::make('password')]);
        $clientId = DB::table('clients')->insertGetId(['name' => 'Client', 'created_at' => now(), 'updated_at' => now()]);
        $this->siteA = DB::table('projects')->insertGetId([
            'name' => 'Site A',
            'project_code' => 'SITE-A',
            'client_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->siteB = DB::table('projects')->insertGetId([
            'name' => 'Site B',
            'project_code' => 'SITE-B',
            'client_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->vendorId = DB::table('vendors')->insertGetId([
            'name' => 'Vendor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_stock_ledger_updates_balances_and_blocks_over_issue(): void
    {
        $material = $this->createMaterial();

        $this->postAssignment($material, ['transaction_type' => 'purchase', 'quantity' => 50, 'rate' => 2, 'amount' => 0]);
        $this->assertDatabaseHas('tool_material_assignments', [
            'tool_material_id' => $material->id,
            'transaction_type' => 'purchase',
            'amount' => 100,
        ]);

        $this->postAssignment($material, ['transaction_type' => 'issue_to_site', 'to_project_id' => $this->siteA, 'quantity' => 30]);

        $material = $material->fresh(['assignments.fromProject', 'assignments.toProject']);
        $this->assertSame(120.0, $material->office_stock_quantity);
        $this->assertSame(30.0, $material->stockBalances()['site:' . $this->siteA]['quantity']);
        $this->assertSame(150.0, $material->stock_quantity);

        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, [
                'transaction_type' => 'issue_to_site',
                'to_project_id' => $this->siteA,
                'quantity' => 999,
            ]))
            ->assertSessionHasErrors('quantity');

        $this->postAssignment($material, ['transaction_type' => 'site_to_site', 'from_project_id' => $this->siteA, 'to_project_id' => $this->siteB, 'quantity' => 10]);
        $this->postAssignment($material, ['transaction_type' => 'return_to_office', 'from_project_id' => $this->siteA, 'quantity' => 5]);
        $this->postAssignment($material, ['transaction_type' => 'damage_wastage', 'source_type' => 'office', 'quantity' => 25]);

        $material = $material->fresh(['assignments.fromProject', 'assignments.toProject']);
        $this->assertSame(100.0, $material->office_stock_quantity);
        $this->assertSame(15.0, $material->stockBalances()['site:' . $this->siteA]['quantity']);
        $this->assertSame(10.0, $material->stockBalances()['site:' . $this->siteB]['quantity']);
        $this->assertSame(125.0, $material->stock_quantity);
    }

    public function test_draft_transactions_do_not_affect_stock_until_completed(): void
    {
        $material = $this->createMaterial();

        $this->postAssignment($material, [
            'status' => 'draft',
            'transaction_type' => 'issue_to_site',
            'to_project_id' => $this->siteA,
            'quantity' => 40,
        ]);

        $assignment = ToolMaterialAssignment::query()->firstOrFail();
        $this->assertSame(100.0, $material->fresh(['assignments'])->stock_quantity);

        $this->actingAs($this->admin)
            ->put(route('tools-material-assignments.update', $assignment), $this->payload($material, [
                'status' => 'completed',
                'transaction_type' => 'issue_to_site',
                'to_project_id' => $this->siteA,
                'quantity' => 40,
            ]))
            ->assertRedirect(route('tools-material-assignments.index'));

        $material = $material->fresh(['assignments.fromProject', 'assignments.toProject']);
        $this->assertSame(60.0, $material->office_stock_quantity);
        $this->assertSame(40.0, $material->stockBalances()['site:' . $this->siteA]['quantity']);
    }

    public function test_new_tool_material_assignment_defaults_to_draft(): void
    {
        $material = $this->createMaterial();

        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, [
                'status' => 'draft',
                'transaction_type' => 'issue_to_site',
                'to_project_id' => $this->siteA,
                'quantity' => 20,
            ]))
            ->assertRedirect(route('tools-material-assignments.index'));

        $this->assertDatabaseHas('tool_material_assignments', [
            'tool_material_id' => $material->id,
            'transaction_type' => 'issue_to_site',
            'status' => 'draft',
        ]);
        $this->assertSame(100.0, $material->fresh(['assignments'])->office_stock_quantity);
    }

    public function test_vendor_return_adjusts_advance_and_reverses_on_update_and_delete(): void
    {
        $material = $this->createMaterial();

        $this->postAssignment($material, [
            'transaction_type' => 'return_to_vendor',
            'source_type' => 'office',
            'vendor_id' => $this->vendorId,
            'quantity' => 20,
            'rate' => 5,
        ]);

        $assignment = ToolMaterialAssignment::query()->firstOrFail();
        $vendor = DB::table('vendors')->where('id', $this->vendorId)->first();
        $this->assertSame('100', (string) (int) $vendor->advance_amount);
        $this->assertSame(80.0, $material->fresh(['assignments'])->office_stock_quantity);

        $this->actingAs($this->admin)
            ->put(route('tools-material-assignments.update', $assignment), $this->payload($material, [
                'transaction_type' => 'return_to_vendor',
                'source_type' => 'office',
                'vendor_id' => $this->vendorId,
                'quantity' => 30,
                'rate' => 5,
            ]))
            ->assertRedirect(route('tools-material-assignments.index'));

        $vendor = DB::table('vendors')->where('id', $this->vendorId)->first();
        $this->assertSame('150', (string) (int) $vendor->advance_amount);
        $this->assertSame('150', (string) (int) $vendor->advance_amt);
        $this->assertSame(70.0, $material->fresh(['assignments'])->office_stock_quantity);

        $this->actingAs($this->admin)
            ->delete(route('tools-material-assignments.destroy', $assignment->fresh()))
            ->assertRedirect(route('tools-material-assignments.index'));

        $vendor = DB::table('vendors')->where('id', $this->vendorId)->first();
        $this->assertSame('0', (string) (int) $vendor->advance_amount);
        $this->assertSame(100.0, $material->fresh(['assignments'])->office_stock_quantity);
    }

    public function test_material_with_transactions_cannot_be_deleted_directly(): void
    {
        $material = $this->createMaterial();
        $this->postAssignment($material, ['transaction_type' => 'purchase', 'quantity' => 10]);

        $this->actingAs($this->admin)
            ->from(route('tools-materials.index'))
            ->delete(route('tools-materials.destroy', $material))
            ->assertRedirect(route('tools-materials.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tools_materials', ['id' => $material->id]);
        $this->assertDatabaseCount('tool_material_assignments', 1);
    }

    public function test_tool_material_form_can_create_and_update_inactive_items(): void
    {
        $this->actingAs($this->admin)
            ->post(route('tools-materials.store'), [
                'item_type' => 'tool',
                'sku' => 'DRILL-1',
                'name' => 'Drill Machine',
                'unit' => 'Nos',
                'date' => '2026-07-16',
                'opening_quantity' => 2,
                'opening_rate' => 1500,
                'reorder_level' => 1,
                'active_status' => 0,
            ])
            ->assertRedirect(route('tools-materials.index'));

        $material = ToolMaterial::query()->where('sku', 'DRILL-1')->firstOrFail();
        $this->assertFalse((bool) $material->active_status);
        $this->assertSame(3000.0, (float) $material->opening_amount);

        $this->actingAs($this->admin)
            ->put(route('tools-materials.update', $material), [
                'item_type' => 'tool',
                'sku' => 'DRILL-1',
                'name' => 'Drill Machine',
                'unit' => 'Nos',
                'date' => '2026-07-16',
                'opening_quantity' => 3,
                'opening_rate' => 1500,
                'reorder_level' => 1,
                'active_status' => 0,
            ])
            ->assertRedirect(route('tools-materials.index'));

        $material->refresh();
        $this->assertFalse((bool) $material->active_status);
        $this->assertSame(4500.0, (float) $material->opening_amount);
    }

    public function test_assign_transfer_create_form_prefills_return_and_transfer_fields(): void
    {
        $material = $this->createMaterial();

        $this->actingAs($this->admin)
            ->get(route('tools-material-assignments.create', [
                'tool_material_id' => $material->id,
                'transaction_type' => 'site_to_site',
                'source_type' => 'site',
                'destination_type' => 'site',
                'from_project_id' => $this->siteA,
                'quantity' => 12,
                'rate' => 7,
                'amount' => 84,
                'purpose' => 'Transfer from Site A',
                'lock_transaction' => 1,
            ]))
            ->assertOk()
            ->assertSee('value="' . $material->id . '" selected', false)
            ->assertSee('name="transaction_type" value="site_to_site"', false)
            ->assertSee('name="_transaction_type_display"', false)
            ->assertSee('disabled', false)
            ->assertSee('value="site_to_site" selected', false)
            ->assertSee('value="' . $this->siteA . '" selected', false)
            ->assertSee('value="12"', false)
            ->assertSee('value="7"', false)
            ->assertSee('value="84"', false)
            ->assertSee('Transfer from Site A');
    }

    public function test_draft_site_assignment_shows_return_and_transfer_actions(): void
    {
        $material = $this->createMaterial();

        $this->postAssignment($material, [
            'status' => 'draft',
            'transaction_type' => 'issue_to_site',
            'to_project_id' => $this->siteA,
            'quantity' => 12,
            'rate' => 7,
        ]);

        $this->actingAs($this->admin)
            ->get(route('tools-material-assignments.index'))
            ->assertOk()
            ->assertSee('Return')
            ->assertSee('Transfer')
            ->assertSee('transaction_type=return_to_office', false)
            ->assertSee('transaction_type=site_to_site', false)
            ->assertSee('quantity=12', false)
            ->assertDontSee('No site stock');
    }

    public function test_assignment_form_rejects_invalid_transaction_location_combinations(): void
    {
        $material = $this->createMaterial();

        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, [
                'transaction_type' => 'purchase',
                'vendor_id' => null,
            ]))
            ->assertSessionHasErrors('vendor_id');

        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, [
                'transaction_type' => 'issue_to_site',
                'to_project_id' => null,
            ]))
            ->assertSessionHasErrors('to_project_id');

        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, [
                'transaction_type' => 'site_to_site',
                'from_project_id' => $this->siteA,
                'to_project_id' => $this->siteA,
            ]))
            ->assertSessionHasErrors('to_project_id');

        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, [
                'transaction_type' => 'return_to_vendor',
                'source_type' => 'site',
                'from_project_id' => null,
                'vendor_id' => $this->vendorId,
            ]))
            ->assertSessionHasErrors('from_project_id');
    }

    public function test_mobile_inventory_api_creates_purchase_and_blocks_over_issue(): void
    {
        $material = $this->createMaterial(['opening_quantity' => 0, 'opening_amount' => 0]);
        $token = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
            'device_name' => 'Inventory Flow Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->postJson('/api/inventory/transactions', [
                'tool_material_id' => $material->id,
                'transaction_type' => 'purchase',
                'status' => 'transferred',
                'destination_type' => 'office',
                'vendor_id' => $this->vendorId,
                'quantity' => 25,
                'rate' => 12,
                'transferred_at' => '2026-07-16 10:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('transaction.transaction_type', 'purchase')
            ->assertJsonPath('transaction.amount', 300);

        $this->withHeaders($headers)
            ->postJson('/api/inventory/transactions', [
                'tool_material_id' => $material->id,
                'transaction_type' => 'issue_to_site',
                'status' => 'transferred',
                'to_project_id' => $this->siteA,
                'quantity' => 30,
                'rate' => 12,
                'transferred_at' => '2026-07-16 11:00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Insufficient stock. Available quantity is 25.00 CFT.');

        $this->withHeaders($headers)
            ->postJson('/api/inventory/transactions', [
                'tool_material_id' => $material->id,
                'transaction_type' => 'issue_to_site',
                'status' => 'transferred',
                'to_project_id' => $this->siteA,
                'quantity' => 10,
                'rate' => 12,
                'transferred_at' => '2026-07-16 11:30:00',
            ])
            ->assertCreated()
            ->assertJsonPath('transaction.to_project.id', $this->siteA)
            ->assertJsonPath('transaction.tool_material.office_stock_quantity', 15)
            ->assertJsonPath('transaction.tool_material.site_stock_quantity', 10);
    }

    public function test_tools_materials_api_can_create_update_show_list_and_delete_items(): void
    {
        $headers = $this->apiHeaders();

        $createResponse = $this->withHeaders($headers)
            ->postJson('/api/tools-materials', [
                'item_type' => 'tool',
                'sku' => 'CUTTER-1',
                'name' => 'Tile Cutter',
                'date' => '2026-07-16',
                'active_status' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('tool_material.name', 'Tile Cutter')
            ->assertJsonPath('tool_material.unit', 'Nos')
            ->assertJsonPath('tool_material.active_status', false);

        $itemId = $createResponse->json('tool_material.id');

        $this->withHeaders($headers)
            ->getJson('/api/tools-materials/' . $itemId)
            ->assertOk()
            ->assertJsonPath('tool_material.sku', 'CUTTER-1');

        $this->withHeaders($headers)
            ->putJson('/api/tools-materials/' . $itemId, [
                'item_type' => 'material',
                'sku' => 'TILE-1',
                'name' => 'Floor Tile',
                'unit' => 'Nos',
                'date' => '2026-07-17',
                'opening_quantity' => 20,
                'opening_rate' => 50,
                'reorder_level' => 5,
                'active_status' => true,
            ])
            ->assertOk()
            ->assertJsonPath('tool_material.name', 'Floor Tile')
            ->assertJsonPath('tool_material.opening_amount', 1000)
            ->assertJsonPath('tool_material.active_status', true);

        $this->withHeaders($headers)
            ->getJson('/api/tools-materials?q=Floor&item_type=material')
            ->assertOk()
            ->assertJsonPath('summary.materials', 1)
            ->assertJsonPath('data.0.name', 'Floor Tile');

        $this->withHeaders($headers)
            ->deleteJson('/api/tools-materials/' . $itemId)
            ->assertOk()
            ->assertJsonPath('message', 'Tool / material deleted successfully.');

        $this->assertDatabaseMissing('tools_materials', ['id' => $itemId]);
    }

    public function test_tools_material_assignments_api_can_update_and_delete_transactions(): void
    {
        $material = $this->createMaterial();
        $headers = $this->apiHeaders();

        $createResponse = $this->withHeaders($headers)
            ->postJson('/api/tools-material-assignments', [
                'tool_material_id' => $material->id,
                'status' => 'transferred',
                'transaction_type' => 'issue_to_site',
                'to_project_id' => $this->siteA,
                'quantity' => 30,
                'rate' => 10,
                'transferred_at' => '2026-07-16 10:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('transaction.to_project.id', $this->siteA)
            ->assertJsonPath('transaction.quantity', 30);

        $assignmentId = $createResponse->json('transaction.id');
        $this->assertSame(70.0, $material->fresh(['assignments.fromProject', 'assignments.toProject'])->office_stock_quantity);

        $this->withHeaders($headers)
            ->putJson('/api/tools-material-assignments/' . $assignmentId, [
                'tool_material_id' => $material->id,
                'status' => 'transferred',
                'transaction_type' => 'issue_to_site',
                'to_project_id' => $this->siteA,
                'quantity' => 80,
                'rate' => 10,
                'transferred_at' => '2026-07-16 11:00:00',
            ])
            ->assertOk()
            ->assertJsonPath('transaction.quantity', 80)
            ->assertJsonPath('transaction.tool_material.office_stock_quantity', 20);

        $this->withHeaders($headers)
            ->putJson('/api/tools-material-assignments/' . $assignmentId, [
                'tool_material_id' => $material->id,
                'status' => 'transferred',
                'transaction_type' => 'issue_to_site',
                'to_project_id' => $this->siteA,
                'quantity' => 999,
                'rate' => 10,
                'transferred_at' => '2026-07-16 12:00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Insufficient stock. Available quantity is 100.00 CFT.');

        $this->withHeaders($headers)
            ->deleteJson('/api/tools-material-assignments/' . $assignmentId)
            ->assertOk()
            ->assertJsonPath('message', 'Inventory transaction deleted successfully.');

        $this->assertSame(100.0, $material->fresh(['assignments.fromProject', 'assignments.toProject'])->office_stock_quantity);
        $this->assertDatabaseMissing('tool_material_assignments', ['id' => $assignmentId]);
    }

    private function createMaterial(array $overrides = []): ToolMaterial
    {
        return ToolMaterial::query()->create(array_merge([
            'item_type' => 'material',
            'name' => 'Sand',
            'unit' => 'CFT',
            'date' => '2026-07-16',
            'opening_quantity' => 100,
            'opening_rate' => 10,
            'opening_amount' => 1000,
            'reorder_level' => 10,
            'active_status' => true,
        ], $overrides));
    }

    private function postAssignment(ToolMaterial $material, array $overrides = []): void
    {
        $this->actingAs($this->admin)
            ->post(route('tools-material-assignments.store'), $this->payload($material, $overrides))
            ->assertRedirect(route('tools-material-assignments.index'));
    }

    private function payload(ToolMaterial $material, array $overrides = []): array
    {
        $payload = array_merge([
            'tool_material_id' => $material->id,
            'reference_no' => null,
            'status' => 'completed',
            'transaction_type' => 'purchase',
            'source_type' => 'office',
            'destination_type' => 'office',
            'from_project_id' => null,
            'to_project_id' => null,
            'vendor_id' => null,
            'quantity' => 10,
            'rate' => 10,
            'amount' => null,
            'receiver_name' => null,
            'vehicle_no' => null,
            'purpose' => null,
            'notes' => null,
            'transferred_at' => '2026-07-16 10:00:00',
        ], $overrides);

        if (($payload['transaction_type'] ?? null) === 'purchase' && ! array_key_exists('vendor_id', $overrides)) {
            $payload['vendor_id'] = $this->vendorId;
        }

        return $payload;
    }

    private function apiHeaders(): array
    {
        $token = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
            'device_name' => 'Inventory Flow Test',
        ])->json('token');

        return ['Authorization' => 'Bearer ' . $token];
    }
}
