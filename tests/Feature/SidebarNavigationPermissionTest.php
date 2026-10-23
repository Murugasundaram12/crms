<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SidebarNavigationPermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');
    }

    /**
     * Helper to create an employee user with only the specified permissions.
     */
    private function createUserWithPermissions(array $permissionKeys): User
    {
        $user = User::factory()->create([
            'role' => 'Employee',
            'status' => 'active',
        ]);

        if (! empty($permissionKeys)) {
            $role = Role::query()->create([
                'name' => 'Test Role ' . uniqid(),
            ]);

            $permissionIds = [];
            foreach ($permissionKeys as $key) {
                $permission = Permission::query()->firstOrCreate(
                    ['key' => $key],
                    ['name' => ucwords(str_replace(['-', '_'], ' ', $key))]
                );
                $permissionIds[] = $permission->id;
            }

            $role->permissions()->sync($permissionIds);
            $user->roles()->sync([$role->id]);
        }

        return $user->fresh();
    }

    /**
     * F-01: A user with transfers-list only must see Transfer History and Wallet History
     * under the History menu, but MUST NOT see Vendor History (which requires expenses-list).
     */
    public function test_transfers_user_sees_transfer_and_wallet_history_but_not_vendor_history(): void
    {
        $user = $this->createUserWithPermissions(['transfers-list']);
        $this->actingAs($user);

        $html = view('partials.sidebar')->render();

        // Must see Transfer History and Wallet History
        $this->assertStringContainsString(route('transfers.index'), $html);
        $this->assertStringContainsString('Transfer History', $html);
        $this->assertStringContainsString(route('wallet.index'), $html);
        $this->assertStringContainsString('Wallet History', $html);

        // Must NOT see Vendor History under History or anywhere in the sidebar
        $this->assertStringNotContainsString(route('vendor-expenses.history'), $html);
        $this->assertStringNotContainsString('Vendor History', $html);
        $this->assertStringNotContainsString('Vendor Expenses', $html);
    }

    /**
     * F-01: A user lacking expenses-list who attempts direct access to /vendor-expenses
     * must be redirected with an authorization error.
     */
    public function test_user_without_expenses_permission_is_blocked_from_vendor_expenses_route(): void
    {
        $user = $this->createUserWithPermissions(['transfers-list']);

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('vendor-expenses.history'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'You do not have permission to access this module.');
    }

    /**
     * F-02: A user with expense-reports-list only must see Expense Reports inside the Reports menu,
     * but must NOT see Client Summary, Payment Summary, or Total Report (which require reports-list).
     */
    public function test_expense_reporter_only_sees_expense_reports_link_under_reports_menu(): void
    {
        $user = $this->createUserWithPermissions(['expense-reports-list']);
        $this->actingAs($user);

        $html = view('partials.sidebar')->render();

        // Reports menu should be present
        $this->assertStringContainsString('ti-report-analytics', $html);
        $this->assertStringContainsString('<span>Reports</span>', $html);

        // Expense Reports link must be visible
        $this->assertStringContainsString(route('expenseReports.index'), $html);
        $this->assertStringContainsString('Expense Reports', $html);

        // General reports requiring reports-list must NOT be visible
        $this->assertStringNotContainsString(route('reports.index', ['type' => 'site']), $html);
        $this->assertStringNotContainsString('Client Summary', $html);
        $this->assertStringNotContainsString(route('reports.index', ['type' => 'office']), $html);
        $this->assertStringNotContainsString('Payment Summary', $html);
        $this->assertStringNotContainsString(route('reports.index', ['type' => 'total']), $html);
        $this->assertStringNotContainsString('Total Report', $html);
    }

    /**
     * F-02: A user with reports-list only must see Client Summary, Payment Summary, and Total Report,
     * but must NOT see Expense Reports.
     */
    public function test_general_reporter_only_sees_general_reports_under_reports_menu(): void
    {
        $user = $this->createUserWithPermissions(['reports-list']);
        $this->actingAs($user);

        $html = view('partials.sidebar')->render();

        // Reports menu should be present
        $this->assertStringContainsString('ti-report-analytics', $html);
        $this->assertStringContainsString('<span>Reports</span>', $html);

        // General reports must be visible
        $this->assertStringContainsString(route('reports.index', ['type' => 'site']), $html);
        $this->assertStringContainsString('Client Summary', $html);
        $this->assertStringContainsString(route('reports.index', ['type' => 'office']), $html);
        $this->assertStringContainsString('Payment Summary', $html);
        $this->assertStringContainsString(route('reports.index', ['type' => 'total']), $html);
        $this->assertStringContainsString('Total Report', $html);

        // Expense Reports must NOT be visible
        $this->assertStringNotContainsString(route('expenseReports.index'), $html);
        $this->assertStringNotContainsString('Expense Reports', $html);
    }

    /**
     * F-02: A user with BOTH reports-list and expense-reports-list must see all 4 report links.
     */
    public function test_user_with_both_reports_permissions_sees_all_four_report_links(): void
    {
        $user = $this->createUserWithPermissions(['reports-list', 'expense-reports-list']);
        $this->actingAs($user);

        $html = view('partials.sidebar')->render();

        // All 4 links must be visible
        $this->assertStringContainsString(route('reports.index', ['type' => 'site']), $html);
        $this->assertStringContainsString('Client Summary', $html);
        $this->assertStringContainsString(route('reports.index', ['type' => 'office']), $html);
        $this->assertStringContainsString('Payment Summary', $html);
        $this->assertStringContainsString(route('reports.index', ['type' => 'total']), $html);
        $this->assertStringContainsString('Total Report', $html);
        $this->assertStringContainsString(route('expenseReports.index'), $html);
        $this->assertStringContainsString('Expense Reports', $html);
    }

    /**
     * Users without any reports permissions must not see the Reports menu at all.
     */
    public function test_user_without_any_reports_permission_does_not_see_reports_menu(): void
    {
        $user = $this->createUserWithPermissions(['transfers-list']);
        $this->actingAs($user);

        $html = view('partials.sidebar')->render();

        // Reports menu should NOT be present
        $this->assertStringNotContainsString('ti-report-analytics', $html);
        $this->assertStringNotContainsString('route(\'reports.index\')', $html);
        $this->assertStringNotContainsString(route('expenseReports.index'), $html);
    }

    /**
     * Direct route access authorization check:
     * - An unauthorized user without expense-reports-list is blocked from /expense-reports
     * - An unauthorized user without reports-list or expense-reports-list is blocked from /reports with HTTP 403
     */
    public function test_direct_route_authorization_for_reports(): void
    {
        $userWithoutReports = $this->createUserWithPermissions(['transfers-list']);

        // CheckPermission middleware on expense-reports
        $this->actingAs($userWithoutReports)
            ->from(route('dashboard'))
            ->get(route('expenseReports.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access this module.');

        // ReportController index abort_unless on reports.index
        $this->actingAs($userWithoutReports)
            ->get(route('reports.index'))
            ->assertStatus(403);
    }
}
