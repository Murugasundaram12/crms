<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expenses;
use App\Models\Labour;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\ProjectDetails;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ExpenseAmounts;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExpenseImportService
{
    public function import(UploadedFile $file, string $type): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $rawRows = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, true, true);

        if (count($rawRows) < 2) {
            return $this->summary(0, 0, 0, ['File has no data rows.']);
        }

        $headers = $this->headers(array_shift($rawRows));
        $type = $this->detectType($headers, $type);
        $lookups = $this->lookups();
        $summary = $this->summary(count($rawRows), 0, 0, [], $type);

        DB::transaction(function () use ($rawRows, $headers, $type, $lookups, &$summary) {
            foreach ($rawRows as $index => $row) {
                $rowNumber = ((int) $index) + 2;
                $data = $this->rowData($row, $headers);

                if ($this->isEmptyRow($data)) {
                    $summary['skipped']++;
                    continue;
                }

                $resolved = $this->resolveRow($data, $type, $lookups, (int) $rowNumber);
                if (!$resolved['ok']) {
                    $summary['skipped']++;
                    $summary['errors'][] = $resolved['message'];
                    continue;
                }

                $expenseData = $resolved['data'];
                if ($this->duplicateExists($expenseData)) {
                    $summary['duplicates']++;
                    $summary['skipped']++;
                    continue;
                }

                Expenses::create($expenseData);
                $summary['imported']++;
            }
        });

        return $summary;
    }

    private function resolveRow(array $data, string $type, array $lookups, int $rowNumber): array
    {
        $mainCategoryName = $this->value($data, ['maincategoryname', 'maincategory']);
        $categoryName = $this->value($data, ['categoryname', 'category']);
        $projectName = $this->value($data, ['projectname', 'project']);
        $paymentName = $this->value($data, ['paymentmode', 'payment']);

        $mainCategoryId = $lookups['main_categories'][$this->key($mainCategoryName)] ?? null;
        if (!$mainCategoryId) {
            return $this->rowError($rowNumber, "Main category not found: {$mainCategoryName}");
        }

        $categoryId = $lookups['categories'][$mainCategoryId . '|' . $this->key($categoryName)]
            ?? $lookups['categories_by_name'][$this->key($categoryName)]
            ?? null;
        if (!$categoryId) {
            return $this->rowError($rowNumber, "Category not found: {$categoryName}");
        }

        $projectId = $lookups['projects'][$this->key($projectName)] ?? null;
        if (!$projectId) {
            return $this->rowError($rowNumber, "Project not found: {$projectName}");
        }

        $paymentId = $lookups['payments'][$this->key($paymentName)] ?? null;
        if (!$paymentId) {
            return $this->rowError($rowNumber, "Payment mode not found: {$paymentName}");
        }

        $labourId = null;
        $vendorId = null;
        if ($type === 'labour') {
            $labourName = $this->value($data, ['labourname', 'labour']);
            $labourId = $lookups['labours'][$this->key($labourName)] ?? null;
            if (!$labourId) {
                return $this->rowError($rowNumber, "Labour not found: {$labourName}");
            }
        }
        if ($type === 'vendor') {
            $vendorName = $this->value($data, ['vendorname', 'vendor']);
            $vendorId = $lookups['vendors'][$this->key($vendorName)] ?? null;
            if (!$vendorId) {
                return $this->rowError($rowNumber, "Vendor not found: {$vendorName}");
            }
        }

        $amount = $this->number($this->value($data, ['amount']));
        $paid = $this->number($this->value($data, ['paidamount', 'paidamt']));
        if ($amount <= 0) {
            return $this->rowError($rowNumber, 'Amount should be greater than zero.');
        }

        $calculated = ExpenseAmounts::calculate($amount, $paid);
        $unpaid = $this->number($this->value($data, ['unpaidamount', 'unpaidamt']));
        $extra = $this->number($this->value($data, ['advancedamount', 'advanceamount', 'extraamount']));
        if ($unpaid > 0 || $extra > 0) {
            $calculated['unpaid_amt'] = $unpaid;
            $calculated['extra_amt'] = $extra;
        }

        $addedBy = $this->userId($this->value($data, ['addedby']), $lookups) ?? Auth::id();
        $editedBy = $this->userId($this->value($data, ['editedby']), $lookups);
        $advanceEditedBy = $this->userId($this->value($data, ['advanceeditedby']), $lookups);

        return [
            'ok' => true,
            'data' => [
                'main_category_id' => $mainCategoryId,
                'category_id' => $categoryId,
                'project_id' => $projectId,
                'user_id' => $addedBy,
                'current_date' => $this->dateTime($this->value($data, ['paiddate', 'date']), $this->value($data, ['paidtime', 'time'])),
                'amount' => $amount,
                'paid_amt' => $calculated['paid_amt'],
                'unpaid_amt' => $calculated['unpaid_amt'],
                'extra_amt' => $calculated['extra_amt'],
                'payment_mode' => $paymentId,
                'description' => $this->value($data, ['description']),
                'editedBy' => $editedBy,
                'is_advance' => $advanceEditedBy,
                'labour_id' => $labourId,
                'vendor_id' => $vendorId,
            ],
        ];
    }

    private function duplicateExists(array $data): bool
    {
        return Expenses::where('main_category_id', $data['main_category_id'])
            ->where('category_id', $data['category_id'])
            ->where('project_id', $data['project_id'])
            ->where('current_date', $data['current_date'])
            ->where('amount', $data['amount'])
            ->where('paid_amt', $data['paid_amt'])
            ->where('unpaid_amt', $data['unpaid_amt'])
            ->where('extra_amt', $data['extra_amt'])
            ->where('payment_mode', $data['payment_mode'])
            ->where('description', $data['description'])
            ->when($data['labour_id'], fn ($query) => $query->where('labour_id', $data['labour_id']), fn ($query) => $query->whereNull('labour_id'))
            ->when($data['vendor_id'], fn ($query) => $query->where('vendor_id', $data['vendor_id']), fn ($query) => $query->whereNull('vendor_id'))
            ->exists();
    }

    private function lookups(): array
    {
        return [
            'main_categories' => MainCategory::query()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])->all(),
            'categories' => Category::query()->get()->mapWithKeys(fn ($category) => [$category->main_category_id . '|' . $this->key($category->name) => $category->id])->all(),
            'categories_by_name' => Category::query()->get()->mapWithKeys(fn ($category) => [$this->key($category->name) => $category->id])->all(),
            'projects' => ProjectDetails::query()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])->all(),
            'payments' => Payment::query()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])->all(),
            'labours' => Labour::query()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])->all(),
            'vendors' => Vendor::query()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])->all(),
            'users' => User::query()->get()->mapWithKeys(fn ($user) => [$this->key(trim($user->first_name . ' ' . $user->last_name)) => $user->id])->all(),
        ];
    }

    private function headers(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $column => $header) {
            $headers[$column] = $this->headerKey($header);
        }
        return $headers;
    }

    private function rowData(array $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $column => $header) {
            if ($header) {
                $data[$header] = $row[$column] ?? null;
            }
        }
        return $data;
    }

    private function isEmptyRow(array $data): bool
    {
        return collect($data)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function value(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) !== '') {
                return trim((string) $data[$key]);
            }
        }
        return null;
    }

    private function number(?string $value): float
    {
        return (float) str_replace([',', '₹', ' '], '', (string) $value);
    }

    private function userId(?string $name, array $lookups): ?int
    {
        if (!$name) {
            return null;
        }
        return $lookups['users'][$this->key($name)] ?? null;
    }

    private function dateTime(?string $date, ?string $time): string
    {
        $datePart = $this->parseDate($date)->format('Y-m-d');
        $timePart = $this->parseTime($time);
        return "{$datePart} {$timePart}";
    }

    private function parseDate(?string $date): Carbon
    {
        if (is_numeric($date)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $date));
        }
        return Carbon::parse($date ?: now());
    }

    private function parseTime(?string $time): string
    {
        if (!$time) {
            return '00:00:00';
        }
        if (is_numeric($time)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $time))->format('H:i:s');
        }
        return Carbon::parse($time)->format('H:i:s');
    }

    private function key(?string $value): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim((string) $value)));
    }

    private function headerKey($value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim((string) $value)));
    }

    private function rowError(int $rowNumber, string $message): array
    {
        return ['ok' => false, 'message' => "Row {$rowNumber}: {$message}"];
    }

    private function detectType(array $headers, string $fallback): string
    {
        if (in_array('vendorname', $headers, true) || in_array('vendor', $headers, true)) {
            return 'vendor';
        }

        if (in_array('labourname', $headers, true) || in_array('labour', $headers, true)) {
            return 'labour';
        }

        return $fallback;
    }

    private function summary(int $total, int $imported, int $skipped, array $errors, string $type = 'general'): array
    {
        return [
            'type' => $type,
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'duplicates' => 0,
            'errors' => $errors,
        ];
    }
}
