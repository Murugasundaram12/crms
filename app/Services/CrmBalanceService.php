<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmBalanceService
{
    public function debitUserWallet(int $userId, float $amount, string $description, string $referenceType, int $referenceId): void
    {
        $this->adjustColumn('users', $userId, 'wallet', -$amount);
    }

    public function creditUserWallet(int $userId, float $amount, string $description, string $referenceType, int $referenceId): void
    {
        $this->adjustColumn('users', $userId, 'wallet', $amount);
    }

    public function adjustEmployeeWallet(int $employeeId, float $amount): void
    {
        $this->adjustColumn('employees', $employeeId, 'wallet', $amount);
    }

    public function adjustUserWallet(int $userId, float $amount): void
    {
        $this->adjustColumn('users', $userId, 'wallet', $amount);
    }

    public function adjustVendorAdvance(int $vendorId, float $amount): void
    {
        $this->adjustColumn('vendors', $vendorId, 'advance_amt', $amount);
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
