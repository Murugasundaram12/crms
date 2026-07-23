<?php

namespace App\Services;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmBalanceService
{
    public function recordWalletTransaction(
        int $userId,
        float $amount,
        string $direction, // 'credit' or 'debit'
        string $sourceType,
        int $sourceId,
        ?int $paymentMethodId = null,
        string $description = '',
        ?int $createdBy = null,
        ?int $projectId = null,
        ?int $clientId = null
    ): void {
        if ($amount <= 0.0) {
            return;
        }

        $transferType = strtolower($direction) === 'credit' ? 0 : 1;

        if ($transferType === 1) {
            $this->debitUserWallet($userId, $amount, $description, $sourceType, $sourceId);
        } else {
            $this->creditUserWallet($userId, $amount, $description, $sourceType, $sourceId);
        }

        if (Schema::hasTable('wallet')) {
            Wallet::query()->create([
                'user_id' => $userId,
                'client_id' => $clientId,
                'project_id' => $projectId,
                'amount' => (int) round($amount),
                'payment_mode' => $paymentMethodId ?? 1,
                'payment_method_id' => $paymentMethodId,
                'transfer_type' => $transferType,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'created_by' => $createdBy,
                'current_date' => now(),
                'active_status' => 1,
                'delete_status' => 0,
            ]);
        }
    }

    public function debitUserWallet(int $userId, float $amount, string $description = '', string $referenceType = 'wallet', int $referenceId = 0): void
    {
        if ($amount <= 0.0 || ! Schema::hasColumn('users', 'wallet')) {
            return;
        }

        $wallet = (float) DB::table('users')
            ->where('id', $userId)
            ->lockForUpdate()
            ->value('wallet');

        if ($wallet < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient wallet balance. Available balance is Rs ' . number_format($wallet, 2) . '.',
                'paid_amt' => 'Insufficient wallet balance. Available balance is Rs ' . number_format($wallet, 2) . '.',
                'paid_amount' => 'Insufficient wallet balance. Available balance is Rs ' . number_format($wallet, 2) . '.',
            ]);
        }

        DB::table('users')->where('id', $userId)->update([
            'wallet' => $wallet - $amount,
        ]);

        $this->syncEmployeeWalletFromUser($userId);
    }

    public function creditUserWallet(int $userId, float $amount, string $description = '', string $referenceType = 'wallet', int $referenceId = 0): void
    {
        if ($amount <= 0.0) {
            return;
        }

        $this->adjustColumn('users', $userId, 'wallet', $amount);
        $this->syncEmployeeWalletFromUser($userId);
    }

    public function adjustEmployeeWallet(int $employeeId, float $amount): void
    {
        $userId = $this->userIdFromEmployeeId($employeeId);

        if ($userId) {
            $this->adjustUserWallet($userId, $amount);
            return;
        }

        $this->adjustColumn('employees', $employeeId, 'wallet', $amount);
    }

    public function adjustUserWallet(int $userId, float $amount): void
    {
        if ($amount < 0) {
            $this->debitUserWallet($userId, abs($amount), 'Wallet debit', 'wallet', 0);
            return;
        }

        $this->adjustColumn('users', $userId, 'wallet', $amount);
        $this->syncEmployeeWalletFromUser($userId);
    }

    public function syncEmployeeWalletFromUser(int $userId): void
    {
        if (! Schema::hasColumn('users', 'wallet') || ! Schema::hasColumn('employees', 'wallet')) {
            return;
        }

        $user = DB::table('users')->where('id', $userId)->first(['id', 'email', 'wallet']);

        if (! $user) {
            return;
        }

        DB::table('employees')
            ->where('id', $user->id)
            ->when($user->email, fn($query) => $query->orWhere('email', $user->email))
            ->update(['wallet' => $user->wallet]);
    }

    public function userIdFromEmployeeId(int $employeeId): ?int
    {
        if (! Schema::hasTable('employees')) {
            return null;
        }

        $employee = DB::table('employees')->where('id', $employeeId)->first(['id', 'email']);

        if (! $employee) {
            return null;
        }

        $user = DB::table('users')
            ->where('id', $employee->id)
            ->when($employee->email, fn($query) => $query->orWhere('email', $employee->email))
            ->first(['id']);

        return $user ? (int) $user->id : null;
    }

    public function adjustVendorAdvance(int $vendorId, float $amount): void
    {
        $this->adjustColumn('vendors', $vendorId, 'advance_amt', $amount);
        $this->adjustColumn('vendors', $vendorId, 'advance_amount', $amount);
    }

    public function adjustLabourAdvance(int $labourId, float $amount): void
    {
        $this->adjustColumn('labours', $labourId, 'advance_amt', $amount);
    }

    public function applyProjectIncome(int $projectId, float $amount): void
    {
        $this->adjustColumn('projects', $projectId, 'advance_amt', $amount);
        $this->adjustColumn('projects', $projectId, 'profit', -$amount);
    }

    public function reverseProjectIncome(int $projectId, float $amount): void
    {
        $this->adjustColumn('projects', $projectId, 'advance_amt', -$amount);
        $this->adjustColumn('projects', $projectId, 'profit', $amount);
    }

    private function adjustColumn(string $table, int $id, string $column, float $delta): void
    {
        if ($delta == 0.0 || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->where('id', $id)->update([
            $column => DB::raw($column . ' + ' . $delta),
        ]);
    }
}
