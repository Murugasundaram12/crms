<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Employee;
use App\Models\Labour;
use App\Models\PaymentMethod;
use App\Models\TransferDetails;
use App\Models\Vendor;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferDetailsController extends Controller
{
    public function index(Request $request)
    {
        $query = TransferDetails::query()->where('delete_status', false)->with(['paymentMethod', 'labour']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('transfer_type', 'like', "%{$search}%")
                    ->orWhere('payment_mode', 'like', "%{$search}%")
                    ->orWhereHas('paymentMethod', fn($pm) => $pm->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('labour', fn($l) => $l->where('name', 'like', "%{$search}%"))
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from_date')) {
            // yyyy-mm-dd
            $query->whereDate('current_date', '>=', $request->string('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('current_date', '<=', $request->string('to_date'));
        }

        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', $request->string('transfer_type'));
        }

        $paginate = (int) $request->get('paginate', 10);
        $transfers = $query->latest()->paginate($paginate);

        return view('pages.transfers.index', [
            'transfers' => $transfers,
        ]);
    }

    public function create()
    {
        $employees = Employee::query()->latest()->get();
        $vendors = Vendor::query()->latest()->get();
        $labours = Labour::query()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('pages.transfers.create', [
            'employees' => $employees,
            'vendors' => $vendors,
            'labours' => $labours,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTransfer($request);

        $paymentMethod = PaymentMethod::find($validated['payment_method_id']);
        $validated['payment_mode'] = $paymentMethod?->name ?? 'Cash';
        $validated['user_id'] = Auth::id();
        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        DB::transaction(function () use ($validated) {
            $transfer = TransferDetails::create($validated);
            $this->applyTransferBalances($transfer, 1, $validated['description'] ?? null);
        });

        return redirect()->route('transfers.index')->with('success', 'Transfer added successfully.');
    }

    public function edit(string $id)
    {
        $transfer = TransferDetails::where('id', $id)->where('delete_status', false)->firstOrFail();
        $employees = Employee::query()->latest()->get();
        $vendors = Vendor::query()->latest()->get();
        $labours = Labour::query()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('pages.transfers.edit', [
            'transfer' => $transfer,
            'employees' => $employees,
            'vendors' => $vendors,
            'labours' => $labours,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $transfer = TransferDetails::where('id', $id)->where('delete_status', false)->firstOrFail();

        $validated = $this->validateTransfer($request);

        $paymentMethod = PaymentMethod::find($validated['payment_method_id']);
        $validated['payment_mode'] = $paymentMethod?->name ?? 'Cash';

        DB::transaction(function () use ($transfer, $validated, $request) {
            $this->applyTransferBalances($transfer, -1, 'Reversal for transfer update');

            $transfer->fill($validated);
            $transfer->current_date = $this->parseDateToYmd($request->string('current_date'));
            $transfer->current_time = $request->string('current_time');
            $transfer->save();

            $this->applyTransferBalances($transfer, 1, $validated['description'] ?? null);
        });

        return redirect()->route('transfers.index')->with('success', 'Transfer updated successfully.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $transfer = TransferDetails::where('id', $id)->where('delete_status', false)->firstOrFail();

        DB::transaction(function () use ($transfer) {
            if ($transfer->transfer_type === 'labour' && $transfer->labour_id) {
                $labour = Labour::query()->where('id', $transfer->labour_id)->lockForUpdate()->first();
                if ($labour && (float) $labour->advance_amt < (float) $transfer->amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Cannot delete transfer. Labour has already consumed part of this advance balance.',
                    ]);
                }
            }

            $this->applyTransferBalances($transfer, -1, 'Deleted transfer reversal');

            $transfer->delete_status = true;
            $transfer->active_status = false;
            $transfer->save();
        });

        return redirect()->route('transfers.index')->with('success', 'Transfer deleted successfully.');
    }

    private function validateTransfer(Request $request): array
    {
        $validated = $request->validate([
            'transfer_type' => ['required', 'in:employee,vendor,labour'],
            'employee_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
            'labour_id' => ['nullable', 'integer'],

            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'current_date' => ['required', 'date_format:Y-m-d'],
            'current_time' => ['required', 'string', 'max:20'],
        ], [], [
            'employee_id' => 'Employee',
            'vendor_id' => 'Vendor',
            'labour_id' => 'Labour',
            'payment_method_id' => 'Payment Method',
        ]);

        if ($validated['transfer_type'] === 'employee' && empty($validated['employee_id'])) {
            throw ValidationException::withMessages(['employee_id' => 'Employee is required for employee transfer.']);
        }

        if ($validated['transfer_type'] === 'vendor' && empty($validated['vendor_id'])) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor is required for vendor transfer.']);
        }

        if ($validated['transfer_type'] === 'labour') {
            if (empty($validated['labour_id'])) {
                throw ValidationException::withMessages(['labour_id' => 'Labour is required for labour transfer.']);
            }
            if (! Labour::query()->where('id', $validated['labour_id'])->exists()) {
                throw ValidationException::withMessages(['labour_id' => 'Selected labour does not exist.']);
            }
        }

        $validated['employee_id'] = $validated['transfer_type'] === 'employee' ? $validated['employee_id'] : null;
        $validated['vendor_id'] = $validated['transfer_type'] === 'vendor' ? $validated['vendor_id'] : null;
        $validated['labour_id'] = $validated['transfer_type'] === 'labour' ? $validated['labour_id'] : null;

        return $validated;
    }

    private function applyTransferBalances(TransferDetails $transfer, int $direction, ?string $customNotes = null): void
    {
        $amount = (float) $transfer->amount * $direction;
        $balanceService = app(CrmBalanceService::class);

        if ($amount > 0) {
            $balanceService->debitUserWallet((int) $transfer->user_id, $amount, 'Transfer debit', 'transfer', (int) $transfer->id);
        } elseif ($amount < 0) {
            $balanceService->creditUserWallet((int) $transfer->user_id, abs($amount), 'Transfer rollback credit', 'transfer', (int) $transfer->id);
        }

        if ($transfer->transfer_type === 'vendor' && $transfer->vendor_id) {
            $balanceService->adjustVendorAdvance((int) $transfer->vendor_id, $amount);
            return;
        }

        if ($transfer->transfer_type === 'employee' && $transfer->employee_id) {
            $balanceService->adjustEmployeeWallet((int) $transfer->employee_id, $amount);
            return;
        }

        if ($transfer->transfer_type === 'labour' && $transfer->labour_id) {
            Labour::query()->where('id', $transfer->labour_id)->lockForUpdate()->first();

            $balanceService->adjustLabourAdvance((int) $transfer->labour_id, $amount);

            $entryType = $direction > 0 ? 'credit' : 'withdraw';
            $defaultNote = $direction > 0
                ? 'Wallet Transfer from Employee #' . $transfer->user_id
                : 'Reversal for Wallet Transfer #' . $transfer->id;
            $note = $customNotes ? $defaultNote . ': ' . $customNotes : $defaultNote;

            AdvanceHistory::create([
                'labour_id' => $transfer->labour_id,
                'amount' => abs((float) $transfer->amount),
                'entry_type' => $entryType,
                'notes' => $note,
                'user_id' => $transfer->user_id,
                'current_date' => $transfer->current_date ? \Carbon\Carbon::parse($transfer->current_date)->toDateString() : now()->toDateString(),
                'current_time' => $transfer->current_time ?? now()->format('H:i:s'),
            ]);
        }
    }

    private function parseDateToYmd(string $date): string
    {
        // dd/mm/yyyy
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Throwable $e) {
            // fallback
            return $date;
        }
    }
}
