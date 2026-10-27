<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\MainCategory;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalaryExpenseDeduplicationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Labour $labour;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureBasicTestSchema();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $role = Role::query()->firstOrCreate(['name' => 'Super Admin'], ['description' => 'Super Admin']);
        foreach ([
            'reports-list',
            'expense-reports-list',
            'labour-salaries-list',
            'labour-salaries-create',
            'labour-salaries-edit',
            'labour-salaries-delete',
        ] as $permKey) {
            $perm = Permission::query()->firstOrCreate(['key' => $permKey], ['name' => $permKey]);
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        $this->admin = User::factory()->create([
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 100000.00,
        ]);
        $this->admin->roles()->syncWithoutDetaching([$role->id]);
        $this->actingAs($this->admin);

        $labourRole = LabourRole::query()->firstOrCreate(
            ['name' => 'Mason Dedup ' . uniqid()],
            ['salary_type' => 'daily', 'salary' => 800.00]
        );

        $phone = '987' . rand(1000000, 9999999);
        $this->labour = Labour::create([
            'name' => 'Dedup Worker ' . uniqid(),
            'job_title' => 'Mason',
            'phone' => $phone,
            'phone_number' => $phone,
            'labour_role_id' => $labourRole->id,
            'salary' => 800.00,
            'advance_amt' => 0.00,
        ]);

        $this->paymentMethod = PaymentMethod::query()->firstOrCreate(
            ['code' => 'CASH'],
            ['name' => 'Cash', 'active_status' => 1]
        );
    }

    /**
     * 1. A normal salary payment creates or updates exactly one linked expense.
     */
    public function test_normal_salary_payment_creates_or_updates_one_linked_expense(): void
    {
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 8000.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'paid_amount' => 5000.00,
            'payment_date' => '2026-10-16',
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $response->assertRedirect(route('labour-salaries.index'));

        $salary = LabourSalary::query()->where('labour_id', $this->labour->id)->latest('id')->firstOrFail();
        $this->assertEquals(8000.00, (float) $salary->salary_amount);
        $this->assertEquals(5000.00, (float) $salary->paid_amount);

        // Exactly one linked expense exists
        $expenses = Expense::query()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $salary->id)
            ->get();

        $this->assertCount(1, $expenses);
        $expense = $expenses->first();
        $this->assertEquals(5000.00, (float) $expense->paid_amt);
        $this->assertEquals(3000.00, (float) $expense->unpaid_amt);

        // Update paid amount from 5,000 to 8,000
        $updateResponse = $this->put(route('labour-salaries.update', $salary->id), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 8000.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'paid_amount' => 8000.00,
            'payment_date' => '2026-10-16',
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $updateResponse->assertRedirect(route('labour-salaries.index'));

        // Still exactly one linked expense, with updated amounts and preserved ID
        $updatedExpenses = Expense::query()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $salary->id)
            ->get();

        $this->assertCount(1, $updatedExpenses);
        $this->assertEquals($expense->id, $updatedExpenses->first()->id);
        $this->assertEquals(8000.00, (float) $updatedExpenses->first()->paid_amt);
        $this->assertEquals(0.00, (float) $updatedExpenses->first()->unpaid_amt);
    }

    /**
     * 2. Soft-delete followed by a later payment restores/reuses the record.
     */
    public function test_soft_delete_followed_by_later_payment_restores_and_reuses_record(): void
    {
        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 6000.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'paid_amount' => 6000.00,
            'payment_date' => '2026-10-16',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $salary = LabourSalary::query()->where('labour_id', $this->labour->id)->latest('id')->firstOrFail();
        $originalExpense = Expense::query()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $salary->id)
            ->firstOrFail();

        $originalExpenseId = $originalExpense->id;

        // Step A: Set paid_amount to 0 -> expense is soft-deleted
        $this->put(route('labour-salaries.update', $salary->id), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 6000.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'paid_amount' => 0.00,
            'payment_date' => '2026-10-16',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        // Active count is 0, trashed count is 1
        $this->assertEquals(0, Expense::query()->where('source_type', 'labour_salary')->where('source_id', $salary->id)->count());
        $this->assertEquals(1, Expense::withTrashed()->where('source_type', 'labour_salary')->where('source_id', $salary->id)->count());

        // Step B: Set paid_amount back to 4000 -> trashed expense must be RESTORED and REUSED, not duplicated
        $this->put(route('labour-salaries.update', $salary->id), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 6000.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'paid_amount' => 4000.00,
            'payment_date' => '2026-10-16',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $activeExpenses = Expense::query()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $salary->id)
            ->get();

        $allExpenses = Expense::withTrashed()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $salary->id)
            ->get();

        $this->assertCount(1, $activeExpenses);
        $this->assertCount(1, $allExpenses);
        $this->assertEquals($originalExpenseId, $activeExpenses->first()->id);
        $this->assertNull($activeExpenses->first()->deleted_at);
        $this->assertEquals(4000.00, (float) $activeExpenses->first()->paid_amt);
        $this->assertEquals(2000.00, (float) $activeExpenses->first()->unpaid_amt);
    }

    /**
     * 3. Repeated synchronization is idempotent.
     */
    public function test_repeated_synchronization_is_idempotent(): void
    {
        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 5000.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'paid_amount' => 5000.00,
            'payment_date' => '2026-10-16',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $salary = LabourSalary::query()->where('labour_id', $this->labour->id)->latest('id')->firstOrFail();

        // Repeat update 3 times with the exact same payload
        for ($i = 0; $i < 3; $i++) {
            $this->put(route('labour-salaries.update', $salary->id), [
                'labour_id' => $this->labour->id,
                'salary_period_start' => '2026-10-01',
                'salary_period_end' => '2026-10-15',
                'salary_amount' => 5000.00,
                'advance_paid' => 0,
                'advance_adjusted' => 0.00,
                'paid_amount' => 5000.00,
                'payment_date' => '2026-10-16',
                'payment_method_id' => $this->paymentMethod->id,
            ]);
        }

        $allExpenses = Expense::withTrashed()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $salary->id)
            ->get();

        $this->assertCount(1, $allExpenses);
    }

    /**
     * 4. Legacy duplicate active rows do not multiply report totals.
     */
    public function test_legacy_duplicate_active_rows_do_not_multiply_report_totals(): void
    {
        $project = Project::factory()->create(['name' => 'Metro Station']);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_amount' => 10000.00,
            'paid_amount' => 8000.00,
            'remaining_amount' => 2000.00,
            'payment_date' => '2026-10-20',
            'status' => 'partial',
        ]);

        // Manually simulate 2 legacy duplicate active expense records for this single salary
        Expense::create([
            'project_id' => $project->id,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 8000.00,
            'paid_amt' => 8000.00,
            'unpaid_amt' => 2000.00,
            'current_date' => '2026-10-20',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        Expense::create([
            'project_id' => $project->id,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 8000.00,
            'paid_amt' => 8000.00,
            'unpaid_amt' => 2000.00,
            'current_date' => '2026-10-20',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        $reportService = app(ReportService::class);
        $totalReport = $reportService->totalReport([]);
        $summary = $totalReport['summary'];

        // Total report must NOT double-count: count=1, total=10,000, paid=8,000, unpaid=2,000
        $this->assertEquals(1, $summary['count'], 'Summary count must not multiply due to duplicate expenses');
        $this->assertEquals(10000.00, $summary['total_amount'], 'Total amount must not double');
        $this->assertEquals(8000.00, $summary['paid'], 'Paid expenses must not double');
        $this->assertEquals(2000.00, $summary['unpaid'], 'Unpaid expenses must not double');

        // Paginator records: must return exactly 1 row, not 2
        $this->assertEquals(1, $totalReport['records']->total());
    }

    /**
     * 5. Conflicting project IDs do not silently produce arbitrary project attribution.
     *    Latest active expense by MAX(id) deterministically governs project attribution.
     */
    public function test_conflicting_project_ids_deterministically_follow_latest_expense_by_id(): void
    {
        $projectA = Project::factory()->create(['name' => 'Bridge Alpha']);
        $projectB = Project::factory()->create(['name' => 'Tunnel Beta']);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_amount' => 7000.00,
            'paid_amount' => 7000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-22',
            'status' => 'paid',
        ]);

        // Duplicate row 1 (older ID) associated with Project A
        $olderExp = Expense::create([
            'project_id' => $projectA->id,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 7000.00,
            'paid_amt' => 7000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-22',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        // Duplicate row 2 (newer ID) associated with Project B
        $newerExp = Expense::create([
            'project_id' => $projectB->id,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 7000.00,
            'paid_amt' => 7000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-22',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        $this->assertGreaterThan($olderExp->id, $newerExp->id);

        $reportService = app(ReportService::class);

        // Total report filtered by Project A: MUST NOT include the salary because latest active expense belongs to Project B
        $totalAReport = $reportService->totalReport(['project_id' => $projectA->id]);
        $this->assertEquals(0, $totalAReport['summary']['count']);
        $this->assertEquals(0.00, $totalAReport['summary']['paid']);

        // Total report filtered by Project B: MUST include the salary
        $totalBReport = $reportService->totalReport(['project_id' => $projectB->id]);
        $this->assertEquals(1, $totalBReport['summary']['count']);
        $this->assertEquals(7000.00, $totalBReport['summary']['paid']);
        $firstItem = $totalBReport['records']->items()[0];
        $projectName = is_array($firstItem) ? $firstItem['project_name'] : $firstItem->project_name;
        $this->assertEquals('Tunnel Beta', $projectName);
    }

    /**
     * 6. Null project attribution remains null when required.
     *    MAX(project_id) would have wrongly picked Project A; our MAX(id) preserves NULL.
     */
    public function test_null_project_attribution_remains_null_when_latest_expense_is_office(): void
    {
        $projectA = Project::factory()->create(['name' => 'Highrise Tower']);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_amount' => 4500.00,
            'paid_amount' => 4500.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-23',
            'status' => 'paid',
        ]);

        // Older expense was tagged with Project A
        Expense::create([
            'project_id' => $projectA->id,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 4500.00,
            'paid_amt' => 4500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-23',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        // Newer expense was reassigned to Office (project_id = null)
        Expense::create([
            'project_id' => null,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 4500.00,
            'paid_amt' => 4500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-23',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        $reportService = app(ReportService::class);

        // Total Report filtered to Project A: MUST NOT include the salary because latest expense has project_id = null
        $totalReportA = $reportService->totalReport(['project_id' => $projectA->id]);
        $this->assertEquals(0, $totalReportA['summary']['count']);
        $this->assertEquals(0.00, $totalReportA['summary']['paid']);

        // Office Report: MUST include the salary with project_name = '-'
        $officeReport = $reportService->officeReport([]);
        $this->assertEquals(1, $officeReport['summary']['count']);
        $this->assertEquals(4500.00, $officeReport['summary']['paid']);
        $firstItem = $officeReport['records']->items()[0];
        $projectName = is_array($firstItem) ? $firstItem['project_name'] : $firstItem->project_name;
        $this->assertEquals('-', $projectName);
    }

    /**
     * 7. Site, Office and Total reports retain correct amounts, paid/unpaid totals, filters and ledger rows.
     */
    public function test_site_office_and_total_reports_reconcile_with_exact_mathematics(): void
    {
        $siteProject = Project::factory()->create(['name' => 'Coastal Highway']);

        // Site Salary: 10,000 paid 10,000
        $siteSalary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_amount' => 10000.00,
            'paid_amount' => 10000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-24',
            'status' => 'paid',
        ]);
        Expense::create([
            'project_id' => $siteProject->id,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 10000.00,
            'paid_amt' => 10000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-24',
            'source_type' => 'labour_salary',
            'source_id' => $siteSalary->id,
        ]);

        // Office Salary: 6,000 paid 4,000 unpaid 2,000
        $officeSalary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_amount' => 6000.00,
            'paid_amount' => 4000.00,
            'remaining_amount' => 2000.00,
            'payment_date' => '2026-10-24',
            'status' => 'partial',
        ]);
        Expense::create([
            'project_id' => null,
            'labour_id' => $this->labour->id,
            'user_id' => $this->admin->id,
            'amount' => 4000.00,
            'paid_amt' => 4000.00,
            'unpaid_amt' => 2000.00,
            'current_date' => '2026-10-24',
            'source_type' => 'labour_salary',
            'source_id' => $officeSalary->id,
        ]);

        $reportService = app(ReportService::class);

        $siteReport = $reportService->siteReport(['project_id' => $siteProject->id]);
        $officeReport = $reportService->officeReport([]);
        $totalReport = $reportService->totalReport([]);

        // Site: 1 item, 10,000 paid
        $this->assertEquals(1, $siteReport['summary']['count']);
        $this->assertEquals(10000.00, $siteReport['summary']['paid']);
        $this->assertEquals(0.00, $siteReport['summary']['unpaid']);

        // Office: 1 item, 4,000 paid, 2,000 unpaid
        $this->assertEquals(1, $officeReport['summary']['count']);
        $this->assertEquals(4000.00, $officeReport['summary']['paid']);
        $this->assertEquals(2000.00, $officeReport['summary']['unpaid']);

        // Total: exactly 2 items, 14,000 paid, 2,000 unpaid, total = 16,000
        $this->assertEquals(2, $totalReport['summary']['count']);
        $this->assertEquals(16000.00, $totalReport['summary']['total_amount']);
        $this->assertEquals(14000.00, $totalReport['summary']['paid']);
        $this->assertEquals(2000.00, $totalReport['summary']['unpaid']);

        // Mathematical reconciliation
        $this->assertEquals(
            $totalReport['summary']['paid'],
            $siteReport['summary']['paid'] + $officeReport['summary']['paid']
        );
        $this->assertEquals(
            $totalReport['summary']['unpaid'],
            $siteReport['summary']['unpaid'] + $officeReport['summary']['unpaid']
        );
    }
}
