<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmBalanceService
{
    public function debitUserWallet(int $userId, float $amount, string $description, string $referenceType, int $referenceId): void
    {
        if ($amount == 0.0 || ! Schema::hasColumn('users', 'wallet')) {
            return;
        }

        $wallet = (float) DB::table('users')
            ->where('id', $userId)
            ->lockForUpdate()
            ->value('wallet');

        if ($wallet < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient wallet balance.',
                'paid_amt' => 'Insufficient wallet balance.',
                'paid_amount' => 'Insufficient wallet balance.',
            ]);
        }

        DB::table('users')->where('id', $userId)->update([
            'wallet' => $wallet - $amount,
        ]);

        $this->syncEmployeeWalletFromUser($userId);
    }

    public function creditUserWallet(int $userId, float $amount, string $description, string $referenceType, int $referenceId): void
    {
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

    private function syncEmployeeWalletFromUser(int $userId): void
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

    private function userIdFromEmployeeId(int $employeeId): ?int
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
