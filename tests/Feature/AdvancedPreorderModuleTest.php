<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Preorder;
use App\Models\PreorderAdvance;
use App\Models\PreorderDelivery;
use App\Models\ToolMaterial;
use App\Models\ToolMaterialAssignment;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PreorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvancedPreorderModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $manager;
    protected ToolMaterial $material;
    protected Vendor $vendor;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([\Spatie\Permission\Middleware\PermissionMiddleware::class]);

        $this->user = User::factory()->create([
            'wallet' => 500000,
        ]);

        $this->manager = User::factory()->create([
            'wallet' => 500000,
        ]);

        $this->material = ToolMaterial::create([
            'item_type' => 'material',
            'sku' => 'MAT-CEMENT-01',
            'name' => 'Ultratech Cement',
            'unit' => 'bags',
            'date' => now()->toDateString(),
            'opening_quantity' => 100,
            'opening_rate' => 400,
            'opening_amount' => 40000,
            'reorder_level' => 20,
            'active_status' => true,
        ]);

        $this->vendor = Vendor::create([
            'name' => 'ABC Building Suppliers',
            'phone' => '9876543210',
            'email' => 'vendor@abcsuppliers.com',
            'active_status' => true,
        ]);

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['code' => 'HDFC'],
            ['name' => 'HDFC Bank', 'type' => 'bank_transfer', 'active_status' => true, 'sort_order' => 1]
        );
    }

    /** @test */
    public function it_creates_draft_preorder_with_auto_generated_reference_number()
    {
        $this->actingAs($this->user);

        $service = app(PreorderService::class);
        $preorder = $service->createPreorder([
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 100,
            'unit' => 'bags',
            'expected_rate' => 400,
            'gst_percent' => 18,
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
            'notes' => 'Test preorder creation',
        ], $this->user->id);

        $this->assertNotNull($preorder);
        $this->assertEquals('PRE-000001', $preorder->reference_no);
        $this->assertEquals($this->material->id, $preorder->tool_material_id);
        $this->assertEquals($this->vendor->id, $preorder->vendor_id);
        $this->assertEquals(100, (float) $preorder->quantity);
        $this->assertEquals(400, (float) $preorder->rate);
        $this->assertEquals(40000, (float) $preorder->estimated_amount);
        $this->assertEquals(7200, (float) $preorder->gst_amount);
        $this->assertEquals(47200, (float) $preorder->total_amount);
        $this->assertEquals('pending_approval', $preorder->status);

        $this->assertCount(1, $preorder->statusHistories);
        $this->assertCount(1, $preorder->auditLogs);
    }

    /** @test */
    public function it_enforces_approval_workflow_before_purchase_conversion()
    {
        $this->actingAs($this->user);

        $service = app(PreorderService::class);
        $preorder = $service->createPreorder([
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 100,
            'unit' => 'bags',
            'expected_rate' => 400,
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
        ], $this->user->id);

        $this->assertFalse($preorder->canBeConvertedToPurchase());

        // Attempt purchase conversion while unapproved -> Should Fail / Redirect
        $response = $this->from(route('preorders.index'))->post(route('preorders.convert', $preorder->id), [
            'vendor_id' => $this->vendor->id,
            'quantity' => 100,
            'rate' => 400,
            'purchase_amount' => 40000,
            'transferred_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('preorders.index'));
        $response->assertSessionHas('error');

        // Approve preorder as Manager
        $service->approvePreorder($preorder, $this->manager->id);
        $preorder->refresh();

        $this->assertTrue($preorder->isApproved());
        $this->assertTrue($preorder->canBeConvertedToPurchase());
        $this->assertEquals($this->manager->id, $preorder->approved_by);
    }

    /** @test */
    public function it_supports_multiple_advance_payments_and_debits_user_wallet()
    {
        $this->actingAs($this->user);

        $service = app(PreorderService::class);
        $preorder = $service->createPreorder([
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 100,
            'unit' => 'bags',
            'expected_rate' => 400,
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
        ], $this->user->id);

        $initialWallet = $this->user->fresh()->wallet;

        // Advance 1: Rs 10,000
        $service->addAdvancePayment($preorder, [
            'amount' => 10000,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'notes' => 'Advance 1',
            'deduct_wallet' => true,
        ], $this->user->id);

        // Advance 2: Rs 5,000
        $service->addAdvancePayment($preorder, [
            'amount' => 5000,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'notes' => 'Advance 2',
            'deduct_wallet' => true,
        ], $this->user->id);

        $preorder->refresh();

        $this->assertEquals(15000, $preorder->advance_amount);
        $this->assertEquals(25000, $preorder->remaining_amount);
        $this->assertEquals('partially_paid', $preorder->payment_status);
        $this->assertCount(2, $preorder->advances);

        // Verify user wallet debited by 15,000
        $this->assertEquals($initialWallet - 15000, $this->user->fresh()->wallet);
    }

    /** @test */
    public function it_guarantees_inventory_stock_increases_only_after_delivery()
    {
        $this->actingAs($this->user);

        $initialStock = $this->material->stock_quantity; // 100 bags
        $this->assertEquals(100, $initialStock);

        $service = app(PreorderService::class);
        $preorder = $service->createPreorder([
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 100,
            'unit' => 'bags',
            'expected_rate' => 400,
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
        ], $this->user->id);

        // Stock must NOT change on preorder creation
        $this->assertEquals(100, $this->material->fresh()->stock_quantity);

        // Approve & Order
        $service->approvePreorder($preorder, $this->manager->id);
        $service->changeStatus($preorder, 'ordered', $this->user->id);

        // Stock must STILL NOT change on approval or ordering
        $this->assertEquals(100, $this->material->fresh()->stock_quantity);

        // Delivery 1: Receive 40 bags
        $service->recordDelivery($preorder, [
            'quantity' => 40,
            'delivery_date' => now()->toDateString(),
            'notes' => 'Partial Delivery 1',
        ], $this->user->id);

        // Stock MUST NOW INCREASE by 40 -> 140 bags
        $this->assertEquals(140, $this->material->fresh()->stock_quantity);
        $this->assertEquals('partially_delivered', $preorder->fresh()->status);

        // Delivery 2: Receive remaining 60 bags
        $service->recordDelivery($preorder, [
            'quantity' => 60,
            'delivery_date' => now()->toDateString(),
            'notes' => 'Final Delivery 2',
        ], $this->user->id);

        // Stock MUST NOW INCREASE by 60 more -> 200 bags
        $this->assertEquals(200, $this->material->fresh()->stock_quantity);
        $this->assertEquals('delivered', $preorder->fresh()->status);
    }

    /** @test */
    public function it_records_document_uploads_and_audit_trail()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $service = app(PreorderService::class);
        $preorder = $service->createPreorder([
            'tool_material_id' => $this->material->id,
            'vendor_id' => $this->vendor->id,
            'quantity' => 50,
            'unit' => 'bags',
            'expected_rate' => 400,
            'preorder_date' => now()->toDateString(),
            'status' => 'pending_approval',
        ], $this->user->id);

        $file = UploadedFile::fake()->create('quotation_v1.pdf', 500, 'application/pdf');

        $doc = $service->uploadDocument($preorder, $file, 'quotation', 'Vendor Quotation V1', $this->user->id);

        $this->assertDatabaseHas('preorder_documents', [
            'id' => $doc->id,
            'preorder_id' => $preorder->id,
            'title' => 'Vendor Quotation V1',
            'document_type' => 'quotation',
        ]);

        Storage::disk('public')->assertExists($doc->file_path);

        $this->assertDatabaseHas('preorder_audit_logs', [
            'preorder_id' => $preorder->id,
            'action' => 'document_uploaded',
        ]);
    }
}
