<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    protected function validatedPageSize(Request $request, string $key, int $default, int $max = 100): int
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return $default;
        }

        $pageSize = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $pageSize === false ? $default : min($pageSize, $max);
    }

    protected function validateDateRange(Request $request, string $from = 'date_from', string $to = 'date_to'): void
    {
        $data = $request->only([$from, $to]);
        $validator = Validator::make($data, [
            $from => ['nullable', 'date_format:Y-m-d'],
            $to => ['nullable', 'date_format:Y-m-d'],
        ]);

        $validator->after(function ($validator) use ($data, $from, $to) {
            if (!empty($data[$from]) && !empty($data[$to]) && $data[$to] < $data[$from]) {
                $validator->errors()->add($to, 'The end date must be on or after the start date.');
            }
        });

        $validator->validate();
    }

    protected function validateDateRangeString(Request $request, string $key = 'date_range'): void
    {
        if (!$request->filled($key)) {
            return;
        }

        $range = $request->string($key)->toString();
        [$from, $to] = str_contains($range, ' - ')
            ? array_map('trim', explode(' - ', $range, 2))
            : [trim($range), null];

        $validator = Validator::make(['from' => $from, 'to' => $to], [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $validator->after(function ($validator) use ($from, $to) {
            if ($to !== null && $to !== '' && $to < $from) {
                $validator->errors()->add('to', 'The end date must be on or after the start date.');
            }
        });

        $validator->validate();
    }

    protected function validateAliasedDateRange(
        array $data,
        string $fromKey,
        string $toKey,
        string $aliasFromKey,
        string $aliasToKey
    ): void {
        $from = $data[$fromKey] ?? $data[$aliasFromKey] ?? null;
        $to = $data[$toKey] ?? $data[$aliasToKey] ?? null;

        Validator::make([
            'from' => $from,
            'to' => $to,
        ], [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ])->validate();
    }
}
