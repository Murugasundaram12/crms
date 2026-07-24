<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Project;
use App\Models\ToolMaterial;
use App\Models\ToolMaterialAssignment;
use App\Models\Unit;
use App\Models\Vendor;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait MobileInventoryEndpoints
{
    private const INVENTORY_TRANSACTION_TYPES = [
        'purchase' => 'Purchase',
        'issue_to_site' => 'Issue to Site',
        'return_to_office' => 'Return to Office',
        'site_to_site' => 'Site to Site',
        'return_to_vendor' => 'Return to Vendor',
        'damage_wastage' => 'Damage / Wastage',
    ];

    private const INVENTORY_STATUSES = [
        'draft' => 'Draft',
        'transferred' => 'Transferred',
        'returned' => 'Returned',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function inventoryOptions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        $materials = ToolMaterial::query()
            ->with(['assignments.fromProject', 'assignments.toProject'])
            ->where('active_status', true)
            ->orderBy('name')
            ->get()
            ->map(fn(ToolMaterial $item) => $this->toolMaterialPayload($item));

        $vendors = Vendor::query()->orderBy('name')->get()->map(fn(Vendor $vendor) => $this->vendorPayload($vendor));

        $units = Schema::hasTable('units')
            ? Unit::query()->active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn(Unit $unit) => [
                'id' => $unit->id,
                'name' => $unit->code, // Use code as option name
            ])
            : [];

        $paymentMethods = \App\Models\PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get()->map(fn($pm) => [
            'id' => $pm->id,
            'name' => $pm->name,
        ]);

        $preorderStatuses = [];
        foreach (\App\Http\Controllers\PreorderController::STATUSES as $id => $name) {
            $preorderStatuses[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        $categories = Category::query()->orderBy('name')->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        $data = [
            'materials' => $materials,
            'vendors' => $vendors,
            'units' => $units,
            'payment_methods' => $paymentMethods,
            'preorder_statuses' => $preorderStatuses,
            'delivery_statuses' => [
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'partially_delivered', 'name' => 'Partially Delivered'],
                ['id' => 'delivered', 'name' => 'Delivered'],
            ],
            'payment_statuses' => [
                ['id' => 'unpaid', 'name' => 'Unpaid'],
                ['id' => 'partially_paid', 'name' => 'Partially Paid'],
                ['id' => 'paid', 'name' => 'Paid'],
            ],
            'categories' => $categories,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            // Keep old keys for backward compatibility
            'transaction_types' => self::INVENTORY_TRANSACTION_TYPES,
            'transaction_type_options' => $this->enumOptions(self::INVENTORY_TRANSACTION_TYPES),
            'statuses' => self::INVENTORY_STATUSES,
            'status_options' => $this->enumOptions(self::INVENTORY_STATUSES),
            'source_types' => ['office' => 'Office', 'site' => 'Site', 'vendor' => 'Vendor'],
            'source_type_options' => $this->enumOptions(['office' => 'Office', 'site' => 'Site', 'vendor' => 'Vendor']),
            'destination_types' => ['office' => 'Office', 'site' => 'Site', 'vendor' => 'Vendor', 'wastage' => 'Wastage'],
            'destination_type_options' => $this->enumOptions(['office' => 'Office', 'site' => 'Site', 'vendor' => 'Vendor', 'wastage' => 'Wastage']),
            'item_types' => ['material' => 'Material', 'tool' => 'Tool'],
            'item_type_options' => $this->enumOptions(['material' => 'Material', 'tool' => 'Tool']),
            'units' => Schema::hasTable('units') ? Unit::query()->active()->orderBy('name')->get(['id', 'name', 'code']) : [],
            'unit_options' => Schema::hasTable('units')
                ? Unit::query()->active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn(Unit $unit) => [
                    'id' => $unit->id,
                    'value' => $unit->code,
                    'label' => $unit->display_name,
                ])
                : [],
            'tools_materials' => $materials,
            'projects' => $this->scopeProjectsForAppUser(Project::query(), $request->user())
                ->orderBy('name')
                ->get(['id', 'name', 'project_code']),
            'vendors' => $vendors,
        ]);
    }

    public function inventoryItems(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', Rule::in(['tool', 'material'])],
            'low_stock' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'vendor_id' => ['nullable', 'integer'],
            'material_id' => ['nullable', 'integer'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $validated['search'] ?? $validated['q'] ?? null;
        $vendorId = $validated['vendor_id'] ?? null;
        $materialId = $validated['material_id'] ?? null;
        $status = $validated['status'] ?? null;

        $query = ToolMaterial::query()
            ->with(['assignments.fromProject', 'assignments.toProject'])
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($validated['item_type'] ?? null, fn($q, string $type) => $q->where('item_type', $type))
            ->when($materialId, fn($q) => $q->where('id', $materialId))
            ->when($vendorId, fn($q) => $q->whereHas('assignments', fn($inner) => $inner->where('vendor_id', $vendorId)))
            ->when($status !== null, function($q) use ($status) {
                if ($status === 'active' || $status === '1') {
                    $q->where('active_status', true);
                } elseif ($status === 'inactive' || $status === '0') {
                    $q->where('active_status', false);
                }
            })
            ->when($validated['date_from'] ?? null, fn($q) => $q->whereDate('date', '>=', $request->date('date_from')->toDateString()))
            ->when($validated['date_to'] ?? null, fn($q) => $q->whereDate('date', '<=', $request->date('date_to')->toDateString()));

        // Safe sorting
        $allowedSortColumns = ['id', 'name', 'created_at', 'updated_at', 'sku', 'date', 'opening_quantity', 'opening_rate', 'opening_amount', 'reorder_level', 'active_status'];
        $sortBy = in_array($request->input('sort_by'), $allowedSortColumns, true) ? $request->input('sort_by') : 'id';
        $sortOrder = in_array(strtolower($request->input('sort_order', '')), ['asc', 'desc'], true) ? $request->input('sort_order') : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $summaryItems = (clone $query)->get();
        $summary = [
            'items' => $summaryItems->count(),
            'tools' => $summaryItems->where('item_type', 'tool')->count(),
            'materials' => $summaryItems->where('item_type', 'material')->count(),
            'stock_value' => (float) $summaryItems->sum('stock_amount'),
            'low_stock' => $summaryItems->filter(fn(ToolMaterial $item) => $item->is_low_stock)->count(),
        ];

        $items = $query->paginate((int) ($validated['per_page'] ?? 15));
        $collection = $items->getCollection()
            ->filter(fn(ToolMaterial $item) => ! $request->boolean('low_stock') || $item->is_low_stock)
            ->map(fn(ToolMaterial $item) => $this->toolMaterialPayload($item))
            ->values();
        $items->setCollection($collection);

        $paginated = $items->toArray();

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data' => $collection->all(),
            'pagination' => [
                'current_page' => $paginated['current_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
                'last_page' => $paginated['last_page'],
            ],
            // Keep old fields at root for backward compatibility
            ...$paginated,
        ]);
    }

    public function storeInventoryItem(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-create')) {
            return $forbidden;
        }

        $validated = $this->validateInventoryItem($request);
        $validated = $this->normalizeInventoryItemStockFields($validated);
        $validated['active_status'] = $request->boolean('active_status', true);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('tools-materials', 'public');
        }

        $item = ToolMaterial::query()->create($validated);
        $payload = $this->toolMaterialPayload($item->load(['assignments.fromProject', 'assignments.toProject']));

        return response()->json([
            'success' => true,
            'message' => 'Tool / material created successfully.',
            'data' => $payload,
            'tool_material' => $payload,
        ], 201);
    }

    public function showInventoryItem(Request $request, ToolMaterial $toolMaterial)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        $payload = $this->toolMaterialPayload($toolMaterial->load(['assignments.fromProject', 'assignments.toProject']));

        return response()->json([
            'success' => true,
            'data' => $payload,
            'tool_material' => $payload,
        ]);
    }

    public function updateInventoryItem(Request $request, ToolMaterial $toolMaterial)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-edit')) {
            return $forbidden;
        }

        $validated = $this->validateInventoryItem($request);
        $validated = $this->normalizeInventoryItemStockFields($validated);
        $validated['active_status'] = $request->boolean('active_status', true);

        if ($request->hasFile('image')) {
            if ($toolMaterial->image_path) {
                Storage::disk('public')->delete($toolMaterial->image_path);
            }

            $validated['image_path'] = $request->file('image')->store('tools-materials', 'public');
        }

        $toolMaterial->update($validated);
        $payload = $this->toolMaterialPayload($toolMaterial->fresh(['assignments.fromProject', 'assignments.toProject']));

        return response()->json([
            'success' => true,
            'message' => 'Tool / material updated successfully.',
            'data' => $payload,
            'tool_material' => $payload,
        ]);
    }

    public function deleteInventoryItem(Request $request, ToolMaterial $toolMaterial)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-delete')) {
            return $forbidden;
        }

        if ($toolMaterial->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This tool / material has stock transactions. Delete the transactions before deleting this item.',
            ], 409);
        }

        if ($toolMaterial->image_path) {
            Storage::disk('public')->delete($toolMaterial->image_path);
        }

        $toolMaterial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tool / material deleted successfully.',
        ]);
    }

    public function inventoryTransactions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'tool_material_id' => ['nullable', 'exists:tools_materials,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'transaction_type' => ['nullable', Rule::in(array_keys(self::INVENTORY_TRANSACTION_TYPES))],
            'status' => ['nullable', Rule::in(array_keys(self::INVENTORY_STATUSES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ToolMaterialAssignment::query()
            ->with(['toolMaterial.assignments.fromProject', 'toolMaterial.assignments.toProject', 'fromProject', 'toProject', 'vendor', 'handler'])
            ->when($validated['q'] ?? null, function ($q, string $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('receiver_name', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('toolMaterial', fn($toolQuery) => $toolQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['tool_material_id'] ?? null, fn($q, int $id) => $q->where('tool_material_id', $id))
            ->when($validated['vendor_id'] ?? null, fn($q, int $id) => $q->where('vendor_id', $id))
            ->when($validated['transaction_type'] ?? null, fn($q, string $type) => $q->where('transaction_type', $type))
            ->when($validated['status'] ?? null, fn($q, string $status) => $q->where('status', $status));

        if (! blank($validated['project_id'] ?? null)) {
            $projectId = (int) $validated['project_id'];
            $query->where(fn($q) => $q->where('from_project_id', $projectId)->orWhere('to_project_id', $projectId));
        }

        if (! blank($validated['date_from'] ?? null)) {
            $query->whereDate('transferred_at', '>=', $request->date('date_from')->toDateString());
        }

        if (! blank($validated['date_to'] ?? null)) {
            $query->whereDate('transferred_at', '<=', $request->date('date_to')->toDateString());
        }

        $transactions = $query->latest('transferred_at')->paginate((int) ($validated['per_page'] ?? 15));
        $transactions->setCollection($transactions->getCollection()->map(fn(ToolMaterialAssignment $assignment) => $this->toolMaterialTransactionPayload($assignment)));

        return response()->json([
            'summary' => [
                'transactions' => (clone $query)->count(),
                'completed' => (clone $query)->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->count(),
                'quantity' => (float) (clone $query)->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->sum('quantity'),
                'amount' => (float) (clone $query)->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->sum('amount'),
                'vendor_returns' => (float) (clone $query)->whereIn('status', ToolMaterialAssignment::STOCK_EFFECTIVE_STATUSES)->where('transaction_type', 'return_to_vendor')->sum('amount'),
            ],
            ...$transactions->toArray(),
        ]);
    }

    public function storeInventoryTransaction(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-create')) {
            return $forbidden;
        }

        $validated = $this->validateInventoryTransaction($request);

        $assignment = DB::transaction(function () use ($validated) {
            $assignment = ToolMaterialAssignment::query()->create($validated);
            $this->applyInventoryVendorReturnBalance($assignment, 1);

            return $assignment;
        });

        return response()->json([
            'message' => 'Inventory transaction saved successfully.',
            'transaction' => $this->toolMaterialTransactionPayload($assignment->load(['toolMaterial.assignments.fromProject', 'toolMaterial.assignments.toProject', 'fromProject', 'toProject', 'vendor', 'handler'])),
        ], 201);
    }

    public function showInventoryTransaction(Request $request, ToolMaterialAssignment $assignment)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        return response()->json([
            'transaction' => $this->toolMaterialTransactionPayload($assignment->load(['toolMaterial.assignments.fromProject', 'toolMaterial.assignments.toProject', 'fromProject', 'toProject', 'vendor', 'handler'])),
        ]);
    }

    public function updateInventoryTransaction(Request $request, ToolMaterialAssignment $assignment)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-edit')) {
            return $forbidden;
        }

        $validated = $this->validateInventoryTransaction($request, $assignment);

        DB::transaction(function () use ($assignment, $validated) {
            $this->applyInventoryVendorReturnBalance($assignment, -1);
            $assignment->update($validated);
            $this->applyInventoryVendorReturnBalance($assignment->fresh(), 1);
        });

        return response()->json([
            'message' => 'Inventory transaction updated successfully.',
            'transaction' => $this->toolMaterialTransactionPayload($assignment->fresh(['toolMaterial.assignments.fromProject', 'toolMaterial.assignments.toProject', 'fromProject', 'toProject', 'vendor', 'handler'])),
        ]);
    }

    public function deleteInventoryTransaction(Request $request, ToolMaterialAssignment $assignment)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-delete')) {
            return $forbidden;
        }

        DB::transaction(function () use ($assignment) {
            $this->applyInventoryVendorReturnBalance($assignment, -1);
            $assignment->delete();
        });

        return response()->json(['message' => 'Inventory transaction deleted successfully.']);
    }

    private function validateInventoryItem(Request $request): array
    {
        return $request->validate([
            'item_type' => ['required', Rule::in(['tool', 'material'])],
            'sku' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required_if:item_type,material', 'nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'opening_quantity' => ['required_if:item_type,material', 'nullable', 'numeric', 'min:0'],
            'opening_rate' => ['required_if:item_type,material', 'nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'active_status' => ['nullable', 'boolean'],
        ]);
    }

    private function enumOptions(array $options): array
    {
        $payload = [];
        $index = 1;

        foreach ($options as $value => $label) {
            $payload[] = [
                'id' => $index,
                'value' => $value,
                'label' => $label,
            ];
            $index++;
        }

        return $payload;
    }

    private function normalizeInventoryItemStockFields(array $validated): array
    {
        if (($validated['item_type'] ?? null) === 'tool') {
            $validated['unit'] = 'Nos';
        }

        $validated['opening_quantity'] = (float) ($validated['opening_quantity'] ?? 0);
        $validated['opening_rate'] = (float) ($validated['opening_rate'] ?? 0);
        $validated['opening_amount'] = round($validated['opening_quantity'] * $validated['opening_rate'], 2);
        $validated['reorder_level'] = (float) ($validated['reorder_level'] ?? 0);

        return $validated;
    }

    private function validateInventoryTransaction(Request $request, ?ToolMaterialAssignment $assignment = null): array
    {
        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'reference_no' => ['nullable', 'string', 'max:100', Rule::unique('tool_material_assignments', 'reference_no')->ignore($assignment?->id)],
            'status' => ['nullable', Rule::in(array_keys(self::INVENTORY_STATUSES))],
            'from_project_id' => ['nullable', 'exists:projects,id'],
            'to_project_id' => ['nullable', 'exists:projects,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'transaction_type' => ['required', Rule::in(array_keys(self::INVENTORY_TRANSACTION_TYPES))],
            'source_type' => ['nullable', Rule::in(['office', 'site', 'vendor'])],
            'destination_type' => ['nullable', Rule::in(['office', 'site', 'vendor', 'wastage'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'rate' => ['required', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'transferred_at' => ['nullable', 'date'],
        ]);

        $validated['reference_no'] = filled($validated['reference_no'] ?? null)
            ? $validated['reference_no']
            : $this->nextInventoryReferenceNumber();
        $validated['status'] = filled($validated['status'] ?? null)
            ? $validated['status']
            : $this->defaultInventoryStatus($validated['transaction_type'] ?? null);
        $validated['handled_by'] = $request->user()->id;
        $validated['transferred_at'] = $validated['transferred_at'] ?? now();
        $validated['amount'] = round((float) ($validated['amount'] ?? 0) > 0
            ? (float) $validated['amount']
            : (float) $validated['quantity'] * (float) $validated['rate'], 2);
        $validated['unit'] = ToolMaterial::query()->whereKey($validated['tool_material_id'])->value('unit') ?: 'Nos';
        $validated['transfer_type'] = $validated['transaction_type'];

        $this->normalizeInventoryLocations($validated);

        if (ToolMaterialAssignment::isStockEffectiveStatus($validated['status'])) {
            $this->ensureInventoryStockAvailable($validated, $assignment);
        }

        return $validated;
    }

    private function normalizeInventoryLocations(array &$validated): void
    {
        match ($validated['transaction_type']) {
            'purchase' => $this->normalizeInventoryPurchase($validated),
            'issue_to_site' => $this->normalizeInventoryIssueToSite($validated),
            'return_to_office' => $this->normalizeInventoryReturnToOffice($validated),
            'site_to_site' => $this->normalizeInventorySiteToSite($validated),
            'return_to_vendor' => $this->normalizeInventoryReturnToVendor($validated),
            'damage_wastage' => $this->normalizeInventoryDamageWastage($validated),
        };
    }

    private function normalizeInventoryPurchase(array &$validated): void
    {
        $validated['source_type'] = 'vendor';
        $validated['destination_type'] = ($validated['destination_type'] ?? 'office') === 'site' ? 'site' : 'office';
        $validated['from_project_id'] = null;

        if (empty($validated['vendor_id'])) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor is required for purchase.']);
        }

        if ($validated['destination_type'] === 'site' && empty($validated['to_project_id'])) {
            throw ValidationException::withMessages(['to_project_id' => 'Site is required when purchase is directly added to site.']);
        }
    }

    private function normalizeInventoryIssueToSite(array &$validated): void
    {
        $validated['source_type'] = 'office';
        $validated['destination_type'] = 'site';
        $validated['from_project_id'] = null;

        if (empty($validated['to_project_id'])) {
            throw ValidationException::withMessages(['to_project_id' => 'Site is required for issue to site.']);
        }
    }

    private function normalizeInventoryReturnToOffice(array &$validated): void
    {
        $validated['source_type'] = 'site';
        $validated['destination_type'] = 'office';
        $validated['to_project_id'] = null;

        if (empty($validated['from_project_id'])) {
            throw ValidationException::withMessages(['from_project_id' => 'Site is required for return to office.']);
        }
    }

    private function normalizeInventorySiteToSite(array &$validated): void
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

    private function normalizeInventoryReturnToVendor(array &$validated): void
    {
        $validated['source_type'] = ($validated['source_type'] ?? 'office') === 'site' ? 'site' : 'office';
        $validated['destination_type'] = 'vendor';
        $validated['to_project_id'] = null;

        if (empty($validated['vendor_id'])) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor is required for return to vendor.']);
        }

        if ($validated['source_type'] === 'site' && empty($validated['from_project_id'])) {
            throw ValidationException::withMessages(['from_project_id' => 'Site is required when returning to vendor from site.']);
        }
    }

    private function normalizeInventoryDamageWastage(array &$validated): void
    {
        $validated['source_type'] = ($validated['source_type'] ?? 'office') === 'site' ? 'site' : 'office';
        $validated['destination_type'] = 'wastage';
        $validated['to_project_id'] = null;
        $validated['vendor_id'] = null;

        if ($validated['source_type'] === 'site' && empty($validated['from_project_id'])) {
            throw ValidationException::withMessages(['from_project_id' => 'Site is required for site damage / wastage.']);
        }
    }

    private function ensureInventoryStockAvailable(array $validated, ?ToolMaterialAssignment $editingAssignment = null): void
    {
        $source = match ($validated['transaction_type']) {
            'issue_to_site' => 'office',
            'return_to_office', 'site_to_site' => 'site:' . (int) $validated['from_project_id'],
            'return_to_vendor', 'damage_wastage' => ($validated['source_type'] ?? 'office') === 'site'
                ? 'site:' . (int) $validated['from_project_id']
                : 'office',
            default => null,
        };

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

    private function applyInventoryVendorReturnBalance(ToolMaterialAssignment $assignment, int $direction): void
    {
        if (! ToolMaterialAssignment::isStockEffectiveStatus($assignment->status) || $assignment->transaction_type !== 'return_to_vendor' || ! $assignment->vendor_id || (float) $assignment->amount <= 0) {
            return;
        }

        app(CrmBalanceService::class)->adjustVendorAdvance((int) $assignment->vendor_id, (float) $assignment->amount * $direction);
    }

    private function defaultInventoryStatus(?string $transactionType): string
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

    private function nextInventoryReferenceNumber(): string
    {
        $nextId = ((int) ToolMaterialAssignment::query()->max('id')) + 1;

        do {
            $reference = 'TM-' . now()->format('ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (ToolMaterialAssignment::query()->where('reference_no', $reference)->exists());

        return $reference;
    }

    private function toolMaterialPayload(ToolMaterial $item): array
    {
        return [
            'id' => $item->id,
            'item_type' => $item->item_type,
            'sku' => $item->sku,
            'name' => $item->name,
            'unit' => $item->unit,
            'date' => $item->date?->toDateString(),
            'image_path' => $item->image_path,
            'image_url' => $item->image_path ? asset('storage/' . $item->image_path) : null,
            'description' => $item->description,
            'opening_quantity' => (float) $item->opening_quantity,
            'opening_rate' => (float) $item->opening_rate,
            'opening_amount' => (float) $item->opening_amount,
            'reorder_level' => (float) $item->reorder_level,
            'office_stock_quantity' => $item->office_stock_quantity,
            'site_stock_quantity' => $item->site_stock_quantity,
            'stock_quantity' => $item->stock_quantity,
            'stock_amount' => $item->stock_amount,
            'is_low_stock' => $item->is_low_stock,
            'active_status' => (bool) $item->active_status,
            'balances' => array_values($item->stockBalances()),
        ];
    }

    private function toolMaterialTransactionPayload(ToolMaterialAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'reference_no' => $assignment->reference_no,
            'status' => $assignment->status,
            'status_label' => $assignment->statusLabel(),
            'transaction_type' => $assignment->transaction_type,
            'transaction_label' => $assignment->transactionLabel(),
            'tool_material' => $assignment->toolMaterial ? $this->toolMaterialPayload($assignment->toolMaterial) : null,
            'from_project' => $assignment->fromProject ? ['id' => $assignment->fromProject->id, 'name' => $assignment->fromProject->name] : null,
            'to_project' => $assignment->toProject ? ['id' => $assignment->toProject->id, 'name' => $assignment->toProject->name] : null,
            'vendor' => $assignment->vendor ? $this->vendorPayload($assignment->vendor) : null,
            'handled_by' => $assignment->handler ? $this->userPayload($assignment->handler) : null,
            'source_type' => $assignment->source_type,
            'destination_type' => $assignment->destination_type,
            'quantity' => (float) $assignment->quantity,
            'unit' => $assignment->unit,
            'rate' => (float) $assignment->rate,
            'amount' => (float) $assignment->amount,
            'receiver_name' => $assignment->receiver_name,
            'vehicle_no' => $assignment->vehicle_no,
            'purpose' => $assignment->purpose,
            'notes' => $assignment->notes,
            'transferred_at' => $assignment->transferred_at?->toISOString(),
            'created_at' => $assignment->created_at?->toISOString(),
            'updated_at' => $assignment->updated_at?->toISOString(),
        ];
    }
}
