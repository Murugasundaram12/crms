<?php

namespace Tests\Feature\Qa;

use App\Models\Attendance;
use App\Models\Category;
use App\Models\Client;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourAttendance;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EmployeePayrollService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\QaTestCase;

class BusinessWorkflowIntegrationQaTest extends QaTestCase
{
    public function test_client_project_task_workflow_preserves_relationships_in_http_and_database(): void
    {
        $admin = $this->createSuperAdmin();
        $token = Str::upper(Str::random(10));
        $client = Client::factory()->create(['name' => 'QA Client ' . $token]);

        $this->actingAs($admin)->post(route('projects.store'), [
            'project_code' => 'QA-' . $token,
            'client_id' => $client->id,
            'name' => 'QA Project ' . $token,
            'type' => 'Residential',
            'priority' => 'high',
            'status' => 'active',
            'progress' => 0,
        ])->assertRedirect(route('projects.index'));

        $project = Project::query()->where('project_code', 'QA-' . $token)->firstOrFail();
        $this->assertSame($client->id, $project->client_id);
        $this->actingAs($admin)->get(route('projects.show', $project))
            ->assertOk()->assertSee($client->name);

        $this->actingAs($admin)->post(route('tasks.store'), [
            'project_id' => $project->id,
            'title' => 'QA Task ' . $token,
            'description' => 'Integration workflow task',
            'type' => 'general',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => now()->addDays(3)->toDateString(),
        ])->assertRedirect(route('tasks.index'));

        $task = Task::query()->where('title', 'QA Task ' . $token)->firstOrFail();
        $this->assertSame($project->id, $task->project_id);
        $this->actingAs($admin)->get(route('tasks.index'))
            ->assertOk()->assertSee($task->title)->assertSee($project->name);
    }

