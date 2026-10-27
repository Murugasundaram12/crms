<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourSalary;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportControllerUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureBasicTestSchema();
    }

    private function createAdminUser(bool $isSuperAdmin = true): User
    {
        $roleName = $isSuperAdmin ? 'Super Admin' : 'Manager';
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['description' => $roleName]);

        $perm1 = Permission::query()->firstOrCreate(['key' => 'reports-list'], ['name' => 'Reports List']);
        $perm2 = Permission::query()->firstOrCreate(['key' => 'expense-reports-list'], ['name' => 'Expense Reports List']);

        $role->permissions()->syncWithoutDetaching([$perm1->id, $perm2->id]);

        $user = User::factory()->create([
            'role' => $roleName,
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_without_permission_receives_403(): void
    {
        $user = User::factory()->create([
            'role' => 'Worker',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));
        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_site_report_with_defaults(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Villa Construction']);

        Expense::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'amount' => 5000.00,
            'paid_amt' => 3000.00,
            'unpaid_amt' => 2000.00,
            'current_date' => now()->toDateString(),
            'description' => 'Cement bags purchase',
        ]);

        Payment::create([
            'project_id' => $project->id,
            'amount' => 10000.00,
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'payment_code' => 'PAY-TEST-001',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index'));
        $response->assertOk();
        $response->assertViewIs('pages.reports.index');
        $response->assertViewHas('records');
        $response->assertViewHas('summary');
        $response->assertViewHas('projects');
        $response->assertSee('Site Report');
        $response->assertSee('Villa Construction');
        $response->assertSee('Rs 5,000.00');
        $response->assertSee('Rs 3,000.00');
        $response->assertSee('Rs 2,000.00');
        $response->assertSee('Rs 10,000.00');
        // Net balance = 10,000 - 3,000 = 7,000.00
        $response->assertSee('Rs 7,000.00');
    }

    public function test_date_range_validation_rejects_end_date_before_start_date(): void
    {
        $admin = $this->createAdminUser(true);

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'date_from' => '2026-10-15',
            'date_to' => '2026-10-10',
        ]));

        $response->assertSessionHasErrors('date_to');
    }

    public function test_project_filter_scopes_expenses_and_income_to_selected_project(): void
    {
        $admin = $this->createAdminUser(true);
        $projectA = Project::factory()->create(['name' => 'Project Alpha']);
        $projectB = Project::factory()->create(['name' => 'Project Beta']);

        Expense::create([
            'project_id' => $projectA->id,
            'user_id' => $admin->id,
            'amount' => 1200.00,
            'paid_amt' => 1200.00,
            'unpaid_amt' => 0.00,
            'current_date' => now()->toDateString(),
            'description' => 'Alpha Expense',
        ]);

        Expense::create([
            'project_id' => $projectB->id,
            'user_id' => $admin->id,
            'amount' => 4500.00,
            'paid_amt' => 4500.00,
            'unpaid_amt' => 0.00,
            'current_date' => now()->toDateString(),
            'description' => 'Beta Expense',
        ]);

        Payment::create([
            'project_id' => $projectA->id,
            'amount' => 8000.00,
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'payment_code' => 'PAY-ALPHA-01',
        ]);

        Payment::create([
            'project_id' => $projectB->id,
            'amount' => 20000.00,
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'payment_code' => 'PAY-BETA-01',
        ]);

        // Filter by Project A only
        $response = $this->actingAs($admin)->get(route('reports.index', [
            'project_id' => $projectA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Project Alpha');
        $response->assertDontSee('Beta Expense');
        $response->assertSee('Rs 1,200.00');
        $response->assertDontSee('Rs 4,500.00');
        $response->assertSee('Rs 8,000.00');
        $response->assertDontSee('Rs 20,000.00');
    }

    public function test_date_range_filter_scopes_expenses_and_income_within_dates(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Date Test Project']);

        // In range
        Expense::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'amount' => 2500.00,
            'paid_amt' => 2500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-05-15',
            'description' => 'May Expense',
        ]);

        // Out of range
        Expense::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'amount' => 9999.00,
            'paid_amt' => 9999.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-01-10',
            'description' => 'January Expense',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertOk();
        $response->assertSee('May Expense');
        $response->assertDontSee('January Expense');
        $response->assertSee('Rs 2,500.00');
        $response->assertDontSee('Rs 9,999.00');
    }

    public function test_office_and_total_reports_switch_type_cleanly(): void
    {
        $admin = $this->createAdminUser(true);

        $responseOffice = $this->actingAs($admin)->get(route('reports.index', ['type' => 'office']));
        $responseOffice->assertOk();
        $responseOffice->assertSee('Office Report');

        $responseTotal = $this->actingAs($admin)->get(route('reports.index', ['type' => 'total']));
        $responseTotal->assertOk();
        $responseTotal->assertSee('Total Report');
    }

    public function test_income_and_net_balance_kpi_cards_render_for_super_admin(): void
    {
        $admin = $this->createAdminUser(true);

        $response = $this->actingAs($admin)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('Income Received');
        $response->assertSee('Net Balance');
        $response->assertSee('Paid Payment Income');
    }

    public function test_non_super_admin_does_not_see_income_or_net_balance_cards(): void
    {
        $manager = $this->createAdminUser(false);

        $response = $this->actingAs($manager)->get(route('reports.index'));
        $response->assertOk();
        $response->assertDontSee('Income Received');
        $response->assertDontSee('Net Balance');
        $response->assertDontSee('Paid Payment Income');
    }

    public function test_empty_state_renders_cleanly_when_no_records_match(): void
    {
        $admin = $this->createAdminUser(true);

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'date_from' => '2099-01-01',
            'date_to' => '2099-01-02',
        ]));

        $response->assertOk();
        $response->assertSee('No report data found');
        $response->assertSee('Try changing the date range or report type.');
        $response->assertSee('0 entries');
    }

    public function test_report_view_contains_search_input_csv_export_and_print_styles(): void
    {
        $admin = $this->createAdminUser(true);

        $response = $this->actingAs($admin)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('id="reportTableSearchInput"', false);
        $response->assertSee('id="exportReportCsvBtn"', false);
        $response->assertSee('@media print', false);
        $response->assertSee('window.print()', false);
    }

    public function test_site_report_page_2_pagination_preserves_summary_totals(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Pagination Test Project']);

        // Create 15 expenses so there are at least 2 pages (PER_PAGE = 10)
        for ($i = 1; $i <= 15; $i++) {
            Expense::create([
                'project_id' => $project->id,
                'user_id' => $admin->id,
                'amount' => 100.00,
                'paid_amt' => 80.00,
                'unpaid_amt' => 20.00,
                'current_date' => now()->subDays($i)->toDateString(),
                'description' => "Expense item {$i}",
            ]);
        }

        // On page 2, the summary must still show 15 entries, total 1500, paid 1200, unpaid 300
        $response = $this->actingAs($admin)->get(route('reports.index', ['page' => 2]));
        $response->assertOk();

        $summary = $response->viewData('summary');
        $this->assertEquals(15, $summary['count']);
        $this->assertEquals(1500.00, $summary['total_amount']);
        $this->assertEquals(1200.00, $summary['paid']);
        $this->assertEquals(300.00, $summary['unpaid']);
    }

    public function test_partial_and_paid_payments_contribute_to_income(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Income Status Project']);

        Payment::create([
            'project_id' => $project->id,
            'amount' => 3000.00,
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'payment_code' => 'PAY-STATUS-01',
        ]);

        Payment::create([
            'project_id' => $project->id,
            'amount' => 2000.00,
            'status' => 'partial',
            'payment_date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'payment_code' => 'PAY-STATUS-02',
        ]);

        Payment::create([
            'project_id' => $project->id,
            'amount' => 9000.00,
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'method' => 'cheque',
            'payment_code' => 'PAY-STATUS-03',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index'));
        $response->assertOk();

        $summary = $response->viewData('summary');
        // Total income should be 3000 (paid) + 2000 (partial) = 5000; pending is excluded
        $this->assertEquals(5000.00, $summary['income']);
    }

    public function test_total_report_does_not_double_count_labour_salary_and_linked_expense(): void
    {
        $admin = $this->createAdminUser(true);

        $labour = Labour::create([
            'name' => 'Ramesh Construction Worker',
            'salary' => 800.00,
        ]);

        $labourSalary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 4000.00,
            'paid_amount' => 3000.00,
            'remaining_amount' => 1000.00,
            'payment_date' => '2026-10-20',
            'status' => 'partial',
        ]);

        // Synced expense created by LabourSalaryController::syncSalaryExpense
        Expense::create([
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 3000.00,
            'paid_amt' => 3000.00,
            'unpaid_amt' => 1000.00,
            'current_date' => '2026-10-20',
            'description' => 'Paid Labour Salary to Ramesh',
            'source_type' => 'labour_salary',
            'source_id' => $labourSalary->id,
        ]);

        // Ordinary expense
        Expense::create([
            'user_id' => $admin->id,
            'amount' => 1500.00,
            'paid_amt' => 1000.00,
            'unpaid_amt' => 500.00,
            'current_date' => '2026-10-20',
            'description' => 'Office Supplies',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index', ['type' => 'total']));
        $response->assertOk();

        $summary = $response->viewData('summary');
        // Must count 2 entries (1 ordinary expense + 1 labour salary), NOT 3!
        $this->assertEquals(2, $summary['count']);
        // Total amount: 1500 + 4000 = 5500, NOT 8500 or 9500!
        $this->assertEquals(5500.00, $summary['total_amount']);
        // Paid amount: 1000 + 3000 = 4000, NOT 7000!
        $this->assertEquals(4000.00, $summary['paid']);
        // Unpaid amount: 500 + 1000 = 1500, NOT 2500!
        $this->assertEquals(1500.00, $summary['unpaid']);

        // Verify ledger items: ordinary expense is present, labour salary is present, synced expense is NOT duplicated
        $records = $response->viewData('records');
        $this->assertCount(2, $records->items());
        $descriptions = array_map(fn($r) => is_array($r) ? $r['description'] : $r->description, $records->items());
        $this->assertContains('Office Supplies', $descriptions);
        $this->assertNotContains('Paid Labour Salary to Ramesh', $descriptions);
    }

    public function test_site_report_still_includes_project_linked_labour_salary_expense_once(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Site Labour Project']);

        $labour = Labour::create([
            'name' => 'Suresh Mason',
            'salary' => 900.00,
        ]);

        $labourSalary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 2500.00,
            'paid_amount' => 2500.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-22',
            'status' => 'paid',
        ]);

        // Synced expense linked to project
        Expense::create([
            'project_id' => $project->id,
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 2500.00,
            'paid_amt' => 2500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-22',
            'description' => 'Paid Labour Salary to Suresh for Site',
            'source_type' => 'labour_salary',
            'source_id' => $labourSalary->id,
        ]);

        // Ordinary site expense
        Expense::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'amount' => 1200.00,
            'paid_amt' => 1200.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-22',
            'description' => 'Site Bricks Purchase',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'type' => 'site',
            'project_id' => $project->id,
        ]));
        $response->assertOk();

        $summary = $response->viewData('summary');
        // Site report does not query labour_salaries table directly, so the synced expense must be counted once
        $this->assertEquals(2, $summary['count']);
        $this->assertEquals(3700.00, $summary['total_amount']);
        $this->assertEquals(3700.00, $summary['paid']);
        $this->assertEquals(0.00, $summary['unpaid']);

        $response->assertSee('Site Bricks Purchase');
        $response->assertSee('Paid Labour Salary to Suresh for Site');
    }

    public function test_office_report_does_not_double_count_unassigned_labour_salary_and_expense(): void
    {
        $admin = $this->createAdminUser(true);

        $labour = Labour::create([
            'name' => 'Ganesh Office Cleaner',
            'salary' => 600.00,
        ]);

        $labourSalary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 3000.00,
            'paid_amount' => 3000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-21',
            'status' => 'paid',
        ]);

        // Synced unassigned expense (project_id is null)
        Expense::create([
            'project_id' => null,
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 3000.00,
            'paid_amt' => 3000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-21',
            'description' => 'Paid Labour Salary to Ganesh',
            'source_type' => 'labour_salary',
            'source_id' => $labourSalary->id,
        ]);

        // Ordinary office expense
        Expense::create([
            'project_id' => null,
            'user_id' => $admin->id,
            'amount' => 800.00,
            'paid_amt' => 800.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-21',
            'description' => 'Office Electricity Bill',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index', ['type' => 'office']));
        $response->assertOk();

        $summary = $response->viewData('summary');
        // Must count 2 entries (1 office expense + 1 labour salary), NOT 3!
        $this->assertEquals(2, $summary['count']);
        $this->assertEquals(3800.00, $summary['total_amount']);
        $this->assertEquals(3800.00, $summary['paid']);
        $this->assertEquals(0.00, $summary['unpaid']);
    }

    public function test_total_report_date_filtering_with_labour_salaries_and_expenses(): void
    {
        $admin = $this->createAdminUser(true);

        $labour = Labour::create([
            'name' => 'Mahesh Painter',
            'salary' => 750.00,
        ]);

        // In range: August 2026
        $inRangeSalary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-08-01',
            'salary_period_end' => '2026-08-15',
            'salary_amount' => 2000.00,
            'paid_amount' => 2000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-08-15',
            'status' => 'paid',
        ]);

        Expense::create([
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 2000.00,
            'paid_amt' => 2000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-08-15',
            'description' => 'Paid Labour Salary to Mahesh Aug',
            'source_type' => 'labour_salary',
            'source_id' => $inRangeSalary->id,
        ]);

        Expense::create([
            'user_id' => $admin->id,
            'amount' => 1000.00,
            'paid_amt' => 1000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-08-15',
            'description' => 'August Maintenance',
        ]);

        // Out of range: January 2026
        $outRangeSalary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-01-01',
            'salary_period_end' => '2026-01-15',
            'salary_amount' => 5000.00,
            'paid_amount' => 5000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-01-10',
            'status' => 'paid',
        ]);

        Expense::create([
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 5000.00,
            'paid_amt' => 5000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-01-10',
            'description' => 'Paid Labour Salary to Mahesh Jan',
            'source_type' => 'labour_salary',
            'source_id' => $outRangeSalary->id,
        ]);

        Expense::create([
            'user_id' => $admin->id,
            'amount' => 3000.00,
            'paid_amt' => 3000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-01-10',
            'description' => 'January Maintenance',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'type' => 'total',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]));
        $response->assertOk();

        $summary = $response->viewData('summary');
        // Only August records should be counted (1 labour salary + 1 ordinary expense = 2)
        $this->assertEquals(2, $summary['count']);
        $this->assertEquals(3000.00, $summary['total_amount']);
        $this->assertEquals(3000.00, $summary['paid']);
        $this->assertEquals(0.00, $summary['unpaid']);
    }

    public function test_project_linked_labour_salary_appears_in_site_and_total_but_not_office_report(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Skyline Heights']);

        $labour = Labour::create([
            'name' => 'Karan Site Worker',
            'salary' => 900.00,
        ]);

        $labourSalary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-10-01',
            'salary_period_end' => '2026-10-15',
            'salary_amount' => 4500.00,
            'paid_amount' => 4500.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-20',
            'status' => 'paid',
        ]);

        Expense::create([
            'project_id' => $project->id,
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 4500.00,
            'paid_amt' => 4500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-20',
            'description' => 'Paid Labour Salary to Karan',
            'source_type' => 'labour_salary',
            'source_id' => $labourSalary->id,
        ]);

        // 1. Site Report: must include the project labour expense
        $responseSite = $this->actingAs($admin)->get(route('reports.index', [
            'type' => 'site',
            'project_id' => $project->id,
        ]));
        $responseSite->assertOk();
        $this->assertEquals(1, $responseSite->viewData('summary')['count']);
        $this->assertEquals(4500.00, $responseSite->viewData('summary')['paid']);

        // 2. Office Report: must EXCLUDE the site-linked labour salary
        $responseOffice = $this->actingAs($admin)->get(route('reports.index', ['type' => 'office']));
        $responseOffice->assertOk();
        $this->assertEquals(0, $responseOffice->viewData('summary')['count']);
        $this->assertEquals(0.00, $responseOffice->viewData('summary')['paid']);

        // 3. Total Report: must include the salary once with project attribution
        $responseTotal = $this->actingAs($admin)->get(route('reports.index', ['type' => 'total']));
        $responseTotal->assertOk();
        $this->assertEquals(1, $responseTotal->viewData('summary')['count']);
        $this->assertEquals(4500.00, $responseTotal->viewData('summary')['paid']);
        $records = $responseTotal->viewData('records');
        $this->assertCount(1, $records->items());
        $firstRow = $records->items()[0];
        $projectName = is_array($firstRow) ? $firstRow['project_name'] : $firstRow->project_name;
        $this->assertEquals('Skyline Heights', $projectName);
    }

    public function test_site_and_office_reports_reconcile_with_total_report(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Metro Station']);

        // Ordinary Office Expense: 1500
        Expense::create([
            'project_id' => null,
            'user_id' => $admin->id,
            'amount' => 1500.00,
            'paid_amt' => 1500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-25',
            'description' => 'HQ Internet Bill',
        ]);

        // Ordinary Site Expense (Metro Station): 2500
        Expense::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'amount' => 2500.00,
            'paid_amt' => 2500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-25',
            'description' => 'Steel Rods Metro',
        ]);

        // Site-linked Labour Salary: 6000
        $siteLabour = Labour::create(['name' => 'Site Worker Jagan', 'salary' => 800.00]);
        $siteSalary = LabourSalary::create([
            'labour_id' => $siteLabour->id,
            'salary_amount' => 6000.00,
            'paid_amount' => 6000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-25',
            'status' => 'paid',
        ]);
        Expense::create([
            'project_id' => $project->id,
            'labour_id' => $siteLabour->id,
            'user_id' => $admin->id,
            'amount' => 6000.00,
            'paid_amt' => 6000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-25',
            'source_type' => 'labour_salary',
            'source_id' => $siteSalary->id,
        ]);

        // Office / Unassigned Labour Salary: 3500
        $officeLabour = Labour::create(['name' => 'Office Cleaner Mary', 'salary' => 500.00]);
        $officeSalary = LabourSalary::create([
            'labour_id' => $officeLabour->id,
            'salary_amount' => 3500.00,
            'paid_amount' => 3500.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-25',
            'status' => 'paid',
        ]);
        Expense::create([
            'project_id' => null,
            'labour_id' => $officeLabour->id,
            'user_id' => $admin->id,
            'amount' => 3500.00,
            'paid_amt' => 3500.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-25',
            'source_type' => 'labour_salary',
            'source_id' => $officeSalary->id,
        ]);

        $siteSummary = $this->actingAs($admin)->get(route('reports.index', ['type' => 'site']))->viewData('summary');
        $officeSummary = $this->actingAs($admin)->get(route('reports.index', ['type' => 'office']))->viewData('summary');
        $totalSummary = $this->actingAs($admin)->get(route('reports.index', ['type' => 'total']))->viewData('summary');

        // Site: 2500 (Steel) + 6000 (Site Labour) = 8500 across 2 entries
        $this->assertEquals(2, $siteSummary['count']);
        $this->assertEquals(8500.00, $siteSummary['paid']);

        // Office: 1500 (Internet) + 3500 (Cleaner) = 5000 across 2 entries
        $this->assertEquals(2, $officeSummary['count']);
        $this->assertEquals(5000.00, $officeSummary['paid']);

        // Total: 8500 (Site) + 5000 (Office) = 13500 across 4 entries
        $this->assertEquals(4, $totalSummary['count']);
        $this->assertEquals(13500.00, $totalSummary['paid']);

        // Exact reconciliation: Site + Office == Total
        $this->assertEquals($totalSummary['paid'], $siteSummary['paid'] + $officeSummary['paid']);
        $this->assertEquals($totalSummary['count'], $siteSummary['count'] + $officeSummary['count']);
    }

    public function test_total_report_scopes_labour_salaries_when_filtered_by_project(): void
    {
        $admin = $this->createAdminUser(true);
        $projectA = Project::factory()->create(['name' => 'Project Alpha']);
        $projectB = Project::factory()->create(['name' => 'Project Beta']);

        $labourA = Labour::create(['name' => 'Alpha Worker', 'salary' => 700.00]);
        $salaryA = LabourSalary::create([
            'labour_id' => $labourA->id,
            'salary_amount' => 4000.00,
            'paid_amount' => 4000.00,
            'payment_date' => '2026-10-26',
            'status' => 'paid',
        ]);
        Expense::create([
            'project_id' => $projectA->id,
            'labour_id' => $labourA->id,
            'user_id' => $admin->id,
            'amount' => 4000.00,
            'paid_amt' => 4000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-26',
            'source_type' => 'labour_salary',
            'source_id' => $salaryA->id,
        ]);

        $labourB = Labour::create(['name' => 'Beta Worker', 'salary' => 700.00]);
        $salaryB = LabourSalary::create([
            'labour_id' => $labourB->id,
            'salary_amount' => 7000.00,
            'paid_amount' => 7000.00,
            'payment_date' => '2026-10-26',
            'status' => 'paid',
        ]);
        Expense::create([
            'project_id' => $projectB->id,
            'labour_id' => $labourB->id,
            'user_id' => $admin->id,
            'amount' => 7000.00,
            'paid_amt' => 7000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-26',
            'source_type' => 'labour_salary',
            'source_id' => $salaryB->id,
        ]);

        // Filter Total Report by Project Alpha only
        $response = $this->actingAs($admin)->get(route('reports.index', [
            'type' => 'total',
            'project_id' => $projectA->id,
        ]));
        $response->assertOk();

        $summary = $response->viewData('summary');
        // Only Alpha's salary (4000) should be included; Beta's (7000) must be excluded
        $this->assertEquals(1, $summary['count']);
        $this->assertEquals(4000.00, $summary['paid']);

        $records = $response->viewData('records');
        $this->assertCount(1, $records->items());
        $firstRow = $records->items()[0];
        $projectName = is_array($firstRow) ? $firstRow['project_name'] : $firstRow->project_name;
        $this->assertEquals('Project Alpha', $projectName);
    }

    public function test_missing_or_soft_deleted_linked_expense_does_not_cause_double_counting(): void
    {
        $admin = $this->createAdminUser(true);
        $project = Project::factory()->create(['name' => 'Harbor Bridge']);

        $labour = Labour::create(['name' => 'Bridge Worker Dev', 'salary' => 850.00]);
        $salary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_amount' => 5000.00,
            'paid_amount' => 5000.00,
            'remaining_amount' => 0.00,
            'payment_date' => '2026-10-26',
            'status' => 'paid',
        ]);

        $expense = Expense::create([
            'project_id' => $project->id,
            'labour_id' => $labour->id,
            'user_id' => $admin->id,
            'amount' => 5000.00,
            'paid_amt' => 5000.00,
            'unpaid_amt' => 0.00,
            'current_date' => '2026-10-26',
            'source_type' => 'labour_salary',
            'source_id' => $salary->id,
        ]);

        // Now soft-delete the linked expense
        $expense->delete();

        // 1. Site Report: must NOT show the soft-deleted expense
        $siteSummary = $this->actingAs($admin)->get(route('reports.index', [
            'type' => 'site',
            'project_id' => $project->id,
        ]))->viewData('summary');
        $this->assertEquals(0, $siteSummary['count']);
        $this->assertEquals(0.00, $siteSummary['paid']);

        // 2. Office Report: treats the salary as general/unassigned overhead because no active project expense exists
        $officeSummary = $this->actingAs($admin)->get(route('reports.index', ['type' => 'office']))->viewData('summary');
        $this->assertEquals(1, $officeSummary['count']);
        $this->assertEquals(5000.00, $officeSummary['paid']);

        // 3. Total Report: counts the salary exactly once
        $totalSummary = $this->actingAs($admin)->get(route('reports.index', ['type' => 'total']))->viewData('summary');
        $this->assertEquals(1, $totalSummary['count']);
        $this->assertEquals(5000.00, $totalSummary['paid']);

        // Zero double-counting across Site + Office
        $this->assertEquals(5000.00, $siteSummary['paid'] + $officeSummary['paid']);
    }
}
