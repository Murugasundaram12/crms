<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Preorder;
use App\Models\ToolMaterial;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ToolsMaterialsPreordersApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected string $adminToken;
    protected string $employeeToken;
    protected ToolMaterial $material;
    protected Vendor $vendor;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create permissions and roles manually
        $this->withoutMiddleware([\Spatie\Permission\Middleware\PermissionMiddleware::class]);

        // 2. Create users
        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin-api-test@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 100000,
            'password' => Hash::make('password'),
        ]);

        $this->employee = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee-api-test@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 10000,
            'password' => Hash::make('password'),
        ]);

        // 3. Obtain API tokens
        $this->adminToken = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
            'device_name' => 'Admin Device',
            'device_id' => 'device-admin',
        ])->json('token');

        $this->employeeToken = $this->postJson('/api/login', [
            'email' => $this->employee->email,
            'password' => 'password',
            'device_name' => 'Employee Device',
            'device_id' => 'device-employee',
        ])->json('token');

        // 4. Create models
        $this->material = ToolMaterial::create([
            'item_type' => 'material',
            'sku' => 'TEST-SKU-001',
            'name' => 'Sand Bags',
            'unit' => 'bags',
            'date' => now()->toDateString(),
            'opening_quantity' => 100,
            'opening_rate' => 50,
            'opening_amount' => 5000,
            'reorder_level' => 10,
            'active_status' => true,
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Sand Co',
            'active_status' => true,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Cash on Delivery',
            'code' => 'COD',
            'active_status' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_tools_materials_list_and_filters(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        // List tools and materials
        $response = $this->withHeaders($headers)
            ->getJson('/api/tools-materials')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        // Test filtering
        $this->withHeaders($headers)
            ->getJson('/api/tools-materials?q=Sand')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($headers)
            ->getJson('/api/tools-materials?q=Brick')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tools_materials_options_payload(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        $response = $this->withHeaders($headers)
            ->getJson('/api/tools-materials/options')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'materials',
                    'vendors',
                    'units',
                    'payment_methods',
                    'preorder_statuses',
                    'delivery_statuses',
                    'payment_statuses',
                    'categories',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_preorders_lifecycle_and_calculations(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        // 1. Create Preorder
        $preorderData = [
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 10,
            'unit' => 'bags',
            'expected_rate' => 100,
            'gst_percent' => 10,
            'advance_amount' => 50,
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
            'notes' => 'API test preorder',
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->withHeaders($headers)
            ->postJson('/api/preorders', $preorderData)
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'reference_no', 'estimated_amount', 'gst_amount', 'total_amount', 'remaining_amount'],
            ]);

        $this->assertTrue($response->json('success'));
        
        $id = $response->json('data.id');
        $this->assertEquals(1000, $response->json('data.estimated_amount')); // 10 * 100
        $this->assertEquals(100, $response->json('data.gst_amount')); // 1000 * 10%
        $this->assertEquals(1100, $response->json('data.total_amount')); // 1000 + 100
        $this->assertEquals(1050, $response->json('data.remaining_amount')); // 1100 - 50

        // 2. View Preorder
        $this->withHeaders($headers)
            ->getJson("/api/preorders/{$id}")
            ->assertOk()
            ->assertJsonPath('data.notes', 'API test preorder');

        // 3. Update Preorder
        $updateData = array_merge($preorderData, [
            'notes' => 'Updated notes',
            'quantity' => 20, // total changes: 20 * 100 = 2000 estimated, + 200 GST = 2200 total
        ]);

        $this->withHeaders($headers)
            ->putJson("/api/preorders/{$id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes')
            ->assertJsonPath('data.total_amount', 2200.0);

        // 4. Delete Preorder
        $this->withHeaders($headers)
            ->deleteJson("/api/preorders/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('preorders', ['id' => $id]);
    }

    public function test_preorders_validations_and_failures(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        // Advance > Total amount
        $invalidData = [
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 10,
            'unit' => 'bags',
            'expected_rate' => 10, // total estimated = 100
            'gst_percent' => 0, // total total = 100
            'advance_amount' => 150, // advance > 100 (invalid)
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
        ];

        $this->withHeaders($headers)
            ->postJson('/api/preorders', $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['advance_amount']);

        // Negative quantity
        $invalidData['quantity'] = -5;
        $this->withHeaders($headers)
            ->postJson('/api/preorders', $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }
}
