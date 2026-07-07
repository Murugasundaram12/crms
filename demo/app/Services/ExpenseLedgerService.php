<?php

namespace App\Services;

use App\Models\AdvanceHistory;
use App\Models\Expenses;
use App\Models\ExpensesUnpaidDate;
use App\Models\Labour;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ExpenseAmounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseLedgerService
{
    public static function totals($query): object
    {
        return (clone $query)->select([])->reorder()->selectRaw(
            'COALESCE(SUM(expenses.amount), 0) as total_amount, COALESCE(SUM(expenses.paid_amt), 0) as total_paid, COALESCE(SUM(expenses.unpaid_amt), 0) as total_unpaid, COALESCE(SUM(expenses.extra_amt), 0) as total_extra'
        )->first();
    }

    public function update(int $expenseId, array $attributes): Expenses
    {
        return DB::transaction(function () use ($expenseId, $attributes) {
            $expense = Expenses::whereKey($expenseId)->lockForUpdate()->firstOrFail();
            $oldPaid = (float) $expense->paid_amt;
            $oldExtra = (float) $expense->extra_amt;
            $newPaid = (float) $attributes['paid_amt'];
            $amount = (float) $attributes['amount'];
            $paidDelta = $newPaid - $oldPaid;
            $amounts = ExpenseAmounts::calculate($amount, $newPaid);
            $newExtra = $amounts['extra_amt'];

            if ($expense->vendor_id) {
                $account = Vendor::whereKey($expense->vendor_id)->lockForUpdate()->firstOrFail();
                $this->applyDebit($account, 'advance_amt', $paidDelta, 'Insufficient vendor advance balance.');
            } else {
                $account = User::whereKey($expense->user_id)->lockForUpdate()->firstOrFail();
                $this->applyDebit($account, 'wallet', $paidDelta, 'Insufficient wallet balance.');
            }

            if ($expense->labour_id) {
                $labour = Labour::whereKey($expense->labour_id)->lockForUpdate()->firstOrFail();
                $nextAdvance = (float) $labour->advance_amt + ($newExtra - $oldExtra);
                if ($nextAdvance < 0) {
                    throw ValidationException::withMessages([
                        'paid_amt' => 'This labour advance has already been used and cannot be reduced.',
                    ]);
                }
                $labour->advance_amt = $nextAdvance;
                $labour->save();
            }

            $attributes = array_merge($attributes, $amounts);
            $attributes['editedBy'] = Auth::id();
            $expense->update($attributes);

            return $expense;
        });
    }

    public function settle(int $expenseId, float $payment, string $dateTime): Expenses
    {
        return DB::transaction(function () use ($expenseId, $payment, $dateTime) {
            $expense = Expenses::whereKey($expenseId)->lockForUpdate()->firstOrFail();
            $oldExtra = (float) $expense->extra_amt;
            $newPaid = (float) $expense->paid_amt + $payment;
            $amounts = ExpenseAmounts::calculate((float) $expense->amount, $newPaid);
            $newExtra = $amounts['extra_amt'];

            if ($expense->vendor_id) {
                $account = Vendor::whereKey($expense->vendor_id)->lockForUpdate()->firstOrFail();
                $this->applyDebit($account, 'advance_amt', $payment, 'Insufficient vendor advance balance.');
            } else {
                $account = User::whereKey($expense->user_id)->lockForUpdate()->firstOrFail();
                $this->applyDebit($account, 'wallet', $payment, 'Insufficient wallet balance.');
            }

            if ($expense->labour_id && $newExtra !== $oldExtra) {
                $labour = Labour::whereKey($expense->labour_id)->lockForUpdate()->firstOrFail();
                $labour->advance_amt = (float) $labour->advance_amt + ($newExtra - $oldExtra);
                $labour->save();
            }

            ExpensesUnpaidDate::create([
                'expense_id' => $expense->id,
                'amount' => $payment,
                'unpaid_amt' => $payment,
                'current_date' => $dateTime,
            ]);

            $expense->paid_amt = $amounts['paid_amt'];
            $expense->unpaid_amt = $amounts['unpaid_amt'];
            $expense->extra_amt = $amounts['extra_amt'];
            $expense->save();

            return $expense;
        });
    }

    public function delete(int $expenseId, string $reason): void
    {
        DB::transaction(function () use ($expenseId, $reason) {
            $expense = Expenses::whereKey($expenseId)->lockForUpdate()->firstOrFail();

            if ($expense->vendor_id) {
                $vendor = Vendor::whereKey($expense->vendor_id)->lockForUpdate()->firstOrFail();
                $vendor->advance_amt = (float) $vendor->advance_amt + (float) $expense->paid_amt;
                $vendor->save();
            } else {
                $user = User::whereKey($expense->user_id)->lockForUpdate()->firstOrFail();
                $user->wallet = (float) $user->wallet + (float) $expense->paid_amt;
                $user->save();
            }

            if ($expense->labour_id && (float) $expense->extra_amt > 0) {
                $labour = Labour::whereKey($expense->labour_id)->lockForUpdate()->firstOrFail();
                if ((float) $labour->advance_amt < (float) $expense->extra_amt) {
                    throw ValidationException::withMessages([
                        'id' => 'This labour advance has already been used; reverse its settlement before deleting.',
                    ]);
                }
                $labour->advance_amt = (float) $labour->advance_amt - (float) $expense->extra_amt;
                $labour->save();
            }

            $expense->reason = $reason;
            $expense->save();
            $expense->delete();
        });
    }

    public function deleteMany(array $expenseIds, string $reason): void
    {
        DB::transaction(function () use ($expenseIds, $reason) {
            foreach (array_unique($expenseIds) as $expenseId) {
                $this->delete((int) $expenseId, $reason);
            }
        });
    }

    public function settleLabourBulk(
        int $labourId,
        array $projectIds,
        string $startDate,
        string $endDate
    ): void {
        DB::transaction(function () use ($labourId, $projectIds, $startDate, $endDate) {
            $expenses = Expenses::where('labour_id', $labourId)
                ->whereIn('project_id', $projectIds)
                ->whereBetween('current_date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
                ->where('unpaid_amt', '>', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $total = (float) $expenses->sum('unpaid_amt');
            $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
            if ((float) $user->wallet < $total) {
                throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance.']);
            }

            foreach ($expenses as $expense) {
                $payment = (float) $expense->unpaid_amt;
                $expense->paid_amt = (float) $expense->paid_amt + $payment;
                $expense->unpaid_amt = 0;
                $expense->save();
                ExpensesUnpaidDate::create([
                    'expense_id' => $expense->id,
                    'amount' => $payment,
                    'unpaid_amt' => $payment,
                    'current_date' => now(),
                    'description' => 'Labour bulk settlement',
                ]);
            }

            $user->wallet = (float) $user->wallet - $total;
            $user->save();
        });
    }

    public function adjustAdvance(
        string $type,
        int $entityId,
        int $projectId,
        float $amount,
        bool $settleUnpaid = true
    ): void {
        DB::transaction(function () use ($type, $entityId, $projectId, $amount, $settleUnpaid) {
            $isVendor = $type === 'vendor';
            $entity = $isVendor
                ? Vendor::whereKey($entityId)->lockForUpdate()->firstOrFail()
                : Labour::whereKey($entityId)->lockForUpdate()->firstOrFail();
            $foreignKey = $isVendor ? 'vendor_id' : 'labour_id';

            if ((float) $entity->advance_amt < $amount) {
                throw ValidationException::withMessages(['extra_amt' => 'Insufficient advance balance.']);
            }

            if ($settleUnpaid) {
                $pending = Expenses::where($foreignKey, $entityId)
                    ->where('project_id', $projectId)
                    ->where('unpaid_amt', '>', 0)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if ((float) $pending->sum('unpaid_amt') < $amount) {
                    throw ValidationException::withMessages(['extra_amt' => 'Amount exceeds project unpaid balance.']);
                }

                $remaining = $amount;
                foreach ($pending as $expense) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $applied = min($remaining, (float) $expense->unpaid_amt);
                    $expense->unpaid_amt = (float) $expense->unpaid_amt - $applied;
                    $expense->paid_amt = (float) $expense->paid_amt + $applied;
                    $expense->is_advance = Auth::id();
                    $expense->save();
                    AdvanceHistory::create([
                        $isVendor ? 'vendor_id' : 'labour_id' => $entityId,
                        'expense_id' => $expense->id,
                        'amount' => $applied,
                        'date' => now(),
                    ]);
                    $remaining -= $applied;
                }
            } else {
                $available = (float) Expenses::where($foreignKey, $entityId)
                    ->where('project_id', $projectId)
                    ->sum('extra_amt');
                if ($available < $amount) {
                    throw ValidationException::withMessages(['extra_amt' => 'Amount exceeds project advance.']);
                }
            }

            $this->consumeExtra($foreignKey, $entityId, $amount, $settleUnpaid ? null : $projectId);
            $entity->advance_amt = (float) $entity->advance_amt - $amount;
            $entity->save();
        });
    }

    public function withdrawVendor(int $vendorId, int $memberId, float $amount): void
    {
        DB::transaction(function () use ($vendorId, $memberId, $amount) {
            $vendor = Vendor::whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($memberId)->lockForUpdate()->firstOrFail();
            if ((float) $vendor->advance_amt < $amount) {
                throw ValidationException::withMessages(['amount' => 'Insufficient vendor advance balance.']);
            }
            $vendor->advance_amt = (float) $vendor->advance_amt - $amount;
            $user->wallet = (float) $user->wallet + $amount;
            $vendor->save();
            $user->save();
        });
    }

    private function applyDebit($model, string $field, float $delta, string $message): void
    {
        $next = (float) $model->{$field} - $delta;
        if ($next < 0) {
            throw ValidationException::withMessages(['paid_amt' => $message]);
        }
        $model->{$field} = $next;
        $model->save();
    }

    private function consumeExtra(string $foreignKey, int $entityId, float $amount, ?int $projectId): void
    {
        $sources = Expenses::where($foreignKey, $entityId)
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->where('extra_amt', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $remaining = $amount;
        foreach ($sources as $source) {
            if ($remaining <= 0) {
                break;
            }
            $used = min($remaining, (float) $source->extra_amt);
            $source->extra_amt = (float) $source->extra_amt - $used;
            $source->save();
            $remaining -= $used;
        }
    }
}
