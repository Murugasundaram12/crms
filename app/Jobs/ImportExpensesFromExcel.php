<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MainCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

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

            $amount = (int) ($row[5] ?? 0);
            $paid = (int) ($row[6] ?? 0);

            $records[] = [
                'amount' => $amount,
                'main_category_id' => $this->mainCategoryId($row[0] ?? null),
                'category_id' => $this->categoryId($row[1] ?? 'GENERAL'),
                'project_id' => null,
                'user_id' => $this->userId,
                'current_date' => $now,
                'description' => $row[9] ?? null,
                'paid_amt' => $paid,
                'unpaid_amt' => max($amount - $paid, 0),
                'extra_amt' => max($paid - $amount, 0),
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
