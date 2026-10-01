<?php

namespace App\Services\Excel\Imports;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MainCategory;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use App\Services\CrmBalanceService;
use App\Services\Excel\ExcelImportDefinition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExpenseImport implements ExcelImportDefinition
{
    public function module(): string
    {
        return 'expenses';
    }

    public function requiredHeaders(): array
    {
        return ['date', 'category', 'amount'];
    }

    public function requiredHeaderGroups(): array
    {
        return [
            ['date', 'paiddate', 'expensedate', 'currentdate'],
            ['category', 'categoryname'],
            ['amount', 'expenseamount', 'totalamount', 'total'],
        ];
    }

    public function aliases(): array
    {
        return [
            'paiddate' => 'date',
            'expensedate' => 'date',
            'currentdate' => 'date',
            'maincategoryname' => 'maincategory',
            'categoryname' => 'category',
            'projectname' => 'project',
            'projectcode' => 'project',
            'expenseamount' => 'amount',
            'totalamount' => 'amount',
            'total' => 'amount',
            'paidamt' => 'paidamount',
            'paid' => 'paidamount',
            'paymentmode' => 'paymentmethod',
            'paymentmodeid' => 'paymentmethod',
            'paymentmethodid' => 'paymentmethod',
            'payment' => 'paymentmethod',
            'remarks' => 'description',
            'remark' => 'description',
            'notes' => 'description',
            'note' => 'description',
            'member' => 'employee',
            'addedby' => 'employee',
            'user' => 'employee',
            'username' => 'employee',
        ];
    }

    public function importRow(array $row, int $rowNumber): string
    {
        // 1. Validate and resolve Date
        $dateVal = $row['date'] ?? null;
        $paidDate = $this->parseDate($dateVal);

        // 2. Validate and resolve Category
        $categoryRef = trim((string) ($row['category'] ?? ''));
        if ($categoryRef === '') {
            throw new \InvalidArgumentException('Category is required.');
        }

        $category = is_numeric($categoryRef)
            ? Category::find((int) $categoryRef)
            : Category::whereRaw('UPPER(name) = ?', [mb_strtoupper($categoryRef)])->first();

        if (! $category) {
            throw new \InvalidArgumentException("Category reference '{$categoryRef}' was not found.");
        }
        $categoryId = $category->id;

        // 3. Resolve Main Category (optional)
        $mainCatRef = trim((string) ($row['maincategory'] ?? ''));
        $mainCategoryId = null;
        if ($mainCatRef !== '') {
            $mainCategory = is_numeric($mainCatRef)
                ? MainCategory::find((int) $mainCatRef)
                : MainCategory::whereRaw('UPPER(name) = ?', [mb_strtoupper($mainCatRef)])->first();

            if (! $mainCategory) {
                throw new \InvalidArgumentException("Main category reference '{$mainCatRef}' was not found.");
            }
            $mainCategoryId = $mainCategory->id;
        } else {
            $mainCategoryId = $category->main_category_id ?? null;
        }

        // 4. Resolve Project (optional)
        $projectRef = trim((string) ($row['project'] ?? ''));
        $projectId = null;
        if ($projectRef !== '') {
            $project = is_numeric($projectRef)
                ? Project::find((int) $projectRef)
                : Project::where('name', $projectRef)->orWhere('project_code', $projectRef)->first();

            if (! $project) {
                throw new \InvalidArgumentException("Project reference '{$projectRef}' was not found.");
            }
            $projectId = $project->id;
        }

        // 5. Validate Amount
        $amountVal = isset($row['amount']) ? str_replace(',', '', trim((string) $row['amount'])) : null;
        if ($amountVal === null || $amountVal === '') {
            throw new \InvalidArgumentException('Amount is required.');
        }
        if (! is_numeric($amountVal) || (float) $amountVal < 0) {
            throw new \InvalidArgumentException('Amount must be a non-negative number.');
        }
        $amount = round((float) $amountVal, 2);

        // 6. Validate Paid Amount (optional, defaults to amount)
        $paidAmtVal = isset($row['paidamount']) ? str_replace(',', '', trim((string) $row['paidamount'])) : null;
        if ($paidAmtVal !== null && $paidAmtVal !== '') {
            if (! is_numeric($paidAmtVal) || (float) $paidAmtVal < 0) {
                throw new \InvalidArgumentException('Paid amount must be a non-negative number.');
            }
            $paidAmount = round((float) $paidAmtVal, 2);
        } else {
            $paidAmount = $amount;
        }

        $unpaidAmount = round(max($amount - $paidAmount, 0), 2);
        $extraAmount = round(max($paidAmount - $amount, 0), 2);

        // 7. Resolve Payment Method (optional)
        $pmRef = trim((string) ($row['paymentmethod'] ?? ''));
        $paymentMethodId = null;
        if ($pmRef !== '') {
            $paymentMethod = is_numeric($pmRef)
                ? PaymentMethod::find((int) $pmRef)
                : PaymentMethod::whereRaw('UPPER(name) = ?', [mb_strtoupper($pmRef)])
                    ->orWhereRaw('UPPER(code) = ?', [mb_strtoupper($pmRef)])
                    ->first();

            if (! $paymentMethod) {
                throw new \InvalidArgumentException("Payment method reference '{$pmRef}' was not found.");
            }
            $paymentMethodId = $paymentMethod->id;
        }

        // 8. Resolve Employee / User (optional, defaults to auth user)
        $userRef = trim((string) ($row['employee'] ?? ''));
        $userId = (int) (Auth::id() ?? 1);
        if ($userRef !== '') {
            $user = is_numeric($userRef)
                ? User::find((int) $userRef)
                : User::where('name', $userRef)->orWhere('email', $userRef)->first();

            if (! $user) {
                throw new \InvalidArgumentException("Employee reference '{$userRef}' was not found.");
            }
            $userId = (int) $user->id;
        }

        // 9. Optional Description and Image
        $description = isset($row['description']) && trim((string) $row['description']) !== ''
            ? trim((string) $row['description'])
            : null;

        $image = isset($row['image']) && trim((string) $row['image']) !== ''
            ? trim((string) $row['image'])
            : null;

        if ($image && strlen($image) > 250) {
            throw new \InvalidArgumentException('Image value must not exceed 250 characters.');
        }

        // 10. Duplicate check
        $dateOnly = Carbon::parse($paidDate)->toDateString();
        $existing = Expense::query()
            ->where('category_id', $categoryId)
            ->where('amount', $amount)
            ->whereDate('current_date', $dateOnly)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId), fn ($q) => $q->whereNull('project_id'))
            ->when(filled($description), fn ($q) => $q->where('description', $description), fn ($q) => $q->whereNull('description'))
            ->whereNull('labour_id')
            ->whereNull('vendor_id')
            ->first();

        if ($existing) {
            return 'skipped';
        }

        // 11. Create new Expense record
        $expense = null;
        DB::transaction(function () use (
            &$expense,
            $amount,
            $paidAmount,
            $unpaidAmount,
            $extraAmount,
            $categoryId,
            $mainCategoryId,
            $projectId,
            $userId,
            $paidDate,
            $paymentMethodId,
            $description,
            $image
        ): void {
            $expense = Expense::create([
                'amount' => $amount,
                'main_category_id' => $mainCategoryId,
                'category_id' => $categoryId,
                'project_id' => $projectId,
                'user_id' => $userId,
                'current_date' => $paidDate,
                'description' => $description,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'image' => $image,
                'payment_mode' => $paymentMethodId,
                'payment_method_id' => $paymentMethodId,
            ]);

            if ($paidAmount > 0) {
                app(CrmBalanceService::class)->replaceUserWalletDebit(
                    null,
                    0,
                    $userId,
                    $paidAmount,
                    'Expense payment (Import)',
                    'expense',
                    (int) $expense->id
                );
            }
        });

        return 'created';
    }

    private function parseDate($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            throw new \InvalidArgumentException('Paid date is required.');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // fallback to parsing string
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Invalid date format: '{$value}'");
        }
    }
}
