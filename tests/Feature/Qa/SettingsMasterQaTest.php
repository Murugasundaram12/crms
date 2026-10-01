<?php

namespace Tests\Feature\Qa;

use App\Models\Category;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\QaTestCase;

class SettingsMasterQaTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('payments');
            Schema::dropIfExists('payment_stages');
            Schema::dropIfExists('payment_methods');
            Schema::dropIfExists('units');
            Schema::dropIfExists('categories');
            Schema::dropIfExists('main_categories');

            Schema::create('main_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->integer('status')->default(1);
                $table->timestamps();
            });

            Schema::create('categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('main_category_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });

            Schema::create('units', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('description')->nullable();
                $table->boolean('active_status')->default(true);
                $table->timestamps();
            });

            Schema::create('payment_stages', function (Blueprint $table): void {
                $table->id();
                $table->string('stage_name');
                $table->timestamps();
            });

            Schema::create('payment_methods', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('type')->default('cash');
                $table->boolean('active_status')->default(true);
                $table->integer('sort_order')->default(1);
                $table->timestamps();
            });

            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('stage_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Test Main Category Uppercase Conversion.
     */
    public function test_main_category_name_auto_uppercased(): void
    {
        $category = MainCategory::create([
            'name' => 'electrical works',
            'status' => 1,
        ]);

        $this->assertEquals('ELECTRICAL WORKS', $category->name);
    }

    /**
     * Test Unit Master Active Scope.
     */
    public function test_unit_master_active_scope_and_display_name(): void
    {
        Unit::create([
            'name' => 'Square Feet',
            'code' => 'SQFT',
            'active_status' => true,
        ]);

        Unit::create([
            'name' => 'Bags',
            'code' => 'BAG',
            'active_status' => false,
        ]);

        $activeUnits = Unit::active()->get();
        $this->assertCount(1, $activeUnits);
        $this->assertEquals('SQFT (Square Feet)', $activeUnits->first()->display_name);
    }

    /**
     * Test Payment Method Active Scope.
     */
    public function test_payment_method_active_scope(): void
    {
        PaymentMethod::create([
            'name' => 'Bank Transfer (IMPS)',
            'code' => 'IMPS',
            'active_status' => true,
        ]);

        PaymentMethod::create([
            'name' => 'Old Cheque System',
            'code' => 'CHQ',
            'active_status' => false,
        ]);

        $activeMethods = PaymentMethod::active()->get();
        $this->assertCount(1, $activeMethods);
        $this->assertEquals('IMPS', $activeMethods->first()->code);
    }

    /**
     * Test Payment Stage Deletion Protection when Payments Exist.
     */
    public function test_payment_stage_in_use_deletion_is_blocked(): void
    {
        $admin = $this->createSuperAdmin();
        $project = \App\Models\Project::factory()->create();
        $stage = PaymentStage::create([
            'project_id' => $project->id,
            'stage_name' => 'Plastering & Painting',
        ]);

        Payment::factory()->create([
            'project_id' => $project->id,
            'stage_id' => $stage->id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('payment-stages.destroy', $stage->id));

        $response->assertRedirect(route('payment-stages.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payment_stages', ['id' => $stage->id]);
    }
}
