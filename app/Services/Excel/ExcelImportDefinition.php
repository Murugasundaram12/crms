<?php
namespace App\Services\Excel;
interface ExcelImportDefinition
{
    public function module(): string;
    public function requiredHeaders(): array;
    public function importRow(array $row, int $rowNumber): string;
}
