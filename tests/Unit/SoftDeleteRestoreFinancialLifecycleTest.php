<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use App\Models\Category;
use App\Http\Controllers\ExpensesController;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteRestoreFinancialLifecycleTest extends TestCase
{
    use RefreshDatabase;
    public function test_complete_expense_soft_delete_and_restore_financial_lifecycle(): void
    {
        $user = User::find(1);
        $project = Project::find(1);
        $category = Category::first();

        if (! $user || ! $project || ! $category) {
            $this->assertTrue(true);
            return;
        }

        Auth::login($user);

        $initialWallet = (float) DB::table('users')->where('id', $user->id)->value('wallet');
        $initialSpent = (float) $project->spent;

        $decimalAmount = 1250.50;

        // 1. Create Expense
        $expense = Expense::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'category_id' => $category->id,
            'amount' => $decimalAmount,
            'paid_amt' => $decimalAmount,
            'unpaid_amt' => 0.00,
            'extra_amt' => 0.00,
            'current_date' => now(),
            'description' => 'Lifecycle Decimal Test',
            'source_type' => 'lifecycle_test',
            'source_id' => 888888,
        ]);

        app(CrmBalanceService::class)->replaceUserWalletDebit(
            null,
            0,
            $user->id,
            $decimalAmount,
            'Created test expense',
            'expense',
            $expense->id
        );

        $walletAfterCreate = (float) DB::table('users')->where('id', $user->id)->value('wallet');
        $projectFresh = Project::find($project->id);

        $this->assertEquals($initialWallet - 1250.50, $walletAfterCreate);
        $this->assertEquals($initialSpent + 1250.50, (float) $projectFresh->spent);

        // 2. Edit Expense Amount to 1500.25 (No Double Debit)
        $newAmount = 1500.25;
        $expense->update([
            'amount' => $newAmount,
            'paid_amt' => $newAmount,
        ]);

        app(CrmBalanceService::class)->replaceUserWalletDebit(
            $user->id,
            1250.50,
            $user->id,
            $newAmount,
            'Updated test expense',
            'expense',
            $expense->id
        );

        $walletAfterEdit = (float) DB::table('users')->where('id', $user->id)->value('wallet');
        $projectFresh2 = Project::find($project->id);

        $this->assertEquals($initialWallet - 1500.25, $walletAfterEdit);
        $this->assertEquals($initialSpent + 1500.25, (float) $projectFresh2->spent);

        // 3. Delete Expense (Refund Wallet, Exclude from Project Spent)
        $controller = new ExpensesController();
        $deleteRequest = new Request([
            'expense_id' => $expense->id,
            'delete_reason' => 'Testing deletion',
        ]);

        $controller->deleteRecord($deleteRequest);

        $walletAfterDelete = (float) DB::table('users')->where('id', $user->id)->value('wallet');
        $projectFresh3 = Project::find($project->id);

        $this->assertEquals($initialWallet, $walletAfterDelete);
        $this->assertEquals($initialSpent, (float) $projectFresh3->spent);
        $this->assertTrue(Expense::onlyTrashed()->where('id', $expense->id)->exists());

        // 4. Restore Expense (Restore Wallet Debit, Include in Project Spent)
        $restoreRequest = new Request([
            'expense_id' => $expense->id,
        ]);

        $controller->restoreRecord($restoreRequest);

        $walletAfterRestore = (float) DB::table('users')->where('id', $user->id)->value('wallet');
        $projectFresh4 = Project::find($project->id);

        $this->assertEquals($initialWallet - 1500.25, $walletAfterRestore);
        $this->assertEquals($initialSpent + 1500.25, (float) $projectFresh4->spent);
        $this->assertTrue(Expense::where('id', $expense->id)->whereNull('deleted_at')->exists());

        // Cleanup: delete and forceDelete test expense and revert wallet
        app(CrmBalanceService::class)->replaceUserWalletDebit(
            $user->id,
            1500.25,
            null,
            0,
            'Cleanup test expense',
            'expense',
            $expense->id
        );

        $expense->forceDelete();
    }
}
