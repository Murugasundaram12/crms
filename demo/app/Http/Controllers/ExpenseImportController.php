<?php

namespace App\Http\Controllers;

use App\Services\ExpenseImportService;
use Illuminate\Http\Request;

class ExpenseImportController extends Controller
{
    public function store(Request $request, ExpenseImportService $importer)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:general,labour,vendor'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $summary = $importer->import($request->file('file'), $validated['type']);

        $message = "Import completed as {$summary['type']} expenses. Total: {$summary['total']}, Imported: {$summary['imported']}, Skipped: {$summary['skipped']}, Duplicates: {$summary['duplicates']}.";
        if (!empty($summary['errors'])) {
            $message .= ' First issues: ' . implode(' | ', array_slice($summary['errors'], 0, 5));
        }

        return back()->with('expenses-popup', $message);
    }
}
