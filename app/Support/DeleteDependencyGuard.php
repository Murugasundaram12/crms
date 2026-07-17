<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteDependencyGuard
{
    /**
     * @param  array<int, array{table: string, column: string, label: string}>  $references
     * @return array{blocked: bool, label: string|null}
     */
    public static function firstBlockingReference(int|string $id, array $references): array
    {
        foreach ($references as $reference) {
            if (! Schema::hasTable($reference['table']) || ! Schema::hasColumn($reference['table'], $reference['column'])) {
                continue;
            }

            if (DB::table($reference['table'])->where($reference['column'], $id)->exists()) {
                return [
                    'blocked' => true,
                    'label' => $reference['label'],
                ];
            }
        }

        return [
            'blocked' => false,
            'label' => null,
        ];
    }

    public static function message(string $recordName, string $usedIn): string
    {
        return "{$recordName} is already used in {$usedIn} and cannot be deleted.";
    }
}
