<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\MainCategory;
use App\Models\Project;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportExpensesFromExcel implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 0;
    public int $tries = 1;

    private array $mainCategoryCache = [];
    private array $categoryCache = [];
    private array $projectCache = [];
    private array $labourCache = [];
    private array $vendorCache = [];
    private array $headers = [];
    private string $detectedType = 'general';

    public function __construct(
        private readonly string $path,
        private readonly int $userId
    ) {
    }

    public function handle(): void
    {
        $fullPath = Storage::path($this->path);
        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);

        $worksheetInfo = $reader->listWorksheetInfo($fullPath);
        $totalRows = (int) ($worksheetInfo[0]['totalRows'] ?? 0);
        $chunkSize = 1000;

        try {
            $this->loadHeaders($reader, $fullPath);

            for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkSize) {
                $reader->setReadFilter(new ExpenseImportChunkReadFilter($startRow, $startRow + $chunkSize - 1));
                $spreadsheet = $reader->load($fullPath);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

                $this->insertRows($rows);

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $rows);
            }
        } finally {
            Storage::delete($this->path);
        }
    }

    private function insertRows(array $rows): void
    {
        $now = now();
        $records = [];

        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $amount = $this->number($this->value($row, ['amount'], 5));
            $paid = $this->number($this->value($row, ['paidamount', 'paidamt'], 6));
            $unpaid = $this->number($this->value($row, ['unpaidamount', 'unpaidamt'], 7));
            $extra = $this->number($this->value($row, ['advancedamount', 'advanceamount', 'extraamount'], 8));
            $calculatedUnpaid = $unpaid > 0 || $extra > 0 ? $unpaid : max($amount - $paid, 0);
            $calculatedExtra = $unpaid > 0 || $extra > 0 ? $extra : max($paid - $amount, 0);
            $type = $this->rowType($row);

            $records[] = [
                'amount' => $amount,
                'main_category_id' => $this->mainCategoryId($this->value($row, ['maincategoryname', 'maincategory'], 0)),
                'category_id' => $this->categoryId($this->value($row, ['categoryname', 'category'], 1) ?? 'GENERAL'),
                'project_id' => $this->projectId($this->value($row, ['projectname', 'project'], 4)),
                'user_id' => $this->userId,
                'current_date' => $this->dateTime(
                    $this->value($row, ['paiddate', 'date'], 2),
                    $this->value($row, ['paidtime', 'time'], 3)
                ),
                'description' => $this->description($row),
                'paid_amt' => $paid,
                'unpaid_amt' => $calculatedUnpaid,
                'extra_amt' => $calculatedExtra,
                'payment_mode' => $this->paymentMode($this->value($row, ['paymentmode', 'payment'], $this->detectedType === 'general' ? 10 : 11)),
                'labour_id' => $type === 'labour' ? $this->labourId($this->value($row, ['labourname', 'labour'], 5)) : null,
                'vendor_id' => $type === 'vendor' ? $this->vendorId($this->value($row, ['vendorname', 'vendor'], 5)) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            Expense::query()->insert($chunk);
        }
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function loadHeaders($reader, string $fullPath): void
    {
        $reader->setReadFilter(new ExpenseImportChunkReadFilter(1, 1));
        $spreadsheet = $reader->load($fullPath);
        $headerRow = $spreadsheet->getActiveSheet()->toArray(null, true, true, false)[0] ?? [];
        $spreadsheet->disconnectWorksheets();

        foreach ($headerRow as $index => $header) {
            $key = $this->headerKey($header);
            if ($key !== '') {
                $this->headers[$key] = $index;
            }
        }

        if (array_key_exists('vendorname', $this->headers) || array_key_exists('vendor', $this->headers)) {
            $this->detectedType = 'vendor';
            return;
        }

        if (array_key_exists('labourname', $this->headers) || array_key_exists('labour', $this->headers)) {
            $this->detectedType = 'labour';
        }
    }

    private function mainCategoryId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $key = mb_strtoupper($name);

        if (! array_key_exists($key, $this->mainCategoryCache)) {
            $this->mainCategoryCache[$key] = MainCategory::query()->firstOrCreate(
                ['name' => $key],
                ['status' => 'active']
            )->id;
        }

        return $this->mainCategoryCache[$key];
    }

    private function categoryId(?string $name): int
    {
        $key = mb_strtoupper(trim((string) $name) ?: 'GENERAL');

        if (! array_key_exists($key, $this->categoryCache)) {
            $this->categoryCache[$key] = Category::query()->firstOrCreate(['name' => $key])->id;
        }

        return $this->categoryCache[$key];
    }

    private function projectId(?string $name): ?int
    {
        $key = $this->key($name);
        if ($key === '') {
            return null;
        }

        if (! array_key_exists($key, $this->projectCache)) {
            $this->projectCache[$key] = Project::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $name))])
                ->value('id');
        }

        return $this->projectCache[$key];
    }

    private function labourId(?string $name): ?int
    {
        $name = trim((string) $name);
        $key = $this->key($name);
        if ($key === '') {
            return null;
        }

        if (! array_key_exists($key, $this->labourCache)) {
            $labour = Labour::withTrashed()->firstOrCreate(
                ['name' => $name],
                [
                    'phone' => '',
                    'phone_number' => '',
                    'labour_role_id' => $this->labourRoleId(),
                    'salary' => 0,
                    'advance_amt' => 0,
                ]
            );

            if ($labour->trashed()) {
                $labour->restore();
            }

            $this->labourCache[$key] = $labour->id;
        }

        return $this->labourCache[$key];
    }

    private function vendorId(?string $name): ?int
    {
        $name = trim((string) $name);
        $key = $this->key($name);
        if ($key === '') {
            return null;
        }

        if (! array_key_exists($key, $this->vendorCache)) {
            $this->vendorCache[$key] = Vendor::query()->firstOrCreate(
                ['name' => $name],
                ['phone' => '', 'advance_amt' => 0, 'advance_amount' => 0]
            )->id;
        }

        return $this->vendorCache[$key];
    }

    private function labourRoleId(): int
    {
        return (int) (LabourRole::query()->value('id')
            ?? LabourRole::query()->create([
                'name' => 'Imported',
                'salary_type' => 'daily',
                'salary' => 0,
            ])->id);
    }

    private function value(array $row, array $keys, ?int $fallbackIndex = null): ?string
    {
        $headerFound = false;

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->headers)) {
                $headerFound = true;
                $value = $row[$this->headers[$key]] ?? null;
                if (trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
        }

        if ($headerFound) {
            return null;
        }

        if ($fallbackIndex !== null && trim((string) ($row[$fallbackIndex] ?? '')) !== '') {
            return trim((string) $row[$fallbackIndex]);
        }

        return null;
    }

    private function rowType(array $row): string
    {
        if ($this->value($row, ['vendorname', 'vendor'])) {
            return 'vendor';
        }

        if ($this->value($row, ['labourname', 'labour'])) {
            return 'labour';
        }

        return $this->detectedType;
    }

    private function description(array $row): ?string
    {
        $fallback = $this->detectedType === 'general' ? 9 : 10;
        return $this->value($row, ['description'], $fallback);
    }

    private function number(?string $value): int
    {
        return (int) round((float) preg_replace('/[^0-9.\-]/', '', (string) $value));
    }

    private function dateTime(?string $date, ?string $time): Carbon
    {
        $datePart = $this->parseDate($date)->format('Y-m-d');
        $timePart = $this->parseTime($time);

        return Carbon::parse("{$datePart} {$timePart}");
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
        if (! $time) {
            return '00:00:00';
        }

        if (is_numeric($time)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $time))->format('H:i:s');
        }

        $time = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*([AP]M)$/i', $time, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $second = (int) ($matches[3] ?? 0);
            $period = strtoupper($matches[4]);

            if ($hour <= 12) {
                if ($period === 'PM' && $hour < 12) {
                    $hour += 12;
                }
                if ($period === 'AM' && $hour === 12) {
                    $hour = 0;
                }
            }

            return sprintf('%02d:%02d:%02d', min($hour, 23), min($minute, 59), min($second, 59));
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $matches)) {
            return sprintf(
                '%02d:%02d:%02d',
                min((int) $matches[1], 23),
                min((int) $matches[2], 59),
                min((int) ($matches[3] ?? 0), 59)
            );
        }

        return Carbon::parse($time)->format('H:i:s');
    }

    private function paymentMode(?string $value): ?int
    {
        $key = $this->key($value);
        if ($key === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return [
            'cash' => 1,
            'banktransfer' => 2,
            'bank' => 2,
            'upi' => 3,
            'phonepe' => 3,
            'gpay' => 3,
            'googlepay' => 3,
            'cheque' => 4,
            'check' => 4,
            'card' => 5,
        ][$key] ?? null;
    }

    private function headerKey($value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim((string) $value)));
    }

    private function key(?string $value): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim((string) $value)));
    }
}

class ExpenseImportChunkReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
