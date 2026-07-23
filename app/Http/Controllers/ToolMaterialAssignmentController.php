<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ToolMaterial;
use App\Models\ToolMaterialAssignment;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ToolMaterialAssignmentController extends Controller
{
    private const TRANSACTION_TYPES = [
        'purchase' => 'Purchase',
        'issue_to_site' => 'Issue to Site',
        'return_to_office' => 'Return to Office',
        'site_to_site' => 'Site to Site',
        'return_to_vendor' => 'Return to Vendor',
        'damage_wastage' => 'Damage / Wastage',
    ];

    private const STATUSES = [
        'draft' => 'Draft',
        'transferred' => 'Transferred',
        'returned' => 'Returned',
    ];

    public function index(Request $request): View
    {
        $query = ToolMaterialAssignment::query()
            ->with(['toolMaterial.assignments.fromProject', 'toolMaterial.assignments.toProject', 'fromProject', 'toProject', 'vendor', 'handler']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('vehicle_no', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('toolMaterial', fn($toolQuery) => $toolQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('fromProject', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('toProject', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('tool_material_id')) {
            $query->where('tool_material_id', $request->integer('tool_material_id'));
        }

        if ($request->filled('project_id')) {
            $projectId = $request->integer('project_id');
            $query->where(function ($projectQuery) use ($projectId) {
                $projectQuery
                    ->where('from_project_id', $projectId)
                    ->orWhere('to_project_id', $projectId);
            });
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->string('transaction_type')->toString());
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $query->whereIn('status', $status === 'returned' ? ['returned', 'completed'] : [$status]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transferred_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transferred_at', '<=', $request->date('date_to')->toDateString());
        }

        $assignments = $query
            ->latest('transferred_at')
            ->paginate((int) $request->input('paginate', 10));

        $summaryQuery = clone $query;
        $summaryItems = $summaryQuery->get();

        return view('pages.tool_material_assignments.index', [
            'assignments' => $assignments,
            'toolsMaterials' => $this->toolsMaterials(),
            'projects' => $this->projects(),
            'transactionTypes' => self::TRANSACTION_TYPES,
            'statuses' => self::STATUSES,
            'summary' => [
                'transactions' => $summaryItems->count(),
                'completed' => $summaryItems->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->count(),
                'quantity' => $summaryItems->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->sum('quantity'),
                'amount' => $summaryItems->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->sum('amount'),
                'vendor_returns' => $summaryItems->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->where('transaction_type', 'return_to_vendor')->sum('amount'),
            ],
        ]);
    }

    public function create(): View
    {
        $selectedToolMaterialId = request()->integer('tool_material_id') ?: null;
        $prefill = request()->only([
            'transaction_type',
            'source_type',
            'destination_type',
            'from_project_id',
            'to_project_id',
            'vendor_id',
            'quantity',
            'rate',
            'amount',
            'receiver_name',
            'vehicle_no',
            'purpose',
            'notes',
            'lock_transaction',
            'status',
        ]);

        return view('pages.tool_material_assignments.create', [
            'toolsMaterials' => $this->toolsMaterials($selectedToolMaterialId),
            'projects' => $this->projects(),
            'vendors' => $this->vendors(),
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
            'transactionTypes' => self::TRANSACTION_TYPES,
            'statuses' => self::STATUSES,
            'selectedToolMaterialId' => $selectedToolMaterialId,
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAssignment($request);

        DB::transaction(function () use ($validated) {
            $assignment = ToolMaterialAssignment::query()->create($validated);
            $this->applyVendorReturnBalance($assignment, 1);
        });

        return redirect()->route('tools-material-assignments.index')->with('success', 'Tool / material transfer saved successfully.');
    }

    public function edit(ToolMaterialAssignment $toolsMaterialAssignment): View
    {
        return view('pages.tool_material_assignments.edit', [
            'assignment' => $toolsMaterialAssignment,
            'toolsMaterials' => $this->toolsMaterials((int) $toolsMaterialAssignment->tool_material_id),
            'projects' => $this->projects(),
            'vendors' => $this->vendors(),
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
            'transactionTypes' => self::TRANSACTION_TYPES,
            'statuses' => self::STATUSES,
            'selectedToolMaterialId' => null,
            'prefill' => [],
        ]);
    }

    public function update(Request $request, ToolMaterialAssignment $toolsMaterialAssignment): RedirectResponse
    {
        $validated = $this->validateAssignment($request, $toolsMaterialAssignment);

        DB::transaction(function () use ($toolsMaterialAssignment, $validated) {
            $this->applyVendorReturnBalance($toolsMaterialAssignment, -1);
            $toolsMaterialAssignment->update($validated);
            $this->applyVendorReturnBalance($toolsMaterialAssignment->fresh(), 1);
        });

        return redirect()->route('tools-material-assignments.index')->with('success', 'Tool / material transfer updated successfully.');
    }

    public function destroy(ToolMaterialAssignment $toolsMaterialAssignment): RedirectResponse
    {
        DB::transaction(function () use ($toolsMaterialAssignment) {
            $this->applyVendorReturnBalance($toolsMaterialAssignment, -1);
            $toolsMaterialAssignment->delete();
        });

        return redirect()->route('tools-material-assignments.index')->with('success', 'Tool / material transfer deleted successfully.');
    }

    private function validateAssignment(Request $request, ?ToolMaterialAssignment $editingAssignment = null): array
    {
        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'reference_no' => ['nullable', 'string', 'max:100', Rule::unique('tool_material_assignments', 'reference_no')->ignore($request->route('toolsMaterialAssignment')?->id)],
            'status' => ['nullable', Rule::in([...array_keys(self::STATUSES), 'completed'])],
            'from_project_id' => ['nullable', 'exists:projects,id'],
            'to_project_id' => ['nullable', 'exists:projects,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'transaction_type' => ['required', Rule::in(array_keys(self::TRANSACTION_TYPES))],
            'source_type' => ['nullable', Rule::in(['office', 'site', 'vendor'])],
            'destination_type' => ['nullable', Rule::in(['office', 'site', 'vendor', 'wastage'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'rate' => ['required', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'transferred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'tool_material_id' => 'tool name',
            'from_project_id' => 'site name',
            'to_project_id' => 'to site',
            'vendor_id' => 'vendor',
            'transaction_type' => 'transaction type',
            'reference_no' => 'reference number',
            'transferred_at' => 'date and time',
        ]);

        $validated['reference_no'] = filled($validated['reference_no'] ?? null)
            ? $validated['reference_no']
            : $this->nextReferenceNumber();
        $validated['status'] = filled($validated['status'] ?? null)
            ? $validated['status']
            : $this->defaultAssignmentStatus($validated['transaction_type'] ?? null);
        $validated['handled_by'] = Auth::id();
        $amount = (float) ($validated['amount'] ?? 0);
        if ($amount <= 0 && (float) $validated['rate'] > 0) {
            $amount = (float) $validated['quantity'] * (float) $validated['rate'];
        }

        $validated['amount'] = round($amount, 2);
        $validated['unit'] = ToolMaterial::query()->whereKey($validated['tool_material_id'])->value('unit') ?: 'Nos';
        $validated['transfer_type'] = $validated['transaction_type'];

        $this->normalizeTransactionLocations($validated);
        if (ToolMaterialAssignment::isStockEffectiveStatus($validated['status'])) {
            $this->ensureStockAvailable($validated, $editingAssignment);
        }

        return $validated;
    }

    private function normalizeTransactionLocations(array &$validated): void
    {
        match ($validated['transaction_type']) {
            'purchase' => $this->normalizePurchase($validated),
            'issue_to_site' => $this->normalizeIssueToSite($validated),
            'return_to_office' => $this->normalizeReturnToOffice($validated),
            'site_to_site' => $this->normalizeSiteToSite($validated),
            'return_to_vendor' => $this->normalizeReturnToVendor($validated),
            'damage_wastage' => $this->normalizeDamageWastage($validated),
        };
    }

    private function normalizePurchase(array &$validated): void
    {
        $validated['source_type'] = 'vendor';
        $validated['destination_type'] = $validated['destination_type'] === 'site' ? 'site' : 'office';
        $validated['from_project_id'] = null;

        if (empty($validated['vendor_id'])) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor is required for purchase.']);
        }

        if ($validated['destination_type'] === 'site' && empty($validated['to_project_id'])) {
            throw ValidationException::withMessages(['to_project_id' => 'Site is required when purchase is directly added to site.']);
        }
    }

    private function normalizeIssueToSite(array &$validated): void
    {
        $validated['source_type'] = 'office';
        $validated['destination_type'] = 'site';
        $validated['from_project_id'] = null;

        if (empty($validated['to_project_id'])) {
            throw ValidationException::withMessages(['to_project_id' => 'Site is required for issue to site.']);
        }
    }

    private function normalizeReturnToOffice(array &$validated): void
    {
        $validated['source_type'] = 'site';
        $validated['destination_type'] = 'office';
        $validated['to_project_id'] = null;

        if (empty($validated['from_project_id'])) {
            throw ValidationException::withMessages(['from_project_id' => 'Site is required for return to office.']);
        }
    }

    private function normalizeSiteToSite(array &$validated): void
    {
        $validated['source_type'] = 'site';
        $validated['destination_type'] = 'site';

        if (empty($validated['from_project_id']) || empty($validated['to_project_id'])) {
            throw ValidationException::withMessages(['to_project_id' => 'From site and to site are required for site to site transfer.']);
        }

        if ((int) $validated['from_project_id'] === (int) $validated['to_project_id']) {
            throw ValidationException::withMessages(['to_project_id' => 'To site must be different from from site.']);
        }
    }

    private function normalizeReturnToVendor(array &$validated): void
    {
        $validated['source_type'] = $validated['source_type'] === 'site' ? 'site' : 'office';
        $validated['destination_type'] = 'vendor';
        $validated['to_project_id'] = null;

        if (empty($validated['vendor_id'])) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor is required for return to vendor.']);
        }

        if ($validated['source_type'] === 'site' && empty($validated['from_project_id'])) {
            throw ValidationException::withMessages(['from_project_id' => 'Site is required when returning to vendor from site.']);
        }
    }

    private function normalizeDamageWastage(array &$validated): void
    {
        $validated['source_type'] = $validated['source_type'] === 'site' ? 'site' : 'office';
        $validated['destination_type'] = 'wastage';
        $validated['to_project_id'] = null;
        $validated['vendor_id'] = null;

        if ($validated['source_type'] === 'site' && empty($validated['from_project_id'])) {
            throw ValidationException::withMessages(['from_project_id' => 'Site is required for site damage / wastage.']);
        }
    }

    private function applyVendorReturnBalance(ToolMaterialAssignment $assignment, int $direction): void
    {
        if (! ToolMaterialAssignment::isStockEffectiveStatus($assignment->status) || $assignment->transaction_type !== 'return_to_vendor' || ! $assignment->vendor_id || (float) $assignment->amount <= 0) {
            return;
        }

        app(CrmBalanceService::class)->adjustVendorAdvance((int) $assignment->vendor_id, (float) $assignment->amount * $direction);
    }

    private function toolsMaterials(?int $selectedToolMaterialId = null)
    {
        return ToolMaterial::query()
            ->with(['assignments.fromProject', 'assignments.toProject'])
            ->where(function ($query) use ($selectedToolMaterialId): void {
                $query->where('active_status', true);

                if ($selectedToolMaterialId) {
                    $query->orWhere('id', $selectedToolMaterialId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name', 'project_code']);
    }

    private function vendors()
    {
        return Vendor::query()->orderBy('name')->get(['id', 'name']);
    }

    private function ensureStockAvailable(array $validated, ?ToolMaterialAssignment $editingAssignment = null): void
    {
        $source = $this->stockSourceKey($validated);

        if (! $source) {
            return;
        }

        $material = ToolMaterial::query()
            ->with(['assignments.fromProject', 'assignments.toProject'])
            ->findOrFail($validated['tool_material_id']);

        $balances = $material->stockBalances();

        if ($editingAssignment && $editingAssignment->tool_material_id === (int) $validated['tool_material_id']) {
            foreach ($editingAssignment->load(['fromProject', 'toProject'])->locationEffects() as $effect) {
                if (! isset($balances[$effect['key']])) {
                    $balances[$effect['key']] = [
                        'label' => $effect['label'],
                        'quantity' => 0.0,
                        'amount' => 0.0,
                    ];
                }

                $balances[$effect['key']]['quantity'] -= $effect['quantity'];
                $balances[$effect['key']]['amount'] -= $effect['amount'];
            }
        }

        $available = (float) ($balances[$source]['quantity'] ?? 0);

        if ($available < (float) $validated['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock. Available quantity is ' . number_format($available, 2) . ' ' . $material->unit . '.',
            ]);
        }
    }

    private function stockSourceKey(array $validated): ?string
    {
        return match ($validated['transaction_type']) {
            'issue_to_site' => 'office',
            'return_to_office', 'site_to_site' => 'site:' . (int) $validated['from_project_id'],
            'return_to_vendor', 'damage_wastage' => ($validated['source_type'] ?? 'office') === 'site'
                ? 'site:' . (int) $validated['from_project_id']
                : 'office',
            default => null,
        };
    }

    private function defaultAssignmentStatus(?string $transactionType): string
    {
        return match ($transactionType) {
            'purchase',
            'issue_to_site',
            'return_to_office',
            'site_to_site',
            'return_to_vendor',
            'damage_wastage' => 'transferred',
            default => 'draft',
        };
    }

    private function nextReferenceNumber(): string
    {
        $nextId = ((int) ToolMaterialAssignment::query()->max('id')) + 1;

        do {
            $reference = 'TM-' . now()->format('ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (ToolMaterialAssignment::query()->where('reference_no', $reference)->exists());

        return $reference;
    }
}