    public function test_quotation_payment_workflow_calculates_total_balance_and_wallet_ledger(): void
    {
        $admin = $this->createSuperAdmin(['wallet' => 0]);
        $token = Str::upper(Str::random(10));
        $client = Client::factory()->create(['name' => 'QA Billing Client ' . $token]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $method = PaymentMethod::factory()->create(['name' => 'QA Method ' . $token, 'active_status' => true]);
        $stage = PaymentStage::factory()->create(['stage_name' => 'QA Stage ' . $token]);

        $this->actingAs($admin)->post(route('quotations.store'), [
            'quotation_date' => now()->toDateString(),
            'quotation_title' => 'QA Quotation ' . $token,
            'client_name' => $client->name,
            'client_address' => 'QA address',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'items' => [[
                'main_title' => 'Works',
                'rows' => [
                    ['description' => 'First item', 'quantity' => 2, 'price' => 100, 'amount' => 200, 'unit' => 'each'],
                    ['description' => 'Second item', 'quantity' => 3, 'price' => 100, 'amount' => 300, 'unit' => 'each'],
                ],
            ]],
        ])->assertRedirect(route('quotations.list'));

        $quotation = Quotation::query()->where('quotation_title', 'QA Quotation ' . $token)->firstOrFail();
        $this->assertSame($client->id, $quotation->client_id);
        $this->assertSame($project->id, $quotation->project_id);
        $this->assertSame(500.0, (float) $quotation->amount);
        $this->assertSame(500.0, (float) $quotation->items()->sum('amount'));

        $this->actingAs($admin)->post(route('payments.store'), [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'quotation_id' => $quotation->id,
            'stage_id' => $stage->id,
            'payment_method_id' => $method->id,
            'method' => 'cash',
            'amount' => 200,
            'status' => 'partial',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect(route('payments.index'));

        $payment = Payment::query()->where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertSame($client->id, $payment->client_id);
        $this->assertSame($project->id, $payment->project_id);
        $this->assertSame($method->id, $payment->payment_method_id);
        $this->assertSame(300.0, 500.0 - (float) Payment::query()->where('quotation_id', $quotation->id)->sum('amount'));
        $this->assertDatabaseHas('wallet', ['source_type' => 'payment', 'source_id' => $payment->id, 'amount' => 200]);
    }

    public function test_employee_attendance_payroll_and_salary_period_are_integrated(): void
    {
        $admin = $this->createSuperAdmin(['wallet' => 10000]);
        $employee = User::factory()->create([
            'name' => 'QA Employee ' . Str::upper(Str::random(8)),
            'salary_amount' => 26000,
            'salary_type' => 'monthly',
        ]);
        $method = PaymentMethod::factory()->create(['name' => 'QA Payroll Method ' . Str::upper(Str::random(8))]);

        $this->actingAs($employee)->post(route('attendance.check-in'))->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('attendances', ['user_id' => $employee->id, 'attendance_date' => now()->toDateString()]);
        $this->actingAs($employee)->post(route('attendance.check-in'))->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $breakdown = app(EmployeePayrollService::class)->calculateMonthlySalary($employee, 'August 2026');
        $this->assertSame(26, $breakdown['working_days']);

        $period = 'QA-August-' . Str::upper(Str::random(8));
        $payload = [
            'user_id' => $employee->id, 'salary_period' => $period,
            'salary_amount' => $breakdown['net_salary'], 'paid_amount' => 100,
            'payment_date' => now()->toDateString(), 'payment_method_id' => $method->id,
        ];
        $this->actingAs($admin)->post(route('employee-salaries.store'), $payload)
            ->assertRedirect(route('employee-salaries.index'));
        $salary = EmployeeSalary::query()->where('user_id', $employee->id)->where('salary_period', $period)->firstOrFail();
        $this->assertSame(100.0, (float) $salary->paid_amount);
        $this->assertDatabaseHas('wallet', ['source_type' => 'employee_salary', 'source_id' => $salary->id, 'amount' => 100]);

        $this->actingAs($admin)->post(route('employee-salaries.store'), $payload)
            ->assertSessionHasErrors('salary_period');
    }

    public function test_labour_attendance_salary_advance_and_overlap_are_integrated(): void
    {
        $admin = $this->createSuperAdmin(['wallet' => 10000]);
        $role = LabourRole::factory()->create(['name' => 'QA Role ' . Str::upper(Str::random(8)), 'salary_type' => 'daily', 'salary' => 500]);
        $mainCategory = MainCategory::factory()->create(['name' => 'QA Labour Main ' . Str::upper(Str::random(8))]);
        Category::factory()->create(['name' => 'LABOUR SALARY', 'main_category_id' => $mainCategory->id]);
        $labour = Labour::factory()->create(['labour_role_id' => $role->id, 'salary' => 500, 'advance_amt' => 100]);
        $project = Project::factory()->create();
        $method = PaymentMethod::factory()->create(['name' => 'QA Labour Method ' . Str::upper(Str::random(8))]);
        LabourAssignment::create([
            'labour_id' => $labour->id, 'project_id' => $project->id, 'employee_id' => $admin->id,
            'start_date' => '2026-09-07', 'end_date' => '2026-09-09', 'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('labour-attendances.store'), [
            'labour_id' => $labour->id, 'attendance_date' => '2026-09-07', 'status' => 'present', 'project_id' => $project->id,
        ])->assertSessionHasNoErrors();
        $attendance = LabourAttendance::query()->where('labour_id', $labour->id)->firstOrFail();

        $payload = [
            'labour_id' => $labour->id, 'salary_period_start' => '2026-09-07', 'salary_period_end' => '2026-09-09',
            'salary_amount' => 100, 'paid_amount' => 0, 'advance_paid' => 1, 'advance_adjusted' => 100,
            'payment_date' => '2026-09-09', 'payment_method_id' => $method->id, 'attendance_ids' => [$attendance->id],
        ];
        $this->actingAs($admin)->post(route('labour-salaries.store'), $payload)
            ->assertRedirect(route('labour-salaries.index'));
        $salary = LabourSalary::query()->where('labour_id', $labour->id)->firstOrFail();
        $this->assertSame(100.0, (float) $salary->advance_adjusted);
        $this->assertSame($salary->id, $attendance->fresh()->labour_salary_id);
        $this->assertSame(0.0, (float) $labour->fresh()->advance_amt);

        $this->actingAs($admin)->post(route('labour-salaries.store'), $payload)
            ->assertSessionHasErrors('salary_period_start');
    }

    public function test_expense_wallet_debit_edit_and_reversal_do_not_double_debit(): void
    {
        $admin = $this->createSuperAdmin(['wallet' => 1000]);
        $token = Str::upper(Str::random(8));
        $mainCategory = MainCategory::factory()->create(['name' => 'QA Expense Main ' . $token]);
        $category = Category::factory()->create(['name' => 'QA Expense Category ' . $token, 'main_category_id' => $mainCategory->id]);
        $before = (float) $admin->fresh()->wallet;
        $this->actingAs($admin)->post(route('expenses.store.new'), [
            'amount' => 100, 'paid_amt' => 100, 'category_id' => $category->id, 'main_category_id' => $mainCategory->id, 'current_date' => now()->toDateString(),
            'description' => 'QA Expense ' . $token,
        ])->assertRedirect();
        $expense = Expense::query()->where('description', 'QA Expense ' . $token)->firstOrFail();
        $this->assertSame($before - 100, (float) $admin->fresh()->wallet);
        $this->assertSame(1, Wallet::query()->where('source_type', 'expense')->where('source_id', $expense->id)->count());

        $this->actingAs($admin)->put(route('expenses.update.new', $expense->id), [
            'amount' => 150, 'paid_amt' => 150, 'category_id' => $category->id, 'main_category_id' => $mainCategory->id, 'current_date' => now()->toDateString(),
            'description' => 'QA Expense updated ' . $token,
        ])->assertRedirect();
        $this->assertSame($before - 150, (float) $admin->fresh()->wallet);
        $this->assertSame(1, Wallet::query()->where('source_type', 'expense')->where('source_id', $expense->id)->count());

        $this->actingAs($admin)->post(route('expenses.delete-record'), ['expense_id' => $expense->id, 'delete_reason' => 'QA cleanup'])
            ->assertRedirect();
        $this->assertSame($before, (float) $admin->fresh()->wallet);
        $this->assertNotNull($expense->fresh()->deleted_at);
        $this->assertSame(0, (int) Wallet::query()->where('source_type', 'expense')->where('source_id', $expense->id)->where('transfer_type', 0)->count());
    }
}
