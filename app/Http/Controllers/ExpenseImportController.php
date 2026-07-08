<?php

namespace App\Http\Controllers;

use App\Jobs\ImportExpensesFromExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpenseImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'Please choose an Excel or CSV file to import.',
            'file.mimes' => 'Upload only XLSX, XLS, or CSV files.',
            'file.max' => 'The import file must be 10 MB or smaller.',
        ]);

        $path = $request->file('file')->store('imports');

        ImportExpensesFromExcel::dispatch($path, (int) Auth::id());
        $this->startQueueWorkerOnce();

        return back()->with('success', 'Expense import uploaded successfully. Processing will continue in the background.');
    }

    private function startQueueWorkerOnce(): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $command = sprintf(
            '"%s" "%s" queue:work --once --timeout=0 --tries=1',
            $php,
            $artisan
        );

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen('start /B "" ' . $command . ' > NUL 2>&1', 'r'));
                return;
            }

            exec($command . ' > /dev/null 2>&1 &');
        } catch (\Throwable $exception) {
            Log::warning('Unable to auto-start expense import queue worker.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
